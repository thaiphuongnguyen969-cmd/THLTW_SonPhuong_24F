<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartApiTest extends TestCase
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
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
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
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'cart_items',
            'carts',
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

    public function test_all_cart_routes_require_authentication_and_active_account(): void
    {
        $user = $this->createUser('active', 'cart-auth@example.test');
        $other = $this->createUser('active', 'cart-auth-other@example.test');
        $variant = $this->createVariant();
        $item = $this->createCartItem($other['user'], $variant, 1);
        $itemPath = '/api/cart/items/'.$item->id;

        $this->getJson('/api/cart')->assertUnauthorized();
        $this->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertUnauthorized();
        $this->patchJson($itemPath, ['quantity' => 2])->assertUnauthorized();
        $this->deleteJson($itemPath)->assertUnauthorized();
        $this->deleteJson('/api/cart')->assertUnauthorized();

        $inactive = $this->createUser('inactive', 'cart-inactive@example.test');
        $this->withBearerToken($inactive['token'])->getJson('/api/cart')->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->postJson('/api/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->patchJson($itemPath, ['quantity' => 2])->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->deleteJson($itemPath)->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->deleteJson('/api/cart')->assertUnauthorized();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
        $this->assertSame('active', $user['user']->fresh()->status);
    }

    public function test_get_returns_empty_cart_without_creating_cart_and_does_not_expose_other_users_cart(): void
    {
        $owner = $this->createUser('active', 'cart-get-owner@example.test');
        $other = $this->createUser('active', 'cart-get-other@example.test');
        $variant = $this->createVariant();
        $otherItem = $this->createCartItem($other['user'], $variant, 2);

        $this->withBearerToken($owner['token'])
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.total_quantity', 0)
            ->assertJsonPath('data.total', '0.00');

        $this->assertDatabaseMissing('carts', ['user_id' => $owner['user']->id]);

        $this->withBearerToken($owner['token'])
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->withBearerToken($other['token'])
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $otherItem->id)
            ->assertJsonPath('data.items.0.quantity', 2);
    }

    public function test_get_returns_cart_items_and_calculates_effective_prices_and_totals(): void
    {
        $user = $this->createUser('active', 'cart-prices@example.test');
        $variantPrice = $this->createVariant(stock: 9, variantPrice: '12.50', productPrice: '30.00', salePrice: '20.00');
        $salePrice = $this->createVariant(stock: 9, variantPrice: null, productPrice: '30.00', salePrice: '20.00');
        $basePrice = $this->createVariant(stock: 9, variantPrice: null, productPrice: '30.00', salePrice: '30.00');
        $invalidSalePrice = $this->createVariant(stock: 9, variantPrice: null, productPrice: '30.00', salePrice: '-1.00');
        $this->createCartItem($user['user'], $variantPrice, 2);
        $this->createCartItem($user['user'], $salePrice, 3);
        $this->createCartItem($user['user'], $basePrice, 1);
        $this->createCartItem($user['user'], $invalidSalePrice, 1);

        $this->withBearerToken($user['token'])
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.total_quantity', 7)
            ->assertJsonPath('data.total', '145.00')
            ->assertJsonPath('data.items.0.unit_price', '12.50')
            ->assertJsonPath('data.items.0.subtotal', '25.00')
            ->assertJsonPath('data.items.0.product.name', $variantPrice->product->name)
            ->assertJsonPath('data.items.0.variant.size.name', $variantPrice->size->name)
            ->assertJsonPath('data.items.0.variant.color.name', $variantPrice->color->name)
            ->assertJsonMissingPath('data.items.0.variant.sku')
            ->assertJsonMissingPath('data.items.0.variant.stock')
            ->assertJsonMissingPath('data.items.0.variant.status')
            ->assertJsonPath('data.items.1.unit_price', '20.00')
            ->assertJsonPath('data.items.1.subtotal', '60.00')
            ->assertJsonPath('data.items.2.unit_price', '30.00')
            ->assertJsonPath('data.items.2.subtotal', '30.00')
            ->assertJsonPath('data.items.3.unit_price', '30.00');
    }

    public function test_large_cart_totals_remain_exact_beyond_php_integer_multiplication_range(): void
    {
        $user = $this->createUser('active', 'cart-large-total@example.test');
        $variant = $this->createVariant(
            stock: 4294967295,
            productPrice: '9999999999.99'
        );
        $this->createCartItem($user['user'], $variant, 4294967295);

        $this->withBearerToken($user['token'])
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.total_quantity', 4294967295)
            ->assertJsonPath('data.items.0.subtotal', '42949672949957050327.05')
            ->assertJsonPath('data.total', '42949672949957050327.05');
    }

    public function test_post_creates_then_adds_to_existing_variant_item_without_duplicate_and_without_changing_stock(): void
    {
        $user = $this->createUser('active', 'cart-add@example.test');
        $variant = $this->createVariant(stock: 5);
        $path = '/api/cart/items';

        $this->withBearerToken($user['token'])
            ->postJson($path, ['product_variant_id' => $variant->id, 'quantity' => 2])
            ->assertCreated()
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonPath('data.unit_price', '100.00')
            ->assertJsonPath('data.subtotal', '200.00');

        $this->withBearerToken($user['token'])
            ->postJson($path, ['product_variant_id' => $variant->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('data.quantity', 4);

        $this->assertSame(1, DB::table('cart_items')->where('product_variant_id', $variant->id)->count());
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 4]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 5]);
    }

    public function test_post_rejects_invalid_payload_and_fields_outside_the_whitelist(): void
    {
        $user = $this->createUser('active', 'cart-validation@example.test');
        $variant = $this->createVariant();

        foreach ([0, -1, 1.5, 'not-an-integer'] as $quantity) {
            $this->withBearerToken($user['token'])
                ->postJson('/api/cart/items', [
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('quantity');
        }

        $this->withBearerToken($user['token'])
            ->postJson('/api/cart/items', ['product_variant_id' => 99999, 'quantity' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_variant_id');

        $this->withBearerToken($user['token'])
            ->postJson('/api/cart/items', [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'user_id' => 999,
                'cart_id' => 999,
                'price' => 0,
                'stock' => 999,
                'status' => 'inactive',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'cart_id', 'price', 'stock', 'status']);

        $this->assertSame(0, Cart::count());
        $this->assertSame(0, DB::table('cart_items')->count());
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 10]);
    }

    public function test_post_rejects_variant_or_product_that_is_not_sellable(): void
    {
        $user = $this->createUser('active', 'cart-unavailable@example.test');
        $cases = [
            $this->createVariant(status: 'inactive'),
            $this->createVariant(productStatus: 'inactive'),
            $this->createVariant(categoryStatus: 'inactive'),
            $this->createVariant(brandStatus: 'inactive'),
        ];
        $softDeletedProductVariant = $this->createVariant();
        $softDeletedProductVariant->product->delete();
        $cases[] = $softDeletedProductVariant;

        foreach ($cases as $variant) {
            $this->withBearerToken($user['token'])
                ->postJson('/api/cart/items', [
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                ])
                ->assertNotFound();
        }

        $this->assertSame(0, Cart::count());
        $this->assertSame(0, DB::table('cart_items')->count());
    }

    public function test_post_checks_stock_for_new_and_existing_items_and_preserves_data_on_conflict(): void
    {
        $user = $this->createUser('active', 'cart-stock@example.test');
        $zeroStock = $this->createVariant(stock: 0);
        $this->withBearerToken($user['token'])
            ->postJson('/api/cart/items', ['product_variant_id' => $zeroStock->id, 'quantity' => 1])
            ->assertStatus(409);

        $variant = $this->createVariant(stock: 3);
        $this->withBearerToken($user['token'])
            ->postJson('/api/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 4])
            ->assertStatus(409);
        $this->assertSame(0, DB::table('cart_items')->count());

        $item = $this->createCartItem($user['user'], $variant, 2);
        $this->withBearerToken($user['token'])
            ->postJson('/api/cart/items', ['product_variant_id' => $variant->id, 'quantity' => 2])
            ->assertStatus(409);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 2]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 3]);
    }

    public function test_patch_sets_quantity_and_validates_stock_and_sellability(): void
    {
        $user = $this->createUser('active', 'cart-patch@example.test');
        $variant = $this->createVariant(stock: 5);
        $item = $this->createCartItem($user['user'], $variant, 2);
        $path = '/api/cart/items/'.$item->id;

        $this->withBearerToken($user['token'])
            ->patchJson($path, ['quantity' => 4])
            ->assertOk()
            ->assertJsonPath('data.quantity', 4);

        $this->withBearerToken($user['token'])
            ->patchJson($path, ['quantity' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->withBearerToken($user['token'])
            ->patchJson($path, ['quantity' => 6])
            ->assertStatus(409);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 4]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 5]);

        $variant->update(['status' => 'inactive']);
        $this->withBearerToken($user['token'])
            ->patchJson($path, ['quantity' => 3])
            ->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 4]);

        $variant->update(['status' => 'active']);
        $variant->product->update(['status' => 'inactive']);
        $this->withBearerToken($user['token'])
            ->patchJson($path, ['quantity' => 3])
            ->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 4]);
    }

    public function test_patch_and_delete_hide_items_owned_by_another_user_and_missing_ids(): void
    {
        $owner = $this->createUser('active', 'cart-item-owner@example.test');
        $other = $this->createUser('active', 'cart-item-other@example.test');
        $variant = $this->createVariant(stock: 5);
        $item = $this->createCartItem($owner['user'], $variant, 2);

        foreach ([99999, $item->id] as $id) {
            $path = '/api/cart/items/'.$id;
            $this->withBearerToken($other['token'])->patchJson($path, ['quantity' => 3])->assertNotFound();
            $this->withBearerToken($other['token'])->deleteJson($path)->assertNotFound();
        }

        $this->withBearerToken($owner['token'])
            ->patchJson('/api/cart/items/'.$item->id, [
                'quantity' => 3,
                'user_id' => $other['user']->id,
                'cart_id' => $other['user']->cart()->value('id'),
                'price' => 0,
                'stock' => 999,
                'status' => 'inactive',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'cart_id', 'price', 'stock', 'status']);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 2]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock' => 5, 'status' => 'active']);
    }

    public function test_delete_item_and_clear_cart_are_owned_idempotent_and_do_not_affect_other_users(): void
    {
        $first = $this->createUser('active', 'cart-delete-first@example.test');
        $second = $this->createUser('active', 'cart-delete-second@example.test');
        $variant = $this->createVariant(stock: 10);
        $firstItem = $this->createCartItem($first['user'], $variant, 1);
        $secondItem = $this->createCartItem($second['user'], $variant, 2);

        $this->withBearerToken($first['token'])
            ->deleteJson('/api/cart/items/'.$firstItem->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cart_items', ['id' => $firstItem->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $secondItem->id, 'quantity' => 2]);
        $this->withBearerToken($first['token'])->deleteJson('/api/cart/items/'.$firstItem->id)->assertNotFound();

        $this->createCartItem($first['user'], $variant, 3);
        $this->withBearerToken($first['token'])->deleteJson('/api/cart')->assertOk();
        $this->withBearerToken($first['token'])->deleteJson('/api/cart')->assertOk();
        $this->withBearerToken($first['token'])->deleteJson('/api/cart')->assertOk();

        $this->assertSame(0, DB::table('cart_items')
            ->where('cart_id', $first['user']->cart()->value('id'))
            ->count());
        $this->assertDatabaseHas('cart_items', ['id' => $secondItem->id, 'quantity' => 2]);
    }

    /**
     * @return array{user: User, token: string}
     */
    private function createUser(string $status, string $email): array
    {
        $user = User::create([
            'name' => 'Cart Test User',
            'email' => $email,
            'password' => Hash::make('secret123'),
        ]);
        $user->forceFill(['status' => $status])->save();

        return [
            'user' => $user,
            'token' => $user->createToken('cart-test')->plainTextToken,
        ];
    }

    private function createVariant(
        int $stock = 10,
        ?string $variantPrice = null,
        string $productPrice = '100.00',
        ?string $salePrice = null,
        string $status = 'active',
        string $productStatus = 'active',
        string $categoryStatus = 'active',
        ?string $brandStatus = null
    ): ProductVariant {
        static $sequence = 0;
        $sequence++;
        $category = Category::create([
            'name' => 'Cart Category '.$sequence,
            'slug' => 'cart-category-'.$sequence,
            'status' => $categoryStatus,
        ]);
        $brand = null;

        if ($brandStatus !== null) {
            $brand = Brand::create([
                'name' => 'Cart Brand '.$sequence,
                'slug' => 'cart-brand-'.$sequence,
                'status' => $brandStatus,
            ]);
        }

        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand?->id,
            'name' => 'Cart Product '.$sequence,
            'slug' => 'cart-product-'.$sequence,
            'price' => $productPrice,
            'sale_price' => $salePrice,
            'status' => $productStatus,
            'quantity' => 0,
        ]);
        $size = Size::create(['name' => 'Size '.$sequence]);
        $color = Color::create(['name' => 'Color '.$sequence, 'hex_code' => '#123456']);

        return ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'CART-SKU-'.$sequence,
            'price' => $variantPrice,
            'stock' => $stock,
            'status' => $status,
        ]);
    }

    private function createCartItem(User $user, ProductVariant $variant, int $quantity)
    {
        $cart = $user->cart()->firstOrCreate([]);

        return $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }

    private function withBearerToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
