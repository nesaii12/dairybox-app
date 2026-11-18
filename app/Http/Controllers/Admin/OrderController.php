<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Sale;
use App\Models\Delivery;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'rider']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('order_type', $request->type);
        }

        $perPage = $request->input('per_page', session('per_page_orders', 10));
        session(['per_page_orders' => $perPage]);

        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'orderItems.product', 'rider']);
        
        // Fetch all active riders to assign
        $riders = User::where('role', 'rider')->where('status', 'active')->get();

        return view('admin.orders.show', compact('order', 'riders'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'order_status' => 'required|in:Pending,Confirmed,Preparing,Ready for Pickup,Out for Delivery,Delivered,Delivered - Pending Remittance,Paid & Remitted,Cancelled',
            'cancellation_reason' => 'nullable|string|max:255',
        ]);

        $oldStatus = $order->order_status;
        $newStatus = $request->order_status;
        $cancellationReason = trim($request->input('cancellation_reason')) ?: 'Cancelled by administrator.';

        if ($order->order_type === 'Delivery' && !$order->rider_id && in_array($newStatus, ['Out for Delivery', 'Delivered', 'Delivered - Pending Remittance'])) {
            return redirect()->back()
                ->with('error', "Invalid action: No delivery rider is in charge. Order {$order->order_number} cannot be marked as '{$newStatus}' while rider status is Unassigned. Please assign a delivery driver first.")
                ->withErrors(['rider_id' => "Invalid: Please select and assign a delivery rider before setting the order to {$newStatus}."]);
        }

        if ($oldStatus === $newStatus) {
            return redirect()->back()->with('success', 'Order status updated (no changes).');
        }

        try {
            DB::beginTransaction();

            $orderData = ['order_status' => $newStatus];
            if ($newStatus === 'Cancelled') {
                $orderData['cancellation_reason'] = $cancellationReason;
            }
            $order->update($orderData);

            if ($newStatus === 'Out for Delivery' && $order->rider_id) {
                Delivery::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'rider_id' => $order->rider_id,
                        'delivery_status' => 'Out for Delivery',
                    ]
                );

                // Notify assigned rider
                AppNotification::create([
                    'user_id' => $order->rider_id,
                    'title' => 'Order Out for Delivery',
                    'message' => "Order {$order->order_number} is out for delivery to {$order->delivery_address}.",
                    'link' => route('rider.orders.show', $order->id),
                    'is_read' => false,
                ]);
            }

            // If cancelled, return the inventory back using FIFO additions
            if ($newStatus === 'Cancelled') {
                if ($order->delivery) {
                    $order->delivery->update([
                        'delivery_status' => 'Cancelled',
                        'cancellation_reason' => $cancellationReason,
                    ]);
                }

                foreach ($order->orderItems as $item) {
                    InventoryService::addStock(
                        $item->product_id, 
                        $item->quantity, 
                        $item->product->expiration_date,
                        "Order Cancelled #{$order->order_number}: {$cancellationReason}"
                    );
                }

                // If admin requested restricting customer upon cancellation
                if ($request->boolean('restrict_customer')) {
                    $banDurationDays = 3;
                    $until = now()->addDays($banDurationDays);
                    $order->customer->update([
                        'status' => 'restricted',
                        'restricted_until' => $until,
                    ]);

                    AppNotification::create([
                        'user_id' => $order->customer_id,
                        'title' => 'Ordering Privileges Suspended (3 Days)',
                        'message' => "Your ordering privileges have been suspended for {$banDurationDays} days until " . $until->format('M d, Y h:i A') . ". Reason: Order #{$order->order_number} cancellation ({$cancellationReason}).",
                        'link' => route('customer.orders.index'),
                        'is_read' => false,
                    ]);

                    AuditLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'CUSTOMER_RESTRICTED',
                        'details' => "Restricted customer {$order->customer->name} for {$banDurationDays} days due to cancellation of Order #{$order->order_number}",
                        'ip_address' => $request->ip(),
                    ]);
                }
            }

            // If updated to Delivered, record in sales
            if ($newStatus === 'Delivered') {
                $saleExists = Sale::where('order_id', $order->id)->exists();
                if (!$saleExists) {
                    Sale::create([
                        'order_id' => $order->id,
                        'total_amount' => $order->total_amount,
                        'sales_date' => now()->toDateString(),
                    ]);
                }

                // If it has an active delivery record, update delivery status as well
                $delivery = Delivery::where('order_id', $order->id)->first();
                if ($delivery) {
                    $delivery->update([
                        'delivery_status' => 'Delivered',
                        'delivered_at' => now(),
                    ]);
                }
            }

            // Create notification for customer
            $customerMsg = ($newStatus === 'Cancelled')
                ? "Your order {$order->order_number} has been cancelled by store administration. Reason: {$cancellationReason}"
                : "Your order {$order->order_number} status has been updated to '{$newStatus}'.";

            AppNotification::create([
                'user_id' => $order->customer_id,
                'title' => "Order {$newStatus}",
                'message' => $customerMsg,
                'link' => route('customer.orders.show', $order->id),
                'is_read' => false,
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'ORDER_STATUS_CHANGED',
                'details' => "Updated order {$order->order_number} status from {$oldStatus} to {$newStatus}" . ($newStatus === 'Cancelled' ? ". Reason: {$cancellationReason}" : ""),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('admin.orders.show', $order->id)->with('success', "Order status successfully updated to {$newStatus}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Status update failed: ' . $e->getMessage());
        }
    }

    public function assignRider(Request $request, Order $order)
    {
        $request->validate([
            'rider_id' => 'required|exists:users,id',
        ]);

        $rider = User::findOrFail($request->rider_id);

        if ($rider->role !== 'rider') {
            return redirect()->back()->with('error', 'Selected user is not registered as a Rider.');
        }

        try {
            DB::beginTransaction();

            $order->update([
                'rider_id' => $rider->id,
                'order_status' => 'Out for Delivery', // Auto progress order status to Out for Delivery
            ]);

            // Create or update delivery record
            Delivery::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'rider_id' => $rider->id,
                    'delivery_status' => 'Out for Delivery',
                ]
            );

            // Notify Rider
            AppNotification::create([
                'user_id' => $rider->id,
                'title' => 'New Delivery Assigned',
                'message' => "You have been assigned to deliver order {$order->order_number} to {$order->delivery_address}.",
                'link' => route('rider.orders.show', $order->id),
                'is_read' => false,
            ]);

            // Notify Customer
            AppNotification::create([
                'user_id' => $order->customer_id,
                'title' => 'Order Out for Delivery',
                'message' => "Your order {$order->order_number} is out for delivery. Assigned rider: {$rider->name} ({$rider->phone}).",
                'link' => route('customer.orders.show', $order->id),
                'is_read' => false,
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'ORDER_RIDER_ASSIGNED',
                'details' => "Assigned Rider {$rider->name} to Order {$order->order_number}",
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('admin.orders.show', $order->id)->with('success', "Rider {$rider->name} has been assigned and order status set to Out for Delivery.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Rider assignment failed: ' . $e->getMessage());
        }
    }
}
