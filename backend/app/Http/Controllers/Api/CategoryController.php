<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(CategoryIndexRequest $request)
    {
        $isAdmin = $this->isActiveAdmin($request);

        if ($request->validated('status') === 'inactive' && ! $isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Không có quyền xem danh mục không hoạt động.',
            ], 403);
        }

        $query = Category::query()
            ->with([
                'parent' => fn ($query) => $query
                    ->select(['id', 'name', 'slug', 'status'])
                    ->when(! $isAdmin, fn ($parent) => $parent->where('status', 'active')),
                'children' => fn ($query) => $query
                    ->select(['id', 'parent_id', 'name', 'slug', 'status'])
                    ->when(! $isAdmin, fn ($children) => $children->where('status', 'active')),
            ])
            ->when(
                $isAdmin,
                fn ($builder) => $builder->orderByDesc('created_at')->orderByDesc('id'),
                fn ($builder) => $builder->orderBy('name')
            );

        if ($isAdmin && in_array($request->validated('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->validated('status'));
        } elseif (! $isAdmin) {
            $query->where('status', 'active');
        }

        if ($isAdmin && $request->filled('search')) {
            $query->where('name', 'like', '%'.$request->validated('search').'%');
        }

        if ($request->exists('parent_id')) {
            $query->where('parent_id', $request->validated('parent_id'));
        }

        $categories = $query->paginate($request->integer('per_page', 15));

        return CategoryResource::collection($categories)->additional([
            'success' => true,
            'message' => 'Lấy danh sách danh mục thành công.',
        ]);
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return (new CategoryResource($category->load(['parent:id,name,slug', 'children'])))
            ->additional([
                'success' => true,
                'message' => 'Tạo danh mục thành công.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category)
    {
        abort_unless($category->status === 'active' || $this->isActiveAdmin(request()), 404);

        return (new CategoryResource($category->load([
            'parent' => fn ($query) => $query
                ->select(['id', 'name', 'slug', 'status'])
                ->when(! $this->isActiveAdmin(request()), fn ($parent) => $parent->where('status', 'active')),
            'children' => fn ($query) => $query
                ->select(['id', 'parent_id', 'name', 'slug', 'status'])
                ->when(! $this->isActiveAdmin(request()), fn ($children) => $children->where('status', 'active')),
        ])))->additional([
            'success' => true,
            'message' => 'Lấy chi tiết danh mục thành công.',
        ]);
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return (new CategoryResource($category->fresh()->load([
            'parent:id,name,slug,status',
            'children:id,parent_id,name,slug,status',
        ])))->additional([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công.',
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if (
            $category->products()->withTrashed()->exists()
            || Category::withTrashed()->where('parent_id', $category->id)->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa danh mục đang có sản phẩm hoặc danh mục con.',
            ], 409);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa danh mục thành công.',
        ]);
    }

    private function isActiveAdmin(CategoryIndexRequest|Request $request): bool
    {
        return $request->user()?->role === 'admin'
            && $request->user()?->status === 'active';
    }
}
