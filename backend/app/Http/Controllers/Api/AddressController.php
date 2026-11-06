<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressStoreRequest;
use App\Http\Requests\AddressUpdateRequest;
use App\Http\Resources\AddressResource;
use App\Models\User;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return AddressResource::collection($addresses)->additional([
            'success' => true,
            'message' => 'Lấy danh sách địa chỉ thành công.',
        ]);
    }

    public function store(AddressStoreRequest $request, AddressService $addressService)
    {
        /** @var User $user */
        $user = $request->user();
        $address = $addressService->create($user, $request->validated());

        return AddressResource::make($address)->additional([
            'success' => true,
            'message' => 'Tạo địa chỉ thành công.',
        ])->response()->setStatusCode(201);
    }

    public function show(Request $request, int $address)
    {
        /** @var User $user */
        $user = $request->user();
        $record = $user->addresses()->whereKey($address)->firstOrFail();

        return AddressResource::make($record)->additional([
            'success' => true,
            'message' => 'Lấy chi tiết địa chỉ thành công.',
        ]);
    }

    public function update(
        AddressUpdateRequest $request,
        int $address,
        AddressService $addressService
    ) {
        /** @var User $user */
        $user = $request->user();
        $record = $addressService->update($user, $address, $request->validated());

        return AddressResource::make($record)->additional([
            'success' => true,
            'message' => 'Cập nhật địa chỉ thành công.',
        ]);
    }

    public function setDefault(Request $request, int $address, AddressService $addressService)
    {
        /** @var User $user */
        $user = $request->user();
        $record = $addressService->setDefault($user, $address);

        return AddressResource::make($record)->additional([
            'success' => true,
            'message' => 'Đặt địa chỉ mặc định thành công.',
        ]);
    }

    public function destroy(Request $request, int $address, AddressService $addressService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $addressService->delete($user, $address);

        return response()->json([
            'success' => true,
            'message' => 'Xóa địa chỉ thành công.',
        ]);
    }
}
