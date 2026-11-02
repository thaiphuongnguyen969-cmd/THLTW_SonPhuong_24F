<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerApiTest extends TestCase
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
            $table->index(['status', 'sort_order']);
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['banners', 'personal_access_tokens', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_admin_can_create_show_update_and_soft_delete_a_banner(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin@example.test', 'admin');
        $token = $admin->createToken('admin')->plainTextToken;

        $this->withToken($token)->withHeader('Accept', 'application/json')->post('/api/admin/banners', [
            'title' => 'Homepage feature',
            'description' => 'A new collection.',
            'link_url' => '/products',
            'button_text' => 'Shop now',
            'status' => 'active',
            'sort_order' => 3,
        ])->assertUnprocessable()->assertJsonValidationErrors(['image']);

        $created = $this->withToken($token)->withHeader('Accept', 'application/json')->post('/api/admin/banners', [
            'title' => 'Homepage feature',
            'description' => 'A new collection.',
            'link_url' => '/products',
            'button_text' => 'Shop now',
            'status' => 'active',
            'sort_order' => 3,
            'image' => $this->pngUpload(),
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Homepage feature')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.sort_order', 3);

        $bannerId = $created->json('data.id');
        $oldImage = Banner::findOrFail($bannerId)->image;
        Storage::disk('public')->assertExists($oldImage);
        $this->assertStringContainsString('/storage/banners/', $created->json('data.image'));

        $this->withToken($token)->getJson('/api/admin/banners/'.$bannerId)
            ->assertOk()->assertJsonPath('data.id', $bannerId);

        $this->withToken($token)->patchJson('/api/admin/banners/'.$bannerId, [
            'title' => 'Updated feature',
            'description' => 'Updated description.',
            'link_url' => 'https://example.test/collection',
            'button_text' => 'Discover',
            'status' => 'inactive',
            'sort_order' => 5,
            'image' => null,
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated feature')
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.sort_order', 5);
        $this->assertSame($oldImage, Banner::findOrFail($bannerId)->image);
        Storage::disk('public')->assertExists($oldImage);

        $this->withToken($token)->withHeader('Accept', 'application/json')->post('/api/admin/banners/'.$bannerId, [
            '_method' => 'PATCH',
            'title' => 'Replaced image',
            'status' => 'active',
            'image' => $this->pngUpload('replacement.png'),
        ])->assertOk()->assertJsonPath('data.title', 'Replaced image');

        $newImage = Banner::findOrFail($bannerId)->image;
        $this->assertNotSame($oldImage, $newImage);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($newImage);

        $this->withToken($token)->deleteJson('/api/admin/banners/'.$bannerId)->assertOk();
        $this->assertSoftDeleted('banners', ['id' => $bannerId]);
        Storage::disk('public')->assertExists($newImage);
        $this->getJson('/api/banners')->assertJsonCount(0, 'data');
    }

    public function test_customers_cannot_manage_banners(): void
    {
        $customer = $this->createUser('customer@example.test', 'customer');
        $token = $customer->createToken('customer')->plainTextToken;
        $banner = $this->createBanner('Protected banner');

        $this->withToken($token)->getJson('/api/admin/banners')->assertForbidden();
        $this->withToken($token)->post('/api/admin/banners', [
            'title' => 'Unauthorized',
            'status' => 'active',
            'image' => $this->pngUpload(),
        ])->assertForbidden();
        $this->withToken($token)->patchJson('/api/admin/banners/'.$banner->id, ['title' => 'Unauthorized'])->assertForbidden();
        $this->withToken($token)->deleteJson('/api/admin/banners/'.$banner->id)->assertForbidden();

        $this->assertDatabaseHas('banners', ['id' => $banner->id, 'title' => 'Protected banner']);
    }

    public function test_public_api_returns_only_active_banners_in_display_order(): void
    {
        $later = $this->createBanner('Later banner', 'active', 20);
        $first = $this->createBanner('First banner', 'active', 1);
        $this->createBanner('Inactive banner', 'inactive', 0);
        $deleted = $this->createBanner('Deleted banner', 'active', 0);
        $deleted->delete();

        $this->getJson('/api/banners')->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $later->id)
            ->assertJsonPath('data.0.title', 'First banner')
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.image_path')
            ->assertJsonMissingPath('data.0.deleted_at');
    }

    public function test_admin_banner_list_search_and_validation(): void
    {
        Storage::fake('public');
        $admin = $this->createUser('admin@example.test', 'admin');
        $token = $admin->createToken('admin')->plainTextToken;
        $banner = $this->createBanner('Searchable banner', 'active', 1);
        $this->createBanner('Hidden banner', 'inactive', 2);

        $this->withToken($token)->getJson('/api/admin/banners?search=Searchable&status=active')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $banner->id);

        $this->withToken($token)->withHeader('Accept', 'application/json')->post('/api/admin/banners', [
            'title' => '',
            'link_url' => 'http://insecure.example.test',
            'status' => 'unknown',
            'sort_order' => -1,
            'image' => UploadedFile::fake()->create('malware.svg', 10, 'image/svg+xml'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['title', 'link_url', 'status', 'sort_order', 'image']);
    }

    private function createUser(string $email, string $role): User
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('secret123'),
        ]);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    private function createBanner(string $title, string $status = 'active', int $sortOrder = 0): Banner
    {
        return Banner::create([
            'title' => $title,
            'image' => 'banners/'.$title.'.jpg',
            'status' => $status,
            'sort_order' => $sortOrder,
        ]);
    }

    private function pngUpload(string $name = 'banner.png'): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l6sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
