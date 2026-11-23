<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory;

    public const MIN_QUANTITY = 15;
    public const FREE_DELIVERY_THRESHOLD = 3000.00;
    public const STANDARD_DELIVERY_FEE = 25.00;

    public const FULFILLMENT_PICKUP = 'Pickup';
    public const FULFILLMENT_DELIVERY = 'Delivery';

    public const STATUS_PENDING = 'Pending';
    public const STATUS_CONFIRMED = 'Confirmed';
    public const STATUS_READY = 'Ready for Pickup';
    public const STATUS_OUT_FOR_DELIVERY = 'Out for Delivery';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_READY,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const ALLOCATED_STATUSES = [
        self::STATUS_CONFIRMED,
        self::STATUS_READY,
        self::STATUS_OUT_FOR_DELIVERY,
    ];

    public const PAYMENT_UNPAID = 'Unpaid';
    public const PAYMENT_PARTIAL = 'Partially Paid';
    public const PAYMENT_PAID = 'Fully Paid';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_UNPAID,
        self::PAYMENT_PARTIAL,
        self::PAYMENT_PAID,
    ];

    public const CANCELLED_BY_CUSTOMER = 'customer';
    public const CANCELLED_BY_BUSINESS = 'business';

    public const CANCELLATION_INITIATORS = [
        self::CANCELLED_BY_CUSTOMER,
        self::CANCELLED_BY_BUSINESS,
    ];

    public const REFUND_PENDING = 'Pending';
    public const REFUND_COMPLETED = 'Completed';
    public const REFUND_NOT_APPLICABLE = 'Not Applicable';

    public const REFUND_STATUSES = [
        self::REFUND_PENDING,
        self::REFUND_COMPLETED,
        self::REFUND_NOT_APPLICABLE,
    ];

    protected $fillable = [
        'reservation_code',
        'customer_id',
        'customer_name',
        'contact_number',
        'product_id',
        'size',
        'quantity',
        'allocated_quantity',
        'unit_price',
        'total_amount',
        'delivery_fee',
        'down_payment',
        'remaining_balance',
        'payment_status',
        'status',
        'fulfillment_type',
        'delivery_address',
        'rider_id',
        'reservation_date',
        'pickup_date',
        'notes',
        'order_id',
        'created_by',
        'confirmed_at',
        'ready_at',
        'dispatched_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancellation_initiated_by',
        'applied_fee_percentage',
        'cancellation_fee_amount',
        'refund_amount',
        'refund_status',
        'applied_policy_description',
        'refund_confirmed_at',
        'refund_confirmed_by',
        'refund_method',
        'refund_reference',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'allocated_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'applied_fee_percentage' => 'decimal:2',
            'cancellation_fee_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'reservation_date' => 'date',
            'pickup_date' => 'date',
            'confirmed_at' => 'datetime',
            'ready_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refund_confirmed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function refundConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refund_confirmed_by');
    }

    public function cashLedgerEntries(): HasMany
    {
        return $this->hasMany(CashLedgerEntry::class, 'reservation_id');
    }

    public static function generateReservationCode(): string
    {
        $prefix = 'RES-' . now()->format('Ymd');
        $last = static::where('reservation_code', 'like', $prefix . '-%')
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($last) {
            $parts = explode('-', $last->reservation_code);
            $lastNum = (int) end($parts);
            $nextSeq = $lastNum + 1;
        }

        $code = $prefix . '-' . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        while (static::where('reservation_code', $code)->exists()) {
            $nextSeq++;
            $code = $prefix . '-' . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    public static function computePaymentStatus(float $totalAmount, float $downPayment): string
    {
        if ($downPayment <= 0) {
            return self::PAYMENT_UNPAID;
        }

        if ($totalAmount > 0 && $downPayment >= $totalAmount) {
            return self::PAYMENT_PAID;
        }

        return self::PAYMENT_PARTIAL;
    }

    public function isAllocated(): bool
    {
        return in_array($this->status, self::ALLOCATED_STATUSES, true) && $this->allocated_quantity > 0;
    }

    public function canEdit(): bool
    {
        return !in_array($this->status, [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }

    public function canConfirm(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canMarkReady(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canComplete(): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_READY], true);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_READY], true);
    }

    public function isPickupDay(?\Carbon\Carbon $now = null): bool
    {
        if (!$this->pickup_date) {
            return false;
        }

        $todayManila = ($now ?? \Carbon\Carbon::now('Asia/Manila'))->toDateString();

        return $this->pickup_date->toDateString() === $todayManila;
    }

    public function canConvertToDelivery(?\Carbon\Carbon $now = null): bool
    {
        return in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_READY], true)
            && $this->order_id === null
            && ($this->fulfillment_type ?? self::FULFILLMENT_PICKUP) !== self::FULFILLMENT_DELIVERY
            && $this->isPickupDay($now);
    }

    public function productSubtotal(): float
    {
        return round((int) $this->quantity * (float) $this->unit_price, 2);
    }

    public function canConfirmRefund(): bool
    {
        return $this->status === self::STATUS_CANCELLED
            && $this->refund_status === self::REFUND_PENDING
            && (float) $this->refund_amount > 0;
    }

    public function hasCancellationSnapshot(): bool
    {
        return $this->status === self::STATUS_CANCELLED
            && ($this->cancellation_initiated_by !== null || $this->refund_status !== null);
    }

    public function cancellationInitiatorLabel(): ?string
    {
        return match ($this->cancellation_initiated_by) {
            self::CANCELLED_BY_CUSTOMER => 'Customer-Initiated',
            self::CANCELLED_BY_BUSINESS => 'Business-Initiated (Unable to Fulfill)',
            default => null,
        };
    }
}
