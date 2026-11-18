<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Sale;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiderController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->role !== 'rider') {
            if (auth()->user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('customer.catalog');
        }

        $riderId = auth()->id();

        // Auto-heal / synchronize any active orders assigned to this rider where Delivery row is missing
        $assignedOrders = Order::where('rider_id', $riderId)
            ->where('order_type', 'Delivery')
            ->whereIn('order_status', ['Out for Delivery', 'Preparing', 'Confirmed'])
            ->get();

        foreach ($assignedOrders as $ao) {
            Delivery::firstOrCreate(
                ['order_id' => $ao->id],
                [
                    'rider_id' => $riderId,
                    'delivery_status' => 'Out for Delivery',
                ]
            );
        }

        // Fetch active deliveries for the current rider
        $deliveries = Delivery::with(['order.customer', 'order.orderItems.product'])
            ->where('rider_id', $riderId)
            ->whereIn('delivery_status', ['Out for Delivery', 'Assigned'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Count of pending unassigned delivery orders waiting for fleet dispatch
        $pendingDispatchCount = Order::where('order_type', 'Delivery')
            ->whereNull('rider_id')
            ->whereIn('order_status', ['Pending', 'Confirmed', 'Preparing'])
            ->count();

        // Also fetch pending unassigned delivery orders so rider can view incoming customer orders
        $pendingOrders = Order::with(['customer', 'orderItems.product'])
            ->where('order_type', 'Delivery')
            ->whereNull('rider_id')
            ->whereIn('order_status', ['Pending', 'Confirmed', 'Preparing'])
            ->latest()
            ->take(5)
            ->get();

        return view('rider.dashboard', compact('deliveries', 'pendingDispatchCount', 'pendingOrders'));
    }

    /**
     * Real-time live polling endpoint for rider notifications and queue updates.
     */
    public function liveCheck(Request $request)
    {
        $riderId = auth()->id();

        $unreadCount = AppNotification::where('user_id', $riderId)
            ->where('is_read', false)
            ->count();

        $latestNotif = AppNotification::where('user_id', $riderId)
            ->latest('id')
            ->first();

        $activeDeliveriesCount = Delivery::where('rider_id', $riderId)
            ->whereIn('delivery_status', ['Out for Delivery', 'Assigned'])
            ->count();

        $pendingDispatchCount = Order::where('order_type', 'Delivery')
            ->whereNull('rider_id')
            ->whereIn('order_status', ['Pending', 'Confirmed', 'Preparing'])
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'active_deliveries_count' => $activeDeliveriesCount,
            'pending_dispatch_count' => $pendingDispatchCount,
            'latest_notif' => $latestNotif ? [
                'id' => $latestNotif->id,
                'title' => $latestNotif->title,
                'message' => $latestNotif->message,
                'link' => route('notifications.readAndRedirect', $latestNotif->id),
                'is_read' => (bool) $latestNotif->is_read,
                'time' => $latestNotif->created_at->diffForHumans(),
                'timestamp' => $latestNotif->created_at->timestamp,
            ] : null,
        ]);
    }

    public function markArrived(Request $request, Delivery $delivery)
    {
        if ($delivery->rider_id !== auth()->id()) {
            abort(403);
        }

        if ($delivery->delivery_status !== 'Out for Delivery') {
            return redirect()->back()->with('error', 'Only active deliveries can be marked as arrived.');
        }

        $delivery->update([
            'arrived_at' => now(),
        ]);

        $order = $delivery->order;

        // Notify customer that rider is at the destination
        AppNotification::create([
            'user_id' => $order->customer_id,
            'title' => 'Rider Arrived at Location',
            'message' => "Your rider has arrived at the destination for Order {$order->order_number}. Please prepare to receive your items.",
            'link' => route('customer.orders.show', $order->id),
            'is_read' => false,
        ]);

        // Notify admin
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            AppNotification::create([
                'user_id' => $admin->id,
                'title' => "Rider Arrived: {$order->order_number}",
                'message' => "Rider {$delivery->rider->name} has arrived at the customer location for Order {$order->order_number}.",
                'link' => route('admin.deliveries.index', ['search' => $order->order_number]),
                'is_read' => false,
            ]);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'RIDER_ARRIVED',
            'details' => "Rider arrived at location for Order {$order->order_number}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('rider.dashboard')->with('success', "Arrival recorded for Order {$order->order_number}. Customer notified.");
    }

    public function updateStatus(Request $request, Delivery $delivery)
    {
        $request->validate([
            'status' => 'required|in:Delivered,Failed Delivery,Cancelled',
            'cancellation_reason' => 'nullable|string|max:255',
            'cancelled_after_arrival' => 'nullable|boolean',
        ]);

        if ($delivery->rider_id !== auth()->id()) {
            abort(403);
        }

        $newStatus = $request->status;
        $order = $delivery->order;
        $hasArrived = $delivery->arrived_at !== null;
        $cancelledAfterArrival = $request->boolean('cancelled_after_arrival') || ($hasArrived && in_array($newStatus, ['Cancelled', 'Failed Delivery']));
        $cancellationReason = $request->input('cancellation_reason') ?: ($cancelledAfterArrival ? 'Customer cancelled after rider arrived at destination.' : "Order marked as {$newStatus}");

        try {
            DB::beginTransaction();

            // 1. Update delivery status & metadata
            $deliveryData = [
                'delivery_status' => $newStatus,
                'delivered_at' => ($newStatus === 'Delivered') ? now() : null,
            ];

            if ($newStatus !== 'Delivered') {
                $deliveryData['cancelled_after_arrival'] = $cancelledAfterArrival;
                $deliveryData['cancellation_reason'] = $cancellationReason;
            }

            $delivery->update($deliveryData);

            // 2. Update order status
            $orderStatus = ($newStatus === 'Delivered')
                ? (strtolower($order->payment_method ?? 'cod') === 'cod' ? 'Delivered - Pending Remittance' : 'Delivered')
                : 'Cancelled';
            $orderData = [
                'order_status' => $orderStatus,
            ];

            if ($newStatus !== 'Delivered') {
                $orderData['cancelled_after_arrival'] = $cancelledAfterArrival;
                $orderData['cancellation_reason'] = $cancellationReason;
            }

            $order->update($orderData);

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
                    'link' => route('customer.orders.show', $order->id),
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
                        "Delivery Cancelled/Failed #{$order->order_number} (" . ($cancelledAfterArrival ? 'Post-Arrival' : 'Standard') . ")"
                    );
                }

                // Notify customer
                AppNotification::create([
                    'user_id' => $order->customer_id,
                    'title' => 'Delivery Unsuccessful',
                    'message' => "Your delivery for order {$order->order_number} is logged as: {$newStatus}." . ($cancelledAfterArrival ? " Reason: Cancellation after rider arrival." : ""),
                    'link' => route('customer.orders.show', $order->id),
                    'is_read' => false,
                ]);

                // If customer cancelled after rider arrived, alert admins for verification & 3-day restriction
                if ($cancelledAfterArrival) {
                    $admins = User::where('role', 'admin')->get();
                    foreach ($admins as $admin) {
                        AppNotification::create([
                            'user_id' => $admin->id,
                            'title' => "Incident: Order {$order->order_number} Cancelled After Arrival",
                            'message' => "Customer {$order->customer->name} cancelled Order {$order->order_number} after rider arrived. Review and verify to apply 3-day restriction.",
                            'link' => route('admin.deliveries.index', ['status' => 'incidents', 'search' => $order->order_number]),
                            'is_read' => false,
                        ]);
                    }
                }

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => $cancelledAfterArrival ? 'ORDER_CANCELLED_AFTER_ARRIVAL' : 'RIDER_DELIVERY_FAILED',
                    'details' => "Rider reported Order {$order->order_number} as {$newStatus}. Cancelled after arrival: " . ($cancelledAfterArrival ? 'YES' : 'NO') . ". Reason: {$cancellationReason}",
                    'ip_address' => $request->ip(),
                ]);
            }

            DB::commit();

            return redirect()->route('rider.dashboard')->with('success', "Delivery status updated to {$newStatus}." . ($cancelledAfterArrival ? " Incident reported to administrator." : ""));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function history()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->role !== 'rider') {
            if (auth()->user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('customer.catalog');
        }

        $deliveries = Delivery::with(['order.customer', 'order.orderItems.product'])
            ->where('rider_id', auth()->id())
            ->where('delivery_status', '!=', 'Out for Delivery')
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return view('rider.history', compact('deliveries'));
    }

    /**
     * Display order & customer details for the rider.
     */
    public function showOrder(Request $request, Order $order)
    {
        $riderId = auth()->id();

        // Security check: Rider can only view Delivery orders assigned to them or unassigned delivery orders in the fleet
        if ($order->order_type !== 'Delivery' || ($order->rider_id && $order->rider_id !== $riderId)) {
            abort(403, 'Unauthorized access to this order record.');
        }

        $order->load(['customer', 'orderItems.product', 'delivery', 'rider']);

        $customer = $order->customer;
        $completedOrdersCount = $customer->orders()
            ->whereIn('order_status', ['Delivered', 'Paid & Remitted'])
            ->count();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_status' => $order->order_status,
                    'order_type' => $order->order_type,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'is_paid' => $order->isPaid(),
                    'delivery_address' => $order->delivery_address,
                    'schedule_date' => $order->schedule_date ? $order->schedule_date->format('M d, Y') : null,
                    'schedule_time' => $order->schedule_time ? \Carbon\Carbon::parse($order->schedule_time)->format('h:i A') : 'Flexible Window',
                    'subtotal' => number_format($order->subtotal, 2),
                    'delivery_fee' => number_format($order->delivery_fee, 2),
                    'total_amount' => number_format($order->total_amount, 2),
                    'created_at' => $order->created_at->format('M d, Y h:i A'),
                    'items' => $order->orderItems->map(function ($item) {
                        return [
                            'name' => $item->product ? $item->product->product_name : 'Item',
                            'size' => $item->product ? $item->product->size : null,
                            'quantity' => $item->quantity,
                            'price' => number_format($item->unit_price, 2),
                            'subtotal' => number_format($item->subtotal, 2),
                        ];
                    }),
                    'delivery' => $order->delivery ? [
                        'id' => $order->delivery->id,
                        'delivery_status' => $order->delivery->delivery_status,
                        'arrived_at' => $order->delivery->arrived_at ? $order->delivery->arrived_at->format('h:i A') : null,
                    ] : null,
                ],
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'avatar' => $customer->avatar,
                    'member_since' => $customer->created_at->format('M Y'),
                    'completed_orders_count' => $completedOrdersCount,
                    'status' => $customer->status ?? 'active',
                ],
            ]);
        }

        return view('rider.order-details', compact('order', 'customer', 'completedOrdersCount'));
    }
}
