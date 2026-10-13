<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private readonly CartPriceCalculator $prices
    ) {
    }

    /** @return array{CartItem, bool} */
    public function addItem(User $user, int $variantId, int $quantity): array
    {
        try {
            return DB::transaction(
                fn (): array => $this->addItemInTransaction($user, $variantId, $quantity),
                3
            );
        } catch (QueryException $exception) {
            if (! $this->isCartItemUniqueViolation($exception)) {
                throw $exception;
            }
        }

        try {
            return DB::transaction(
                fn (): array => $this->addItemInTransaction($user, $variantId, $quantity),
                3
            );
        } catch (QueryException $exception) {
            if (! $this->isCartItemUniqueViolation($exception)) {
                throw $exception;
            }

            abort(response()->json([
                'success' => false,
                'message' => 'Giỏ hàng vừa được cập nhật đồng thời. Vui lòng thử lại.',
            ], 409));
        }
    }

    public function updateItem(User $user, int $cartItemId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($user, $cartItemId, $quantity): CartItem {
            [$lockedUser, $cart] = $this->lockUserAndCart($user);
            $initialItem = $cart->items()->whereKey($cartItemId)->first();

            abort_unless($initialItem, 404);

            $variant = $this->lockPurchasableVariant((int) $initialItem->product_variant_id);
            $item = $cart->items()
                ->whereKey($cartItemId)
                ->where('product_variant_id', $variant->getKey())
                ->lockForUpdate()
                ->first();

            abort_unless($item, 404);
            $this->assertAvailableQuantity($variant, $quantity);

            $item->update(['quantity' => $quantity]);

            return $this->loadItem($item->refresh());
        }, 3);
    }

    public function deleteItem(User $user, int $cartItemId): void
    {
        DB::transaction(function () use ($user, $cartItemId): void {
            [, $cart] = $this->lockUserAndCart($user, false);

            abort_unless($cart, 404);

            $item = $cart->items()->whereKey($cartItemId)->lockForUpdate()->first();

            abort_unless($item, 404);

            $item->delete();
        }, 3);
    }

    public function clear(User $user): void
    {
        DB::transaction(function () use ($user): void {
            [, $cart] = $this->lockUserAndCart($user, false);

            if ($cart) {
                $cart->items()->delete();
            }
        }, 3);
    }

    /**
     * @return array{CartItem, bool}
     */
    private function addItemInTransaction(User $user, int $variantId, int $quantity): array
    {
        [, $cart] = $this->lockUserAndCart($user);
        $variant = $this->lockPurchasableVariant($variantId);
        $item = $cart->items()
            ->where('product_variant_id', $variant->getKey())
            ->lockForUpdate()
            ->first();

        $newQuantity = $quantity + (int) ($item?->quantity ?? 0);
        $this->assertAvailableQuantity($variant, $newQuantity);

        if ($item) {
            $item->update(['quantity' => $newQuantity]);

            return [$this->loadItem($item->refresh()), false];
        }

        $item = $cart->items()->create([
            'product_variant_id' => $variant->getKey(),
            'quantity' => $newQuantity,
        ]);

        return [$this->loadItem($item), true];
    }

    /**
     * @return array{User, Cart|null}
     */
    private function lockUserAndCart(User $user, bool $createCart = true): array
    {
        $lockedUser = User::query()
            ->whereKey($user->getKey())
            ->lockForUpdate()
            ->firstOrFail();
        $cart = $lockedUser->cart()->lockForUpdate()->first();

        if (! $cart && $createCart) {
            $cart = $lockedUser->cart()->create([]);
        }

        return [$lockedUser, $cart];
    }

    private function lockPurchasableVariant(int $variantId): ProductVariant
    {
        $initialVariant = ProductVariant::query()->whereKey($variantId)->first();

        abort_unless($initialVariant, 404);

        $product = Product::withTrashed()
            ->whereKey($initialVariant->product_id)
            ->lockForUpdate()
            ->first();

        abort_unless($product, 404);

        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('product_id', $product->getKey())
            ->with(['size', 'color'])
            ->lockForUpdate()
            ->first();

        abort_unless($variant, 404);

        $category = Category::query()
            ->whereKey($product->category_id)
            ->lockForUpdate()
            ->first();
        $brand = $product->brand_id
            ? Brand::query()->whereKey($product->brand_id)->lockForUpdate()->first()
            : null;
        $product->setRelation('category', $category);
        $product->setRelation('brand', $brand);
        $variant->setRelation('product', $product);

        abort_unless($this->prices->isPurchasable($variant), 404);

        return $variant;
    }

    private function assertAvailableQuantity(ProductVariant $variant, int $quantity): void
    {
        if ($variant->stock < 1 || $quantity > $variant->stock) {
            abort(response()->json([
                'success' => false,
                'message' => 'Số lượng yêu cầu vượt quá tồn kho hiện có.',
            ], 409));
        }
    }

    private function loadItem(CartItem $item): CartItem
    {
        return $item->load([
            'productVariant.size',
            'productVariant.color',
            'productVariant.product' => fn ($query) => $query->withTrashed(),
            'productVariant.product.category',
            'productVariant.product.brand',
        ]);
    }

    private function isCartItemUniqueViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'cart_items_cart_id_product_variant_id_unique')
            || str_contains($message, 'cart_items.cart_id, cart_items.product_variant_id');
    }
}
