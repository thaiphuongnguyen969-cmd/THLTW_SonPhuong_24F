<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminBannerIndexRequest;
use App\Http\Requests\AdminBannerRequest;
use App\Http\Resources\AdminBannerResource;
use App\Http\Resources\PublicBannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class BannerController extends Controller
{
    public function adminIndex(AdminBannerIndexRequest $request)
    {
        $validated = $request->validated();
        $query = Banner::query()->orderBy('sort_order')->orderBy('id');
        $search = trim($validated['search'] ?? '');
        if ($search !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(fn ($banners) => $banners
                ->where('title', 'like', '%'.$escaped.'%')
                ->orWhere('description', 'like', '%'.$escaped.'%'));
        }
        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        return AdminBannerResource::collection($query->paginate($validated['per_page'] ?? 15))
            ->additional(['success' => true, 'message' => 'Đã tải danh sách banner.']);
    }

    public function adminStore(AdminBannerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $image = $request->file('image')->store('banners', 'public');
        abort_unless(is_string($image), 500, 'Không thể lưu ảnh banner.');
        $data['image'] = $image;

        try {
            $banner = DB::transaction(fn () => Banner::create($data));
        } catch (Throwable $error) {
            $this->deleteStoredImage($image);
            throw $error;
        }

        return (new AdminBannerResource($banner))
            ->additional(['success' => true, 'message' => 'Đã tạo banner.'])
            ->response()->setStatusCode(201);
    }

    public function adminShow(Banner $banner): AdminBannerResource
    {
        return new AdminBannerResource($banner);
    }

    public function adminUpdate(AdminBannerRequest $request, Banner $banner): AdminBannerResource
    {
        $data = $request->validated();
        unset($data['image']);
        $oldImage = $banner->image;
        $newImage = null;
        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('banners', 'public');
            abort_unless(is_string($newImage), 500, 'Không thể lưu ảnh banner.');
            $data['image'] = $newImage;
        }

        try {
            DB::transaction(fn () => $banner->update($data));
        } catch (Throwable $error) {
            if ($newImage) {
                $this->deleteStoredImage($newImage);
            }
            throw $error;
        }

        if ($newImage && $oldImage) {
            $this->deleteStoredImage($oldImage);
        }

        return new AdminBannerResource($banner->refresh());
    }

    public function adminDestroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return response()->json(['success' => true, 'message' => 'Đã chuyển banner vào thùng rác.']);
    }

    public function publicIndex()
    {
        $banners = Banner::query()->where('status', 'active')->orderBy('sort_order')->orderBy('id')->get();

        return PublicBannerResource::collection($banners)->additional([
            'success' => true,
            'message' => 'Đã tải banner hiển thị.',
        ]);
    }

    private function deleteStoredImage(string $path): void
    {
        if (Str::startsWith($path, 'banners/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
