<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Requests\AdminUserIndexRequest;
use App\Http\Requests\AdminUserUpdateRequest;
use App\Http\Requests\AdminUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Lấy danh sách users
    public function index(AdminUserIndexRequest $request)
    {
        $query = User::query();
        $search = trim((string) $request->validated('search', ''));
        if ($search !== '') {
            $query->where(function ($users) use ($search) {
                $users->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        return UserResource::collection($query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate((int) $request->validated('per_page', 15)));
    }

    // Thêm mới user
    public function store(UserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'created_at' => now(),
        ]);
        $user->forceFill(['role' => 'customer', 'status' => 'active'])->save();

        return (new UserResource($user))
            ->additional(['message' => 'Thêm người dùng thành công'])
            ->response()
            ->setStatusCode(201);
    }

    // Xem chi tiết một user
    public function show($id)
    {
        $user = User::findOrFail($id);
        return new UserResource($user);
    }

    // Cập nhật thông tin user
    public function update(AdminUserUpdateRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validated();
        if (array_key_exists('password', $data)) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return (new UserResource($user))
            ->additional(['message' => 'Cập nhật thành công']);
    }

    // Xóa user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Xóa người dùng thành công'
        ], 200);
    }

    public function status(AdminUserStatusRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $status = $request->validated('status');
        if ($user->id === $request->user()->id && $status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Không thể khóa tài khoản quản trị đang sử dụng.'], 422);
        }
        if ($user->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'Không thể thay đổi trạng thái tài khoản quản trị.'], 422);
        }
        $user->forceFill(['status' => $status])->save();

        return (new UserResource($user))->additional(['message' => 'Đã cập nhật trạng thái tài khoản.']);
    }
}
