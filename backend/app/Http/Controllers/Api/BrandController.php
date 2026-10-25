<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandIndexRequest;
use App\Http\Requests\BrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(BrandIndexRequest $request)
    {
        $isAdmin = $this->isActiveAdmin($request);
        $query = Brand::query()->when(
            $isAdmin,
            fn ($builder) => $builder->orderByDesc('created_at')->orderByDesc('id'),
            fn ($builder) => $builder->orderBy('name')
        );

        if ($request->validated('status') === 'inactive' && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền xem thương hiệu không hoạt động.',
            ], 403);
        }

        if ($isAdmin && in_array($request->validated('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->validated('status'));
        } elseif (! $isAdmin) {
            $query->where('status', 'active');
        }

        if ($isAdmin && $request->filled('search')) {
            $query->where('name', 'like', '%'.$request->validated('search').'%');
        }

        return BrandResource::collection(
            $query->paginate($request->integer('per_page', 15))
        )->additional([
            'success' => true,
            'message' => 'Lấy danh sách thương hiệu thành công.',
        ]);
    }

    public function store(BrandRequest $request)
    {
        $brand = Brand::create($request->validated());

        return (new BrandResource($brand))->additional([
            'success' => true,
            'message' => 'Tạo thương hiệu thành công.',
        ])->response()->setStatusCode(201);
    }

    public function show(Brand $brand)
    {
        abort_unless($brand->status === 'active' || $this->isActiveAdmin(request()), 404);

        return (new BrandResource($brand))->additional([
            'success' => true,
            'message' => 'Lấy chi tiết thương hiệu thành công.',
        ]);
    }

    public function update(BrandRequest $request, Brand $brand)
    {
        $brand->update($request->validated());

        return (new BrandResource($brand->fresh()))->additional([
            'success' => true,
            'message' => 'Cập nhật thương hiệu thành công.',
        ]);
    }

    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->products()->withTrashed()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa thương hiệu đang được sản phẩm sử dụng.',
            ], 409);
        }

        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa thương hiệu thành công.',
        ]);
    }

    private function isActiveAdmin(BrandIndexRequest|Request $request): bool
    {
        return $request->user()?->role === 'admin'
            && $request->user()?->status === 'active';
    }
}
