@extends('layouts.admin')

@section('title', 'Manage Orders')
@section('page_title', 'Manage Orders')

@section('content')
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <!-- Header with Filters -->
    <div class="px-6 py-5 border-b border-slate-100 flex flex-col gap-4">
        <div>
            <h3 class="text-lg font-bold text-slate-950 font-bold">Customer Orders</h3>
            <p class="text-xs text-slate-500">Track and fulfill client orders, assign riders, and update progress pipelines.</p>
        </div>
        
        <!-- Filters Form -->
        <form action="{{ route('admin.orders.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Filter Status</label>
                <select name="status" id="status" onchange="this.form.submit()"
                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all">
                    <option value="">All Statuses</option>
                    <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Confirmed" {{ request('status') === 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="Preparing" {{ request('status') === 'Preparing' ? 'selected' : '' }}>Preparing</option>
                    <option value="Ready for Pickup" {{ request('status') === 'Ready for Pickup' ? 'selected' : '' }}>Ready for Pickup</option>
                    <option value="Out for Delivery" {{ request('status') === 'Out for Delivery' ? 'selected' : '' }}>Out for Delivery</option>
                    <option value="Delivered" {{ request('status') === 'Delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="Delivered - Pending Remittance" {{ request('status') === 'Delivered - Pending Remittance' ? 'selected' : '' }}>Delivered - Pending Remittance</option>
                    <option value="Paid & Remitted" {{ request('status') === 'Paid & Remitted' ? 'selected' : '' }}>Paid & Remitted</option>
                    <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Fulfillment Mode Filter -->
            <div>
                <label for="type" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Fulfillment Mode</label>
                <select name="type" id="type" onchange="this.form.submit()"
                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all">
                    <option value="">All Fulfillment Types</option>
                    <option value="Delivery" {{ request('type') === 'Delivery' ? 'selected' : '' }}>Delivery</option>
                    <option value="Pickup" {{ request('type') === 'Pickup' ? 'selected' : '' }}>Pickup</option>
                </select>
            </div>

            <!-- Rows per page Filter -->
            <div>
                <label for="per_page" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Rows Per Page</label>
                <select name="per_page" id="per_page" onchange="this.form.submit()"
                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all cursor-pointer">
                    <option value="5" {{ request('per_page', session('per_page_orders', 10)) == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ request('per_page', session('per_page_orders', 10)) == 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', session('per_page_orders', 10)) == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page', session('per_page_orders', 10)) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', session('per_page_orders', 10)) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Order No.</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Schedule Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Total Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Rider</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($orders as $order)
                <tr id="order-{{ $order->id }}" data-record-id="order-{{ $order->id }}" data-record-number="{{ $order->order_number }}" class="hover:bg-slate-50/50 transition-colors record-highlight-target">
                    <!-- Order number -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">
                        {{ $order->order_number }}
                    </td>
                    <!-- Customer -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                            <span>{{ $order->customer->name }}</span>
                            @if($order->customer->isRestricted())
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-700" title="Restricted from ordering until {{ $order->customer->restricted_until->format('M d, Y h:i A') }}">
                                    <i class="bi bi-shield-slash-fill mr-0.5"></i> Restricted (3-Day)
                                </span>
                            @endif
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono">{{ $order->customer->email }}</div>
                    </td>
                    <!-- Fulfillment -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                        <span class="inline-flex items-center space-x-1">
                            @if($order->order_type === 'Delivery')
                                <i class="bi bi-truck text-sky-500"></i>
                                <span>Delivery</span>
                            @else
                                <i class="bi bi-shop text-amber-500"></i>
                                <span>Pickup</span>
                            @endif
                        </span>
                    </td>
                    <!-- Schedule Date -->
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                        {{ \Carbon\Carbon::parse($order->schedule_date)->format('M d, Y') }} 
                        @if($order->schedule_time)
                            &bull; {{ \Carbon\Carbon::parse($order->schedule_time)->format('h:i A') }}
                        @endif
                    </td>
                    <!-- Total Amount -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-bold text-slate-950 block">₱{{ number_format($order->total_amount, 2) }}</span>
                        @if($order->isPaid())
                            <span class="inline-flex items-center text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100 mt-0.5">
                                <i class="bi bi-check-circle-fill text-[8px] mr-1 text-emerald-600"></i> Paid
                            </span>
                        @elseif($order->isOnlinePayment())
                            <span class="inline-flex items-center text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100 mt-0.5">
                                <i class="bi bi-clock-fill text-[8px] mr-1 text-amber-600"></i> Xendit Unpaid
                            </span>
                        @else
                            <span class="inline-flex items-center text-[9px] font-medium text-slate-500 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-200 mt-0.5">
                                Cash
                            </span>
                        @endif
                    </td>
                    <!-- Status Badge -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'Pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                'Confirmed' => 'bg-sky-50 text-sky-700 border-sky-100',
                                'Preparing' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                'Ready for Pickup' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'Out for Delivery' => 'bg-purple-50 text-purple-700 border-purple-100',
                                'Delivered' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                                'Delivered - Pending Remittance' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'Paid & Remitted' => 'bg-emerald-50 text-emerald-800 border-emerald-200 font-bold',
                                'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                            ];
                            $color = $statusColors[$order->order_status] ?? 'bg-slate-50 text-slate-600';
                        @endphp
                        <span class="order-status-badge inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border {{ $color }}">
                            {{ $order->order_status }}
                        </span>
                    </td>
                    <!-- Rider -->
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                        @if($order->rider)
                            <span class="order-rider-name font-bold text-slate-800"><i class="bi bi-person mr-1"></i>{{ $order->rider->name }}</span>
                        @else
                            <span class="order-rider-name text-slate-400 italic">Unassigned</span>
                        @endif
                    </td>
                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="inline-flex items-center space-x-1 py-1.5 px-3 rounded-lg text-xs font-bold text-sky-600 bg-sky-50 hover:bg-sky-100 transition-all border border-sky-100">
                            <span>Process Order</span>
                            <i class="bi bi-arrow-right-short text-base"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <i class="bi bi-cart-x text-4xl text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-800">No Orders Found</p>
                            <p class="text-xs text-slate-500">Wait for customer orders to be placed or relax.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($orders->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
