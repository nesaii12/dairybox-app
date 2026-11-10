<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
            'cancelled_after_arrival' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return strtolower($this->payment_status) === 'paid';
    }

    public function isOnlinePayment(): bool
    {
        return strtolower($this->payment_method) === 'xendit';
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
