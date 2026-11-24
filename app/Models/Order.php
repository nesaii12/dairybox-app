<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'rider_id',
        'remittance_id',
        'order_type',
        'payment_method',
        'payment_status',
        'payment_channel',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'paid_at',
        'delivery_address',
        'schedule_date',
        'schedule_time',
        'subtotal',
        'delivery_fee',
        'total_amount',
        'down_payment_paid',
        'pending_balance',
        'order_status',
        'cancelled_after_arrival',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'schedule_date' => 'date',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'down_payment_paid' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'cancelled_after_arrival' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return strtolower((string) $this->payment_status) === 'paid';
    }

    public function isOnlinePayment(): bool
    {
        return strtolower((string) $this->payment_method) === 'xendit';
    }

    public function isReservationOrder(): bool
    {
        return (float) ($this->down_payment_paid ?? 0) > 0
            || $this->pending_balance !== null
            || ($this->relationLoaded('reservation') && $this->reservation !== null);
    }

    /**
     * Check if this order is cancelled (either cancelled by customer or admin).
     */
    public function isCancelled(): bool
    {
        return in_array($this->order_status, ['Cancelled', 'Cancelled by Customer']);
    }

    /**
     * Check if this order was cancelled specifically by the customer.
     */
    public function isCancelledByCustomer(): bool
    {
        if ($this->order_status === 'Cancelled by Customer') {
            return true;
        }

        if ($this->order_status === 'Cancelled') {
            $reason = strtolower($this->cancellation_reason ?? '');
            if (
                str_contains($reason, 'customer') ||
                str_contains($reason, 'changed my mind') ||
                str_contains($reason, 'wrong item') ||
                str_contains($reason, 'schedule is too late') ||
                str_contains($reason, 'change delivery address') ||
                str_contains($reason, 'alternative product')
            ) {
                return true;
            }

            // Check if an audit log exists for customer cancellation
            return \App\Models\AuditLog::where('action', 'ORDER_CANCELLED_BY_CUSTOMER')
                ->where('details', 'like', "%{$this->order_number}%")
                ->exists();
        }

        return false;
    }

    /**
     * Check if this order has actually been dispatched for delivery.
     * Pickup and Walk-in orders are fulfillment at store and never dispatched.
     * Delivery orders are dispatched when assigned to a rider and marked Out for Delivery.
     */
    public function isDispatched(): bool
    {
        // Pickup and Walk-in orders are never dispatched
        if (in_array($this->order_type, ['Pickup', 'Walk-in'])) {
            return false;
        }

        // Explicit status check
        if (in_array($this->order_status, ['Out for Delivery', 'Dispatched'])) {
            return true;
        }

        // Post-arrival cancellation incident implies rider was dispatched
        if ($this->cancelled_after_arrival) {
            return true;
        }

        // Associated delivery record check
        $delivery = $this->relationLoaded('delivery') ? $this->delivery : $this->delivery()->first();
        if ($delivery) {
            if ($delivery->arrived_at !== null || $delivery->cancelled_after_arrival) {
                return true;
            }
            if (in_array($delivery->delivery_status, ['Out for Delivery', 'Dispatched', 'Delivered'])) {
                return true;
            }
            if ($delivery->rider_id !== null && !in_array($delivery->delivery_status, ['Pending', 'Assigned'])) {
                return true;
            }
        }

        // Reservation converted with dispatched timestamp
        $reservation = $this->relationLoaded('reservation') ? $this->reservation : $this->reservation()->first();
        if ($reservation && $reservation->dispatched_at !== null) {
            return true;
        }

        return false;
    }

    /**
     * Return the human-readable status badge label for the Admin UI and customer views.
     */
    public function statusBadgeLabel(): string
    {
        if ($this->isCancelledByCustomer()) {
            return 'Cancelled by Customer';
        }

        return $this->order_status;
    }

    /**
     * Return CSS classes for status badges.
     */
    public function statusBadgeClasses(): string
    {
        if ($this->isCancelledByCustomer()) {
            return 'bg-rose-50 text-rose-700 border-rose-200';
        }

        return match ($this->order_status) {
            'Pending' => 'bg-amber-50 text-amber-700 border-amber-100',
            'Confirmed' => 'bg-sky-50 text-sky-700 border-sky-100',
            'Preparing' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            'Ready for Pickup' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'Out for Delivery' => 'bg-purple-50 text-purple-700 border-purple-100',
            'Delivered' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
            'Delivered - Pending Remittance' => 'bg-amber-50 text-amber-800 border-amber-200',
            'Paid & Remitted' => 'bg-emerald-50 text-emerald-800 border-emerald-200 font-bold',
            'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
            default => 'bg-slate-50 text-slate-600 border-slate-200',
        };
    }

    /**
     * Exact cash-on-delivery (COD) amount the rider must collect upon handover and remit to admin.
     * - Paid orders: ₱0.00
     * - Converted reservation deliveries: pending_balance (Total Amount - Down Payment Paid)
     * - Standard COD orders: total_amount
     */
    public function collectibleCodAmount(): float
    {
        if ($this->isPaid()) {
            return 0.00;
        }

        if ($this->pending_balance !== null) {
            return round(max(0.0, (float) $this->pending_balance), 2);
        }

        if (strtolower((string) $this->payment_method) === 'cod') {
            return round(max(0.0, (float) $this->total_amount), 2);
        }

        return 0.00;
    }

    public function hasPendingBalance(): bool
    {
        return $this->collectibleCodAmount() > 0;
    }

    /**
     * Calculate tiered delivery fee based on order subtotal and fulfillment type.
     * - Subtotal >= ₱1,000: Free Delivery (₱0.00)
     * - Subtotal >= ₱150 up to ₱999.99: ₱25.00
     * - Subtotal < ₱150: ₱50.00
     */
    public static function calculateDeliveryFee(float $subtotal, string $orderType = 'Delivery'): float
    {
        if ($orderType !== 'Delivery') {
            return 0.00;
        }

        if ($subtotal >= 1000) {
            return 0.00;
        }

        if ($subtotal >= 150) {
            return 25.00;
        }

        return 50.00;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (Order $order) {
            try {
                broadcast(new \App\Events\OrderUpdated($order, 'created'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Order broadcast failed: ' . $e->getMessage());
            }
        });

        static::updated(function (Order $order) {
            try {
                broadcast(new \App\Events\OrderUpdated($order, 'updated'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Order broadcast failed: ' . $e->getMessage());
            }
        });
    }

    /**
     * Get the customer who placed this order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the rider assigned to this order.
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    /**
     * Get the remittance record associated with this order.
     */
    public function remittance(): BelongsTo
    {
        return $this->belongsTo(RiderRemittance::class, 'remittance_id');
    }

    /**
     * Get the items in this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the delivery record for this order.
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    /**
     * Get the sale record for this order.
     */
    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class, 'order_id');
    }

    public function chatThread(): MorphOne
    {
        return $this->morphOne(ChatThread::class, 'chatable');
    }

    public function unreadChatCountFor(string $viewerRole): int
    {
        if (!$this->relationLoaded('chatThread')) {
            $this->load('chatThread');
        }

        return $this->chatThread ? $this->chatThread->unreadCountForRole($viewerRole) : 0;
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'DB-' . date('Ymd');
        $lastOrder = static::where('order_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . '-' . $newNumber;
    }
}
