@extends('layouts.customer')

@section('title', 'My Orders')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-black text-slate-900 font-serif">Order History & Tracking</h2>
    <p class="text-xs text-slate-500">Monitor your active orders or view purchase history.</p>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <!-- Mobile Card View -->
    <div class="md:hidden divide-y divide-slate-100">
        @forelse($orders as $order)
            @php
                $statusColors = [
                    'Pending' => 'bg-amber-50 text-amber-700 border-amber-100/50',
                    'Confirmed' => 'bg-sky-50 text-sky-700 border-sky-100/50',
                    'Preparing' => 'bg-indigo-50 text-indigo-700 border-indigo-100/50',
                    'Ready for Pickup' => 'bg-emerald-50 text-emerald-700 border-emerald-100/50',
                    'Out for Delivery' => 'bg-purple-50 text-purple-700 border-purple-100/50',
                    'Delivered' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                    'Delivered - Pending Remittance' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                    'Paid & Remitted' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                    'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-100/50',
                ];
                $displayStatus = in_array($order->order_status, ['Delivered - Pending Remittance', 'Paid & Remitted']) ? 'Delivered' : $order->order_status;
                $color = $statusColors[$displayStatus] ?? 'bg-slate-50 text-slate-600';
            @endphp
            <div id="mobile-order-{{ $order->id }}" class="p-4 space-y-3 hover:bg-slate-50/50 transition-colors record-highlight-target">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Order Reference</span>
                        <span class="text-sm font-bold text-slate-900 font-mono">{{ $order->order_number }}</span>
                    </div>
                    <div class="text-right">
                        <span class="order-status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $color }} shadow-2xs">
                            {{ $displayStatus }}
                        </span>
                        @if($order->isPaid())
                            <span class="text-[9px] text-emerald-600 font-bold block mt-0.5"><i class="bi bi-check-circle-fill text-[8px] mr-0.5"></i> Paid</span>
                        @elseif($order->isOnlinePayment())
                            <span class="text-[9px] text-amber-600 font-bold block mt-0.5"><i class="bi bi-clock-fill text-[8px] mr-0.5"></i> Unpaid</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs py-2 px-3 bg-slate-50/70 rounded-xl border border-slate-100">
                    <div>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Mode</span>
                        <span class="font-semibold text-slate-700 flex items-center gap-1 mt-0.5">
                            @if($order->order_type === 'Delivery')
                                <i class="bi bi-truck text-sky-500"></i> Delivery
                            @else
                                <i class="bi bi-shop text-amber-500"></i> Pickup
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Total Amount</span>
                        <span class="font-black text-slate-900 mt-0.5 block">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-slate-500 pt-0.5">
                    <span class="text-[11px]">
                        <i class="bi bi-calendar3 mr-1 text-slate-400"></i>
                        {{ \Carbon\Carbon::parse($order->schedule_date)->format('M d, Y') }}
                        @if($order->schedule_time)
                            &bull; {{ \Carbon\Carbon::parse($order->schedule_time)->format('h:i A') }}
                        @endif
                    </span>
                    <div class="flex items-center gap-1.5">
                        @if(in_array($order->order_status, ['Delivered', 'Ready for Pickup', 'Delivered - Pending Remittance', 'Paid & Remitted']))
                            <a href="{{ route('customer.orders.show', $order->id) }}#review-products" class="inline-flex items-center space-x-1 py-1.5 px-2.5 rounded-xl text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 transition-all border border-amber-200/80 shadow-2xs">
                                <i class="bi bi-star-fill text-amber-400 text-[10px]"></i>
                                <span>Rate</span>
                            </a>
                        @endif
                        <a href="{{ route('customer.orders.show', $order->id) }}" class="inline-flex items-center space-x-1 py-1.5 px-3 rounded-xl text-xs font-bold text-sky-600 bg-sky-50 hover:bg-sky-100 transition-all border border-sky-100 shadow-2xs">
                            <span>Track</span>
                            <i class="bi bi-chevron-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-12 px-4 text-center">
                <i class="bi bi-clock-history text-3xl text-slate-200 mb-2 block"></i>
                <p class="text-sm font-semibold text-slate-800">No Orders Logged</p>
                <p class="text-xs text-slate-400 mt-0.5">You haven't placed any orders yet.</p>
                <a href="{{ route('customer.catalog') }}" class="mt-3 inline-flex items-center space-x-1.5 py-2 px-4 rounded-xl text-xs font-bold text-white bg-sky-500 hover:bg-sky-600 transition-all shadow-md shadow-sky-100">
                    <span>Order Now</span>
                </a>
            </div>
        @endforelse
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <th class="py-4 px-6">Order No.</th>
                    <th class="py-4 px-6">Fulfillment</th>
                    <th class="py-4 px-6">Schedule Date</th>
                    <th class="py-4 px-6">Total Amount</th>
                    <th class="py-4 px-6">Status</th>
                    <th class="py-4 px-6 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-xs text-slate-600">
                @forelse($orders as $order)
                <tr id="order-{{ $order->id }}" data-record-id="order-{{ $order->id }}" data-record-number="{{ $order->order_number }}" class="hover:bg-slate-50/50 transition-colors record-highlight-target">
                    <td class="py-4 px-6 font-bold text-slate-950">
                        {{ $order->order_number }}
                    </td>
                    <td class="py-4 px-6 font-medium text-slate-600">
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
                    <td class="py-4 px-6 text-slate-500 font-medium">
                        {{ \Carbon\Carbon::parse($order->schedule_date)->format('M d, Y') }} 
                        @if($order->schedule_time)
                            &bull; {{ \Carbon\Carbon::parse($order->schedule_time)->format('h:i A') }}
                        @endif
                    </td>
                    <td class="py-4 px-6 font-black text-slate-900">
                        ₱{{ number_format($order->total_amount, 2) }}
                    </td>
                    <td class="py-4 px-6">
                        @php
                            $statusColors = [
                                'Pending' => 'bg-amber-50 text-amber-700 border-amber-100/50',
                                'Confirmed' => 'bg-sky-50 text-sky-700 border-sky-100/50',
                                'Preparing' => 'bg-indigo-50 text-indigo-700 border-indigo-100/50',
                                'Ready for Pickup' => 'bg-emerald-50 text-emerald-700 border-emerald-100/50',
                                'Out for Delivery' => 'bg-purple-50 text-purple-700 border-purple-100/50',
                                'Delivered' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                                'Delivered - Pending Remittance' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                                'Paid & Remitted' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                                'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-100/50',
                            ];
                            $displayStatus = in_array($order->order_status, ['Delivered - Pending Remittance', 'Paid & Remitted']) ? 'Delivered' : $order->order_status;
                            $color = $statusColors[$displayStatus] ?? 'bg-slate-50 text-slate-600';
                        @endphp
                        <span class="order-status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $color }} shadow-2xs">
                            {{ $displayStatus }}
                        </span>
                        @if($order->isPaid())
                            <span class="text-[9px] text-emerald-600 font-bold block mt-1"><i class="bi bi-check-circle-fill text-[8px] mr-0.5"></i> Paid</span>
                        @elseif($order->isOnlinePayment())
                            <span class="text-[9px] text-amber-600 font-bold block mt-1"><i class="bi bi-clock-fill text-[8px] mr-0.5"></i> Unpaid</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-center">
                        <div class="inline-flex items-center justify-center gap-2">
                            @if(in_array($order->order_status, ['Delivered', 'Ready for Pickup', 'Delivered - Pending Remittance', 'Paid & Remitted']))
                                <a href="{{ route('customer.orders.show', $order->id) }}#review-products" class="inline-flex items-center space-x-1.5 py-1.5 px-3 rounded-xl text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 transition-all border border-amber-200/80 shadow-2xs hover:shadow-xs cursor-pointer">
                                    <i class="bi bi-star-fill text-amber-400 text-[10px]"></i>
                                    <span>Rate & Feedback</span>
                                </a>
                            @endif
                            <a href="{{ route('customer.orders.show', $order->id) }}" class="inline-flex items-center space-x-1.5 py-1.5 px-3.5 rounded-xl text-xs font-bold text-sky-600 bg-sky-50 hover:bg-sky-100 transition-all border border-sky-100 shadow-2xs hover:shadow-xs cursor-pointer">
                                <span>Track Order</span>
                                <i class="bi bi-chevron-right text-[10px]"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-16 text-center">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <i class="bi bi-clock-history text-4xl text-slate-200"></i>
                            <p class="text-sm font-semibold text-slate-800">No Orders Logged</p>
                            <p class="text-xs text-slate-400">You haven't placed any orders yet.</p>
                            <a href="{{ route('customer.catalog') }}" class="mt-3 inline-flex items-center space-x-1.5 py-2 px-4 rounded-xl text-xs font-bold text-white bg-sky-500 hover:bg-sky-600 transition-all shadow-md shadow-sky-100 cursor-pointer">
                                <span>Order Now</span>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
