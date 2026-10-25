<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\Size;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogApiTest extends TestCase
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

        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('image_url');
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
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

        Schema::create('promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 12, 2);
            $table->decimal('min_order_value', 12, 2)->default(0);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('inactive');
            $table->timestamps();
        });

        Schema::create('promotion_products', function (Blueprint $table): void {
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unique(['promotion_id', 'product_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'promotion_products',
            'promotions',
            'product_variants',
            'colors',
            'sizes',
            'product_images',
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

    public function test_public_category_reads_are_paginated_by_slug_and_hide_inactive_children(): void
    {
        $parent = Category::create(['name' => 'Tops', 'slug' => 'tops']);
        Category::create([
            'parent_id' => $parent->id,
            'name' => 'Shirts',
            'slug' => 'shirts',
        ]);
        Category::create([
            'parent_id' => $parent->id,
            'name' => 'Hidden',
            'slug' => 'hidden',
            'status' => 'inactive',
        ]);

        $this->getJson('/api/categories?per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/categories/tops')
            ->assertOk()
            ->assertJsonPath('data.children.0.slug', 'shirts')
            ->assertJsonMissing(['slug' => 'hidden']);

        $this->getJson('/api/categories/hidden')->assertNotFound();
    }

    public function test_admin_category_listing_searches_paginates_and_sorts_newest_first(): void
    {
        $oldest = Category::create(['name' => 'Needle Alpha', 'slug' => 'needle-alpha']);
        $newest = Category::create(['name' => 'Needle Zulu', 'slug' => 'needle-zulu']);
        $oldest->forceFill(['created_at' => now()->subDay()])->saveQuietly();
        $newest->forceFill(['created_at' => now()])->saveQuietly();
        Category::create(['name' => 'Other', 'slug' => 'other']);
        $admin = $this->createAdmin('admin-category-index@example.test');

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/categories?status=all&search=Needle&per_page=1&page=1')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => [['id', 'name', 'slug']], 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
            ->assertJsonPath('data.0.slug', 'needle-zulu')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 2);

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/categories?status=all&search=Needle&per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'needle-alpha')
            ->assertJsonPath('meta.current_page', 2);

        $this->getJson('/api/categories')->assertOk()->assertJsonPath('data.0.slug', 'other');
    }

    public function test_admin_catalog_routes_allow_inactive_reads_and_protect_them_from_customers(): void
    {
        $category = Category::create([
            'name' => 'Inactive Tops',
            'slug' => 'inactive-tops',
            'status' => 'inactive',
        ]);
        $brand = Brand::create([
            'name' => 'Inactive Brand',
            'slug' => 'inactive-brand',
            'status' => 'inactive',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Inactive Shirt',
            'slug' => 'inactive-shirt',
            'price' => 100,
            'quantity' => 4,
            'status' => 'inactive',
        ]);
        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue']);
        ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'INACTIVE-M-BLUE',
            'stock' => 3,
            'status' => 'inactive',
        ]);
        $admin = $this->createAdmin('admin-inactive-catalog@example.test');
        $customer = $this->createCustomer('customer-inactive-catalog@example.test');

        $this->getJson('/api/admin/categories?status=inactive&per_page=1')->assertUnauthorized();
        $this->withBearerToken($customer['token'])
            ->getJson('/api/admin/categories?status=inactive')
            ->assertForbidden();
        $this->withBearerToken($customer['token'])
            ->getJson('/api/admin/brands?status=inactive')
            ->assertForbidden();
        $this->withBearerToken($customer['token'])
            ->getJson('/api/admin/products?status=inactive')
            ->assertForbidden();

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/categories?status=inactive&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'inactive-tops')
            ->assertJsonPath('meta.per_page', 1);
        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/categories/inactive-tops')
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/brands?status=inactive&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'inactive-brand')
            ->assertJsonPath('meta.per_page', 1);
        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/brands/inactive-brand')
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/products?status=inactive&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'inactive-shirt')
            ->assertJsonPath('data.0.quantity', 4)
            ->assertJsonPath('meta.per_page', 1);
        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/products/inactive-shirt')
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.variants.0.sku', 'INACTIVE-M-BLUE')
            ->assertJsonPath('data.variants.0.stock', 3);

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->getJson('/api/categories/inactive-tops')->assertNotFound();
        $this->getJson('/api/brands/inactive-brand')->assertNotFound();
        $this->getJson('/api/products/inactive-shirt')->assertNotFound();
        $this->getJson('/api/categories?status=inactive')->assertForbidden();
        $this->getJson('/api/brands?status=inactive')->assertForbidden();
        $this->getJson('/api/products?status=inactive')->assertForbidden();

        $inactiveAdmin = $this->createAdmin('inactive-admin-catalog@example.test');
        $inactiveAdmin['user']->forceFill(['status' => 'inactive'])->save();
        $this->withBearerToken($inactiveAdmin['token'])
            ->getJson('/api/admin/categories?status=inactive')
            ->assertUnauthorized();
    }

    public function test_category_writes_require_an_active_admin_and_validate_payloads(): void
    {
        $this->postJson('/api/categories', ['name' => 'Guest'])->assertUnauthorized();

        $admin = $this->createAdmin('admin-category@example.test');
        $customer = $this->createCustomer('customer-category@example.test');

        $this->withBearerToken($customer['token'])
            ->postJson('/api/categories', ['name' => 'Forbidden'])
            ->assertForbidden();

        $this->withBearerToken($admin['token'])
            ->postJson('/api/categories', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $created = $this->withBearerToken($admin['token'])
            ->postJson('/api/categories', ['name' => 'Jackets', 'role' => 'admin'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'jackets');

        $slug = $created->json('data.slug');

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/categories/'.$slug, ['parent_id' => $created->json('data.id')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/categories/'.$slug, ['name' => 'Outerwear'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Outerwear');

        $this->withBearerToken($admin['token'])
            ->deleteJson('/api/categories/'.$slug)
            ->assertOk();
    }

    public function test_category_cannot_be_deleted_while_products_or_children_reference_it(): void
    {
        $admin = $this->createAdmin('admin-category-delete@example.test');
        $category = Category::create(['name' => 'Tops', 'slug' => 'tops']);
        Category::create([
            'parent_id' => $category->id,
            'name' => 'Shirts',
            'slug' => 'shirts',
        ]);

        $this->withBearerToken($admin['token'])
            ->deleteJson('/api/categories/tops')
            ->assertConflict();

        $category->children()->delete();
        Product::create([
            'category_id' => $category->id,
            'name' => 'Legacy Shirt',
            'slug' => 'legacy-shirt',
            'price' => 100,
        ])->delete();

        $this->withBearerToken($admin['token'])
            ->deleteJson('/api/categories/tops')
            ->assertConflict();
    }

    public function test_brand_reads_and_writes_use_validation_and_protect_referenced_brands(): void
    {
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $admin = $this->createAdmin('admin-brand@example.test');

        $this->getJson('/api/brands')->assertOk()->assertJsonPath('data.0.slug', 'acme');
        $this->getJson('/api/brands/acme')->assertOk()->assertJsonPath('data.name', 'Acme');
        $this->getJson('/api/brands/missing')->assertNotFound();

        $this->withBearerToken($admin['token'])
            ->postJson('/api/brands', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->withBearerToken($admin['token'])
            ->postJson('/api/brands', ['name' => 'Northwind'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'northwind');

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/brands/acme', ['name' => 'Acme Outfitters'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Acme Outfitters')
            ->assertJsonPath('data.slug', 'acme');

        Product::create([
            'category_id' => Category::create(['name' => 'Tops', 'slug' => 'tops'])->id,
            'brand_id' => $brand->id,
            'name' => 'Acme Shirt',
            'slug' => 'acme-shirt',
            'price' => 100,
        ]);

        $this->withBearerToken($admin['token'])
            ->deleteJson('/api/brands/acme')
            ->assertConflict();
    }

    public function test_admin_brand_listing_searches_paginates_and_sorts_newest_first(): void
    {
        $oldest = Brand::create(['name' => 'Needle Alpha Label', 'slug' => 'needle-alpha-label']);
        $newest = Brand::create(['name' => 'Needle Zulu Label', 'slug' => 'needle-zulu-label']);
        $oldest->forceFill(['created_at' => now()->subDay()])->saveQuietly();
        $newest->forceFill(['created_at' => now()])->saveQuietly();
        Brand::create(['name' => 'Other Label', 'slug' => 'other-label']);
        $admin = $this->createAdmin('admin-brand-index@example.test');

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/brands?status=all&search=Needle&per_page=1&page=1')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => [['id', 'name', 'slug']], 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
            ->assertJsonPath('data.0.slug', 'needle-zulu-label')
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 2);

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/brands?status=all&search=Needle&per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'needle-alpha-label');

        $this->getJson('/api/brands')->assertOk()->assertJsonPath('data.0.slug', 'other-label');
    }

    public function test_product_listing_supports_filters_pagination_and_validated_sorting(): void
    {
        $category = Category::create(['name' => 'Tops', 'slug' => 'tops']);
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Blue Shirt',
            'slug' => 'blue-shirt',
            'price' => 100,
            'quantity' => 4,
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Black Shirt',
            'slug' => 'black-shirt',
            'price' => 200,
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Inactive Shirt',
            'slug' => 'inactive-shirt',
            'price' => 300,
            'status' => 'inactive',
        ]);

        $this->getJson('/api/products?search=shirt&category_id='.$category->id.'&brand_id='.$brand->id.'&min_price=50&max_price=150&sort_by=price&sort_direction=asc&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'blue-shirt')
            ->assertJsonMissingPath('data.0.quantity');

        $this->getJson('/api/products?per_page=101')->assertUnprocessable();
        $this->getJson('/api/products?sort_by=password')->assertUnprocessable();
        $this->getJson('/api/products?min_price=200&max_price=100')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_price');
        $this->getJson('/api/products?status=inactive')->assertForbidden();
    }

    public function test_admin_product_listing_searches_paginates_and_sorts_newest_first(): void
    {
        $category = Category::create(['name' => 'Tops', 'slug' => 'tops']);
        $oldest = Product::create(['category_id' => $category->id, 'name' => 'Needle Tee Old', 'slug' => 'needle-tee-old', 'price' => 100]);
        $newest = Product::create(['category_id' => $category->id, 'name' => 'Needle Tee New', 'slug' => 'needle-tee-new', 'price' => 100]);
        $oldest->forceFill(['created_at' => now()->subDay()])->saveQuietly();
        $newest->forceFill(['created_at' => now()])->saveQuietly();
        Product::create(['category_id' => $category->id, 'name' => 'Other Shirt', 'slug' => 'other-shirt', 'price' => 100]);
        $admin = $this->createAdmin('admin-product-index@example.test');

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/products?status=all&search=Needle&per_page=1&page=1')
            ->assertOk()
            ->assertJsonStructure(['success', 'data' => [['id', 'name', 'slug', 'category']], 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
            ->assertJsonPath('data.0.slug', 'needle-tee-new')
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 2);

        $this->withBearerToken($admin['token'])
            ->getJson('/api/admin/products?status=all&search=Needle&per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'needle-tee-old');
    }

    public function test_product_detail_eager_loads_related_catalog_data_without_exposing_private_inventory(): void
    {
        $category = Category::create(['name' => 'Tops', 'slug' => 'tops']);
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $product = Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Blue Shirt',
            'slug' => 'blue-shirt',
            'price' => 100,
            'quantity' => 7,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'image_url' => 'https://example.test/shirt.jpg',
            'is_primary' => true,
        ]);

        $size = Size::create(['name' => 'M']);
        $color = Color::create(['name' => 'Blue', 'hex_code' => '#0000FF']);
        ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => $size->id,
            'color_id' => $color->id,
            'sku' => 'BLUE-M',
            'price' => 110,
            'stock' => 5,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'size_id' => Size::create(['name' => 'L'])->id,
            'color_id' => Color::create(['name' => 'Black'])->id,
            'sku' => 'BLACK-L',
            'stock' => 8,
            'status' => 'inactive',
        ]);

        $promotion = Promotion::create([
            'code' => 'BLUE10',
            'name' => 'Blue discount',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'status' => 'active',
        ]);
        $product->promotions()->attach($promotion->id);

        $response = $this->getJson('/api/products/blue-shirt')
            ->assertOk()
            ->assertJsonPath('data.category.slug', 'tops')
            ->assertJsonPath('data.brand.slug', 'acme')
            ->assertJsonPath('data.images.0.image_url', 'https://example.test/shirt.jpg')
            ->assertJsonPath('data.variants.0.size.name', 'M')
            ->assertJsonPath('data.variants.0.color.name', 'Blue')
            ->assertJsonPath('data.variants.0.available', true)
            ->assertJsonPath('data.promotions.0.code', 'BLUE10')
            ->assertJsonMissingPath('data.quantity')
            ->assertJsonMissingPath('data.variants.0.sku')
            ->assertJsonMissingPath('data.variants.0.stock')
            ->assertJsonMissingPath('data.variants.0.status')
            ->assertJsonMissing(['sku' => 'BLACK-L']);

        $this->assertCount(1, $response->json('data.variants'));
        $this->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.slug', 'blue-shirt');
        $this->getJson('/api/products/inactive-shirt')->assertNotFound();
    }

    public function test_product_writes_validate_data_require_admin_and_soft_delete(): void
    {
        $this->postJson('/api/products', [])->assertUnauthorized();

        $category = Category::create(['name' => 'Tops', 'slug' => 'tops']);
        $admin = $this->createAdmin('admin-product@example.test');
        $customer = $this->createCustomer('customer-product@example.test');

        $this->withBearerToken($customer['token'])
            ->postJson('/api/products', [])
            ->assertForbidden();

        $this->withBearerToken($admin['token'])
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Invalid Shirt',
                'price' => 100,
                'sale_price' => 100,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sale_price');

        $this->withBearerToken($admin['token'])
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Test Shirt',
                'price' => 100,
                'quantity' => 3,
                'deleted_at' => now()->toDateTimeString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('deleted_at');

        $this->assertSame(0, Product::count());

        $this->withBearerToken($admin['token'])
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Invalid Price Shirt',
                'price' => 'not-a-price',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');

        $created = $this->withBearerToken($admin['token'])
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'name' => 'Test Shirt',
                'price' => 100,
                'quantity' => 3,
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'test-shirt');

        $slug = $created->json('data.slug');

        $this->withBearerToken($admin['token'])
            ->patchJson('/api/products/'.$slug, [
                'price' => 120,
                'sale_price' => 100,
            ])
            ->assertOk()
            ->assertJsonPath('data.price', '120.00');

        $this->withBearerToken($admin['token'])
            ->deleteJson('/api/products/'.$slug)
            ->assertOk();

        $this->assertSoftDeleted('products', ['slug' => $slug]);
        $this->getJson('/api/products/'.$slug)->assertNotFound();
    }

    public function test_public_cannot_read_inactive_brand_and_unreferenced_brand_can_be_deleted(): void
    {
        Brand::create([
            'name' => 'Hidden Brand',
            'slug' => 'hidden-brand',
            'status' => 'inactive',
        ]);
        $admin = $this->createAdmin('admin-brand-delete@example.test');
        Brand::create(['name' => 'Unused', 'slug' => 'unused']);

        $this->getJson('/api/brands/hidden-brand')->assertNotFound();
        $this->getJson('/api/brands?status=inactive')->assertForbidden();

        $this->withBearerToken($admin['token'])
            ->deleteJson('/api/brands/unused')
            ->assertOk();
    }

    /**
     * @return array{user: User, token: string}
     */
    private function createAdmin(string $email): array
    {
        $user = $this->createCustomer($email)['user'];
        $user->forceFill(['role' => 'admin'])->save();

        return [
            'user' => $user,
            'token' => $user->createToken('catalog-test')->plainTextToken,
        ];
    }

    /**
     * @return array{user: User, token: string}
     */
    private function createCustomer(string $email): array
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('secret123'),
        ]);

        return [
            'user' => $user,
            'token' => $user->createToken('catalog-test')->plainTextToken,
        ];
    }

    private function withBearerToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
