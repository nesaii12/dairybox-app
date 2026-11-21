<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            if ($oldProduct && $lockedReservation->allocated_quantity > 0) {
                $oldProduct->reserved_stock = max(0, (int) $oldProduct->reserved_stock - (int) $lockedReservation->allocated_quantity);
                $oldProduct->save();
                $lockedReservation->allocated_quantity = 0;
            }

            $size = !empty($data['size']) ? trim((string) $data['size']) : ($lockedReservation->size ?? $newProduct->size ?? 'Medium');
            $quantity = (int) ($data['quantity'] ?? $lockedReservation->quantity);
            $unitPrice = isset($data['unit_price']) && $data['unit_price'] !== ''
                ? round((float) $data['unit_price'], 2)
                : round((float) $newProduct->getPriceForSize($size), 2);
            $totalAmount = round($quantity * $unitPrice, 2);

            [$downPayment, $remainingBalance, $paymentStatus] = self::resolvePaymentFields(
                $totalAmount,
                (float) ($data['down_payment'] ?? $lockedReservation->down_payment),
                $data['payment_status'] ?? null
            );

            $reservationDate = Carbon::parse($data['reservation_date'] ?? $lockedReservation->reservation_date->toDateString())->toDateString();
            $pickupDate = Carbon::parse($data['pickup_date'] ?? $lockedReservation->pickup_date->toDateString())->toDateString();
            $targetStatus = $data['status'] ?? $lockedReservation->status;

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
                $lockedReservation->status = Reservation::STATUS_CANCELLED;
                $lockedReservation->allocated_quantity = 0;
                $lockedReservation->cancelled_at = now();
                $lockedReservation->cancellation_reason = !empty($data['cancellation_reason'])
                    ? trim((string) $data['cancellation_reason'])
                    : 'Cancelled by administrator.';
                $lockedReservation->save();
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
     * Cancel a reservation -> releases Reserved Stock back to Available Stock if it was allocated.
     */
    public static function cancel(Reservation $reservation, ?string $reason = null, ?int $adminId = null, ?string $ipAddress = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $reason, $adminId, $ipAddress) {
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

            $cleanReason = $reason && trim($reason) !== '' ? trim($reason) : 'Cancelled by administrator.';
            $lockedReservation->status = Reservation::STATUS_CANCELLED;
            $lockedReservation->cancelled_at = now();
            $lockedReservation->cancellation_reason = $cleanReason;
            $lockedReservation->save();

            if ($adminId) {
                AuditLog::create([
                    'user_id' => $adminId,
                    'action' => 'RESERVATION_CANCELLED',
                    'details' => "Cancelled reservation {$lockedReservation->reservation_code} ({$lockedReservation->customer_name}). Released {$releasedQty} reserved unit(s) of {$product->product_name} back to Available Stock. Reason: {$cleanReason}",
                    'ip_address' => $ipAddress,
                ]);
            }

            return $lockedReservation->fresh(['product', 'customer', 'order']);
        });
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
     * Validate Available Stock and Batch Expiry >= Pickup Date, then increment Product::reserved_stock.
     * Must be called with $lockedProduct already locked via lockForUpdate().
     */
    protected static function validateAndAllocateStock(
        Product $lockedProduct,
        int $quantity,
        string $pickupDate,
        ?int $excludeReservationId = null
    ): void {
        // 1. Check Available Stock = Physical Stock - Reserved Stock
        $availableStock = InventoryService::getAvailableStock($lockedProduct);
        if ($availableStock < $quantity) {
            throw new \Exception(InventoryService::insufficientStockMessage($lockedProduct, $availableStock));
        }

        // 2. Validate batch expiry >= scheduled pickup date
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
                "Cannot confirm {$quantity} pieces of {$lockedProduct->product_name} for {$formattedDate}. Only {$netEligibleForDate} pieces have a batch expiration date on or after the scheduled pickup date."
            );
        }

        // 3. Allocate to Reserved Stock (do NOT deduct Physical Stock)
        $lockedProduct->reserved_stock = (int) ($lockedProduct->reserved_stock ?? 0) + $quantity;
        $lockedProduct->save();
    }

    /**
     * Compute consistent [downPayment, remainingBalance, paymentStatus] values.
     */
    public static function resolvePaymentFields(float $totalAmount, float $downPayment, ?string $requestedPaymentStatus = null): array
    {
        $downPayment = round(max(0, $downPayment), 2);
        if ($downPayment > $totalAmount) {
            throw new \Exception('Down payment cannot exceed the total reservation amount (₱' . number_format($totalAmount, 2) . ').');
        }

        // If user explicitly chose Fully Paid with 0/partial typed, respect consistency only when sensible;
        // otherwise derive automatically from Down Payment vs Total Amount.
        if ($requestedPaymentStatus === Reservation::PAYMENT_PAID && $downPayment < $totalAmount) {
            $downPayment = $totalAmount;
        } elseif ($requestedPaymentStatus === Reservation::PAYMENT_UNPAID && $downPayment > 0) {
            // Keep actual entered down payment and compute honest payment status so accounting stays truthful
            $requestedPaymentStatus = null;
        }

        $remainingBalance = round(max(0, $totalAmount - $downPayment), 2);
        $paymentStatus = Reservation::computePaymentStatus($totalAmount, $downPayment);

        return [$downPayment, $remainingBalance, $paymentStatus];
    }
}
