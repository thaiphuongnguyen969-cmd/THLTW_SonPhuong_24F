<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request)
    {
        $query = Product::query()
            ->with(['category:id,name,slug', 'brand:id,name,slug'])
            ->orderBy(
                $request->validated('sort_by', 'created_at'),
                $request->validated('sort_direction', 'desc')
            )
            ->orderByDesc('id');

        $isAdmin = $this->isActiveAdmin($request);

        if ($request->validated('status') === 'inactive' && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền xem sản phẩm không hoạt động.',
            ], 403);
        }

        if ($isAdmin && in_array($request->validated('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->validated('status'));
        } elseif (! $isAdmin) {
            $query->where('status', 'active');
        }

        if (! $isAdmin) {
            $query
                ->whereHas('category', fn ($category) => $category->where('status', 'active'))
                ->where(function ($builder) {
                    $builder->whereNull('brand_id')
                        ->orWhereHas('brand', fn ($brand) => $brand->where('status', 'active'));
                });
        }

        if ($request->filled('search')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->validated('search'));
            $query->where('name', 'like', '%'.$search.'%');
        }

        $query
            ->when($request->validated('category_id'), fn ($builder, $id) => $builder->where('category_id', $id))
            ->when($request->validated('brand_id'), fn ($builder, $id) => $builder->where('brand_id', $id))
            ->when($request->filled('min_price'), fn ($builder) => $builder->where('price', '>=', $request->validated('min_price')))
            ->when($request->filled('max_price'), fn ($builder) => $builder->where('price', '<=', $request->validated('max_price')));

        $products = $query->paginate($request->integer('per_page', 15));

        return ProductResource::collection($products)->additional([
            'success' => true,
            'message' => 'Lấy danh sách sản phẩm thành công.',
        ]);
    }

    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product->load(['category', 'brand', 'images', 'variants.size', 'variants.color', 'promotions'])))
            ->additional([
                'success' => true,
                'message' => 'Tạo sản phẩm thành công.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product)
    {
        $isAdmin = $this->isActiveAdmin(request());

        $product->load([
            'category:id,name,slug,parent_id,status',
            'brand:id,name,slug,logo,status',
            'images:id,product_id,image_url,is_primary,sort_order',
            'variants' => fn ($query) => $query
                ->select(['id', 'product_id', 'size_id', 'color_id', 'sku', 'price', 'stock', 'status'])
                ->when(! $isAdmin, fn ($variants) => $variants->where('status', 'active')),
            'variants.size:id,name,sort_order',
            'variants.color:id,name,hex_code',
            'promotions' => fn ($query) => $query
                ->where('promotions.status', 'active')
                ->where('promotions.starts_at', '<=', now())
                ->where('promotions.ends_at', '>=', now()),
        ]);

        if (! $this->isActiveAdmin(request())) {
            abort_unless(
                $product->status === 'active'
                && $product->category?->status === 'active'
                && ($product->brand === null || $product->brand->status === 'active'),
                404
            );
        }

        return (new ProductResource($product))->additional([
            'success' => true,
            'message' => 'Lấy chi tiết sản phẩm thành công.',
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        $product->refresh();

        return (new ProductResource($product->load([
            'category',
            'brand',
            'images',
            'variants.size',
            'variants.color',
            'promotions',
        ])))->additional([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công.',
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ngừng kinh doanh sản phẩm thành công.',
        ]);
    }

    private function isActiveAdmin(ProductIndexRequest|Request $request): bool
    {
        return $request->user()?->role === 'admin'
            && $request->user()?->status === 'active';
    }
}
