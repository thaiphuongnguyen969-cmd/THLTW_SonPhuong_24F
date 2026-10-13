<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddressApiTest extends TestCase
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

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
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
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_address_routes_require_authentication_and_active_account(): void
    {
        $owner = $this->createUser('active', 'address-auth-owner@example.test');
        $address = $this->createAddress($owner['user']);
        $addressPath = '/api/addresses/'.$address->id;

        $this->getJson('/api/addresses')->assertUnauthorized();
        $this->postJson('/api/addresses', [])->assertUnauthorized();
        $this->getJson($addressPath)->assertUnauthorized();
        $this->putJson($addressPath, [])->assertUnauthorized();
        $this->patchJson($addressPath, [])->assertUnauthorized();
        $this->deleteJson($addressPath)->assertUnauthorized();
        $this->patchJson($addressPath.'/default')->assertUnauthorized();

        $inactive = $this->createUser('inactive', 'address-auth-inactive@example.test');
        $this->withBearerToken($inactive['token'])->getJson('/api/addresses')->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->postJson('/api/addresses', [])->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->getJson($addressPath)->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->putJson($addressPath, [])->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->patchJson($addressPath, [])->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->deleteJson($addressPath)->assertUnauthorized();
        $this->withBearerToken($inactive['token'])->patchJson($addressPath.'/default')->assertUnauthorized();

        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'user_id' => $owner['user']->id]);
    }

    public function test_user_can_list_only_own_addresses_and_get_empty_list(): void
    {
        $first = $this->createUser('active', 'address-list-first@example.test');
        $second = $this->createUser('active', 'address-list-second@example.test');
        $firstAddress = $this->createAddress($first['user'], ['label' => 'Home', 'is_default' => true]);
        $secondAddress = $this->createAddress($first['user'], ['label' => 'Work']);
        $otherAddress = $this->createAddress($second['user'], ['label' => 'Private']);

        $this->withBearerToken($first['token'])
            ->getJson('/api/addresses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $firstAddress->id)
            ->assertJsonPath('data.1.id', $secondAddress->id)
            ->assertJsonMissing(['id' => $otherAddress->id]);

        $empty = $this->createUser('active', 'address-list-empty@example.test');
        $this->withBearerToken($empty['token'])
            ->getJson('/api/addresses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    public function test_post_creates_first_address_as_default_and_later_addresses_as_non_default(): void
    {
        $user = $this->createUser('active', 'address-create@example.test');
        $payload = $this->validPayload();

        $first = $this->withBearerToken($user['token'])
            ->postJson('/api/addresses', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.is_default', true);

        $second = $this->withBearerToken($user['token'])
            ->postJson('/api/addresses', [...$payload, 'label' => 'Work'])
            ->assertCreated()
            ->assertJsonPath('data.label', 'Work')
            ->assertJsonPath('data.is_default', false);

        $this->assertDatabaseHas('addresses', [
            'id' => $first->json('data.id'),
            'user_id' => $user['user']->id,
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('addresses', [
            'id' => $second->json('data.id'),
            'user_id' => $user['user']->id,
            'is_default' => false,
        ]);
    }

    public function test_post_validates_required_and_max_length_fields_and_accepts_nullable_fields(): void
    {
        $user = $this->createUser('active', 'address-validation@example.test');

        $this->withBearerToken($user['token'])
            ->postJson('/api/addresses', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['label', 'recipient_name', 'address_detail']);

        $this->withBearerToken($user['token'])
            ->postJson('/api/addresses', [
                ...$this->validPayload(),
                'label' => str_repeat('a', 101),
                'recipient_name' => str_repeat('b', 101),
                'phone' => str_repeat('1', 21),
                'province' => str_repeat('p', 101),
                'district' => str_repeat('d', 101),
                'ward' => str_repeat('w', 101),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['label', 'recipient_name', 'phone', 'province', 'district', 'ward']);

        $created = $this->withBearerToken($user['token'])
            ->postJson('/api/addresses', [
                'label' => 'Home',
                'recipient_name' => 'Test Recipient',
                'phone' => null,
                'province' => null,
                'district' => null,
                'ward' => null,
                'address_detail' => '123 Main Street',
            ])
            ->assertCreated()
            ->assertJsonPath('data.phone', null)
            ->assertJsonPath('data.province', null)
            ->assertJsonPath('data.district', null)
            ->assertJsonPath('data.ward', null);

        $this->assertDatabaseHas('addresses', [
            'id' => $created->json('data.id'),
            'phone' => null,
            'province' => null,
            'district' => null,
            'ward' => null,
        ]);
    }

    public function test_post_rejects_ownership_default_system_and_unknown_fields(): void
    {
        $user = $this->createUser('active', 'address-fields@example.test');

        foreach ([
            ['user_id' => 999],
            ['is_default' => false],
            ['id' => 999],
            ['created_at' => now()->toDateTimeString()],
            ['updated_at' => now()->toDateTimeString()],
            ['unknown' => 'value'],
        ] as $extra) {
            $this->withBearerToken($user['token'])
                ->postJson('/api/addresses', [...$this->validPayload(), ...$extra])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(array_key_first($extra));
        }

        $this->assertSame(0, Address::count());
    }

    public function test_detail_is_scoped_to_authenticated_user_for_customers_and_admins(): void
    {
        $owner = $this->createUser('active', 'address-detail-owner@example.test');
        $otherCustomer = $this->createUser('active', 'address-detail-other@example.test');
        $address = $this->createAddress($owner['user']);
        $path = '/api/addresses/'.$address->id;

        $this->withBearerToken($owner['token'])
            ->getJson($path)
            ->assertOk()
            ->assertJsonPath('data.id', $address->id)
            ->assertJsonPath('data.address_detail', $address->address_detail);

        $this->withBearerToken($otherCustomer['token'])->getJson($path)->assertNotFound();
        $this->withBearerToken($owner['token'])->getJson('/api/addresses/99999')->assertNotFound();

        $admin = $this->createUser('active', 'address-detail-admin@example.test', 'admin');
        $this->withBearerToken($admin['token'])->getJson($path)->assertNotFound();
        $ownAdminAddress = $this->createAddress($admin['user']);
        $this->withBearerToken($admin['token'])
            ->getJson('/api/addresses/'.$ownAdminAddress->id)
            ->assertOk()
            ->assertJsonPath('data.id', $ownAdminAddress->id);
    }

    public function test_put_replaces_all_address_fields_and_requires_required_and_nullable_keys(): void
    {
        $user = $this->createUser('active', 'address-put@example.test');
        $address = $this->createAddress($user['user'], ['is_default' => true]);
        $path = '/api/addresses/'.$address->id;

        $this->withBearerToken($user['token'])
            ->putJson($path, [
                'label' => 'Office',
                'recipient_name' => 'New Recipient',
                'phone' => null,
                'province' => null,
                'district' => null,
                'ward' => null,
                'address_detail' => '456 New Road',
            ])
            ->assertOk()
            ->assertJsonPath('data.label', 'Office')
            ->assertJsonPath('data.phone', null)
            ->assertJsonPath('data.address_detail', '456 New Road')
            ->assertJsonPath('data.is_default', true);

        $this->withBearerToken($user['token'])
            ->putJson($path, [
                'label' => 'Office',
                'recipient_name' => 'New Recipient',
                'address_detail' => '456 New Road',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'province', 'district', 'ward']);

        $this->withBearerToken($user['token'])
            ->putJson($path, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['label', 'recipient_name', 'phone', 'province', 'district', 'ward', 'address_detail']);

        foreach ([
            ['user_id' => 999],
            ['is_default' => false],
            ['id' => 999],
            ['created_at' => now()->toDateTimeString()],
            ['updated_at' => now()->toDateTimeString()],
        ] as $protectedField) {
            $this->withBearerToken($user['token'])
                ->putJson($path, [
                    ...$this->validPayload(),
                    ...$protectedField,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(array_key_first($protectedField));
        }

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'label' => 'Office',
            'address_detail' => '456 New Road',
            'is_default' => true,
        ]);
    }

    public function test_patch_updates_only_sent_fields_and_rejects_empty_or_protected_fields(): void
    {
        $user = $this->createUser('active', 'address-patch@example.test');
        $address = $this->createAddress($user['user'], ['is_default' => true]);
        $path = '/api/addresses/'.$address->id;

        $this->withBearerToken($user['token'])
            ->patchJson($path, ['label' => 'Updated Home'])
            ->assertOk()
            ->assertJsonPath('data.label', 'Updated Home')
            ->assertJsonPath('data.recipient_name', $address->recipient_name);

        $this->withBearerToken($user['token'])
            ->patchJson($path, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address');

        foreach ([
            ['user_id' => 999],
            ['is_default' => false],
            ['id' => 999],
            ['created_at' => now()->toDateTimeString()],
            ['updated_at' => now()->toDateTimeString()],
        ] as $extra) {
            $this->withBearerToken($user['token'])
                ->patchJson($path, $extra)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(array_key_first($extra));
        }

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $user['user']->id,
            'label' => 'Updated Home',
            'is_default' => true,
        ]);
    }

    public function test_put_and_patch_cannot_modify_another_users_address(): void
    {
        $owner = $this->createUser('active', 'address-update-owner@example.test');
        $other = $this->createUser('active', 'address-update-other@example.test');
        $address = $this->createAddress($owner['user']);
        $path = '/api/addresses/'.$address->id;
        $payload = [
            'label' => 'Changed',
            'recipient_name' => 'Changed',
            'phone' => null,
            'province' => null,
            'district' => null,
            'ward' => null,
            'address_detail' => 'Changed address',
        ];

        $this->withBearerToken($other['token'])->putJson($path, $payload)->assertNotFound();
        $this->withBearerToken($other['token'])->patchJson($path, ['label' => 'Changed'])->assertNotFound();

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $owner['user']->id,
            'label' => $address->label,
        ]);
    }

    public function test_setting_default_updates_only_the_authenticated_users_addresses(): void
    {
        $owner = $this->createUser('active', 'address-default-owner@example.test');
        $other = $this->createUser('active', 'address-default-other@example.test');
        $oldDefault = $this->createAddress($owner['user'], ['is_default' => true]);
        $newDefault = $this->createAddress($owner['user'], ['is_default' => false]);
        $otherDefault = $this->createAddress($other['user'], ['is_default' => true]);

        $this->withBearerToken($owner['token'])
            ->patchJson('/api/addresses/'.$newDefault->id.'/default')
            ->assertOk()
            ->assertJsonPath('data.id', $newDefault->id)
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('addresses', ['id' => $oldDefault->id, 'is_default' => false]);
        $this->assertDatabaseHas('addresses', ['id' => $newDefault->id, 'is_default' => true]);
        $this->assertDatabaseHas('addresses', ['id' => $otherDefault->id, 'is_default' => true]);
        $this->assertSame(
            1,
            Address::query()->where('user_id', $owner['user']->id)->where('is_default', true)->count()
        );

        $this->withBearerToken($owner['token'])
            ->patchJson('/api/addresses/'.$otherDefault->id.'/default')
            ->assertNotFound();
        $this->withBearerToken($owner['token'])
            ->patchJson('/api/addresses/99999/default')
            ->assertNotFound();
    }

    public function test_deleting_non_default_does_not_change_default(): void
    {
        $user = $this->createUser('active', 'address-delete-nondefault@example.test');
        $default = $this->createAddress($user['user'], ['is_default' => true]);
        $nonDefault = $this->createAddress($user['user'], ['is_default' => false]);

        $this->withBearerToken($user['token'])
            ->deleteJson('/api/addresses/'.$nonDefault->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('addresses', ['id' => $nonDefault->id]);
        $this->assertDatabaseHas('addresses', ['id' => $default->id, 'is_default' => true]);
    }

    public function test_deleting_default_selects_next_address_then_previous_fallback(): void
    {
        $user = $this->createUser('active', 'address-delete-default@example.test');
        $first = $this->createAddress($user['user'], ['is_default' => false]);
        $default = $this->createAddress($user['user'], ['is_default' => true]);
        $next = $this->createAddress($user['user'], ['is_default' => false]);

        $this->withBearerToken($user['token'])
            ->deleteJson('/api/addresses/'.$default->id)
            ->assertOk();

        $this->assertDatabaseMissing('addresses', ['id' => $default->id]);
        $this->assertDatabaseHas('addresses', ['id' => $next->id, 'is_default' => true]);
        $this->assertDatabaseHas('addresses', ['id' => $first->id, 'is_default' => false]);

        $this->withBearerToken($user['token'])
            ->deleteJson('/api/addresses/'.$next->id)
            ->assertOk();
        $this->assertDatabaseHas('addresses', ['id' => $first->id, 'is_default' => true]);
    }

    public function test_deleting_last_address_leaves_no_default_and_other_users_are_unaffected(): void
    {
        $owner = $this->createUser('active', 'address-delete-last@example.test');
        $other = $this->createUser('active', 'address-delete-other@example.test');
        $last = $this->createAddress($owner['user'], ['is_default' => true]);
        $otherAddress = $this->createAddress($other['user'], ['is_default' => true]);

        $this->withBearerToken($owner['token'])->deleteJson('/api/addresses/'.$last->id)->assertOk();
        $this->assertDatabaseMissing('addresses', ['id' => $last->id]);
        $this->assertSame(0, $owner['user']->addresses()->count());
        $this->assertDatabaseHas('addresses', [
            'id' => $otherAddress->id,
            'user_id' => $other['user']->id,
            'is_default' => true,
        ]);
    }

    public function test_delete_hides_other_users_and_missing_address_ids(): void
    {
        $owner = $this->createUser('active', 'address-delete-owner@example.test');
        $other = $this->createUser('active', 'address-delete-caller@example.test');
        $address = $this->createAddress($owner['user']);

        $this->withBearerToken($other['token'])->deleteJson('/api/addresses/'.$address->id)->assertNotFound();
        $this->withBearerToken($owner['token'])->deleteJson('/api/addresses/99999')->assertNotFound();

        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'user_id' => $owner['user']->id]);
    }

    /**
     * @return array{user: User, token: string}
     */
    private function createUser(string $status, string $email, string $role = 'customer'): array
    {
        $user = User::create([
            'name' => 'Address Test User',
            'email' => $email,
            'password' => Hash::make('secret123'),
        ]);
        $user->forceFill(['status' => $status, 'role' => $role])->save();

        return [
            'user' => $user,
            'token' => $user->createToken('address-test')->plainTextToken,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAddress(User $user, array $overrides = []): Address
    {
        return $user->addresses()->create([
            'label' => 'Home',
            'recipient_name' => 'Test Recipient',
            'phone' => '0123456789',
            'province' => 'Ha Noi',
            'district' => 'Ba Dinh',
            'ward' => 'Phuc Xa',
            'address_detail' => '1 Example Street',
            'is_default' => false,
            ...$overrides,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'label' => 'Home',
            'recipient_name' => 'Test Recipient',
            'phone' => '0123456789',
            'province' => 'Ha Noi',
            'district' => 'Ba Dinh',
            'ward' => 'Phuc Xa',
            'address_detail' => '1 Example Street',
        ];
    }

    private function withBearerToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
