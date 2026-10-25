<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\Color;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 20)->default('customer');
            $table->string('avatar')->nullable();
            $table->string('status', 20)->default('active');
            $table->rememberToken();
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
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
            $table->string('name', 100);
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 200);
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('image')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('sizes', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('colors', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('hex_code', 7)->nullable();
            $table->timestamps();
        });
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('size_id')->constrained()->restrictOnDelete();
            $table->foreignId('color_id')->constrained()->restrictOnDelete();
            $table->string('sku', 100)->unique();
            $table->decimal('price', 12, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['product_id', 'size_id', 'color_id']);
        });
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('recipient_name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('ward', 100)->nullable();
            $table->text('address_detail');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['cart_id', 'product_variant_id']);
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_code', 100)->nullable()->unique();
            $table->decimal('total', 12, 2);
            $table->string('status', 50)->default('Chờ xác nhận');
            $table->string('address', 255);
            $table->string('phone', 20);
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('shipping_fee', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_status', 50)->nullable();
            $table->text('shipping_address_detail')->nullable();
            $table->string('shipping_phone', 20)->nullable();
            $table->string('promotion_code', 100)->nullable();
            $table->text('note')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->foreignId('promotion_id')->nullable();
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 200);
            $table->string('sku', 100)->nullable();
            $table->string('size', 50)->nullable();
            $table->string('color', 100)->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('payment_method', 50);
            $table->string('transaction_code', 150)->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status', 50);
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'order_status_histories',
            'payments',
            'order_items',
            'orders',
            'cart_items',
            'carts',
            'addresses',
            'product_variants',
            'colors',
            'sizes',
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

    public function test_order_routes_require_authenticated_active_users(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->postJson('/api/orders', [])->assertUnauthorized();
        $this->getJson('/api/orders/1')->assertUnauthorized();
        $this->postJson('/api/orders/1/cancel')->assertUnauthorized();

        $inactive = $this->createUser('inactive', 'order-inactive@example.test');
        $this->withBearerToken($inactive['token'])->getJson('/api/orders')->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->postJson('/api/orders', [])->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->getJson('/api/orders/1')->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->postJson('/api/orders/1/cancel')->assertUnauthorized();
    }

    public function test_admin_order_routes_require_an_active_admin(): void
    {
        $this->getJson('/api/admin/orders')->assertUnauthorized();
        $this->getJson('/api/admin/orders/1')->assertUnauthorized();
        $this->patchJson('/api/admin/orders/1/status', [])->assertUnauthorized();

        $customer = $this->createUser('active', 'admin-orders-customer@example.test');
        $this->withBearerToken($customer['token'])->getJson('/api/admin/orders')->assertForbidden();
        $this->withBearerToken($customer['token'])->getJson('/api/admin/orders/1')->assertForbidden();
        $this->withBearerToken($customer['token'])->patchJson('/api/admin/orders/1/status', [])->assertForbidden();

        $inactiveAdmin = $this->createUser('inactive', 'admin-orders-inactive@example.test');
        $inactiveAdmin['user']->forceFill(['role' => 'admin'])->save();
        $this->withBearerToken($inactiveAdmin['token'])->getJson('/api/admin/orders')->assertUnauthorized();
    }

    public function test_admin_can_search_filter_paginate_and_read_order_details(): void
    {
        $customer = $this->createUser('active', 'findable-order-customer@example.test');
        $admin = $this->createUser('active', 'admin-orders@example.test');
        $admin['user']->forceFill(['role' => 'admin'])->save();

        $older = $this->createLegacyOrder($customer['user'], ['order_code' => 'SP-OLD-001']);
        $older->forceFill(['created_at' => now()->subDay()])->saveQuietly();
        $newer = $this->createLegacyOrder($customer['user'], ['order_code' => 'SP-NEW-002']);
        $newer->forceFill(['created_at' => now()])->saveQuietly();
        $other = $this->createUser('active', 'other-order-customer@example.test');
        $this->createLegacyOrder($other['user'], ['order_code' => 'OTHER-003']);

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/orders?search=findable-order&per_page=1&page=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.0.customer.email', 'findable-order-customer@example.test')
            ->assertJsonPath('data.0.order_code', 'SP-NEW-002')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/orders?search=findable-order&per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.id', $older->id);

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/orders?status=Chờ%20xác%20nhận')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/orders/'.$newer->id)
            ->assertOk()
            ->assertJsonPath('data.customer.name', 'Order Test User')
            ->assertJsonPath('data.customer.email', 'findable-order-customer@example.test')
            ->assertJsonPath('data.shipping_address.address_detail', 'Legacy address')
            ->assertJsonPath('data.order_code', 'SP-NEW-002');
    }

    public function test_admin_order_index_validates_pagination_search_and_status_filters(): void
    {
        $admin = $this->createUser('active', 'admin-orders-validation@example.test');
        $admin['user']->forceFill(['role' => 'admin'])->save();

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/orders?per_page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/orders?search='.str_repeat('x', 201))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/orders?page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('page');
    }

    public function test_admin_can_advance_orders_only_through_allowed_transitions_and_customer_sees_latest_status(): void
    {
        $buyer = $this->createUser('active', 'order-transition-buyer@example.test');
        $admin = $this->createUser('active', 'order-transition-admin@example.test');
        $admin['user']->forceFill(['role' => 'admin'])->save();
        $order = $this->createLegacyOrder($buyer['user'], [
            'order_code' => 'SP-TRANSITION-001',
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ]);

        $transitions = [
            'Chờ xác nhận' => 'Đã xác nhận',
            'Đã xác nhận' => 'Đang chuẩn bị hàng',
            'Đang chuẩn bị hàng' => 'Đang giao hàng',
            'Đang giao hàng' => 'Đã giao hàng',
        ];
        foreach ($transitions as $from => $to) {
            $this->withBearerToken($admin['token'])
                ->patchJson('/api/admin/orders/'.$order->id.'/status', [
                    'expected_status' => $from,
                    'status' => $to,
                    'reason' => 'Cập nhật tiến độ',
                ])
                ->assertOk()
                ->assertJsonPath('data.status', $to)
                ->assertJsonPath('data.payment_status', 'unpaid');
        }

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'changed_by' => $admin['user']->id,
            'from_status' => 'Đang giao hàng',
            'to_status' => 'Đã giao hàng',
            'note' => 'Cập nhật tiến độ',
        ]);
        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$order->id.'/status', [
                'expected_status' => 'Đã giao hàng',
                'status' => 'Đã xác nhận',
            ])
            ->assertStatus(409);

        $this->withBearerToken($buyer['token'])
            ->getJson('/api/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'Đã giao hàng')
            ->assertJsonPath('data.payment_status', 'unpaid');

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/orders?status='.rawurlencode('Đã giao hàng'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.allowed_status_transitions', []);
        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.status_histories.0.changed_by_name', 'Order Test User');
    }

    public function test_admin_cancellation_reuses_stock_restoration_and_is_idempotent(): void
    {
        $buyer = $this->createUser('active', 'order-admin-cancel-buyer@example.test');
        $admin = $this->createUser('active', 'order-admin-cancel-admin@example.test');
        $admin['user']->forceFill(['role' => 'admin'])->save();
        $address = $this->createAddress($buyer['user']);
        $variant = $this->createVariant(['stock' => 5]);
        $this->createCartItem($buyer['user'], $variant, 2);
        $orderId = $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders', ['address_id' => $address->id])
            ->assertCreated()
            ->json('data.id');
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 3]);

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$orderId.'/status', [
                'expected_status' => 'Chờ xác nhận',
                'status' => 'Đã xác nhận',
            ])
            ->assertOk();
        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$orderId.'/status', [
                'expected_status' => 'Đã xác nhận',
                'status' => 'Đang chuẩn bị hàng',
            ])
            ->assertOk();

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$orderId.'/status', [
                'expected_status' => 'Đang chuẩn bị hàng',
                'status' => 'Đã hủy',
                'reason' => 'Khách yêu cầu hủy',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Đã hủy')
            ->assertJsonPath('data.cancelled_reason', 'Khách yêu cầu hủy')
            ->assertJsonPath('data.payment_status', 'unpaid');

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 5]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'payment_method' => 'cod',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $orderId,
            'changed_by' => $admin['user']->id,
            'from_status' => 'Đang chuẩn bị hàng',
            'to_status' => 'Đã hủy',
            'note' => 'Khách yêu cầu hủy',
        ]);

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$orderId.'/status', [
                'expected_status' => 'Đã hủy',
                'status' => 'Đã giao hàng',
            ])
            ->assertStatus(409);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 5]);
        $this->assertSame(4, \App\Models\OrderStatusHistory::query()->where('order_id', $orderId)->count());

        $this->withBearerToken($buyer['token'])
            ->getJson('/api/orders/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.status', 'Đã hủy');
    }

    public function test_admin_status_update_validates_payload_and_rejects_stale_state(): void
    {
        $buyer = $this->createUser('active', 'order-stale-buyer@example.test');
        $admin = $this->createUser('active', 'order-stale-admin@example.test');
        $admin['user']->forceFill(['role' => 'admin'])->save();
        $order = $this->createLegacyOrder($buyer['user']);

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$order->id.'/status', [
                'expected_status' => 'Chờ xác nhận',
                'status' => 'paid',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$order->id.'/status', [
                'expected_status' => 'Chờ xác nhận',
                'status' => 'Đã giao hàng',
            ])
            ->assertStatus(409);

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$order->id.'/status', [
                'expected_status' => 'Chờ xác nhận',
                'status' => 'Đã xác nhận',
                'reason' => str_repeat('x', 1001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$order->id.'/status', [
                'expected_status' => 'Chờ xác nhận',
                'status' => 'Đã xác nhận',
                'unknown' => 'not allowed',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('unknown');

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/admin/orders/'.$order->id.'/status', [
                'expected_status' => 'Đã xác nhận',
                'status' => 'Đã xác nhận',
            ])
            ->assertStatus(409);
        $this->assertSame(0, \App\Models\OrderStatusHistory::query()->where('order_id', $order->id)->count());
    }

    public function test_checkout_rejects_an_address_owned_by_another_user(): void
    {
        $buyer = $this->createUser('active', 'order-address-buyer@example.test');
        $owner = $this->createUser('active', 'order-address-owner@example.test');
        $address = $this->createAddress($owner['user']);
        $this->createCartItem($buyer['user'], $this->createVariant(), 1);

        $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders', ['address_id' => $address->id])
            ->assertNotFound();

        $this->assertSame(0, Order::query()->count());
    }

    public function test_checkout_rejects_an_empty_cart(): void
    {
        $buyer = $this->createUser('active', 'order-empty@example.test');
        $address = $this->createAddress($buyer['user']);

        $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders', ['address_id' => $address->id])
            ->assertStatus(409);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_checkout_rejects_unavailable_variant_product_category_brand_and_soft_deleted_product(): void
    {
        $buyer = $this->createUser('active', 'order-unavailable@example.test');
        $address = $this->createAddress($buyer['user']);
        $cases = [
            ['variant' => ['status' => 'inactive']],
            ['product' => ['status' => 'inactive']],
            ['category' => ['status' => 'inactive']],
            ['brand' => ['status' => 'inactive']],
            ['soft_delete' => true],
        ];

        foreach ($cases as $index => $case) {
            $variant = $this->createVariant($case);
            $this->createCartItem($buyer['user'], $variant, 1);
            $this->withBearerToken($buyer['token'])
                ->postJson('/api/orders', ['address_id' => $address->id])
                ->assertStatus(409);

            $this->assertSame(0, Order::query()->count());
            $this->assertDatabaseHas('product_variants', [
                'id' => $variant->id,
                'stock' => 5,
            ]);
            $buyer['user']->cart()->first()->items()->delete();
        }
    }

    public function test_checkout_rejects_zero_or_insufficient_stock_without_partial_changes(): void
    {
        $buyer = $this->createUser('active', 'order-stock@example.test');
        $address = $this->createAddress($buyer['user']);
        $variant = $this->createVariant(['stock' => 0]);
        $cartItem = $this->createCartItem($buyer['user'], $variant, 1);

        $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders', ['address_id' => $address->id])
            ->assertStatus(409);
        $this->assertSame(0, Order::query()->count());
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 1]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 0]);

        $variant->update(['stock' => 1]);
        $cartItem->update(['quantity' => 2]);
        $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders', ['address_id' => $address->id])
            ->assertStatus(409);
        $this->assertSame(0, Order::query()->count());
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 1]);
    }

    public function test_sequential_checkouts_cannot_sell_more_than_remaining_stock(): void
    {
        $firstBuyer = $this->createUser('active', 'order-competition-one@example.test');
        $secondBuyer = $this->createUser('active', 'order-competition-two@example.test');
        $firstAddress = $this->createAddress($firstBuyer['user']);
        $secondAddress = $this->createAddress($secondBuyer['user']);
        $variant = $this->createVariant(['stock' => 3]);
        $firstItem = $this->createCartItem($firstBuyer['user'], $variant, 2);
        $secondItem = $this->createCartItem($secondBuyer['user'], $variant, 2);

        $this->withBearerToken($firstBuyer['token'])
            ->postJson('/api/orders', ['address_id' => $firstAddress->id])
            ->assertCreated();
        $this->withBearerToken($secondBuyer['token'])
            ->postJson('/api/orders', ['address_id' => $secondAddress->id])
            ->assertStatus(409);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 1]);
        $this->assertDatabaseMissing('cart_items', ['id' => $firstItem->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $secondItem->id, 'quantity' => 2]);
        $this->assertSame(1, Order::query()->count());
    }

    public function test_checkout_rejects_client_system_fields_and_unknown_fields(): void
    {
        $buyer = $this->createUser('active', 'order-fields@example.test');
        $address = $this->createAddress($buyer['user']);
        $this->createCartItem($buyer['user'], $this->createVariant(), 1);

        foreach ([
            'user_id' => $buyer['user']->id,
            'total' => 1,
            'total_amount' => 1,
            'subtotal' => 1,
            'unit_price' => 1,
            'quantity' => 100,
            'status' => 'Đã giao',
            'payment_status' => 'paid',
            'stock' => 999,
            'payment_method' => 'card',
            'unknown' => true,
        ] as $field => $value) {
            $this->withBearerToken($buyer['token'])
                ->postJson('/api/orders', ['address_id' => $address->id, $field => $value])
                ->assertUnprocessable();
        }

        $this->assertSame(0, Order::query()->count());
    }

    public function test_checkout_recalculates_current_price_and_creates_snapshots_payment_and_history(): void
    {
        $buyer = $this->createUser('active', 'order-success@example.test');
        $address = $this->createAddress($buyer['user'], [
            'recipient_name' => 'Original Recipient',
            'phone' => '0123456789',
            'province' => 'Ha Noi',
            'district' => 'Ba Dinh',
            'ward' => 'Phuc Xa',
            'address_detail' => str_repeat('Long street ', 30),
        ]);
        $variant = $this->createVariant([
            'variant' => ['price' => null],
            'product' => ['price' => '120.00', 'sale_price' => '80.00'],
            'stock' => 5,
        ]);
        $snapshotSku = $variant->sku;
        $cartItem = $this->createCartItem($buyer['user'], $variant, 2);

        $response = $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders', [
                'address_id' => $address->id,
                'note' => 'Please call.',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Chờ xác nhận')
            ->assertJsonPath('data.payment_method', 'cod')
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.amounts.subtotal', '160.00')
            ->assertJsonPath('data.amounts.discount', '0.00')
            ->assertJsonPath('data.amounts.shipping_fee', '0.00')
            ->assertJsonPath('data.amounts.total', '160.00')
            ->assertJsonPath('data.items.0.unit_price', '80.00')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.subtotal', '160.00')
            ->assertJsonPath('data.payments.0.payment_method', 'cod')
            ->assertJsonPath('data.payments.0.status', 'pending')
            ->assertJsonPath('data.status_histories.0.from_status', null)
            ->assertJsonPath('data.status_histories.0.to_status', 'Chờ xác nhận');

        $orderId = $response->json('data.id');
        $orderCode = $response->json('data.order_code');
        $this->assertNotEmpty($orderCode);
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'order_code' => $orderCode,
            'total' => '160.00',
            'total_amount' => '160.00',
            'subtotal' => '160.00',
            'discount_amount' => '0.00',
            'shipping_fee' => '0.00',
            'user_id' => $buyer['user']->id,
            'note' => 'Please call.',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_variant_id' => $variant->id,
            'product_name' => 'Original Product',
            'sku' => $variant->sku,
            'size' => 'M',
            'color' => 'Blue',
            'price' => '80.00',
            'quantity' => 2,
            'subtotal' => '160.00',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'payment_method' => 'cod',
            'amount' => '160.00',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $orderId,
            'from_status' => null,
            'to_status' => 'Chờ xác nhận',
        ]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 3]);
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);

        $snapshot = json_decode((string) Order::findOrFail($orderId)->shipping_address_detail, true);
        $this->assertSame('Original Recipient', $snapshot['recipient_name']);
        $this->assertSame(str_repeat('Long street ', 30), $snapshot['address_detail']);
        $address->update(['recipient_name' => 'Changed Recipient', 'address_detail' => 'Changed address']);
        $variant->product->update(['name' => 'Changed Product', 'price' => '999.00']);
        $variant->update(['sku' => 'CHANGED-SKU']);

        $this->withBearerToken($buyer['token'])
            ->getJson('/api/orders/'.$orderId)
            ->assertOk()
            ->assertJsonPath('data.order_code', $orderCode)
            ->assertJsonPath('data.shipping_address.recipient_name', 'Original Recipient')
            ->assertJsonPath('data.shipping_address.address_detail', str_repeat('Long street ', 30))
            ->assertJsonPath('data.items.0.product_name', 'Original Product')
            ->assertJsonPath('data.items.0.sku', $snapshotSku)
            ->assertJsonPath('data.items.0.unit_price', '80.00');
    }

    public function test_new_checkouts_receive_distinct_stable_order_codes(): void
    {
        $buyer = $this->createUser('active', 'order-code-unique@example.test');
        $address = $this->createAddress($buyer['user']);
        $codes = [];

        foreach ([1, 2] as $index) {
            $variant = $this->createVariant();
            $this->createCartItem($buyer['user'], $variant, 1);
            $order = $this->withBearerToken($buyer['token'])
                ->postJson('/api/orders', ['address_id' => $address->id])
                ->assertCreated();
            $codes[] = $order->json('data.order_code');
        }

        $this->assertNotEmpty($codes[0]);
        $this->assertNotEmpty($codes[1]);
        $this->assertNotSame($codes[0], $codes[1]);
    }

    public function test_checkout_rolls_back_order_stock_and_cart_if_order_item_creation_fails(): void
    {
        $buyer = $this->createUser('active', 'order-rollback@example.test');
        $address = $this->createAddress($buyer['user']);
        $variant = $this->createVariant(['stock' => 5]);
        $cartItem = $this->createCartItem($buyer['user'], $variant, 2);
        Event::listen('eloquent.creating: '.OrderItem::class, function (): void {
            throw new \RuntimeException('Simulated order item failure.');
        });

        $response = $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders', ['address_id' => $address->id]);
        Event::forget('eloquent.creating: '.OrderItem::class);

        $this->assertSame(500, $response->status());
        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, \App\Models\Payment::query()->count());
        $this->assertSame(0, \App\Models\OrderStatusHistory::query()->count());
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 5]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 2]);
    }

    public function test_order_list_and_detail_only_expose_the_authenticated_users_orders(): void
    {
        $owner = $this->createUser('active', 'order-owner@example.test');
        $other = $this->createUser('active', 'order-other@example.test');
        $ownerOrder = $this->createLegacyOrder($owner['user']);
        $otherOrder = $this->createLegacyOrder($other['user']);

        $this->withBearerToken($owner['token'])
            ->getJson('/api/orders?per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownerOrder->id)
            ->assertJsonPath('meta.per_page', 1);
        $this->withBearerToken($owner['token'])->getJson('/api/orders/'.$ownerOrder->id)->assertOk();
        $this->withBearerToken($owner['token'])->getJson('/api/orders/'.$otherOrder->id)->assertNotFound();
        $this->withBearerToken($owner['token'])->getJson('/api/orders/99999')->assertNotFound();
    }

    public function test_legacy_order_uses_total_when_total_amount_is_null(): void
    {
        $user = $this->createUser('active', 'order-legacy-total@example.test');
        $order = $this->createLegacyOrder($user['user'], [
            'total' => '123.45',
            'subtotal' => null,
            'discount_amount' => null,
            'shipping_fee' => null,
            'total_amount' => null,
        ]);

        $this->withBearerToken($user['token'])
            ->getJson('/api/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.amounts.total', '123.45')
            ->assertJsonPath('data.amounts.subtotal', null)
            ->assertJsonPath('data.amounts.discount', null)
            ->assertJsonPath('data.amounts.shipping_fee', null);
    }

    public function test_checkout_truncates_legacy_address_at_utf8_byte_boundary_and_preserves_full_snapshot(): void
    {
        $user = $this->createUser('active', 'order-utf8-address@example.test');
        $address = $this->createAddress($user['user']);
        $cases = [
            str_repeat('á', 127).'x',
            str_repeat('á', 128),
        ];

        foreach ($cases as $index => $fullAddress) {
            $address->update(['address_detail' => $fullAddress]);
            $variant = $this->createVariant();
            $this->createCartItem($user['user'], $variant, 1);

            $response = $this->withBearerToken($user['token'])
                ->postJson('/api/orders', ['address_id' => $address->id])
                ->assertCreated();

            $orderId = $response->json('data.id');
            $order = Order::findOrFail($orderId);
            $this->assertLessThanOrEqual(255, strlen($order->address));
            $this->assertTrue(mb_check_encoding($order->address, 'UTF-8'));
            $this->assertSame(
                mb_strcut($fullAddress, 0, 255, 'UTF-8'),
                $order->address
            );

            $snapshot = json_decode((string) $order->shipping_address_detail, true);
            $this->assertSame($fullAddress, $snapshot['address_detail']);
            $this->assertSame($fullAddress, $response->json('data.shipping_address.address_detail'));
        }
    }

    public function test_customer_cancellation_restores_stock_once_and_writes_history(): void
    {
        $buyer = $this->createUser('active', 'order-cancel@example.test');
        $address = $this->createAddress($buyer['user']);
        $variant = $this->createVariant(['stock' => 5]);
        $this->createCartItem($buyer['user'], $variant, 2);
        $created = $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders', ['address_id' => $address->id])
            ->assertCreated();
        $orderId = $created->json('data.id');

        $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders/'.$orderId.'/cancel', ['reason' => 'Changed my mind'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Đã hủy')
            ->assertJsonPath('data.cancelled_reason', 'Changed my mind');
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 5]);
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'Đã hủy']);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $orderId,
            'from_status' => 'Chờ xác nhận',
            'to_status' => 'Đã hủy',
            'note' => 'Changed my mind',
        ]);

        $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders/'.$orderId.'/cancel')
            ->assertStatus(409);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 5]);
        $this->assertSame(
            1,
            \App\Models\OrderStatusHistory::query()
                ->where('order_id', $orderId)
                ->where('to_status', 'Đã hủy')
                ->count()
        );
    }

    public function test_customer_cannot_cancel_another_users_order(): void
    {
        $owner = $this->createUser('active', 'order-cancel-owner@example.test');
        $attacker = $this->createUser('active', 'order-cancel-attacker@example.test');
        $address = $this->createAddress($owner['user']);
        $variant = $this->createVariant(['stock' => 4]);
        $this->createCartItem($owner['user'], $variant, 1);
        $orderId = $this->withBearerToken($owner['token'])
            ->postJson('/api/orders', ['address_id' => $address->id])
            ->assertCreated()
            ->json('data.id');

        $this->withBearerToken($attacker['token'])
            ->postJson('/api/orders/'.$orderId.'/cancel')
            ->assertNotFound();
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'Chờ xác nhận']);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 3]);
    }

    public function test_customer_cannot_cancel_an_order_in_another_status_or_without_checkout_marker(): void
    {
        $buyer = $this->createUser('active', 'order-cancel-state@example.test');
        $variant = $this->createVariant(['stock' => 3]);
        $order = $this->createLegacyOrder($buyer['user'], ['status' => 'Đã xác nhận']);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => 'Old item',
            'price' => '10.00',
            'quantity' => 1,
            'subtotal' => '10.00',
        ]);

        $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders/'.$order->id.'/cancel')
            ->assertStatus(409);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 3]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'Đã xác nhận']);
    }

    public function test_checkout_money_stays_within_decimal_12_2_bounds(): void
    {
        $buyer = $this->createUser('active', 'order-money-limit@example.test');
        $address = $this->createAddress($buyer['user']);
        $variant = $this->createVariant([
            'variant' => ['price' => '9999999999.99'],
            'product' => ['price' => '9999999999.99'],
            'stock' => 2,
        ]);
        $this->createCartItem($buyer['user'], $variant, 2);

        $this->withBearerToken($buyer['token'])
            ->postJson('/api/orders', ['address_id' => $address->id])
            ->assertStatus(422);
        $this->assertSame(0, Order::query()->count());
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 2]);
    }

    /**
     * @return array{user: User, token: string}
     */
    private function createUser(string $status, string $email): array
    {
        $user = User::create([
            'name' => 'Order Test User',
            'email' => $email,
            'password' => Hash::make('secret123'),
        ]);
        $user->forceFill(['role' => 'customer', 'status' => $status])->save();

        return [
            'user' => $user,
            'token' => $user->createToken('order-test')->plainTextToken,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAddress(User $user, array $overrides = []): Address
    {
        return $user->addresses()->create([
            'label' => 'Home',
            'recipient_name' => 'Order Recipient',
            'phone' => '0123456789',
            'province' => 'Ha Noi',
            'district' => 'Ba Dinh',
            'ward' => 'Phuc Xa',
            'address_detail' => '1 Example Street',
            'is_default' => true,
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createVariant(array $overrides = []): ProductVariant
    {
        $category = Category::create([
            'name' => 'Clothing',
            'slug' => 'clothing-'.uniqid(),
            'status' => $overrides['category']['status'] ?? 'active',
        ]);
        $brand = Brand::create([
            'name' => 'Brand '.uniqid(),
            'slug' => 'brand-'.uniqid(),
            'status' => $overrides['brand']['status'] ?? 'active',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => $overrides['product']['name'] ?? 'Original Product',
            'slug' => 'product-'.uniqid(),
            'price' => $overrides['product']['price'] ?? '100.00',
            'sale_price' => $overrides['product']['sale_price'] ?? null,
            'status' => $overrides['product']['status'] ?? 'active',
        ]);
        if ($overrides['soft_delete'] ?? false) {
            $product->delete();
        }
        $size = Size::firstOrCreate(['name' => 'M']);
        $color = Color::firstOrCreate(['name' => 'Blue']);

        return ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'SKU-'.uniqid(),
            'price' => array_key_exists('price', $overrides['variant'] ?? [])
                ? $overrides['variant']['price']
                : '100.00',
            'stock' => $overrides['stock'] ?? 5,
            'status' => $overrides['variant']['status'] ?? 'active',
        ]);
    }

    private function createCartItem(User $user, ProductVariant $variant, int $quantity): \App\Models\CartItem
    {
        $cart = $user->cart()->firstOrCreate([]);

        return $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createLegacyOrder(User $user, array $overrides = []): Order
    {
        return $user->orders()->create([
            'total' => '10.00',
            'status' => 'Chờ xác nhận',
            'address' => 'Legacy address',
            'phone' => '0123456789',
            ...$overrides,
        ]);
    }

    private function withBearerToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
