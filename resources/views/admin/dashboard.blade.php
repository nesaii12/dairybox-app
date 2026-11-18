@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Dashboard Summary')

@section('content')
<!-- Warm Dairy Welcome & Quick Operational Overview Banner -->
<div class="mb-6 sm:mb-8 p-4 sm:p-6 rounded-2xl sm:rounded-3xl bg-gradient-to-r from-amber-500/[0.08] via-yellow-400/[0.05] to-amber-200/[0.03] border border-amber-200/70 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 relative overflow-hidden">
    <div class="absolute -right-8 -top-8 w-36 h-36 rounded-full bg-yellow-300/20 blur-2xl pointer-events-none"></div>
    <div class="space-y-1 relative z-10">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-100/80 border border-amber-200 text-[10px] font-bold text-amber-900 uppercase tracking-wide shadow-2xs">
            <i class="bi bi-sun-fill text-amber-500"></i>
            <span>Fresh Operations Overview</span>
        </div>
        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            Good day, {{ auth()->user()->name }}
        </h2>
        <p class="text-xs text-slate-600 max-w-xl">
            Live overview of sales revenue, inventory thresholds, and incoming customer fulfillment.
        </p>
    </div>
    <div class="flex items-center gap-2 relative z-10 shrink-0 w-full sm:w-auto flex-wrap sm:flex-nowrap">
        <button type="button" onclick="openWalkInModal()" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 sm:py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 border border-emerald-600 transition-all shadow-2xs active:scale-95 cursor-pointer">
            <i class="bi bi-plus-circle-fill"></i>
            <span>New Walk-in Sale</span>
        </button>
        <a href="{{ route('admin.orders.index') }}" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 sm:py-2 rounded-xl text-xs font-bold text-amber-950 bg-amber-100 hover:bg-amber-200/80 border border-amber-200 transition-all shadow-2xs active:scale-95">
            <i class="bi bi-cart3"></i>
            <span>Manage Orders</span>
        </a>
        <a href="{{ route('home') }}" target="_blank" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 sm:py-2 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-2xs active:scale-95">
            <i class="bi bi-shop"></i>
            <span>Storefront</span>
        </a>
    </div>
</div>

@if(session('success'))
<div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-between shadow-xs">
    <div class="flex items-center gap-2">
        <i class="bi bi-check-circle-fill text-emerald-600 text-base"></i>
        <span>{{ session('success') }}</span>
    </div>
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 cursor-pointer">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
@endif

@if(session('error'))
<div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center justify-between shadow-xs">
    <div class="flex items-center gap-2">
        <i class="bi bi-exclamation-octagon-fill text-rose-600 text-base"></i>
        <span>{{ session('error') }}</span>
    </div>
    <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 cursor-pointer">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
@endif

<!-- KPI Cards Row -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6 mb-6 sm:mb-8">
    <!-- Sales Card -->
    <div class="bg-white rounded-2xl border border-amber-100/90 hover:border-amber-300/80 p-4 sm:p-5 shadow-xs hover:shadow-sm transition-all flex items-center justify-between">
        <div class="min-w-0 pr-2">
            <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Total Revenue</span>
            <span class="text-xl sm:text-2xl font-black text-slate-900 block mt-1 truncate">₱{{ number_format($totalSales, 2) }}</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-2xs shrink-0">
            <i class="bi bi-wallet2 text-lg"></i>
        </div>
    </div>

    <!-- Orders Card -->
    <div class="bg-white rounded-2xl border border-amber-100/90 hover:border-amber-300/80 p-4 sm:p-5 shadow-xs hover:shadow-sm transition-all flex items-center justify-between">
        <div class="min-w-0 pr-2">
            <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Total Orders</span>
            <span class="text-xl sm:text-2xl font-black text-slate-900 block mt-1 truncate">{{ $totalOrders }}</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-slate-900 text-sky-400 flex items-center justify-center border border-slate-800 shadow-2xs shrink-0">
            <i class="bi bi-cart-check text-lg"></i>
        </div>
    </div>

    <!-- Products Card -->
    <div class="bg-white rounded-2xl border border-amber-100/90 hover:border-amber-300/80 p-4 sm:p-5 shadow-xs hover:shadow-sm transition-all flex items-center justify-between">
        <div class="min-w-0 pr-2">
            <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Active Catalog</span>
            <span class="text-xl sm:text-2xl font-black text-slate-900 block mt-1 truncate">{{ $totalProducts }}</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center border border-amber-200 shadow-2xs shrink-0">
            <i class="bi bi-egg-fried text-lg"></i>
        </div>
    </div>
</div>

<!-- Stock Alerts Sub-bar -->
@if($lowStockAlerts > 0 || $nearExpirationAlerts > 0)
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
    @if($lowStockAlerts > 0)
        <a href="{{ route('admin.inventory.index') }}" class="bg-rose-50 border border-rose-100 rounded-2xl p-4 flex items-center justify-between hover:bg-rose-100/50 transition-colors">
            <div class="flex items-center space-x-3 text-rose-800">
                <i class="bi bi-exclamation-triangle-fill text-xl text-rose-500"></i>
                <div>
                    <span class="block text-xs font-bold">Low Stock Warning</span>
                    <span class="text-[10px] text-rose-600 font-semibold">{{ $lowStockAlerts }} items are below minimum stock thresholds (<10 units).</span>
                </div>
            </div>
            <span class="bg-rose-600 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow">Replenish</span>
        </a>
    @endif

    @if($nearExpirationAlerts > 0)
        <a href="{{ route('admin.products.index') }}" class="bg-[#fefce8] border border-amber-200/90 rounded-2xl p-4 flex items-center justify-between hover:bg-amber-100/60 transition-colors">
            <div class="flex items-center space-x-3 text-amber-900">
                <i class="bi bi-clock-history text-xl text-amber-600 animate-pulse"></i>
                <div>
                    <span class="block text-xs font-bold">Upcoming Batch Expirations</span>
                    <span class="text-[10px] text-amber-700 font-semibold">{{ $nearExpirationAlerts }} products are expiring within the next 7 days.</span>
                </div>
            </div>
            <span class="bg-amber-500 text-slate-950 text-xs px-2.5 py-0.5 rounded-full font-bold shadow-2xs">Inspect</span>
        </a>
    @endif
</div>
@endif

<!-- Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8 mb-8">
    <!-- Sales trend Line Chart -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-amber-100/90 shadow-xs p-4 sm:p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-4 sm:mb-6 flex items-center space-x-1.5">
            <i class="bi bi-graph-up text-sky-600"></i>
            <span>Monthly Revenue Sales Trend</span>
        </h3>
        <div class="h-56 sm:h-64">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Best Seller Bar Chart -->
    <div class="lg:col-span-1 bg-white rounded-2xl border border-amber-100/90 shadow-xs p-4 sm:p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-4 sm:mb-6 flex items-center space-x-1.5">
            <i class="bi bi-bar-chart-fill text-amber-600"></i>
            <span>Best Selling Product Volumes</span>
        </h3>
        <div class="h-56 sm:h-64">
            <canvas id="productsChart"></canvas>
        </div>
    </div>
</div>

<!-- Bottom Area: Recent Orders -->
<div class="bg-white rounded-2xl border border-amber-100/90 shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-amber-100/80 bg-[#fefce8]/60 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-950">Recent Customer Placements</h3>
        <a href="{{ route('admin.orders.index') }}" class="text-xs text-sky-600 hover:text-sky-700 font-bold">See All Orders</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-xs">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left font-bold text-slate-500 uppercase">Order No</th>
                    <th scope="col" class="px-6 py-3 text-left font-bold text-slate-500 uppercase">Customer</th>
                    <th scope="col" class="px-6 py-3 text-left font-bold text-slate-500 uppercase">Mode</th>
                    <th scope="col" class="px-6 py-3 text-left font-bold text-slate-500 uppercase">Total Amount</th>
                    <th scope="col" class="px-6 py-3 text-left font-bold text-slate-500 uppercase">Status</th>
                    <th scope="col" class="px-6 py-3 text-center font-bold text-slate-500 uppercase">Fulfill</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($recentOrders as $order)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-3.5 font-bold text-slate-900">{{ $order->order_number }}</td>
                    <td class="px-6 py-3.5 font-semibold text-slate-800">{{ $order->customer?->name ?? 'Walk-in Customer' }}</td>
                    <td class="px-6 py-3.5 text-slate-600">
                        @if($order->order_type === 'Walk-in')
                            <span class="inline-flex items-center gap-1 font-semibold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-100 text-[11px]">
                                <i class="bi bi-person-walking"></i> Walk-in
                            </span>
                        @elseif($order->order_type === 'Delivery')
                            <span class="inline-flex items-center gap-1 font-medium text-sky-700 text-[11px]">
                                <i class="bi bi-truck"></i> Delivery
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 font-medium text-amber-700 text-[11px]">
                                <i class="bi bi-shop"></i> Pickup
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-3.5 font-black text-slate-950">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td class="px-6 py-3.5">
                        @php
                            $statusColors = [
                                'Pending' => 'bg-amber-50 text-amber-700',
                                'Confirmed' => 'bg-sky-50 text-sky-700',
                                'Preparing' => 'bg-indigo-50 text-indigo-700',
                                'Ready for Pickup' => 'bg-emerald-50 text-emerald-700',
                                'Out for Delivery' => 'bg-purple-50 text-purple-700',
                                'Delivered' => 'bg-emerald-500/10 text-emerald-700',
                                'Cancelled' => 'bg-rose-50 text-rose-600',
                            ];
                            $color = $statusColors[$order->order_status] ?? 'bg-slate-50 text-slate-600';
                        @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full font-semibold {{ $color }}">{{ $order->order_status }}</span>
                    </td>
                    <td class="px-6 py-3.5 text-center">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-sky-600 hover:text-sky-500 font-bold">Process</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-slate-400 italic">No orders logged in queue yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== DEMAND FORECAST SUMMARY SECTION ===== --}}
<div class="mt-8">
    {{-- Section Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="bi bi-graph-up-arrow text-indigo-600"></i>
                Demand Forecast Summary
                <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full">{{ $targetMonthLabel }}</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Predicted product demand based on historical sales analysis (Linear Regression + Seasonal Indexing).</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if($forecastedCount > 0)
                <span class="text-[10px] text-slate-500 font-semibold">{{ $forecastedCount }} product(s) with active forecasts</span>
            @endif
            <a href="{{ route('admin.forecast.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 transition-all shadow-2xs">
                <i class="bi bi-cpu"></i>
                <span>Manage Forecasts</span>
            </a>
        </div>
    </div>

    @if($forecastedCount === 0)
        {{-- No forecasts generated yet --}}
        <div class="bg-white border border-slate-200/80 rounded-2xl p-8 text-center shadow-xs">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-500 flex items-center justify-center mx-auto mb-3">
                <i class="bi bi-graph-up-arrow text-2xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-800 mb-1">No Forecasts Generated Yet</p>
            <p class="text-xs text-slate-500 max-w-sm mx-auto mb-4">Go to the Forecasting page to generate demand predictions for your products. Forecasts update automatically every day based on new completed sales.</p>
            <a href="{{ route('admin.forecast.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-all shadow shadow-indigo-100">
                <i class="bi bi-cpu"></i>
                Go to Forecasting
            </a>
        </div>
    @else
        {{-- Summary KPI Mini Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6">
            {{-- Top Demand Product --}}
            <div class="bg-white rounded-2xl border border-indigo-100/80 p-4 shadow-xs flex items-center justify-between gap-3">
                <div class="min-w-0 pr-2">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Highest Predicted Demand</span>
                    @if($topDemandProducts->isNotEmpty())
                        <span class="text-sm font-black text-slate-900 block mt-1 truncate">
                            {{ round($topDemandProducts->first()['predicted_sales'], 0) }} units
                        </span>
                        <span class="text-[10px] text-slate-500 truncate block">{{ $topDemandProducts->first()['name'] }}</span>
                    @else
                        <span class="text-sm font-black text-slate-400 block mt-1">—</span>
                    @endif
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 shadow-2xs shrink-0">
                    <i class="bi bi-arrow-up-right text-lg"></i>
                </div>
            </div>

            {{-- Expected Revenue --}}
            <div class="bg-white rounded-2xl border border-indigo-100/80 p-4 shadow-xs flex items-center justify-between gap-3">
                <div class="min-w-0 pr-2">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Expected Revenue</span>
                    <span class="text-sm font-black text-slate-900 block mt-1 truncate">
                        ₱{{ number_format($totalForecastRevenue, 2) }}
                    </span>
                    <span class="text-[10px] text-slate-500 block">Projected for {{ $targetMonthLabel }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-2xs shrink-0">
                    <i class="bi bi-currency-exchange text-lg"></i>
                </div>
            </div>

            {{-- Stockout Risks --}}
            <div class="bg-white rounded-2xl border border-indigo-100/80 p-4 shadow-xs flex items-center justify-between gap-3">
                <div class="min-w-0 pr-2">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Stockout Risks Detected</span>
                    @php
                        $criticalCount = $stockoutRiskProducts->where('stockout_risk', 'critical')->count();
                        $lowCount = $stockoutRiskProducts->where('stockout_risk', 'low')->count();
                    @endphp
                    <span class="text-sm font-black {{ ($criticalCount + $lowCount) > 0 ? 'text-rose-600' : 'text-emerald-600' }} block mt-1">
                        {{ $criticalCount + $lowCount }} product(s)
                    </span>
                    <span class="text-[10px] text-slate-500 block">{{ $criticalCount }} critical · {{ $lowCount }} low</span>
                </div>
                <div class="w-10 h-10 rounded-xl {{ ($criticalCount + $lowCount) > 0 ? 'bg-rose-50 text-rose-500 border-rose-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100' }} flex items-center justify-center border shadow-2xs shrink-0">
                    <i class="bi bi-exclamation-triangle-fill text-lg"></i>
                </div>
            </div>
        </div>

        {{-- Main Forecast Grid: Top Demand + Stockout Risk side by side --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Top Predicted Demand Table --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/40 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <i class="bi bi-bar-chart-steps text-indigo-500"></i>
                            Top Predicted Demand
                        </h4>
                        <p class="text-[10px] text-slate-400 mt-0.5">Products with highest expected orders in {{ $targetMonthLabel }}</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="bg-slate-50/60 border-b border-slate-100 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="px-5 py-3">Product</th>
                                <th class="px-5 py-3 text-right">Forecast</th>
                                <th class="px-5 py-3 text-right">Expected Revenue</th>
                                <th class="px-5 py-3 text-right">Restock Needed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($topDemandProducts as $item)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3.5">
                                    <span class="font-bold text-slate-900 block truncate max-w-[150px]">{{ $item['name'] }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $item['category'] }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span class="font-black text-indigo-600">{{ round($item['predicted_sales'], 0) }}</span>
                                    <span class="text-slate-400 font-medium"> units</span>
                                </td>
                                <td class="px-5 py-3.5 text-right font-bold text-emerald-700">
                                    ₱{{ number_format($item['expected_revenue'], 2) }}
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if($item['recommended_restock'] > 0)
                                        <span class="font-bold text-amber-700">+{{ $item['recommended_restock'] }} units</span>
                                    @else
                                        <span class="text-emerald-600 font-semibold">Sufficient</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-5 py-6 text-center text-slate-400 italic text-xs">No forecast data available yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Stockout Risk Table --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/40 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            <i class="bi bi-exclamation-diamond-fill text-rose-500"></i>
                            Products at Stockout Risk
                        </h4>
                        <p class="text-[10px] text-slate-400 mt-0.5">Current stock may not meet forecasted demand for {{ $targetMonthLabel }}</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    @if($stockoutRiskProducts->isEmpty())
                        <div class="px-5 py-10 text-center">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-2 border border-emerald-100">
                                <i class="bi bi-check-circle-fill text-xl"></i>
                            </div>
                            <p class="text-xs font-bold text-slate-800 mb-1">Stock Levels Look Good</p>
                            <p class="text-[10px] text-slate-400">No products are at risk of running out based on current forecasts.</p>
                        </div>
                    @else
                        <table class="w-full text-xs text-left">
                            <thead>
                                <tr class="bg-slate-50/60 border-b border-slate-100 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                                    <th class="px-5 py-3">Product</th>
                                    <th class="px-5 py-3 text-right">Stock</th>
                                    <th class="px-5 py-3 text-right">Forecast</th>
                                    <th class="px-5 py-3 text-center">Risk Level</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($stockoutRiskProducts as $item)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-5 py-3.5">
                                        <span class="font-bold text-slate-900 block truncate max-w-[150px]">{{ $item['name'] }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $item['category'] }}</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-semibold text-slate-700">
                                        {{ $item['current_stock'] }} units
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-black text-indigo-600">
                                        {{ round($item['predicted_sales'], 0) }} units
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($item['stockout_risk'] === 'critical')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                                <i class="bi bi-exclamation-circle-fill text-[9px]"></i>
                                                Critical
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                                <i class="bi bi-exclamation-triangle-fill text-[9px]"></i>
                                                Low Stock
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($stockoutRiskProducts->count() >= 5)
                        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/40 text-center">
                            <a href="{{ route('admin.forecast.index') }}" class="text-xs text-sky-600 hover:text-sky-700 font-bold">View full forecast →</a>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<!-- ========================================== -->
<!-- Walk-in Sale Modal Dialog                  -->
<!-- ========================================== -->
<div id="walkInModal" class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-5 bg-slate-950/60 backdrop-blur-xs overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="relative bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-2xl w-full max-w-3xl my-auto overflow-hidden flex flex-col max-h-[92vh]">
        <!-- Modal Header -->
        <div class="p-5 sm:p-6 border-b border-slate-100 bg-gradient-to-r from-emerald-500/[0.06] via-transparent to-amber-500/[0.04] flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg font-bold shrink-0 shadow-2xs">
                    <i class="bi bi-cart-plus-fill"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 id="modal-title" class="text-base sm:text-lg font-black text-slate-900 tracking-tight">New Walk-in Sale</h3>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                            <i class="bi bi-person-walking"></i> Walk-in
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5">Record counter sale, deduct FIFO product stock, and update sales forecasts immediately.</p>
                </div>
            </div>
            <button type="button" onclick="closeWalkInModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors cursor-pointer shrink-0" aria-label="Close modal">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="{{ route('admin.walkInSales.store') }}" method="POST" id="dashboardWalkInForm" class="flex flex-col flex-1 overflow-hidden">
            @csrf

            <!-- Scrollable Form Body -->
            <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
                <!-- Customer & Payment Context -->
                <div class="bg-slate-50/80 border border-slate-200/70 rounded-2xl p-4 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Customer Type -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Customer Type</label>
                            <select name="customer_type" id="modal_customer_type" onchange="toggleModalCustomer(this.value)" class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                                <option value="walkin" selected>Walk-in Guest</option>
                                <option value="registered">Registered Customer</option>
                            </select>
                        </div>

                        <!-- Customer Input / Select -->
                        <div id="modal_walkin_name_field">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Customer Reference / Name (Optional)</label>
                            <input type="text" name="customer_name" placeholder="e.g. Counter Guest / Juan Dela Cruz" class="w-full text-xs rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                        </div>

                        <div id="modal_registered_field" class="hidden">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Registered Customer</label>
                            <select name="customer_id" class="w-full text-xs rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                                <option value="">-- Choose Customer --</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Payment Method</label>
                            <select name="payment_method" class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                                <option value="cash" selected>Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Notes / Remarks (Optional)</label>
                        <input type="text" name="notes" placeholder="e.g. Paid in exact amount, official receipt provided" class="w-full text-xs rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                    </div>
                </div>

                <!-- Products Selection Section -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="bi bi-box-seam text-slate-500"></i>
                            <span>Products to Sell</span>
                        </span>
                        <button type="button" onclick="addModalProductRow()" class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all cursor-pointer">
                            <i class="bi bi-plus-circle"></i>
                            <span>Add Product</span>
                        </button>
                    </div>

                    <!-- Table Header on Desktop -->
                    <div class="hidden sm:grid grid-cols-12 gap-3 px-3 py-1.5 text-[9px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50 rounded-xl">
                        <div class="col-span-5">Product & Stock</div>
                        <div class="col-span-2">Quantity</div>
                        <div class="col-span-2">Price (₱)</div>
                        <div class="col-span-2 text-right">Subtotal</div>
                        <div class="col-span-1 text-center">Remove</div>
                    </div>

                    <!-- Dynamic Rows Container -->
                    <div id="modalItemsContainer" class="space-y-2.5">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Modal Footer / Sticky Summary Bar -->
            <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="w-full sm:w-auto flex items-center justify-between sm:justify-start gap-4">
                    <div>
                        <span class="block text-[9px] uppercase font-bold text-slate-400">Total Items</span>
                        <span id="modalGrandQty" class="text-xs sm:text-sm font-black text-slate-900">0 units</span>
                    </div>
                    <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
                    <div>
                        <span class="block text-[9px] uppercase font-bold text-slate-400">Total Amount</span>
                        <span id="modalGrandTotal" class="text-lg sm:text-2xl font-black text-emerald-600">₱0.00</span>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex items-center gap-2">
                    <button type="button" onclick="closeWalkInModal()" class="flex-1 sm:flex-initial px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="modalSubmitBtn" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 border border-emerald-600 shadow-sm transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="bi bi-check2-circle text-sm"></i>
                        <span>Complete Walk-in Sale</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<!-- Chart.js Library CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Sales Trend line chart
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx) {
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($salesLabels) !!},
                    datasets: [{
                        label: 'Monthly Sales Revenue',
                        data: {!! json_encode($salesValues) !!},
                        borderColor: '#0284c7', // sky-600
                        backgroundColor: 'rgba(2, 132, 199, 0.05)',
                        borderWidth: 3.5,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#0284c7'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 2. Best Sellers Bar Chart
        const productsCtx = document.getElementById('productsChart');
        if (productsCtx) {
            new Chart(productsCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($productLabels) !!},
                    datasets: [{
                        data: {!! json_encode($productValues) !!},
                        backgroundColor: '#f59e0b', // amber-500 dairy theme
                        borderRadius: 6,
                        barThickness: 20
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });

    // ==========================================
    // Walk-in Sale Modal Interactive Controller
    // ==========================================
    const dashboardAvailableProducts = @json($activeProducts);
    let modalRowCounter = 0;

    function openWalkInModal() {
        const modal = document.getElementById('walkInModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        // If no rows exist, initialize first row
        const container = document.getElementById('modalItemsContainer');
        if (container && container.children.length === 0) {
            addModalProductRow();
        }
    }

    function closeWalkInModal() {
        const modal = document.getElementById('walkInModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    // Close on Escape or click outside
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeWalkInModal();
        }
    });

    document.getElementById('walkInModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeWalkInModal();
        }
    });

    function toggleModalCustomer(val) {
        const walkinField = document.getElementById('modal_walkin_name_field');
        const regField = document.getElementById('modal_registered_field');
        if (val === 'registered') {
            walkinField?.classList.add('hidden');
            regField?.classList.remove('hidden');
        } else {
            walkinField?.classList.remove('hidden');
            regField?.classList.add('hidden');
        }
    }

    function addModalProductRow(preselectedKey = '', quantity = 1, customPrice = '') {
        modalRowCounter++;
        const rowId = `modal_item_row_${modalRowCounter}`;
        const container = document.getElementById('modalItemsContainer');
        if (!container) return;

        let optionsHtml = '<option value="">-- Choose Active Product --</option>';
        dashboardAvailableProducts.forEach(p => {
            const isOutOfStock = p.stock <= 0;
            const hasMultipleSizes = p.sizes && p.sizes.length > 1;

            if (hasMultipleSizes) {
                optionsHtml += `<optgroup label="${p.product_name} — Current Stock: ${p.stock} (${p.sizes_summary})">`;
                p.sizes.forEach(s => {
                    const optKey = `${p.id}:${s.size}`;
                    const isSelected = optKey === preselectedKey ? 'selected' : '';
                    optionsHtml += `
                        <option value="${optKey}" ${isSelected} ${isOutOfStock ? 'disabled class="text-slate-400"' : ''} data-product-id="${p.id}" data-size="${s.size}" data-price="${s.price}" data-stock="${p.stock}">
                            ${p.product_name} (${s.size}) — ${s.formatted_price} (Current Stock: ${p.stock})
                        </option>
                    `;
                });
                optionsHtml += `</optgroup>`;
            } else {
                const singleSize = p.sizes && p.sizes[0] ? p.sizes[0] : { size: p.size, price: p.base_price, formatted_price: '₱' + p.base_price.toFixed(2) };
                const optKey = `${p.id}:${singleSize.size}`;
                const isSelected = optKey === preselectedKey ? 'selected' : '';
                optionsHtml += `
                    <option value="${optKey}" ${isSelected} ${isOutOfStock ? 'disabled class="text-slate-400"' : ''} data-product-id="${p.id}" data-size="${singleSize.size}" data-price="${singleSize.price}" data-stock="${p.stock}">
                        ${p.product_name} (${singleSize.size}) — ${singleSize.formatted_price} (Current Stock: ${p.stock})
                    </option>
                `;
            }
        });

        const row = document.createElement('div');
        row.id = rowId;
        row.className = "grid grid-cols-1 sm:grid-cols-12 gap-2.5 p-3 sm:p-2.5 bg-slate-50/70 sm:bg-white rounded-2xl sm:rounded-xl border border-slate-200/80 items-start transition-colors shadow-2xs";
        row.innerHTML = `
            <!-- Product Select & Size Selection -->
            <div class="sm:col-span-5 space-y-1">
                <label class="block sm:hidden text-[9px] font-bold uppercase text-slate-400">Product</label>
                <select name="items[${modalRowCounter}][selection]" required onchange="onModalProductChange('${rowId}')" class="modal-product-select w-full text-xs font-medium rounded-xl border-slate-200 bg-white py-1.5 px-2.5 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                    ${optionsHtml}
                </select>
                <input type="hidden" name="items[${modalRowCounter}][product_id]" class="modal-product-id-input" value="">
                <input type="hidden" name="items[${modalRowCounter}][size]" class="modal-size-input" value="">

                <div class="modal-stock-badge text-[10px] font-semibold text-slate-500 px-0.5"></div>
                <div class="modal-size-pills flex items-center gap-1 flex-wrap pt-0.5"></div>
            </div>

            <!-- Quantity -->
            <div class="sm:col-span-2 pt-0.5">
                <label class="block sm:hidden text-[9px] font-bold uppercase text-slate-400 mb-1">Quantity</label>
                <div class="flex items-center">
                    <input type="number" name="items[${modalRowCounter}][quantity]" value="${quantity}" min="1" required oninput="calculateModalTotals()" class="modal-qty-input w-full text-xs font-bold text-center rounded-xl border-slate-200 bg-white py-1.5 px-2 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                </div>
            </div>

            <!-- Unit Price -->
            <div class="sm:col-span-2 pt-0.5">
                <label class="block sm:hidden text-[9px] font-bold uppercase text-slate-400 mb-1">Price (₱)</label>
                <div class="relative">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">₱</span>
                    <input type="number" step="0.01" min="0" name="items[${modalRowCounter}][price]" value="${customPrice}" required oninput="calculateModalTotals()" class="modal-price-input w-full text-xs font-bold rounded-xl border-slate-200 bg-white py-1.5 pl-6 pr-2 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                </div>
            </div>

            <!-- Subtotal -->
            <div class="sm:col-span-2 flex items-center justify-between sm:justify-end pt-2">
                <span class="block sm:hidden text-[9px] font-bold uppercase text-slate-400">Subtotal</span>
                <span class="modal-subtotal-display text-xs sm:text-sm font-black text-slate-900">₱0.00</span>
            </div>

            <!-- Action -->
            <div class="sm:col-span-1 text-right sm:text-center pt-1">
                <button type="button" onclick="removeModalProductRow('${rowId}')" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Remove item">
                    <i class="bi bi-trash3 text-xs"></i>
                </button>
            </div>
        `;

        container.appendChild(row);

        if (preselectedKey) {
            onModalProductChange(rowId);
        } else {
            calculateModalTotals();
        }
    }

    function onModalProductChange(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const select = row.querySelector('.modal-product-select');
        const priceInput = row.querySelector('.modal-price-input');
        const qtyInput = row.querySelector('.modal-qty-input');
        const stockBadge = row.querySelector('.modal-stock-badge');
        const sizePills = row.querySelector('.modal-size-pills');
        const productIdInput = row.querySelector('.modal-product-id-input');
        const sizeInput = row.querySelector('.modal-size-input');

        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            stockBadge.innerHTML = '';
            sizePills.innerHTML = '';
            productIdInput.value = '';
            sizeInput.value = '';
            calculateModalTotals();
            return;
        }

        const productId = parseInt(selectedOption.getAttribute('data-product-id') || '0', 10);
        const currentSize = selectedOption.getAttribute('data-size') || '';
        const stock = parseInt(selectedOption.getAttribute('data-stock') || '0', 10);
        const defaultPrice = parseFloat(selectedOption.getAttribute('data-price') || '0');

        productIdInput.value = productId;
        sizeInput.value = currentSize;

        // Pre-fill price if empty or not overridden
        if (!priceInput.value || parseFloat(priceInput.value) <= 0 || !priceInput.dataset.manualOverride) {
            priceInput.value = defaultPrice.toFixed(2);
        }

        // Adjust qty limit
        qtyInput.max = stock > 0 ? stock : 1;
        if (parseInt(qtyInput.value, 10) > stock) {
            qtyInput.value = stock;
        }

        // Show live badge
        if (stock <= 5) {
            stockBadge.innerHTML = `<span class="text-rose-600 font-bold"><i class="bi bi-exclamation-triangle-fill"></i> Low: ${stock} left</span>`;
        } else {
            stockBadge.innerHTML = `<span class="text-emerald-700 font-bold"><i class="bi bi-check-circle-fill"></i> Current Stock: ${stock} units</span>`;
        }

        // Render size pills
        const productData = dashboardAvailableProducts.find(p => p.id === productId);
        if (productData && productData.sizes && productData.sizes.length > 1) {
            let pillsHtml = '<span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mr-0.5">Size:</span>';
            productData.sizes.forEach(s => {
                const isSelectedSize = s.size === currentSize;
                pillsHtml += `
                    <button type="button" onclick="selectModalRowSize('${rowId}', ${productId}, '${s.size}', ${s.price})" class="px-2 py-0.5 rounded-lg text-[10px] transition-all cursor-pointer ${isSelectedSize ? 'font-black bg-emerald-600 text-white shadow-2xs' : 'font-semibold bg-white text-slate-700 border border-slate-200 hover:border-emerald-500 hover:text-emerald-700'}">
                        ${s.size} (${s.formatted_price})
                    </button>
                `;
            });
            sizePills.innerHTML = pillsHtml;
        } else {
            sizePills.innerHTML = '';
        }

        calculateModalTotals();
    }

    function selectModalRowSize(rowId, productId, sizeName, sizePrice) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const select = row.querySelector('.modal-product-select');
        const priceInput = row.querySelector('.modal-price-input');
        const sizeInput = row.querySelector('.modal-size-input');

        const targetValue = `${productId}:${sizeName}`;
        select.value = targetValue;
        sizeInput.value = sizeName;
        priceInput.value = parseFloat(sizePrice).toFixed(2);
        delete priceInput.dataset.manualOverride;

        onModalProductChange(rowId);
    }

    function removeModalProductRow(rowId) {
        const container = document.getElementById('modalItemsContainer');
        const rows = container.querySelectorAll('[id^="modal_item_row_"]');
        if (rows.length <= 1) {
            alert('A walk-in sale requires at least one product.');
            return;
        }
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            calculateModalTotals();
        }
    }

    function calculateModalTotals() {
        const container = document.getElementById('modalItemsContainer');
        if (!container) return;
        const rows = container.querySelectorAll('[id^="modal_item_row_"]');

        let grandQty = 0;
        let grandTotal = 0.0;
        let hasError = false;

        rows.forEach(row => {
            const select = row.querySelector('.modal-product-select');
            const qtyInput = row.querySelector('.modal-qty-input');
            const priceInput = row.querySelector('.modal-price-input');
            const subtotalDisplay = row.querySelector('.modal-subtotal-display');

            const selectedOption = select.options[select.selectedIndex];
            const stock = selectedOption && selectedOption.value ? parseInt(selectedOption.getAttribute('data-stock') || '0', 10) : 0;

            const qty = parseInt(qtyInput.value, 10) || 0;
            const price = parseFloat(priceInput.value) || 0.0;

            if (selectedOption && selectedOption.value && qty > stock) {
                qtyInput.classList.add('border-rose-500', 'bg-rose-50');
                hasError = true;
            } else {
                qtyInput.classList.remove('border-rose-500', 'bg-rose-50');
            }

            const lineTotal = Math.max(0, qty * price);
            subtotalDisplay.innerText = `₱${lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            grandQty += qty;
            grandTotal += lineTotal;
        });

        const qtyDisplay = document.getElementById('modalGrandQty');
        const totalDisplay = document.getElementById('modalGrandTotal');
        const submitBtn = document.getElementById('modalSubmitBtn');

        if (qtyDisplay) qtyDisplay.innerText = `${grandQty} unit${grandQty === 1 ? '' : 's'}`;
        if (totalDisplay) totalDisplay.innerText = `₱${grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        if (submitBtn) {
            submitBtn.disabled = (grandQty <= 0 || grandTotal < 0 || hasError);
        }
    }
</script>
@endsection
