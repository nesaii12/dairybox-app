<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'stock_in',
        'stock_out',
        'remaining_stock',
        'expiration_date',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
        ];
    }

    /**
     * Get the product this inventory batch belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get batches with remaining stock, ordered FIFO.
     */
    public function scopeAvailableFifo($query)
    {
        return $query->where('remaining_stock', '>', 0)->orderBy('created_at', 'asc');
    }
}
