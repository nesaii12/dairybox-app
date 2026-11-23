<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Delivery;
use App\Models\Reservation;
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
                $arrivedDeliveriesCount = Delivery::where('delivery_status', 'Out for Delivery')
                    ->whereNotNull('arrived_at')
                    ->count();
            } catch (\Throwable $e) {
                $arrivedDeliveriesCount = 0;
            }

            try {
                $unremittedOrders = Order::where('order_type', 'Delivery')
                    ->whereRaw("LOWER(payment_method) = 'cod'")
                    ->whereNull('remittance_id')
                    ->whereIn('order_status', ['Delivered', 'Delivered - Pending Remittance'])
                    ->get();

                $collectibleOrders = $unremittedOrders->filter(fn ($o) => $o->collectibleCodAmount() > 0);
                $pendingFleetCash = (float) $collectibleOrders->sum(fn ($o) => $o->collectibleCodAmount());
                $pendingRemittanceRidersCount = $collectibleOrders->pluck('rider_id')->filter()->unique()->count();

                $pendingRemittancesCount = $pendingFleetCash > 0 ? max(1, $pendingRemittanceRidersCount) : 0;
            } catch (\Throwable $e) {
                $pendingFleetCash = 0;
                $pendingRemittanceRidersCount = 0;
                $pendingRemittancesCount = 0;
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

            try {
                $activeReservationsCount = Reservation::whereIn('status', [
                    Reservation::STATUS_PENDING,
                    Reservation::STATUS_CONFIRMED,
                    Reservation::STATUS_READY,
                    Reservation::STATUS_OUT_FOR_DELIVERY,
                ])->count();
            } catch (\Throwable $e) {
                $activeReservationsCount = 0;
            }

            $view->with([
                'pendingOrdersCount' => $pendingOrdersCount,
                'arrivedDeliveriesCount' => $arrivedDeliveriesCount,
                'pendingFleetCash' => $pendingFleetCash,
                'pendingRemittanceRidersCount' => $pendingRemittanceRidersCount,
                'pendingRemittancesCount' => $pendingRemittancesCount,
                'activeReservationsCount' => $activeReservationsCount,
                'adminNotifCount' => $adminNotifCount,
                'adminNotifications' => $adminNotifications,
            ]);
        });
    }
}
