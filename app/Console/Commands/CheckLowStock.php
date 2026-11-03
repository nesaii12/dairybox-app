<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-thresholds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan products for low stock and upcoming expiration dates, and notify administrators.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting stock threshold check...');

        $lowStockProducts = Product::where('status', 'active')
            ->where('stock', '<', 10)
            ->get();

        $nearExpiryProducts = Product::where('status', 'active')
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', Carbon::now()->addDays(7))
            ->where('expiration_date', '>', Carbon::now())
            ->get();

        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found. Skipping notification dispatch.');
            return 0;
        }

        // 1. Process Low Stock Alerts
        foreach ($lowStockProducts as $product) {
            $title = "Low Stock Alert: {$product->product_name}";
            $message = "The product '{$product->product_name} ({$product->size})' is running low on stock. Only {$product->stock} units remaining in inventory. Please consider restocking.";

            foreach ($admins as $admin) {
                // Prevent duplicate unread notifications for the same alert
                $exists = AppNotification::where('user_id', $admin->id)
                    ->where('title', $title)
                    ->where('is_read', false)
                    ->exists();

                if (!$exists) {
                    AppNotification::create([
                        'user_id' => $admin->id,
                        'title' => $title,
                        'message' => $message,
                        'link' => route('admin.inventory.index'),
                        'is_read' => false,
                    ]);
                }
            }
            $this->line("Notified admins about low stock on product #{$product->id}");
        }

        // 2. Process Near Expiration Alerts via ExpiryNotificationService
        $expiryAlertsCount = \App\Services\ExpiryNotificationService::checkAndNotify(true);
        $this->line("Dispatched {$expiryAlertsCount} near-expiration alert(s) to administrators.");

        $this->info('Stock threshold checks completed successfully.');
        return 0;
    }
}
