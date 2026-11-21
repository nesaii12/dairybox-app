<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_CONFIRMED = 'Confirmed';
    public const STATUS_READY = 'Ready for Pickup';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_READY,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const ALLOCATED_STATUSES = [
        self::STATUS_CONFIRMED,
        self::STATUS_READY,
    ];

    public const PAYMENT_UNPAID = 'Unpaid';
    public const PAYMENT_PARTIAL = 'Partially Paid';
    public const PAYMENT_PAID = 'Fully Paid';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_UNPAID,
        self::PAYMENT_PARTIAL,
        self::PAYMENT_PAID,
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
        'down_payment',
        'remaining_balance',
        'payment_status',
        'status',
        'reservation_date',
        'pickup_date',
        'notes',
        'order_id',
        'created_by',
        'confirmed_at',
        'ready_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'allocated_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'reservation_date' => 'date',
            'pickup_date' => 'date',
            'confirmed_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
        return !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
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
}
