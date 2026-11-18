<?php

namespace App\Services;

use App\Notifications\EmailVerificationOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SmsOtpService
{
    public const EXPIRATION_MINUTES = 10;
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Normalize a Philippine phone number to 09XXXXXXXXX format.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9+]/', '', trim($phone));

        if (str_starts_with($digits, '+639') && strlen($digits) === 13) {
            return '0' . substr($digits, 3);
        }

        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return '0' . substr($digits, 2);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0' . $digits;
        }

        return $digits;
    }

    /**
     * Format phone number for international gateway dispatch (+639XXXXXXXXX).
     */
    public function toInternationalFormat(string $phone): string
    {
        $normalized = $this->normalizePhone($phone);
        if (str_starts_with($normalized, '09') && strlen($normalized) === 11) {
            return '+63' . substr($normalized, 1);
        }
        return $normalized;
    }

    /**
     * Validate if a phone number is a valid Philippine mobile number.
     */
    public function isValidPhilippineMobile(string $phone): bool
    {
        $normalized = $this->normalizePhone($phone);
        return (bool) preg_match('/^09\d{9}$/', $normalized);
    }

    /**
     * Check whether a live SMS gateway API key is configured.
     */
    public function isGatewayConfigured(): bool
    {
        return !empty(config('services.sms.semaphore_api_key'))
            || !empty(config('services.sms.philsms_api_token'))
            || !empty(config('services.sms.iprogsms_api_token'));
    }

    /**
     * Generate a 6-digit OTP and send it via SMS to the given phone number.
     *
     * @return array{otp: string, sent_via: string, cooldown: int}
     */
    public function generateAndSend(string $phone, ?string $fallbackEmail = null, ?string $existingOtp = null): array
    {
        $normalized = $this->normalizePhone($phone);
        $otp = $existingOtp ?: sprintf('%06d', random_int(100000, 999999));
        $expiresAt = now()->addMinutes(self::EXPIRATION_MINUTES);
        $resendAt = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        Cache::put($this->otpCacheKey($normalized), [
            'code' => $otp,
            'expires_at' => $expiresAt->timestamp,
            'resend_at' => $resendAt->timestamp,
        ], $expiresAt);

        $message = "Your DairyBox verification code is {$otp}. Valid for " . self::EXPIRATION_MINUTES . " minutes. Do not share this code with anyone.";
        $sentVia = $this->dispatchSms($normalized, $message, $otp, $fallbackEmail);

        return [
            'otp' => $otp,
            'sent_via' => $sentVia,
            'cooldown' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    /**
     * Verify the 6-digit OTP code for a phone number.
     */
    public function verify(string $phone, string $inputCode, ?string $sessionId = null): bool
    {
        $normalized = $this->normalizePhone($phone);
        $inputCode = trim(preg_replace('/\s+/', '', $inputCode));

        $cached = Cache::get($this->otpCacheKey($normalized));
        if (!$cached || !isset($cached['code'], $cached['expires_at'])) {
            return false;
        }

        if ($cached['code'] === $inputCode && now()->timestamp <= $cached['expires_at']) {
            Cache::forget($this->otpCacheKey($normalized));
            $this->markVerifiedForSession($normalized, $sessionId);
            return true;
        }

        return false;
    }

    /**
     * Mark a phone number as verified (valid for 30 minutes).
     */
    public function markVerifiedForSession(string $phone, ?string $sessionId = null): void
    {
        $normalized = $this->normalizePhone($phone);
        Cache::put($this->verifiedCacheKey($normalized), true, now()->addMinutes(30));
    }

    /**
     * Check if a phone number has been verified.
     */
    public function isVerifiedForSession(string $phone, ?string $sessionId = null): bool
    {
        $normalized = $this->normalizePhone($phone);
        return (bool) Cache::get($this->verifiedCacheKey($normalized), false);
    }

    /**
     * Clear the verification flag after registration completes.
     */
    public function clearVerifiedForSession(string $phone, ?string $sessionId = null): void
    {
        $normalized = $this->normalizePhone($phone);
        Cache::forget($this->verifiedCacheKey($normalized));
    }

    /**
     * Get remaining cooldown seconds before another SMS can be sent to this phone.
     */
    public function getRemainingCooldownSeconds(string $phone): int
    {
        $normalized = $this->normalizePhone($phone);
        $cached = Cache::get($this->otpCacheKey($normalized));

        if ($cached && isset($cached['resend_at'])) {
            return max(0, $cached['resend_at'] - now()->timestamp);
        }

        return 0;
    }

    /**
     * Dispatch the SMS message through Semaphore, PhilSMS, IPROG SMS, or fallback.
     */
    protected function dispatchSms(string $normalizedPhone, string $message, string $otp, ?string $fallbackEmail = null): string
    {
        $semaphoreKey = config('services.sms.semaphore_api_key');
        $philsmsToken = config('services.sms.philsms_api_token');
        $iprogToken   = config('services.sms.iprogsms_api_token');

        if (!empty($semaphoreKey)) {
            $payload = [
                'apikey' => $semaphoreKey,
                'number' => $normalizedPhone,
                'message' => $message,
            ];

            $senderName = config('services.sms.semaphore_sender_name');
            if (!empty($senderName)) {
                $payload['sendername'] = $senderName;
            }

            $response = Http::timeout(15)->asForm()->post('https://api.semaphore.co/api/v4/messages', $payload);

            if ($response->successful()) {
                return 'semaphore';
            }

            Log::error('Semaphore SMS send failed', [
                'phone' => $normalizedPhone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Semaphore SMS error: ' . ($response->json('message') ?? 'Unable to send SMS verification code. Check your SEMAPHORE_API_KEY.'));
        }

        if (!empty($philsmsToken)) {
            $response = Http::timeout(15)
                ->withToken($philsmsToken)
                ->acceptJson()
                ->post('https://app.philsms.com/api/v3/sms/send', [
                    'recipient' => ltrim($this->toInternationalFormat($normalizedPhone), '+'),
                    'sender_id' => config('services.sms.philsms_sender_id', 'PhilSMS'),
                    'type' => 'plain',
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return 'philsms';
            }

            Log::error('PhilSMS send failed', [
                'phone' => $normalizedPhone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('PhilSMS error: ' . ($response->json('message') ?? 'Unable to send SMS verification code. Check your PHILSMS_API_TOKEN.'));
        }

        if (!empty($iprogToken)) {
            $response = Http::timeout(15)
                ->acceptJson()
                ->post('https://sms.iprogtech.com/api/v1/otp/send_otp', [
                    'api_token' => $iprogToken,
                    'phone_number' => $normalizedPhone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return 'iprogsms';
            }

            Log::error('IPROG SMS send failed', [
                'phone' => $normalizedPhone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('IPROG SMS error: Unable to send SMS verification code. Check your IPROGSMS_API_TOKEN.');
        }

        // Log the OTP code when no SMS gateway API key is configured yet
        Log::info("[DairyBox SMS OTP] To {$normalizedPhone}: {$message}");

        // If user provided an email address on the form, also forward the OTP via email so they receive the code
        if ($fallbackEmail && filter_var($fallbackEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Notification::route('mail', $fallbackEmail)->notifyNow(new EmailVerificationOtpNotification($otp));
                return 'email_fallback';
            } catch (\Throwable $e) {
                Log::warning('SMS OTP email fallback failed: ' . $e->getMessage());
            }
        }

        return 'log';
    }

    protected function otpCacheKey(string $normalizedPhone): string
    {
        return 'phone_sms_otp_' . $normalizedPhone;
    }

    protected function verifiedCacheKey(string $normalizedPhone): string
    {
        return 'phone_sms_verified_' . $normalizedPhone;
    }
}
