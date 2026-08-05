@extends('layouts.customer')

@section('title', 'My Shopping Cart')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-black text-slate-900">Shopping Cart</h2>
    <p class="text-xs text-slate-500">Review your selected items and adjust quantities before placing order.</p>
</div>

@if($items->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 p-12 text-center shadow-sm max-w-lg mx-auto mt-12">
        <i class="bi bi-cart-x text-5xl text-slate-300 mb-4 block"></i>
        <h3 class="text-base font-bold text-slate-800">Your Cart is Empty</h3>
        <p class="text-xs text-slate-500 mt-1">Explore our delicious milk flavors and ice creams to add them here.</p>
        <a href="{{ route('customer.catalog') }}" class="mt-6 inline-flex items-center space-x-2 py-2.5 px-5 rounded-xl text-xs font-bold text-white bg-sky-500 hover:bg-sky-600 transition-all shadow-md shadow-sky-100">
            <i class="bi bi-shop"></i>
            <span>Browse Products</span>
        </a>
    </div>
@else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Items List (Left Columns) -->
        <div class="lg:col-span-2 space-y-4">
            @foreach($items as $item)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Product Thumb & Names -->
                    <div class="flex items-center space-x-4 w-full sm:w-auto">
                        <div class="h-16 w-16 rounded-xl overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                            @if($item->product->image)
                                <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->product_name }}" class="h-full w-full object-cover">
                            @else
                                <i class="bi bi-image text-slate-300 text-xl"></i>
                            @endif
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 hover:text-sky-600">
                                <a href="{{ route('customer.product', $item->product->id) }}">{{ $item->product->product_name }}</a>
                            </h4>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                <span class="bg-slate-50 border border-slate-100 text-slate-600 text-[9px] px-2 py-0.5 rounded font-bold uppercase">{{ $item->product->milk_type }} Milk</span>
                                <span class="bg-slate-50 border border-slate-100 text-slate-600 text-[9px] px-2 py-0.5 rounded font-bold uppercase">Size: {{ $item->product->size }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Adjust Controls -->
                    <div class="flex items-center justify-between sm:justify-start gap-6 w-full sm:w-auto border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-50">
                        <div class="text-left sm:text-right">
                            <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider">Unit Price</span>
                            <span class="text-sm font-bold text-slate-900">₱{{ number_format($item->product->price, 2) }}</span>
                        </div>
                        
                        <!-- Form with inline post submission -->
                        <form action="{{ route('customer.cart.update', $item->id) }}" method="POST" class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50 w-28 justify-between">
                            @csrf
                            <button type="submit" name="quantity" value="{{ $item->quantity - 1 }}" {{ $item->quantity <= 1 ? 'disabled' : '' }}
                                class="px-2.5 py-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 transition-colors">
                                <i class="bi bi-dash"></i>
                            </button>
                            <span class="text-xs font-bold text-slate-900">{{ $item->quantity }}</span>
                            <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}" {{ $item->product->stock <= $item->quantity ? 'disabled' : '' }}
                                class="px-2.5 py-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 transition-colors">
                                <i class="bi bi-plus"></i>
                            </button>
                        </form>

                        <div class="text-right">
                            <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider">Subtotal</span>
                            <span class="text-sm font-black text-slate-950">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>

                        <!-- Delete button -->
                        <form action="{{ route('customer.cart.remove', $item->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all" title="Remove Item">
                                <i class="bi bi-trash3 text-base"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Order Summary Column (Right) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-24">
                <h3 class="text-base font-bold text-slate-950 mb-4 pb-3 border-b border-slate-50">Order Summary</h3>
                
                <div class="space-y-3 mb-6">
                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <span>Items Subtotal</span>
                        <span class="font-semibold text-slate-900">₱{{ number_format($total, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <span>Delivery Fee</span>
                        <span class="font-semibold text-slate-900 italic">Calculated at next step</span>
                    </div>
                </div>

                <div class="border-t border-slate-50 pt-4 mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900">Subtotal Amount</span>
                        <span class="text-xl font-black text-sky-600">₱{{ number_format($total, 2) }}</span>
                    </div>
                </div>

                <a href="{{ route('customer.checkout.index') }}" class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-sm font-black text-white bg-sky-500 hover:bg-sky-600 shadow-md shadow-sky-100 transition-all hover:-translate-y-0.5">
                    <span>Proceed to Checkout</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
                
                <div class="mt-4 text-[10px] text-slate-400 text-center font-medium">
                    Prices are tax inclusive. Double check items before continuing.
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
