<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'total_amount',
        'sales_date',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'sales_date' => 'date',
        ];
    }

    /**
     * Get the order this sale was generated from.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
