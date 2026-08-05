<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Sale;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderController extends Controller
{
    public function index()
    {
        // Fetch active deliveries for the current rider
        $deliveries = Delivery::with(['order.customer', 'order.orderItems.product'])
            ->where('rider_id', auth()->id())
            ->where('delivery_status', 'Out for Delivery')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('rider.dashboard', compact('deliveries'));
    }

    public function updateStatus(Request $request, Delivery $delivery)
    {
        $request->validate([
            'status' => 'required|in:Delivered,Failed Delivery,Cancelled',
        ]);

        if ($delivery->rider_id !== auth()->id()) {
            abort(403);
        }

        $newStatus = $request->status;
        $order = $delivery->order;

        try {
            DB::beginTransaction();

            // 1. Update delivery status
            $delivery->update([
                'delivery_status' => $newStatus,
                'delivered_at' => ($newStatus === 'Delivered') ? now() : null,
            ]);

            // 2. Update order status
            $orderStatus = ($newStatus === 'Delivered') ? 'Delivered' : 'Cancelled';
            $order->update(['order_status' => $orderStatus]);

            // 3. Fulfill downstream consequences
            if ($newStatus === 'Delivered') {
                // Record sales
                Sale::create([
                    'order_id' => $order->id,
                    'total_amount' => $order->total_amount,
                    'sales_date' => now()->toDateString(),
                ]);

                // Notify customer
                AppNotification::create([
                    'user_id' => $order->customer_id,
                    'title' => 'Order Delivered',
                    'message' => "Your order {$order->order_number} has been marked as Delivered by the rider.",
                    'is_read' => false,
                ]);

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'RIDER_DELIVERY_SUCCESS',
                    'details' => "Rider completed delivery of Order {$order->order_number}",
                    'ip_address' => $request->ip(),
                ]);
            } else {
                // Return FIFO inventory back
                foreach ($order->orderItems as $item) {
                    InventoryService::addStock(
                        $item->product_id, 
                        $item->quantity, 
                        $item->product->expiration_date,
                        "Delivery Failed/Cancelled #{$order->order_number}"
                    );
                }

                // Notify customer
                AppNotification::create([
                    'user_id' => $order->customer_id,
                    'title' => 'Delivery Unsuccessful',
                    'message' => "Your delivery for order {$order->order_number} is logged as: {$newStatus}.",
                    'is_read' => false,
                ]);

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'RIDER_DELIVERY_FAILED',
                    'details' => "Rider failed delivery of Order {$order->order_number}. Reason: {$newStatus}",
                    'ip_address' => $request->ip(),
                ]);
            }

            DB::commit();

            return redirect()->route('rider.dashboard')->with('success', "Delivery status updated to {$newStatus}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function history()
    {
        $deliveries = Delivery::with(['order.customer'])
            ->where('rider_id', auth()->id())
            ->where('delivery_status', '!=', 'Out for Delivery')
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return view('rider.history', compact('deliveries'));
    }
}
