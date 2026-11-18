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
    <div class="flex items-center gap-2 relative z-10 shrink-0 w-full sm:w-auto">
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
                    <td class="px-6 py-3.5 font-semibold text-slate-800">{{ $order->customer->name }}</td>
                    <td class="px-6 py-3.5 text-slate-600">{{ $order->order_type }}</td>
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
</script>
@endsection
