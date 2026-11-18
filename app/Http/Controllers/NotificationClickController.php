<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Order;
use Illuminate\Http\Request;

class NotificationClickController extends Controller
{
    /**
     * Handle clicking a notification: mark it as read and redirect to the designated record.
     */
    public function handleClick(Request $request, AppNotification $notification)
    {
        // Ensure user can only access their own notifications
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Mark as read
        if (!$notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        $targetUrl = $notification->target_url;

        // If authenticated user is a rider and notification points to an order (e.g. /customer/orders/{id}, /admin/orders/{id}, or /rider/orders/{id})
        if (auth()->user()->role === 'rider') {
            if (preg_match('#/(admin|customer|rider)/orders/(\d+)#', $targetUrl, $matches)) {
                $orderId = (int) $matches[2];
                $order = Order::find($orderId);
                if ($order && ($order->rider_id === auth()->id() || ($order->order_type === 'Delivery' && is_null($order->rider_id)))) {
                    return redirect()->route('rider.orders.show', $order->id);
                }
                return redirect()->route('rider.dashboard')->with('info', "Viewing active delivery queue.");
            }
        }

        // If target URL points to a specific order show page: /admin/orders/{id} or /customer/orders/{id}
        if (preg_match('#/(admin|customer)/orders/(\d+)#', $targetUrl, $matches)) {
            $rolePrefix = $matches[1];
            $orderId = (int) $matches[2];
            $order = Order::find($orderId);

            if (!$order) {
                // If the specific order no longer exists, safely redirect to the orders index with a flash message
                $fallback = ($rolePrefix === 'admin') ? route('admin.orders.index') : route('customer.orders.index');
                return redirect()->to($fallback)->with('info', "The requested order record is no longer available.");
            }
        }

        // Redirect relative to current active host & port
        return redirect()->to($targetUrl);
    }
}
