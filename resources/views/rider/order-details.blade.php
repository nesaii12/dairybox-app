@extends('layouts.rider')

@section('title', 'Customer & Order Details #' . $order->order_number)

@section('content')
<!-- Top Back Navigation & Header -->
<div class="mb-4">
    <a href="{{ route('rider.dashboard') }}" class="inline-flex items-center text-xs font-bold text-slate-500 hover:text-sky-600 transition-colors mb-2">
        <i class="bi bi-arrow-left mr-1.5"></i> Back to Active Queue
    </a>
    <div class="flex items-center justify-between">
        <div>
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">ORDER FULFILLMENT</span>
            <h2 class="text-base font-black text-slate-900 font-mono leading-tight">#{{ $order->order_number }}</h2>
        </div>
        <span class="inline-flex items-center text-[10px] font-bold px-2.5 py-1 rounded-full border
            @if($order->order_status === 'Delivered' || $order->order_status === 'Delivered - Pending Remittance' || $order->order_status === 'Paid & Remitted') bg-emerald-50 text-emerald-700 border-emerald-200
            @elseif($order->order_status === 'Out for Delivery') bg-sky-50 text-sky-700 border-sky-200 animate-pulse
            @elseif($order->order_status === 'Cancelled') bg-rose-50 text-rose-700 border-rose-200
            @else bg-amber-50 text-amber-700 border-amber-200
            @endif">
            {{ $order->order_status }}
        </span>
    </div>
</div>

<div class="space-y-4">
    <!-- Customer Profile Card -->
    <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm space-y-4">
        <div class="flex items-center space-x-3.5 pb-4 border-b border-slate-100">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-500 to-sky-400 text-white font-black flex items-center justify-center text-base shadow-sm shrink-0">
                @if($customer->avatar)
                    <img src="{{ asset('storage/' . $customer->avatar) }}" alt="{{ $customer->name }}" class="w-full h-full object-cover rounded-2xl">
                @else
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                    <h3 class="text-sm font-bold text-slate-900 truncate">{{ $customer->name }}</h3>
                    <span class="text-[8px] font-bold bg-sky-50 text-sky-700 px-1.5 py-0.5 rounded border border-sky-100 uppercase tracking-wider shrink-0">Client</span>
                </div>
                <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ $customer->email }}</p>
            </div>
        </div>

        <!-- Trust Stats: Member Since & Order Count -->
        <div class="grid grid-cols-2 gap-2.5">
            <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-100 text-center">
                <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Client Since</span>
                <span class="text-xs font-bold text-slate-800 mt-0.5 block">{{ $customer->created_at->format('M Y') }}</span>
            </div>
            <div class="bg-slate-50 p-2.5 rounded-2xl border border-slate-100 text-center">
                <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Completed Orders</span>
                <span class="text-xs font-bold text-emerald-600 mt-0.5 block">{{ $completedOrdersCount }} completed</span>
            </div>
        </div>

        <!-- Direct Contact Actions -->
        <div>
            <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-2">Direct Contact Actions</span>
            <div class="grid grid-cols-3 gap-2">
                @if($customer->phone)
                    <a href="tel:{{ $customer->phone }}" class="flex flex-col items-center justify-center p-3 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200/80 rounded-2xl text-emerald-700 transition active:scale-95 shadow-2xs">
                        <i class="bi bi-telephone-fill text-base mb-1 text-emerald-600"></i>
                        <span class="text-[10px] font-bold">Call</span>
                    </a>
                    <a href="sms:{{ $customer->phone }}" class="flex flex-col items-center justify-center p-3 bg-sky-50 hover:bg-sky-100/80 border border-sky-200/80 rounded-2xl text-sky-700 transition active:scale-95 shadow-2xs">
                        <i class="bi bi-chat-dots-fill text-base mb-1 text-sky-600"></i>
                        <span class="text-[10px] font-bold">SMS Text</span>
                    </a>
                @else
                    <span class="col-span-2 text-center py-2 text-xs text-slate-400 bg-slate-50 rounded-xl border border-slate-100 font-medium">No phone on file</span>
                @endif
                <a href="mailto:{{ $customer->email }}" class="flex flex-col items-center justify-center p-3 bg-indigo-50 hover:bg-indigo-100/80 border border-indigo-200/80 rounded-2xl text-indigo-700 transition active:scale-95 shadow-2xs">
                    <i class="bi bi-envelope-fill text-base mb-1 text-indigo-600"></i>
                    <span class="text-[10px] font-bold">Email</span>
                </a>
            </div>
            @if($customer->phone)
                <p class="text-center text-[10px] text-slate-500 font-mono mt-2 font-medium">
                    Phone: <span class="font-bold text-slate-800 select-all">{{ $customer->phone }}</span>
                </p>
            @endif
        </div>
    </div>

    <!-- Delivery Destination & Schedule Card -->
    <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm space-y-3.5">
        <div class="flex items-center justify-between">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                <i class="bi bi-geo-alt-fill text-sky-500"></i> Delivery Destination
            </span>
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->delivery_address) }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-1 text-[10px] font-bold text-sky-600 hover:text-sky-700 hover:underline bg-sky-50 px-2 py-1 rounded-lg border border-sky-100">
                <i class="bi bi-map-fill"></i>
                <span>Open Maps</span>
                <i class="bi bi-box-arrow-up-right text-[8px] ml-0.5"></i>
            </a>
        </div>
        <p class="text-xs font-semibold text-slate-800 leading-relaxed bg-slate-50 p-3 rounded-2xl border border-slate-100 select-all">
            {{ $order->delivery_address }}
        </p>

        <!-- Scheduled Window -->
        <div class="grid grid-cols-2 gap-2 pt-1">
            <div class="p-2.5 bg-slate-50 rounded-2xl border border-slate-100">
                <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Scheduled Date</span>
                <span class="text-[11px] font-bold text-slate-800 mt-0.5 block flex items-center gap-1">
                    <i class="bi bi-calendar3 text-sky-500"></i>
                    {{ $order->schedule_date ? $order->schedule_date->format('M d, Y') : 'Today' }}
                </span>
            </div>
            <div class="p-2.5 bg-slate-50 rounded-2xl border border-slate-100">
                <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Time Window</span>
                <span class="text-[11px] font-bold text-slate-800 mt-0.5 block flex items-center gap-1">
                    <i class="bi bi-clock text-sky-500"></i>
                    {{ $order->schedule_time ? \Carbon\Carbon::parse($order->schedule_time)->format('h:i A') : 'Flexible Window' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Payment & Collection Card -->
    @php
        $isResOrder = $order->isReservationOrder();
        $resDownPayment = (float) ($order->down_payment_paid ?? 0);
        $codCollectible = $order->collectibleCodAmount();
        $hasPendingCod = $order->hasPendingBalance();
    @endphp
    <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">PAYMENT MODE</span>
                <span class="text-xs font-bold text-slate-800 mt-0.5 block">
                    {{ $isResOrder ? ($hasPendingCod ? 'RESERVATION COD BALANCE' : 'RESERVATION FULLY PAID') : strtoupper($order->payment_method) }}
                </span>
            </div>
            <div class="text-right">
                @if(!$hasPendingCod)
                    <span class="text-[8px] font-black text-emerald-600 uppercase tracking-widest block flex items-center justify-end">
                        <i class="bi bi-check-circle-fill mr-1 text-[8px]"></i> {{ $isResOrder ? 'FULLY PAID' : 'PAID ONLINE' }}
                    </span>
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-lg inline-block mt-0.5">
                        {{ $isResOrder ? 'Fully Paid / ₱0.00 to Collect' : '₱0.00 to collect' }}
                    </span>
                @else
                    <span class="text-[8px] font-black text-amber-700 uppercase tracking-widest block">COLLECT CASH (COD)</span>
                    <span class="text-sm font-black text-slate-950">
                        ₱{{ number_format($codCollectible, 2) }}
                    </span>
                @endif
            </div>
        </div>

        @if($isResOrder)
            <div class="pt-3 border-t border-slate-100 space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">Down Payment Paid</span>
                    <span class="font-bold text-emerald-700">₱{{ number_format($resDownPayment, 2) }} Paid</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Delivery Fee</span>
                    <span class="font-bold {{ (float) $order->delivery_fee > 0 ? 'text-slate-800' : 'text-emerald-700' }}">
                        {{ (float) $order->delivery_fee > 0 ? '₱' . number_format((float) $order->delivery_fee, 2) : 'Free' }}
                    </span>
                </div>
                <div class="flex justify-between font-black">
                    <span class="text-slate-800">Pending Balance to Collect</span>
                    <span class="{{ $hasPendingCod ? 'text-amber-700' : 'text-emerald-700' }}">
                        {{ $hasPendingCod ? '₱' . number_format($codCollectible, 2) : '₱0.00 (Fully Paid)' }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Package Contents Items -->
    <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm space-y-3">
        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 pb-2 border-b border-slate-100">
            <i class="bi bi-box-seam text-slate-500"></i> Package Contents ({{ $order->orderItems->count() }} items)
        </span>
        <div class="space-y-2">
            @foreach($order->orderItems as $item)
                <div class="flex items-center justify-between text-xs py-1.5 border-b border-slate-50 last:border-0">
                    <div class="min-w-0 pr-3">
                        <p class="font-bold text-slate-800 truncate">{{ $item->product ? $item->product->product_name : 'Dairy Product' }}</p>
                        <p class="text-[10px] text-slate-400">{{ $item->product?->size }} • ₱{{ number_format((float) ($item->price ?? $item->unit_price ?? 0), 2) }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-[10px] font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded-md">x{{ $item->quantity }}</span>
                        <p class="text-[11px] font-bold text-slate-900 mt-0.5">₱{{ number_format($item->subtotal, 2) }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span class="font-semibold text-slate-800">₱{{ number_format($order->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span>Delivery Fee</span>
                <span class="font-semibold text-slate-800">₱{{ number_format($order->delivery_fee, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm font-black text-slate-900 pt-2 border-t border-slate-100">
                <span>Total Amount</span>
                <span class="text-sky-600 font-bold">₱{{ number_format($order->total_amount, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Active Delivery Triggers if Assigned -->
    @if($order->delivery && $order->delivery->rider_id === auth()->id() && in_array($order->delivery->delivery_status, ['Out for Delivery', 'Assigned']))
        <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-sm space-y-2.5">
            <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Dispatch Action Controls</span>
            @if(!$order->delivery->arrived_at)
                <form action="{{ route('rider.deliveries.markArrived', $order->delivery->id) }}" method="POST" class="w-full">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 hover:bg-sky-100 active:scale-98 transition-all cursor-pointer">
                        <i class="bi bi-geo-alt-fill text-sky-600"></i>
                        <span>Mark Arrived at Destination</span>
                    </button>
                </form>
            @else
                <div class="bg-sky-50/80 border border-sky-200/70 rounded-xl py-2 px-3 flex items-center justify-between text-[11px] text-sky-900 font-semibold">
                    <span class="flex items-center gap-1.5">
                        <i class="bi bi-geo-alt-fill text-sky-600"></i>
                        <span>Arrived at Customer Location</span>
                    </span>
                    <span class="text-[10px] text-sky-700 font-mono">{{ $order->delivery->arrived_at->timezone('Asia/Manila')->format('h:i A') }}</span>
                </div>
            @endif

            <form action="{{ route('rider.deliveries.updateStatus', $order->delivery->id) }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="status" value="Delivered">
                <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 shadow-md shadow-sky-500/20 active:scale-98 transition-all cursor-pointer">
                    <i class="bi bi-check2-circle text-sm"></i>
                    <span>Confirm Delivered</span>
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
