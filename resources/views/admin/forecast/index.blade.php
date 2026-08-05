@extends('layouts.admin')

@section('title', 'Demand Forecasting')
@section('page_title', 'Demand Forecasting')

@section('content')
<!-- Explanation Hero Section -->
<div class="bg-gradient-to-tr from-sky-600 via-indigo-600 to-indigo-700 rounded-3xl p-6 sm:p-8 text-white shadow-md shadow-sky-100/30 mb-8">
    <div class="max-w-3xl">
        <span class="bg-white/20 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Algorithmic Sales Intelligence</span>
        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-3">Smart Demand Forecasting Engine</h2>
        <p class="text-xs sm:text-sm text-sky-100/90 mt-2.5 leading-relaxed">
            DairyBox utilizes a mathematical **Linear Regression + Seasonal Indexing** model. By analyzing 12 months of historical order placements and matching monthly seasonal spikes, it estimates product inventory demand for the next month.
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- Products Forecast Status Table (Columns 1-3) -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/20">
                <div>
                    <h3 class="text-sm font-bold text-slate-950">Predictive Forecasts</h3>
                    <p class="text-xs text-slate-500">Generate and inspect customer demand predictions for <strong>{{ $targetMonthLabel }}</strong>.</p>
                </div>
                <!-- Rows per page selection -->
                <div class="flex items-center space-x-2 text-xs text-slate-500 self-start sm:self-center">
                    <span>Rows:</span>
                    <select onchange="window.location.href = updateQueryStringParameter(window.location.href, 'per_page', this.value)" class="bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 font-semibold text-slate-700 cursor-pointer text-xs shadow-2xs transition-all">
                        <option value="5" {{ request('per_page', session('per_page_forecast', 10)) == 5 ? 'selected' : '' }}>5</option>
                        <option value="10" {{ request('per_page', session('per_page_forecast', 10)) == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page', session('per_page_forecast', 10)) == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page', session('per_page_forecast', 10)) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page', session('per_page_forecast', 10)) == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-4 px-6">Product</th>
                            <th class="py-4 px-6">Category</th>
                            <th class="py-4 px-6">Current Stock</th>
                            <th class="py-4 px-6">Forecast ({{ $targetMonth }})</th>
                            <th class="py-4 px-6 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-xs text-slate-600">
                        @forelse($products as $product)
                        @php
                            // Check if forecast is generated for next month
                            $forecast = $product->forecasts->where('forecast_month', $targetMonth)->first();
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6 font-bold text-slate-900">{{ $product->product_name }} ({{ $product->size }})</td>
                            <td class="py-4 px-6 text-slate-600 font-medium">{{ $product->category->category_name }}</td>
                            <td class="py-4 px-6 font-semibold text-slate-800">{{ $product->stock }} units</td>
                            <td class="py-4 px-6 font-bold">
                                @if($forecast)
                                    <span class="text-indigo-600 text-sm font-black">{{ round($forecast->predicted_sales, 1) }} units</span>
                                    <p class="text-[9px] text-slate-400 font-medium">Generated on {{ $forecast->updated_at->format('M d, Y') }}</p>
                                @else
                                    <span class="text-slate-400 italic">Not generated</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                <form action="{{ route('admin.forecast.generate') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
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
                            <td colspan="5" class="py-12 text-center text-slate-400 italic">No products found.</td>
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

    <!-- Math Model Breakdown Sidebar (Column 4) -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center space-x-2">
                <i class="bi bi-info-circle text-sky-600"></i>
                <span>Mathematical Model</span>
            </h3>
            
            <div class="space-y-4 text-slate-600 text-xs">
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl space-y-1 hover:bg-white transition-all shadow-2xs hover:shadow-sm">
                    <span class="block text-[8px] font-black text-slate-400 uppercase tracking-wider">1. Linear Regression</span>
                    <p class="font-medium text-slate-700 leading-relaxed">Fits a straight trend line ($y = mx + b$) over 12 months of sales indexes to determine overall growth or decline rates.</p>
                </div>
                
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl space-y-1 hover:bg-white transition-all shadow-2xs hover:shadow-sm">
                    <span class="block text-[8px] font-black text-slate-400 uppercase tracking-wider">2. Seasonal Indexing</span>
                    <p class="font-medium text-slate-700 leading-relaxed">Compares historically matching calendar months to the overall annual average, applying a smoothing multiplier for cyclical sales (e.g. Summer ice cream spikes).</p>
                </div>
                
                <div class="p-3 bg-slate-50 border border-slate-100 rounded-2xl space-y-1 hover:bg-white transition-all shadow-2xs hover:shadow-sm">
                    <span class="block text-[8px] font-black text-slate-400 uppercase tracking-wider">3. Safety Stock Alignment</span>
                    <p class="font-medium text-slate-700 leading-relaxed">Admin managers can cross-verify generated predictions against actual stock levels to avoid shelf-life expiration waste or stockouts.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
