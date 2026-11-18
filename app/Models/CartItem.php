<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Get the dynamic unit price for this cart item based on customized size.
     */
    public function getUnitPriceAttribute(): float
    {
        if ($this->quantity > 0 && (float) $this->subtotal > 0) {
            return (float) $this->subtotal / $this->quantity;
        }

        $custom = Product::getCartItemCustomization($this->id, $this->product);
        return $this->product ? $this->product->getPriceForSize($custom['size'] ?? 'Medium') : 0.00;
    }

    /**
     * Get the cart this item belongs to.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product for this cart item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
