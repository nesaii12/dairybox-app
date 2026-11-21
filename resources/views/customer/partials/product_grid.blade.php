<div class="grid grid-cols-1 min-[420px]:grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
    @forelse($products as $product)
        @php
            $isExpired  = $product->expiration_date &&
                          \Carbon\Carbon::parse($product->expiration_date)->startOfDay()->lt(now()->startOfDay());
            $availStock = $product->available_stock;
            $outOfStock = $isExpired || $availStock <= 0;
            $isLowStock = $product->isLowStock() && !$outOfStock;
            $inCart     = isset($cartQuantities) ? ($cartQuantities[$product->id] ?? 0) : 0;
            $maxInCart  = ($inCart >= $availStock) && !$outOfStock;
        @endphp
        <div id="product-{{ $product->id }}" data-record-id="product-{{ $product->id }}" data-record-name="{{ strtolower($product->product_name) }}" data-product-url="{{ route('customer.product', $product->id) }}" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-md hover:-translate-y-1 transition-all duration-300 record-highlight-target cursor-pointer">
            <!-- Image Wrapper -->
            <a href="{{ route('customer.product', $product->id) }}" class="aspect-square bg-slate-50 relative flex items-center justify-center overflow-hidden border-b border-slate-50 block cursor-pointer">
                @if($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->product_name }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <!-- Custom bottle illustration inside page layout -->
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <div class="w-14 h-14 rounded-full bg-white shadow-sm flex items-center justify-center text-sky-500">
                            @if(str_contains(strtolower($product->product_name), 'ice cream'))
                                <i class="bi bi-egg-fried text-3xl"></i>
                            @else
                                <i class="bi bi-droplet-fill text-2xl"></i>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Stock indicator badges -->
                @if($outOfStock)
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center pointer-events-none">
                        <span class="bg-rose-600 text-white text-[10px] px-3 py-1.5 rounded-full font-bold shadow-md uppercase tracking-wider">
                            {{ $isExpired ? 'Out of Stock' : 'Sold Out' }}
                        </span>
                    </div>
                @elseif($isLowStock)
                    <span class="absolute top-4 left-4 bg-amber-100/90 backdrop-blur border border-amber-200/50 text-amber-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-2xs pointer-events-none">Only {{ $availStock }} left</span>
                @endif

                @if($product->flavor)
                    <span class="absolute top-4 right-4 bg-white/95 backdrop-blur border border-slate-200/80 text-slate-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-2xs flex items-center space-x-1 pointer-events-none">
                        <i class="bi bi-tag-fill text-[9px] text-sky-500"></i>
                        <span>{{ $product->flavor }}</span>
                    </span>
                @endif
            </a>

            <!-- Product Information -->
            @php
                $allowedCardMilks = !$outOfStock ? $product->available_milk_types : [];
                $allowedCardSizes = !$outOfStock ? $product->available_sizes : [];
                $defaultCardMilk = in_array($product->milk_type, $allowedCardMilks) ? $product->milk_type : ($allowedCardMilks[0] ?? $product->milk_type);
                $defaultCardSize = in_array($product->size, $allowedCardSizes) ? $product->size : ($allowedCardSizes[0] ?? $product->size);
                $cardMilkOptions = ['Carabao' => 'Carabao', 'Cow' => "Cow's Milk"];
                $cardSizeOptions = ['Small', 'Medium', 'Large'];
            @endphp
            <div class="p-5 flex-1 flex flex-col justify-between space-y-3.5">
                @php
                    $ratingCount = (int) ($product->ratings_count ?? $product->ratings()->count());
                    $ratingAvg = (float) ($product->ratings_avg_rating ?? ($ratingCount > 0 ? $product->ratings()->avg('rating') : 0));
                @endphp
                <div class="space-y-2">
                    <div>
                        <!-- Product Rating Reference -->
                        <a href="{{ route('customer.product', $product->id) }}#reviews-section" class="inline-flex items-center gap-1.5 text-[11px] mb-1 w-max group/rating" title="{{ $ratingCount > 0 ? number_format($ratingAvg, 1) . ' out of 5 (' . $ratingCount . ' ' . Str::plural('review', $ratingCount) . ')' : 'No reviews yet' }}">
                            <span class="flex items-center text-amber-400 space-x-0.5 text-[10px]">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= round($ratingAvg))
                                        <i class="bi bi-star-fill"></i>
                                    @else
                                        <i class="bi bi-star text-slate-300"></i>
                                    @endif
                                @endfor
                            </span>
                            <span class="font-bold {{ $ratingCount > 0 ? 'text-slate-700' : 'text-slate-400' }}">{{ number_format($ratingAvg, 1) }}</span>
                            <span class="text-[10px] text-slate-400 font-medium group-hover/rating:text-sky-600 transition-colors">
                                ({{ $ratingCount > 0 ? $ratingCount . ' ' . Str::plural('review', $ratingCount) : 'No reviews yet' }})
                            </span>
                        </a>

                        <h3 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight group-hover:text-sky-600 transition-colors truncate">
                            <a href="{{ route('customer.product', $product->id) }}">{{ $product->product_name }}</a>
                        </h3>
                        @if($product->flavor)
                            <p class="text-[11px] text-sky-600 font-bold mt-0.5 truncate">{{ $product->flavor }}</p>
                        @endif
                    </div>

                    <!-- Milk Type & Size Customization Options -->
                    <div class="space-y-1.5 pt-0.5">
                        <!-- Milk Type Pills -->
                        <div class="flex items-center gap-1 flex-wrap" data-card-milk-group="{{ $product->id }}">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 w-9 shrink-0">Milk:</span>
                            @foreach($cardMilkOptions as $mKey => $mLabel)
                                @php
                                    $milkAvail = in_array($mKey, $allowedCardMilks);
                                    $isCurrMilk = ($defaultCardMilk === $mKey) && $milkAvail;
                                @endphp
                                @if($milkAvail)
                                    <button type="button"
                                        data-card-option="milk"
                                        data-product-id="{{ $product->id }}"
                                        data-value="{{ $mKey }}"
                                        class="card-milk-pill px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer {{ $isCurrMilk ? 'font-bold bg-sky-600 text-white border border-sky-600 shadow-2xs' : 'font-semibold bg-white text-slate-700 border border-slate-200 hover:border-sky-400 hover:text-sky-600' }}">
                                        {{ $mLabel }}
                                    </button>
                                @else
                                    <button type="button" disabled aria-disabled="true" title="{{ $mLabel }} unavailable"
                                        class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-400 border border-slate-200/70 line-through opacity-55 cursor-not-allowed select-none">
                                        {{ $mLabel }}
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        <!-- Size Pills -->
                        <div class="flex items-center gap-1 flex-wrap" data-card-size-group="{{ $product->id }}">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 w-9 shrink-0">Size:</span>
                            @foreach($cardSizeOptions as $sOpt)
                                @php
                                    $sizeAvail = in_array($sOpt, $allowedCardSizes);
                                    $isCurrSize = ($defaultCardSize === $sOpt) && $sizeAvail;
                                @endphp
                                @if($sizeAvail)
                                    <button type="button"
                                        data-card-option="size"
                                        data-product-id="{{ $product->id }}"
                                        data-value="{{ $sOpt }}"
                                        data-price="{{ $product->getPriceForSize($sOpt) }}"
                                        class="card-size-pill px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer {{ $isCurrSize ? 'font-bold bg-sky-600 text-white border border-sky-600 shadow-2xs' : 'font-semibold bg-white text-slate-700 border border-slate-200 hover:border-sky-400 hover:text-sky-600' }}">
                                        {{ $sOpt }}
                                    </button>
                                @else
                                    <button type="button" disabled aria-disabled="true" title="{{ $sOpt }} unavailable"
                                        class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-400 border border-slate-200/70 line-through opacity-55 cursor-not-allowed select-none">
                                        {{ $sOpt }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-0.5 pt-0.5 text-[10px] text-slate-400 font-medium">
                        @if($product->manufacturing_date)
                            <p class="flex items-center gap-1">
                                <i class="bi bi-calendar2-check text-slate-400 text-[10px]"></i> Mfg: {{ \Carbon\Carbon::parse($product->manufacturing_date)->format('M d, Y') }}
                            </p>
                        @endif
                        @if($product->expiration_date)
                            <p class="flex items-center gap-1">
                                <i class="bi bi-clock-history text-slate-400 text-[10px]"></i> Exp: {{ \Carbon\Carbon::parse($product->expiration_date)->format('M d, Y') }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Price & Add-To-Cart Action -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                    <div>
                        <span class="card-product-price text-sm font-bold text-sky-600">₱{{ number_format($product->getPriceForSize($defaultCardSize), 2) }}</span>
                        @if($isExpired)
                            <span class="block text-[10px] text-rose-500 font-semibold">Out of Stock</span>
                        @elseif(!$outOfStock)
                            <span class="block text-[10px] text-slate-400 font-medium">{{ $availStock }} in stock</span>
                        @endif
                    </div>
                    
                    @if(!$outOfStock)
                        @auth
                            @if(auth()->user()->isRestricted())
                                <button type="button" disabled class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 border border-slate-200 flex items-center justify-center cursor-not-allowed" title="Ordering restricted until {{ auth()->user()->restricted_until->format('M d, Y h:i A') }}">
                                    <i class="bi bi-lock-fill text-xs"></i>
                                </button>
                            @else
                                @php
                                    $availQty = max(1, $availStock);
                                @endphp
                                <form action="{{ route('customer.cart.add', $product->id) }}" method="POST" class="inline-flex items-center gap-1.5 add-to-cart-form" data-product-id="{{ $product->id }}" data-product-name="{{ $product->product_name }}" data-stock="{{ $availStock }}" data-in-cart="{{ $inCart }}">
                                    @csrf
                                    <input type="hidden" name="milk_type" class="card-selected-milk" value="{{ $defaultCardMilk }}">
                                    <input type="hidden" name="size" class="card-selected-size" value="{{ $defaultCardSize }}">
                                    <div class="catalog-qty-stepper flex items-center border border-slate-200 rounded-xl bg-slate-50/90 overflow-hidden h-8">
                                        <button type="button" class="catalog-qty-minus px-2 h-full text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer text-xs" title="Decrease quantity">
                                            <i class="bi bi-dash pointer-events-none"></i>
                                        </button>
                                        <input type="number" name="quantity" value="1" min="1" max="{{ $availQty }}"
                                            class="catalog-qty-input w-7 text-center text-xs font-bold text-slate-800 bg-transparent border-0 p-0 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                            aria-label="Quantity">
                                        <button type="button" class="catalog-qty-plus px-2 h-full text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer text-xs" title="Increase quantity">
                                            <i class="bi bi-plus pointer-events-none"></i>
                                        </button>
                                    </div>
                                    <button type="submit" class="add-to-cart-btn w-8 h-8 rounded-xl bg-[#0ea5e9] hover:bg-sky-600 text-white flex items-center justify-center transition-colors shadow-md shadow-sky-100 cursor-pointer animate-none hover:scale-105 active:scale-95 flex-shrink-0" title="Add to selection batch">
                                        <i class="bi bi-cart-plus-fill text-xs"></i>
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="w-8 h-8 rounded-full bg-[#0ea5e9] hover:bg-sky-600 text-white flex items-center justify-center transition-colors shadow-md shadow-sky-100 cursor-pointer hover:scale-105 active:scale-95" title="Sign in to add to cart">
                                <i class="bi bi-plus text-lg font-bold"></i>
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-slate-100 p-8 shadow-sm">
            <i class="bi bi-basket text-4xl text-slate-300 mb-2 block"></i>
            <p class="text-sm font-semibold text-slate-800">No Products Found</p>
            <p class="text-xs text-slate-500">Try adjusting your search queries or filters.</p>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($products->hasPages())
    <div class="flex justify-center mt-8">
        {{ $products->links() }}
    </div>
@endif
