<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ForgotPasswordController extends Controller
{
    private const GENERIC_SEND_MESSAGE = 'Nếu email này đã đăng ký, mã xác minh sẽ được gửi đến hộp thư của bạn.';

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'],
        ]);

        if (! $this->hasGmailSmtpConfiguration()) {
            return response()->json([
                'message' => 'Dịch vụ email chưa được cấu hình Gmail SMTP.',
            ], 503);
        }

        $email = mb_strtolower(trim($validated['email']));
        $emailKey = hash('sha256', $email);
        $limitKey = "forgot-password-send:{$emailKey}";
        $cooldownKey = "forgot-password-cooldown:{$emailKey}";

        if (RateLimiter::tooManyAttempts($limitKey, 5) || RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return response()->json([
                'message' => 'Vui lòng chờ trước khi yêu cầu mã mới.',
            ], 429);
        }

        RateLimiter::hit($limitKey, 3600);
        RateLimiter::hit($cooldownKey, 60);

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return response()->json(['message' => self::GENERIC_SEND_MESSAGE]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = Hash::make($otp);

        DB::table('password_reset_otps')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'otp_hash' => $otpHash,
                'expires_at' => now()->addMinutes(5),
                'attempts' => 0,
                'verified_token_hash' => null,
                'verified_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        try {
            Mail::mailer('smtp')->to($user->email)->send(new PasswordResetOtpMail($otp));
        } catch (TransportExceptionInterface $exception) {
            DB::table('password_reset_otps')
                ->where('user_id', $user->id)
                ->where('otp_hash', $otpHash)
                ->delete();

            Log::error('Password reset OTP email could not be delivered.', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'Không thể gửi email lúc này. Vui lòng thử lại sau.',
            ], 503);
        }

        return response()->json(['message' => self::GENERIC_SEND_MESSAGE]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'],
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);
        $email = mb_strtolower(trim($validated['email']));

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return $this->invalidOtpResponse();
        }

        $resetToken = DB::transaction(function () use ($user, $validated): ?string {
            $challenge = DB::table('password_reset_otps')
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $challenge || ! $challenge->otp_hash || ! $challenge->expires_at) {
                return null;
            }

            if (! Carbon::parse($challenge->expires_at)->isFuture()) {
                DB::table('password_reset_otps')->where('user_id', $user->id)->update([
                    'otp_hash' => null,
                    'expires_at' => null,
                    'updated_at' => now(),
                ]);

                return null;
            }

            if ($challenge->attempts >= 5) {
                return null;
            }

            if (! Hash::check($validated['otp'], $challenge->otp_hash)) {
                $attempts = $challenge->attempts + 1;
                DB::table('password_reset_otps')->where('user_id', $user->id)->update([
                    'attempts' => $attempts,
                    ...($attempts >= 5 ? ['otp_hash' => null, 'expires_at' => null] : []),
                    'updated_at' => now(),
                ]);

                return null;
            }

            $token = bin2hex(random_bytes(32));
            DB::table('password_reset_otps')->where('user_id', $user->id)->update([
                'otp_hash' => null,
                'expires_at' => null,
                'verified_token_hash' => hash('sha256', $token),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);

            return $token;
        });

        if (! $resetToken) {
            return $this->invalidOtpResponse();
        }

        return response()->json([
            'message' => 'Xác minh OTP thành công.',
            'reset_token' => $resetToken,
            'expires_in' => 600,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reset_token' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);
        $tokenHash = hash('sha256', $validated['reset_token']);

        $updated = DB::transaction(function () use ($tokenHash, $validated): bool {
            $challenge = DB::table('password_reset_otps')
                ->where('verified_token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (
                ! $challenge
                || ! $challenge->verified_at
                || ! Carbon::parse($challenge->verified_at)->addMinutes(10)->isFuture()
            ) {
                return false;
            }

            $user = User::query()->find($challenge->user_id);
            if (! $user) {
                return false;
            }

            $user->forceFill(['password' => Hash::make($validated['password'])])->save();
            DB::table('password_reset_otps')->where('user_id', $user->id)->delete();

            return true;
        });

        if (! $updated) {
            return response()->json([
                'message' => 'Phiên đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng xác minh OTP lại.',
            ], 422);
        }

        return response()->json(['message' => 'Đổi mật khẩu thành công.']);
    }

    private function invalidOtpResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.',
        ], 422);
    }

    private function hasGmailSmtpConfiguration(): bool
    {
        return config('mail.mailers.smtp.host') === 'smtp.gmail.com'
            && (int) config('mail.mailers.smtp.port') === 587
            && config('mail.mailers.smtp.encryption') === 'tls'
            && filled(config('mail.mailers.smtp.username'))
            && filled(config('mail.mailers.smtp.password'))
            && filled(config('mail.from.address'));
    }
}
