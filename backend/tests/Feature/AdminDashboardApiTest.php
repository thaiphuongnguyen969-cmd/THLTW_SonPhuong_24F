<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('customer');
            $table->string('status')->default('active');
            $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('order_code')->nullable();
            $table->decimal('total', 12, 2);
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->string('to_status');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('product_variant_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'order_items',
            'order_status_histories',
            'orders',
            'product_variants',
            'products',
            'brands',
            'categories',
            'personal_access_tokens',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_dashboard_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    public function test_dashboard_rejects_regular_users(): void
    {
        $customer = $this->createUser();
        $this->withToken($this->tokenFor($customer))->getJson('/api/admin/dashboard')->assertForbidden();
    }

    public function test_dashboard_rejects_inactive_admins(): void
    {
        $inactiveAdmin = $this->createUser('admin', 'inactive');
        $this->withToken($this->tokenFor($inactiveAdmin))->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    public function test_active_admin_can_access_dashboard(): void
    {
        $activeAdmin = $this->createUser('admin');
        $this->withToken($this->tokenFor($activeAdmin))
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_dashboard_totals_exclude_soft_deleted_catalog_and_admin_users(): void
    {
        $activeAdmin = $this->createUser('admin');
        $this->insert('categories', ['name' => 'Live category']);
        $this->insert('categories', ['name' => 'Deleted category', 'deleted_at' => now()]);
        $this->insert('brands', ['name' => 'Live brand']);
        $this->insert('brands', ['name' => 'Deleted brand', 'deleted_at' => now()]);
        $this->insert('products', ['name' => 'Live product', 'slug' => 'live-product']);
        $this->insert('products', ['name' => 'Inactive product', 'slug' => 'inactive-product', 'status' => 'inactive']);
        $this->insert('products', ['name' => 'Deleted product', 'slug' => 'deleted-product', 'deleted_at' => now()]);
        $this->createUser('customer');

        $this->withToken($this->tokenFor($activeAdmin))
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.products', 2)
            ->assertJsonPath('data.totals.categories', 1)
            ->assertJsonPath('data.totals.brands', 1)
            ->assertJsonPath('data.totals.customers', 1)
            ->assertJsonPath('data.totals.orders', 0);
    }

    public function test_dashboard_counts_known_and_unknown_statuses_and_delivered_values(): void
    {
        $admin = $this->createUser('admin');
        $monthStart = now()->startOfMonth();

        $this->insertOrder('Chờ xác nhận', '10.00');
        $deliveredWithAmount = $this->insertOrder('Đã giao hàng', '100.00', '80.00', $monthStart->copy()->addDays(2));
        $deliveredWithFallback = $this->insertOrder('Đã giao hàng', '200.00', null, $monthStart->copy()->addDays(3));
        $this->insertOrder('Đã giao hàng', '300.00', '250.00', null);
        $this->history($deliveredWithAmount, 'Đã giao hàng', $monthStart->copy()->addDays(2));
        $this->history($deliveredWithFallback, 'Đã giao hàng', $monthStart->copy()->addDays(3));
        $this->insertOrder('Đã hủy', '500.00', null, $monthStart->copy()->addDays(4));
        $this->insertOrder('Unknown legacy status', '12.00');

        $this->withToken($this->tokenFor($admin))
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.orders', 6)
            ->assertJsonPath('data.order_status_counts.Chờ xác nhận', 1)
            ->assertJsonPath('data.order_status_counts.Đã xác nhận', 0)
            ->assertJsonPath('data.order_status_counts.Đã giao hàng', 3)
            ->assertJsonPath('data.order_status_counts.Đã hủy', 1)
            ->assertJsonPath('data.other_order_statuses.0.status', 'Unknown legacy status')
            ->assertJsonPath('data.other_order_statuses.0.count', 1)
            ->assertJsonPath('data.delivered_order_value.all_time', '530.00')
            ->assertJsonPath('data.delivered_order_value.this_month', '280.00');
    }

    public function test_dashboard_recent_orders_are_limited_sorted_and_allow_missing_customers(): void
    {
        $admin = $this->createUser('admin');
        $customer = $this->createUser();
        $timestamp = now()->subDays(2);

        for ($index = 1; $index <= 12; $index++) {
            $this->insertOrder(
                'Chờ xác nhận',
                (string) $index,
                null,
                $timestamp->copy()->addMinutes($index),
                $index === 12 ? null : $customer->id,
                'ORDER-'.$index,
            );
        }
        $this->insertOrder('Chờ xác nhận', '10.00', null, $timestamp->copy()->addMinutes(12), null, 'TIE-ORDER');

        $response = $this->withToken($this->tokenFor($admin))->getJson('/api/admin/dashboard');
        $response->assertOk()
            ->assertJsonCount(10, 'data.recent_orders')
            ->assertJsonPath('data.recent_orders.0.order_code', 'TIE-ORDER')
            ->assertJsonPath('data.recent_orders.0.customer', null)
            ->assertJsonPath('data.recent_orders.1.order_code', 'ORDER-12')
            ->assertJsonPath('data.recent_orders.2.customer.id', $customer->id);

        $this->assertSame(
            ['id', 'order_code', 'status', 'customer', 'total', 'created_at'],
            array_keys($response->json('data.recent_orders.0')),
        );
    }

    public function test_dashboard_monthly_value_uses_distinct_delivery_histories_and_ignores_null_dates(): void
    {
        $admin = $this->createUser('admin');
        $monthStart = now()->startOfMonth();
        $delivered = $this->insertOrder('Đã giao hàng', '125.50', '100.00');
        $this->history($delivered, 'Đã giao hàng', $monthStart->copy()->addDay());
        $this->history($delivered, 'Đã giao hàng', $monthStart->copy()->addDays(2));

        $missingDate = $this->insertOrder('Đã giao hàng', '50.00', null);
        $this->history($missingDate, 'Đã giao hàng', null);

        $oldDelivery = $this->insertOrder('Đã giao hàng', '75.00', null);
        $this->history($oldDelivery, 'Đã giao hàng', $monthStart->copy()->subSecond());

        $this->withToken($this->tokenFor($admin))
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.delivered_order_value.all_time', '225.00')
            ->assertJsonPath('data.delivered_order_value.this_month', '100.00');
    }

    public function test_dashboard_returns_an_empty_response_shape_when_database_has_no_records(): void
    {
        $admin = $this->createUser('admin');

        $this->withToken($this->tokenFor($admin))
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.products', 0)
            ->assertJsonPath('data.totals.orders', 0)
            ->assertJsonPath('data.order_status_counts.Đã giao hàng', 0)
            ->assertJsonPath('data.delivered_order_value.all_time', '0.00')
            ->assertJsonPath('data.delivered_order_value.this_month', '0.00')
            ->assertJsonPath('data.recent_orders', [])
            ->assertJsonPath('data.best_sellers', []);
    }

    public function test_dashboard_best_sellers_only_use_delivered_orders_with_linked_variants(): void
    {
        $admin = $this->createUser('admin');
        $productId = $this->insert('products', ['name' => 'Best seller', 'slug' => 'best-seller']);
        $variantId = $this->insert('product_variants', ['product_id' => $productId]);
        $deliveredOrder = $this->insertOrder('Đã giao hàng', '10.00');
        $this->insert('order_items', [
            'order_id' => $deliveredOrder,
            'product_variant_id' => $variantId,
            'quantity' => 3,
        ]);
        $cancelledOrder = $this->insertOrder('Đã hủy', '10.00');
        $this->insert('order_items', [
            'order_id' => $cancelledOrder,
            'product_variant_id' => $variantId,
            'quantity' => 20,
        ]);
        $this->insert('order_items', [
            'order_id' => $deliveredOrder,
            'product_variant_id' => null,
            'quantity' => 50,
        ]);
        $this->insert('order_items', [
            'order_id' => $deliveredOrder,
            'product_variant_id' => 999,
            'quantity' => 100,
        ]);

        $this->withToken($this->tokenFor($admin))
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.best_sellers.0.id', $productId)
            ->assertJsonPath('data.best_sellers.0.quantity_sold', 3);
    }

    private function createUser(string $role = 'customer', string $status = 'active'): User
    {
        $user = User::create([
            'name' => ucfirst($role).' User',
            'email' => $role.'-'.$status.'-'.uniqid().'@example.test',
            'password' => Hash::make('password'),
        ]);
        $user->forceFill(['role' => $role, 'status' => $status])->save();

        return $user;
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    private function insert(string $table, array $attributes): int
    {
        $timestamp = now();
        $id = DB::table($table)->insertGetId([
            ...$attributes,
            'created_at' => $attributes['created_at'] ?? $timestamp,
            'updated_at' => $attributes['updated_at'] ?? $timestamp,
        ]);

        return (int) $id;
    }

    private function insertOrder(
        string $status,
        string $total,
        ?string $totalAmount = null,
        mixed $createdAt = null,
        ?int $userId = null,
        ?string $orderCode = null,
    ): int {
        return $this->insert('orders', [
            'user_id' => $userId,
            'order_code' => $orderCode,
            'total' => $total,
            'total_amount' => $totalAmount,
            'status' => $status,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    private function history(int $orderId, string $status, mixed $createdAt): void
    {
        DB::table('order_status_histories')->insert([
            'order_id' => $orderId,
            'to_status' => $status,
            'created_at' => $createdAt,
        ]);
    }
}
