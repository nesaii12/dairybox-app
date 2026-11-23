<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ReservationService;
use App\Services\SettingService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * Display the Reservation Management dashboard & history table.
     */
    public function index(Request $request)
    {
        $query = Reservation::with(['product.category', 'customer', 'order.delivery', 'rider']);

        // Search filter (Reservation ID, Customer Name, Contact Number, Product Name, Notes)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reservation_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('product_name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status') && in_array($request->status, Reservation::STATUSES, true)) {
            $query->where('status', $request->status);
        }

        // Pickup Date exact filter
        if ($request->filled('pickup_date')) {
            $query->whereDate('pickup_date', $request->pickup_date);
        }

        // Optional Pickup Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('pickup_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('pickup_date', '<=', $request->date_to);
        }

        $perPage = (int) $request->input('per_page', session('per_page_reservations', 10));
        if (!in_array($perPage, [5, 10, 20, 50, 100], true)) {
            $perPage = 10;
        }
        session(['per_page_reservations' => $perPage]);

        $reservations = $query
            ->orderByRaw("CASE
                WHEN status = 'Out for Delivery' THEN 1
                WHEN status = 'Ready for Pickup' THEN 2
                WHEN status = 'Confirmed' THEN 3
                WHEN status = 'Pending' THEN 4
                WHEN status = 'Completed' THEN 5
                ELSE 6
            END ASC")
            ->orderBy('pickup_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        // Real-time KPI summary metrics from live database
        $activeReservedUnits = (int) Reservation::whereIn('status', Reservation::ALLOCATED_STATUSES)
            ->sum('allocated_quantity');
        $pendingCount = (int) Reservation::where('status', Reservation::STATUS_PENDING)->count();
        $confirmedCount = (int) Reservation::where('status', Reservation::STATUS_CONFIRMED)->count();
        $readyCount = (int) Reservation::where('status', Reservation::STATUS_READY)->count();
        $outstandingBalance = (float) Reservation::whereIn('status', [
            Reservation::STATUS_PENDING,
            Reservation::STATUS_CONFIRMED,
            Reservation::STATUS_READY,
            Reservation::STATUS_OUT_FOR_DELIVERY,
        ])->sum('remaining_balance');

        $cancellationPolicy = SettingService::getReservationCancellationPolicy();
        $activeRiders = User::where('role', 'rider')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('admin.reservations.index', compact(
            'reservations',
            'activeReservedUnits',
            'pendingCount',
            'confirmedCount',
            'readyCount',
            'outstandingBalance',
            'cancellationPolicy',
            'activeRiders'
        ));
    }

    /**
     * Show the form for creating a new reservation.
     */
    public function create()
    {
        $nextReservationCode = Reservation::generateReservationCode();
        $products = $this->formatProductsForForm();
        $customers = User::where('role', 'customer')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return view('admin.reservations.create', compact('nextReservationCode', 'products', 'customers'));
    }

    /**
     * Store a newly created reservation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reservation_code' => 'nullable|string|max:32',
            'customer_id' => 'nullable|exists:users,id',
            'customer_name' => 'required|string|max:150',
            'contact_number' => 'required|string|max:50',
            'product_id' => 'required|exists:products,id',
            'size' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:15',
            'unit_price' => 'nullable|numeric|min:0',
            'reservation_date' => 'required|date',
            'pickup_date' => 'required|date|after_or_equal:reservation_date',
            'down_payment' => 'required|numeric|gt:0',
            'payment_status' => 'nullable|in:Partially Paid,Fully Paid',
            'status' => 'required|in:Pending,Confirmed,Ready for Pickup',
            'notes' => 'nullable|string|max:1000',
        ], [
            'quantity.min' => 'Minimum reservation is 15 pieces of products.',
            'pickup_date.after_or_equal' => 'Scheduled pickup date must be on or after the reservation date.',
            'down_payment.required' => 'A down payment greater than ₱0.00 is required to create a reservation.',
            'down_payment.gt' => 'Down payment must be greater than ₱0.00.',
            'payment_status.in' => 'Unpaid reservations are not allowed. Please select Partially Paid or Fully Paid.',
        ]);

        try {
            $reservation = ReservationService::create($validated, auth()->id(), $request->ip());

            return redirect()
                ->route('admin.reservations.show', $reservation->id)
                ->with('success', "Reservation {$reservation->reservation_code} created successfully ({$reservation->status}).");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified reservation details.
     */
    public function show(Reservation $reservation)
    {
        $reservation->load([
            'product.category',
            'product.inventories' => fn ($q) => $q->where('remaining_stock', '>', 0)->orderBy('expiration_date', 'asc'),
            'customer',
            'order.sale',
            'order.delivery.rider',
            'creator',
            'rider',
            'refundConfirmer',
        ]);

        $cancellationPolicy = SettingService::getReservationCancellationPolicy();
        $activeRiders = User::where('role', 'rider')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('admin.reservations.show', compact('reservation', 'cancellationPolicy', 'activeRiders'));
    }

    /**
     * Show the form for editing an active reservation.
     */
    public function edit(Reservation $reservation)
    {
        if (!$reservation->canEdit()) {
            return redirect()
                ->route('admin.reservations.show', $reservation->id)
                ->with('error', "Reservation {$reservation->reservation_code} is {$reservation->status} and can no longer be edited.");
        }

        $reservation->load('product');
        $products = $this->formatProductsForForm($reservation->product_id, $reservation->id);
        $customers = User::where('role', 'customer')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);
        $cancellationPolicy = SettingService::getReservationCancellationPolicy();

        return view('admin.reservations.edit', compact('reservation', 'products', 'customers', 'cancellationPolicy'));
    }

    /**
     * Update an active reservation.
     */
    public function update(Request $request, Reservation $reservation)
    {
        $isCancelling = $request->input('status') === Reservation::STATUS_CANCELLED;

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:users,id',
            'customer_name' => 'required|string|max:150',
            'contact_number' => 'required|string|max:50',
            'product_id' => 'required|exists:products,id',
            'size' => 'nullable|string|max:20',
            'quantity' => 'required|integer|min:15',
            'unit_price' => 'nullable|numeric|min:0',
            'reservation_date' => 'required|date',
            'pickup_date' => 'required|date|after_or_equal:reservation_date',
            'down_payment' => $isCancelling ? 'nullable|numeric|min:0' : 'required|numeric|gt:0',
            'payment_status' => $isCancelling ? 'nullable|in:Unpaid,Partially Paid,Fully Paid' : 'nullable|in:Partially Paid,Fully Paid',
            'status' => 'required|in:Pending,Confirmed,Ready for Pickup,Completed,Cancelled',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|in:cash,gcash,card,other',
            'cancellation_reason' => 'nullable|string|max:255',
            'cancellation_initiated_by' => 'nullable|in:customer,business',
        ], [
            'quantity.min' => 'Minimum reservation is 15 pieces of products.',
            'pickup_date.after_or_equal' => 'Scheduled pickup date must be on or after the reservation date.',
            'down_payment.required' => 'A down payment greater than ₱0.00 is required to save a reservation.',
            'down_payment.gt' => 'Down payment must be greater than ₱0.00.',
            'payment_status.in' => 'Unpaid reservations are not allowed. Please select Partially Paid or Fully Paid.',
        ]);

        try {
            $updated = ReservationService::update($reservation, $validated, auth()->id(), $request->ip());

            return redirect()
                ->route('admin.reservations.show', $updated->id)
                ->with('success', "Reservation {$updated->reservation_code} updated successfully.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Confirm a Pending reservation and allocate Reserved Stock.
     */
    public function confirm(Request $request, Reservation $reservation)
    {
        try {
            $confirmed = ReservationService::confirm($reservation, auth()->id(), $request->ip());

            return redirect()
                ->back()
                ->with('success', "Reservation {$confirmed->reservation_code} confirmed. {$confirmed->allocated_quantity} piece(s) allocated to Reserved Stock.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark a Confirmed reservation as Ready for Pickup without duplicating stock allocation.
     */
    public function markReady(Request $request, Reservation $reservation)
    {
        try {
            $ready = ReservationService::markReadyForPickup($reservation, auth()->id(), $request->ip());

            return redirect()
                ->back()
                ->with('success', "Reservation {$ready->reservation_code} is now marked Ready for Pickup.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark a reservation Completed: deduct Physical Stock, release Reserved Stock, and log fulfilled Sale.
     */
    public function complete(Request $request, Reservation $reservation)
    {
        $request->validate([
            'payment_method' => 'nullable|in:cash,gcash,card,other',
        ]);

        try {
            $completed = ReservationService::complete(
                $reservation,
                auth()->id(),
                $request->ip(),
                $request->input('payment_method', 'cash')
            );

            return redirect()
                ->back()
                ->with('success', "Reservation {$completed->reservation_code} completed! Physical stock deducted, reserved stock released, and ₱" . number_format((float) $completed->total_amount, 2) . " logged in sales reports.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Convert an unclaimed Confirmed or Ready for Pickup reservation to Rider Delivery on its scheduled pickup date.
     */
    public function convertToDelivery(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'delivery_address' => 'required|string|max:250',
            'rider_id' => 'required|exists:users,id',
        ], [
            'delivery_address.required' => 'Please enter a delivery destination address.',
            'rider_id.required' => 'Please assign an active delivery rider.',
        ]);

        try {
            $dispatched = ReservationService::convertToDelivery(
                $reservation,
                $validated['delivery_address'],
                (int) $validated['rider_id'],
                auth()->id(),
                $request->ip()
            );

            $codText = (float) $dispatched->remaining_balance > 0
                ? 'Pending Balance to Collect (COD): ₱' . number_format((float) $dispatched->remaining_balance, 2)
                : 'Fully Paid (₱0.00 to Collect)';

            return redirect()
                ->route('admin.reservations.show', $dispatched->id)
                ->with('success', "Reservation {$dispatched->reservation_code} converted to Rider Delivery and dispatched to {$dispatched->rider?->name}. {$codText}.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a reservation, release Reserved Stock back to Available Stock, and record cancellation snapshot.
     */
    public function cancel(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:255',
            'cancellation_initiated_by' => 'nullable|in:customer,business',
        ]);

        try {
            $initiatedBy = $validated['cancellation_initiated_by'] ?? Reservation::CANCELLED_BY_CUSTOMER;

            $cancelled = ReservationService::cancel(
                $reservation,
                $validated['cancellation_reason'] ?? null,
                auth()->id(),
                $request->ip(),
                $initiatedBy
            );

            $feeSummary = 'Fee: ₱' . number_format((float) $cancelled->cancellation_fee_amount, 2)
                . ' (' . rtrim(rtrim(number_format((float) $cancelled->applied_fee_percentage, 2), '0'), '.') . '%)'
                . ', Refund: ₱' . number_format((float) $cancelled->refund_amount, 2)
                . ' (' . $cancelled->refund_status . ')';

            return redirect()
                ->back()
                ->with('success', "Reservation {$cancelled->reservation_code} cancelled and reserved stock released back to available inventory. {$feeSummary}.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Explicitly confirm physical/digital refund handover for a cancelled reservation.
     */
    public function confirmRefund(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'refund_method' => 'required|in:Cash,GCash,Bank Transfer,Other',
            'refund_reference' => 'nullable|string|max:100',
        ]);

        try {
            $confirmed = ReservationService::confirmRefund(
                $reservation,
                $validated['refund_method'],
                $validated['refund_reference'] ?? null,
                auth()->id(),
                $request->ip()
            );

            return redirect()
                ->back()
                ->with('success', "Refund handover of ₱" . number_format((float) $confirmed->refund_amount, 2) . " for {$confirmed->reservation_code} confirmed via {$confirmed->refund_method} and logged in the cash ledger.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Prepare product dataset for Reservation create/edit forms with real non-expired stock & batch expiry metadata.
     */
    protected function formatProductsForForm(?int $includeProductId = null, ?int $excludeReservationId = null)
    {
        $today = now()->toDateString();

        return Product::with([
            'category',
            'inventories' => fn ($q) => $q->where('remaining_stock', '>', 0)->orderBy('expiration_date', 'asc'),
            'reservations' => fn ($q) => $q->whereIn('status', Reservation::ALLOCATED_STATUSES)
                ->when($excludeReservationId, fn ($rq) => $rq->where('id', '!=', $excludeReservationId)),
        ])
            ->where(function ($q) use ($includeProductId) {
                $q->where('status', 'active');
                if ($includeProductId) {
                    $q->orWhere('id', $includeProductId);
                }
            })
            ->orderBy('product_name')
            ->get()
            ->map(function ($product) use ($today) {
                $sizesWithPrices = [];
                foreach ($product->available_sizes as $size) {
                    $sizesWithPrices[] = [
                        'size' => $size,
                        'price' => (float) $product->getPriceForSize($size),
                        'formatted_price' => '₱' . number_format($product->getPriceForSize($size), 2),
                    ];
                }

                // Only include non-expired active batches (expiration_date >= today)
                $validBatches = $product->inventories
                    ->filter(fn ($b) => $b->expiration_date && $b->expiration_date->toDateString() >= $today)
                    ->values();

                $nonExpiredStock = (int) $validBatches->sum('remaining_stock');
                if ($product->inventories->isEmpty() && $product->expiration_date && $product->expiration_date->toDateString() >= $today) {
                    $nonExpiredStock = (int) $product->stock;
                }

                $availableStock = InventoryService::getAvailableStock($product);
                $reservableToday = max(0, min($availableStock, $nonExpiredStock));

                $latestValidExpiry = $validBatches
                    ->sortByDesc('expiration_date')
                    ->first()?->expiration_date?->toDateString()
                    ?? ($product->expiration_date && $product->expiration_date->toDateString() >= $today
                        ? $product->expiration_date->toDateString()
                        : null);

                $nearestValidExpiry = $validBatches
                    ->sortBy('expiration_date')
                    ->first()?->expiration_date?->toDateString()
                    ?? $latestValidExpiry;

                $batchesPayload = $validBatches->map(fn ($b) => [
                    'remaining_stock' => (int) $b->remaining_stock,
                    'expiration_date' => $b->expiration_date->toDateString(),
                ])->values()->all();

                if (empty($batchesPayload) && $nonExpiredStock > 0 && $latestValidExpiry) {
                    $batchesPayload = [[
                        'remaining_stock' => $nonExpiredStock,
                        'expiration_date' => $latestValidExpiry,
                    ]];
                }

                $activeAllocations = $product->reservations->map(fn ($r) => [
                    'pickup_date' => $r->pickup_date->toDateString(),
                    'allocated_quantity' => (int) $r->allocated_quantity,
                ])->values()->all();

                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'category_name' => $product->category?->category_name ?? '',
                    'milk_type' => $product->milk_type ?? 'Cow',
                    'size' => $product->size ?? 'Medium',
                    'base_price' => (float) $product->price,
                    'physical_stock' => (int) $product->stock,
                    'reserved_stock' => (int) ($product->reserved_stock ?? 0),
                    'available_stock' => $availableStock,
                    'non_expired_stock' => $nonExpiredStock,
                    'reservable_stock' => $reservableToday,
                    'is_expired' => $nonExpiredStock <= 0,
                    'nearest_expiry' => $nearestValidExpiry,
                    'latest_expiry' => $latestValidExpiry,
                    'valid_batches' => $batchesPayload,
                    'active_allocations' => $activeAllocations,
                    'sizes' => $sizesWithPrices,
                ];
            })
            ->values();
    }
}
