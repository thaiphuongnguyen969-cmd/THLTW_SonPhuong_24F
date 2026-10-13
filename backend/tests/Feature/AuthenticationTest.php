<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthenticationTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_registration_creates_customer_and_never_assigns_admin_from_request(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Customer',
            'email' => 'customer@example.test',
            'password' => 'secret123',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'customer@example.test')
            ->assertJsonPath('user.role', 'customer')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token')
            ->assertJsonMissingPath('user.status');

        $user = User::where('email', 'customer@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertSame('customer', $user->role);
        $this->assertSame('active', $user->status);
    }

    public function test_registration_validates_required_and_unique_fields(): void
    {
        User::create([
            'name' => 'Existing Customer',
            'email' => 'existing@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson('/api/register', [
            'name' => 'Duplicate',
            'email' => 'existing@example.test',
            'password' => 'short',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_issues_a_sanctum_token_without_exposing_secrets(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'customer@example.test',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', 'customer')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token')
            ->assertJsonMissingPath('user.access_token');

        $this->assertNotEmpty($response->json('access_token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'auth_token',
        ]);
    }

    public function test_login_rejects_invalid_credentials_and_inactive_users(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'incorrect',
        ])->assertUnauthorized();

        $user->forceFill(['status' => 'inactive'])->save();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rate_limit_allows_five_attempts_and_rejects_the_next_request(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'limited@example.test',
                'password' => 'incorrect',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'limited@example.test',
            'password' => 'incorrect',
        ])->assertTooManyRequests()
            ->assertJsonPath('message', 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau một phút.');
    }

    public function test_login_rate_limit_is_separate_per_account_and_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
                ->postJson('/api/login', [
                    'email' => 'first@example.test',
                    'password' => 'incorrect',
                ])->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->postJson('/api/login', [
                'email' => 'second@example.test',
                'password' => 'incorrect',
            ])->assertUnauthorized();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])
            ->postJson('/api/login', [
                'email' => 'first@example.test',
                'password' => 'incorrect',
            ])->assertUnauthorized();
    }

    public function test_login_rate_limit_normalizes_email_case(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'MixedCase@example.test',
                'password' => 'incorrect',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'MIXEDCASE@EXAMPLE.TEST',
            'password' => 'incorrect',
        ])->assertTooManyRequests();
    }

    public function test_me_requires_authentication_and_returns_only_safe_user_fields(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();

        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => Hash::make('secret123'),
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'customer')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token')
            ->assertJsonMissingPath('user.access_token');
    }

    public function test_inactive_user_cannot_use_an_existing_token_on_authenticated_routes(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => Hash::make('secret123'),
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk();

        $user->forceFill(['status' => 'inactive'])->save();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $this->withToken($token)->postJson('/api/logout')->assertUnauthorized();
        $this->withToken($token)->getJson('/api/users')->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_sanctum_token_expires_after_the_configured_duration(): void
    {
        $this->assertSame(120, config('sanctum.expiration'));

        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'customer@example.test',
            'password' => 'secret123',
        ])->assertOk();

        $token = $loginResponse->json('access_token');

        $this->assertNotEmpty($token);
        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->travel(121)->minutes();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();

        $this->travelBack();
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => Hash::make('secret123'),
        ]);
        $token = $user->createToken('test')->plainTextToken;
        $otherToken = $user->createToken('other')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
        $this->withToken($otherToken)->getJson('/api/me')->assertOk();
    }

    public function test_user_management_requires_an_active_admin_from_the_database(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();

        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $customerToken = $customer->createToken('customer')->plainTextToken;
        $this->withToken($customerToken)->getJson('/api/users')->assertForbidden();

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret123'),
        ]);
        $admin->forceFill(['role' => 'admin'])->save();

        $adminToken = $admin->createToken('admin')->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->withToken($adminToken)->getJson('/api/users')->assertOk();

        $admin->forceFill(['status' => 'inactive'])->save();
        $this->app['auth']->forgetGuards();
        $this->withToken($adminToken)->getJson('/api/users')->assertUnauthorized();
    }

    public function test_admin_can_patch_only_the_name_without_changing_email_or_password(): void
    {
        $target = $this->createTestUser('target@example.test');
        $originalPassword = $target->password;
        $adminToken = $this->createAdminToken();

        $this->withBearerToken($adminToken)
            ->patchJson('/api/users/'.$target->id, ['name' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $target->refresh();
        $this->assertSame('target@example.test', $target->email);
        $this->assertSame($originalPassword, $target->password);
    }

    public function test_admin_can_patch_user_without_changing_their_current_email(): void
    {
        $target = $this->createTestUser('target@example.test');

        $this->withBearerToken($this->createAdminToken())
            ->patchJson('/api/users/'.$target->id, [
                'name' => 'Updated Name',
                'email' => 'target@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'target@example.test');
    }

    public function test_admin_can_patch_user_to_an_unused_email(): void
    {
        $target = $this->createTestUser('target@example.test');

        $this->withBearerToken($this->createAdminToken())
            ->patchJson('/api/users/'.$target->id, ['email' => 'new@example.test'])
            ->assertOk()
            ->assertJsonPath('data.email', 'new@example.test');

        $this->assertSame('new@example.test', $target->fresh()->email);
    }

    public function test_admin_cannot_patch_user_to_an_email_owned_by_another_user(): void
    {
        $target = $this->createTestUser('target@example.test');
        $this->createTestUser('other@example.test');

        $this->withBearerToken($this->createAdminToken())
            ->patchJson('/api/users/'.$target->id, ['email' => 'other@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertSame('target@example.test', $target->fresh()->email);
    }

    public function test_admin_can_patch_only_password_and_password_is_hashed(): void
    {
        $target = $this->createTestUser('target@example.test');

        $this->withBearerToken($this->createAdminToken())
            ->patchJson('/api/users/'.$target->id, ['password' => 'newpass123'])
            ->assertOk()
            ->assertJsonMissingPath('data.password');

        $target->refresh();
        $this->assertTrue(Hash::check('newpass123', $target->password));
        $this->assertNotSame('newpass123', $target->password);
    }

    public function test_omitting_password_does_not_change_the_existing_password(): void
    {
        $target = $this->createTestUser('target@example.test');
        $originalPassword = $target->password;

        $this->withBearerToken($this->createAdminToken())
            ->patchJson('/api/users/'.$target->id, ['name' => 'Updated Name'])
            ->assertOk();

        $this->assertSame($originalPassword, $target->fresh()->password);
    }

    public function test_password_shorter_than_six_characters_is_rejected_without_changing_hash(): void
    {
        $target = $this->createTestUser('target@example.test');
        $originalPassword = $target->password;

        $this->withBearerToken($this->createAdminToken())
            ->patchJson('/api/users/'.$target->id, ['password' => '12345'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertSame($originalPassword, $target->fresh()->password);
    }

    public function test_put_requires_name_and_email(): void
    {
        $target = $this->createTestUser('target@example.test');
        $adminToken = $this->createAdminToken();

        $this->withBearerToken($adminToken)
            ->putJson('/api/users/'.$target->id, [
                'email' => 'target@example.test',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->withBearerToken($adminToken)
            ->putJson('/api/users/'.$target->id, [
                'name' => 'Updated Name',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_can_put_user_with_valid_data_without_resending_password(): void
    {
        $target = $this->createTestUser('target@example.test');
        $originalPassword = $target->password;

        $this->withBearerToken($this->createAdminToken())
            ->putJson('/api/users/'.$target->id, [
                'name' => 'Updated Name',
                'email' => 'target@example.test',
                'phone' => '0123456789',
                'address' => 'New address',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'target@example.test');

        $target->refresh();
        $this->assertSame('0123456789', $target->phone);
        $this->assertSame('New address', $target->address);
        $this->assertSame($originalPassword, $target->password);
    }

    public function test_updating_a_nonexistent_user_returns_not_found(): void
    {
        $this->withBearerToken($this->createAdminToken())
            ->patchJson('/api/users/999', ['name' => 'Missing User'])
            ->assertNotFound();
    }

    public function test_user_update_requires_authentication(): void
    {
        $target = $this->createTestUser('target@example.test');

        $this->patchJson('/api/users/'.$target->id, ['name' => 'Updated Name'])
            ->assertUnauthorized();
    }

    public function test_customer_cannot_update_another_user(): void
    {
        $customer = $this->createTestUser('customer@example.test');
        $target = $this->createTestUser('target@example.test');
        $customerToken = $customer->createToken('customer')->plainTextToken;

        $this->withBearerToken($customerToken)
            ->patchJson('/api/users/'.$target->id, ['name' => 'Unauthorized Name'])
            ->assertForbidden();

        $this->assertSame('Test User', $target->fresh()->name);
    }

    public function test_user_update_rejects_role_status_and_system_fields(): void
    {
        $target = $this->createTestUser('target@example.test');

        $this->withBearerToken($this->createAdminToken())
            ->patchJson('/api/users/'.$target->id, [
                'name' => 'Updated Name',
                'role' => 'admin',
                'status' => 'inactive',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role', 'status']);

        $target->refresh();
        $this->assertSame('customer', $target->role);
        $this->assertSame('active', $target->status);
        $this->assertSame('Test User', $target->name);
    }

    private function createAdminToken(): string
    {
        $admin = $this->createTestUser('admin-'.uniqid().'@example.test');
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin->createToken('admin-test')->plainTextToken;
    }

    private function createTestUser(string $email): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('secret123'),
        ]);
    }

    private function withBearerToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
