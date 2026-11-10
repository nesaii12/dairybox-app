@extends('layouts.admin')

@section('title', 'Inventory Management')
@section('page_title', 'Inventory Management')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Left Column: Adjustment Form Card -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-6 lg:sticky lg:top-24">
            <h3 class="text-lg font-bold text-slate-950 mb-1">Stock Management</h3>
            <p class="text-xs text-slate-500 mb-6">Manually adjust warehouse inventory levels. Stock additions will create new FIFO batches.</p>
            
            <form action="{{ route('admin.inventory.adjust') }}" method="POST" class="space-y-4">
                @csrf
                <!-- Product Selector -->
                <div>
                    <label for="product_id" class="block text-sm font-semibold text-slate-700">Select Product</label>
                    <select name="product_id" id="product_id" required
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                        <option value="">-- Choose Product --</option>
                        @foreach($productsForForm as $p)
                            <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->product_name }} ({{ $p->size }}) - Stock: {{ $p->stock }}
                            </option>
                        @endforeach
                    </select>
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
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                </div>

                <!-- Batch Dates (Only for Stock In) -->
                <div id="dates_container" class="space-y-4">
                    <div>
                        <label for="manufacturing_date" class="block text-sm font-semibold text-slate-700">Manufacturing Date</label>
                        <input type="date" name="manufacturing_date" id="manufacturing_date" value="{{ old('manufacturing_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}"
                            class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                    </div>
                    <div>
                        <label for="expiration_date" class="block text-sm font-semibold text-slate-700">Expiration Date</label>
                        <input type="date" name="expiration_date" id="expiration_date" value="{{ old('expiration_date') }}"
                            class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                    </div>
                </div>

                <!-- Remarks -->
                <div>
                    <label for="remarks" class="block text-sm font-semibold text-slate-700">Remarks / Reason</label>
                    <textarea name="remarks" id="remarks" required rows="2"
                        class="mt-1.5 block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                        placeholder="e.g. New delivery batch, damaged stock dump..."></textarea>
                </div>
                
                <button type="submit" class="w-full flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100">
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

<!-- Bottom Panel: Stock Overview Table -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Overall Stock</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Manufacture Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Expiry Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($products as $product)
                @php
                    $today        = now()->startOfDay();
                    $sevenDaysOut = now()->addDays(7)->endOfDay();
                    $sevenDaysAgo = now()->subDays(7)->startOfDay();

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

                    // New stock: most recently added batch within 7 days
                    $newBatch = $activeBatches
                        ->filter(fn($b) => $b->created_at >= $sevenDaysAgo)
                        ->sortByDesc('created_at')
                        ->first();

                    // Pick display batch based on active filter
                    if ($filter === 'near_expiry') {
                        $displayBatch = $nearExpiryBatch ?? $firstBatch;
                    } elseif ($filter === 'new_stock') {
                        // Show ONLY the new batch for this product
                        $displayBatch = $newBatch;
                    } else {
                        // "All" — show the most relevant batch: near expiry > new > first > expired
                        $displayBatch = $nearExpiryBatch ?? $newBatch ?? $firstBatch ?? $expiredBatch;
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

                    <!-- Overall Stock -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-bold {{ $product->stock == 0 ? 'text-slate-400' : ($isLowStock ? 'text-rose-600' : 'text-slate-900') }}">
                            {{ $product->stock }} units
                        </span>
                        @if($product->stock == 0)
                            <span class="flex items-center gap-1 mt-0.5 w-max px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-400 border border-slate-200">
                                <i class="bi bi-dash-circle"></i> Inactive
                            </span>
                        @elseif($isLowStock)
                            <span class="block text-[9px] font-bold text-rose-500">Needs Replenishing</span>
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
                        @if($displayBatch && $mfgDateRaw)
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
                            <span class="block text-[10px] text-slate-400">Batch #{{ $displayBatch->id }}</span>
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
                        @elseif($filter === 'new_stock')
                            <i class="bi bi-box-seam text-3xl text-slate-300 mb-2 block"></i>
                            <p class="text-xs text-slate-500 font-medium">No new stock added in the past 7 days.</p>
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
@endsection

@section('scripts')
<script>
    function toggleExpiryField() {
        const typeSelect = document.getElementById('adjustment_type');
        const datesContainer = document.getElementById('dates_container');
        const expiryInput = document.getElementById('expiration_date');

        if (typeSelect.value === 'out') {
            datesContainer.style.display = 'none';
            expiryInput.removeAttribute('required');
        } else {
            datesContainer.style.display = 'block';
            expiryInput.setAttribute('required', 'required');
        }
    }

    // Run on startup to align view state
    document.addEventListener('DOMContentLoaded', toggleExpiryField);
</script>
@endsection
