<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MenuApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name', 100); $table->string('email', 100)->unique();
            $table->string('password'); $table->string('role', 20)->default('customer');
            $table->string('status', 20)->default('active'); $table->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id(); $table->morphs('tokenable'); $table->text('name'); $table->string('token', 64)->unique();
            $table->text('abilities')->nullable(); $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index(); $table->timestamps();
        });
        Schema::create('menus', function (Blueprint $table): void {
            $table->id(); $table->string('name', 100); $table->string('link_type', 20)->default('internal');
            $table->string('url', 2048); $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 20)->default('active'); $table->timestamps(); $table->softDeletes();
            $table->index(['status', 'sort_order']);
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('menus'); Schema::dropIfExists('personal_access_tokens'); Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();
        parent::tearDown();
    }

    public function test_admin_routes_require_active_admin_and_public_endpoint_is_open(): void
    {
        $this->getJson('/api/admin/menus')->assertUnauthorized();
        $customer = $this->makeUser('customer@example.test', 'customer');
        $this->withToken($customer->createToken('customer')->plainTextToken)->getJson('/api/admin/menus')->assertForbidden();
        $inactive = $this->makeUser('inactive@example.test', 'admin', 'inactive');
        $this->withToken($inactive->createToken('inactive')->plainTextToken)->postJson('/api/admin/menus', [])->assertForbidden();
        $this->getJson('/api/menus')->assertOk()->assertJsonPath('success', true);
    }

    public function test_admin_can_create_search_paginate_show_update_toggle_and_soft_delete_menus(): void
    {
        $admin = $this->makeUser('admin@example.test', 'admin');
        $token = $admin->createToken('admin')->plainTextToken;
        $this->withToken($token)->postJson('/api/admin/menus', [
            'name' => 'Catalog', 'link_type' => 'internal', 'url' => '/catalog', 'sort_order' => 10, 'status' => 'active',
        ])->assertCreated()->assertJsonPath('data.name', 'Catalog')->assertJsonMissingPath('data.deleted_at');
        $first = Menu::query()->where('name', 'Catalog')->firstOrFail();
        Menu::create(['name' => 'Home', 'url' => '/', 'sort_order' => 1]);
        Menu::create(['name' => 'Hidden Catalog', 'url' => '/hidden-catalog', 'status' => 'inactive', 'sort_order' => 0]);

        $this->withToken($token)->getJson('/api/admin/menus?search=Catalog&per_page=1')
            ->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('meta.per_page', 1);
        $this->withToken($token)->getJson('/api/admin/menus?per_page=1&page=1')
            ->assertOk()->assertJsonPath('data.0.name', 'Hidden Catalog')->assertJsonPath('meta.last_page', 3);
        $this->withToken($token)->getJson('/api/admin/menus/'.$first->id)->assertOk()->assertJsonPath('data.url', '/catalog');
        $this->withToken($token)->patchJson('/api/admin/menus/'.$first->id, ['status' => 'inactive', 'sort_order' => 2])
            ->assertOk()->assertJsonPath('data.status', 'inactive')->assertJsonPath('data.sort_order', 2);
        $this->withToken($token)->patchJson('/api/admin/menus/'.$first->id, ['status' => 'active'])->assertOk();
        $this->withToken($token)->deleteJson('/api/admin/menus/'.$first->id)->assertOk();
        $this->assertSoftDeleted('menus', ['id' => $first->id]);

        $this->getJson('/api/menus')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Home')
            ->assertJsonMissingPath('data.0.status');
    }

    public function test_invalid_menu_urls_fields_and_pagination_are_rejected(): void
    {
        $admin = $this->makeUser('admin@example.test', 'admin');
        $token = $admin->createToken('admin')->plainTextToken;
        foreach ([
            ['name' => 'Bad path', 'link_type' => 'internal', 'url' => '//evil.test', 'status' => 'active'],
            ['name' => 'Bad external', 'link_type' => 'external', 'url' => 'http://example.test', 'status' => 'active'],
            ['name' => 'Bad type', 'link_type' => 'unknown', 'url' => '/', 'status' => 'active'],
            ['name' => 'Bad status', 'link_type' => 'internal', 'url' => '/', 'status' => 'deleted'],
            ['name' => 'Bad order', 'link_type' => 'internal', 'url' => '/', 'sort_order' => -1, 'status' => 'active'],
        ] as $payload) {
            $this->withToken($token)->postJson('/api/admin/menus', $payload)->assertUnprocessable();
        }
        $this->withToken($token)->getJson('/api/admin/menus?per_page=101')->assertUnprocessable();
        $this->assertDatabaseCount('menus', 0);
    }

    private function makeUser(string $email, string $role, string $status = 'active'): User
    {
        $user = User::create(['name' => 'Test', 'email' => $email, 'password' => Hash::make('secret')]);
        $user->forceFill(['role' => $role, 'status' => $status])->save();
        return $user;
    }
}
