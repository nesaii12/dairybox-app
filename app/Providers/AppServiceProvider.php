<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\AppNotification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define system-wide strong password defaults
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        View::composer('layouts.admin', function ($view) {
            try {
                $pendingOrdersCount = Order::where('order_status', 'Pending')->count();
            } catch (\Throwable $e) {
                $pendingOrdersCount = 0;
            }

            try {
                \App\Services\ExpiryNotificationService::checkAndNotify();
            } catch (\Throwable $e) {
                // Ignore transient check errors
            }

            try {
                $userId = auth()->id();
                $adminNotifCount = $userId ? AppNotification::where('user_id', $userId)->where('is_read', false)->count() : 0;
                $adminNotifications = $userId ? AppNotification::where('user_id', $userId)->latest()->take(30)->get() : collect();
            } catch (\Throwable $e) {
                $adminNotifCount = 0;
                $adminNotifications = collect();
            }

            $view->with([
                'pendingOrdersCount' => $pendingOrdersCount,
                'adminNotifCount' => $adminNotifCount,
                'adminNotifications' => $adminNotifications,
            ]);
        });
    }
}
