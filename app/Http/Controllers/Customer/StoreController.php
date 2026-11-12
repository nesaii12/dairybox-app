<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::all();

        // Determine available active, in-stock, non-expired products for availability checks (exclude missing/unlabelled expiry)
        $allActiveProducts = Product::where('status', 'active')
            ->whereNotNull('expiration_date')
            ->get();
        $availableProducts = $allActiveProducts->filter(function ($p) {
            $expired = $p->expiration_date && \Carbon\Carbon::parse($p->expiration_date)->startOfDay()->lt(now()->startOfDay());
            return !$expired && $p->stock > 0;
        });

        // Available category IDs
        $availableCategoryIds = $availableProducts->pluck('category_id')->unique()->values()->all();

        // Group variants by normalized product_name so each product card knows which milk_types & sizes are available
        $productVariantsMap = [];
        foreach ($allActiveProducts as $p) {
            $key = strtolower(trim($p->product_name));
            $expired = $p->expiration_date && \Carbon\Carbon::parse($p->expiration_date)->startOfDay()->lt(now()->startOfDay());
            $isAvail = !$expired && $p->stock > 0;
            $productVariantsMap[$key][] = [
                'id' => $p->id,
                'milk_type' => $p->milk_type,
                'size' => $p->size,
                'price' => (float) $p->price,
                'stock' => (int) $p->stock,
                'available' => $isAvail,
                'url' => route('customer.product', $p->id),
            ];
        }
        
        $query = Product::where('status', 'active')
            ->whereNotNull('expiration_date')
            ->withCount('ratings')
            ->withAvg('ratings', 'rating');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('milk_type')) {
            $query->where('milk_type', $request->milk_type);
        }

        if ($request->filled('size')) {
            $query->where('size', $request->size);
        }

        // Priority sorting: 1. Near Expiry, 2. Not Expired, 3. Expired, 4. Out of Stock
        $today = now()->toDateString();
        $sevenDaysOut = now()->addDays(7)->toDateString();
        $query->orderByRaw("
            CASE
                WHEN stock > 0 AND expiration_date IS NOT NULL AND DATE(expiration_date) >= ? AND DATE(expiration_date) <= ? THEN 1
                WHEN stock > 0 AND (expiration_date IS NULL OR DATE(expiration_date) > ?) THEN 2
                WHEN stock > 0 AND expiration_date IS NOT NULL AND DATE(expiration_date) < ? THEN 3
                ELSE 4
            END ASC
        ", [$today, $sevenDaysOut, $sevenDaysOut, $today]);

        // Secondary sorting
        if ($request->filled('sort')) {
            if ($request->sort === 'price_asc') {
                $query->orderBy('price', 'asc');
            } elseif ($request->sort === 'price_desc') {
                $query->orderBy('price', 'desc');
            } else {
                $query->orderByRaw("CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END ASC")
                      ->orderBy('expiration_date', 'asc')
                      ->latest();
            }
        } else {
            $query->orderByRaw("CASE WHEN expiration_date IS NULL THEN 1 ELSE 0 END ASC")
                  ->orderBy('expiration_date', 'asc')
                  ->latest();
        }

        $products = $query->paginate(9)->withQueryString();

        $cartQuantities = [];
        if (auth()->check()) {
            if ($request->boolean('new_batch')) {
                session()->forget('active_cart_batch_id');
            } elseif ($request->filled('batch')) {
                $requestedBatch = \App\Models\Cart::where('id', (int) $request->batch)
                    ->where('customer_id', auth()->id())
                    ->first();
                if ($requestedBatch) {
                    session(['active_cart_batch_id' => $requestedBatch->id]);
                }
            }

            $cartIds = \App\Models\Cart::where('customer_id', auth()->id())->pluck('id');
            if ($cartIds->isNotEmpty()) {
                $cartQuantities = \App\Models\CartItem::whereIn('cart_id', $cartIds)
                    ->selectRaw('product_id, SUM(quantity) as total_quantity')
                    ->groupBy('product_id')
                    ->pluck('total_quantity', 'product_id')
                    ->map(fn($qty) => (int) $qty)
                    ->toArray();
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('customer.partials.product_grid', compact('products', 'cartQuantities', 'productVariantsMap'))->render()
            ]);
        }

        return view('customer.catalog', compact('products', 'categories', 'cartQuantities', 'availableCategoryIds', 'productVariantsMap'));
    }

    public function show(Product $product)
    {
        if ($product->status !== 'active' || is_null($product->expiration_date)) {
            abort(404, 'Product not found or unavailable for purchase.');
        }

        $cartItemQuantity = 0;
        if (auth()->check()) {
            $cartIds = \App\Models\Cart::where('customer_id', auth()->id())->pluck('id');
            if ($cartIds->isNotEmpty()) {
                $cartItemQuantity = (int) \App\Models\CartItem::whereIn('cart_id', $cartIds)
                    ->where('product_id', $product->id)
                    ->sum('quantity');
            }
        }

        // Load all active variants of this product with verified expiration info
        $siblingVariants = Product::where('status', 'active')
            ->whereNotNull('expiration_date')
            ->whereRaw('LOWER(TRIM(product_name)) = ?', [strtolower(trim($product->product_name))])
            ->get();

        // Load reviews/ratings with customer names
        $reviews = $product->ratings()->with('customer')->latest()->get();
        $avgRating = $product->ratings()->avg('rating') ?: 0;

        $hasPurchased = false;
        $userRating = null;
        if (auth()->check()) {
            $hasPurchased = \App\Models\Order::where('customer_id', auth()->id())
                ->where('order_status', '!=', 'Cancelled')
                ->whereHas('orderItems', function ($q) use ($product) {
                    $q->where('product_id', $product->id);
                })
                ->exists();

            $userRating = \App\Models\Rating::where('customer_id', auth()->id())
                ->where('product_id', $product->id)
                ->first();
        }

        return view('customer.product', compact('product', 'reviews', 'avgRating', 'cartItemQuantity', 'siblingVariants', 'hasPurchased', 'userRating'));
    }
}
