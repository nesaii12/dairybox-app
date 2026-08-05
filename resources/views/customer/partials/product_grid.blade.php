<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @forelse($products as $product)
        @php
            $outOfStock = $product->stock <= 0;
            $isLowStock = $product->isLowStock() && !$outOfStock;
        @endphp
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-md hover:-translate-y-1 transition-all duration-300">
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
                    <span class="absolute top-4 left-4 bg-amber-100/90 backdrop-blur border border-amber-200/50 text-amber-800 text-[10px] font-bold px-2.5 py-1 rounded-full shadow-2xs">Low stock</span>
                @endif
            </div>

            <!-- Product Information -->
            <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                <div class="space-y-1">
                    <h3 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight group-hover:text-sky-600 transition-colors truncate">
                        <a href="{{ route('customer.product', $product->id) }}">{{ $product->product_name }}</a>
                    </h3>
                    <p class="text-[11px] text-slate-500 font-semibold">
                        {{ $product->milk_type }} Milk &bull; {{ $product->size }}
                    </p>
                </div>

                <!-- Price & Add-To-Cart Action -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                    <span class="text-sm font-bold text-sky-600">₱{{ number_format($product->price, 2) }}</span>
                    
                    @if(!$outOfStock)
                        @auth
                            <form action="{{ route('customer.cart.add', $product->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="w-8 h-8 rounded-full bg-[#0ea5e9] hover:bg-sky-600 text-white flex items-center justify-center transition-colors shadow-md shadow-sky-100 cursor-pointer animate-none hover:scale-105 active:scale-95" title="Add to cart">
                                    <i class="bi bi-plus text-lg font-bold"></i>
                                </button>
                            </form>
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
