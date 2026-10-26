<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
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
        if (! $isAdmin) {
            $query->with(['variants' => fn ($variants) => $variants
                ->select(['id', 'product_id', 'price', 'sale_price', 'stock', 'status'])
                ->where('status', 'active')]);
        }

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

        if ($request->filled('exclude_id')) {
            $query->where('products.id', '<>', $request->integer('exclude_id'));
        }

        if ($request->boolean('promotion')) {
            $query->whereHas('variants', fn ($variants) => $variants
                ->where('status', 'active')
                ->where('stock', '>', 0)
                ->where(fn ($pricing) => $pricing
                    ->where(fn ($variantSale) => $variantSale
                        ->whereNotNull('product_variants.price')
                        ->whereNotNull('product_variants.sale_price')
                        ->where('product_variants.sale_price', '>', 0)
                        ->whereColumn('product_variants.sale_price', '<', 'product_variants.price'))
                    ->orWhere(fn ($productSale) => $productSale
                        ->whereNull('product_variants.price')
                        ->whereNotNull('products.sale_price')
                        ->where('products.sale_price', '>=', 0)
                        ->whereColumn('products.sale_price', '<', 'products.price'))));
        }

        $products = $query->paginate($request->integer('per_page', 15));
        if (! $isAdmin) {
            $products->getCollection()->each(fn (Product $product) => $product->setAttribute('_hide_variants_resource', true));
        }

        return ProductResource::collection($products)->additional([
            'success' => true,
            'message' => 'Lấy danh sách sản phẩm thành công.',
        ]);
    }

    public function hot()
    {
        $deliveredOrders = DB::table('order_status_histories')
            ->select('order_id')
            ->where('to_status', 'Đã giao hàng')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('order_id');

        $sales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->joinSub($deliveredOrders, 'recent_deliveries', fn ($join) => $join->on('recent_deliveries.order_id', '=', 'orders.id'))
            ->where('orders.status', 'Đã giao hàng')
            ->select('product_variants.product_id', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('product_variants.product_id');

        $products = Product::query()
            ->joinSub($sales, 'sales', fn ($join) => $join->on('sales.product_id', '=', 'products.id'))
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'variants:id,product_id,price,sale_price,stock,status'])
            ->where('products.status', 'active')
            ->whereHas('category', fn ($category) => $category->where('status', 'active'))
            ->where(fn ($query) => $query->whereNull('products.brand_id')
                ->orWhereHas('brand', fn ($brand) => $brand->where('status', 'active')))
            ->orderByDesc('sales.total_sold')
            ->orderByDesc('products.id')
            ->limit(4)
            ->get(['products.*', 'sales.total_sold']);
        $products->each(fn (Product $product) => $product->setAttribute('_hide_variants_resource', true));

        return ProductResource::collection($products)->additional([
            'success' => true,
            'message' => 'Lấy sản phẩm bán chạy thành công.',
        ]);
    }

    public function related(Product $product)
    {
        abort_unless(
            $product->status === 'active'
            && $product->category?->status === 'active'
            && ($product->brand === null || $product->brand->status === 'active'),
            404
        );

        $related = Product::query()
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'variants:id,product_id,price,sale_price,stock,status'])
            ->where('products.status', 'active')
            ->where('products.id', '<>', $product->id)
            ->whereHas('category', fn ($category) => $category->where('status', 'active'))
            ->where(fn ($query) => $query->whereNull('products.brand_id')
                ->orWhereHas('brand', fn ($brand) => $brand->where('status', 'active')))
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 WHEN brand_id = ? AND ? IS NOT NULL THEN 1 ELSE 2 END', [
                $product->category_id,
                $product->brand_id,
                $product->brand_id,
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();
        $related->each(fn (Product $relatedProduct) => $relatedProduct->setAttribute('_hide_variants_resource', true));

        return ProductResource::collection($related)->additional([
            'success' => true,
            'message' => 'Lấy sản phẩm liên quan thành công.',
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
                ->select(['id', 'product_id', 'size_id', 'color_id', 'sku', 'price', 'sale_price', 'stock', 'status'])
                ->when(! $isAdmin, fn ($variants) => $variants->where('status', 'active')),
            'variants.product:id,price,sale_price',
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
