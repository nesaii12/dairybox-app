<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
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

        // Verify if user already rated this product to prevent duplicate reviews
        $exists = Rating::where('customer_id', auth()->id())
            ->where('product_id', $product->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'You have already submitted a review for this product.');
        }

        Rating::create([
            'customer_id' => auth()->id(),
            'product_id' => $product->id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return redirect()->back()->with('success', 'Thank you! Your product review has been submitted.');
    }
}
