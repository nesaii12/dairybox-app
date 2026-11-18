<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class OtpService
{
    public const EXPIRATION_MINUTES = 10;
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Generate and dispatch a new 6-digit OTP code to the user.
     */
    public function generateAndSend(User $user): string
    {
        $otp = sprintf('%06d', random_int(100000, 999999));
        $expiresAt = now()->addMinutes(self::EXPIRATION_MINUTES);
        $resendAt = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        $payload = [
            'code' => $otp,
            'expires_at' => $expiresAt->timestamp,
            'resend_at' => $resendAt->timestamp,
        ];

        Cache::put($this->cacheKey($user), $payload, now()->addMinutes(self::EXPIRATION_MINUTES));

        if (Schema::hasColumn('users', 'verification_otp')) {
            $user->update([
                'verification_otp' => $otp,
                'verification_otp_expires_at' => $expiresAt,
            ]);
        }

        $user->notifyNow(new EmailVerificationOtpNotification($otp));

        if (!empty($user->phone)) {
            try {
                app(SmsOtpService::class)->generateAndSend($user->phone, null, $otp);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('SMS OTP dispatch during account verification failed: ' . $e->getMessage());
            }
        }

        return $otp;
    }

    /**
     * Verify the entered OTP code.
     */
    public function verify(User $user, string $inputCode): bool
    {
        $inputCode = trim(str_replace(' ', '', $inputCode));
        $cached = Cache::get($this->cacheKey($user));

        $valid = false;

        if ($cached && isset($cached['code'])) {
            if ($cached['code'] === $inputCode && now()->timestamp <= $cached['expires_at']) {
                $valid = true;
            }
        } elseif (Schema::hasColumn('users', 'verification_otp') && !empty($user->verification_otp)) {
            if ($user->verification_otp === $inputCode && $user->verification_otp_expires_at && Carbon::parse($user->verification_otp_expires_at)->isFuture()) {
                $valid = true;
            }
        }

        if ($valid) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();

            if (Schema::hasColumn('users', 'verification_otp')) {
                $user->update([
                    'verification_otp' => null,
                    'verification_otp_expires_at' => null,
                ]);
            }

            Cache::forget($this->cacheKey($user));
            return true;
        }

        return false;
    }

    /**
     * Check if the user is allowed to request a resend (cooldown passed).
     */
    public function canResend(User $user): bool
    {
        return $this->getRemainingCooldownSeconds($user) <= 0;
    }

    /**
     * Get remaining cooldown seconds before next resend is permitted.
     */
    public function getRemainingCooldownSeconds(User $user): int
    {
        $cached = Cache::get($this->cacheKey($user));
        if ($cached && isset($cached['resend_at'])) {
            $remaining = $cached['resend_at'] - now()->timestamp;
            return max(0, $remaining);
        }
        return 0;
    }

    /**
     * Cache key for user OTP.
     */
    protected function cacheKey(User $user): string
    {
        return 'email_otp_' . $user->id;
    }

    /**
     * Normalize an email address for pre-registration OTP caching.
     */
    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Generate and send a 6-digit verification code to an email address before registration.
     *
     * @return array{otp: string, cooldown: int}
     */
    public function generateAndSendForRegistrationEmail(string $email): array
    {
        $normalized = $this->normalizeEmail($email);
        $otp = sprintf('%06d', random_int(100000, 999999));
        $expiresAt = now()->addMinutes(self::EXPIRATION_MINUTES);
        $resendAt = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        Cache::put($this->registrationOtpCacheKey($normalized), [
            'code' => $otp,
            'expires_at' => $expiresAt->timestamp,
            'resend_at' => $resendAt->timestamp,
        ], $expiresAt);

        \Illuminate\Support\Facades\Notification::route('mail', $normalized)
            ->notifyNow(new EmailVerificationOtpNotification($otp));

        return [
            'otp' => $otp,
            'cooldown' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    /**
     * Verify the 6-digit OTP code for a pre-registration email address.
     */
    public function verifyRegistrationEmail(string $email, string $inputCode): bool
    {
        $normalized = $this->normalizeEmail($email);
        $inputCode = trim(preg_replace('/\s+/', '', $inputCode));

        $cached = Cache::get($this->registrationOtpCacheKey($normalized));
        if (!$cached || !isset($cached['code'], $cached['expires_at'])) {
            return false;
        }

        if ($cached['code'] === $inputCode && now()->timestamp <= $cached['expires_at']) {
            Cache::forget($this->registrationOtpCacheKey($normalized));
            Cache::put($this->registrationVerifiedCacheKey($normalized), true, now()->addMinutes(30));
            return true;
        }

        return false;
    }

    /**
     * Check if a pre-registration email address has been verified.
     */
    public function isRegistrationEmailVerified(string $email): bool
    {
        $normalized = $this->normalizeEmail($email);
        return (bool) Cache::get($this->registrationVerifiedCacheKey($normalized), false);
    }

    /**
     * Clear the verified flag for a pre-registration email after account creation.
     */
    public function clearRegistrationEmailVerified(string $email): void
    {
        $normalized = $this->normalizeEmail($email);
        Cache::forget($this->registrationVerifiedCacheKey($normalized));
    }

    /**
     * Get remaining cooldown seconds before another verification email can be sent.
     */
    public function getRegistrationEmailCooldownSeconds(string $email): int
    {
        $normalized = $this->normalizeEmail($email);
        $cached = Cache::get($this->registrationOtpCacheKey($normalized));

        if ($cached && isset($cached['resend_at'])) {
            return max(0, $cached['resend_at'] - now()->timestamp);
        }

        return 0;
    }

    protected function registrationOtpCacheKey(string $normalizedEmail): string
    {
        return 'reg_email_otp_' . sha1($normalizedEmail);
    }

    protected function registrationVerifiedCacheKey(string $normalizedEmail): string
    {
        return 'reg_email_verified_' . sha1($normalizedEmail);
    }
}
