@extends('layouts.admin')

@section('title', 'Inventory Management')
@section('page_title', 'Inventory Management')

@section('content')
<!-- Top Panel: Stock Overview Table -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-8">
    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Stock Overview</h3>
            <p class="text-xs text-slate-500">View all products with their current stock, manufacture date, and expiry status.</p>
        </div>
        <!-- Filter + Rows per page -->
        <div class="flex flex-wrap items-center gap-3 self-start sm:self-center">
            {{-- Filter Pills --}}
            <div class="flex items-center gap-1.5">
                <a href="{{ request()->fullUrlWithQuery(['filter' => 'all', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition-all
                          {{ $filter === 'all' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <i class="bi bi-grid-3x3-gap"></i> All
                </a>
                <a href="{{ request()->fullUrlWithQuery(['filter' => 'near_expiry', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition-all
                          {{ $filter === 'near_expiry' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                    <i class="bi bi-exclamation-triangle"></i> Near Expiry
                </a>
                <a href="{{ request()->fullUrlWithQuery(['filter' => 'expired', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition-all
                          {{ $filter === 'expired' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    <i class="bi bi-x-circle"></i> Expired
                </a>
                <a href="{{ request()->fullUrlWithQuery(['filter' => 'new_stock', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition-all
                          {{ $filter === 'new_stock' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                    <i class="bi bi-box-seam"></i> New Stock
                </a>
            </div>
            {{-- Rows per page --}}
            <div class="flex items-center space-x-2 text-xs text-slate-500">
                <span>Rows:</span>
                <select onchange="window.location.href = updateQueryStringParameter(window.location.href, 'per_page', this.value)" class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 font-semibold text-slate-700 cursor-pointer">
                    <option value="5"   {{ request('per_page', session('per_page_inventory', 10)) == 5   ? 'selected' : '' }}>5</option>
                    <option value="10"  {{ request('per_page', session('per_page_inventory', 10)) == 10  ? 'selected' : '' }}>10</option>
                    <option value="20"  {{ request('per_page', session('per_page_inventory', 10)) == 20  ? 'selected' : '' }}>20</option>
                    <option value="50"  {{ request('per_page', session('per_page_inventory', 10)) == 50  ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', session('per_page_inventory', 10)) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Stock Breakdown</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Manufacture Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Expiry Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($products as $product)
                @php
                    $today        = now()->startOfDay();
                    $sevenDaysOut = now()->addDays(7)->endOfDay();
                    $recentCutoff = now()->subDay();

                    // All active batches (FIFO order)
                    $activeBatches = $product->inventories->where('remaining_stock', '>', 0)->sortBy('created_at');
                    $isLowStock    = $product->stock < 10;
                    $firstBatch    = $activeBatches->first();

                    // Near-expiry: soonest batch expiring within 7 days (from today forward)
                    $nearExpiryBatch = $activeBatches
                        ->filter(fn($b) => $b->expiration_date &&
                            \Carbon\Carbon::parse($b->expiration_date)->gte($today) &&
                            \Carbon\Carbon::parse($b->expiration_date)->lte($sevenDaysOut))
                        ->sortBy('expiration_date')
                        ->first();

                    // Expired: earliest batch that is already past its expiry
                    $expiredBatch = $activeBatches
                        ->filter(fn($b) => $b->expiration_date &&
                            \Carbon\Carbon::parse($b->expiration_date)->lt($today))
                        ->sortBy('expiration_date')
                        ->first();

                    // New stock: most recently added genuine stock-in batch (last 24 hours, not near-expiry/expired)
                    $newBatch = $activeBatches
                        ->filter(fn($b) => $b->stock_in > 0
                            && !str_starts_with((string) $b->remarks, 'Delivery Cancelled/Failed')
                            && $b->created_at >= $recentCutoff
                            && (!$b->expiration_date || \Carbon\Carbon::parse($b->expiration_date)->gt($sevenDaysOut)))
                        ->sortByDesc('created_at')
                        ->first();

                    // Pick display batch based on active filter
                    if ($filter === 'near_expiry') {
                        $displayBatch = $nearExpiryBatch ?? $firstBatch;
                    } elseif ($filter === 'expired') {
                        $displayBatch = $expiredBatch ?? $firstBatch;
                    } elseif ($filter === 'new_stock') {
                        // Show ONLY the new batch for this product
                        $displayBatch = $newBatch;
                    } else {
                        // "All" — FIFO batch order matching date manufactured
                        $displayBatch = $firstBatch ?? $nearExpiryBatch ?? $newBatch ?? $expiredBatch;
                    }
                @endphp
                <tr id="product-{{ $product->id }}"
                    data-record-id="product-{{ $product->id }}"
                    data-record-name="{{ strtolower($product->product_name) }}"
                    class="hover:bg-slate-50/50 transition-colors record-highlight-target">

                    <!-- Product Details -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            <div class="h-8 w-8 rounded overflow-hidden bg-slate-100 flex items-center justify-center flex-shrink-0 border border-slate-100">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->product_name }}" class="h-full w-full object-cover">
                                @else
                                    <i class="bi bi-image text-slate-400"></i>
                                @endif
                            </div>
                            <div>
                                <span class="block text-sm font-bold text-slate-900">{{ $product->product_name }}</span>
                                <span class="block text-[10px] text-slate-500">{{ $product->category->category_name }} &bull; Size: {{ $product->size }}</span>
                            </div>
                        </div>
                    </td>

                    <!-- Stock Breakdown (Physical / Reserved / Available) -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $maxStockLimit = \App\Models\Product::MAX_STOCK_LIMIT;
                            $displayedStock = ($filter === 'new_stock' && $displayBatch) ? $displayBatch->remaining_stock : $product->stock;
                            $reservedStock = (int) ($product->reserved_stock ?? 0);
                            $availableStock = $product->available_stock;
                            $isOverLimit = $product->stock > $maxStockLimit;
                            $isAtLimit = $product->stock === $maxStockLimit;
                        @endphp
                        <span class="text-sm font-bold {{ $displayedStock == 0 ? 'text-slate-400' : ($isLowStock ? 'text-rose-600' : ($isOverLimit ? 'text-amber-700' : 'text-slate-900')) }}">
                            {{ $displayedStock }} <span class="text-xs font-medium text-slate-400">/ {{ $maxStockLimit }}</span> physical
                        </span>
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                {{ $availableStock }} available
                            </span>
                            @if($reservedStock > 0)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/80">
                                    {{ $reservedStock }} reserved
                                </span>
                            @endif
                            @if($isOverLimit)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    Over {{ $maxStockLimit }} Limit
                                </span>
                            @elseif($isAtLimit)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                    At Limit
                                </span>
                            @endif
                        </div>
                        @if($product->stock == 0)
                            <span class="flex items-center gap-1 mt-1 w-max px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-400 border border-slate-200">
                                <i class="bi bi-dash-circle"></i> Inactive
                            </span>
                        @elseif($isLowStock)
                            <span class="block text-[9px] font-bold text-rose-500 mt-0.5">Needs Replenishing</span>
                        @endif
                    </td>

                    <!-- Manufacture Date -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $mfgDateRaw = $displayBatch?->manufacturing_date
                                ?? $activeBatches->whereNotNull('manufacturing_date')->first()?->manufacturing_date
                                ?? $product->manufacturing_date;
                            $isNew = $newBatch && $displayBatch && $displayBatch->id === $newBatch->id;
                        @endphp
                        @if($mfgDateRaw)
                            @php
                                $mfgDate = \Carbon\Carbon::parse($mfgDateRaw);
                            @endphp
                            <span class="text-sm font-semibold {{ $isNew ? 'text-emerald-700' : 'text-slate-700' }}">
                                {{ $mfgDate->format('M d, Y') }}
                            </span>
                            @if($isNew)
                                <span class="ml-1.5 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700">
                                    <i class="bi bi-box-seam"></i> New
                                </span>
                            @endif
                            @if($displayBatch)
                                <span class="block text-[10px] text-slate-400">Batch #{{ $displayBatch->id }}</span>
                            @endif
                        @elseif($displayBatch)
                            <span class="text-xs text-slate-400 italic">—</span>
                            @if($isNew)
                                <span class="ml-1 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700">
                                    <i class="bi bi-box-seam"></i> New
                                </span>
                            @endif
                        @else
                            <span class="text-xs text-slate-400 italic">—</span>
                        @endif
                    </td>

                    <!-- Expiry Date -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($displayBatch && $displayBatch->expiration_date)
                            @php
                                $expDate      = \Carbon\Carbon::parse($displayBatch->expiration_date);
                                $daysLeft     = (int) now()->startOfDay()->diffInDays($expDate->startOfDay(), false);
                                $isExpired    = $daysLeft < 0;
                                $isNearExpiry = !$isExpired && $daysLeft <= 7;
                            @endphp
                            <span class="text-sm font-semibold
                                {{ $isExpired ? 'text-rose-600' : ($isNearExpiry ? 'text-amber-600' : 'text-slate-700') }}">
                                {{ $expDate->format('M d, Y') }}
                            </span>
                            @if($isExpired)
                                <span class="ml-1.5 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-700">
                                    <i class="bi bi-x-circle"></i> Expired
                                </span>
                            @elseif($isNearExpiry)
                                <span class="ml-1.5 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-700">
                                    <i class="bi bi-exclamation-triangle"></i> {{ $daysLeft }}d left
                                </span>
                            @endif
                            <span class="block text-[10px] text-slate-400">Batch #{{ $displayBatch->id }}</span>
                        @elseif($displayBatch)
                            <span class="text-xs text-slate-400 italic">No expiry set</span>
                        @else
                            <span class="text-xs text-slate-400 italic">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center">
                        @if($filter === 'near_expiry')
                            <i class="bi bi-check-circle text-3xl text-emerald-300 mb-2 block"></i>
                            <p class="text-xs text-slate-500 font-medium">No products with batches expiring within 7 days.</p>
                        @elseif($filter === 'expired')
                            <i class="bi bi-check-circle text-3xl text-emerald-300 mb-2 block"></i>
                            <p class="text-xs text-slate-500 font-medium">No products with expired batches found.</p>
                        @elseif($filter === 'new_stock')
                            <i class="bi bi-box-seam text-3xl text-slate-300 mb-2 block"></i>
                            <p class="text-xs text-slate-500 font-medium">No recent new stock available.</p>
                        @else
                            <p class="text-xs text-slate-500 font-medium">No products found.</p>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $products->links() }}
    </div>
    @endif
</div>

<!-- Bottom Panel: Stock Management & Recent Inventory Logs -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column: Adjustment Form Card -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-6">
            <h3 class="text-lg font-bold text-slate-950 mb-1">Stock Management</h3>
            <p class="text-xs text-slate-500 mb-6">Manually adjust warehouse inventory levels. Stock In is capped at {{ \App\Models\Product::MAX_STOCK_LIMIT }} units per product to avoid spoilage.</p>
            
            <form id="stockAdjustmentForm" action="{{ route('admin.inventory.adjust') }}" method="POST" class="space-y-4">
                @csrf
                <!-- Product Selector -->
                <div>
                    <label for="product_id" class="block text-sm font-semibold text-slate-700">Select Product</label>
                    <select name="product_id" id="product_id" required onchange="syncStockLimitState()"
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                        <option value="">-- Choose Product --</option>
                        @foreach($productsForForm as $p)
                            <option value="{{ $p->id }}" data-stock="{{ $p->stock }}" data-available="{{ $p->available_stock }}" data-name="{{ $p->product_name }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->product_name }} ({{ $p->size }}) - Stock: {{ $p->stock }}/{{ \App\Models\Product::MAX_STOCK_LIMIT }} (Avail: {{ $p->available_stock }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Adjustment Type -->
                <div>
                    <label for="adjustment_type" class="block text-sm font-semibold text-slate-700">Adjustment Type</label>
                    <select name="adjustment_type" id="adjustment_type" required onchange="toggleExpiryField()"
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                        <option value="in" {{ old('adjustment_type', 'in') == 'in' ? 'selected' : '' }}>Stock In (Add Inventory)</option>
                        <option value="out" {{ old('adjustment_type') == 'out' ? 'selected' : '' }}>Stock Out (FIFO Deduction)</option>
                    </select>
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-semibold text-slate-700">Quantity</label>
                    <input type="number" name="quantity" id="quantity" required min="1" value="{{ old('quantity', 1) }}"
                        oninput="syncStockLimitState()" onchange="syncStockLimitState()"
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                    <p id="stock_limit_hint" class="hidden mt-1.5 text-[11px] font-medium text-slate-500"></p>
                    <p id="stock_limit_error" class="hidden items-start gap-1.5 mt-1.5 text-xs font-semibold text-rose-600" role="alert">
                        <i class="bi bi-exclamation-circle-fill mt-0.5 shrink-0"></i>
                        <span id="stock_limit_error_text"></span>
                    </p>
                    @error('quantity')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Batch Dates (Only for Stock In) -->
                @php
                    $initialMfgDate = old('manufacturing_date', date('Y-m-d'));
                @endphp
                <div id="dates_container" class="space-y-4">
                    <div>
                        <label for="manufacturing_date" class="block text-sm font-semibold text-slate-700">Manufacturing Date</label>
                        <input type="date" name="manufacturing_date" id="manufacturing_date" value="{{ $initialMfgDate }}" max="{{ date('Y-m-d') }}"
                            oninput="syncStockDates()" onchange="syncStockDates()"
                            class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                        @error('manufacturing_date')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="expiration_date" class="block text-sm font-semibold text-slate-700">Expiration Date</label>
                        <input type="date" name="expiration_date" id="expiration_date" value="{{ old('expiration_date') }}"
                            @if($initialMfgDate) min="{{ $initialMfgDate }}" @endif
                            oninput="syncStockDates()" onchange="syncStockDates()"
                            class="mt-1.5 block w-full px-3 py-2 {{ $errors->has('expiration_date') ? 'bg-rose-50/40 border-rose-500 text-rose-900 focus:ring-rose-500/20 focus:border-rose-500' : 'bg-slate-50 border-slate-200 text-slate-900 focus:ring-sky-500/20 focus:border-sky-500' }} border rounded-xl focus:outline-none focus:ring-2 text-sm transition-all">
                        <p id="stock_date_error" class="hidden items-start gap-1.5 mt-1.5 text-xs font-semibold text-rose-600" role="alert">
                            <i class="bi bi-exclamation-circle-fill mt-0.5 shrink-0"></i>
                            <span id="stock_date_error_text">Expiration date must be on or after the manufacturing date.</span>
                        </p>
                        @error('expiration_date')
                            <p id="stock_date_server_error" class="flex items-start gap-1.5 mt-1.5 text-xs font-semibold text-rose-600" role="alert">
                                <i class="bi bi-exclamation-circle-fill mt-0.5 shrink-0"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Remarks -->
                <div>
                    <label for="remarks" class="block text-sm font-semibold text-slate-700">Remarks / Reason</label>
                    <textarea name="remarks" id="remarks" required rows="2"
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                        placeholder="e.g. New delivery batch, damaged stock dump...">{{ old('remarks') }}</textarea>
                    @error('remarks')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <button type="submit" id="stockAdjustmentSubmitBtn" class="w-full flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100 cursor-pointer">
                    <i class="bi bi-arrow-down-up"></i>
                    <span>Execute Transaction</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Recent Transactions List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col h-full">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-950">Recent Inventory Logs</h3>
                <p class="text-xs text-slate-500">The 10 latest stock-in and stock-out events recorded.</p>
            </div>
            
            <div class="flex-grow overflow-y-auto max-h-[420px]">
                <div class="divide-y divide-slate-100">
                    @forelse($recentTransactions as $log)
                    <div class="p-4 flex items-start justify-between hover:bg-slate-50/50 transition-colors">
                        <div class="flex space-x-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $log->stock_in > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                                <i class="bi {{ $log->stock_in > 0 ? 'bi-plus-circle-fill' : 'bi-dash-circle-fill' }} text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-950">{{ $log->product->product_name }} ({{ $log->product->size }})</p>
                                <p class="text-[11px] text-slate-500">Remarks: <span class="font-medium text-slate-700">{{ $log->remarks }}</span></p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $log->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-black {{ $log->stock_in > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $log->stock_in > 0 ? '+' . $log->stock_in : '-' . $log->stock_out }}
                            </span>
                            @if($log->manufacturing_date)
                                <p class="text-[9px] text-slate-400 font-medium mt-0.5">Mfg: {{ \Carbon\Carbon::parse($log->manufacturing_date)->format('M d, Y') }}</p>
                            @endif
                            @if($log->expiration_date)
                                <p class="text-[9px] text-slate-500 font-semibold mt-0.5">Exp: {{ \Carbon\Carbon::parse($log->expiration_date)->format('M d, Y') }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="py-12 text-center">
                        <i class="bi bi-journal-x text-3xl text-slate-300 mb-2 block"></i>
                        <p class="text-xs font-semibold text-slate-500">No inventory transactions logged yet.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function formatIsoDate(isoDate) {
        if (!isoDate || !/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) return isoDate;
        const [year, month, day] = isoDate.split('-').map(Number);
        const dateObj = new Date(year, month - 1, day);
        return dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    const MAX_STOCK_LIMIT = {{ \App\Models\Product::MAX_STOCK_LIMIT }};
    let isExpiryInvalidState = false;
    let isStockLimitInvalidState = false;

    function updateAdjustmentSubmitButton() {
        const submitBtn = document.getElementById('stockAdjustmentSubmitBtn');
        if (!submitBtn) return;
        const blocked = isExpiryInvalidState || isStockLimitInvalidState;
        submitBtn.disabled = blocked;
        submitBtn.classList.toggle('opacity-50', blocked);
        submitBtn.classList.toggle('cursor-not-allowed', blocked);
        submitBtn.classList.toggle('hover:bg-sky-500', !blocked);
        submitBtn.classList.toggle('cursor-pointer', !blocked);
    }

    function syncStockLimitState() {
        const productSelect = document.getElementById('product_id');
        const typeSelect = document.getElementById('adjustment_type');
        const qtyInput = document.getElementById('quantity');
        const hintEl = document.getElementById('stock_limit_hint');
        const errEl = document.getElementById('stock_limit_error');
        const errText = document.getElementById('stock_limit_error_text');

        if (!productSelect || !typeSelect || !qtyInput) return true;

        const selectedOpt = productSelect.options[productSelect.selectedIndex];
        const hasProduct = Boolean(selectedOpt && selectedOpt.value);

        if (typeSelect.value !== 'in' || !hasProduct) {
            qtyInput.removeAttribute('max');
            qtyInput.setCustomValidity('');
            if (hintEl) hintEl.classList.add('hidden');
            if (errEl) {
                errEl.classList.add('hidden');
                errEl.classList.remove('flex');
            }
            isStockLimitInvalidState = false;
            updateAdjustmentSubmitButton();
            return true;
        }

        const currentStock = parseInt(selectedOpt.dataset.stock || '0', 10) || 0;
        const productName = selectedOpt.dataset.name || 'this product';
        const remaining = Math.max(0, MAX_STOCK_LIMIT - currentStock);
        const qty = parseInt(qtyInput.value || '0', 10) || 0;

        if (remaining > 0) {
            qtyInput.max = String(remaining);
            if (hintEl) {
                hintEl.textContent = `Remaining stock capacity: ${remaining} of ${MAX_STOCK_LIMIT} units (to avoid spoilage).`;
                hintEl.classList.remove('hidden');
            }
        } else {
            qtyInput.max = '0';
            if (hintEl) hintEl.classList.add('hidden');
        }

        let invalidMsg = '';
        if (remaining <= 0) {
            invalidMsg = `Stock limit reached for ${productName} (${currentStock}/${MAX_STOCK_LIMIT} units). Stock In is blocked until stock drops below ${MAX_STOCK_LIMIT}.`;
        } else if (qty > remaining) {
            invalidMsg = `Cannot add ${qty} units. Remaining capacity for ${productName} is ${remaining} units (max ${MAX_STOCK_LIMIT} to avoid spoilage).`;
        }

        if (invalidMsg) {
            if (errText) errText.textContent = invalidMsg;
            if (errEl) {
                errEl.classList.remove('hidden');
                errEl.classList.add('flex');
            }
            qtyInput.setCustomValidity(invalidMsg);
            isStockLimitInvalidState = true;
            updateAdjustmentSubmitButton();
            return false;
        }

        if (errEl) {
            errEl.classList.add('hidden');
            errEl.classList.remove('flex');
        }
        qtyInput.setCustomValidity('');
        isStockLimitInvalidState = false;
        updateAdjustmentSubmitButton();
        return true;
    }

    function setExpiryErrorState(isInvalid, message = '', clearServerError = true) {
        const expiryInput = document.getElementById('expiration_date');
        const errContainer = document.getElementById('stock_date_error');
        const errText = document.getElementById('stock_date_error_text');
        const serverErr = document.getElementById('stock_date_server_error');

        if (!expiryInput) return;

        if (isInvalid) {
            if (serverErr) {
                serverErr.classList.add('hidden');
                serverErr.classList.remove('flex');
            }
            if (errContainer && errText) {
                errText.textContent = message;
                errContainer.classList.remove('hidden');
                errContainer.classList.add('flex');
            }
            expiryInput.classList.remove('bg-slate-50', 'border-slate-200', 'text-slate-900', 'focus:ring-sky-500/20', 'focus:border-sky-500');
            expiryInput.classList.add('bg-rose-50/40', 'border-rose-500', 'text-rose-900', 'focus:ring-rose-500/20', 'focus:border-rose-500');
            expiryInput.setCustomValidity(message);
            expiryInput.setAttribute('aria-invalid', 'true');

            isExpiryInvalidState = true;
            updateAdjustmentSubmitButton();
            return;
        }

        if (errContainer) {
            errContainer.classList.add('hidden');
            errContainer.classList.remove('flex');
        }
        if (clearServerError && serverErr) {
            serverErr.classList.add('hidden');
            serverErr.classList.remove('flex');
        }

        const hasVisibleServerError = serverErr && !serverErr.classList.contains('hidden');
        if (!hasVisibleServerError) {
            expiryInput.classList.remove('bg-rose-50/40', 'border-rose-500', 'text-rose-900', 'focus:ring-rose-500/20', 'focus:border-rose-500');
            expiryInput.classList.add('bg-slate-50', 'border-slate-200', 'text-slate-900', 'focus:ring-sky-500/20', 'focus:border-sky-500');
            expiryInput.removeAttribute('aria-invalid');
        }

        expiryInput.setCustomValidity('');
        isExpiryInvalidState = false;
        updateAdjustmentSubmitButton();
    }

    function syncStockDates(clearServerError = true) {
        const typeSelect = document.getElementById('adjustment_type');
        const mfgInput = document.getElementById('manufacturing_date');
        const expiryInput = document.getElementById('expiration_date');

        if (!mfgInput || !expiryInput) return true;

        if (typeSelect && typeSelect.value === 'out') {
            setExpiryErrorState(false, '', true);
            return true;
        }

        const mfgVal = mfgInput.value;
        const expVal = expiryInput.value;

        if (mfgVal) {
            // Directly bind minimum selectable expiration date to the manufacturing date
            expiryInput.min = mfgVal;

            if (expVal && expVal < mfgVal) {
                const msg = `Expiration date must be on or after the manufacturing date (${formatIsoDate(mfgVal)}).`;
                setExpiryErrorState(true, msg, true);
                return false;
            }
        } else {
            expiryInput.removeAttribute('min');
        }

        setExpiryErrorState(false, '', clearServerError);
        return true;
    }

    function toggleExpiryField(isInitialLoad = false) {
        const typeSelect = document.getElementById('adjustment_type');
        const datesContainer = document.getElementById('dates_container');
        const mfgInput = document.getElementById('manufacturing_date');
        const expiryInput = document.getElementById('expiration_date');

        if (typeSelect.value === 'out') {
            datesContainer.style.display = 'none';
            expiryInput.removeAttribute('required');
            expiryInput.disabled = true;
            mfgInput.disabled = true;
            syncStockDates(true);
        } else {
            datesContainer.style.display = 'block';
            expiryInput.disabled = false;
            mfgInput.disabled = false;
            expiryInput.setAttribute('required', 'required');
            syncStockDates(!isInitialLoad);
        }
        syncStockLimitState();
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleExpiryField(true);
        syncStockLimitState();

        const form = document.getElementById('stockAdjustmentForm');
        const expiryInput = document.getElementById('expiration_date');
        const mfgInput = document.getElementById('manufacturing_date');
        const typeSelect = document.getElementById('adjustment_type');

        if (form) {
            form.addEventListener('submit', function (e) {
                if (typeSelect && typeSelect.value === 'in') {
                    if (!syncStockLimitState()) {
                        e.preventDefault();
                        const qtyInput = document.getElementById('quantity');
                        if (qtyInput) qtyInput.focus();
                        return;
                    }
                    if (!syncStockDates(true)) {
                        e.preventDefault();
                        if (expiryInput) expiryInput.focus();
                    }
                }
            });
        }

        if (expiryInput) {
            expiryInput.addEventListener('invalid', function (e) {
                if (typeSelect && typeSelect.value === 'in' && mfgInput && mfgInput.value && expiryInput.value && expiryInput.value < mfgInput.value) {
                    e.preventDefault();
                    syncStockDates(true);
                    expiryInput.focus();
                }
            });
        }
    });
</script>
@endsection
