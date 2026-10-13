<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private const PENDING_STATUS = 'Chờ xác nhận';
    private const CANCELLED_STATUS = 'Đã hủy';
    private const MAX_DECIMAL_MINOR_UNITS = '999999999999';
    private const MAX_UNSIGNED_INTEGER = 4294967295;

    public function __construct(
        private readonly CartPriceCalculator $prices
    ) {
    }

    public function checkout(User $user, int $addressId, ?string $note): Order
    {
        return DB::transaction(function () use ($user, $addressId, $note): Order {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $cart = $lockedUser->cart()->lockForUpdate()->first();

            abort_unless($cart, response()->json([
                'success' => false,
                'message' => 'Giỏ hàng đang trống.',
            ], 409));

            $address = $lockedUser->addresses()
                ->whereKey($addressId)
                ->lockForUpdate()
                ->first();
            abort_unless($address, 404);

            $items = $cart->items()->orderBy('id')->lockForUpdate()->get();
            abort_unless($items->isNotEmpty(), response()->json([
                'success' => false,
                'message' => 'Giỏ hàng đang trống.',
            ], 409));

            $variants = $this->lockPurchasableVariants($items);
            $orderLines = [];
            $subtotalMinorUnits = '0';
            $totalQuantity = 0;

            foreach ($items as $item) {
                $variant = $variants->get((int) $item->product_variant_id);
                abort_unless($variant, response()->json([
                    'success' => false,
                    'message' => 'Một hoặc nhiều sản phẩm trong giỏ không còn được bán.',
                ], 409));

                $quantity = (int) $item->quantity;
                if ($quantity < 1 || $quantity > self::MAX_UNSIGNED_INTEGER || $variant->stock < $quantity) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Số lượng yêu cầu vượt quá tồn kho hiện có.',
                    ], 409));
                }

                if ($totalQuantity > PHP_INT_MAX - $quantity) {
                    $this->abortInvalidMoney();
                }
                $totalQuantity += $quantity;

                $unitPrice = $this->prices->unitPrice($variant);
                $unitPriceMinorUnits = $this->prices->toMinorUnits($unitPrice);
                if ($unitPriceMinorUnits < 0) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Giá sản phẩm hiện không hợp lệ.',
                    ], 409));
                }
                $lineSubtotal = $this->prices->multiplyMinorUnits($unitPriceMinorUnits, $quantity);
                $this->assertWithinDecimalLimit($unitPriceMinorUnits);
                $this->assertWithinDecimalLimit($lineSubtotal);
                $subtotalMinorUnits = $this->prices->addMinorUnitAmounts($subtotalMinorUnits, $lineSubtotal);
                $this->assertWithinDecimalLimit($subtotalMinorUnits);

                $orderLines[] = [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $this->prices->formatMinorUnits($lineSubtotal),
                ];
            }

            $subtotal = $this->prices->formatMinorUnits($subtotalMinorUnits);
            $addressSnapshot = [
                'label' => $address->label,
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'province' => $address->province,
                'district' => $address->district,
                'ward' => $address->ward,
                'address_detail' => $address->address_detail,
            ];
            $encodedAddress = json_encode($addressSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encodedAddress === false) {
                throw new \RuntimeException('Could not encode the verified shipping address snapshot.');
            }

            $order = $lockedUser->orders()->create([
                'total' => $subtotal,
                'subtotal' => $subtotal,
                'discount_amount' => '0.00',
                'shipping_fee' => '0.00',
                'total_amount' => $subtotal,
                'status' => self::PENDING_STATUS,
                'address' => mb_strcut((string) $address->address_detail, 0, 255, 'UTF-8'),
                'phone' => $address->phone ?? '',
                'payment_method' => 'cod',
                'payment_status' => 'unpaid',
                'shipping_address_detail' => $encodedAddress,
                'shipping_phone' => $address->phone,
                'note' => $note,
            ]);

            foreach ($orderLines as $line) {
                /** @var ProductVariant $variant */
                $variant = $line['variant'];
                /** @var Product $product */
                $product = $variant->product;
                $variant->stock = (int) $variant->stock - $line['quantity'];
                $variant->save();

                $order->items()->create([
                    'product_variant_id' => $variant->getKey(),
                    'product_name' => $product->name,
                    'sku' => $variant->sku,
                    'size' => $variant->size->name,
                    'color' => $variant->color->name,
                    'price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $order->payments()->create([
                'payment_method' => 'cod',
                'amount' => $subtotal,
                'status' => 'pending',
            ]);

            $order->statusHistories()->create([
                'changed_by' => $lockedUser->getKey(),
                'from_status' => null,
                'to_status' => self::PENDING_STATUS,
                'note' => 'Order created.',
            ]);

            $cart->items()->delete();

            return $this->loadOrder($order);
        }, 3);
    }

    public function cancel(User $user, int $orderId, ?string $reason): Order
    {
        return DB::transaction(function () use ($user, $orderId, $reason): Order {
            $order = $user->orders()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($order->status === self::PENDING_STATUS, response()->json([
                'success' => false,
                'message' => 'Chỉ có thể hủy đơn hàng đang chờ xác nhận.',
            ], 409));

            $hasCheckoutHistory = $order->statusHistories()
                ->whereNull('from_status')
                ->where('to_status', self::PENDING_STATUS)
                ->exists();
            abort_unless($hasCheckoutHistory, response()->json([
                'success' => false,
                'message' => 'Không thể hủy đơn hàng không có lịch sử checkout hợp lệ.',
            ], 409));

            $items = $order->items()->orderBy('id')->lockForUpdate()->get();
            abort_unless($items->isNotEmpty(), response()->json([
                'success' => false,
                'message' => 'Không thể hoàn kho vì đơn hàng không có chi tiết sản phẩm.',
            ], 409));

            $quantities = [];
            foreach ($items as $item) {
                if ($item->product_variant_id === null) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Không thể hoàn kho vì biến thể của đơn hàng không còn tồn tại.',
                    ], 409));
                }

                $variantId = (int) $item->product_variant_id;
                $quantity = (int) $item->quantity;
                $currentQuantity = $quantities[$variantId] ?? 0;
                if (
                    $quantity < 1
                    || $quantity > self::MAX_UNSIGNED_INTEGER - $currentQuantity
                ) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Số lượng hoàn kho không hợp lệ.',
                    ], 409));
                }
                $quantities[$variantId] = $currentQuantity + $quantity;
            }

            $initialVariants = ProductVariant::query()
                ->whereIn('id', array_keys($quantities))
                ->orderBy('product_id')
                ->orderBy('id')
                ->get();
            abort_unless($initialVariants->count() === count($quantities), response()->json([
                'success' => false,
                'message' => 'Không thể hoàn kho vì biến thể của đơn hàng không còn tồn tại.',
            ], 409));

            $productIds = $initialVariants->pluck('product_id')->unique()->sort()->values();
            Product::withTrashed()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();
            $variants = ProductVariant::query()
                ->whereIn('id', array_keys($quantities))
                ->orderBy('product_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $variantId => $quantity) {
                /** @var ProductVariant|null $variant */
                $variant = $variants->get($variantId);
                abort_unless($variant, response()->json([
                    'success' => false,
                    'message' => 'Không thể hoàn kho vì biến thể của đơn hàng không còn tồn tại.',
                ], 409));

                if ($quantity < 1 || (int) $variant->stock > self::MAX_UNSIGNED_INTEGER - $quantity) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Tồn kho không cho phép hoàn số lượng của đơn hàng.',
                    ], 409));
                }
            }

            foreach ($quantities as $variantId => $quantity) {
                $variant = $variants->get($variantId);
                $variant->stock = (int) $variant->stock + $quantity;
                $variant->save();
            }

            $order->status = self::CANCELLED_STATUS;
            $order->cancelled_reason = $reason;
            $order->save();
            $order->statusHistories()->create([
                'changed_by' => $user->getKey(),
                'from_status' => self::PENDING_STATUS,
                'to_status' => self::CANCELLED_STATUS,
                'note' => $reason,
            ]);

            return $this->loadOrder($order);
        }, 3);
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @return Collection<int, ProductVariant>
     */
    private function lockPurchasableVariants(Collection $items): Collection
    {
        $variantIds = $items->pluck('product_variant_id')->map(fn ($id): int => (int) $id)->unique()->sort()->values();
        $initialVariants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->orderBy('product_id')
            ->orderBy('id')
            ->get();

        if ($initialVariants->count() !== $variantIds->count()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Một hoặc nhiều sản phẩm trong giỏ không còn được bán.',
            ], 409));
        }

        $productIds = $initialVariants->pluck('product_id')->unique()->sort()->values();
        $products = Product::withTrashed()
            ->whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->orderBy('product_id')
            ->orderBy('id')
            ->with(['size', 'color'])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $categoryIds = $products->pluck('category_id')->unique()->sort()->values();
        $categories = Category::query()
            ->whereIn('id', $categoryIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $brandIds = $products->pluck('brand_id')->filter()->unique()->sort()->values();
        $brands = Brand::query()
            ->whereIn('id', $brandIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($variants as $variant) {
            $product = $products->get((int) $variant->product_id);
            abort_unless($product, response()->json([
                'success' => false,
                'message' => 'Một hoặc nhiều sản phẩm trong giỏ không còn được bán.',
            ], 409));

            $product->setRelation('category', $categories->get((int) $product->category_id));
            $product->setRelation('brand', $product->brand_id === null
                ? null
                : $brands->get((int) $product->brand_id));
            $variant->setRelation('product', $product);

            abort_unless($this->prices->isPurchasable($variant), response()->json([
                'success' => false,
                'message' => 'Một hoặc nhiều sản phẩm trong giỏ không còn được bán.',
            ], 409));
        }

        return $variants;
    }

    private function assertWithinDecimalLimit(string|int $minorUnits): void
    {
        $amount = ltrim((string) $minorUnits, '0') ?: '0';

        if (
            strlen($amount) > strlen(self::MAX_DECIMAL_MINOR_UNITS)
            || (strlen($amount) === strlen(self::MAX_DECIMAL_MINOR_UNITS)
                && strcmp($amount, self::MAX_DECIMAL_MINOR_UNITS) > 0)
        ) {
            $this->abortInvalidMoney();
        }
    }

    private function abortInvalidMoney(): never
    {
        abort(response()->json([
            'success' => false,
            'message' => 'Giá trị đơn hàng vượt giới hạn lưu trữ.',
        ], 422));
    }

    private function loadOrder(Order $order): Order
    {
        return $order->load(['items', 'payments', 'statusHistories']);
    }
}
