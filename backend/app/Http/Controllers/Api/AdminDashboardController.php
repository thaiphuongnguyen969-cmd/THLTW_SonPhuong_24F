<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __invoke()
    {
        $statuses = OrderService::adminStatuses();
        $statusCounts = array_fill_keys($statuses, 0);

        $countsByStatus = Order::query()
            ->select('status')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('status')
            ->get();

        $otherStatuses = [];
        foreach ($countsByStatus as $row) {
            $status = (string) $row->status;
            $count = (int) $row->aggregate;

            if (array_key_exists($status, $statusCounts)) {
                $statusCounts[$status] = $count;
            } else {
                $otherStatuses[] = [
                    'status' => $status,
                    'count' => $count,
                ];
            }
        }

        $generatedAt = now();
        $monthStart = $generatedAt->copy()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();

        $deliveredOrders = Order::query()->where('status', 'Đã giao hàng');
        $allTimeValue = (clone $deliveredOrders)
            ->selectRaw('COALESCE(SUM(COALESCE(total_amount, total)), 0) AS aggregate')
            ->value('aggregate');

        $thisMonthValue = (clone $deliveredOrders)
            ->whereHas('statusHistories', function (Builder $query) use ($monthStart, $nextMonthStart): void {
                $query->where('to_status', 'Đã giao hàng')
                    ->where('created_at', '>=', $monthStart)
                    ->where('created_at', '<', $nextMonthStart);
            })
            ->selectRaw('COALESCE(SUM(COALESCE(total_amount, total)), 0) AS aggregate')
            ->value('aggregate');

        $recentOrders = Order::query()
            ->select(['id', 'user_id', 'order_code', 'status', 'total_amount', 'total', 'created_at'])
            ->with(['user:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'status' => $order->status,
                'customer' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                ] : null,
                'total' => self::formatAmount($order->total_amount ?? $order->total),
                'created_at' => $order->created_at?->toDateTimeString(),
            ])
            ->values();

        $bestSellers = DB::table('orders')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->where('orders.status', 'Đã giao hàng')
            ->whereNull('products.deleted_at')
            ->select('products.id', 'products.name', 'products.slug')
            ->selectRaw('SUM(order_items.quantity) AS quantity_sold')
            ->groupBy('products.id', 'products.name', 'products.slug')
            ->orderByDesc('quantity_sold')
            ->orderBy('products.id')
            ->limit(4)
            ->get()
            ->map(fn (object $product): array => [
                'id' => (int) $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'quantity_sold' => (int) $product->quantity_sold,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Lấy dữ liệu Dashboard thành công.',
            'data' => [
                'generated_at' => $generatedAt->toDateTimeString(),
                'totals' => [
                    'products' => Product::query()->count(),
                    'categories' => Category::query()->count(),
                    'brands' => Brand::query()->count(),
                    'orders' => Order::query()->count(),
                    'customers' => User::query()->where('role', 'customer')->count(),
                ],
                'order_status_counts' => $statusCounts,
                'other_order_statuses' => $otherStatuses,
                'delivered_order_value' => [
                    'all_time' => self::formatAmount($allTimeValue),
                    'this_month' => self::formatAmount($thisMonthValue),
                    'month' => $monthStart->format('Y-m'),
                ],
                'recent_orders' => $recentOrders,
                'best_sellers' => $bestSellers,
            ],
        ]);
    }

    private static function formatAmount(mixed $amount): string
    {
        if (is_float($amount)) {
            return number_format($amount, 2, '.', '');
        }

        $amount = (string) ($amount ?? '0');
        if (preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $amount, $parts) !== 1) {
            return number_format((float) $amount, 2, '.', '');
        }

        $digits = ltrim($parts[2], '0').str_pad(substr($parts[3] ?? '', 0, 2), 2, '0');
        $digits = $digits !== '' ? $digits : '000';

        if (isset($parts[3][2]) && $parts[3][2] >= '5') {
            for ($index = strlen($digits) - 1; $index >= 0; $index--) {
                if ($digits[$index] !== '9') {
                    $digits[$index] = (string) ((int) $digits[$index] + 1);
                    break;
                }

                $digits[$index] = '0';
                if ($index === 0) {
                    $digits = '1'.$digits;
                }
            }
        }

        $digits = str_pad($digits, 3, '0', STR_PAD_LEFT);
        $isZero = trim($digits, '0') === '';
        $sign = $parts[1] === '-' && ! $isZero ? '-' : '';

        return $sign.substr($digits, 0, -2).'.'.substr($digits, -2);
    }
}
