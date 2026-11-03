<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;

class AppNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'link',
        'is_read',
    ];

    protected $appends = [
        'target_url',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * Get the user this notification belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (AppNotification $notification) {
            try {
                broadcast(new \App\Events\NotificationSent($notification));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Notification broadcast failed: ' . $e->getMessage());
            }
        });
    }

    /**
     * Dynamic target URL accessor.
     * Uses explicit link if set; otherwise smartly extracts the designated record
     * from order numbers, product names, or alert context according to the user's role.
     * Always returns relative paths so it stays on the user's active host and port.
     */
    public function getTargetUrlAttribute(): string
    {
        $rawUrl = $this->computeRawTargetUrl();
        return $this->normalizeToRelativePath($rawUrl);
    }

    protected function computeRawTargetUrl(): string
    {
        // 1. If an explicit link is stored on this notification, use it and ensure highlight param
        if (!empty($this->link)) {
            return $this->appendHighlightToUrl($this->link);
        }

        $user = $this->user ?? auth()->user();
        $role = $user?->role ?? 'customer';
        $fullText = $this->title . ' ' . $this->message;

        // 2. Try to match an order reference (e.g., DB-20261024-0001 or ORD-XXXXXXXX or #ORD-...)
        if (preg_match('/(DB-\d{8}-\d{4}|ORD-[A-Za-z0-9_-]+)/i', $fullText, $matches)) {
            $orderNumber = $matches[1];
            $order = Order::where('order_number', $orderNumber)->first();
            if ($order) {
                return $this->resolveOrderUrl($order, $role);
            }
        } elseif (preg_match('/(?:order\s*#?\s*)([A-Za-z0-9-]+)/i', $fullText, $matches)) {
            $potentialNum = trim($matches[1]);
            $order = Order::where('order_number', $potentialNum)->first();
            if ($order) {
                return $this->resolveOrderUrl($order, $role);
            }
        }

        // 3. Try to match a product name in quotes or message (e.g., 'Chocolate Carabao Milk (Small)')
        $product = $this->extractProductFromText($fullText);

        // 4. Expiration or Near-Expiry Alerts
        if (stripos($fullText, 'expir') !== false) {
            $params = $product ? ['highlight' => 'product-' . $product->id] : [];
            if ($role === 'admin') {
                return route('admin.products.index', $params);
            }
            return route('customer.catalog', $params);
        }

        // 5. Low Stock Alerts
        if (stripos($fullText, 'low stock') !== false || stripos($fullText, 'stock') !== false) {
            $params = $product ? ['highlight' => 'product-' . $product->id] : [];
            if ($role === 'admin') {
                return route('admin.inventory.index', $params);
            }
            return route('customer.catalog', $params);
        }

        // 6. Delivery Arrival & Monitoring
        if (stripos($fullText, 'arrival') !== false || stripos($fullText, 'delivery') !== false || stripos($fullText, 'rider') !== false) {
            if ($role === 'admin') {
                return route('admin.deliveries.index');
            } elseif ($role === 'rider') {
                return route('rider.dashboard');
            } else {
                return route('customer.orders.index');
            }
        }

        // 7. Restriction & Suspension Alerts
        if (stripos($fullText, 'restrict') !== false || stripos($fullText, 'suspend') !== false) {
            if ($role === 'admin') {
                return route('admin.customers.index');
            }
            return route('customer.orders.index');
        }

        // 8. Role-based fallback
        if ($role === 'admin') {
            return route('admin.dashboard');
        } elseif ($role === 'rider') {
            return route('rider.dashboard');
        }

        return route('customer.orders.index');
    }

    /**
     * Smartly match a product referenced in notification text.
     */
    protected function extractProductFromText(string $text): ?Product
    {
        // Check single quotes first: 'Chocolate Carabao Milk (Small)' or 'Chocolate Carabao Milk'
        if (preg_match("/'([^']+)'/", $text, $matches)) {
            $raw = trim($matches[1]);
            $name = $raw;
            $size = null;
            if (preg_match('/^(.*?)\s*\((.*?)\)$/', $raw, $sm)) {
                $name = trim($sm[1]);
                $size = trim($sm[2]);
            }

            $query = Product::where('product_name', 'like', "%{$name}%");
            if ($size) {
                $query->where('size', $size);
            }
            $p = $query->first();
            if ($p) {
                return $p;
            }
        }

        // Check product names without quotes
        $products = Product::all();
        foreach ($products as $p) {
            if (stripos($text, $p->product_name) !== false) {
                if ($p->size && stripos($text, $p->size) !== false) {
                    return $p;
                }
            }
        }
        foreach ($products as $p) {
            if (stripos($text, $p->product_name) !== false) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Ensure a target URL has a highlight parameter if one can be inferred.
     */
    protected function appendHighlightToUrl(string $url): string
    {
        if (str_contains($url, 'highlight=')) {
            return $url;
        }

        // Order URL: /orders/63 -> ?highlight=order-63
        if (preg_match('#/orders/(\d+)#', $url, $m)) {
            $sep = str_contains($url, '?') ? '&' : '?';
            return $url . $sep . 'highlight=order-' . $m[1];
        }

        // Product URL: /products/1 -> ?highlight=product-1
        if (preg_match('#/products/(\d+)#', $url, $m)) {
            $sep = str_contains($url, '?') ? '&' : '?';
            return $url . $sep . 'highlight=product-' . $m[1];
        }

        // Check if an order reference is present in message
        $fullText = $this->title . ' ' . $this->message;
        if (preg_match('/(DB-\d{8}-\d{4}|ORD-[A-Za-z0-9_-]+)/i', $fullText, $m)) {
            $sep = str_contains($url, '?') ? '&' : '?';
            return $url . $sep . 'highlight=' . $m[1];
        }

        // Check product reference
        $product = $this->extractProductFromText($fullText);
        if ($product) {
            $sep = str_contains($url, '?') ? '&' : '?';
            return $url . $sep . 'highlight=product-' . $product->id;
        }

        return $url;
    }

    /**
     * Normalize absolute URLs to relative paths so they never break when accessed
     * across different hosts/ports (e.g. localhost, 127.0.0.1:8000, or LAN IP).
     */
    protected function normalizeToRelativePath(?string $url): string
    {
        if (empty($url)) {
            return '/';
        }

        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return '/' . ltrim($url, '/');
        }

        $path = $parsed['path'] ?? '/';
        if (!empty($parsed['query'])) {
            $path .= '?' . $parsed['query'];
        }

        return $path;
    }

    /**
     * Resolve target URL for a matched Order based on user role and context.
     */
    protected function resolveOrderUrl(Order $order, string $role): string
    {
        if ($role === 'admin') {
            // If it's a post-arrival cancellation incident or delivery event, route to delivery monitoring or order show
            if (stripos($this->title, 'incident') !== false || stripos($this->title, 'arrival') !== false) {
                return route('admin.deliveries.index', ['search' => $order->order_number, 'highlight' => 'order-' . $order->id]);
            }
            return route('admin.orders.show', ['order' => $order->id, 'highlight' => 'order-' . $order->id]);
        } elseif ($role === 'rider') {
            return route('rider.dashboard', ['highlight' => 'order-' . $order->id]);
        }

        // Default for customer: their individual order tracking screen
        return route('customer.orders.show', ['order' => $order->id, 'highlight' => 'order-' . $order->id]);
    }
}
