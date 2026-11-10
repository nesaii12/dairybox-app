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
        'flavor',
        'size',
        'price',
        'image',
        'stock',
        'manufacturing_date',
        'expiration_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'manufacturing_date' => 'date',
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
     * Get predefined flavor presets with curated imagery and styling colors.
     */
    public static function getFlavorPresets(): array
    {
        return [
            ['name' => 'Chocolate', 'category' => 'Flavored Milk', 'image' => 'products/presets/chocolate_milk.png', 'color' => '#845339'],
            ['name' => 'Strawberry', 'category' => 'Flavored Milk', 'image' => 'products/presets/strawberry_milk.png', 'color' => '#f43f5e'],
            ['name' => 'Vanilla', 'category' => 'Flavored Milk', 'image' => 'products/presets/vanilla_milk.jpg', 'color' => '#eab308'],
            ['name' => 'Ube', 'category' => 'Flavored Milk', 'image' => 'products/presets/ube_milk.jpg', 'color' => '#9333ea'],
            ['name' => 'Melon', 'category' => 'Flavored Milk', 'image' => 'products/presets/melon_milk.jpg', 'color' => '#22c55e'],
            ['name' => 'Matcha', 'category' => 'Flavored Milk', 'image' => 'products/presets/matcha_milk.jpg', 'color' => '#16a34a'],
            ['name' => 'Taro', 'category' => 'Flavored Milk', 'image' => 'products/presets/taro_milk.jpg', 'color' => '#a855f7'],
            ['name' => 'Caramel', 'category' => 'Flavored Milk', 'image' => 'products/presets/caramel_milk.jpg', 'color' => '#d97706'],
            ['name' => 'Mango', 'category' => 'Ice Cream', 'image' => 'products/presets/mango_ice_cream.png', 'color' => '#f59e0b'],
            ['name' => 'Cheese', 'category' => 'Ice Cream', 'image' => 'products/presets/cheese_ice_cream.jpg', 'color' => '#eab308'],
            ['name' => 'Cookies & Cream', 'category' => 'Ice Cream', 'image' => 'products/presets/cookies_cream.jpg', 'color' => '#475569'],
            ['name' => 'Avocado', 'category' => 'Ice Cream', 'image' => 'products/presets/avocado_ice_cream.png', 'color' => '#84cc16'],
        ];
    }

    /**
     * Scope to filter active products only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Path to the JSON store for multi-select milk types and sizes.
     */
    protected static function optionsFilePath(): string
    {
        return storage_path('app/product_options.json');
    }

    public static function getAllOptionsStore(): array
    {
        $path = static::optionsFilePath();
        if (!file_exists($path)) {
            return [];
        }
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    public static function saveProductOptions(int $productId, array $milkTypes, array $sizes): void
    {
        $store = static::getAllOptionsStore();
        $store['products'][(string) $productId] = [
            'milk_types' => array_values(array_unique(array_intersect($milkTypes, ['Carabao', 'Cow']))),
            'sizes' => array_values(array_unique(array_intersect($sizes, ['Small', 'Medium', 'Large']))),
        ];
        file_put_contents(static::optionsFilePath(), json_encode($store, JSON_PRETTY_PRINT));
    }

    public static function deleteProductOptions(int $productId): void
    {
        $store = static::getAllOptionsStore();
        unset($store['products'][(string) $productId]);
        file_put_contents(static::optionsFilePath(), json_encode($store, JSON_PRETTY_PRINT));
    }

    public function getAvailableMilkTypesAttribute(): array
    {
        $store = static::getAllOptionsStore();
        $saved = $store['products'][(string) $this->id]['milk_types'] ?? null;
        if (!empty($saved) && is_array($saved)) {
            return $saved;
        }
        return $this->milk_type ? [$this->milk_type] : ['Cow'];
    }

    public function getAvailableSizesAttribute(): array
    {
        $store = static::getAllOptionsStore();
        $saved = $store['products'][(string) $this->id]['sizes'] ?? null;
        if (!empty($saved) && is_array($saved)) {
            return $saved;
        }
        return $this->size ? [$this->size] : ['Medium'];
    }

    public static function saveCartItemCustomization(int $cartItemId, string $milkType, string $size): void
    {
        $store = static::getAllOptionsStore();
        $store['cart_items'][(string) $cartItemId] = [
            'milk_type' => $milkType,
            'size' => $size,
        ];
        file_put_contents(static::optionsFilePath(), json_encode($store, JSON_PRETTY_PRINT));
    }

    public static function getCartItemCustomization(int $cartItemId, ?self $product = null): array
    {
        $store = static::getAllOptionsStore();
        $saved = $store['cart_items'][(string) $cartItemId] ?? null;
        return [
            'milk_type' => $saved['milk_type'] ?? ($product?->milk_type ?? 'Cow'),
            'size' => $saved['size'] ?? ($product?->size ?? 'Medium'),
        ];
    }

    public static function saveOrderItemCustomization(int $orderItemId, string $milkType, string $size): void
    {
        $store = static::getAllOptionsStore();
        $store['order_items'][(string) $orderItemId] = [
            'milk_type' => $milkType,
            'size' => $size,
        ];
        file_put_contents(static::optionsFilePath(), json_encode($store, JSON_PRETTY_PRINT));
    }

    public static function getOrderItemCustomization(int $orderItemId, ?self $product = null): array
    {
        $store = static::getAllOptionsStore();
        $saved = $store['order_items'][(string) $orderItemId] ?? null;
        return [
            'milk_type' => $saved['milk_type'] ?? ($product?->milk_type ?? 'Cow'),
            'size' => $saved['size'] ?? ($product?->size ?? 'Medium'),
        ];
    }
}
