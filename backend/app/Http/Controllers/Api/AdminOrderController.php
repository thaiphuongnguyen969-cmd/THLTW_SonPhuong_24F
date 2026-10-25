<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOrderIndexRequest;
use App\Http\Requests\AdminOrderStatusRequest;
use App\Http\Resources\AdminOrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;

class AdminOrderController extends Controller
{
    public function index(AdminOrderIndexRequest $request)
    {
        $validated = $request->validated();
        $search = trim((string) ($validated['search'] ?? ''));

        $orders = Order::query()
            ->with(['items', 'payments', 'statusHistories.changedBy:id,name', 'user:id,name,email,phone'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('order_code', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when(($validated['status'] ?? '') !== '', fn ($query) => $query->where('status', $validated['status']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 15));

        return AdminOrderResource::collection($orders)->additional([
            'success' => true,
            'message' => 'Lấy danh sách đơn hàng thành công.',
        ]);
    }

    public function show(int $order)
    {
        $record = Order::query()
            ->with(['items', 'payments', 'statusHistories.changedBy:id,name', 'user:id,name,email,phone'])
            ->findOrFail($order);

        return AdminOrderResource::make($record)->additional([
            'success' => true,
            'message' => 'Lấy chi tiết đơn hàng thành công.',
        ]);
    }

    public function updateStatus(AdminOrderStatusRequest $request, int $order, OrderService $orderService)
    {
        /** @var User $admin */
        $admin = $request->user();
        $validated = $request->validated();
        $record = $orderService->updateAdminStatus(
            $admin,
            $order,
            $validated['expected_status'],
            $validated['status'],
            $validated['reason'] ?? null,
        );

        $record->load(['items', 'payments', 'statusHistories.changedBy:id,name', 'user:id,name,email,phone']);

        return AdminOrderResource::make($record)->additional([
            'success' => true,
            'message' => 'Cập nhật trạng thái đơn hàng thành công.',
        ]);
    }
}
