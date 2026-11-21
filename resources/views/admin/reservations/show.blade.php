@extends('layouts.admin')

@section('title', 'Reservation ' . $reservation->reservation_code)
@section('page_title', 'Reservation Details')

@section('content')
@php
    $statusStyles = [
        'Pending' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Confirmed' => 'bg-sky-50 text-sky-700 border-sky-200',
        'Ready for Pickup' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-200',
    ];
    $paymentStyles = [
        'Unpaid' => 'bg-slate-100 text-slate-700 border-slate-200',
        'Partially Paid' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Fully Paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="space-y-1">
            <a href="{{ route('admin.reservations.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Reservations</span>
            </a>
            <div class="flex items-center gap-2.5 flex-wrap pt-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ $reservation->reservation_code }}</h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusStyles[$reservation->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    {{ $reservation->status }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $paymentStyles[$reservation->payment_status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                    {{ $reservation->payment_status }}
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            @if($reservation->canEdit())
                <a href="{{ route('admin.reservations.edit', $reservation->id) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-2xs">
                    <i class="bi bi-pencil-square"></i>
                    <span>Edit</span>
                </a>
            @endif

            @if($reservation->canConfirm())
                <form action="{{ route('admin.reservations.confirm', $reservation->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-xs transition-all cursor-pointer">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Confirm Reservation</span>
                    </button>
                </form>
            @endif

            @if($reservation->canMarkReady())
                <form action="{{ route('admin.reservations.ready', $reservation->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-xs transition-all cursor-pointer">
                        <i class="bi bi-box2-heart-fill"></i>
                        <span>Mark Ready for Pickup</span>
                    </button>
                </form>
            @endif

            @if($reservation->canComplete())
                <form action="{{ route('admin.reservations.complete', $reservation->id) }}" method="POST" class="inline"
                      onsubmit="return confirm('Mark {{ $reservation->reservation_code }} as Completed? This deducts {{ $reservation->quantity }} pcs from physical stock, releases reserved stock, and records ₱{{ number_format((float) $reservation->total_amount, 2) }} in sales reports.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-xs transition-all cursor-pointer">
                        <i class="bi bi-check2-circle"></i>
                        <span>Complete Pickup</span>
                    </button>
                </form>
            @endif

            @if($reservation->canCancel())
                <form action="{{ route('admin.reservations.cancel', $reservation->id) }}" method="POST" class="inline"
                      onsubmit="return confirm('Cancel reservation {{ $reservation->reservation_code }}? Reserved stock will be released back to available stock.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-all cursor-pointer">
                        <i class="bi bi-x-circle"></i>
                        <span>Cancel</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Columns -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer & Schedule Card -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-person-lines-fill text-sky-600"></i>
                    <span>Customer & Schedule Information</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Customer Name</span>
                        <span class="text-sm font-bold text-slate-900 mt-0.5 block">{{ $reservation->customer_name }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Contact Number</span>
                        <span class="text-sm font-bold text-slate-900 font-mono mt-0.5 block">{{ $reservation->contact_number }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Reservation Date</span>
                        <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ $reservation->reservation_date->format('F d, Y') }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-sky-50/70 border border-sky-100">
                        <span class="text-[10px] font-bold uppercase text-sky-600 block">Scheduled Pickup Date</span>
                        <span class="text-sm font-black text-sky-900 mt-0.5 block">{{ $reservation->pickup_date->format('F d, Y') }} ({{ $reservation->pickup_date->format('l') }})</span>
                    </div>
                </div>

                @if($reservation->notes)
                    <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-200/70 text-xs">
                        <span class="text-[10px] font-bold uppercase text-amber-800 block mb-1">Notes / Purpose</span>
                        <p class="text-slate-700 leading-relaxed">{{ $reservation->notes }}</p>
                    </div>
                @endif

                @if($reservation->status === 'Cancelled' && $reservation->cancellation_reason)
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs">
                        <span class="text-[10px] font-bold uppercase text-rose-700 block mb-1">Cancellation Reason</span>
                        <p class="text-rose-800">{{ $reservation->cancellation_reason }}</p>
                    </div>
                @endif
            </div>

            <!-- Reserved Product & Live Inventory Snapshot -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-box-seam-fill text-indigo-600"></i>
                    <span>Product & Inventory Allocation</span>
                </h3>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <div>
                        <span class="text-base font-black text-slate-900 block">{{ $reservation->product?->product_name ?? 'Product Removed' }}</span>
                        <span class="text-xs text-slate-500">
                            {{ $reservation->product?->category?->category_name ?? 'Uncategorized' }}
                            &bull; Size: {{ $reservation->size ?? $reservation->product?->size ?? 'Standard' }}
                            @if($reservation->product?->milk_type)
                                &bull; {{ $reservation->product->milk_type }} Milk
                            @endif
                        </span>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="text-lg font-black text-slate-900 block">{{ $reservation->quantity }} pcs &times; ₱{{ number_format((float) $reservation->unit_price, 2) }}</span>
                        <span class="text-xs font-bold text-sky-700">Allocated to Reserved Stock: {{ $reservation->allocated_quantity }} pcs</span>
                    </div>
                </div>

                @if($reservation->product)
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Physical Stock</span>
                            <span class="text-lg font-black text-slate-900">{{ $reservation->product->stock }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-100">
                            <span class="text-[10px] font-bold uppercase text-amber-700 block">Reserved Stock</span>
                            <span class="text-lg font-black text-amber-700">{{ $reservation->product->reserved_stock ?? 0 }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100">
                            <span class="text-[10px] font-bold uppercase text-emerald-700 block">Available Stock</span>
                            <span class="text-lg font-black text-emerald-700">{{ $reservation->product->available_stock }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Payment Breakdown & Status Log -->
        <div class="space-y-6">
            <!-- Payment Summary -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-receipt-cutoff text-emerald-600"></i>
                    <span>Payment Breakdown</span>
                </h3>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Total Amount</span>
                        <span class="font-bold text-slate-900">₱{{ number_format((float) $reservation->total_amount, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Down Payment</span>
                        <span class="font-bold text-emerald-700">₱{{ number_format((float) $reservation->down_payment, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-slate-700 font-bold">Remaining Balance</span>
                        <span class="text-lg font-black {{ (float) $reservation->remaining_balance > 0 ? 'text-slate-900' : 'text-emerald-600' }}">
                            ₱{{ number_format((float) $reservation->remaining_balance, 2) }}
                        </span>
                    </div>
                </div>

                @if($reservation->order)
                    <div class="pt-3 border-t border-slate-100">
                        <a href="{{ route('admin.orders.show', $reservation->order->id) }}" class="w-full inline-flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs font-bold transition-colors">
                            <span>Fulfilled Sale #{{ $reservation->order->order_number }}</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                @else
                    <p class="text-[11px] text-slate-500 bg-slate-50 border border-slate-100 rounded-xl p-3 leading-relaxed">
                        Revenue is logged into sales reports only when this reservation is marked <strong>Completed</strong>, preventing double-counting of down payments.
                    </p>
                @endif
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-3">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-clock-history text-slate-500"></i>
                    <span>Activity Log</span>
                </h3>
                <ul class="space-y-2.5 text-xs text-slate-600">
                    <li class="flex items-center justify-between">
                        <span>Created</span>
                        <span class="font-mono text-[11px] text-slate-500">{{ $reservation->created_at->format('M d, Y h:i A') }}</span>
                    </li>
                    @if($reservation->confirmed_at)
                        <li class="flex items-center justify-between">
                            <span class="text-sky-700 font-semibold">Confirmed</span>
                            <span class="font-mono text-[11px] text-slate-500">{{ $reservation->confirmed_at->format('M d, Y h:i A') }}</span>
                        </li>
                    @endif
                    @if($reservation->ready_at)
                        <li class="flex items-center justify-between">
                            <span class="text-indigo-700 font-semibold">Ready for Pickup</span>
                            <span class="font-mono text-[11px] text-slate-500">{{ $reservation->ready_at->format('M d, Y h:i A') }}</span>
                        </li>
                    @endif
                    @if($reservation->completed_at)
                        <li class="flex items-center justify-between">
                            <span class="text-emerald-700 font-semibold">Completed</span>
                            <span class="font-mono text-[11px] text-slate-500">{{ $reservation->completed_at->format('M d, Y h:i A') }}</span>
                        </li>
                    @endif
                    @if($reservation->cancelled_at)
                        <li class="flex items-center justify-between">
                            <span class="text-rose-600 font-semibold">Cancelled</span>
                            <span class="font-mono text-[11px] text-slate-500">{{ $reservation->cancelled_at->format('M d, Y h:i A') }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
