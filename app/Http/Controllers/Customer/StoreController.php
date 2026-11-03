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
        
        $query = Product::where('status', 'active');

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

        // Sorting
        if ($request->filled('sort')) {
            if ($request->sort === 'price_asc') {
                $query->orderBy('price', 'asc');
            } elseif ($request->sort === 'price_desc') {
                $query->orderBy('price', 'desc');
            } else {
                $query->latest();
            }
        } else {
            $query->latest();
        }

        $products = $query->paginate(9)->withQueryString();

        $cartQuantities = [];
        if (auth()->check()) {
            $cart = \App\Models\Cart::where('customer_id', auth()->id())->first();
            if ($cart) {
                $cartQuantities = $cart->cartItems->pluck('quantity', 'product_id')->toArray();
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('customer.partials.product_grid', compact('products', 'cartQuantities'))->render()
            ]);
        }

        return view('customer.catalog', compact('products', 'categories', 'cartQuantities'));
    }

    public function show(Product $product)
    {
        if ($product->status !== 'active') {
            abort(404, 'Product not found.');
        }

        $cartItemQuantity = 0;
        if (auth()->check()) {
            $cart = \App\Models\Cart::where('customer_id', auth()->id())->first();
            $cartItem = $cart?->cartItems()->where('product_id', $product->id)->first();
            $cartItemQuantity = $cartItem ? $cartItem->quantity : 0;
        }

        // Load reviews/ratings with customer names
        $reviews = $product->ratings()->with('customer')->latest()->get();
        $avgRating = $product->ratings()->avg('rating') ?: 0;

        return view('customer.product', compact('product', 'reviews', 'avgRating', 'cartItemQuantity'));
    }
}
