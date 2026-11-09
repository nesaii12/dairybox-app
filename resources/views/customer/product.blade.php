@extends('layouts.customer')

@section('title', $product->product_name)

@section('content')
<div class="mb-6">
    <a href="{{ route('customer.catalog') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors font-semibold">
        <i class="bi bi-arrow-left mr-1"></i> Back to Catalog
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
    <!-- Left Panel: Product Media -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 flex flex-col items-center justify-center shadow-sm">
        <div class="w-full h-80 rounded-2xl overflow-hidden bg-slate-50 border border-slate-100/50 flex items-center justify-center shadow-inner mb-6">
            @if($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" id="productMainImage" alt="{{ $product->product_name }}" class="h-full w-full object-cover product-main-img">
            @else
                <i class="bi bi-image text-slate-300 text-6xl"></i>
            @endif
        </div>
        
        <!-- Ratings Snapshot -->
        <div class="w-full border-t border-slate-100 pt-4 flex items-center justify-between px-2">
            <div class="flex items-center space-x-2">
                <!-- Stars -->
                <div class="flex text-amber-400">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= round($avgRating))
                            <i class="bi bi-star-fill text-sm"></i>
                        @else
                            <i class="bi bi-star text-sm text-slate-300"></i>
                        @endif
                    @endfor
                </div>
                <span class="text-sm font-bold text-slate-800">{{ number_format($avgRating, 1) }} / 5.0</span>
            </div>
            <span class="text-xs text-slate-400 font-semibold">{{ $reviews->count() }} reviews logged</span>
        </div>
    </div>

    <!-- Right Panel: Buying Panel -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-sm flex flex-col justify-between">
        <div>
            <!-- Breadcrumbs / Meta -->
            <div class="flex items-center justify-between">
                <span class="bg-sky-50 text-sky-700 text-xs px-2.5 py-0.5 rounded-full border border-sky-100 font-bold uppercase tracking-wider">
                    {{ $product->category->category_name }}
                </span>
                
                @if($product->stock > 0 && (!$product->expiration_date || \Carbon\Carbon::parse($product->expiration_date)->startOfDay()->gte(now()->startOfDay())))
                    <span class="inline-flex items-center text-xs font-bold text-emerald-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                        {{ $product->stock }} Units Available
                    </span>
                @else
                    <span class="inline-flex items-center text-xs font-bold text-rose-600">
                        Out of Stock
                    </span>
                @endif
            </div>

            <!-- Title & Price -->
            <h2 class="text-2xl font-black text-slate-900 mt-4 leading-tight font-serif">{{ $product->product_name }}</h2>
            <div class="mt-2 text-2xl font-black text-slate-950">₱{{ number_format($product->price, 2) }}</div>

            <!-- Description -->
            <div class="mt-6 border-t border-slate-100 pt-6">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Description</h4>
                <p class="text-sm text-slate-600 leading-relaxed">{{ $product->description ? $product->description : 'Fresh, premium quality product processed directly from dairy sources.' }}</p>
            </div>

            <!-- Specs & Freshness Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-6">
                @if($product->flavor)
                <div class="p-3.5 bg-sky-50/60 border border-sky-100 rounded-2xl">
                    <span class="block text-[9px] font-bold text-sky-700 uppercase tracking-wider flex items-center gap-1">
                        <i class="bi bi-tag-fill text-[9px]"></i> Flavor
                    </span>
                    <span class="text-xs font-bold text-sky-950">{{ $product->flavor }}</span>
                </div>
                @endif
                <div class="p-3.5 bg-slate-50 border border-slate-100 rounded-2xl">
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider">Milk Source</span>
                    <span class="text-xs font-bold text-slate-800">{{ $product->milk_type }} Milk</span>
                </div>
                <div class="p-3.5 bg-slate-50 border border-slate-100 rounded-2xl">
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider">Container Size</span>
                    <span class="text-xs font-bold text-slate-800">{{ $product->size }}</span>
                </div>
                @if($product->manufacturing_date)
                <div class="p-3.5 bg-slate-50 border border-slate-100 rounded-2xl">
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="bi bi-calendar-check text-sky-500"></i> Manufactured
                    </span>
                    <span class="text-xs font-bold text-slate-800">{{ \Carbon\Carbon::parse($product->manufacturing_date)->format('M d, Y') }}</span>
                </div>
                @endif
                @if($product->expiration_date)
                @php
                    $isNearExpiry = \Carbon\Carbon::parse($product->expiration_date)->isFuture() && \Carbon\Carbon::parse($product->expiration_date)->diffInDays(now()) <= 7;
                @endphp
                <div class="p-3.5 bg-slate-50 border border-slate-100 rounded-2xl">
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="bi bi-clock-history {{ $isNearExpiry ? 'text-amber-500' : 'text-emerald-500' }}"></i> Best Before / Exp
                    </span>
                    <span class="text-xs font-bold {{ $isNearExpiry ? 'text-amber-700' : 'text-slate-800' }}">
                        {{ \Carbon\Carbon::parse($product->expiration_date)->format('M d, Y') }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Purchase Action Form -->
        <div class="mt-8 pt-6 border-t border-slate-100">
            @php
                $cartQty        = $cartItemQuantity ?? 0;
                $isExpired      = $product->expiration_date &&
                                  \Carbon\Carbon::parse($product->expiration_date)->startOfDay()->lt(now()->startOfDay());
                $effectiveStock = $isExpired ? 0 : $product->stock;
                $availableToAdd = max(0, $effectiveStock - $cartQty);
                $allStockInCart = ($cartQty >= $effectiveStock) && ($effectiveStock > 0);
            @endphp

            @if($isExpired)
                <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1 mb-3">
                    <div class="flex items-center gap-1.5 font-bold text-rose-900">
                        <i class="bi bi-x-circle-fill text-rose-600"></i>
                        <span>Product Unavailable</span>
                    </div>
                    <p class="text-[11px] leading-relaxed">This product has passed its expiration date and is no longer available for purchase.</p>
                </div>
                <button disabled class="w-full py-3 px-4 rounded-xl text-sm font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed text-center">
                    Out of Stock
                </button>
            @elseif($effectiveStock > 0)
                @auth
                    @if(auth()->user()->isRestricted())
                        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-rose-900">
                                <i class="bi bi-shield-slash-fill text-rose-600"></i>
                                <span>Ordering Privileges Restricted</span>
                            </div>
                            <p class="text-[11px] leading-relaxed">
                                Your account is temporarily restricted from placing orders until {{ auth()->user()->restricted_until->format('M d, Y h:i A') }} due to an order cancellation policy violation.
                            </p>
                        </div>
                        <button disabled class="w-full mt-3 py-3 px-4 rounded-xl text-sm font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed text-center flex items-center justify-center space-x-2">
                            <i class="bi bi-lock-fill"></i>
                            <span>Ordering Restricted</span>
                        </button>
                    @elseif($allStockInCart)
                        <div class="p-4 bg-sky-50/60 border border-sky-100 rounded-2xl text-xs text-sky-800 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-sky-900">
                                <i class="bi bi-cart-check-fill text-sky-600 text-sm"></i>
                                <span>All Available Units in Cart</span>
                            </div>
                            <p class="text-[11px] leading-relaxed">
                                You already have all {{ $product->stock }} available unit(s) of this item in your shopping cart.
                            </p>
                        </div>
                        <a href="{{ route('customer.cart.index') }}" class="w-full mt-3 py-3 px-4 rounded-xl text-sm font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 transition-all text-center flex items-center justify-center space-x-2">
                            <i class="bi bi-cart3"></i>
                            <span>View in Shopping Cart</span>
                        </a>
                    @else
                        <form action="{{ route('customer.cart.add', $product->id) }}" method="POST" class="space-y-4 add-to-cart-form" data-product-id="{{ $product->id }}" data-product-name="{{ $product->product_name }}" data-stock="{{ $product->stock }}" data-in-cart="{{ $cartQty }}">
                            @csrf
                            <div class="flex items-center justify-between">
                                <div>
                                    <label for="quantity" class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Order Quantity</label>
                                    @if($cartQty > 0)
                                        <span class="text-[10px] text-sky-600 font-semibold">{{ $cartQty }} already in cart ({{ $availableToAdd }} more available)</span>
                                    @endif
                                </div>
                                <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50 w-32 justify-between">
                                    <button type="button" onclick="decrementQty()" class="px-3 py-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer">
                                        <i class="bi bi-dash-lg"></i>
                                    </button>
                                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="{{ $availableToAdd }}" 
                                        class="w-10 text-center text-sm font-bold bg-transparent border-0 focus:outline-none focus:ring-0 text-slate-900 select-none">
                                    <button type="button" onclick="incrementQty()" class="px-3 py-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="add-to-cart-btn w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-[#0ea5e9] hover:bg-sky-600 transition-all shadow-md shadow-sky-100 cursor-pointer">
                                <i class="bi bi-cart-plus-fill text-base"></i>
                                <span>Add to Shopping Cart</span>
                            </button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-[#0ea5e9] hover:bg-sky-600 transition-all shadow-md shadow-sky-100 text-center cursor-pointer">
                        <i class="bi bi-cart-plus-fill text-base"></i>
                        <span>Sign in to Add to Cart</span>
                    </a>
                @endauth
            @else
                <button disabled class="w-full py-3 px-4 rounded-xl text-sm font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed text-center">
                    Out of Stock
                </button>
            @endif
        </div>
    </div>
</div>

<!-- Reviews Section -->
<div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
    <div class="border-b border-slate-100 pb-5 mb-6">
        <h3 class="text-sm font-bold text-slate-950 uppercase tracking-wider">Ratings & Customer Reviews</h3>
        <p class="text-xs text-slate-500 mt-0.5">Real customer feedback submitted after confirmed orders.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Reviews Breakdown Summary -->
        <div class="lg:col-span-1 bg-slate-50 border border-slate-100 p-6 rounded-2xl flex flex-col items-center justify-center text-center">
            <h4 class="text-5xl font-black text-slate-950 font-serif">{{ number_format($avgRating, 1) }}</h4>
            <div class="flex text-amber-400 my-2">
                @for($i = 1; $i <= 5; $i++)
                    @if($i <= round($avgRating))
                        <i class="bi bi-star-fill text-base"></i>
                    @else
                        <i class="bi bi-star text-base text-slate-300"></i>
                    @endif
                @endfor
            </div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Average based on {{ $reviews->count() }} ratings</p>
            <div class="mt-4 p-3 bg-white border border-slate-200/50 rounded-xl text-[10px] text-slate-500 font-medium leading-relaxed">
                Verified purchases only. You can leave reviews on completed orders in your orders tracker.
            </div>
        </div>

        <!-- Individual reviews list -->
        <div class="lg:col-span-2">
            <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2">
                @forelse($reviews as $rev)
                <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100/80 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-sky-500 to-sky-400 text-white flex items-center justify-center font-bold text-xs shadow-2xs shadow-sky-100/50">
                                {{ strtoupper(substr($rev->customer->name, 0, 1)) }}
                            </div>
                            <div>
                                <span class="block text-xs font-bold text-slate-900">{{ $rev->customer->name }}</span>
                                <span class="block text-[9px] text-slate-400 mt-0.5">{{ $rev->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                        <div class="flex text-amber-400 text-xs">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $rev->rating)
                                    <i class="bi bi-star-fill"></i>
                                @else
                                    <i class="bi bi-star text-slate-300"></i>
                                @endif
                            @endfor
                        </div>
                    </div>
                    @if($rev->review)
                        <p class="text-xs text-slate-600 mt-3 leading-relaxed bg-white border border-slate-100 p-2.5 rounded-xl shadow-2xs">
                            "{{ $rev->review }}"
                        </p>
                    @endif
                </div>
                @empty
                <div class="py-12 text-center">
                    <i class="bi bi-chat-square-dots text-3xl text-slate-200 block mb-2"></i>
                    <p class="text-xs text-slate-400 italic">No reviews recorded yet for this product.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const maxStock = {{ $availableToAdd ?? $product->stock }};
    const totalStock = {{ $product->stock }};
    const inCart = {{ $cartQty ?? 0 }};
    const qtyInput = document.getElementById('quantity');

    function incrementQty() {
        if (!qtyInput) return;
        let val = parseInt(qtyInput.value, 10) || 1;
        if (val < maxStock) {
            qtyInput.value = val + 1;
        } else {
            showStockLimitNotice();
        }
    }

    function decrementQty() {
        if (!qtyInput) return;
        let val = parseInt(qtyInput.value, 10) || 1;
        if (val > 1) {
            qtyInput.value = val - 1;
        }
    }

    if (qtyInput) {
        qtyInput.addEventListener('input', () => {
            let val = parseInt(qtyInput.value, 10);
            if (val > maxStock) {
                qtyInput.value = maxStock;
                showStockLimitNotice();
            } else if (val < 1 && qtyInput.value !== '') {
                qtyInput.value = 1;
            }
        });

        qtyInput.addEventListener('blur', () => {
            let val = parseInt(qtyInput.value, 10);
            if (isNaN(val) || val < 1) qtyInput.value = 1;
            if (val > maxStock) qtyInput.value = maxStock;
        });
    }

    function showStockLimitNotice() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Stock Limit',
                text: inCart > 0 
                    ? `You already have ${inCart} in your cart. Only ${maxStock} more unit(s) can be added (Total stock: ${totalStock}).` 
                    : `Maximum available stock is ${totalStock} unit(s).`,
                timer: 2500,
                showConfirmButton: false,
                customClass: { popup: 'rounded-2xl border border-slate-100 shadow-xl' }
            });
        }
    }
</script>
@endsection
