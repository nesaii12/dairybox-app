<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * Display the delivery monitoring center for administrators.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'all');
        $search = $request->query('search');

        $query = Delivery::with(['order.customer', 'rider', 'order.orderItems.product'])
            ->latest('updated_at');

        // Apply status tabs
        if ($statusFilter === 'active') {
            $query->where('delivery_status', 'Out for Delivery');
        } elseif ($statusFilter === 'arrived') {
            $query->where('delivery_status', 'Out for Delivery')->whereNotNull('arrived_at');
        } elseif ($statusFilter === 'delivered') {
            $query->where('delivery_status', 'Delivered');
        } elseif ($statusFilter === 'incidents') {
            $query->where('cancelled_after_arrival', true);
        } elseif ($statusFilter === 'cancelled') {
            $query->whereIn('delivery_status', ['Cancelled', 'Failed Delivery']);
        }

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('order', function ($oq) use ($search) {
                    $oq->where('order_number', 'like', "%{$search}%")
                       ->orWhere('delivery_address', 'like', "%{$search}%")
                       ->orWhereHas('customer', function ($cq) use ($search) {
                           $cq->where('name', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%");
                       });
                })->orWhereHas('rider', function ($rq) use ($search) {
                    $rq->where('name', 'like', "%{$search}%");
                });
            });
        }

        $deliveries = $query->paginate(15)->withQueryString();

        // Calculate overview counts for delivery monitoring
        $activeCount = Delivery::where('delivery_status', 'Out for Delivery')->count();
        $arrivedCount = Delivery::where('delivery_status', 'Out for Delivery')->whereNotNull('arrived_at')->count();
        $deliveredTodayCount = Delivery::where('delivery_status', 'Delivered')->whereDate('delivered_at', today())->count();
        $incidentsCount = Delivery::where('cancelled_after_arrival', true)->count();
        $unverifiedIncidentsCount = Delivery::where('cancelled_after_arrival', true)->whereNull('admin_verified_at')->count();

        return view('admin.deliveries.index', compact(
            'deliveries',
            'statusFilter',
            'search',
            'activeCount',
            'arrivedCount',
            'deliveredTodayCount',
            'incidentsCount',
            'unverifiedIncidentsCount'
        ));
    }

    /**
     * Verify a cancellation after rider arrival and restrict the customer for 3 days.
     */
    public function verifyAndRestrict(Request $request, Delivery $delivery)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $order = $delivery->order;
        $customer = $order->customer;

        $banDurationDays = 3;
        $until = now()->addDays($banDurationDays);
        $reason = $request->input('reason') ?: "Cancelled Order #{$order->order_number} after rider arrived at delivery destination.";

        try {
            DB::beginTransaction();

            // Mark delivery as verified by admin
            $delivery->update([
                'cancelled_after_arrival' => true,
                'admin_verified_at' => now(),
            ]);

            // Restrict customer for 3 days
            $customer->update([
                'status' => 'restricted',
                'restricted_until' => $until,
            ]);

            // Notify customer of restriction
            AppNotification::create([
                'user_id' => $customer->id,
                'title' => 'Account Temporarily Restricted (3 Days)',
                'message' => "Your ordering privileges have been suspended for 3 days until " . $until->format('M d, Y h:i A') . " due to cancellation of Order #{$order->order_number} after rider arrival.",
                'link' => route('customer.orders.index'),
                'is_read' => false,
            ]);

            // Log audit
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'CUSTOMER_RESTRICTED_POST_ARRIVAL_CANCELLATION',
                'details' => "Admin verified Order #{$order->order_number} was cancelled after rider arrival. Restricted customer {$customer->name} (#{$customer->id}) for {$banDurationDays} days until " . $until->format('M d, Y h:i A'),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', "Customer {$customer->name} verified and restricted from ordering for 3 days (until " . $until->format('M d, Y h:i A') . ").");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to restrict customer: ' . $e->getMessage());
        }
    }

    /**
     * Admin manually cancels a delivery / order with arrival tracking.
     */
    public function cancelDelivery(Request $request, Delivery $delivery)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'cancelled_after_arrival' => 'nullable|boolean',
        ]);

        $order = $delivery->order;
        $cancelledAfterArrival = $request->boolean('cancelled_after_arrival') || ($delivery->arrived_at !== null);
        $reason = $request->reason;

        try {
            DB::beginTransaction();

            $delivery->update([
                'delivery_status' => 'Cancelled',
                'cancelled_after_arrival' => $cancelledAfterArrival,
                'cancellation_reason' => $reason,
            ]);

            $order->update([
                'order_status' => 'Cancelled',
                'cancelled_after_arrival' => $cancelledAfterArrival,
                'cancellation_reason' => $reason,
            ]);

            // Return FIFO inventory stock
            foreach ($order->orderItems as $item) {
                InventoryService::addStock(
                    $item->product_id,
                    $item->quantity,
                    $item->product->expiration_date,
                    "Admin Cancelled Delivery #{$order->order_number}"
                );
            }

            // Notify customer
            AppNotification::create([
                'user_id' => $order->customer_id,
                'title' => 'Delivery Cancelled',
                'message' => "Your delivery for Order #{$order->order_number} was cancelled by administrator. Reason: {$reason}",
                'link' => route('customer.orders.show', $order->id),
                'is_read' => false,
            ]);

            // Notify rider if assigned
            if ($delivery->rider_id) {
                AppNotification::create([
                    'user_id' => $delivery->rider_id,
                    'title' => 'Delivery Assignment Cancelled',
                    'message' => "Order #{$order->order_number} has been cancelled by administrator. Return package to store inventory.",
                    'link' => route('rider.dashboard'),
                    'is_read' => false,
                ]);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'ADMIN_CANCELLED_DELIVERY',
                'details' => "Admin cancelled delivery for Order #{$order->order_number}. Post-arrival: " . ($cancelledAfterArrival ? 'YES' : 'NO') . ". Reason: {$reason}",
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', "Delivery #{$order->order_number} cancelled and inventory restocked.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Cancellation failed: ' . $e->getMessage());
        }
    }
}
