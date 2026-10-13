<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartItemStoreRequest;
use App\Http\Requests\CartItemUpdateRequest;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $cart = $user->cart()->first();

        if (! $cart) {
            return response()->json([
                'success' => true,
                'message' => 'Lấy giỏ hàng thành công.',
                'data' => [
                    'items' => [],
                    'total_quantity' => 0,
                    'total' => '0.00',
                ],
            ]);
        }

        $this->loadCartItems($cart);

        return CartResource::make($cart)->additional([
            'success' => true,
            'message' => 'Lấy giỏ hàng thành công.',
        ]);
    }

    public function store(CartItemStoreRequest $request, CartService $cartService)
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        [$item, $created] = $cartService->addItem(
            $user,
            (int) $validated['product_variant_id'],
            (int) $validated['quantity']
        );

        return CartItemResource::make($item)->additional([
            'success' => true,
            'message' => $created
                ? 'Thêm sản phẩm vào giỏ hàng thành công.'
                : 'Cập nhật số lượng trong giỏ hàng thành công.',
        ])->response()->setStatusCode($created ? 201 : 200);
    }

    public function update(
        CartItemUpdateRequest $request,
        int $cartItem,
        CartService $cartService
    ) {
        /** @var User $user */
        $user = $request->user();
        $item = $cartService->updateItem($user, $cartItem, (int) $request->validated('quantity'));

        return CartItemResource::make($item)->additional([
            'success' => true,
            'message' => 'Cập nhật giỏ hàng thành công.',
        ]);
    }

    public function destroyItem(Request $request, int $cartItem, CartService $cartService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $cartService->deleteItem($user, $cartItem);

        return response()->json([
            'success' => true,
            'message' => 'Xóa sản phẩm khỏi giỏ hàng thành công.',
        ]);
    }

    public function destroy(Request $request, CartService $cartService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $cartService->clear($user);

        return response()->json([
            'success' => true,
            'message' => 'Xóa giỏ hàng thành công.',
        ]);
    }

    private function loadCartItems(Cart $cart): void
    {
        $cart->load([
            'items.productVariant.size',
            'items.productVariant.color',
            'items.productVariant.product' => fn ($query) => $query->withTrashed(),
            'items.productVariant.product.category',
            'items.productVariant.product.brand',
        ]);
    }
}
