@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Dashboard Summary')

@section('content')
<!-- KPI Cards Row -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
    <!-- Sales Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs hover:border-slate-300 transition-colors flex items-center justify-between">
        <div>
            <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Total Revenue</span>
            <span class="text-xl sm:text-2xl font-black text-slate-900 block mt-1">₱{{ number_format($totalSales, 2) }}</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-2xs">
            <i class="bi bi-wallet2 text-lg"></i>
        </div>
    </div>

    <!-- Orders Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs hover:border-slate-300 transition-colors flex items-center justify-between">
        <div>
            <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Total Orders</span>
            <span class="text-xl sm:text-2xl font-black text-slate-900 block mt-1">{{ $totalOrders }}</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-slate-900 text-sky-400 flex items-center justify-center border border-slate-800 shadow-2xs">
            <i class="bi bi-cart-check text-lg"></i>
        </div>
    </div>

    <!-- Customers Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs hover:border-slate-300 transition-colors flex items-center justify-between">
        <div>
            <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Customers</span>
            <span class="text-xl sm:text-2xl font-black text-slate-900 block mt-1">{{ $totalCustomers }}</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 shadow-2xs">
            <i class="bi bi-people text-lg"></i>
        </div>
    </div>

    <!-- Products Card -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs hover:border-slate-300 transition-colors flex items-center justify-between">
        <div>
            <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Active Catalog</span>
            <span class="text-xl sm:text-2xl font-black text-slate-900 block mt-1">{{ $totalProducts }}</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 shadow-2xs">
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
        <a href="{{ route('admin.products.index') }}" class="bg-amber-50 border border-amber-100 rounded-2xl p-4 flex items-center justify-between hover:bg-amber-100/50 transition-colors">
            <div class="flex items-center space-x-3 text-amber-800">
                <i class="bi bi-clock-history text-xl text-amber-500 animate-pulse"></i>
                <div>
                    <span class="block text-xs font-bold">Upcoming Batch Expirations</span>
                    <span class="text-[10px] text-amber-600 font-semibold">{{ $nearExpirationAlerts }} products are expiring within the next 7 days.</span>
                </div>
            </div>
            <span class="bg-amber-600 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow">Inspect</span>
        </a>
    @endif
</div>
@endif

<!-- Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Sales trend Line Chart -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-6 flex items-center space-x-1.5">
            <i class="bi bi-graph-up text-sky-600"></i>
            <span>Monthly Revenue Sales Trend</span>
        </h3>
        <div class="h-64">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Best Seller Bar Chart -->
    <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
        <h3 class="text-sm font-bold text-slate-900 mb-6 flex items-center space-x-1.5">
            <i class="bi bi-bar-chart-fill text-sky-600"></i>
            <span>Best Selling Product Volumes</span>
        </h3>
        <div class="h-64">
            <canvas id="productsChart"></canvas>
        </div>
    </div>
</div>

<!-- Bottom Area: Recent Orders -->
<div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-950 font-bold">Recent Customer Placements</h3>
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
                        backgroundColor: '#38bdf8', // sky-400
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
