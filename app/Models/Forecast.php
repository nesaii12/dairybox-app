<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Forecast extends Model
{
    protected $fillable = [
        'product_id',
        'forecast_month',
        'predicted_sales',
    ];

    protected function casts(): array
    {
        return [
            'predicted_sales' => 'decimal:2',
        ];
    }

    /**
     * Get the product for this forecast.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
