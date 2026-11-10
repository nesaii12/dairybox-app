<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RiderRemittance extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'rider_id',
        'admin_id',
        'orders_count',
        'expected_amount',
        'actual_amount',
        'variance',
        'status',
        'payment_method',
        'notes',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'variance' => 'decimal:2',
            'orders_count' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'remittance_orders', 'remittance_id', 'order_id')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(CashLedgerEntry::class, 'remittance_id');
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_verification';
    }

    public static function generateReferenceNo(): string
    {
        $prefix = 'REM-' . date('Ymd');
        $last = static::where('reference_no', 'like', $prefix . '-%')
            ->orderBy('id', 'desc')
            ->first();

        if ($last) {
            $lastNum = (int) substr($last->reference_no, -4);
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }

        return $prefix . '-' . $newNum;
    }
}

