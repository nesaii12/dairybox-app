<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Generate a 6-digit OTP and send it via SMS to the given phone number.
     *
     * @return array{otp: string, sent_via: string, cooldown: int}
     */
    public function generateAndSend(string $phone): array
    {
        $normalized = $this->normalizePhone($phone);
        $otp = sprintf('%06d', random_int(100000, 999999));
        $expiresAt = now()->addMinutes(self::EXPIRATION_MINUTES);
        $resendAt = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        Cache::put($this->otpCacheKey($normalized), [
            'code' => $otp,
            'expires_at' => $expiresAt->timestamp,
            'resend_at' => $resendAt->timestamp,
        ], $expiresAt);

        $message = "Your DairyBox verification code is {$otp}. Valid for " . self::EXPIRATION_MINUTES . " minutes. Do not share this code with anyone.";
        $sentVia = $this->dispatchSms($normalized, $message);

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
            if ($sessionId) {
                $this->markVerifiedForSession($normalized, $sessionId);
            }
            return true;
        }

        return false;
    }

    /**
     * Mark a phone number as verified for the current session (valid for 30 minutes).
     */
    public function markVerifiedForSession(string $phone, string $sessionId): void
    {
        $normalized = $this->normalizePhone($phone);
        Cache::put($this->verifiedCacheKey($normalized, $sessionId), true, now()->addMinutes(30));
    }

    /**
     * Check if a phone number has been verified for the current session.
     */
    public function isVerifiedForSession(string $phone, string $sessionId): bool
    {
        $normalized = $this->normalizePhone($phone);
        return (bool) Cache::get($this->verifiedCacheKey($normalized, $sessionId), false);
    }

    /**
     * Clear the session verification flag after registration completes.
     */
    public function clearVerifiedForSession(string $phone, string $sessionId): void
    {
        $normalized = $this->normalizePhone($phone);
        Cache::forget($this->verifiedCacheKey($normalized, $sessionId));
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
     * Dispatch the SMS message through Semaphore, PhilSMS, or local log fallback.
     */
    protected function dispatchSms(string $normalizedPhone, string $message): string
    {
        $semaphoreKey = config('services.sms.semaphore_api_key');
        $philsmsToken = config('services.sms.philsms_api_token');

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

            throw new \RuntimeException('Unable to send SMS verification code via Semaphore. Please check your phone number or try again.');
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

            throw new \RuntimeException('Unable to send SMS verification code via PhilSMS. Please check your phone number or try again.');
        }

        // Fallback to application log when no SMS API key is configured yet
        Log::info("[DairyBox SMS OTP] To {$normalizedPhone}: {$message}");

        return 'log';
    }

    protected function otpCacheKey(string $normalizedPhone): string
    {
        return 'phone_sms_otp_' . $normalizedPhone;
    }

    protected function verifiedCacheKey(string $normalizedPhone, string $sessionId): string
    {
        return 'phone_sms_verified_' . $sessionId . '_' . $normalizedPhone;
    }
}

