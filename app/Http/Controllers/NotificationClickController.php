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
