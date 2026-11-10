<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Product;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:500',
        ]);

        // Verify that the customer has purchased this product in a non-cancelled order
        $hasPurchased = Order::where('customer_id', auth()->id())
            ->where('order_status', '!=', 'Cancelled')
            ->whereHas('orderItems', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->exists();

        if (!$hasPurchased) {
            return redirect()->back()->with('error', 'You can only rate and review products you have purchased.');
        }

        $existing = Rating::where('customer_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        Rating::updateOrCreate(
            [
                'customer_id' => auth()->id(),
                'product_id' => $product->id,
            ],
            [
                'rating' => (int) $request->rating,
                'review' => $request->filled('review') ? trim($request->review) : null,
            ]
        );

        $message = $existing
            ? 'Your product rating and feedback have been updated.'
            : 'Thank you! Your product rating and feedback have been submitted.';

        return redirect()->back()->with('success', $message);
    }
}
