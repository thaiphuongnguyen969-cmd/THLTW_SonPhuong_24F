<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Menu;
use App\Models\OrderItem;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTrashApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('role', 20)->default('customer');
            $table->string('status', 20)->default('active');
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
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 1000)->nullable();
            $table->longText('content');
            $table->string('thumbnail')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'published_at']);
        });
        Schema::create('menus', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('link_type', 20)->default('internal');
            $table->string('url', 2048);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('banners', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('image', 255);
            $table->string('link_url', 2048)->nullable();
            $table->string('button_text', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
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
        Schema::create('sizes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
        });
        Schema::create('colors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
        });
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->restrictOnDelete();
            $table->foreignId('color_id')->constrained('colors')->restrictOnDelete();
            $table->string('sku')->unique();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('image_url');
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->timestamps();
        });
        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'reviews', 'cart_items', 'order_items', 'product_images', 'product_variants', 'colors', 'sizes',
            'posts', 'menus', 'banners', 'products', 'brands', 'categories', 'personal_access_tokens', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_trash_api_requires_an_active_admin(): void
    {
        $this->getJson('/api/admin/trash')->assertUnauthorized();
        $this->postJson('/api/admin/trash/category/1/restore')->assertUnauthorized();
        $this->deleteJson('/api/admin/trash/category/1')->assertUnauthorized();

        $customer = $this->createUser('customer@example.test', 'customer');
        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->getJson('/api/admin/trash')->assertForbidden();
    }

    public function test_trash_lists_all_types_with_search_type_filter_and_pagination(): void
    {
        $category = Category::create(['name' => 'Trash Cotton', 'slug' => 'trash-cotton']);
        $brand = Brand::create(['name' => 'Trash Label', 'slug' => 'trash-label']);
        $product = $this->createProduct($category, 'Trash Tee');
        $post = Post::create(['title' => 'Trash Article', 'slug' => 'trash-article', 'content' => 'Content']);
        $menu = Menu::create(['name' => 'Trash Menu', 'url' => '/trash-menu']);
        $banner = Banner::create(['title' => 'Trash Banner', 'image' => 'banners/trash.jpg']);
        $category->delete();
        $brand->delete();
        $product->delete();
        $post->delete();
        $menu->delete();
        $banner->delete();

        $admin = $this->createAdmin();
        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson('/api/admin/trash?per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.total', 6)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson('/api/admin/trash?type=brand&search=Label')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'brand')
            ->assertJsonPath('data.0.name', 'Trash Label')
            ->assertJsonPath('meta.total', 1);

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson('/api/admin/trash?type=post&search=Article')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'post')
            ->assertJsonPath('data.0.name', 'Trash Article');

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson('/api/admin/trash?type=menu&search=Menu')
            ->assertOk()->assertJsonPath('data.0.type', 'menu')->assertJsonPath('data.0.name', 'Trash Menu');

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson('/api/admin/trash?type=banner&search=Banner')
            ->assertOk()->assertJsonPath('data.0.type', 'banner')->assertJsonPath('data.0.name', 'Trash Banner');

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->getJson('/api/admin/trash?type=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_standard_delete_soft_deletes_and_admin_can_restore_each_type(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin')->plainTextToken;
        $category = Category::create(['name' => 'Restore Tops', 'slug' => 'restore-tops']);
        $brand = Brand::create(['name' => 'Restore Brand', 'slug' => 'restore-brand']);
        $productCategory = Category::create(['name' => 'Product Tops', 'slug' => 'product-tops']);
        $product = $this->createProduct($productCategory, 'Restore Shirt');
        $post = Post::create(['title' => 'Restore Article', 'slug' => 'restore-article', 'content' => 'Content']);
        $menu = Menu::create(['name' => 'Restore Menu', 'url' => '/restore-menu']);
        $banner = Banner::create(['title' => 'Restore Banner', 'image' => 'banners/restore.jpg']);

        $this->withToken($token)->deleteJson('/api/categories/'.$category->slug)->assertOk();
        $this->withToken($token)->deleteJson('/api/brands/'.$brand->slug)->assertOk();
        $this->withToken($token)->deleteJson('/api/products/'.$product->slug)->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/posts/'.$post->slug)->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/menus/'.$menu->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/banners/'.$banner->id)->assertOk();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertSoftDeleted('brands', ['id' => $brand->id]);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->getJson('/api/products/'.$product->slug)->assertNotFound();

        foreach ([['category', $category], ['brand', $brand], ['product', $product], ['post', $post], ['menu', $menu], ['banner', $banner]] as [$type, $record]) {
            $this->withToken($token)
                ->postJson('/api/admin/trash/'.$type.'/'.$record->id.'/restore')
                ->assertOk()
                ->assertJsonPath('data.type', $type);
        }

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('menus', ['id' => $menu->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('banners', ['id' => $banner->id, 'deleted_at' => null]);
    }

    public function test_permanent_product_delete_cascades_only_its_owned_images_and_variants(): void
    {
        $admin = $this->createAdmin();
        $product = $this->createProduct(Category::create(['name' => 'Purge Tops', 'slug' => 'purge-tops']), 'Purge Shirt');
        $sizeId = DB::table('sizes')->insertGetId(['name' => 'M']);
        $colorId = DB::table('colors')->insertGetId(['name' => 'Blue']);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'size_id' => $sizeId, 'color_id' => $colorId, 'sku' => 'PURGE-SKU',
        ]);
        $image = ProductImage::create(['product_id' => $product->id, 'image_url' => 'purge.jpg']);
        $product->delete();

        $this->withToken($admin->createToken('admin')->plainTextToken)
            ->deleteJson('/api/admin/trash/product/'.$product->id)
            ->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }

    public function test_permanent_delete_succeeds_for_unreferenced_category_brand_and_product(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin')->plainTextToken;
        $category = Category::create(['name' => 'Purge Category', 'slug' => 'purge-category']);
        $brand = Brand::create(['name' => 'Purge Brand', 'slug' => 'purge-brand']);
        $productCategory = Category::create(['name' => 'Purge Product Category', 'slug' => 'purge-product-category']);
        $product = $this->createProduct($productCategory, 'Purge Product');
        $post = Post::create(['title' => 'Purge Article', 'slug' => 'purge-article', 'content' => 'Content']);
        $menu = Menu::create(['name' => 'Purge Menu', 'url' => '/purge-menu']);
        $banner = Banner::create(['title' => 'Purge Banner', 'image' => 'banners/purge-cover.jpg']);
        $category->delete();
        $brand->delete();
        $product->delete();
        $post->delete();
        $menu->delete();
        $banner->delete();
        Storage::fake('public');
        Storage::disk('public')->put('posts/purge-cover.jpg', 'image-bytes');
        Storage::disk('public')->put('banners/purge-cover.jpg', 'image-bytes');
        $post->forceFill(['thumbnail' => 'posts/purge-cover.jpg'])->save();

        $this->withToken($token)->deleteJson('/api/admin/trash/category/'.$category->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/trash/brand/'.$brand->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/trash/product/'.$product->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/trash/post/'.$post->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/trash/menu/'.$menu->id)->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/trash/banner/'.$banner->id)->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing('posts/purge-cover.jpg');
        Storage::disk('public')->assertMissing('banners/purge-cover.jpg');
    }

    public function test_permanent_delete_is_blocked_for_category_brand_and_product_references(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('admin')->plainTextToken;
        $category = Category::create(['name' => 'Protected Category', 'slug' => 'protected-category']);
        $brand = Brand::create(['name' => 'Protected Brand', 'slug' => 'protected-brand']);
        $product = $this->createProduct($category, 'Protected Product', $brand);

        // Seed trashed parents directly so purge checks also prove they protect retained references.
        $category->delete();
        $brand->delete();
        $product->delete();

        $this->withToken($token)->deleteJson('/api/admin/trash/category/'.$category->id)->assertStatus(409);
        $this->withToken($token)->deleteJson('/api/admin/trash/brand/'.$brand->id)->assertStatus(409);

        $sizeId = DB::table('sizes')->insertGetId(['name' => 'L']);
        $colorId = DB::table('colors')->insertGetId(['name' => 'Black']);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'size_id' => $sizeId, 'color_id' => $colorId, 'sku' => 'PROTECTED-SKU',
        ]);
        OrderItem::create(['product_variant_id' => $variant->id, 'product_name' => 'Protected Product']);
        CartItem::create(['product_variant_id' => $variant->id]);
        DB::table('reviews')->insert([
            'product_id' => $product->id, 'rating' => 5, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->withToken($token)->deleteJson('/api/admin/trash/product/'.$product->id)->assertStatus(409);
        $this->assertDatabaseHas('order_items', ['product_variant_id' => $variant->id]);
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id]);
        $this->assertDatabaseHas('reviews', ['product_id' => $product->id]);
    }

    private function createAdmin(): User
    {
        $user = $this->createUser('admin@example.test', 'admin');
        $user->forceFill(['role' => 'admin'])->save();

        return $user;
    }

    private function createUser(string $email, string $role): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('test-password'),
            'role' => $role,
        ]);
    }

    private function createProduct(Category $category, string $name, ?Brand $brand = null): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand?->id,
            'name' => $name,
            'slug' => str($name)->slug(),
            'price' => 100,
            'quantity' => 1,
        ]);
    }
}
