<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductVariantApiTest extends TestCase
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
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('image')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
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
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->foreignId('color_id')->constrained('colors')->restrictOnDelete();
            $table->string('sku', 100)->unique();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['product_id', 'size_id', 'color_id']);
        });

        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 50)->default('pending');
            $table->string('address', 255)->default('');
            $table->string('phone', 20)->default('');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()
                ->constrained('product_variants')->nullOnDelete();
            $table->string('product_name', 200);
            $table->string('sku', 100)->nullable();
            $table->string('size', 50)->nullable();
            $table->string('color', 100)->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'order_items',
            'orders',
            'cart_items',
            'carts',
            'product_variants',
            'colors',
            'sizes',
            'products',
            'categories',
            'personal_access_tokens',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_active_admin_can_create_list_show_update_and_delete_variants(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-admin@example.test');
        $product = $this->createProduct('shirt');
        $size = Size::create(['name' => 'M']);
        $otherSize = Size::create(['name' => 'L']);
        $color = Color::create(['name' => 'Blue', 'hex_code' => '#0000FF']);
        $otherColor = Color::create(['name' => 'Black']);
        $basePath = '/api/admin/products/'.$product->slug.'/variants';

        $created = $this->withBearerToken($admin['token'])
            ->postJson($basePath, [
                'sku' => 'SHIRT-M-BLUE',
                'size_id' => $size->id,
                'color_id' => $color->id,
                'price' => null,
                'stock' => 12,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sku', 'SHIRT-M-BLUE')
            ->assertJsonPath('data.stock', 12)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.size.name', 'M')
            ->assertJsonPath('data.color.name', 'Blue');

        $variantId = $created->json('data.id');
        $second = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $otherSize->id,
            'color_id' => $otherColor->id,
            'sku' => 'SHIRT-L-BLACK',
            'stock' => 2,
        ]);

        $this->withBearerToken($admin['token'])
            ->getJson($basePath.'?per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data');

        $this->withBearerToken($admin['token'])
            ->putJson($basePath.'/'.$variantId, [
                'sku' => 'SHIRT-M-BLUE',
                'size_id' => $size->id,
                'color_id' => $color->id,
                'price' => 125,
                'expected_stock' => 12,
                'stock' => 10,
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('data.price', '125.00')
            ->assertJsonPath('data.stock', 10);

        $this->withBearerToken($admin['token'])
            ->getJson($basePath.'/'.$variantId)
            ->assertOk()
            ->assertJsonPath('data.id', $variantId);

        $this->withBearerToken($admin['token'])
            ->patchJson($basePath.'/'.$variantId, [
                'size_id' => $otherSize->id,
                'color_id' => $color->id,
                'expected_stock' => 10,
                'stock' => 8,
            ])
            ->assertOk()
            ->assertJsonPath('data.sku', 'SHIRT-M-BLUE')
            ->assertJsonPath('data.stock', 8)
            ->assertJsonPath('data.size.name', 'L');

        $this->withBearerToken($admin['token'])
            ->deleteJson($basePath.'/'.$variantId)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('product_variants', ['id' => $variantId]);
        $this->assertDatabaseHas('product_variants', ['id' => $second->id]);
    }

    public function test_variant_management_requires_auth_active_account_and_admin_role(): void
    {
        $product = $this->createProduct('auth-shirt');
        $path = '/api/admin/products/'.$product->slug.'/variants';
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);

        $this->postJson($path, [])->assertUnauthorized();

        $customer = $this->createUser('customer', 'active', 'variant-customer@example.test');
        $this->withBearerToken($customer['token'])->postJson($path, [])->assertForbidden();

        $inactiveAdmin = $this->createUser('admin', 'inactive', 'variant-inactive@example.test');
        $this->withBearerToken($inactiveAdmin['token'])
            ->postJson($path, [
                'sku' => 'INACTIVE-ADMIN',
                'size_id' => $size->id,
                'color_id' => $color->id,
            ])
            ->assertUnauthorized();

        $this->assertSame(0, ProductVariant::count());
    }

    public function test_validation_rejects_duplicate_sku_combinations_invalid_options_and_system_fields(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-validation@example.test');
        $product = $this->createProduct('validation-shirt');
        $size = Size::create(['name' => 'M']);
        $otherSize = Size::create(['name' => 'L']);
        $color = Color::create(['name' => 'Blue']);
        $otherColor = Color::create(['name' => 'Black']);
        $path = '/api/admin/products/'.$product->slug.'/variants';
        $existing = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'EXISTING-SKU',
            'stock' => 3,
        ]);

        $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'EXISTING-SKU',
                'size_id' => $otherSize->id,
                'color_id' => $otherColor->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sku');

        $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'DUPLICATE-COMBINATION',
                'size_id' => $size->id,
                'color_id' => $color->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('size_id');

        $otherVariant = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $otherSize->id,
            'color_id' => $otherColor->id,
            'sku' => 'OTHER-COMBINATION',
        ]);

        $this->withBearerToken($admin['token'])
            ->putJson($path.'/'.$otherVariant->id, [
                'sku' => 'OTHER-COMBINATION',
                'size_id' => $size->id,
                'color_id' => $color->id,
                'price' => null,
                'expected_stock' => 0,
                'stock' => 0,
                'status' => 'active',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('size_id');

        $this->assertDatabaseHas('product_variants', [
            'id' => $otherVariant->id,
            'size_id' => $otherSize->id,
            'color_id' => $otherColor->id,
        ]);

        $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'INVALID-OPTIONS',
                'size_id' => 9999,
                'color_id' => 9999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['size_id', 'color_id']);

        $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'SYSTEM-FIELDS',
                'size_id' => $otherSize->id,
                'color_id' => $otherColor->id,
                'product_id' => $product->id,
                'created_at' => now()->toDateTimeString(),
                'id' => 99,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_id', 'created_at', 'id']);

        $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'INVALID-STATUS',
                'size_id' => $otherSize->id,
                'color_id' => $otherColor->id,
                'status' => 'archived',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(2, ProductVariant::count());
        $this->assertDatabaseHas('product_variants', ['id' => $existing->id]);
    }

    public function test_variant_promotion_validates_both_fields_and_returns_authoritative_pricing(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-promotion@example.test');
        $product = $this->createProduct('promotion-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $path = '/api/admin/products/'.$product->slug.'/variants';

        $created = $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'PROMOTION-SKU',
                'size_id' => $size->id,
                'color_id' => $color->id,
                'price' => '500000.00',
                'sale_price' => '400000.00',
                'stock' => 3,
            ])
            ->assertCreated()
            ->assertJsonPath('data.original_price', '500000.00')
            ->assertJsonPath('data.effective_price', '400000.00')
            ->assertJsonPath('data.is_promotion', true)
            ->assertJsonPath('data.discount_percentage', 20);

        $variantId = $created->json('data.id');
        $variantPath = $path.'/'.$variantId;

        foreach ([
            ['sale_price' => '500000.00'],
            ['sale_price' => '600000.00'],
            ['sale_price' => '0.00'],
            ['price' => '300000.00'],
            ['price' => null],
        ] as $invalidUpdate) {
            $this->withBearerToken($admin['token'])
                ->patchJson($variantPath, $invalidUpdate)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('sale_price');
        }

        $this->withBearerToken($admin['token'])
            ->patchJson($variantPath, ['price' => null, 'sale_price' => null])
            ->assertOk()
            ->assertJsonPath('data.price', null)
            ->assertJsonPath('data.sale_price', null)
            ->assertJsonPath('data.is_promotion', false);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variantId,
            'price' => null,
            'sale_price' => null,
        ]);
    }

    public function test_variant_options_route_is_restricted_to_active_admin(): void
    {
        $this->getJson('/api/admin/variant-options')->assertUnauthorized();
        $customer = $this->createUser('customer', 'active', 'variant-options-customer@example.test');
        $this->withBearerToken($customer['token'])->getJson('/api/admin/variant-options')->assertForbidden();

        $admin = $this->createUser('admin', 'active', 'variant-options-admin@example.test');
        Size::create(['name' => 'M']);
        Color::create(['name' => 'Blue']);
        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/variant-options')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sizes.0.name', 'M')
            ->assertJsonPath('data.colors.0.name', 'Blue');
    }

    public function test_stock_and_price_must_fit_database_columns(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-bounds@example.test');
        $product = $this->createProduct('bounds-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $path = '/api/admin/products/'.$product->slug.'/variants';

        foreach ([
            ['stock' => -1],
            ['stock' => 1.5],
            ['stock' => 4294967296],
            ['price' => -0.01],
            ['price' => 10000000000],
            ['price' => 1.234],
        ] as $invalid) {
            $payload = [
                'sku' => 'BOUNDS-'.count($invalid).'-'.(string) ($invalid['stock'] ?? $invalid['price']),
                'size_id' => $size->id,
                'color_id' => $color->id,
                'stock' => 0,
                ...$invalid,
            ];

            $response = $this->withBearerToken($admin['token'])->postJson($path, $payload);
            $response->assertUnprocessable();
            $response->assertJsonValidationErrors(array_key_first($invalid));
        }

        $this->assertSame(0, ProductVariant::count());
    }

    public function test_database_unique_constraint_errors_return_validation_response_without_partial_changes(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-db-unique@example.test');
        $product = $this->createProduct('db-unique-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $path = '/api/admin/products/'.$product->slug.'/variants';

        DB::unprepared("
            CREATE TRIGGER reject_duplicate_variant_sku
            BEFORE INSERT ON product_variants
            WHEN NEW.sku = 'DB-UNIQUE-SKU'
            BEGIN
                SELECT RAISE(ABORT, 'UNIQUE constraint failed: product_variants.sku');
            END
        ");

        $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'DB-UNIQUE-SKU',
                'size_id' => $size->id,
                'color_id' => $color->id,
                'stock' => 4,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sku');

        $this->assertSame(0, ProductVariant::count());

        DB::unprepared('DROP TRIGGER reject_duplicate_variant_sku');

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'DB-UPDATE-ORIGINAL',
            'stock' => 5,
        ]);
        $otherSize = Size::create(['name' => 'L']);
        $otherColor = Color::create(['name' => 'Black']);

        DB::unprepared("
            CREATE TRIGGER reject_duplicate_variant_combination
            BEFORE UPDATE OF size_id, color_id ON product_variants
            WHEN NEW.size_id = {$otherSize->id} AND NEW.color_id = {$otherColor->id}
            BEGIN
                SELECT RAISE(ABORT, 'UNIQUE constraint failed: product_variants.product_id, product_variants.size_id, product_variants.color_id');
            END
        ");

        $this->withBearerToken($admin['token'])
            ->patchJson($path.'/'.$variant->id, [
                'size_id' => $otherSize->id,
                'color_id' => $otherColor->id,
                'expected_stock' => 5,
                'stock' => 9,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('size_id');

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'DB-UPDATE-ORIGINAL',
            'stock' => 5,
            'size_id' => $size->id,
            'color_id' => $color->id,
        ]);
    }

    public function test_patch_validates_the_final_size_color_combination_when_only_one_field_changes(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-patch@example.test');
        $product = $this->createProduct('patch-shirt');
        $medium = Size::create(['name' => 'M']);
        $large = Size::create(['name' => 'L']);
        $blue = Color::create(['name' => 'Blue']);
        $black = Color::create(['name' => 'Black']);
        $red = Color::create(['name' => 'Red']);
        $first = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $medium->id,
            'color_id' => $blue->id,
            'sku' => 'PATCH-M-BLUE',
        ]);
        $second = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $large->id,
            'color_id' => $blue->id,
            'sku' => 'PATCH-L-BLUE',
        ]);
        $third = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $medium->id,
            'color_id' => $black->id,
            'sku' => 'PATCH-M-BLACK',
        ]);
        $path = '/api/admin/products/'.$product->slug.'/variants/';

        $this->withBearerToken($admin['token'])
            ->patchJson($path.$second->id, ['size_id' => $medium->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('size_id');

        $this->withBearerToken($admin['token'])
            ->patchJson($path.$third->id, ['color_id' => $blue->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('color_id');

        $this->assertDatabaseHas('product_variants', [
            'id' => $second->id,
            'size_id' => $large->id,
            'color_id' => $blue->id,
        ]);
        $this->assertDatabaseHas('product_variants', ['id' => $first->id]);
        $this->assertDatabaseHas('product_variants', [
            'id' => $third->id,
            'color_id' => $black->id,
        ]);

        $this->withBearerToken($admin['token'])
            ->patchJson($path.$third->id, ['color_id' => $red->id])
            ->assertOk()
            ->assertJsonPath('data.color.name', 'Red');
    }

    public function test_put_requires_complete_payload_and_patch_requires_at_least_one_field(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-methods@example.test');
        $product = $this->createProduct('methods-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'METHODS-SKU',
            'price' => 100,
            'stock' => 5,
            'status' => 'active',
        ]);
        $path = '/api/admin/products/'.$product->slug.'/variants/'.$variant->id;

        $this->withBearerToken($admin['token'])
            ->putJson($path, [
                'sku' => 'METHODS-SKU',
                'size_id' => $size->id,
                'color_id' => $color->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price', 'stock', 'status']);

        $this->withBearerToken($admin['token'])
            ->putJson($path, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku', 'size_id', 'color_id', 'price', 'stock', 'status']);

        $this->withBearerToken($admin['token'])
            ->putJson($path, [
                'sku' => 'METHODS-SKU',
                'size_id' => $size->id,
                'color_id' => $color->id,
                'price' => null,
                'expected_stock' => 5,
                'stock' => 5,
                'status' => 'active',
            ])
            ->assertOk()
            ->assertJsonPath('data.price', null);

        $this->withBearerToken($admin['token'])
            ->patchJson($path, ['expected_stock' => 5, 'stock' => 7])
            ->assertOk()
            ->assertJsonPath('data.stock', 7);

        $this->withBearerToken($admin['token'])
            ->patchJson($path, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant');

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'METHODS-SKU',
            'price' => null,
            'stock' => 7,
            'status' => 'active',
        ]);
    }

    public function test_stock_updates_require_the_expected_current_value_and_reject_stale_writes_atomically(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-stale-stock@example.test');
        $product = $this->createProduct('stale-stock-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'STALE-STOCK-SKU',
            'price' => '100.00',
            'stock' => 5,
            'status' => 'active',
        ]);
        $path = '/api/admin/products/'.$product->slug.'/variants/'.$variant->id;

        $this->withBearerToken($admin['token'])
            ->patchJson($path, ['stock' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expected_stock');

        $this->withBearerToken($admin['token'])
            ->patchJson($path, ['stock' => null, 'expected_stock' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('stock');

        $this->withBearerToken($admin['token'])
            ->patchJson($path, ['expected_stock' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expected_stock');

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'STALE-STOCK-SKU',
            'price' => '100.00',
            'stock' => 5,
        ]);

        // Simulate a checkout or another stock writer changing the value after the admin read it.
        $variant->update(['stock' => 4]);

        $this->withBearerToken($admin['token'])
            ->patchJson($path, [
                'sku' => 'STALE-STOCK-SKU-CHANGED',
                'price' => '125.00',
                'expected_stock' => 5,
                'stock' => 5,
            ])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('stock');

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'STALE-STOCK-SKU',
            'price' => '100.00',
            'stock' => 4,
        ]);

        $this->withBearerToken($admin['token'])
            ->patchJson($path, [
                'expected_stock' => 4,
                'stock' => 6,
            ])
            ->assertOk()
            ->assertJsonPath('data.stock', 6);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'STALE-STOCK-SKU',
            'price' => '100.00',
            'stock' => 6,
        ]);
    }

    public function test_inactive_variant_cannot_be_created_until_product_has_an_active_variant(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-inactive-create@example.test');
        $product = $this->createProduct('inactive-create-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $path = '/api/admin/products/'.$product->slug.'/variants';

        $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'INACTIVE-FIRST',
                'size_id' => $size->id,
                'color_id' => $color->id,
                'status' => 'inactive',
            ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertSame(0, ProductVariant::count());

        $active = $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'ACTIVE-FIRST',
                'size_id' => $size->id,
                'color_id' => $color->id,
            ])
            ->assertCreated();

        $otherSize = Size::create(['name' => 'L']);
        $inactiveColor = Color::create(['name' => 'Black']);

        $this->withBearerToken($admin['token'])
            ->postJson($path, [
                'sku' => 'INACTIVE-SECOND',
                'size_id' => $otherSize->id,
                'color_id' => $inactiveColor->id,
                'status' => 'inactive',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('product_variants', [
            'id' => $active->json('data.id'),
            'status' => 'active',
        ]);
    }

    public function test_variant_from_another_product_returns_not_found(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-scope@example.test');
        $product = $this->createProduct('scope-shirt');
        $otherProduct = $this->createProduct('other-scope-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $variant = ProductVariant::create([
            'product_id' => $otherProduct->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'OTHER-PRODUCT',
        ]);
        $path = '/api/admin/products/'.$product->slug.'/variants/'.$variant->id;

        $this->withBearerToken($admin['token'])->getJson($path)->assertNotFound();
        $this->withBearerToken($admin['token'])->patchJson($path, ['stock' => 0])->assertNotFound();
        $this->withBearerToken($admin['token'])->deleteJson($path)->assertNotFound();
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
    }

    public function test_last_active_variant_cannot_be_deleted_but_inactive_variant_can(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-last@example.test');
        $product = $this->createProduct('last-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $lastActive = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'LAST-ACTIVE',
            'status' => 'active',
        ]);
        $basePath = '/api/admin/products/'.$product->slug.'/variants/';

        $this->withBearerToken($admin['token'])
            ->patchJson($basePath.$lastActive->id, ['status' => 'inactive'])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('product_variants', ['id' => $lastActive->id, 'status' => 'active']);

        $this->withBearerToken($admin['token'])
            ->deleteJson($basePath.$lastActive->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('product_variants', ['id' => $lastActive->id, 'status' => 'active']);

        $lastActive->update(['status' => 'inactive']);
        $this->withBearerToken($admin['token'])->deleteJson($basePath.$lastActive->id)->assertOk();
        $this->assertDatabaseMissing('product_variants', ['id' => $lastActive->id]);
    }

    public function test_variant_with_cart_item_cannot_be_deleted_and_cart_data_is_preserved(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-references@example.test');
        $customer = $this->createUser('customer', 'active', 'variant-cart@example.test');
        $product = $this->createProduct('reference-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'REFERENCED-VARIANT',
        ]);
        $cartId = DB::table('carts')->insertGetId([
            'user_id' => $customer['user']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cartItemId = DB::table('cart_items')->insertGetId([
            'cart_id' => $cartId,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $basePath = '/api/admin/products/'.$product->slug.'/variants/'.$variant->id;

        $this->withBearerToken($admin['token'])
            ->deleteJson($basePath)
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $cartItemId]);
    }

    public function test_variant_with_order_item_cannot_be_deleted_and_order_snapshot_is_preserved(): void
    {
        $admin = $this->createUser('admin', 'active', 'variant-order-reference@example.test');
        $customer = $this->createUser('customer', 'active', 'variant-order-customer@example.test');
        $product = $this->createProduct('order-reference-shirt');
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'ORDER-REFERENCED-VARIANT',
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $customer['user']->id,
            'total' => 10,
            'status' => 'pending',
            'address' => '',
            'phone' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orderItemId = DB::table('order_items')->insertGetId([
            'order_id' => $orderId,
            'product_variant_id' => $variant->id,
            'product_name' => 'Reference Shirt',
            'sku' => $variant->sku,
            'size' => 'M',
            'color' => 'Blue',
            'price' => 10,
            'quantity' => 1,
            'subtotal' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $path = '/api/admin/products/'.$product->slug.'/variants/'.$variant->id;

        $this->withBearerToken($admin['token'])
            ->deleteJson($path)
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItemId,
            'product_variant_id' => $variant->id,
            'order_id' => $orderId,
            'product_name' => 'Reference Shirt',
            'sku' => 'ORDER-REFERENCED-VARIANT',
            'size' => 'M',
            'color' => 'Blue',
            'price' => 10,
            'quantity' => 1,
            'subtotal' => 10,
        ]);
    }

    /**
     * @return array{user: User, token: string}
     */
    private function createUser(string $role, string $status, string $email): array
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('secret123'),
        ]);
        $user->forceFill(['role' => $role, 'status' => $status])->save();

        return [
            'user' => $user,
            'token' => $user->createToken('variant-test')->plainTextToken,
        ];
    }

    private function createProduct(string $slug): Product
    {
        $category = Category::create([
            'name' => 'Category '.$slug,
            'slug' => $slug.'-category',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Product '.$slug,
            'slug' => $slug,
            'price' => 100,
        ]);
    }

    private function withBearerToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
