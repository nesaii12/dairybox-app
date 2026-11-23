<?php

namespace App\Services;

use App\Mail\RegistrationOtpMail;
use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class OtpService
{
    public const EXPIRATION_MINUTES = 10;
    public const RESEND_COOLDOWN_SECONDS = 60;

    public const REGISTRATION_EXPIRATION_MINUTES = 5;
    public const REGISTRATION_MAX_ATTEMPTS = 5;
    public const REGISTRATION_COOLDOWN_SECONDS = 60;

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

    /**
     * Mask an email address for UI display (e.g., "john.doe@gmail.com" -> "j***@gmail.com").
     */
    public function maskEmail(string $email): string
    {
        $normalized = $this->normalizeEmail($email);
        if (!str_contains($normalized, '@')) {
            return $normalized;
        }

        [$local, $domain] = explode('@', $normalized, 2);
        $firstChar = mb_substr($local, 0, 1);

        return "{$firstChar}***@{$domain}";
    }

    /**
     * Generate a 6-digit OTP, store its hashed code + pending customer payload
     * inside an encrypted cache entry (5-minute TTL, max 5 attempts), and send it
     * via SMS to the customer's phone number (with email fallback if no SMS gateway
     * API key is configured in .env yet).
     *
     * @param  array{name: string, email: string, phone: string, password_hash: string}  $registrationData
     * @return array{email: string, masked_email: string, phone: string, masked_phone: string, delivery_channel: string, sms_provider: string, cooldown: int, expires_in: int}
     */
    public function storeAndSendPendingRegistrationOtp(array $registrationData): array
    {
        $normalized = $this->normalizeEmail($registrationData['email']);
        $smsService = app(SmsOtpService::class);
        $normalizedPhone = $smsService->normalizePhone((string) $registrationData['phone']);
        $otp = sprintf('%06d', random_int(100000, 999999));
        $expiresAt = now()->addMinutes(self::REGISTRATION_EXPIRATION_MINUTES);
        $resendAt = now()->addSeconds(self::REGISTRATION_COOLDOWN_SECONDS);

        $payload = [
            'email' => $normalized,
            'phone' => $normalizedPhone,
            'otp_code' => Hash::make($otp),
            'expires_at' => $expiresAt->timestamp,
            'resend_at' => $resendAt->timestamp,
            'attempts' => 0,
            'verified_at' => null,
            'pending_user' => [
                'name' => $registrationData['name'],
                'email' => $normalized,
                'phone' => $normalizedPhone,
                'password_hash' => $registrationData['password_hash'],
            ],
        ];

        // Dispatch OTP to the customer's phone number via SMS first
        $smsResult = $smsService->sendRegistrationOtpSms(
            phone: $normalizedPhone,
            otp: $otp,
            expiresMinutes: self::REGISTRATION_EXPIRATION_MINUTES,
        );

        $deliveryChannel = 'sms';
        if (!$smsResult['is_live_sms']) {
            // No live SMS API key configured in .env yet: send email copy so verification still works seamlessly
            try {
                Mail::to($normalized)->send(new RegistrationOtpMail(
                    otp: $otp,
                    customerName: $registrationData['name'],
                    expiresMinutes: self::REGISTRATION_EXPIRATION_MINUTES,
                ));
                $deliveryChannel = 'sms_fallback_email';
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Registration OTP email fallback failed: ' . $e->getMessage());
                $deliveryChannel = 'sms_log';
            }
        }

        Cache::put(
            $this->pendingRegistrationOtpCacheKey($normalized),
            Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            $expiresAt
        );

        Cache::put(
            $this->pendingRegistrationCooldownCacheKey($normalized),
            $resendAt->timestamp,
            $resendAt
        );

        return [
            'email' => $normalized,
            'masked_email' => $this->maskEmail($normalized),
            'phone' => $normalizedPhone,
            'masked_phone' => $smsService->maskPhone($normalizedPhone),
            'delivery_channel' => $deliveryChannel,
            'sms_provider' => $smsResult['sent_via'],
            'cooldown' => self::REGISTRATION_COOLDOWN_SECONDS,
            'expires_in' => self::REGISTRATION_EXPIRATION_MINUTES * 60,
        ];
    }

    /**
     * Retrieve and decrypt the pending registration OTP record for an email.
     */
    public function getPendingRegistrationRecord(string $email): ?array
    {
        $normalized = $this->normalizeEmail($email);
        $encrypted = Cache::get($this->pendingRegistrationOtpCacheKey($normalized));

        if (!is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            $decoded = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (DecryptException|\JsonException) {
            Cache::forget($this->pendingRegistrationOtpCacheKey($normalized));
            return null;
        }
    }

    /**
     * Get remaining resend cooldown seconds for pending registration OTP.
     */
    public function getPendingRegistrationCooldownSeconds(string $email): int
    {
        $normalized = $this->normalizeEmail($email);
        $resendAt = Cache::get($this->pendingRegistrationCooldownCacheKey($normalized));

        if (is_numeric($resendAt)) {
            return max(0, (int) $resendAt - now()->timestamp);
        }

        $record = $this->getPendingRegistrationRecord($normalized);
        if ($record && isset($record['resend_at'])) {
            return max(0, (int) $record['resend_at'] - now()->timestamp);
        }

        return 0;
    }

    /**
     * Verify a submitted 6-digit OTP against the encrypted pending registration record.
     * Enforces 5-minute expiration and max 5 verification attempts.
     *
     * @return array{valid: bool, reason?: string, message?: string, attempts_remaining?: int, pending_user?: array, verified_at?: string}
     */
    public function verifyPendingRegistrationOtp(string $email, string $inputOtp): array
    {
        $normalized = $this->normalizeEmail($email);
        $cleanOtp = preg_replace('/\D+/', '', trim($inputOtp));
        $cacheKey = $this->pendingRegistrationOtpCacheKey($normalized);

        $record = $this->getPendingRegistrationRecord($normalized);

        if (!$record || !isset($record['otp_code'], $record['expires_at'], $record['pending_user'])) {
            return [
                'valid' => false,
                'reason' => 'expired',
                'message' => 'Invalid or expired OTP code. Please try again.',
            ];
        }

        if (now()->timestamp > (int) $record['expires_at']) {
            Cache::forget($cacheKey);
            return [
                'valid' => false,
                'reason' => 'expired',
                'message' => 'Invalid or expired OTP code. Please try again.',
            ];
        }

        $attempts = (int) ($record['attempts'] ?? 0);
        if ($attempts >= self::REGISTRATION_MAX_ATTEMPTS) {
            Cache::forget($cacheKey);
            return [
                'valid' => false,
                'reason' => 'max_attempts',
                'attempts_remaining' => 0,
                'message' => 'Too many failed attempts. Please click "Resend Code" to request a new OTP.',
            ];
        }

        if (strlen($cleanOtp) !== 6 || !Hash::check($cleanOtp, (string) $record['otp_code'])) {
            $attempts++;
            $remaining = max(0, self::REGISTRATION_MAX_ATTEMPTS - $attempts);

            if ($attempts >= self::REGISTRATION_MAX_ATTEMPTS) {
                Cache::forget($cacheKey);
                return [
                    'valid' => false,
                    'reason' => 'max_attempts',
                    'attempts_remaining' => 0,
                    'message' => 'Invalid or expired OTP code. Maximum attempts reached—please request a new code.',
                ];
            }

            $record['attempts'] = $attempts;
            $remainingTtlSeconds = max(1, (int) $record['expires_at'] - now()->timestamp);
            Cache::put(
                $cacheKey,
                Crypt::encryptString(json_encode($record, JSON_THROW_ON_ERROR)),
                now()->addSeconds($remainingTtlSeconds)
            );

            return [
                'valid' => false,
                'reason' => 'invalid',
                'attempts_remaining' => $remaining,
                'message' => 'Invalid or expired OTP code. Please try again.',
            ];
        }

        $verifiedAt = now()->toIso8601String();
        $pendingUser = $record['pending_user'];

        // Delete used OTP & cooldown records immediately upon successful verification
        Cache::forget($cacheKey);
        Cache::forget($this->pendingRegistrationCooldownCacheKey($normalized));

        return [
            'valid' => true,
            'pending_user' => $pendingUser,
            'verified_at' => $verifiedAt,
        ];
    }

    protected function pendingRegistrationOtpCacheKey(string $normalizedEmail): string
    {
        return 'reg_pending_otp_' . sha1($normalizedEmail);
    }

    protected function pendingRegistrationCooldownCacheKey(string $normalizedEmail): string
    {
        return 'reg_pending_otp_cooldown_' . sha1($normalizedEmail);
    }
}
