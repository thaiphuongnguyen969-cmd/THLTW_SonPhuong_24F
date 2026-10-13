<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariantIndexRequest;
use App\Http\Requests\ProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductVariantController extends Controller
{
    public function index(ProductVariantIndexRequest $request, Product $product)
    {
        $variants = $product->variants()
            ->with(['size:id,name', 'color:id,name,hex_code'])
            ->orderBy('id')
            ->paginate($request->integer('per_page', 15));

        return ProductVariantResource::collection($variants)->additional([
            'success' => true,
            'message' => 'Lấy danh sách biến thể thành công.',
        ]);
    }

    public function store(ProductVariantRequest $request, Product $product)
    {
        try {
            $variant = DB::transaction(function () use ($request, $product): ProductVariant {
                $lockedProduct = Product::query()
                    ->whereKey($product->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $attributes = $request->validated();
                $attributes['status'] = $attributes['status'] ?? 'active';

                $this->assertUniqueVariantAttributes($lockedProduct, $attributes);

                if (
                    $attributes['status'] === 'inactive'
                    && ! $lockedProduct->variants()->where('status', 'active')->exists()
                ) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Không thể tạo biến thể inactive khi sản phẩm chưa có biến thể active.',
                    ], 409));
                }

                return $lockedProduct->variants()->create($attributes);
            }, 3);
        } catch (QueryException $exception) {
            return $this->uniqueConstraintResponse($exception) ?? throw $exception;
        }

        $variant->load(['size:id,name', 'color:id,name,hex_code']);

        return (new ProductVariantResource($variant))->additional([
            'success' => true,
            'message' => 'Tạo biến thể thành công.',
        ])->response()->setStatusCode(201);
    }

    public function show(Product $product, ProductVariant $variant)
    {
        $this->ensureVariantBelongsToProduct($product, $variant);
        $variant->load(['size:id,name', 'color:id,name,hex_code']);

        return (new ProductVariantResource($variant))->additional([
            'success' => true,
            'message' => 'Lấy chi tiết biến thể thành công.',
        ]);
    }

    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant)
    {
        $this->ensureVariantBelongsToProduct($product, $variant);

        try {
            $variant = DB::transaction(function () use ($request, $product, $variant): ProductVariant {
                $lockedProduct = Product::query()
                    ->whereKey($product->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedVariant = $lockedProduct->variants()
                    ->whereKey($variant->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $attributes = $request->validated();

                if (array_key_exists('stock', $attributes)) {
                    $expectedStock = (int) $attributes['expected_stock'];
                    unset($attributes['expected_stock']);

                    if ((int) $lockedVariant->stock !== $expectedStock) {
                        abort(response()->json([
                            'success' => false,
                            'message' => 'Tồn kho đã thay đổi. Vui lòng tải lại dữ liệu và thử lại.',
                            'errors' => [
                                'stock' => ['Tồn kho hiện tại không khớp với giá trị bạn gửi.'],
                            ],
                        ], 409));
                    }
                } else {
                    unset($attributes['expected_stock']);
                }

                $this->assertUniqueVariantAttributes($lockedProduct, $attributes, $lockedVariant);

                if (
                    ($attributes['status'] ?? null) === 'inactive'
                    && $lockedVariant->status === 'active'
                    && $lockedProduct->variants()->where('status', 'active')->count() <= 1
                ) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Không thể chuyển biến thể active cuối cùng sang inactive.',
                    ], 409));
                }

                $lockedVariant->update($attributes);

                return $lockedVariant;
            }, 3);
        } catch (QueryException $exception) {
            return $this->uniqueConstraintResponse($exception) ?? throw $exception;
        }

        $variant->refresh()->load(['size:id,name', 'color:id,name,hex_code']);

        return (new ProductVariantResource($variant))->additional([
            'success' => true,
            'message' => 'Cập nhật biến thể thành công.',
        ]);
    }

    public function destroy(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->ensureVariantBelongsToProduct($product, $variant);

        DB::transaction(function () use ($product, $variant): void {
            $lockedProduct = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedVariant = $lockedProduct->variants()
                ->whereKey($variant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedVariant->orderItems()->exists()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa biến thể đã có trong đơn hàng. Hãy chuyển biến thể sang inactive để giữ lịch sử.',
                ], 409));
            }

            if ($lockedVariant->cartItems()->exists()) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa biến thể đang có trong giỏ hàng. Hãy chuyển biến thể sang inactive để tránh mất dữ liệu giỏ hàng.',
                ], 409));
            }

            if (
                $lockedVariant->status === 'active'
                && $lockedProduct->variants()->where('status', 'active')->count() <= 1
            ) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa biến thể active cuối cùng của sản phẩm.',
                ], 409));
            }

            $lockedVariant->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Xóa biến thể thành công.',
        ]);
    }

    private function ensureVariantBelongsToProduct(Product $product, ProductVariant $variant): void
    {
        abort_unless((int) $variant->product_id === (int) $product->getKey(), 404);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertUniqueVariantAttributes(
        Product $product,
        array $attributes,
        ?ProductVariant $variant = null
    ): void {
        $sizeId = $attributes['size_id'] ?? $variant?->size_id;
        $colorId = $attributes['color_id'] ?? $variant?->color_id;

        if ($sizeId !== null && $colorId !== null) {
            $duplicateCombination = $product->variants()
                ->where('size_id', $sizeId)
                ->where('color_id', $colorId)
                ->when($variant, fn ($query) => $query->where('id', '<>', $variant->getKey()))
                ->exists();

            if ($duplicateCombination) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => [
                        'size_id' => ['Tổ hợp kích thước và màu sắc này đã tồn tại cho sản phẩm.'],
                    ],
                ], 422));
            }
        }

        if (isset($attributes['sku'])) {
            $duplicateSku = ProductVariant::query()
                ->where('sku', $attributes['sku'])
                ->when($variant, fn ($query) => $query->where('id', '<>', $variant->getKey()))
                ->exists();

            if ($duplicateSku) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ.',
                    'errors' => [
                        'sku' => ['SKU đã được sử dụng.'],
                    ],
                ], 422));
            }
        }
    }

    private function uniqueConstraintResponse(QueryException $exception): ?JsonResponse
    {
        $message = strtolower($exception->getMessage());

        if (
            str_contains($message, 'product_variants_sku_unique')
            || str_contains($message, 'product_variants.sku')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => [
                    'sku' => ['SKU đã được sử dụng.'],
                ],
            ], 422);
        }

        if (
            str_contains($message, 'product_variants_product_size_color_unique')
            || str_contains($message, 'product_variants.product_id, product_variants.size_id, product_variants.color_id')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => [
                    'size_id' => ['Tổ hợp kích thước và màu sắc này đã tồn tại cho sản phẩm.'],
                ],
            ], 422);
        }

        return null;
    }
}
