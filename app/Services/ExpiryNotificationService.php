<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ExpiryNotificationService
{
    /**
     * Threshold in days to consider an expiration date "almost close".
     */
    public const DAYS_THRESHOLD = 7;

    /**
     * Scan products for approaching expiration dates and dispatch notifications to all administrators.
     *
     * @param bool $force Bypass the rate-limit cache lock
     * @return int Number of notifications created
     */
    public static function checkAndNotify(bool $force = false): int
    {
        // Rate-limit automated background checks so repeated page requests don't re-run DB queries
        if (!$force && Cache::has('expiry_notification_check_lock')) {
            return 0;
        }

        $admins = User::where('role', 'admin')->get();
        if ($admins->isEmpty()) {
            return 0;
        }

        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $thresholdDate = $today->copy()->addDays(self::DAYS_THRESHOLD)->endOfDay();

        // Query active products with stock > 0 and expiration date on or before threshold
        $products = Product::where('status', 'active')
            ->whereNotNull('expiration_date')
            ->where('stock', '>', 0)
            ->where('expiration_date', '<=', $thresholdDate)
            ->get();

        $notificationsCreated = 0;

        foreach ($products as $product) {
            $expiry = Carbon::parse($product->expiration_date)->startOfDay();
            $diffDays = $today->diffInDays($expiry, false);

            if ($diffDays < 0) {
                $daysPast = abs($diffDays);
                $title = "Expired Stock Alert: {$product->product_name}";
                $message = "The batch of '{$product->product_name} ({$product->size})' expired {$daysPast} day(s) ago on " . $expiry->format('M d, Y') . ". Current stock: {$product->stock} unit(s). Please pull from shelf.";
            } elseif ($diffDays === 0) {
                $title = "Critical: {$product->product_name} Expires Today";
                $message = "The batch for '{$product->product_name} ({$product->size})' expires today (" . $expiry->format('M d, Y') . ") with {$product->stock} unit(s) remaining in stock.";
            } elseif ($diffDays <= 2) {
                $title = "Urgent: {$product->product_name} Expiring Soon";
                $message = "The batch for '{$product->product_name} ({$product->size})' is set to expire in {$diffDays} day(s) on " . $expiry->format('M d, Y') . ". {$product->stock} unit(s) remaining.";
            } else {
                $title = "Near Expiration: {$product->product_name}";
                $message = "Product '{$product->product_name} ({$product->size})' is approaching its expiration date on " . $expiry->format('M d, Y') . " ({$diffDays} days remaining). Current stock: {$product->stock} units.";
            }

            foreach ($admins as $admin) {
                // Check if an unread notification with this title exists, OR one created within last 24 hours
                $recentNotification = AppNotification::where('user_id', $admin->id)
                    ->where('title', $title)
                    ->where(function ($query) use ($now) {
                        $query->where('is_read', false)
                              ->orWhere('created_at', '>=', $now->copy()->subHours(24));
                    })
                    ->exists();

                if (!$recentNotification) {
                    AppNotification::create([
                        'user_id' => $admin->id,
                        'title' => $title,
                        'message' => $message,
                        'link' => route('admin.products.show', $product->id),
                        'is_read' => false,
                    ]);
                    $notificationsCreated++;
                }
            }
        }

        // Lock for 10 minutes to prevent high-frequency querying
        Cache::put('expiry_notification_check_lock', true, $now->copy()->addMinutes(10));

        return $notificationsCreated;
    }

    /**
     * Check a specific product immediately (used on Product creation, update, or Stock In).
     */
    public static function checkProduct(Product $product): void
    {
        if (!$product->expiration_date || $product->stock <= 0) {
            return;
        }

        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $expiry = Carbon::parse($product->expiration_date)->startOfDay();
        $diffDays = $today->diffInDays($expiry, false);

        if ($diffDays <= self::DAYS_THRESHOLD) {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isEmpty()) {
                return;
            }

            if ($diffDays < 0) {
                $daysPast = abs($diffDays);
                $title = "Expired Stock Alert: {$product->product_name}";
                $message = "The batch of '{$product->product_name} ({$product->size})' expired {$daysPast} day(s) ago on " . $expiry->format('M d, Y') . ". Current stock: {$product->stock} unit(s).";
            } elseif ($diffDays === 0) {
                $title = "Critical: {$product->product_name} Expires Today";
                $message = "The batch for '{$product->product_name} ({$product->size})' expires today (" . $expiry->format('M d, Y') . ") with {$product->stock} unit(s) remaining.";
            } elseif ($diffDays <= 2) {
                $title = "Urgent: {$product->product_name} Expiring Soon";
                $message = "The batch for '{$product->product_name} ({$product->size})' is set to expire in {$diffDays} day(s) on " . $expiry->format('M d, Y') . ". {$product->stock} unit(s) remaining.";
            } else {
                $title = "Near Expiration: {$product->product_name}";
                $message = "Product '{$product->product_name} ({$product->size})' is approaching its expiration date on " . $expiry->format('M d, Y') . " ({$diffDays} days remaining). Current stock: {$product->stock} units.";
            }

            foreach ($admins as $admin) {
                $exists = AppNotification::where('user_id', $admin->id)
                    ->where('title', $title)
                    ->where('is_read', false)
                    ->exists();

                if (!$exists) {
                    AppNotification::create([
                        'user_id' => $admin->id,
                        'title' => $title,
                        'message' => $message,
                        'link' => route('admin.products.show', $product->id),
                        'is_read' => false,
                    ]);
                }
            }
        }
    }
}
