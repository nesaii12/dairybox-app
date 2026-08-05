<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\AppNotification;
use App\Models\AuditLog;
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

    public function cancel(Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        if ($order->order_status !== 'Pending') {
            return redirect()->back()->with('error', 'Only pending orders can be cancelled.');
        }

        try {
            DB::beginTransaction();

            $order->update(['order_status' => 'Cancelled']);

            // Restore stock back using FIFO stock additions
            foreach ($order->orderItems as $item) {
                InventoryService::addStock(
                    $item->product_id, 
                    $item->quantity, 
                    $item->product->expiration_date, 
                    "Order Cancelled #{$order->order_number}"
                );
            }

            // Notification
            AppNotification::create([
                'user_id' => auth()->id(),
                'title' => 'Order Cancelled',
                'message' => "You have cancelled your order {$order->order_number}.",
                'is_read' => false,
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'ORDER_CANCELLED_BY_CUSTOMER',
                'details' => "Customer cancelled order: {$order->order_number}",
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            return redirect()->route('customer.orders.show', $order->id)->with('success', 'Order has been cancelled and stock returned.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Cancellation failed: ' . $e->getMessage());
        }
    }
}
