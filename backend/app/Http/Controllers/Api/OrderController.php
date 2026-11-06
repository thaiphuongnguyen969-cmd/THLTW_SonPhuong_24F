<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderCancelRequest;
use App\Http\Requests\OrderCheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $orders = $user->orders()
            ->with(['items', 'payments', 'statusHistories'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 15));

        return OrderResource::collection($orders)->additional([
            'success' => true,
            'message' => 'Lấy danh sách đơn hàng thành công.',
        ]);
    }

    public function store(OrderCheckoutRequest $request, OrderService $orderService)
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $order = $orderService->checkout(
            $user,
            (int) $validated['address_id'],
            $validated['note'] ?? null
        );

        return OrderResource::make($order)->additional([
            'success' => true,
            'message' => 'Tạo đơn hàng thành công.',
        ])->response()->setStatusCode(201);
    }

    public function show(Request $request, int $order)
    {
        /** @var User $user */
        $user = $request->user();
        $record = $user->orders()->whereKey($order)
            ->with(['items', 'payments', 'statusHistories'])
            ->firstOrFail();

        return OrderResource::make($record)->additional([
            'success' => true,
            'message' => 'Lấy chi tiết đơn hàng thành công.',
        ]);
    }

    public function cancel(
        OrderCancelRequest $request,
        int $order,
        OrderService $orderService
    ) {
        /** @var User $user */
        $user = $request->user();
        $record = $orderService->cancel(
            $user,
            $order,
            $request->validated('reason')
        );

        return OrderResource::make($record)->additional([
            'success' => true,
            'message' => 'Hủy đơn hàng thành công.',
        ]);
    }
}
