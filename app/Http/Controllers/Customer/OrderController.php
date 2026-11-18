<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('customer_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('customer.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['orderItems.product', 'rider']);
        
        return view('customer.orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        if ($order->order_status !== 'Pending') {
            return redirect()->back()->with('error', 'Only pending orders can be cancelled.');
        }

        $request->validate([
            'cancellation_reason' => 'nullable|string|max:255',
        ]);

        $reason = trim($request->input('cancellation_reason')) ?: 'Cancelled by customer';

        try {
            DB::beginTransaction();

            $order->update([
                'order_status' => 'Cancelled',
                'cancellation_reason' => $reason,
            ]);

            // Restore stock back using FIFO stock additions
            foreach ($order->orderItems as $item) {
                InventoryService::addStock(
                    $item->product_id, 
                    $item->quantity, 
                    $item->product->expiration_date, 
                    "Order Cancelled #{$order->order_number}: {$reason}"
                );
            }

            // Notification for customer
            AppNotification::create([
                'user_id' => auth()->id(),
                'title' => 'Order Cancelled',
                'message' => "You have cancelled your order {$order->order_number}. Reason: {$reason}",
                'link' => route('customer.orders.show', $order->id),
                'is_read' => false,
            ]);

            // Notification for admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                AppNotification::create([
                    'user_id' => $admin->id,
                    'title' => 'Order Cancelled by Customer',
                    'message' => "Order {$order->order_number} was cancelled by " . auth()->user()->name . ". Reason: {$reason}",
                    'link' => route('admin.orders.show', $order->id),
                    'is_read' => false,
                ]);
            }

            // Notification for assigned rider if delivery was in progress
            if ($order->rider_id) {
                AppNotification::create([
                    'user_id' => $order->rider_id,
                    'title' => 'Assigned Delivery Cancelled',
                    'message' => "Order {$order->order_number} assigned to you was cancelled by customer {$order->customer->name}. Reason: {$reason}",
                    'link' => route('rider.dashboard'),
                    'is_read' => false,
                ]);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'ORDER_CANCELLED_BY_CUSTOMER',
                'details' => "Customer cancelled order: {$order->order_number}. Reason: {$reason}",
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            return redirect()->route('customer.orders.show', $order->id)->with('success', 'Order has been cancelled.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Cancellation failed: ' . $e->getMessage());
        }
    }
}
