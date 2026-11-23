@extends('layouts.admin')

@section('title', 'Demand Forecasting')
@section('page_title', 'Demand Forecasting')

@section('content')
<!-- Explanation Hero Section & Scope Filter -->
<div class="bg-gradient-to-tr from-sky-600 via-indigo-600 to-indigo-700 rounded-3xl p-6 sm:p-8 text-white shadow-md shadow-sky-100/30 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="bg-white/20 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Historical Trend & Seasonal Demand Intelligence</span>
                <span class="bg-amber-400/25 text-amber-200 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-amber-400/30">
                    <i class="bi bi-arrow-repeat mr-1"></i> Rolling YoY Restart
                </span>
                @if($rollingWindow['is_archived_2025'])
                    <span class="bg-rose-400/25 text-rose-200 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-rose-400/30">
                        2025 Data Archived
                    </span>
                @else
                    <span class="bg-sky-400/25 text-sky-200 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-sky-400/30">
                        2025 Active (Auto-archives in 2027)
                    </span>
                @endif
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-1">Sales Trend & Demand Forecasting</h2>
            <p class="text-xs sm:text-sm text-sky-100/90 mt-2.5 leading-relaxed">
                Prediction logic operates on a <strong>rolling, year-over-year restart</strong> model: currently using <strong>{{ $rollingWindow['basis_label'] }}</strong> as the foundational basis to predict <strong>{{ $rollingWindow['target_label'] }}</strong>. Once 2027 arrives, 2025 historical data is automatically archived, switching the primary forecast basis to 2026 Full-Year Sales to predict 2028 Demand.
            </p>
        </div>

        <!-- Product Scope Selector for Trend & Seasonal Charts -->
        <div class="bg-white/10 backdrop-blur-xs border border-white/20 rounded-2xl p-4 shrink-0 lg:w-80">
            <form action="{{ route('admin.forecast.index') }}" method="GET">
                @if(request()->filled('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <label for="product_filter" class="block text-[10px] font-bold uppercase tracking-wider text-sky-100 mb-1.5">
                    <i class="bi bi-funnel-fill mr-1"></i> Analyze Historical Scope
                </label>
                <select name="product_filter" id="product_filter" onchange="this.form.submit()"
                    class="w-full bg-white text-slate-900 rounded-xl px-3 py-2 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-sky-300 cursor-pointer shadow-xs">
                    <option value="">All Catalog Products (Storewide)</option>
                    @foreach($allProducts as $prodItem)
                        <option value="{{ $prodItem->id }}" {{ ($selectedProduct && $selectedProduct->id === $prodItem->id) ? 'selected' : '' }}>
                            {{ $prodItem->product_name }} ({{ $prodItem->size }})
                        </option>
                    @endforeach
                </select>
                @if($selectedProduct)
                    <div class="mt-2 flex items-center justify-between text-[11px] text-sky-100">
                        <span>Filtered: <strong>{{ $selectedProduct->product_name }}</strong></span>
                        <a href="{{ route('admin.forecast.index', request()->except('product_filter')) }}" class="underline hover:text-white font-semibold">Reset</a>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>

<!-- Historical Insights & Peak Performance Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <!-- Card 1: Peak Seasonal Demand Period -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Peak Seasonal Demand</span>
            @if($analytics['peak_seasonal_month'])
                <h3 class="text-lg font-black text-slate-900 mt-1 truncate">
                    {{ $analytics['peak_seasonal_month']['full_name'] }}
                </h3>
                <p class="text-[11px] text-slate-500 mt-1">
                    Avg <strong class="text-indigo-600">{{ number_format($analytics['peak_seasonal_month']['avg_qty'], 1) }} units</strong>
                    <span class="inline-block ml-1 px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[10px]">
                        {{ $analytics['peak_seasonal_month']['seasonal_index'] }}x index
                    </span>
                </p>
            @else
                <h3 class="text-sm font-bold text-slate-400 mt-1">No Completed Sales Yet</h3>
                <p class="text-[11px] text-slate-400 mt-1">Awaiting delivered order records</p>
            @endif
        </div>
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 shrink-0">
            <i class="bi bi-calendar-check-fill text-lg"></i>
        </div>
    </div>

    <!-- Card 2: Highest Recorded Period (Month & Year) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Best Historical Month</span>
            @if($analytics['peak_historical_period'])
                <h3 class="text-lg font-black text-slate-900 mt-1 truncate">
                    {{ $analytics['peak_historical_period']['label'] }}
                </h3>
                <p class="text-[11px] text-slate-500 mt-1">
                    <strong class="text-emerald-700">{{ number_format($analytics['peak_historical_period']['total_qty']) }} units</strong>
                    (₱{{ number_format($analytics['peak_historical_period']['total_revenue'], 2) }})
                </p>
            @else
                <h3 class="text-sm font-bold text-slate-400 mt-1">No Historical Peak</h3>
                <p class="text-[11px] text-slate-400 mt-1">No historical months recorded</p>
            @endif
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
            <i class="bi bi-trophy-fill text-lg"></i>
        </div>
    </div>

    <!-- Card 3: Sales Trajectory / Trend Direction -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Sales Trend Trajectory</span>
            @if($analytics['trend_direction'] === 'upward')
                <h3 class="text-lg font-black text-emerald-600 mt-1 flex items-center gap-1">
                    <i class="bi bi-graph-up-arrow"></i> Growing Demand
                </h3>
                <p class="text-[11px] text-slate-500 mt-1">
                    Slope: <strong>+{{ $analytics['trend_slope'] }}</strong> units/mo ({{ $analytics['months_analyzed'] }} mos)
                </p>
            @elseif($analytics['trend_direction'] === 'downward')
                <h3 class="text-lg font-black text-rose-600 mt-1 flex items-center gap-1">
                    <i class="bi bi-graph-down-arrow"></i> Slowing Demand
                </h3>
                <p class="text-[11px] text-slate-500 mt-1">
                    Slope: <strong>{{ $analytics['trend_slope'] }}</strong> units/mo ({{ $analytics['months_analyzed'] }} mos)
                </p>
            @elseif($analytics['trend_direction'] === 'stable')
                <h3 class="text-lg font-black text-sky-600 mt-1 flex items-center gap-1">
                    <i class="bi bi-arrow-left-right"></i> Steady Demand
                </h3>
                <p class="text-[11px] text-slate-500 mt-1">
                    Consistent across {{ $analytics['months_analyzed'] }} recorded months
                </p>
            @else
                <h3 class="text-sm font-bold text-slate-400 mt-1">Insufficient History</h3>
                <p class="text-[11px] text-slate-400 mt-1">Needs at least 2 months of sales</p>
            @endif
        </div>
        <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100 shrink-0">
            <i class="bi bi-activity text-lg"></i>
        </div>
    </div>

    <!-- Card 4: Inventory Restock Planning Summary -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Restock Planning ({{ $targetMonthLabel }})</span>
            <h3 class="text-lg font-black {{ $productsRequiringStock > 0 ? 'text-amber-700' : 'text-slate-900' }} mt-1 truncate">
                @if($totalRestockUnits > 0)
                    +{{ number_format($totalRestockUnits) }} units needed
                @else
                    Stock Sufficient
                @endif
            </h3>
            <p class="text-[11px] text-slate-500 mt-1">
                {{ $productsRequiringStock }} product(s) require additional stock
            </p>
        </div>
        <div class="w-10 h-10 rounded-xl {{ $productsRequiringStock > 0 ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-slate-100 text-slate-600 border-slate-200' }} flex items-center justify-center border shrink-0">
            <i class="bi bi-box-seam-fill text-lg"></i>
        </div>
    </div>
</div>

<!-- Historical Sales Comparison & Seasonal Demand Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Chart 1: Multi-Year & Monthly Historical Sales Comparison -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-950 flex items-center gap-1.5">
                    <i class="bi bi-bar-chart-line-fill text-sky-600"></i>
                    <span>Monthly & Year-over-Year Sales Comparison</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Compares unit sales across months and years ({{ $selectedProduct ? $selectedProduct->product_name . ' - ' . $selectedProduct->size : 'All Catalog Products' }})
                </p>
            </div>
            @if($analytics['years_analyzed'] > 0)
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-100 shrink-0">
                    {{ implode(' vs ', $analytics['years']) }}
                </span>
            @endif
        </div>

        <div class="h-64 relative w-full">
            @if($analytics['has_data'])
                <canvas id="yoyComparisonChart"></canvas>
            @else
                <div class="h-full flex flex-col items-center justify-center text-center px-4">
                    <div class="w-11 h-11 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center mb-2">
                        <i class="bi bi-bar-chart text-xl"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-700">No Historical Sales Recorded Yet</p>
                    <p class="text-[11px] text-slate-400 max-w-xs mt-0.5">Monthly and yearly sales comparisons will appear here once customer orders are marked as Delivered.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Chart 2: Seasonal Demand Pattern & Peak Performance Periods -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-950 flex items-center gap-1.5">
                    <i class="bi bi-sun-fill text-amber-500"></i>
                    <span>Seasonal Demand Pattern (Jan – Dec)</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Highlights calendar periods with consistently high sales performance vs. baseline average ({{ $analytics['overall_monthly_avg_qty'] }} units/mo)
                </p>
            </div>
            <div class="flex items-center gap-2 text-[10px] font-bold shrink-0">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> High Season
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Normal
                </span>
            </div>
        </div>

        <div class="h-64 relative w-full">
            @if($analytics['has_data'])
                <canvas id="seasonalPatternChart"></canvas>
            @else
                <div class="h-full flex flex-col items-center justify-center text-center px-4">
                    <div class="w-11 h-11 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center mb-2">
                        <i class="bi bi-calendar3 text-xl"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-700">No Seasonal Pattern Data Yet</p>
                    <p class="text-[11px] text-slate-400 max-w-xs mt-0.5">Seasonal demand indices will be calculated automatically as monthly sales history accumulates.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- Products Forecast & Inventory Planning Table (Columns 1-3) -->
    <div class="lg:col-span-3 space-y-4">
        @if(!empty($lastMonthStats['top_product']) && !empty($topForecastProduct))
        <div class="bg-indigo-50/70 border border-indigo-200/80 rounded-2xl p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <i class="bi bi-stars text-base"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-md">
                            Next Month Top Seller Projection
                        </span>
                        @if($lastMonthStats['reference_label'])
                            <span class="text-[11px] text-slate-500 font-medium">
                                Based on {{ $lastMonthStats['reference_label'] }} sales momentum & seasonal trend
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-slate-800 font-semibold mt-1">
                        <strong class="text-indigo-950 font-black">{{ $topForecastProduct['name'] }} ({{ $topForecastProduct['size'] }})</strong>
                        is projected to lead sales in <strong>{{ $targetMonthLabel }}</strong> with
                        <strong class="text-indigo-700">{{ number_format($topForecastProduct['predicted_sales'], 1) }} units</strong>
                        (₱{{ number_format($topForecastProduct['expected_revenue'], 2) }}),
                        driven by its strong performance in {{ $lastMonthStats['reference_label'] ?? 'the previous month' }}
                        @if($lastMonthStats['top_product']['product_id'] === $topForecastProduct['id'])
                            where it ranked <strong>#1 in sales ({{ number_format($lastMonthStats['top_product']['qty']) }} units / ₱{{ number_format($lastMonthStats['top_product']['revenue'], 2) }})</strong>.
                        @else
                             alongside historical seasonal demand.
                        @endif
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/20">
                <div>
                    <h3 class="text-sm font-bold text-slate-950">Product Demand Forecast & Inventory Planning</h3>
                    <p class="text-xs text-slate-500">Anticipate future demand for <strong>{{ $targetMonthLabel }}</strong> based on <strong>{{ $rollingWindow['basis_label'] }}</strong> baseline and seasonal patterns.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5 self-start sm:self-center">
                    <!-- Target Month Selection -->
                    <div class="flex items-center space-x-1.5 text-xs text-slate-500">
                        <span class="font-bold text-[11px] text-slate-600">Horizon:</span>
                        <select onchange="window.location.href = updateQueryStringParameter(window.location.href, 'target_month', this.value)" class="bg-white border border-indigo-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/10 focus:border-indigo-500 font-bold text-indigo-700 cursor-pointer text-xs shadow-2xs transition-all">
                            @for($m = 1; $m <= 12; $m++)
                                @php
                                    $optVal = sprintf('%04d-%02d', $rollingWindow['target_year'], $m);
                                    $optLabel = \Carbon\Carbon::createFromFormat('Y-m', $optVal)->format('F Y');
                                @endphp
                                <option value="{{ $optVal }}" {{ $targetMonth === $optVal ? 'selected' : '' }}>
                                    {{ $optLabel }} ({{ $rollingWindow['target_label'] }})
                                </option>
                            @endfor
                        </select>
                    </div>

                    <!-- Rows per page selection -->
                    <div class="flex items-center space-x-2 text-xs text-slate-500">
                        <span>Rows:</span>
                        <select onchange="window.location.href = updateQueryStringParameter(window.location.href, 'per_page', this.value)" class="bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 font-semibold text-slate-700 cursor-pointer text-xs shadow-2xs transition-all">
                            <option value="5" {{ request('per_page', session('per_page_forecast', 10)) == 5 ? 'selected' : '' }}>5</option>
                            <option value="10" {{ request('per_page', session('per_page_forecast', 10)) == 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ request('per_page', session('per_page_forecast', 10)) == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ request('per_page', session('per_page_forecast', 10)) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', session('per_page_forecast', 10)) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <!-- Bulk Regenerate All Button -->
                    <form action="{{ route('admin.forecast.generateAll') }}" method="POST"
                          data-confirm-message="This will refresh demand forecasts for all active products for {{ $targetMonthLabel }}. Continue?">
                        @csrf
                        <input type="hidden" name="target_month" value="{{ $targetMonth }}">
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-indigo-200 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-all font-bold text-xs cursor-pointer shadow-2xs">
                            <i class="bi bi-arrow-repeat"></i>
                            <span>Refresh All</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-4 px-6">Product</th>
                            <th class="py-4 px-6">Category</th>
                            <th class="py-4 px-6">Current Stock</th>
                            <th class="py-4 px-6">{{ $lastMonthStats['reference_label'] ?? 'Last Month' }} Sales</th>
                            <th class="py-4 px-6">Forecast ({{ $targetMonth }})</th>
                            <th class="py-4 px-6">Expected Revenue</th>
                            <th class="py-4 px-6">Additional Stock Needed</th>
                            <th class="py-4 px-6 text-center">Risk</th>
                            <th class="py-4 px-6 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-xs text-slate-600">
                        @forelse($products as $product)
                        @php
                            $forecast = $product->forecasts->where('forecast_month', $targetMonth)->first();
                            $predictedSales = $forecast ? (float) $forecast->predicted_sales : null;
                            $expectedRevenue = $predictedSales !== null ? round($predictedSales * (float) $product->price, 2) : null;
                            $recommendedRestock = $predictedSales !== null
                                ? \App\Services\ForecastService::recommendedRestock($predictedSales, $product->stock)
                                : null;
                            $stockoutRisk = $predictedSales !== null
                                ? \App\Services\ForecastService::stockoutRisk($predictedSales, $product->stock)
                                : 'unknown';
                            $lastMonthItem = $lastMonthStats['products'][$product->id] ?? null;
                            $isTopLastMonth = $lastMonthItem && $lastMonthItem['rank'] === 1 && $lastMonthItem['qty'] > 0;
                            $isTopForecast = !empty($topForecastProduct) && $topForecastProduct['id'] === $product->id && $predictedSales > 0;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors {{ ($selectedProduct && $selectedProduct->id === $product->id) ? 'bg-sky-50/40' : '' }}">
                            <td class="py-4 px-6 font-bold text-slate-900">
                                <a href="{{ route('admin.forecast.index', array_merge(request()->query(), ['product_filter' => $product->id])) }}"
                                   class="hover:text-indigo-600 transition-colors inline-flex items-center gap-1"
                                   title="View historical sales trend for this product">
                                    <span>{{ $product->product_name }} ({{ $product->size }})</span>
                                    <i class="bi bi-bar-chart-line text-[10px] text-slate-400"></i>
                                </a>
                                @if($isTopLastMonth)
                                    <span class="block mt-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[9px] font-extrabold bg-amber-50 text-amber-700 border border-amber-200">
                                            <i class="bi bi-trophy-fill text-[8px]"></i> #1 Seller in {{ $lastMonthStats['reference_label'] }}
                                        </span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-slate-600 font-medium">{{ $product->category->category_name }}</td>
                            <td class="py-4 px-6 font-semibold text-slate-800">{{ $product->stock }} units</td>
                            <td class="py-4 px-6">
                                @if($lastMonthItem && $lastMonthItem['qty'] > 0)
                                    <span class="font-bold text-slate-800">{{ number_format($lastMonthItem['qty']) }} units</span>
                                    <span class="block text-[10px] text-slate-400 font-medium">₱{{ number_format($lastMonthItem['revenue'], 2) }}</span>
                                @else
                                    <span class="text-slate-400">0 units</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 font-bold">
                                @if($forecast)
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-indigo-600 text-sm font-black">{{ round($predictedSales, 1) }} units</span>
                                        @if($isTopForecast)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-indigo-100 text-indigo-700">Top Forecast</span>
                                        @endif
                                    </div>
                                    <p class="text-[9px] text-slate-400 font-medium">Updated {{ $forecast->updated_at->format('M d, Y') }}</p>
                                @else
                                    <span class="text-slate-400 italic">Not generated</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 font-bold">
                                @if($expectedRevenue !== null)
                                    <span class="text-emerald-700 font-bold">₱{{ number_format($expectedRevenue, 2) }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 font-bold">
                                @if($recommendedRestock !== null)
                                    @if($recommendedRestock > 0)
                                        <span class="text-amber-700 font-bold">+{{ $recommendedRestock }} units</span>
                                        <a href="{{ route('admin.inventory.index') }}" class="block text-[10px] text-sky-600 hover:underline font-semibold mt-0.5">Plan restock →</a>
                                    @else
                                        <span class="text-emerald-600 font-semibold">Sufficient</span>
                                    @endif
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($stockoutRisk === 'critical')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                        <i class="bi bi-exclamation-circle-fill text-[9px]"></i> Critical
                                    </span>
                                @elseif($stockoutRisk === 'low')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                        <i class="bi bi-exclamation-triangle-fill text-[9px]"></i> Low
                                    </span>
                                @elseif($stockoutRisk === 'safe')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <i class="bi bi-check-circle-fill text-[9px]"></i> Safe
                                    </span>
                                @else
                                    <span class="text-slate-300 text-[10px]">—</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                <form action="{{ route('admin.forecast.generate') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="target_month" value="{{ $targetMonth }}">
                                    @if($forecast)
                                        <button type="submit" class="inline-flex items-center space-x-1.5 py-1.5 px-3 rounded-xl border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 transition-all font-bold text-xs cursor-pointer shadow-2xs">
                                            <i class="bi bi-arrow-repeat text-slate-400"></i>
                                            <span>Regenerate</span>
                                        </button>
                                    @else
                                        <button type="submit" class="inline-flex items-center space-x-1.5 py-1.5 px-3 rounded-xl border border-indigo-100 text-white bg-indigo-600 hover:bg-indigo-500 transition-all font-bold text-xs cursor-pointer shadow shadow-indigo-100">
                                            <i class="bi bi-cpu"></i>
                                            <span>Run Forecast</span>
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400 italic">No products found.</td>
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
    </div>

    <!-- Math Model & Seasonal Summary Sidebar (Column 4) -->
    <div class="lg:col-span-1 space-y-4">
        <!-- Seasonal Performance Breakdown Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-3">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center space-x-2">
                <i class="bi bi-calendar-range text-indigo-600"></i>
                <span>Seasonal Demand Index</span>
            </h3>
            @if($analytics['has_data'])
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Calendar months ranked by historical sales performance ({{ $selectedProduct ? $selectedProduct->product_name : 'All Products' }}):
                </p>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    @foreach(collect($analytics['seasonal_months'])->where('total_qty', '>', 0)->sortByDesc('seasonal_index') as $sMonth)
                        <div class="flex items-center justify-between text-xs p-2 rounded-xl {{ $sMonth['seasonal_index'] >= 1.15 ? 'bg-amber-50/70 border border-amber-200/70' : 'bg-slate-50 border border-slate-100' }}">
                            <div>
                                <span class="font-bold text-slate-800">{{ $sMonth['full_name'] }}</span>
                                <span class="block text-[10px] text-slate-400">Avg: {{ $sMonth['avg_qty'] }} units (₱{{ number_format($sMonth['avg_revenue'], 2) }})</span>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $sMonth['seasonal_index'] >= 1.15 ? 'bg-amber-500 text-slate-950' : ($sMonth['seasonal_index'] >= 0.9 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-700') }}">
                                {{ $sMonth['seasonal_index'] }}x
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400 italic py-2">No completed sales history available yet to rank seasonal months.</p>
            @endif
        </div>

        @if(!empty($analytics['high_sales_days']))
        <!-- Peak High Sales Days Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-3">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center space-x-2">
                <i class="bi bi-graph-up text-emerald-600"></i>
                <span>High Sales Days</span>
            </h3>
            <p class="text-[11px] text-slate-500 leading-relaxed">
                Top recorded single-day sales dates ({{ $selectedProduct ? $selectedProduct->product_name : 'Storewide' }}):
            </p>
            <div class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
                @foreach($analytics['high_sales_days'] as $highDay)
                    <div class="flex items-center justify-between text-xs px-2.5 py-2 rounded-xl bg-slate-50 border border-slate-100">
                        <div>
                            <span class="font-bold text-slate-800">{{ $highDay['label'] }}</span>
                            <span class="block text-[10px] text-slate-400">{{ number_format($highDay['total_qty']) }} units</span>
                        </div>
                        <span class="font-extrabold text-emerald-700 text-[11px]">
                            ₱{{ number_format($highDay['total_revenue'], 2) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Forecasting Methodology -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center space-x-2">
                <i class="bi bi-info-circle text-sky-600"></i>
                <span>Forecasting Methodology</span>
            </h3>
            
            <div class="space-y-3 text-slate-600 text-xs">
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl space-y-1">
                    <span class="block text-[9px] font-black text-slate-400 uppercase tracking-wider">1. Last-Month Momentum & Trend</span>
                    <p class="font-medium text-slate-700 leading-relaxed">Combines recent sales momentum (weighting last month's best-selling products) with Linear Regression across historical months.</p>
                </div>
                
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl space-y-1">
                    <span class="block text-[9px] font-black text-slate-400 uppercase tracking-wider">2. Seasonal Demand Comparison</span>
                    <p class="font-medium text-slate-700 leading-relaxed">Compares sales from matching months across previous years against the overall monthly average to identify high-performing seasons.</p>
                </div>
                
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl space-y-1">
                    <span class="block text-[9px] font-black text-slate-400 uppercase tracking-wider">3. Inventory Planning Buffer</span>
                    <p class="font-medium text-slate-700 leading-relaxed">Recommended restock adds a 20% safety buffer over forecasted demand minus current available stock.</p>
                </div>
            </div>
        </div>

        <!-- Risk Legend -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-3">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center space-x-2">
                <i class="bi bi-shield-exclamation text-amber-500"></i>
                <span>Risk Level Guide</span>
            </h3>
            <div class="space-y-2 text-xs">
                <div class="flex items-start gap-2.5">
                    <span class="mt-0.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-50 text-rose-600 border border-rose-100 shrink-0">
                        <i class="bi bi-exclamation-circle-fill"></i> Critical
                    </span>
                    <p class="text-slate-600 leading-snug">Stock is below 50% of forecasted demand. Immediate restocking required.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="mt-0.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-100 shrink-0">
                        <i class="bi bi-exclamation-triangle-fill"></i> Low
                    </span>
                    <p class="text-slate-600 leading-snug">Stock may not fully cover forecasted demand. Plan replenishment soon.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="mt-0.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 shrink-0">
                        <i class="bi bi-check-circle-fill"></i> Safe
                    </span>
                    <p class="text-slate-600 leading-snug">Stock meets or exceeds the forecasted demand with buffer.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthLabels = {!! json_encode($analytics['month_labels']) !!};
        const yoySeries = {!! json_encode($analytics['yoy_series']) !!};
        const seasonalMonths = {!! json_encode($analytics['seasonal_months']) !!};
        const overallAvgQty = {{ (float) $analytics['overall_monthly_avg_qty'] }};

        // 1. Monthly & Year-over-Year Comparison Chart
        const yoyCtx = document.getElementById('yoyComparisonChart');
        if (yoyCtx && yoySeries.length > 0) {
            const palette = [
                { border: '#0284c7', bg: 'rgba(2, 132, 199, 0.14)' },   // sky-600
                { border: '#4f46e5', bg: 'rgba(79, 70, 229, 0.14)' },   // indigo-600
                { border: '#d97706', bg: 'rgba(217, 119, 6, 0.14)' },   // amber-600
                { border: '#059669', bg: 'rgba(5, 150, 105, 0.14)' }    // emerald-600
            ];

            const datasets = yoySeries.map((series, idx) => {
                const color = palette[idx % palette.length];
                return {
                    label: String(series.year) + ' Sales (Units)',
                    data: series.quantities,
                    revenues: series.revenues,
                    borderColor: color.border,
                    backgroundColor: color.bg,
                    borderWidth: 2.5,
                    tension: 0.3,
                    fill: yoySeries.length === 1,
                    pointRadius: 3.5,
                    pointHoverRadius: 5,
                    pointBackgroundColor: color.border
                };
            });

            new Chart(yoyCtx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 10,
                                usePointStyle: true,
                                font: { size: 11, weight: 'bold' }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const rev = ctx.dataset.revenues ? Number(ctx.dataset.revenues[ctx.dataIndex] || 0) : 0;
                                    const revFormatted = '₱' + rev.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    return ' ' + ctx.dataset.label + ': ' + Number(ctx.raw).toLocaleString() + ' units (' + revFormatted + ')';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                precision: 0,
                                font: { size: 10 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        }
                    }
                }
            });
        }

        // 2. Seasonal Demand Pattern Bar Chart (Jan - Dec)
        const seasonalCtx = document.getElementById('seasonalPatternChart');
        if (seasonalCtx && seasonalMonths.length > 0) {
            const avgQuantities = seasonalMonths.map(m => m.avg_qty);
            const indices = seasonalMonths.map(m => m.seasonal_index);
            const barColors = seasonalMonths.map(m => {
                if (m.seasonal_index >= 1.15) return '#f59e0b'; // High demand season (amber-500)
                if (m.seasonal_index > 0) return '#6366f1';     // Normal active month (indigo-500)
                return '#e2e8f0';                               // No sales recorded
            });

            new Chart(seasonalCtx, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Average Monthly Demand (Units)',
                        data: avgQuantities,
                        backgroundColor: barColors,
                        borderRadius: 6,
                        barThickness: 18
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    const idx = ctx.dataIndex;
                                    const sIdx = indices[idx];
                                    return [
                                        ' Avg Demand: ' + Number(ctx.raw).toLocaleString() + ' units',
                                        ' Seasonal Index: ' + sIdx + 'x' + (sIdx >= 1.15 ? ' (High Demand Period)' : '')
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                precision: 0,
                                font: { size: 10 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
