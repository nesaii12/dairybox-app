<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    protected XenditService $xenditService;

    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    /**
     * Handle incoming Xendit invoice webhook callbacks.
     */
    public function handle(Request $request)
    {
        $callbackToken = $request->header('x-callback-token');

        if (!$this->xenditService->verifyWebhookToken($callbackToken)) {
            Log::warning('Xendit Webhook Token Mismatch', [
                'received' => $callbackToken,
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Unauthorized callback token'], 401);
        }

        $payload = $request->all();
        $invoiceId = $payload['id'] ?? null;
        $externalId = $payload['external_id'] ?? null;
        $status = strtoupper($payload['status'] ?? '');
        $paymentMethod = $payload['payment_method'] ?? $payload['payment_channel'] ?? 'ONLINE';

        Log::info("Xendit Webhook Received: Invoice {$invoiceId}, External {$externalId}, Status: {$status}", $payload);

        // Find the order by external_id (order_number) or xendit_invoice_id
        $order = Order::where('order_number', $externalId)
            ->orWhere('xendit_invoice_id', $invoiceId)
            ->first();

        if (!$order) {
            Log::warning("Xendit Webhook: Order not found for External ID '{$externalId}' or Invoice ID '{$invoiceId}'");
            return response()->json(['message' => 'Order not found, acknowledged'], 200);
        }

        if (in_array($status, ['PAID', 'SETTLED'])) {
            if (!$order->isPaid()) {
                $order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'Confirmed',
                    'payment_channel' => $paymentMethod,
                    'paid_at' => !empty($payload['paid_at']) ? \Carbon\Carbon::parse($payload['paid_at']) : now(),
                ]);

                // Notify Customer
                AppNotification::create([
                    'user_id' => $order->customer_id,
                    'title' => 'Payment Confirmed',
                    'message' => "Your payment of ₱" . number_format($order->total_amount, 2) . " for order {$order->order_number} was confirmed via {$paymentMethod}.",
                    'link' => route('customer.orders.show', $order->id),
                    'is_read' => false,
                ]);

                // Notify Admins
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    AppNotification::create([
                        'user_id' => $admin->id,
                        'title' => 'Order Payment Received',
                        'message' => "Order {$order->order_number} was paid online via {$paymentMethod} (₱" . number_format($order->total_amount, 2) . ").",
                        'link' => route('admin.orders.show', $order->id),
                        'is_read' => false,
                    ]);
                }

                // Notify Riders if order is for delivery
                if ($order->order_type === 'Delivery') {
                    $targetRiders = $order->rider_id 
                        ? User::where('id', $order->rider_id)->get() 
                        : User::where('role', 'rider')->where('status', 'active')->get();

                    foreach ($targetRiders as $rider) {
                        AppNotification::create([
                            'user_id' => $rider->id,
                            'title' => 'Customer Order Paid Online',
                            'message' => "Order {$order->order_number} for delivery to {$order->delivery_address} was paid online via {$paymentMethod} (₱" . number_format($order->total_amount, 2) . ").",
                            'link' => route('rider.dashboard'),
                            'is_read' => false,
                        ]);
                    }
                }

                AuditLog::create([
                    'user_id' => $order->customer_id,
                    'action' => 'PAYMENT_WEBHOOK_CONFIRMED',
                    'details' => "Xendit Webhook: Order {$order->order_number} marked PAID via {$paymentMethod} (Invoice: {$invoiceId})",
                    'ip_address' => $request->ip(),
                ]);
            }
        } elseif ($status === 'EXPIRED') {
            if (!$order->isPaid() && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'expired',
                ]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Webhook processed successfully']);
    }
}
