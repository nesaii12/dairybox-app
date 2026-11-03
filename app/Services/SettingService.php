<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class SettingService
{
    protected static string $path = 'settings.json';

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
}
