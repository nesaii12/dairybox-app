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
        'reserved_stock',
        'manufacturing_date',
        'expiration_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'reserved_stock' => 'integer',
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
     * Get the reservations for this product.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Computed available stock (Physical Stock - Reserved Stock).
     */
    public function getAvailableStockAttribute(): int
    {
        return max(0, (int) ($this->attributes['stock'] ?? 0) - (int) ($this->attributes['reserved_stock'] ?? 0));
    }

    /**
     * Check if the product has low available stock.
     */
    public function isLowStock(int $threshold = 10): bool
    {
        return $this->available_stock <= $threshold;
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

    protected static function booted(): void
    {
        static::saving(function ($product) {
            // A product without a valid expiration date cannot be set to Active
            if (empty($product->expiration_date)) {
                $product->status = 'inactive';
            }
        });
    }

    /**
     * Accessor: Ensure products without expiration date are always presented as inactive.
     */
    public function getStatusAttribute($value): string
    {
        if (empty($this->attributes['expiration_date'])) {
            return 'inactive';
        }
        return $value ?? 'inactive';
    }

    /**
     * Check if product has a valid expiration date assigned.
     */
    public function hasValidExpiration(): bool
    {
        return !empty($this->expiration_date);
    }

    /**
     * Scope to filter active products only (must have active status and valid expiration date).
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('expiration_date');
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

    /**
     * Resolve dynamic price based on the selected size.
     * Pricing rules:
     * - Milks (Strawberry, Fresh Milk, Mango, Milk Cheese, Pastillas, Pandan, Choco, Ube): Small 30, Medium 50, Large 70
     * - Ice Cream & Yogurt (Vanilla Ice Cream, Fresh Yogurt): Small 25, Medium 35, Large 50
     * - Any other product: Small automatically 30, else base product price.
     */
    public function getPriceForSize(?string $size = null): float
    {
        $normalizedSize = ucfirst(strtolower(trim($size ?? $this->size ?? 'Medium')));
        if ($normalizedSize === 'Mid') {
            $normalizedSize = 'Medium';
        }

        $name = strtolower(trim($this->product_name));
        $categoryName = $this->category ? strtolower(trim($this->category->category_name)) : '';

        // 1. Vanilla Ice-cream & Fresh Yogurt: Small 25, Medium 35, Large 50
        if (
            str_contains($name, 'ice cream') || 
            str_contains($name, 'ice-cream') || 
            str_contains($name, 'yogurt') || 
            in_array($categoryName, ['ice cream', 'yogurt'])
        ) {
            return match ($normalizedSize) {
                'Small' => 25.00,
                'Large' => 50.00,
                default => 35.00, // Medium / Mid
            };
        }

        // 2. All Milks (Pasteurized Milk drinks, Fresh Milk, etc.): Small 30, Medium 50, Large 70
        if (str_contains($name, 'milk') || $categoryName === 'milk') {
            return match ($normalizedSize) {
                'Small' => 30.00,
                'Large' => 70.00,
                default => 50.00, // Medium / Mid
            };
        }

        // 3. Fallback for any product: if customer chooses Small, automatically fixed as 30
        return match ($normalizedSize) {
            'Small' => 30.00,
            default => (float) $this->price,
        };
    }

    public function getAvailableSizesAttribute(): array
    {
        $name = strtolower(trim($this->product_name));
        $categoryName = $this->category ? strtolower(trim($this->category->category_name)) : '';

        // Products with standard tiered sizing (Milks, Ice Cream, Yogurt) support Small, Medium, Large
        if (
            str_contains($name, 'milk') || 
            str_contains($name, 'ice') || 
            str_contains($name, 'yogurt') || 
            in_array($categoryName, ['milk', 'ice cream', 'yogurt'])
        ) {
            return ['Small', 'Medium', 'Large'];
        }

        $store = static::getAllOptionsStore();
        $saved = $store['products'][(string) $this->id]['sizes'] ?? null;
        if (!empty($saved) && is_array($saved) && count($saved) > 0) {
            return $saved;
        }

        return $this->size ? [$this->size] : ['Small', 'Medium', 'Large'];
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
