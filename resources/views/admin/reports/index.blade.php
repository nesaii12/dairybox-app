@extends('layouts.admin')

@section('title', 'Sales Reporting & Analysis')
@section('page_title', 'Sales Reporting & Analysis')

@section('content')
<div class="space-y-6">
    <!-- Top Header & Reporting Range -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Sales Reporting & Analytics</h2>
            <p class="text-xs text-slate-500 mt-0.5">Evaluate sales trajectory, product demand, and make data-driven decisions based on selected periods.</p>
        </div>

        <div class="flex items-center flex-wrap gap-2">
            <span class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-xl bg-sky-50 text-sky-700 border border-sky-100">
                <i class="bi bi-calendar3 mr-1.5 text-sky-600"></i>
                <span>{{ $dateRangeLabel }}</span>
            </span>

            <!-- Quick Export Buttons for Active Window -->
            <a href="{{ route('admin.reports.salesExcel', array_merge(request()->query(), ['filter_type' => $filterType, 'start_date' => $startDate, 'end_date' => $endDate])) }}"
               class="inline-flex items-center space-x-1.5 py-1.5 px-3 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 transition-colors shadow-2xs">
                <i class="bi bi-file-earmark-excel-fill text-sm"></i>
                <span>Excel</span>
            </a>
            <a href="{{ route('admin.reports.salesPdf', array_merge(request()->query(), ['filter_type' => $filterType, 'start_date' => $startDate, 'end_date' => $endDate])) }}"
               class="inline-flex items-center space-x-1.5 py-1.5 px-3 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100/80 border border-rose-200 transition-colors shadow-2xs">
                <i class="bi bi-file-earmark-pdf-fill text-sm"></i>
                <span>PDF</span>
            </a>
        </div>
    </div>

    <!-- Date Range Selector Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 sm:p-5">
        <form action="{{ route('admin.reports.index') }}" method="GET" id="report-filter-form" class="space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Preset Filter Pills -->
                <div class="flex items-center flex-wrap gap-2">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mr-1">Period:</span>
                    
                    <button type="submit" name="filter_type" value="daily"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $filterType === 'daily' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Today
                    </button>
                    <button type="submit" name="filter_type" value="weekly"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $filterType === 'weekly' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        This Week
                    </button>
                    <button type="submit" name="filter_type" value="monthly"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $filterType === 'monthly' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        This Month
                    </button>
                    <button type="submit" name="filter_type" value="annual"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $filterType === 'annual' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        This Year
                    </button>
                    <button type="submit" name="filter_type" value="all"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $filterType === 'all' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        All Time
                    </button>
                    <button type="button" onclick="toggleCustomRange()"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer {{ $filterType === 'custom' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        <i class="bi bi-sliders mr-1"></i> Custom Range...
                    </button>
                </div>

                <div class="text-xs text-slate-400 font-medium hidden lg:block">
                    Range: <span class="font-semibold text-slate-700">{{ $startDate }}</span> to <span class="font-semibold text-slate-700">{{ $endDate }}</span>
                </div>
            </div>

            <!-- Custom Date Range Picker Container (toggled or shown when custom) -->
            <div id="custom-range-container" class="pt-4 border-t border-slate-100 {{ $filterType === 'custom' ? 'block' : 'hidden' }}">
                <input type="hidden" name="filter_type" id="filter_type_input" value="{{ $filterType }}">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <div>
                        <label for="start_date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate }}"
                            class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 transition-all focus:bg-white font-medium">
                    </div>
                    <div>
                        <label for="end_date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate }}"
                            class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 transition-all focus:bg-white font-medium">
                    </div>
                    <div>
                        <button type="submit" onclick="document.getElementById('filter_type_input').value='custom'"
                            class="w-full flex items-center justify-center space-x-1.5 py-2 px-4 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-xs cursor-pointer">
                            <i class="bi bi-funnel"></i>
                            <span>Apply Date Filter</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- KPI Summary Decision Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Gross Sales Revenue -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Gross Sales Revenue</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">₱{{ number_format($totalRevenue, 2) }}</h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100">
                    <i class="bi bi-cash-stack text-xl"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 flex items-center">
                <i class="bi bi-check-circle-fill text-emerald-500 mr-1.5 text-xs"></i>
                <span>Completed sales in selected window</span>
            </p>
        </div>

        <!-- Card 2: Completed Orders -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Completed Orders</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalOrders) }}</h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                    <i class="bi bi-bag-check-fill text-xl"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 flex items-center justify-between">
                <span>{{ $deliveryCount }} Delivery</span>
                <span class="text-slate-300">&bull;</span>
                <span>{{ $pickupCount }} Pickup</span>
            </p>
        </div>

        <!-- Card 3: Total Units Sold -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dairy Units Sold</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalUnitsSold) }}</h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                    <i class="bi bi-box-seam-fill text-xl"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 flex items-center">
                <i class="bi bi-pie-chart-fill text-emerald-500 mr-1.5 text-xs"></i>
                <span>Bottles & tubs dispatched</span>
            </p>
        </div>

        <!-- Card 4: Average Order Value (AOV) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Avg Order Value (AOV)</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">₱{{ number_format($avgOrderValue, 2) }}</h3>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                    <i class="bi bi-graph-up-arrow text-xl"></i>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-3 flex items-center">
                <i class="bi bi-calculator text-amber-500 mr-1.5 text-xs"></i>
                <span>Average spend per customer order</span>
            </p>
        </div>
    </div>

    <!-- Visual Analytics Grid: Sales Trend + Milk Category Strategic Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sales Revenue Trend (2 cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-950">Sales Revenue Trend</h3>
                    <p class="text-[10px] text-slate-400 font-medium">Revenue trajectory across the selected reporting window</p>
                </div>
                <span class="text-xs font-bold text-sky-600 bg-sky-50 px-2.5 py-1 rounded-lg">
                    ₱{{ number_format($totalRevenue, 2) }} total
                </span>
            </div>
            
            <div class="h-64 relative w-full">
                @if(count($chartLabels) > 0 && array_sum($chartValues) > 0)
                    <canvas id="salesTrendChart"></canvas>
                @else
                    <div class="h-full flex flex-col items-center justify-center text-slate-400 text-xs">
                        <i class="bi bi-graph-down text-3xl mb-2 text-slate-300"></i>
                        <span>No sales revenue recorded for this period window.</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Milk Stream Decision Analysis (1 col) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-950">Milk Stream Analysis</h3>
                        <p class="text-[10px] text-slate-400 font-medium">Strategic comparison for production & procurement</p>
                    </div>
                    <i class="bi bi-compass text-slate-400"></i>
                </div>

                <!-- Carabao Milk Breakdown -->
                <div class="space-y-4 mb-5">
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-bold text-slate-800 flex items-center">
                                <span class="w-2.5 h-2.5 rounded-full bg-sky-600 mr-2"></span>
                                Carabao Milk Products
                            </span>
                            <span class="font-black text-slate-900">₱{{ number_format($carabaoRevenue, 2) }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-sky-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $carabaoShare }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                            <span>{{ $carabaoUnits }} units sold</span>
                            <span class="font-bold text-sky-700">{{ $carabaoShare }}% share</span>
                        </div>
                    </div>

                    <!-- Cow Milk Breakdown -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-bold text-slate-800 flex items-center">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 mr-2"></span>
                                Cow Milk Products
                            </span>
                            <span class="font-black text-slate-900">₱{{ number_format($cowRevenue, 2) }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-indigo-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $cowShare }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                            <span>{{ $cowUnits }} units sold</span>
                            <span class="font-bold text-indigo-700">{{ $cowShare }}% share</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decision-Making Insight Callout -->
            <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl text-xs text-slate-600">
                <span class="font-bold text-slate-900 block mb-1 flex items-center">
                    <i class="bi bi-lightbulb-fill text-amber-500 mr-1.5"></i> Decision Guidance:
                </span>
                @if($carabaoShare > $cowShare)
                    Carabao milk drives <span class="font-bold text-sky-700">{{ $carabaoShare }}%</span> of revenue this period. Prioritize Carabao raw milk procurement and monitor butterfat pasteurization queues.
                @elseif($cowShare > $carabaoShare)
                    Cow milk drives <span class="font-bold text-indigo-700">{{ $cowShare }}%</span> of revenue this period. Maintain consistent fresh cow milk inventory intake to meet peak customer order velocity.
                @else
                    Both milk streams show balanced demand. Maintain standard inventory intake ratios across Carabao and Cow dairy lines.
                @endif
            </div>
        </div>
    </div>

    <!-- Product Performance Breakdown (Decision-Making Table) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-950">Product Performance & Best Sellers</h3>
                <p class="text-[10px] text-slate-400 font-medium">Sales volume, revenue contribution, and remaining stock for planning</p>
            </div>
            <span class="text-xs text-slate-500 font-mono">{{ $productSales->count() }} {{ Str::plural('product', $productSales->count()) }} sold</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <th class="py-3 px-3">Product Name</th>
                        <th class="py-3 px-3">Category / Milk Type</th>
                        <th class="py-3 px-3">Size</th>
                        <th class="py-3 px-3 text-right">Units Sold</th>
                        <th class="py-3 px-3 text-right">Gross Revenue</th>
                        <th class="py-3 px-3 text-right">Revenue Share</th>
                        <th class="py-3 px-3 text-right">Current Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($productSales as $prod)
                        @php
                            $share = $totalRevenue > 0 ? round(($prod->total_revenue / $totalRevenue) * 100, 1) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-3 font-bold text-slate-900">{{ $prod->product_name }}</td>
                            <td class="py-3.5 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold {{ stripos($prod->milk_type, 'carabao') !== false ? 'bg-sky-50 text-sky-700 border border-sky-100' : 'bg-indigo-50 text-indigo-700 border border-indigo-100' }}">
                                    {{ $prod->milk_type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-slate-600 font-medium">{{ $prod->size }}</td>
                            <td class="py-3.5 px-3 text-right font-black text-slate-900">{{ number_format($prod->units_sold) }}</td>
                            <td class="py-3.5 px-3 text-right font-black text-sky-700">₱{{ number_format($prod->total_revenue, 2) }}</td>
                            <td class="py-3.5 px-3 text-right font-medium text-slate-600">
                                <div class="inline-flex items-center space-x-1.5">
                                    <span class="w-12 bg-slate-100 rounded-full h-1.5 overflow-hidden inline-block align-middle">
                                        <span class="bg-sky-600 h-1.5 rounded-full block" style="width: {{ min(100, $share) }}%"></span>
                                    </span>
                                    <span>{{ $share }}%</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-right">
                                @if($prod->stock <= 5)
                                    <span class="inline-flex items-center text-rose-600 font-bold text-[11px]">
                                        <i class="bi bi-exclamation-triangle mr-1"></i> {{ $prod->stock }} left
                                    </span>
                                @else
                                    <span class="text-slate-600 font-medium">{{ $prod->stock }} in stock</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                No product sales recorded in the selected date window.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sales Transaction Ledger -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-slate-950">Sales Transactions Ledger</h3>
                <p class="text-[10px] text-slate-400 font-medium">Individual sales records in selected period</p>
            </div>
            <span class="text-xs text-slate-500 font-mono">{{ $salesLedger->total() }} {{ Str::plural('transaction', $salesLedger->total()) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <th class="py-3 px-3">Date</th>
                        <th class="py-3 px-3">Order #</th>
                        <th class="py-3 px-3">Customer</th>
                        <th class="py-3 px-3">Fulfillment</th>
                        <th class="py-3 px-3">Items Purchased</th>
                        <th class="py-3 px-3 text-right">Total Amount</th>
                        <th class="py-3 px-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($salesLedger as $sale)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-3 text-slate-600 font-mono">{{ \Carbon\Carbon::parse($sale->sales_date)->format('M d, Y') }}</td>
                            <td class="py-3.5 px-3 font-bold text-slate-900">#{{ $sale->order->order_number }}</td>
                            <td class="py-3.5 px-3 text-slate-700 font-medium">{{ $sale->order->customer->name ?? 'Guest' }}</td>
                            <td class="py-3.5 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $sale->order->order_type === 'Delivery' ? 'bg-sky-50 text-sky-700 border border-sky-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' }}">
                                    <i class="bi {{ $sale->order->order_type === 'Delivery' ? 'bi-truck' : 'bi-shop' }} mr-1"></i>
                                    {{ $sale->order->order_type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-slate-600">
                                @if($sale->order->orderItems->isNotEmpty())
                                    <span class="font-medium text-slate-900">{{ $sale->order->orderItems->first()->product->product_name ?? 'Item' }}</span>
                                    @if($sale->order->orderItems->count() > 1)
                                        <span class="text-slate-400 text-[10px] font-mono">+{{ $sale->order->orderItems->count() - 1 }} more</span>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic">No item details</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-3 text-right font-black text-slate-900">₱{{ number_format($sale->total_amount, 2) }}</td>
                            <td class="py-3.5 px-3 text-center">
                                <a href="{{ route('admin.orders.show', $sale->order_id) }}" class="inline-flex items-center text-xs font-semibold text-sky-600 hover:text-sky-800 transition-colors">
                                    <span>View</span>
                                    <i class="bi bi-chevron-right ml-0.5 text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                No sales transactions found for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($salesLedger->hasPages())
            <div class="mt-4 pt-3 border-t border-slate-100">
                {{ $salesLedger->links() }}
            </div>
        @endif
    </div>

    <!-- Inventory Logs Export Panel (Maintained cleanly for system completeness) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                    <i class="bi bi-archive-fill"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-950">Inventory Transaction Logs Export</h3>
                    <p class="text-[10px] text-slate-400 font-medium">Export batch intake lists, stock deductions, and FIFO audits</p>
                </div>
            </div>
        </div>

        <form action="" method="GET" id="inventory-form" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                <div>
                    <label for="inv_filter" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Window</label>
                    <select name="filter_type" id="inv_filter" onchange="toggleInvDateFields()"
                        class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500">
                        <option value="daily">Daily Logs (Today)</option>
                        <option value="weekly">Weekly Logs (This Week)</option>
                        <option value="monthly" selected>Monthly Logs (This Month)</option>
                        <option value="annual">Annual Logs (This Year)</option>
                        <option value="custom">Custom Date Range...</option>
                    </select>
                </div>

                <div id="inv_custom_dates" class="sm:col-span-2 grid grid-cols-2 gap-3" style="display: none;">
                    <div>
                        <label for="inv_start" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Start Date</label>
                        <input type="date" name="start_date" id="inv_start"
                            class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs">
                    </div>
                    <div>
                        <label for="inv_end" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">End Date</label>
                        <input type="date" name="end_date" id="inv_end"
                            class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs">
                    </div>
                </div>
            </div>

            <div class="pt-3 flex items-center gap-3">
                <button type="button" onclick="submitInventoryReport('excel')"
                    class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 transition-colors shadow-2xs cursor-pointer">
                    <i class="bi bi-file-earmark-excel-fill text-sm"></i>
                    <span>Export Inventory Excel</span>
                </button>
                <button type="button" onclick="submitInventoryReport('pdf')"
                    class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100/80 border border-rose-200 transition-colors shadow-2xs cursor-pointer">
                    <i class="bi bi-file-earmark-pdf-fill text-sm"></i>
                    <span>Export Inventory PDF</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function toggleCustomRange() {
        const container = document.getElementById('custom-range-container');
        const hiddenInput = document.getElementById('filter_type_input');
        if (container.classList.contains('hidden')) {
            container.classList.remove('hidden');
            hiddenInput.value = 'custom';
        } else {
            container.classList.add('hidden');
        }
    }

    function toggleInvDateFields() {
        const select = document.getElementById('inv_filter');
        const container = document.getElementById('inv_custom_dates');
        container.style.display = (select.value === 'custom') ? 'grid' : 'none';
    }

    function submitInventoryReport(format) {
        const form = document.getElementById('inventory-form');
        form.action = (format === 'excel') 
            ? "{{ route('admin.reports.inventoryExcel') }}" 
            : "{{ route('admin.reports.inventoryPdf') }}";
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const salesTrendCtx = document.getElementById('salesTrendChart');
        if (salesTrendCtx) {
            new Chart(salesTrendCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        label: 'Sales Revenue (₱)',
                        data: {!! json_encode($chartValues) !!},
                        borderColor: '#0284c7', // sky-600
                        backgroundColor: 'rgba(2, 132, 199, 0.08)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3.5,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#0284c7',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' Revenue: ₱' + Number(context.raw).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + Number(value).toLocaleString();
                                },
                                font: { size: 10 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                maxRotation: 45,
                                autoSkip: true,
                                maxTicksLimit: 15,
                                font: { size: 10 }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
