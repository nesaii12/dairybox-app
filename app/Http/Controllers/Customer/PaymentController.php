<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\XenditService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected XenditService $xenditService;

    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    /**
     * Handle return from Xendit when payment succeeds.
     */
    public function success(Request $request, Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        // Verify with Xendit API directly
        if ($order->xendit_invoice_id) {
            $invoice = $this->xenditService->getInvoice($order->xendit_invoice_id);
            $status = strtoupper($invoice['status'] ?? '');

            if (in_array($status, ['PAID', 'SETTLED']) && !$order->isPaid()) {
                $paymentChannel = $invoice['payment_method'] ?? $invoice['payment_channel'] ?? 'ONLINE';

                $order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'Confirmed',
                    'payment_channel' => $paymentChannel,
                    'paid_at' => now(),
                ]);

                // Notify Customer
                AppNotification::create([
                    'user_id' => $order->customer_id,
                    'title' => 'Payment Confirmed',
                    'message' => "Your payment of ₱" . number_format($order->total_amount, 2) . " for order {$order->order_number} was successfully confirmed via {$paymentChannel}.",
                    'link' => route('customer.orders.show', $order->id),
                    'is_read' => false,
                ]);

                // Notify Admins
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    AppNotification::create([
                        'user_id' => $admin->id,
                        'title' => 'Order Payment Received',
                        'message' => "Order {$order->order_number} was paid online via {$paymentChannel} (₱" . number_format($order->total_amount, 2) . ").",
                        'link' => route('admin.orders.show', $order->id),
                        'is_read' => false,
                    ]);
                }

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'PAYMENT_RECEIVED_XENDIT',
                    'details' => "Order {$order->order_number} paid via {$paymentChannel} (₱" . number_format($order->total_amount, 2) . ")",
                    'ip_address' => $request->ip(),
                ]);
            }
        }

        return redirect()->route('customer.orders.show', $order->id)
            ->with('success', 'Payment successful! Your order has been confirmed and is now being processed.');
    }

    /**
     * Handle return from Xendit when payment is cancelled or failed.
     */
    public function failure(Request $request, Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        return redirect()->route('customer.orders.show', $order->id)
            ->with('warning', 'Online payment was not completed. You can click "Pay with Xendit" to retry anytime.');
    }

    /**
     * Retry payment for an unpaid online order.
     */
    public function retry(Request $request, Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        if ($order->isPaid()) {
            return redirect()->route('customer.orders.show', $order->id)
                ->with('info', 'This order has already been paid.');
        }

        if ($order->order_status === 'Cancelled') {
            return redirect()->route('customer.orders.show', $order->id)
                ->with('error', 'Cannot pay for a cancelled order.');
        }

        try {
            // Check existing invoice
            if ($order->xendit_invoice_id) {
                $existing = $this->xenditService->getInvoice($order->xendit_invoice_id);
                if (!empty($existing) && strtoupper($existing['status'] ?? '') === 'PENDING' && !empty($existing['invoice_url'])) {
                    return redirect()->away($existing['invoice_url']);
                }
            }

            // Create a fresh invoice if expired or missing
            $invoice = $this->xenditService->createInvoice($order);

            $order->update([
                'xendit_invoice_id' => $invoice['id'],
                'xendit_invoice_url' => $invoice['invoice_url'],
                'payment_status' => 'pending',
            ]);

            return redirect()->away($invoice['invoice_url']);

        } catch (\Exception $e) {
            return redirect()->route('customer.orders.show', $order->id)
                ->with('error', 'Could not initiate payment: ' . $e->getMessage());
        }
    }
}
