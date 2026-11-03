<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @forelse($products as $product)
        @php
            $outOfStock = $product->stock <= 0;
            $isLowStock = $product->isLowStock() && !$outOfStock;
            $inCart = isset($cartQuantities) ? ($cartQuantities[$product->id] ?? 0) : 0;
            $maxInCart = ($inCart >= $product->stock) && !$outOfStock;
        @endphp
        <div id="product-{{ $product->id }}" data-record-id="product-{{ $product->id }}" data-record-name="{{ strtolower($product->product_name) }}" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-md hover:-translate-y-1 transition-all duration-300 record-highlight-target">
            <!-- Image Wrapper -->
            <div class="aspect-square bg-slate-50 relative flex items-center justify-center overflow-hidden border-b border-slate-50">
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
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center">
                        <span class="bg-rose-600 text-white text-[10px] px-3 py-1.5 rounded-full font-bold shadow-md uppercase tracking-wider">Sold Out</span>
                    </div>
                @elseif($isLowStock)
                    <span class="absolute top-4 left-4 bg-amber-100/90 backdrop-blur border border-amber-200/50 text-amber-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-2xs">Only {{ $product->stock }} left</span>
                @endif

                @if($product->flavor)
                    <span class="absolute top-4 right-4 bg-white/95 backdrop-blur border border-slate-200/80 text-slate-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-2xs flex items-center space-x-1">
                        <i class="bi bi-tag-fill text-[9px] text-sky-500"></i>
                        <span>{{ $product->flavor }}</span>
                    </span>
                @endif
            </div>

            <!-- Product Information -->
            <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                <div class="space-y-1">
                    <h3 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight group-hover:text-sky-600 transition-colors truncate">
                        <a href="{{ route('customer.product', $product->id) }}">{{ $product->product_name }}</a>
                    </h3>
                    <p class="text-[11px] text-slate-500 font-semibold">
                        @if($product->flavor)
                            <span class="text-sky-600 font-bold">{{ $product->flavor }}</span> &bull;
                        @endif
                        {{ $product->milk_type }} Milk &bull; {{ $product->size }}
                    </p>
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
                        <span class="text-sm font-bold text-sky-600">₱{{ number_format($product->price, 2) }}</span>
                        @if(!$outOfStock)
                            <span class="block text-[10px] text-slate-400 font-medium">{{ $product->stock }} in stock</span>
                        @endif
                    </div>
                    
                    @if(!$outOfStock)
                        @auth
                            @if(auth()->user()->isRestricted())
                                <button type="button" disabled class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 border border-slate-200 flex items-center justify-center cursor-not-allowed" title="Ordering restricted until {{ auth()->user()->restricted_until->format('M d, Y h:i A') }}">
                                    <i class="bi bi-lock-fill text-xs"></i>
                                </button>
                            @elseif($maxInCart)
                                <button type="button" disabled class="w-8 h-8 rounded-full bg-sky-50 text-sky-600 border border-sky-200 flex items-center justify-center cursor-not-allowed" title="All {{ $product->stock }} available units are already in your cart">
                                    <i class="bi bi-check2 text-base font-bold"></i>
                                </button>
                            @else
                                <form action="{{ route('customer.cart.add', $product->id) }}" method="POST" class="inline add-to-cart-form" data-product-id="{{ $product->id }}" data-product-name="{{ $product->product_name }}" data-stock="{{ $product->stock }}" data-in-cart="{{ $inCart }}">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="add-to-cart-btn w-8 h-8 rounded-full bg-[#0ea5e9] hover:bg-sky-600 text-white flex items-center justify-center transition-colors shadow-md shadow-sky-100 cursor-pointer animate-none hover:scale-105 active:scale-95" title="Add to cart">
                                        <i class="bi bi-plus text-lg font-bold"></i>
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
