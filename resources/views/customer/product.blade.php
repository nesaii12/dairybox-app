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

            <!-- Order Customization: Milk Type & Size -->
            @php
                $isExpiredProd = $product->expiration_date && \Carbon\Carbon::parse($product->expiration_date)->startOfDay()->lt(now()->startOfDay());
                $prodHasStock = !$isExpiredProd && $product->stock > 0;
                $variants = isset($siblingVariants) ? $siblingVariants : collect([$product]);
                $availableVariants = $variants->filter(function ($v) {
                    $vExp = $v->expiration_date && \Carbon\Carbon::parse($v->expiration_date)->startOfDay()->lt(now()->startOfDay());
                    return !$vExp && $v->stock > 0;
                });

                $allowedMilks = $prodHasStock ? $product->available_milk_types : [];
                $allowedSizes = $prodHasStock ? $product->available_sizes : [];
                foreach ($availableVariants as $av) {
                    $allowedMilks = array_values(array_unique(array_merge($allowedMilks, $av->available_milk_types)));
                    $allowedSizes = array_values(array_unique(array_merge($allowedSizes, $av->available_sizes)));
                }

                $defaultMilk = in_array($product->milk_type, $allowedMilks) ? $product->milk_type : ($allowedMilks[0] ?? $product->milk_type);
                $defaultSize = in_array($product->size, $allowedSizes) ? $product->size : ($allowedSizes[0] ?? $product->size);

                $milkOptions = [
                    'Carabao' => 'Carabao Milk',
                    'Cow' => "Cow's Milk",
                ];
                $sizeOptions = ['Small', 'Medium', 'Large'];
            @endphp

            <div class="mt-6 pt-6 border-t border-slate-100 space-y-4">
                <!-- Milk Type Selector -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Select Milk Type</span>
                        <span id="selectedMilkLabel" class="text-[11px] font-semibold text-sky-600">{{ $defaultMilk === 'Cow' ? "Cow's Milk" : 'Carabao Milk' }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5" id="detailMilkGroup">
                        @foreach($milkOptions as $milkKey => $milkLabel)
                            @php
                                $isMilkAvailable = in_array($milkKey, $allowedMilks);
                                $isCurrentMilk = ($defaultMilk === $milkKey) && $isMilkAvailable;
                            @endphp

                            @if($isMilkAvailable)
                                <button type="button"
                                    data-milk="{{ $milkKey }}"
                                    data-label="{{ $milkLabel }}"
                                    onclick="selectDetailMilkType(this)"
                                    class="detail-milk-btn px-3.5 py-2.5 rounded-xl text-xs font-bold flex items-center justify-between transition-all cursor-pointer {{ $isCurrentMilk ? 'border-2 border-sky-600 bg-sky-50/70 text-sky-900 shadow-2xs' : 'border border-slate-200 bg-white hover:border-sky-400 hover:bg-sky-50/30 text-slate-700 hover:text-sky-700' }}">
                                    <span class="flex items-center gap-1.5">
                                        <i class="bi bi-check-circle-fill text-sky-600 detail-check-icon {{ $isCurrentMilk ? '' : 'hidden' }}"></i>
                                        <span>{{ $milkLabel }}</span>
                                    </span>
                                    <span class="detail-status-tag text-[10px] font-bold {{ $isCurrentMilk ? 'text-sky-600 uppercase' : 'text-emerald-600' }}">{{ $isCurrentMilk ? 'Selected' : 'Available' }}</span>
                                </button>
                            @else
                                <button type="button" disabled aria-disabled="true" title="{{ $milkLabel }} is not available for {{ $product->product_name }}"
                                    class="px-3.5 py-2.5 rounded-xl border border-slate-200/80 bg-slate-100/80 text-slate-400 text-xs font-semibold flex items-center justify-between cursor-not-allowed opacity-60 select-none">
                                    <span class="line-through">{{ $milkLabel }}</span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Unavailable</span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Size Selector -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Select Size</span>
                        <span id="selectedSizeLabel" class="text-[11px] font-semibold text-sky-600">{{ $defaultSize }}</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2.5" id="detailSizeGroup">
                        @foreach($sizeOptions as $sizeOption)
                            @php
                                $isSizeAvailable = in_array($sizeOption, $allowedSizes);
                                $isCurrentSize = ($defaultSize === $sizeOption) && $isSizeAvailable;
                            @endphp

                            @if($isSizeAvailable)
                                <button type="button"
                                    data-size="{{ $sizeOption }}"
                                    onclick="selectDetailSize(this)"
                                    class="detail-size-btn px-3 py-2.5 rounded-xl text-xs font-bold flex flex-col items-center justify-center transition-all cursor-pointer {{ $isCurrentSize ? 'border-2 border-sky-600 bg-sky-50/70 text-sky-900 shadow-2xs' : 'border border-slate-200 bg-white hover:border-sky-400 hover:bg-sky-50/30 text-slate-700 hover:text-sky-700' }}">
                                    <span class="flex items-center gap-1">
                                        <i class="bi bi-check-circle-fill text-sky-600 text-[11px] detail-check-icon {{ $isCurrentSize ? '' : 'hidden' }}"></i>
                                        <span>{{ $sizeOption }}</span>
                                    </span>
                                    <span class="detail-status-tag text-[9px] font-bold mt-0.5 {{ $isCurrentSize ? 'text-sky-600' : 'text-emerald-600' }}">{{ $isCurrentSize ? 'Selected' : 'Available' }}</span>
                                </button>
                            @else
                                <button type="button" disabled aria-disabled="true" title="Size {{ $sizeOption }} is not available for {{ $product->product_name }}"
                                    class="px-3 py-2.5 rounded-xl border border-slate-200/80 bg-slate-100/80 text-slate-400 text-xs font-semibold flex flex-col items-center justify-center cursor-not-allowed opacity-60 select-none">
                                    <span class="line-through">{{ $sizeOption }}</span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Unavailable</span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Specs & Freshness Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-5">
                @if($product->flavor)
                <div class="p-3.5 bg-sky-50/60 border border-sky-100 rounded-2xl">
                    <span class="block text-[9px] font-bold text-sky-700 uppercase tracking-wider flex items-center gap-1">
                        <i class="bi bi-tag-fill text-[9px]"></i> Flavor
                    </span>
                    <span class="text-xs font-bold text-sky-950">{{ $product->flavor }}</span>
                </div>
                @endif
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
                $availableToAdd = $effectiveStock;
                $allStockInCart = false;
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
                            <input type="hidden" name="milk_type" id="detail_milk_type_input" value="{{ $defaultMilk }}">
                            <input type="hidden" name="size" id="detail_size_input" value="{{ $defaultSize }}">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label for="quantity" class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Order Quantity</label>
                                    <span id="product-in-cart-info" class="text-[10px] text-sky-600 font-semibold {{ $cartQty > 0 ? '' : 'hidden' }}">{{ $cartQty }} already in cart ({{ $availableToAdd }} more available)</span>
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
@php
    $satisfactionLabels = [
        5 => ['label' => 'Very Satisfied', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        4 => ['label' => 'Satisfied', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
        3 => ['label' => 'Neutral', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
        2 => ['label' => 'Dissatisfied', 'class' => 'bg-orange-50 text-orange-700 border-orange-200'],
        1 => ['label' => 'Very Dissatisfied', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
    ];
    $totalReviewsCount = $reviews->count();
@endphp
<div id="reviews-section" class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
    <div class="border-b border-slate-100 pb-5 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h3 class="text-sm font-bold text-slate-950 uppercase tracking-wider">Ratings & Customer Satisfaction Feedback</h3>
            <p class="text-xs text-slate-500 mt-0.5">Customer ratings and satisfaction reviews from verified product purchases.</p>
        </div>
        @if($totalReviewsCount > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold text-amber-800 w-max">
                <i class="bi bi-star-fill text-amber-400"></i>
                <span>{{ number_format($avgRating, 1) }} / 5.0 ({{ $totalReviewsCount }} {{ Str::plural('review', $totalReviewsCount) }})</span>
            </span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Reviews Breakdown Summary -->
        <div class="lg:col-span-1 space-y-5">
            <div class="bg-slate-50 border border-slate-100 p-6 rounded-2xl flex flex-col items-center justify-center text-center">
                <h4 class="text-5xl font-black text-slate-950 font-serif">{{ number_format($avgRating, 1) }}</h4>
                <div class="flex text-amber-400 my-2 space-x-0.5">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= round($avgRating))
                            <i class="bi bi-star-fill text-base"></i>
                        @else
                            <i class="bi bi-star text-base text-slate-300"></i>
                        @endif
                    @endfor
                </div>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Average based on {{ $totalReviewsCount }} {{ Str::plural('rating', $totalReviewsCount) }}</p>

                <!-- Satisfaction Star Breakdown -->
                <div class="w-full mt-5 pt-4 border-t border-slate-200/70 space-y-2 text-left">
                    @for($star = 5; $star >= 1; $star--)
                        @php
                            $starCount = $reviews->where('rating', $star)->count();
                            $starPct = $totalReviewsCount > 0 ? round(($starCount / $totalReviewsCount) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-2 text-[11px]">
                            <span class="w-12 font-bold text-slate-600 flex items-center gap-0.5 shrink-0">
                                {{ $star }} <i class="bi bi-star-fill text-amber-400 text-[9px]"></i>
                            </span>
                            <div class="flex-1 h-2 bg-slate-200/80 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-400 rounded-full transition-all duration-300" style="width: {{ $starPct }}%"></div>
                            </div>
                            <span class="w-7 text-right text-[10px] font-semibold text-slate-400 shrink-0">{{ $starCount }}</span>
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Customer Rating & Feedback Form (for verified buyers) -->
            @auth
                @if(!empty($hasPurchased))
                    <div class="bg-sky-50/50 border border-sky-100 p-5 rounded-2xl">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="bi bi-chat-heart-fill text-sky-600"></i>
                                <span>{{ !empty($userRating) ? 'Update Your Rating & Feedback' : 'Rate Your Satisfaction' }}</span>
                            </h4>
                            @if(!empty($userRating))
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">Reviewed</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">How satisfied are you with <span class="font-semibold text-slate-700">{{ $product->product_name }}</span>?</p>

                        <form action="{{ route('customer.ratings.store', $product->id) }}" method="POST" class="space-y-3.5">
                            @csrf
                            @php
                                $initialRating = old('rating', $userRating->rating ?? 5);
                                $initialLabel = $satisfactionLabels[$initialRating]['label'] ?? 'Very Satisfied';
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center space-x-1.5 text-xl" id="productRateStars">
                                        @for($s = 1; $s <= 5; $s++)
                                            <label class="cursor-pointer transition-transform hover:scale-110">
                                                <input type="radio" name="rating" value="{{ $s }}" {{ (int) $initialRating === $s ? 'checked' : '' }} required class="sr-only" onchange="setProductSatisfactionRating({{ $s }})">
                                                <i class="bi {{ $s <= $initialRating ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300' }}" data-star-index="{{ $s }}" onclick="setProductSatisfactionRating({{ $s }})"></i>
                                            </label>
                                        @endfor
                                    </div>
                                    <span id="productSatisfactionBadge" class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border bg-amber-50 text-amber-800 border-amber-200">
                                        {{ $initialLabel }}
                                    </span>
                                </div>
                            </div>

                            <!-- Quick Feedback Tags -->
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Quick Feedback Tags</span>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach(['Fresh & creamy', 'Great flavor', 'High quality dairy', 'Good packaging', 'Worth the price'] as $tag)
                                        <button type="button" onclick="appendProductFeedbackTag('{{ $tag }}')" class="px-2 py-1 rounded-lg bg-white hover:bg-sky-50 text-slate-600 hover:text-sky-700 border border-slate-200/80 text-[10px] font-semibold transition cursor-pointer">
                                            + {{ $tag }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label for="product_review_input" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Your Feedback</label>
                                <textarea name="review" id="product_review_input" rows="3" maxlength="500"
                                    placeholder="Share your experience with the taste, freshness, and quality..."
                                    class="block w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 text-xs transition-all resize-none">{{ old('review', $userRating->review ?? '') }}</textarea>
                            </div>

                            <button type="submit" class="w-full py-2.5 px-4 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-sky-100 cursor-pointer flex items-center justify-center gap-1.5">
                                <i class="bi bi-send-check-fill"></i>
                                <span>{{ !empty($userRating) ? 'Update Rating & Feedback' : 'Submit Rating & Feedback' }}</span>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="p-4 bg-slate-50 border border-slate-200/60 rounded-2xl text-xs text-slate-500 leading-relaxed flex items-start gap-2.5">
                        <i class="bi bi-bag-check text-sky-600 text-base mt-0.5"></i>
                        <div>
                            <span class="font-bold text-slate-800 block mb-0.5">Verified Buyers Only</span>
                            Purchase this product to rate your satisfaction and share feedback. You can also review purchased items anytime from <a href="{{ route('customer.orders.index') }}" class="text-sky-600 font-bold hover:underline">My Orders</a>.
                        </div>
                    </div>
                @endif
            @else
                <div class="p-4 bg-slate-50 border border-slate-200/60 rounded-2xl text-xs text-slate-500 leading-relaxed flex items-start gap-2.5">
                    <i class="bi bi-person-check text-sky-600 text-base mt-0.5"></i>
                    <div>
                        <a href="{{ route('login') }}" class="text-sky-600 font-bold hover:underline">Sign in</a> to rate and leave feedback on products you have purchased.
                    </div>
                </div>
            @endauth
        </div>

        <!-- Individual reviews list -->
        <div class="lg:col-span-2">
            <div class="space-y-4 max-h-[520px] overflow-y-auto pr-1">
                @forelse($reviews as $rev)
                @php
                    $satInfo = $satisfactionLabels[$rev->rating] ?? $satisfactionLabels[5];
                @endphp
                <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-100/80 shadow-2xs">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-sky-500 to-sky-400 text-white flex items-center justify-center font-bold text-xs shadow-2xs shadow-sky-100/50">
                                {{ strtoupper(substr($rev->customer->name ?? 'C', 0, 1)) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900">{{ $rev->customer->name ?? 'Customer' }}</span>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold border {{ $satInfo['class'] }}">
                                        {{ $satInfo['label'] }}
                                    </span>
                                </div>
                                <span class="block text-[9px] text-slate-400 mt-0.5">{{ $rev->created_at->format('M d, Y h:i A') }} &bull; Verified Purchase</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 text-amber-400 text-xs shrink-0">
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
                        <p class="text-xs text-slate-600 mt-3 leading-relaxed bg-white border border-slate-100 p-3 rounded-xl shadow-2xs">
                            "{{ $rev->review }}"
                        </p>
                    @endif
                </div>
                @empty
                <div class="py-16 text-center bg-slate-50/40 rounded-2xl border border-dashed border-slate-200">
                    <i class="bi bi-chat-square-dots text-3xl text-slate-300 block mb-2"></i>
                    <p class="text-xs font-semibold text-slate-600">No customer reviews yet</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Be the first verified buyer to rate and review this product.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function selectDetailMilkType(btn) {
        const milk = btn.getAttribute('data-milk');
        const label = btn.getAttribute('data-label');
        const input = document.getElementById('detail_milk_type_input');
        const labelEl = document.getElementById('selectedMilkLabel');
        if (input) input.value = milk;
        if (labelEl) labelEl.textContent = label;

        document.querySelectorAll('.detail-milk-btn').forEach(b => {
            b.className = 'detail-milk-btn px-3.5 py-2.5 rounded-xl text-xs font-bold flex items-center justify-between transition-all cursor-pointer border border-slate-200 bg-white hover:border-sky-400 hover:bg-sky-50/30 text-slate-700 hover:text-sky-700';
            const icon = b.querySelector('.detail-check-icon');
            if (icon) icon.classList.add('hidden');
            const tag = b.querySelector('.detail-status-tag');
            if (tag) {
                tag.className = 'detail-status-tag text-[10px] font-bold text-emerald-600';
                tag.textContent = 'Available';
            }
        });

        btn.className = 'detail-milk-btn px-3.5 py-2.5 rounded-xl text-xs font-bold flex items-center justify-between transition-all cursor-pointer border-2 border-sky-600 bg-sky-50/70 text-sky-900 shadow-2xs';
        const activeIcon = btn.querySelector('.detail-check-icon');
        if (activeIcon) activeIcon.classList.remove('hidden');
        const activeTag = btn.querySelector('.detail-status-tag');
        if (activeTag) {
            activeTag.className = 'detail-status-tag text-[10px] font-bold text-sky-600 uppercase';
            activeTag.textContent = 'Selected';
        }
    }

    function selectDetailSize(btn) {
        const size = btn.getAttribute('data-size');
        const input = document.getElementById('detail_size_input');
        const labelEl = document.getElementById('selectedSizeLabel');
        if (input) input.value = size;
        if (labelEl) labelEl.textContent = size;

        document.querySelectorAll('.detail-size-btn').forEach(b => {
            b.className = 'detail-size-btn px-3 py-2.5 rounded-xl text-xs font-bold flex flex-col items-center justify-center transition-all cursor-pointer border border-slate-200 bg-white hover:border-sky-400 hover:bg-sky-50/30 text-slate-700 hover:text-sky-700';
            const icon = b.querySelector('.detail-check-icon');
            if (icon) icon.classList.add('hidden');
            const tag = b.querySelector('.detail-status-tag');
            if (tag) {
                tag.className = 'detail-status-tag text-[9px] font-bold mt-0.5 text-emerald-600';
                tag.textContent = 'Available';
            }
        });

        btn.className = 'detail-size-btn px-3 py-2.5 rounded-xl text-xs font-bold flex flex-col items-center justify-center transition-all cursor-pointer border-2 border-sky-600 bg-sky-50/70 text-sky-900 shadow-2xs';
        const activeIcon = btn.querySelector('.detail-check-icon');
        if (activeIcon) activeIcon.classList.remove('hidden');
        const activeTag = btn.querySelector('.detail-status-tag');
        if (activeTag) {
            activeTag.className = 'detail-status-tag text-[9px] font-bold mt-0.5 text-sky-600';
            activeTag.textContent = 'Selected';
        }
    }

    let maxStock = {{ $availableToAdd ?? $product->stock }};
    const totalStock = {{ $product->stock }};
    let inCart = {{ $cartQty ?? 0 }};
    const qtyInput = document.getElementById('quantity');

    window.updateProductDetailStockState = function(availableLeft, currentInCart) {
        maxStock = availableLeft;
        inCart = currentInCart;
        if (qtyInput) {
            qtyInput.max = Math.max(1, availableLeft);
            qtyInput.value = 1;
        }
        const infoEl = document.getElementById('product-in-cart-info');
        if (infoEl) {
            if (currentInCart > 0) {
                infoEl.textContent = `${currentInCart} already in cart (${availableLeft} more available)`;
                infoEl.classList.remove('hidden');
            } else {
                infoEl.classList.add('hidden');
            }
        }
    };

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

    const satisfactionLevels = {
        1: 'Very Dissatisfied',
        2: 'Dissatisfied',
        3: 'Neutral',
        4: 'Satisfied',
        5: 'Very Satisfied'
    };

    function setProductSatisfactionRating(rating) {
        const container = document.getElementById('productRateStars');
        const badge = document.getElementById('productSatisfactionBadge');
        if (!container) return;

        container.querySelectorAll('input[name="rating"]').forEach(input => {
            input.checked = parseInt(input.value, 10) === rating;
        });

        container.querySelectorAll('i[data-star-index]').forEach(icon => {
            const idx = parseInt(icon.getAttribute('data-star-index'), 10);
            if (idx <= rating) {
                icon.classList.remove('bi-star', 'text-slate-300');
                icon.classList.add('bi-star-fill', 'text-amber-400');
            } else {
                icon.classList.remove('bi-star-fill', 'text-amber-400');
                icon.classList.add('bi-star', 'text-slate-300');
            }
        });

        if (badge && satisfactionLevels[rating]) {
            badge.textContent = satisfactionLevels[rating];
        }
    }

    function appendProductFeedbackTag(tagText) {
        const textarea = document.getElementById('product_review_input');
        if (!textarea) return;
        const current = textarea.value.trim();
        if (!current) {
            textarea.value = tagText + '. ';
        } else if (!current.toLowerCase().includes(tagText.toLowerCase())) {
            textarea.value = current.replace(/\.*\s*$/, '. ') + tagText + '. ';
        }
        textarea.focus();
    }
</script>
@endsection
