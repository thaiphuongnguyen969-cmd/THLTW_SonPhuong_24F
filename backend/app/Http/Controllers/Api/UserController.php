<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Lấy danh sách users
    public function index()
    {
        $users = User::all();
        return UserResource::collection($users);
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

        return (new UserResource($user))
            ->additional(['message' => 'Thêm người dùng thành công'])
            ->response()
            ->setStatusCode(201);
    }

    // Xem chi tiết một user
    public function show($id)
    {
        $user = User::find($id);
        if (!$id) { // Hoặc check $user
             // Tuỳ biến xử lý nếu tìm không thấy
        }
        
        $user = User::findOrFail($id);
        return new UserResource($user);
    }

    // Cập nhật thông tin user
    public function update(UserRequest $request, $id)
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
}