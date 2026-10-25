<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Post;
use App\Models\Menu;
use App\Models\Review;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;

class AdminTrashController extends Controller
{
    private const TYPES = ['category', 'brand', 'product', 'post', 'menu'];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', Rule::in(self::TYPES)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $types = isset($validated['type']) ? [$validated['type']] : self::TYPES;
        $queries = [];
        $search = trim($validated['search'] ?? '');

        foreach ($types as $type) {
            $query = $this->trashedQuery($type);
            if ($search !== '') {
                $query->where($type === 'post' ? 'title' : 'name', 'like', '%'.$search.'%');
            }
            $queries[] = $query;
        }

        /** @var Builder $query */
        $query = array_shift($queries);
        foreach ($queries as $otherQuery) {
            $query->unionAll($otherQuery);
        }

        $records = $query
            ->orderByDesc('deleted_at')
            ->orderBy('type')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 15)
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách thùng rác thành công.',
            'data' => $records->items(),
            'links' => [
                'first' => $records->url(1),
                'last' => $records->url($records->lastPage()),
                'prev' => $records->previousPageUrl(),
                'next' => $records->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    public function restore(string $type, int $id): JsonResponse
    {
        $record = $this->findTrashedRecord($type, $id);

        try {
            $record->restore();
        } catch (QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khôi phục bản ghi do xung đột dữ liệu.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Khôi phục bản ghi thành công.',
            'data' => ['type' => $type, 'id' => $record->getKey()],
        ]);
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        $record = $this->findTrashedRecord($type, $id);
        $blockMessage = $this->permanentDeleteBlockMessage($type, $record);

        if ($blockMessage !== null) {
            return response()->json([
                'success' => false,
                'message' => $blockMessage,
            ], 409);
        }

        try {
            $thumbnail = $type === 'post' ? $record->thumbnail : null;
            $record->forceDelete();
            if ($thumbnail && Str::startsWith($thumbnail, 'posts/')) {
                Storage::disk('public')->delete($thumbnail);
            }
        } catch (QueryException) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vĩnh viễn vì bản ghi còn được dữ liệu khác tham chiếu.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Xóa vĩnh viễn bản ghi thành công.',
        ]);
    }

    private function trashedQuery(string $type): Builder
    {
        return match ($type) {
            'category' => DB::table('categories')
                ->whereNotNull('deleted_at')
                ->selectRaw("'category' as type, id, name, slug, status, deleted_at"),
            'brand' => DB::table('brands')
                ->whereNotNull('deleted_at')
                ->selectRaw("'brand' as type, id, name, slug, status, deleted_at"),
            'product' => DB::table('products')
                ->whereNotNull('deleted_at')
                ->selectRaw("'product' as type, id, name, slug, status, deleted_at"),
            'post' => DB::table('posts')
                ->whereNotNull('deleted_at')
                ->selectRaw("'post' as type, id, title as name, slug, status, deleted_at"),
            'menu' => DB::table('menus')
                ->whereNotNull('deleted_at')
                ->selectRaw("'menu' as type, id, name, null as slug, status, deleted_at"),
        };
    }

    private function findTrashedRecord(string $type, int $id): Model
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        return match ($type) {
            'category' => Category::onlyTrashed()->findOrFail($id),
            'brand' => Brand::onlyTrashed()->findOrFail($id),
            'product' => Product::onlyTrashed()->findOrFail($id),
            'post' => Post::onlyTrashed()->findOrFail($id),
            'menu' => Menu::onlyTrashed()->findOrFail($id),
        };
    }

    private function permanentDeleteBlockMessage(string $type, Model $record): ?string
    {
        if ($type === 'category') {
            $hasProducts = $record->products()->withTrashed()->exists();
            $hasChildren = Category::withTrashed()->where('parent_id', $record->getKey())->exists();

            return $hasProducts || $hasChildren
                ? 'Không thể xóa vĩnh viễn danh mục còn sản phẩm hoặc danh mục con tham chiếu.'
                : null;
        }

        if ($type === 'brand') {
            return $record->products()->withTrashed()->exists()
                ? 'Không thể xóa vĩnh viễn thương hiệu còn sản phẩm tham chiếu.'
                : null;
        }

        if ($type === 'menu') {
            return null;
        }

        /** @var Product $record */
        $variantIds = ProductVariant::query()
            ->where('product_id', $record->getKey())
            ->select('id');

        if (OrderItem::query()->whereIn('product_variant_id', $variantIds)->exists()) {
            return 'Không thể xóa vĩnh viễn sản phẩm đã xuất hiện trong đơn hàng.';
        }

        if (Review::query()->where('product_id', $record->getKey())->exists()) {
            return 'Không thể xóa vĩnh viễn sản phẩm có đánh giá.';
        }

        if (CartItem::query()->whereIn('product_variant_id', $variantIds)->exists()) {
            return 'Không thể xóa vĩnh viễn sản phẩm đang có trong giỏ hàng.';
        }

        return null;
    }
}
