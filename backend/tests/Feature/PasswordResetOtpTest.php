<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.encryption' => 'tls',
            'mail.mailers.smtp.username' => 'sender@example.test',
            'mail.mailers.smtp.password' => bin2hex(random_bytes(16)),
            'mail.from.address' => 'sender@example.test',
        ]);

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

        Schema::create('password_reset_otps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('otp_hash')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('verified_token_hash', 64)->nullable()->unique();
            $table->timestamp('verified_at')->nullable();
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
        Schema::dropIfExists('password_reset_otps');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_send_otp_returns_same_message_for_registered_and_unknown_emails(): void
    {
        Mail::fake();
        $user = $this->createUser();

        $registered = $this->postJson('/api/forgot-password/send-otp', ['email' => $user->email])
            ->assertOk()
            ->assertJsonMissingPath('otp');
        $unknown = $this->postJson('/api/forgot-password/send-otp', ['email' => 'unknown@example.test'])
            ->assertOk()
            ->assertJsonMissingPath('otp');

        $this->assertSame($registered->json('message'), $unknown->json('message'));
        Mail::assertSent(PasswordResetOtpMail::class, fn (PasswordResetOtpMail $mail): bool => $mail->hasTo($user->email));
        $this->assertNotNull(DB::table('password_reset_otps')->where('user_id', $user->id)->value('otp_hash'));
    }

    public function test_send_otp_validates_email_and_limits_repeated_requests(): void
    {
        Mail::fake();
        $user = $this->createUser();

        $this->postJson('/api/forgot-password/send-otp', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/api/forgot-password/send-otp', ['email' => $user->email])->assertOk();
        $this->postJson('/api/forgot-password/send-otp', ['email' => $user->email])
            ->assertTooManyRequests();

        Mail::assertSent(PasswordResetOtpMail::class, 1);
    }

    public function test_send_otp_fails_instead_of_using_a_non_gmail_or_log_mailer(): void
    {
        Mail::fake();
        $user = $this->createUser();
        config(['mail.mailers.smtp.host' => '127.0.0.1']);

        $this->postJson('/api/forgot-password/send-otp', ['email' => $user->email])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Dịch vụ email chưa được cấu hình Gmail SMTP.');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_otps', 0);
    }

    public function test_wrong_expired_and_reused_otp_cannot_be_verified(): void
    {
        Mail::fake();
        $user = $this->createUser();
        $otp = $this->sendOtpAndGetCode($user->email);

        $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $user->email,
            'otp' => $this->wrongOtp($otp),
        ])->assertUnprocessable();

        $this->travel(6)->minutes();
        $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ])->assertUnprocessable();

        $otp = $this->sendOtpAndGetCode($user->email);
        $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ])->assertOk();
        $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ])->assertUnprocessable();
    }

    public function test_send_otp_is_limited_to_five_requests_per_hour(): void
    {
        Mail::fake();
        $user = $this->createUser();

        for ($request = 0; $request < 5; $request++) {
            if ($request > 0) {
                $this->travel(61)->seconds();
            }

            $this->postJson('/api/forgot-password/send-otp', ['email' => $user->email])->assertOk();
        }

        $this->travel(61)->seconds();
        $this->postJson('/api/forgot-password/send-otp', ['email' => $user->email])
            ->assertTooManyRequests();

        Mail::assertSent(PasswordResetOtpMail::class, 5);
    }

    public function test_fifth_incorrect_otp_attempt_invalidates_the_code(): void
    {
        Mail::fake();
        $user = $this->createUser();
        $otp = $this->sendOtpAndGetCode($user->email);
        $wrongOtp = $this->wrongOtp($otp);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/forgot-password/verify-otp', [
                'email' => $user->email,
                'otp' => $wrongOtp,
            ])->assertUnprocessable();
        }

        $this->assertSame(5, DB::table('password_reset_otps')->where('user_id', $user->id)->value('attempts'));
        $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $user->email,
            'otp' => $otp,
        ])->assertUnprocessable();
    }

    public function test_otp_is_bound_to_the_email_that_requested_it(): void
    {
        Mail::fake();
        $user = $this->createUser();
        $otherUser = $this->createUser('other@example.test');
        $otp = $this->sendOtpAndGetCode($user->email);

        $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $otherUser->email,
            'otp' => $otp,
        ])->assertUnprocessable();
        $this->assertDatabaseMissing('password_reset_otps', ['user_id' => $otherUser->id]);
    }

    public function test_password_cannot_be_reset_without_a_verified_token(): void
    {
        $this->postJson('/api/forgot-password/reset', [
            'reset_token' => str_repeat('a', 64),
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_reset_validates_confirmation_and_only_allows_one_use_of_verified_token(): void
    {
        Mail::fake();
        $user = $this->createUser();
        $otp = $this->sendOtpAndGetCode($user->email);
        $token = $this->verifyOtpAndGetToken($user->email, $otp);

        $this->postJson('/api/forgot-password/reset', [
            'reset_token' => $token,
            'password' => 'new-secret-123',
            'password_confirmation' => 'different-secret',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->postJson('/api/forgot-password/reset', [
            'reset_token' => $token,
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertOk();

        $this->postJson('/api/forgot-password/reset', [
            'reset_token' => $token,
            'password' => 'another-secret-123',
            'password_confirmation' => 'another-secret-123',
        ])->assertUnprocessable();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'new-secret-123',
        ])->assertOk();
        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertUnauthorized();
        $this->assertDatabaseCount('password_reset_otps', 0);
    }

    public function test_reset_token_expires_after_verification(): void
    {
        Mail::fake();
        $user = $this->createUser();
        $otp = $this->sendOtpAndGetCode($user->email);
        $token = $this->verifyOtpAndGetToken($user->email, $otp);
        DB::table('password_reset_otps')->where('user_id', $user->id)->update([
            'verified_at' => now()->subMinutes(11),
        ]);

        $this->postJson('/api/forgot-password/reset', [
            'reset_token' => $token,
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertUnprocessable();

        $this->assertTrue(Hash::check('secret123', $user->fresh()->password));
    }

    private function createUser(string $email = 'customer@example.test'): User
    {
        return User::create([
            'name' => 'Customer',
            'email' => $email,
            'password' => Hash::make('secret123'),
        ]);
    }

    private function wrongOtp(string $otp): string
    {
        return str_pad((string) (((int) $otp + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function sendOtpAndGetCode(string $email): string
    {
        Mail::fake();
        $this->postJson('/api/forgot-password/send-otp', ['email' => $email])->assertOk();

        $otp = null;
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$otp, $email): bool {
            if ($mail->hasTo($email)) {
                $otp = $mail->otp;

                return true;
            }

            return false;
        });

        $this->assertIsString($otp);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);

        return $otp;
    }

    private function verifyOtpAndGetToken(string $email, string $otp): string
    {
        $response = $this->postJson('/api/forgot-password/verify-otp', [
            'email' => $email,
            'otp' => $otp,
        ])->assertOk();
        $token = $response->json('reset_token');

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));

        return $token;
    }
}
