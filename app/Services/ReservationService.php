<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\AuditLog;
use App\Models\CashLedgerEntry;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReservationService
{
    /**
     * Create a new reservation.
     * - Pending: does NOT allocate stock.
     * - Confirmed / Ready for Pickup: validates Available Stock + batch expiry >= pickup date and allocates Reserved Stock under lockForUpdate().
     */
    public static function create(array $data, ?int $adminId = null, ?string $ipAddress = null): Reservation
    {
        return DB::transaction(function () use ($data, $adminId, $ipAddress) {
            $product = Product::where('id', (int) $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $size = !empty($data['size']) ? trim((string) $data['size']) : ($product->size ?? 'Medium');
            $quantity = (int) $data['quantity'];
            if ($quantity < Reservation::MIN_QUANTITY) {
                throw new \Exception('Minimum reservation is 15 pieces of products.');
            }
            $unitPrice = isset($data['unit_price']) && $data['unit_price'] !== ''
                ? round((float) $data['unit_price'], 2)
                : round((float) $product->getPriceForSize($size), 2);
            $totalAmount = round($quantity * $unitPrice, 2);

            [$downPayment, $remainingBalance, $paymentStatus] = self::resolvePaymentFields(
                $totalAmount,
                (float) ($data['down_payment'] ?? 0),
                $data['payment_status'] ?? null
            );

            $status = $data['status'] ?? Reservation::STATUS_PENDING;
            if (!in_array($status, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED, Reservation::STATUS_READY], true)) {
                $status = Reservation::STATUS_PENDING;
            }

            $reservationDate = Carbon::parse($data['reservation_date'] ?? now()->toDateString())->toDateString();
            $pickupDate = Carbon::parse($data['pickup_date'])->toDateString();

            // Strict Guardrail: Always block expired products and batches expiring before pickup date
            // (even for Pending reservations) before saving.
            self::validateReservableEligibility($product, $quantity, $pickupDate);

            $allocatedQty = 0;
            $confirmedAt = null;
            $readyAt = null;

            if (in_array($status, Reservation::ALLOCATED_STATUSES, true)) {
                self::validateAndAllocateStock($product, $quantity, $pickupDate);
                $allocatedQty = $quantity;
                $confirmedAt = now();
                if ($status === Reservation::STATUS_READY) {
                    $readyAt = now();
                }
            }

            $reservationCode = !empty($data['reservation_code'])
                ? trim((string) $data['reservation_code'])
                : Reservation::generateReservationCode();

            if (Reservation::where('reservation_code', $reservationCode)->exists()) {
                $reservationCode = Reservation::generateReservationCode();
            }

            $reservation = Reservation::create([
                'reservation_code' => $reservationCode,
                'customer_id' => !empty($data['customer_id']) ? (int) $data['customer_id'] : null,
                'customer_name' => trim((string) $data['customer_name']),
                'contact_number' => trim((string) $data['contact_number']),
                'product_id' => $product->id,
                'size' => $size,
                'quantity' => $quantity,
                'allocated_quantity' => $allocatedQty,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'down_payment' => $downPayment,
                'remaining_balance' => $remainingBalance,
                'payment_status' => $paymentStatus,
                'status' => $status,
                'reservation_date' => $reservationDate,
                'pickup_date' => $pickupDate,
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
                'created_by' => $adminId,
                'confirmed_at' => $confirmedAt,
                'ready_at' => $readyAt,
            ]);

            if ($adminId) {
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'RESERVATION_CREATED',
                    'details' => "Created reservation {$reservation->reservation_code} for {$reservation->customer_name}: {$quantity}x {$product->product_name} ({$size}), Pickup: {$pickupDate}, Status: {$status}, Total: ₱" . number_format($totalAmount, 2) . ", Down Payment: ₱" . number_format($downPayment, 2),
                    'ip_address' => $ipAddress,
                ]);
            }

            return $reservation->fresh(['product', 'customer', 'order']);
        });
    }

    /**
     * Edit an active (non-Completed, non-Cancelled) reservation.
     * Safely adjusts Reserved Stock under row locks if product, quantity, pickup date, or status changes.
     */
    public static function update(Reservation $reservation, array $data, ?int $adminId = null, ?string $ipAddress = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $data, $adminId, $ipAddress) {
            $lockedReservation = Reservation::where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedReservation->canEdit()) {
                throw new \Exception("Reservation {$lockedReservation->reservation_code} is already {$lockedReservation->status} and cannot be edited.");
            }

            $oldProductId = (int) $lockedReservation->product_id;
            $newProductId = (int) ($data['product_id'] ?? $oldProductId);

            // Lock products in deterministic ID order to prevent deadlocks
            $productIds = array_values(array_unique([$oldProductId, $newProductId]));
            sort($productIds);
            $lockedProducts = Product::whereIn('id', $productIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $oldProduct = $lockedProducts->get($oldProductId);
            $newProduct = $lockedProducts->get($newProductId);

            if (!$newProduct) {
                throw new \Exception('Selected product was not found.');
            }

            // Temporarily release existing allocation from old product before re-validating
            $releasedQty = 0;
            if ($oldProduct && $lockedReservation->allocated_quantity > 0) {
                $releasedQty = (int) $lockedReservation->allocated_quantity;
                $oldProduct->reserved_stock = max(0, (int) $oldProduct->reserved_stock - $releasedQty);
                $oldProduct->save();
                $lockedReservation->allocated_quantity = 0;
            }

            $size = !empty($data['size']) ? trim((string) $data['size']) : ($lockedReservation->size ?? $newProduct->size ?? 'Medium');
            $quantity = (int) ($data['quantity'] ?? $lockedReservation->quantity);
            $targetStatus = $data['status'] ?? $lockedReservation->status;
            if ($targetStatus !== Reservation::STATUS_CANCELLED && $quantity < Reservation::MIN_QUANTITY) {
                throw new \Exception('Minimum reservation is 15 pieces of products.');
            }
            $unitPrice = isset($data['unit_price']) && $data['unit_price'] !== ''
                ? round((float) $data['unit_price'], 2)
                : round((float) $newProduct->getPriceForSize($size), 2);
            $totalAmount = round($quantity * $unitPrice, 2);

            $reservationDate = Carbon::parse($data['reservation_date'] ?? $lockedReservation->reservation_date->toDateString())->toDateString();
            $pickupDate = Carbon::parse($data['pickup_date'] ?? $lockedReservation->pickup_date->toDateString())->toDateString();

            if ($targetStatus === Reservation::STATUS_CANCELLED) {
                $downPayment = round(max(0, (float) ($data['down_payment'] ?? $lockedReservation->down_payment)), 2);
                $remainingBalance = round(max(0, $totalAmount - $downPayment), 2);
                $paymentStatus = Reservation::computePaymentStatus($totalAmount, $downPayment);
            } else {
                [$downPayment, $remainingBalance, $paymentStatus] = self::resolvePaymentFields(
                    $totalAmount,
                    (float) ($data['down_payment'] ?? $lockedReservation->down_payment),
                    $data['payment_status'] ?? null
                );
            }

            // Update core attributes first
            $lockedReservation->customer_id = array_key_exists('customer_id', $data)
                ? (!empty($data['customer_id']) ? (int) $data['customer_id'] : null)
                : $lockedReservation->customer_id;
            $lockedReservation->customer_name = trim((string) ($data['customer_name'] ?? $lockedReservation->customer_name));
            $lockedReservation->contact_number = trim((string) ($data['contact_number'] ?? $lockedReservation->contact_number));
            $lockedReservation->product_id = $newProduct->id;
            $lockedReservation->size = $size;
            $lockedReservation->quantity = $quantity;
            $lockedReservation->unit_price = $unitPrice;
            $lockedReservation->total_amount = $totalAmount;
            $lockedReservation->down_payment = $downPayment;
            $lockedReservation->remaining_balance = $remainingBalance;
            $lockedReservation->payment_status = $paymentStatus;
            $lockedReservation->reservation_date = $reservationDate;
            $lockedReservation->pickup_date = $pickupDate;
            $lockedReservation->notes = array_key_exists('notes', $data)
                ? (isset($data['notes']) ? trim((string) $data['notes']) : null)
                : $lockedReservation->notes;

            if ($targetStatus === Reservation::STATUS_PENDING) {
                // Still block expired products and batches expiring before pickup date on Pending edits
                self::validateReservableEligibility($newProduct, $quantity, $pickupDate, $lockedReservation->id);
                $lockedReservation->status = Reservation::STATUS_PENDING;
                $lockedReservation->allocated_quantity = 0;
                $lockedReservation->save();
            } elseif (in_array($targetStatus, Reservation::ALLOCATED_STATUSES, true)) {
                // Re-validate and allocate once (prevents duplicate allocation)
                self::validateAndAllocateStock($newProduct, $quantity, $pickupDate, $lockedReservation->id);
                $lockedReservation->status = $targetStatus;
                $lockedReservation->allocated_quantity = $quantity;
                if (!$lockedReservation->confirmed_at) {
                    $lockedReservation->confirmed_at = now();
                }
                if ($targetStatus === Reservation::STATUS_READY && !$lockedReservation->ready_at) {
                    $lockedReservation->ready_at = now();
                }
                $lockedReservation->save();
            } elseif ($targetStatus === Reservation::STATUS_COMPLETED) {
                // Validate stock & complete directly in the same transaction
                self::validateAndAllocateStock($newProduct, $quantity, $pickupDate, $lockedReservation->id);
                $lockedReservation->status = Reservation::STATUS_CONFIRMED;
                $lockedReservation->allocated_quantity = $quantity;
                if (!$lockedReservation->confirmed_at) {
                    $lockedReservation->confirmed_at = now();
                }
                $lockedReservation->save();

                return self::completeLockedReservation(
                    $lockedReservation,
                    $newProduct,
                    $adminId,
                    $ipAddress,
                    $data['payment_method'] ?? 'cash'
                );
            } elseif ($targetStatus === Reservation::STATUS_CANCELLED) {
                $cleanReason = !empty($data['cancellation_reason'])
                    ? trim((string) $data['cancellation_reason'])
                    : 'Cancelled by administrator.';
                $initiatedBy = $data['cancellation_initiated_by'] ?? Reservation::CANCELLED_BY_CUSTOMER;

                self::applyCancellationSnapshot(
                    $lockedReservation,
                    $cleanReason,
                    $initiatedBy,
                    $adminId,
                    $ipAddress,
                    $releasedQty,
                    $oldProduct ?? $newProduct
                );

                return $lockedReservation->fresh(['product', 'customer', 'order', 'refundConfirmer']);
            } else {
                throw new \Exception("Invalid reservation status: {$targetStatus}");
            }

            if ($adminId) {
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'RESERVATION_UPDATED',
                    'details' => "Updated reservation {$lockedReservation->reservation_code} ({$lockedReservation->customer_name}): {$quantity}x {$newProduct->product_name}, Pickup: {$pickupDate}, Status: {$lockedReservation->status}, Balance: ₱" . number_format($remainingBalance, 2),
                    'ip_address' => $ipAddress,
                ]);
            }

            return $lockedReservation->fresh(['product', 'customer', 'order']);
        });
    }

    /**
     * Confirm a Pending reservation -> allocates Reserved Stock (does NOT deduct Physical Stock).
     */
    public static function confirm(Reservation $reservation, ?int $adminId = null, ?string $ipAddress = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $adminId, $ipAddress) {
            $lockedReservation = Reservation::where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReservation->status === Reservation::STATUS_CONFIRMED) {
                // Prevent duplicate allocation if already confirmed
                return $lockedReservation->fresh(['product', 'customer', 'order']);
            }

            if ($lockedReservation->status !== Reservation::STATUS_PENDING) {
                throw new \Exception("Only Pending reservations can be confirmed. Current status: {$lockedReservation->status}.");
            }

            $product = Product::where('id', $lockedReservation->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReservation->allocated_quantity === 0) {
                self::validateAndAllocateStock(
                    $product,
                    (int) $lockedReservation->quantity,
                    $lockedReservation->pickup_date->toDateString(),
                    $lockedReservation->id
                );
                $lockedReservation->allocated_quantity = (int) $lockedReservation->quantity;
            }

            $lockedReservation->status = Reservation::STATUS_CONFIRMED;
            $lockedReservation->confirmed_at = now();
            $lockedReservation->save();

            if ($adminId) {
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'RESERVATION_CONFIRMED',
                    'details' => "Confirmed reservation {$lockedReservation->reservation_code}: allocated {$lockedReservation->allocated_quantity}x {$product->product_name} to Reserved Stock (Physical: {$product->stock}, Reserved: {$product->reserved_stock}, Available: {$product->available_stock}).",
                    'ip_address' => $ipAddress,
                ]);
            }

            return $lockedReservation->fresh(['product', 'customer', 'order']);
        });
    }

    /**
     * Mark reservation Ready for Pickup -> preserves existing Reserved Stock allocation without duplicate allocation.
     */
    public static function markReadyForPickup(Reservation $reservation, ?int $adminId = null, ?string $ipAddress = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $adminId, $ipAddress) {
            $lockedReservation = Reservation::where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReservation->status === Reservation::STATUS_READY) {
                return $lockedReservation->fresh(['product', 'customer', 'order']);
            }

            if (!in_array($lockedReservation->status, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
                throw new \Exception("Cannot mark reservation {$lockedReservation->reservation_code} as Ready for Pickup from status '{$lockedReservation->status}'.");
            }

            $product = Product::where('id', $lockedReservation->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Allocate ONLY if not already allocated (prevents duplicate allocation on Confirmed -> Ready for Pickup)
            if ($lockedReservation->allocated_quantity === 0) {
                self::validateAndAllocateStock(
                    $product,
                    (int) $lockedReservation->quantity,
                    $lockedReservation->pickup_date->toDateString(),
                    $lockedReservation->id
                );
                $lockedReservation->allocated_quantity = (int) $lockedReservation->quantity;
                if (!$lockedReservation->confirmed_at) {
                    $lockedReservation->confirmed_at = now();
                }
            }

            $lockedReservation->status = Reservation::STATUS_READY;
            $lockedReservation->ready_at = now();
            $lockedReservation->save();

            if ($adminId) {
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'RESERVATION_READY',
                    'details' => "Marked reservation {$lockedReservation->reservation_code} as Ready for Pickup ({$lockedReservation->quantity}x {$product->product_name}). Reserved Stock remains {$product->reserved_stock}.",
                    'ip_address' => $ipAddress,
                ]);
            }

            return $lockedReservation->fresh(['product', 'customer', 'order']);
        });
    }

    /**
     * Complete a Confirmed or Ready for Pickup reservation:
     * - Releases Reserved Stock
     * - Deducts Physical Stock via FIFO
     * - Logs a single fulfilled Order + OrderItem + Sale into ledger/reports (no double-counting down payments)
     */
    public static function complete(Reservation $reservation, ?int $adminId = null, ?string $ipAddress = null, string $paymentMethod = 'cash'): Reservation
    {
        return DB::transaction(function () use ($reservation, $adminId, $ipAddress, $paymentMethod) {
            $lockedReservation = Reservation::where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedReservation->canComplete()) {
                throw new \Exception("Only Confirmed or Ready for Pickup reservations can be marked Completed. Current status: {$lockedReservation->status}.");
            }

            $product = Product::where('id', $lockedReservation->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            return self::completeLockedReservation($lockedReservation, $product, $adminId, $ipAddress, $paymentMethod);
        });
    }

    /**
     * Tiered Delivery Fee rule for converted reservations:
     * - Reserved Product Subtotal >= ₱3,000.00 -> ₱0.00 (Free Delivery)
     * - Reserved Product Subtotal <  ₱3,000.00 -> ₱25.00
     */
    public static function calculateReservationDeliveryFee(float $productSubtotal): float
    {
        return round($productSubtotal, 2) >= Reservation::FREE_DELIVERY_THRESHOLD
            ? 0.00
            : Reservation::STANDARD_DELIVERY_FEE;
    }

    /**
     * Compute delivery fee, updated total amount, and pending COD balance for a converted reservation.
     *
     * @return array{
     *     product_subtotal: float,
     *     delivery_fee: float,
     *     total_amount: float,
     *     down_payment_paid: float,
     *     pending_balance: float,
     *     is_fully_paid: bool
     * }
     */
    public static function calculateDeliveryConversionBreakdown(float $productSubtotal, float $downPayment): array
    {
        $subtotal = round(max(0.0, $productSubtotal), 2);
        $deliveryFee = self::calculateReservationDeliveryFee($subtotal);
        $totalAmount = round($subtotal + $deliveryFee, 2);
        $downPaymentPaid = round(max(0.0, $downPayment), 2);
        $pendingBalance = round(max(0.0, $totalAmount - $downPaymentPaid), 2);

        return [
            'product_subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'down_payment_paid' => $downPaymentPaid,
            'pending_balance' => $pendingBalance,
            'is_fully_paid' => $pendingBalance <= 0.00,
        ];
    }

    /**
     * Convert an unclaimed Confirmed or Ready for Pickup reservation to Rider Delivery on its scheduled pickup date.
     * - Validates pickup day (Asia/Manila), delivery address, and active rider.
     * - Keeps quantity locked in Reserved Stock while in transit (does NOT deduct Physical Stock yet).
     * - Creates linked Delivery Order + Delivery record with explicit Down Payment Paid and Pending Balance (COD).
     */
    public static function convertToDelivery(
        Reservation $reservation,
        string $deliveryAddress,
        int $riderId,
        ?int $adminId = null,
        ?string $ipAddress = null
    ): Reservation {
        return DB::transaction(function () use ($reservation, $deliveryAddress, $riderId, $adminId, $ipAddress) {
            $lockedReservation = Reservation::where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedReservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_READY], true)) {
                throw new \Exception("Only Confirmed or Ready for Pickup reservations can be converted to Rider Delivery. Current status: {$lockedReservation->status}.");
            }

            if ($lockedReservation->order_id !== null || ($lockedReservation->fulfillment_type ?? Reservation::FULFILLMENT_PICKUP) === Reservation::FULFILLMENT_DELIVERY) {
                throw new \Exception("Reservation {$lockedReservation->reservation_code} has already been converted to Rider Delivery.");
            }

            $nowManila = Carbon::now('Asia/Manila');
            if (!$lockedReservation->isPickupDay($nowManila)) {
                $pickupLabel = $lockedReservation->pickup_date?->format('M d, Y') ?? 'scheduled date';
                throw new \Exception("Conversion to Rider Delivery is only available on the scheduled pickup date ({$pickupLabel}).");
            }

            $cleanAddress = trim($deliveryAddress);
            if ($cleanAddress === '') {
                throw new \Exception('A delivery destination address is required before dispatching to a rider.');
            }

            $rider = User::where('id', $riderId)
                ->where('role', 'rider')
                ->where('status', 'active')
                ->first();

            if (!$rider) {
                throw new \Exception('Please select an active delivery rider.');
            }

            $product = Product::where('id', $lockedReservation->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Keep stock locked in Reserved Stock while in transit (never deduct Physical Stock before rider confirms delivery)
            if ((int) $lockedReservation->allocated_quantity < (int) $lockedReservation->quantity) {
                $missingQty = (int) $lockedReservation->quantity - (int) $lockedReservation->allocated_quantity;
                self::validateAndAllocateStock(
                    $product,
                    $missingQty,
                    $lockedReservation->pickup_date->toDateString(),
                    $lockedReservation->id
                );
                $lockedReservation->allocated_quantity = (int) $lockedReservation->quantity;
            }

            $breakdown = self::calculateDeliveryConversionBreakdown(
                $lockedReservation->productSubtotal(),
                (float) $lockedReservation->down_payment
            );

            // Resolve customer User linkage
            $customer = $lockedReservation->customer_id ? User::find($lockedReservation->customer_id) : null;
            if (!$customer) {
                $customer = User::firstOrCreate(
                    ['email' => 'reservation@dairybox.local'],
                    [
                        'name' => 'Reservation Customer',
                        'role' => 'customer',
                        'status' => 'active',
                        'password' => bcrypt(Str::random(32)),
                    ]
                );
            }

            $orderNumber = $lockedReservation->reservation_code;
            if (Order::where('order_number', $orderNumber)->exists()) {
                $orderNumber = $lockedReservation->reservation_code . '-DEL';
            }

            $orderPayload = [
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'rider_id' => $rider->id,
                'order_type' => 'Delivery',
                'payment_method' => $breakdown['is_fully_paid'] ? 'reservation_prepaid' : 'cod',
                'payment_status' => $breakdown['is_fully_paid'] ? 'paid' : 'pending',
                'paid_at' => $breakdown['is_fully_paid'] ? $nowManila : null,
                'delivery_address' => Str::limit($cleanAddress, 250),
                'schedule_date' => $lockedReservation->pickup_date->toDateString(),
                'schedule_time' => $nowManila->format('H:i:s'),
                'subtotal' => $breakdown['product_subtotal'],
                'delivery_fee' => $breakdown['delivery_fee'],
                'total_amount' => $breakdown['total_amount'],
                'order_status' => 'Out for Delivery',
            ];

            if (Schema::hasColumn('orders', 'down_payment_paid')) {
                $orderPayload['down_payment_paid'] = $breakdown['down_payment_paid'];
            }
            if (Schema::hasColumn('orders', 'pending_balance')) {
                $orderPayload['pending_balance'] = $breakdown['pending_balance'];
            }

            $order = Order::create($orderPayload);

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => (int) $lockedReservation->quantity,
                'price' => (float) $lockedReservation->unit_price,
                'subtotal' => $breakdown['product_subtotal'],
            ]);

            Product::saveOrderItemCustomization(
                $orderItem->id,
                $product->milk_type ?? 'Cow',
                $lockedReservation->size ?? $product->size ?? 'Medium'
            );

            Delivery::create([
                'order_id' => $order->id,
                'rider_id' => $rider->id,
                'delivery_status' => 'Out for Delivery',
            ]);

            $lockedReservation->status = Reservation::STATUS_OUT_FOR_DELIVERY;
            $lockedReservation->fulfillment_type = Reservation::FULFILLMENT_DELIVERY;
            $lockedReservation->delivery_address = Str::limit($cleanAddress, 250);
            $lockedReservation->rider_id = $rider->id;
            $lockedReservation->delivery_fee = $breakdown['delivery_fee'];
            $lockedReservation->total_amount = $breakdown['total_amount'];
            $lockedReservation->remaining_balance = $breakdown['pending_balance'];
            $lockedReservation->payment_status = $breakdown['is_fully_paid']
                ? Reservation::PAYMENT_PAID
                : Reservation::PAYMENT_PARTIAL;
            $lockedReservation->order_id = $order->id;
            $lockedReservation->dispatched_at = $nowManila;
            $lockedReservation->save();

            // Notify assigned rider with exact COD collection summary
            $collectionNote = $breakdown['is_fully_paid']
                ? 'Fully Paid (₱0.00 to collect).'
                : 'Collect Pending Balance (COD): ₱' . number_format($breakdown['pending_balance'], 2) . ' (Down Payment Paid: ₱' . number_format($breakdown['down_payment_paid'], 2) . ').';

            AppNotification::create([
                'user_id' => $rider->id,
                'title' => "Reservation Delivery Assigned: {$order->order_number}",
                'message' => "Deliver {$lockedReservation->quantity}x {$product->product_name} to {$lockedReservation->customer_name} at {$cleanAddress}. {$collectionNote}",
                'link' => route('rider.dashboard'),
                'is_read' => false,
            ]);

            if ($lockedReservation->customer_id) {
                AppNotification::create([
                    'user_id' => $lockedReservation->customer_id,
                    'title' => "Reservation Converted to Delivery ({$lockedReservation->reservation_code})",
                    'message' => "Your reservation has been dispatched via rider {$rider->name} to {$cleanAddress}. {$collectionNote}",
                    'link' => route('customer.orders.show', $order->id),
                    'is_read' => false,
                ]);
            }

            if ($adminId) {
                $feeText = $breakdown['delivery_fee'] > 0
                    ? '₱' . number_format($breakdown['delivery_fee'], 2)
                    : '₱0.00 (Free)';
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'RESERVATION_CONVERTED_TO_DELIVERY',
                    'details' => "Converted reservation {$lockedReservation->reservation_code} ({$lockedReservation->customer_name}) to Rider Delivery. Rider: {$rider->name}, Destination: {$cleanAddress}, Subtotal: ₱" . number_format($breakdown['product_subtotal'], 2) . ", Delivery Fee: {$feeText}, Total: ₱" . number_format($breakdown['total_amount'], 2) . ", Down Payment Paid: ₱" . number_format($breakdown['down_payment_paid'], 2) . ", Pending COD Balance: ₱" . number_format($breakdown['pending_balance'], 2) . ". Stock remains locked in Reserved Stock ({$lockedReservation->allocated_quantity} pcs) until delivery confirmation.",
                    'ip_address' => $ipAddress,
                ]);
            }

            return $lockedReservation->fresh(['product', 'customer', 'order.delivery', 'rider', 'refundConfirmer']);
        });
    }

    /**
     * Finalize a converted reservation when the assigned rider confirms delivery.
     * Executed inside the rider delivery DB transaction:
     * - Releases Reserved Stock
     * - Deducts Physical Stock via FIFO
     * - Marks Reservation Completed
     * - Logs a single fulfilled Sale and refreshes demand forecasts
     */
    public static function completeConvertedDeliveryOrder(Order $order, ?int $riderId = null, ?string $ipAddress = null): ?Reservation
    {
        $lockedReservation = Reservation::where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if (!$lockedReservation) {
            return null;
        }

        if ($lockedReservation->status === Reservation::STATUS_COMPLETED) {
            return $lockedReservation;
        }

        $product = Product::where('id', $lockedReservation->product_id)
            ->lockForUpdate()
            ->firstOrFail();

        $qty = (int) $lockedReservation->quantity;
        $allocatedQty = (int) $lockedReservation->allocated_quantity;

        if ($allocatedQty > 0) {
            $product->reserved_stock = max(0, (int) $product->reserved_stock - $allocatedQty);
            $product->save();
        }

        InventoryService::deductStock(
            $product->id,
            $qty,
            "Reservation Delivery Completed #{$lockedReservation->reservation_code}",
            true,
            $lockedReservation->pickup_date?->toDateString()
        );

        $nowManila = Carbon::now('Asia/Manila');

        $lockedReservation->status = Reservation::STATUS_COMPLETED;
        $lockedReservation->allocated_quantity = 0;
        $lockedReservation->payment_status = Reservation::PAYMENT_PAID;
        $lockedReservation->remaining_balance = 0.00;
        $lockedReservation->completed_at = $nowManila;
        $lockedReservation->save();

        if (!Sale::where('order_id', $order->id)->exists()) {
            Sale::create([
                'order_id' => $order->id,
                'total_amount' => (float) $order->total_amount,
                'sales_date' => $nowManila->toDateString(),
            ]);
        }

        if ($riderId) {
            $freshProduct = $product->fresh();
            $codAmount = $order->collectibleCodAmount();
            AuditLog::create([
                'user_id' => $riderId,
                'action' => 'RESERVATION_DELIVERY_COMPLETED',
                'details' => "Completed converted reservation delivery {$lockedReservation->reservation_code} ({$lockedReservation->customer_name}): deducted {$qty}x {$product->product_name} from Physical Stock, released Reserved Stock, logged sale ₱" . number_format((float) $order->total_amount, 2) . ", COD Balance to Remit: ₱" . number_format($codAmount, 2) . ". Stock now Physical: {$freshProduct->stock}, Reserved: {$freshProduct->reserved_stock}, Available: {$freshProduct->available_stock}.",
                'ip_address' => $ipAddress,
            ]);
        }

        try {
            $targetMonth = $nowManila->copy()->addMonth()->format('Y-m');
            ForecastService::generateAllProducts($targetMonth);
        } catch (\Throwable $th) {
            Log::warning('Forecast auto-refresh after reservation delivery completion failed: ' . $th->getMessage());
        }

        return $lockedReservation->fresh(['product', 'customer', 'order', 'rider']);
    }

    /**
     * Handle cancellation or failed delivery for a converted reservation delivery.
     * Releases Reserved Stock back to Available Stock WITHOUT inflating Physical Stock.
     */
    public static function cancelConvertedDeliveryOrder(
        Order $order,
        string $reason,
        ?int $actorId = null,
        ?string $ipAddress = null,
        string $initiatedBy = Reservation::CANCELLED_BY_CUSTOMER
    ): ?Reservation {
        $lockedReservation = Reservation::where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if (!$lockedReservation || $lockedReservation->status === Reservation::STATUS_CANCELLED) {
            return $lockedReservation;
        }

        $product = Product::where('id', $lockedReservation->product_id)
            ->lockForUpdate()
            ->firstOrFail();

        $releasedQty = (int) $lockedReservation->allocated_quantity;
        if ($releasedQty > 0) {
            $product->reserved_stock = max(0, (int) $product->reserved_stock - $releasedQty);
            $product->save();
            $lockedReservation->allocated_quantity = 0;
        }

        self::applyCancellationSnapshot(
            $lockedReservation,
            trim($reason) !== '' ? trim($reason) : 'Converted reservation delivery cancelled/failed.',
            $initiatedBy,
            $actorId,
            $ipAddress,
            $releasedQty,
            $product
        );

        return $lockedReservation->fresh(['product', 'customer', 'order', 'rider']);
    }

    /**
     * Backend source of truth for cancellation fee and refund calculation.
     * Always reads policy percentages dynamically from database settings unless explicitly provided.
     *
     * Rules:
     * - Customer-Initiated + Fee Enabled:
     *     Cancellation Fee Amount = Down Payment * (Cancellation Fee % / 100)
     *     Refund Amount = Down Payment - Cancellation Fee Amount
     * - Full Refund Override (₱0 Fee):
     *     Issued when Cancellation Fee is Disabled OR Business cancels due to inability to fulfill.
     *
     * @return array{
     *     cancellation_initiated_by: string,
     *     applied_fee_percentage: float,
     *     cancellation_fee_amount: float,
     *     refund_amount: float,
     *     refund_status: string,
     *     applied_policy_description: string
     * }
     */
    public static function calculateCancellationAmounts(
        float $downPayment,
        string $initiatedBy = Reservation::CANCELLED_BY_CUSTOMER,
        ?array $policy = null
    ): array {
        $policy = $policy ?? SettingService::getReservationCancellationPolicy();
        $normalizedDownPayment = round(max(0.0, $downPayment), 2);
        $initiator = $initiatedBy === Reservation::CANCELLED_BY_BUSINESS
            ? Reservation::CANCELLED_BY_BUSINESS
            : Reservation::CANCELLED_BY_CUSTOMER;

        $feeEnabled = (bool) ($policy['enabled'] ?? false);

        if ($initiator === Reservation::CANCELLED_BY_BUSINESS || !$feeEnabled) {
            $appliedFeePercentage = 0.00;
            $cancellationFeeAmount = 0.00;
            $refundAmount = $normalizedDownPayment;
        } else {
            $appliedFeePercentage = round(max(0.0, min(100.0, (float) ($policy['fee_percentage'] ?? 0.0))), 2);
            $cancellationFeeAmount = round($normalizedDownPayment * ($appliedFeePercentage / 100), 2);
            $refundAmount = round(max(0.0, $normalizedDownPayment - $cancellationFeeAmount), 2);
        }

        $refundStatus = $refundAmount > 0
            ? Reservation::REFUND_PENDING
            : Reservation::REFUND_NOT_APPLICABLE;

        return [
            'cancellation_initiated_by' => $initiator,
            'applied_fee_percentage' => $appliedFeePercentage,
            'cancellation_fee_amount' => $cancellationFeeAmount,
            'refund_amount' => $refundAmount,
            'refund_status' => $refundStatus,
            'applied_policy_description' => trim((string) ($policy['description'] ?? '')),
        ];
    }

    /**
     * Cancel a reservation -> releases Reserved Stock back to Available Stock and saves a permanent
     * cancellation & refund snapshot based on the active database policy.
     */
    public static function cancel(
        Reservation $reservation,
        ?string $reason = null,
        ?int $adminId = null,
        ?string $ipAddress = null,
        string $initiatedBy = Reservation::CANCELLED_BY_CUSTOMER
    ): Reservation {
        return DB::transaction(function () use ($reservation, $reason, $adminId, $ipAddress, $initiatedBy) {
            $lockedReservation = Reservation::where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedReservation->canCancel()) {
                throw new \Exception("Reservation {$lockedReservation->reservation_code} cannot be cancelled from status '{$lockedReservation->status}'.");
            }

            $product = Product::where('id', $lockedReservation->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $releasedQty = (int) $lockedReservation->allocated_quantity;
            if ($releasedQty > 0) {
                $product->reserved_stock = max(0, (int) $product->reserved_stock - $releasedQty);
                $product->save();
                $lockedReservation->allocated_quantity = 0;
            }

            $defaultReason = $initiatedBy === Reservation::CANCELLED_BY_BUSINESS
                ? 'Cancelled by business (unable to fulfill order).'
                : 'Cancelled at customer request.';
            $cleanReason = $reason && trim($reason) !== '' ? trim($reason) : $defaultReason;

            self::applyCancellationSnapshot(
                $lockedReservation,
                $cleanReason,
                $initiatedBy,
                $adminId,
                $ipAddress,
                $releasedQty,
                $product
            );

            return $lockedReservation->fresh(['product', 'customer', 'order', 'refundConfirmer']);
        });
    }

    /**
     * Explicitly confirm physical/digital refund handover for a cancelled reservation.
     * Refunds remain Pending until this method is executed by an administrator.
     */
    public static function confirmRefund(
        Reservation $reservation,
        string $refundMethod = 'Cash',
        ?string $refundReference = null,
        ?int $adminId = null,
        ?string $ipAddress = null
    ): Reservation {
        return DB::transaction(function () use ($reservation, $refundMethod, $refundReference, $adminId, $ipAddress) {
            $lockedReservation = Reservation::where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReservation->status !== Reservation::STATUS_CANCELLED) {
                throw new \Exception("Refund confirmation is only available for Cancelled reservations.");
            }

            if ($lockedReservation->refund_status === Reservation::REFUND_COMPLETED) {
                throw new \Exception("Refund for reservation {$lockedReservation->reservation_code} has already been marked as Completed.");
            }

            if (!$lockedReservation->canConfirmRefund()) {
                throw new \Exception("Reservation {$lockedReservation->reservation_code} does not have a pending refund amount to confirm.");
            }

            $allowedMethods = ['Cash', 'GCash', 'Bank Transfer', 'Other'];
            $normalizedMethod = in_array($refundMethod, $allowedMethods, true) ? $refundMethod : 'Cash';
            $cleanReference = $refundReference && trim($refundReference) !== '' ? trim($refundReference) : null;
            $refundAmount = round((float) $lockedReservation->refund_amount, 2);

            $lockedReservation->refund_status = Reservation::REFUND_COMPLETED;
            $lockedReservation->refund_confirmed_at = now();
            $lockedReservation->refund_confirmed_by = $adminId;
            $lockedReservation->refund_method = $normalizedMethod;
            $lockedReservation->refund_reference = $cleanReference;
            $lockedReservation->save();

            // Record confirmed refund cash outflow in the Official Daily Cash Ledger
            if ($adminId && Schema::hasTable('cash_ledger_entries')) {
                $ledgerPayload = [
                    'ledger_date' => now()->toDateString(),
                    'admin_id' => $adminId,
                    'rider_id' => null,
                    'rider_name' => $lockedReservation->customer_name . ' (Reservation Refund)',
                    'remittance_id' => null,
                    'entry_type' => 'RESERVATION_REFUND',
                    'amount_expected' => -$refundAmount,
                    'amount_remitted' => -$refundAmount,
                    'variance' => 0.00,
                    'payment_method' => $normalizedMethod,
                    'notes' => "Confirmed refund handover for {$lockedReservation->reservation_code} ({$lockedReservation->customer_name})." . ($cleanReference ? " Ref: {$cleanReference}" : ''),
                    'recorded_at' => now(),
                ];

                if (Schema::hasColumn('cash_ledger_entries', 'reservation_id')) {
                    $ledgerPayload['reservation_id'] = $lockedReservation->id;
                }

                CashLedgerEntry::create($ledgerPayload);
            }

            if ($adminId) {
                $refDetail = $cleanReference ? ", Reference: {$cleanReference}" : '';
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'RESERVATION_REFUND_CONFIRMED',
                    'details' => "Confirmed refund handover for {$lockedReservation->reservation_code} ({$lockedReservation->customer_name}): ₱" . number_format($refundAmount, 2) . " via {$normalizedMethod}{$refDetail}. Refund Status: Completed.",
                    'ip_address' => $ipAddress,
                ]);
            }

            return $lockedReservation->fresh(['product', 'customer', 'order', 'refundConfirmer']);
        });
    }

    /**
     * Persist permanent historical cancellation snapshot, log any forfeited fee to the cash ledger
     * (never as a product unit sale), and create the audit trail entry.
     */
    protected static function applyCancellationSnapshot(
        Reservation $lockedReservation,
        string $cleanReason,
        string $initiatedBy,
        ?int $adminId,
        ?string $ipAddress,
        int $releasedQty,
        Product $product
    ): void {
        $snapshot = self::calculateCancellationAmounts(
            (float) $lockedReservation->down_payment,
            $initiatedBy
        );

        $lockedReservation->status = Reservation::STATUS_CANCELLED;
        $lockedReservation->allocated_quantity = 0;
        $lockedReservation->cancelled_at = now();
        $lockedReservation->cancellation_reason = $cleanReason;
        $lockedReservation->cancellation_initiated_by = $snapshot['cancellation_initiated_by'];
        $lockedReservation->applied_fee_percentage = $snapshot['applied_fee_percentage'];
        $lockedReservation->cancellation_fee_amount = $snapshot['cancellation_fee_amount'];
        $lockedReservation->refund_amount = $snapshot['refund_amount'];
        $lockedReservation->refund_status = $snapshot['refund_status'];
        $lockedReservation->applied_policy_description = $snapshot['applied_policy_description'] !== ''
            ? $snapshot['applied_policy_description']
            : null;
        $lockedReservation->save();

        // Log forfeited cancellation fee as non-product ledger income (never creates an Order, OrderItem, or Sale)
        if ($snapshot['cancellation_fee_amount'] > 0 && $adminId && Schema::hasTable('cash_ledger_entries')) {
            $ledgerPayload = [
                'ledger_date' => now()->toDateString(),
                'admin_id' => $adminId,
                'rider_id' => null,
                'rider_name' => $lockedReservation->customer_name . ' (Cancellation Fee)',
                'remittance_id' => null,
                'entry_type' => 'RESERVATION_CANCELLATION_FEE',
                'amount_expected' => $snapshot['cancellation_fee_amount'],
                'amount_remitted' => $snapshot['cancellation_fee_amount'],
                'variance' => 0.00,
                'payment_method' => 'Forfeited Deposit',
                'notes' => "Forfeited cancellation fee ({$snapshot['applied_fee_percentage']}%) retained from ₱" . number_format((float) $lockedReservation->down_payment, 2) . " down payment on {$lockedReservation->reservation_code}. Excluded from product unit sales.",
                'recorded_at' => now(),
            ];

            if (Schema::hasColumn('cash_ledger_entries', 'reservation_id')) {
                $ledgerPayload['reservation_id'] = $lockedReservation->id;
            }

            CashLedgerEntry::create($ledgerPayload);
        }

        if ($adminId) {
            $initiatorText = $snapshot['cancellation_initiated_by'] === Reservation::CANCELLED_BY_BUSINESS
                ? 'Business (Full Refund Override)'
                : 'Customer';

            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'RESERVATION_CANCELLED',
                'details' => "Cancelled reservation {$lockedReservation->reservation_code} ({$lockedReservation->customer_name}) [Initiated by: {$initiatorText}]. Released {$releasedQty} reserved unit(s) of {$product->product_name} back to Available Stock. Applied Fee: {$snapshot['applied_fee_percentage']}% (₱" . number_format($snapshot['cancellation_fee_amount'], 2) . "), Refund: ₱" . number_format($snapshot['refund_amount'], 2) . " ({$snapshot['refund_status']}). Reason: {$cleanReason}",
                'ip_address' => $ipAddress,
            ]);
        }
    }

    /**
     * Core completion routine executed inside an existing DB transaction with locked reservation & product rows.
     */
    protected static function completeLockedReservation(
        Reservation $lockedReservation,
        Product $product,
        ?int $adminId,
        ?string $ipAddress,
        string $paymentMethod = 'cash'
    ): Reservation {
        $qty = (int) $lockedReservation->quantity;
        $allocatedQty = (int) $lockedReservation->allocated_quantity;

        // 1. Release Reserved Stock first so this reservation's own units become deductible while still protecting other reservations
        if ($allocatedQty > 0) {
            $product->reserved_stock = max(0, (int) $product->reserved_stock - $allocatedQty);
            $product->save();
        }

        // 2. Deduct Physical Stock via FIFO (preferring batches valid through pickup date)
        InventoryService::deductStock(
            $product->id,
            $qty,
            "Reservation Completed #{$lockedReservation->reservation_code}",
            true,
            $lockedReservation->pickup_date?->toDateString()
        );

        // 3. Resolve customer user record for Order/Sale ledger linkage
        if ($lockedReservation->customer_id) {
            $customer = User::find($lockedReservation->customer_id);
        } else {
            $customer = null;
        }

        if (!$customer) {
            $customer = User::firstOrCreate(
                ['email' => 'reservation@dairybox.local'],
                [
                    'name' => 'Reservation Customer',
                    'role' => 'customer',
                    'status' => 'active',
                    'password' => bcrypt(Str::random(32)),
                ]
            );
        }

        // 4. Create single fulfilled Order + OrderItem + Sale (never double-counts down payments)
        $orderNumber = $lockedReservation->reservation_code;
        if (Order::where('order_number', $orderNumber)->exists()) {
            $orderNumber = $lockedReservation->reservation_code . '-' . strtoupper(Str::random(3));
        }

        $fulfillmentSummary = "Store Reservation Pickup ({$lockedReservation->customer_name} | {$lockedReservation->contact_number})";
        if (!empty($lockedReservation->notes)) {
            $fulfillmentSummary .= ' - ' . $lockedReservation->notes;
        }

        $totalAmount = (float) $lockedReservation->total_amount;
        $normalizedMethod = in_array(strtolower($paymentMethod), ['cash', 'gcash', 'card', 'other'], true)
            ? strtolower($paymentMethod)
            : 'cash';

        $order = Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'order_type' => 'Reservation',
            'payment_method' => $normalizedMethod,
            'payment_status' => 'paid',
            'paid_at' => now(),
            'delivery_address' => Str::limit($fulfillmentSummary, 250),
            'schedule_date' => $lockedReservation->pickup_date->toDateString(),
            'schedule_time' => now()->format('H:i:s'),
            'subtotal' => $totalAmount,
            'delivery_fee' => 0.00,
            'total_amount' => $totalAmount,
            'order_status' => 'Delivered',
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'price' => (float) $lockedReservation->unit_price,
            'subtotal' => $totalAmount,
        ]);

        Product::saveOrderItemCustomization(
            $orderItem->id,
            $product->milk_type ?? 'Cow',
            $lockedReservation->size ?? $product->size ?? 'Medium'
        );

        Sale::create([
            'order_id' => $order->id,
            'total_amount' => $totalAmount,
            'sales_date' => now()->toDateString(),
        ]);

        // 5. Finalize Reservation record
        $lockedReservation->status = Reservation::STATUS_COMPLETED;
        $lockedReservation->allocated_quantity = 0;
        $lockedReservation->payment_status = Reservation::PAYMENT_PAID;
        $lockedReservation->remaining_balance = 0.00;
        $lockedReservation->order_id = $order->id;
        $lockedReservation->completed_at = now();
        $lockedReservation->save();

        // 6. Audit Log
        $freshProduct = $product->fresh();
        if ($adminId) {
            AuditLog::create([
                'user_id' => $adminId,
                'action' => 'RESERVATION_COMPLETED',
                'details' => "Completed reservation {$lockedReservation->reservation_code} ({$lockedReservation->customer_name}): deducted {$qty}x {$product->product_name} from Physical Stock, logged fulfilled sale of ₱" . number_format($totalAmount, 2) . " (Order #{$order->order_number}). Stock now Physical: {$freshProduct->stock}, Reserved: {$freshProduct->reserved_stock}, Available: {$freshProduct->available_stock}.",
                'ip_address' => $ipAddress,
            ]);
        }

        // 7. Refresh demand forecasts safely
        try {
            $targetMonth = Carbon::now()->addMonth()->format('Y-m');
            ForecastService::generateAllProducts($targetMonth);
        } catch (\Throwable $th) {
            Log::warning('Forecast auto-refresh after reservation completion failed: ' . $th->getMessage());
        }

        return $lockedReservation->fresh(['product', 'customer', 'order']);
    }

    /**
     * Validate that the product has non-expired stock (expiration_date >= today)
     * and enough batch stock expiring on or after the scheduled pickup date.
     * Enforced for ALL active reservation statuses including Pending.
     */
    protected static function validateReservableEligibility(
        Product $lockedProduct,
        int $quantity,
        string $pickupDate,
        ?int $excludeReservationId = null
    ): void {
        // 1. Block completely expired products (expiration_date < today)
        $nonExpiredStock = InventoryService::getNonExpiredStock($lockedProduct);
        if ($nonExpiredStock <= 0) {
            throw new \Exception(
                "{$lockedProduct->product_name} cannot be reserved because its remaining stock has expired."
            );
        }

        // 2. Check Available Stock = Physical Stock - Reserved Stock
        $availableStock = InventoryService::getAvailableStock($lockedProduct);
        if ($availableStock < $quantity) {
            throw new \Exception(InventoryService::insufficientStockMessage($lockedProduct, $availableStock));
        }

        // 3. Validate non-expired batch stock valid through the scheduled pickup date (expiration_date >= max(today, pickup_date))
        $eligibleBatchStock = InventoryService::getEligibleBatchStockForDate($lockedProduct->id, $pickupDate);

        $alreadyReservedForDateOrLater = (int) Reservation::where('product_id', $lockedProduct->id)
            ->whereIn('status', Reservation::ALLOCATED_STATUSES)
            ->whereDate('pickup_date', '>=', $pickupDate)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->sum('allocated_quantity');

        $netEligibleForDate = max(0, $eligibleBatchStock - $alreadyReservedForDateOrLater);

        if ($netEligibleForDate < $quantity) {
            $formattedDate = Carbon::parse($pickupDate)->format('M d, Y');
            throw new \Exception(
                "Cannot reserve {$quantity} pieces of {$lockedProduct->product_name} for {$formattedDate}. Only {$netEligibleForDate} non-expired pieces have a batch expiration date on or after the scheduled pickup date."
            );
        }
    }

    /**
     * Validate Available Stock and Batch Expiry >= Pickup Date, then increment Product::reserved_stock.
     * Must be called with $lockedProduct already locked via lockForUpdate().
     */
    protected static function validateAndAllocateStock(
        Product $lockedProduct,
        int $quantity,
        string $pickupDate,
        ?int $excludeReservationId = null
    ): void {
        self::validateReservableEligibility($lockedProduct, $quantity, $pickupDate, $excludeReservationId);

        // Allocate to Reserved Stock (do NOT deduct Physical Stock)
        $lockedProduct->reserved_stock = (int) ($lockedProduct->reserved_stock ?? 0) + $quantity;
        $lockedProduct->save();
    }

    /**
     * Compute consistent [downPayment, remainingBalance, paymentStatus] values.
     * Strictly requires down payment > ₱0 and blocks Unpaid reservations.
     */
    public static function resolvePaymentFields(float $totalAmount, float $downPayment, ?string $requestedPaymentStatus = null): array
    {
        $downPayment = round($downPayment, 2);

        if ($requestedPaymentStatus === Reservation::PAYMENT_UNPAID) {
            throw new \Exception('Unpaid reservations are not allowed. A down payment greater than ₱0.00 is required.');
        }

        if ($requestedPaymentStatus === Reservation::PAYMENT_PAID && $downPayment < $totalAmount) {
            $downPayment = $totalAmount;
        }

        if ($downPayment <= 0) {
            throw new \Exception('A down payment greater than ₱0.00 is required before a reservation can be saved.');
        }

        if ($downPayment > $totalAmount) {
            throw new \Exception('Down payment cannot exceed the total reservation amount (₱' . number_format($totalAmount, 2) . ').');
        }

        $remainingBalance = round(max(0, $totalAmount - $downPayment), 2);
        $paymentStatus = Reservation::computePaymentStatus($totalAmount, $downPayment);

        return [$downPayment, $remainingBalance, $paymentStatus];
    }
}
