<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = [
        'customer_id',
        'product_id',
        'rating',
        'review',
    ];

    /**
     * Get the customer who submitted this rating.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the product being rated.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
