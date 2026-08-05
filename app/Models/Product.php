<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_name',
        'description',
        'milk_type',
        'size',
        'price',
        'image',
        'stock',
        'expiration_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'expiration_date' => 'date',
        ];
    }

    /**
     * Get the category this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the inventory batches for this product.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the ratings for this product.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the forecasts for this product.
     */
    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class);
    }

    /**
     * Check if the product has low stock.
     */
    public function isLowStock(int $threshold = 10): bool
    {
        return $this->stock <= $threshold;
    }

    /**
     * Check if the product is near expiration.
     */
    public function isNearExpiration(int $days = 7): bool
    {
        return $this->expiration_date
            && $this->expiration_date->diffInDays(now()) <= $days
            && $this->expiration_date->isFuture();
    }

    /**
     * Get the average rating for this product.
     */
    public function averageRating(): float
    {
        return $this->ratings()->avg('rating') ?? 0.0;
    }

    /**
     * Scope to filter active products only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
