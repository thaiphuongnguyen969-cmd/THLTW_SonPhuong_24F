<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminMenuIndexRequest;
use App\Http\Requests\AdminMenuRequest;
use App\Http\Resources\AdminMenuResource;
use App\Http\Resources\PublicMenuResource;
use App\Models\Menu;

class MenuController extends Controller
{
    public function adminIndex(AdminMenuIndexRequest $request)
    {
        $query = Menu::query()->orderBy('sort_order')->orderBy('id');
        $search = trim((string) $request->validated('search', ''));
        if ($search !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(fn ($menus) => $menus->where('name', 'like', '%'.$escaped.'%')->orWhere('url', 'like', '%'.$escaped.'%'));
        }

        return AdminMenuResource::collection($query->paginate($request->validated('per_page', 15)))
            ->additional(['success' => true, 'message' => 'Đã tải danh sách menu.']);
    }

    public function adminStore(AdminMenuRequest $request)
    {
        return (new AdminMenuResource(Menu::create($request->validated())))
            ->additional(['success' => true, 'message' => 'Đã tạo menu.'])
            ->response()->setStatusCode(201);
    }

    public function adminShow(Menu $menu): AdminMenuResource
    {
        return new AdminMenuResource($menu);
    }

    public function adminUpdate(AdminMenuRequest $request, Menu $menu): AdminMenuResource
    {
        $menu->update($request->validated());
        return (new AdminMenuResource($menu->refresh()))
            ->additional(['success' => true, 'message' => 'Đã cập nhật menu.']);
    }

    public function adminDestroy(Menu $menu)
    {
        $menu->delete();
        return response()->json(['success' => true, 'message' => 'Đã chuyển menu vào thùng rác.']);
    }

    public function publicIndex()
    {
        $menus = Menu::query()->where('status', 'active')->orderBy('sort_order')->orderBy('id')->get();
        return PublicMenuResource::collection($menus)->additional([
            'success' => true,
            'message' => 'Đã tải menu hiển thị.',
        ]);
    }
}
