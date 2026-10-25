<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPostIndexRequest;
use App\Http\Requests\AdminPostRequest;
use App\Http\Resources\AdminPostResource;
use App\Http\Resources\PublicPostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PostController extends Controller
{
    public function adminIndex(AdminPostIndexRequest $request)
    {
        $validated = $request->validated();
        $query = Post::query()->with('author:id,name')->orderByDesc('created_at')->orderByDesc('id');
        $search = trim($validated['search'] ?? '');
        if ($search !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(fn ($posts) => $posts->where('title', 'like', '%'.$escaped.'%')->orWhere('slug', 'like', '%'.$escaped.'%'));
        }
        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }

        return AdminPostResource::collection($query->paginate($validated['per_page'] ?? 15))
            ->additional(['success' => true, 'message' => 'Đã tải danh sách bài viết.']);
    }

    public function adminStore(AdminPostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $thumbnail = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail')->store('posts', 'public');
            abort_unless(is_string($thumbnail), 500, 'Không thể lưu ảnh đại diện.');
        }
        $data['thumbnail'] = $thumbnail;
        $data['user_id'] = $request->user()->id;
        $data['published_at'] = $this->publicationDate($data['status'], $data['published_at'] ?? null);

        try {
            $post = DB::transaction(function () use ($data, $request) {
                $post = Post::create(collect($data)->except('user_id')->all());
                $post->forceFill(['user_id' => $request->user()->id])->save();
                return $post->load('author:id,name');
            });
        } catch (Throwable $error) {
            if ($thumbnail) Storage::disk('public')->delete($thumbnail);
            throw $error;
        }

        return (new AdminPostResource($post))
            ->additional(['success' => true, 'message' => 'Đã tạo bài viết.'])
            ->response()->setStatusCode(201);
    }

    public function adminShow(Post $post): AdminPostResource
    {
        return new AdminPostResource($post->load('author:id,name'));
    }

    public function adminUpdate(AdminPostRequest $request, Post $post): AdminPostResource
    {
        $data = $request->validated();
        $oldThumbnail = $post->thumbnail;
        $newThumbnail = null;
        if ($request->hasFile('thumbnail')) {
            $newThumbnail = $request->file('thumbnail')->store('posts', 'public');
            abort_unless(is_string($newThumbnail), 500, 'Không thể lưu ảnh đại diện.');
        }
        if ($newThumbnail) $data['thumbnail'] = $newThumbnail;
        $nextStatus = $data['status'] ?? $post->status;
        if ($nextStatus === 'draft') {
            $data['published_at'] = null;
        } elseif (($data['published_at'] ?? $post->published_at) === null) {
            $data['published_at'] = now();
        }
        if (array_key_exists('published_at', $data) && $data['published_at'] !== null) {
            $data['published_at'] = Carbon::parse($data['published_at']);
        }

        try {
            DB::transaction(fn () => $post->update($data));
        } catch (Throwable $error) {
            if ($newThumbnail) Storage::disk('public')->delete($newThumbnail);
            throw $error;
        }
        if ($newThumbnail && $oldThumbnail) $this->deleteStoredThumbnail($oldThumbnail);

        return new AdminPostResource($post->refresh()->load('author:id,name'));
    }

    public function adminDestroy(Post $post): JsonResponse
    {
        $post->delete();
        return response()->json(['success' => true, 'message' => 'Đã chuyển bài viết vào thùng rác.']);
    }

    public function publicIndex(Request $request)
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);
        $posts = Post::query()->with('author:id,name')->where('status', 'published')
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 10);

        return PublicPostResource::collection($posts)->additional(['success' => true, 'message' => 'Đã tải bài viết.']);
    }

    public function publicShow(string $slug): PublicPostResource
    {
        $post = Post::query()->with('author:id,name')->where('slug', $slug)->where('status', 'published')
            ->whereNotNull('published_at')->where('published_at', '<=', now())->firstOrFail();
        return new PublicPostResource($post);
    }

    private function publicationDate(string $status, mixed $provided): mixed
    {
        if ($status === 'draft') return null;
        return $provided ? Carbon::parse($provided) : now();
    }

    private function deleteStoredThumbnail(string $path): void
    {
        if (Str::startsWith($path, 'posts/')) Storage::disk('public')->delete($path);
    }
}
