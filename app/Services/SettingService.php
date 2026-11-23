<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    protected static string $path = 'settings.json';

    public const POLICY_GROUP = 'reservation_policy';
    public const KEY_CANCELLATION_FEE_ENABLED = 'reservation_cancellation_fee_enabled';
    public const KEY_CANCELLATION_FEE_PERCENTAGE = 'reservation_cancellation_fee_percentage';
    public const KEY_CANCELLATION_POLICY_DESCRIPTION = 'reservation_cancellation_policy_description';

    /**
     * Default application and operational settings.
     */
    public static function defaults(): array
    {
        return [
            'store_name' => 'DairyBox',
            'store_email' => 'contact@dairybox.com',
            'store_phone' => '0917-123-4567',
            'store_address' => 'Surigao City, Surigao del Norte, Philippines',
            'low_stock_threshold' => 10,
            'near_expiry_days' => 7,
            'order_auto_cancel_hours' => 24,
            'operating_hours' => '8:00 AM - 6:00 PM (Mon - Sat)',
        ];
    }

    /**
     * Retrieve all settings merged with defaults.
     */
    public static function all(): array
    {
        if (!Storage::disk('local')->exists(self::$path)) {
            return self::defaults();
        }

        try {
            $raw = Storage::disk('local')->get(self::$path);
            $decoded = json_decode($raw, true);
            return array_merge(self::defaults(), is_array($decoded) ? $decoded : []);
        } catch (\Throwable $e) {
            return self::defaults();
        }
    }

    /**
     * Get a specific setting value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default ?? (self::defaults()[$key] ?? null);
    }

    /**
     * Update and persist settings.
     */
    public static function set(array $values): void
    {
        $current = self::all();
        $updated = array_merge($current, $values);
        Storage::disk('local')->put(self::$path, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Retrieve the Reservation Cancellation Policy strictly from the database.
     *
     * @return array{
     *     enabled: bool,
     *     fee_percentage: float,
     *     refund_percentage: float,
     *     effective_fee_percentage: float,
     *     effective_refund_percentage: float,
     *     description: string,
     *     persisted: bool
     * }
     */
    public static function getReservationCancellationPolicy(): array
    {
        if (!Schema::hasTable('settings')) {
            return [
                'enabled' => false,
                'fee_percentage' => 0.0,
                'refund_percentage' => 100.0,
                'effective_fee_percentage' => 0.0,
                'effective_refund_percentage' => 100.0,
                'description' => '',
                'persisted' => false,
            ];
        }

        $rows = Setting::where('group', self::POLICY_GROUP)
            ->pluck('value', 'key');

        $hasPersistedRows = $rows->has(self::KEY_CANCELLATION_FEE_ENABLED)
            && $rows->has(self::KEY_CANCELLATION_FEE_PERCENTAGE);

        $enabled = filter_var($rows->get(self::KEY_CANCELLATION_FEE_ENABLED, false), FILTER_VALIDATE_BOOLEAN);
        $rawFee = (float) $rows->get(self::KEY_CANCELLATION_FEE_PERCENTAGE, 0);
        $feePercentage = round(max(0.0, min(100.0, $rawFee)), 2);
        $refundPercentage = round(max(0.0, 100.0 - $feePercentage), 2);
        $description = trim((string) $rows->get(self::KEY_CANCELLATION_POLICY_DESCRIPTION, ''));

        return [
            'enabled' => $enabled,
            'fee_percentage' => $feePercentage,
            'refund_percentage' => $refundPercentage,
            'effective_fee_percentage' => $enabled ? $feePercentage : 0.0,
            'effective_refund_percentage' => $enabled ? $refundPercentage : 100.0,
            'description' => $description,
            'persisted' => $hasPersistedRows,
        ];
    }

    /**
     * Persist Reservation Cancellation Policy settings to the database and log an audit entry.
     */
    public static function updateReservationCancellationPolicy(
        bool $enabled,
        float $feePercentage,
        ?string $description = null,
        ?int $adminId = null,
        ?string $ipAddress = null
    ): array {
        $normalizedFee = round(max(0.0, min(100.0, $feePercentage)), 2);
        $normalizedRefund = round(max(0.0, 100.0 - $normalizedFee), 2);
        $cleanDescription = trim((string) ($description ?? ''));

        DB::transaction(function () use ($enabled, $normalizedFee, $normalizedRefund, $cleanDescription, $adminId, $ipAddress) {
            Setting::updateOrCreate(
                ['key' => self::KEY_CANCELLATION_FEE_ENABLED],
                [
                    'value' => $enabled ? '1' : '0',
                    'type' => 'boolean',
                    'group' => self::POLICY_GROUP,
                ]
            );

            Setting::updateOrCreate(
                ['key' => self::KEY_CANCELLATION_FEE_PERCENTAGE],
                [
                    'value' => self::formatPercentageStorage($normalizedFee),
                    'type' => 'decimal',
                    'group' => self::POLICY_GROUP,
                ]
            );

            Setting::updateOrCreate(
                ['key' => self::KEY_CANCELLATION_POLICY_DESCRIPTION],
                [
                    'value' => $cleanDescription,
                    'type' => 'string',
                    'group' => self::POLICY_GROUP,
                ]
            );

            if ($adminId) {
                $statusText = $enabled ? 'Enabled' : 'Disabled';
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'RESERVATION_POLICY_UPDATED',
                    'details' => "Updated Reservation Cancellation Policy: Status = {$statusText}, Cancellation Fee = {$normalizedFee}%, Refund Percentage = {$normalizedRefund}%.",
                    'ip_address' => $ipAddress,
                ]);
            }
        });

        return self::getReservationCancellationPolicy();
    }

    protected static function formatPercentageStorage(float $value): string
    {
        return fmod($value, 1.0) === 0.0
            ? (string) (int) $value
            : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
