@extends('layouts.admin')

@section('title', 'Reservation ' . $reservation->reservation_code)
@section('page_title', 'Reservation Details')

@section('content')
@php
    $statusStyles = [
        'Pending' => 'bg-amber-50 text-amber-700 border-amber-200',
        'Confirmed' => 'bg-sky-50 text-sky-700 border-sky-200',
        'Ready for Pickup' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'Out for Delivery' => 'bg-orange-50 text-orange-700 border-orange-200',
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
            <x-chat-button
                type="reservation"
                :id="$reservation->id"
                :label="$reservation->reservation_code"
                :unread="$reservation->unreadChatCountFor('admin')"
                size="md"
                variant="indigo">
                Live Chat
            </x-chat-button>

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

            @if($reservation->canConvertToDelivery())
                <button type="button" onclick="openConvertDeliveryModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 shadow-xs transition-all cursor-pointer">
                    <i class="bi bi-truck"></i>
                    <span>Convert to Rider Delivery</span>
                </button>
            @elseif(in_array($reservation->status, [\App\Models\Reservation::STATUS_CONFIRMED, \App\Models\Reservation::STATUS_READY], true) && !$reservation->order_id)
                <button type="button" disabled
                    title="Available on scheduled pickup date ({{ $reservation->pickup_date->format('M d, Y') }})"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed">
                    <i class="bi bi-truck"></i>
                    <span>Convert to Delivery ({{ $reservation->pickup_date->format('M d') }})</span>
                </button>
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
                <button type="button" onclick="openShowCancelModal()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-all cursor-pointer">
                    <i class="bi bi-x-circle"></i>
                    <span>Cancel</span>
                </button>
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

                @if(($reservation->fulfillment_type ?? 'Pickup') === 'Delivery')
                    <div class="p-4 rounded-xl bg-orange-50/70 border border-orange-200 text-xs space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-bold uppercase text-orange-800 flex items-center gap-1.5">
                                <i class="bi bi-truck text-orange-600"></i>
                                <span>Converted to Rider Delivery</span>
                            </span>
                            <a href="{{ route('admin.deliveries.index', ['search' => $reservation->reservation_code]) }}"
                               class="text-[11px] font-bold text-sky-700 hover:underline">
                                View in Delivery Monitor &rarr;
                            </a>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 block">Assigned Rider</span>
                                <span class="font-bold text-slate-900 block">{{ $reservation->rider?->name ?? 'Assigned Rider' }}</span>
                                @if($reservation->rider?->phone)
                                    <span class="text-[11px] font-mono text-slate-500">{{ $reservation->rider->phone }}</span>
                                @endif
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-500 block">Delivery Destination</span>
                                <span class="font-semibold text-slate-800 block">{{ $reservation->delivery_address }}</span>
                            </div>
                        </div>
                        @if($reservation->order?->delivery)
                            <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-orange-200/70 text-[11px] text-slate-600">
                                <span>Delivery Status: <strong class="text-slate-900">{{ $reservation->order->delivery->delivery_status }}</strong></span>
                                @if($reservation->order->delivery->arrived_at)
                                    <span>&bull; Arrived: <strong class="font-mono text-amber-800">{{ $reservation->order->delivery->arrived_at->timezone('Asia/Manila')->format('M d, h:i A') }}</strong></span>
                                @endif
                                @if($reservation->order->delivery->delivered_at)
                                    <span>&bull; Delivered: <strong class="font-mono text-emerald-700">{{ $reservation->order->delivery->delivered_at->timezone('Asia/Manila')->format('M d, h:i A') }}</strong></span>
                                @endif
                            </div>
                        @endif
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
                    @php
                        $nonExpiredStock = \App\Services\InventoryService::getNonExpiredStock($reservation->product);
                        $reservableForPickup = \App\Services\InventoryService::getReservableStockForDate(
                            $reservation->product,
                            $reservation->pickup_date->toDateString(),
                            $reservation->id
                        );
                    @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
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
                        <div class="p-3 rounded-xl {{ $reservableForPickup > 0 ? 'bg-sky-50/70 border-sky-100' : 'bg-rose-50 border-rose-200' }} border">
                            <span class="text-[10px] font-bold uppercase {{ $reservableForPickup > 0 ? 'text-sky-700' : 'text-rose-700' }} block">Valid for Pickup</span>
                            <span class="text-lg font-black {{ $reservableForPickup > 0 ? 'text-sky-700' : 'text-rose-700' }}">{{ $reservableForPickup }}</span>
                        </div>
                    </div>

                    @if($nonExpiredStock <= 0 && $reservation->canEdit())
                        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                            <i class="bi bi-exclamation-octagon-fill text-rose-600"></i>
                            <span>All remaining physical stock for this product is expired and cannot be confirmed or reserved.</span>
                        </div>
                    @endif
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
                        <span class="text-slate-500 font-medium">Product Subtotal</span>
                        <span class="font-bold text-slate-900">₱{{ number_format($reservation->productSubtotal(), 2) }}</span>
                    </div>
                    @if(($reservation->fulfillment_type ?? 'Pickup') === 'Delivery')
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500 font-medium">Delivery Fee</span>
                            @if((float) $reservation->delivery_fee > 0)
                                <span class="font-bold text-slate-900">₱{{ number_format((float) $reservation->delivery_fee, 2) }}</span>
                            @else
                                <span class="font-bold text-emerald-700">Free (₱0.00)</span>
                            @endif
                        </div>
                    @endif
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Total Amount</span>
                        <span class="font-bold text-slate-900">₱{{ number_format((float) $reservation->total_amount, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500 font-medium">Down Payment Paid</span>
                        <span class="font-bold text-emerald-700">₱{{ number_format((float) $reservation->down_payment, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-slate-700 font-bold">{{ ($reservation->fulfillment_type ?? 'Pickup') === 'Delivery' ? 'Pending Balance (COD)' : 'Remaining Balance' }}</span>
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

            <!-- Cancellation & Refund Audit Card -->
            @if($reservation->hasCancellationSnapshot())
                @php
                    $refundBadgeStyles = [
                        'Pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'Not Applicable' => 'bg-slate-100 text-slate-600 border-slate-200',
                    ];
                    $appliedFeePctFormatted = fmod((float) $reservation->applied_fee_percentage, 1.0) === 0.0
                        ? (string) (int) $reservation->applied_fee_percentage
                        : rtrim(rtrim(number_format((float) $reservation->applied_fee_percentage, 2, '.', ''), '0'), '.');
                @endphp
                <div class="bg-white rounded-2xl border border-rose-200/80 shadow-xs p-6 space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-xs font-bold text-rose-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="bi bi-arrow-return-left"></i>
                            <span>Cancellation &amp; Refund Audit</span>
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $refundBadgeStyles[$reservation->refund_status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                            Refund: {{ $reservation->refund_status }}
                        </span>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500 font-medium">Initiated By</span>
                            <span class="font-bold text-slate-900">{{ $reservation->cancellationInitiatorLabel() }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500 font-medium">Applied Fee Rate</span>
                            <span class="font-bold text-slate-900">{{ $appliedFeePctFormatted }}%</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500 font-medium">Cancellation Fee Retained</span>
                            <span class="font-bold text-rose-600">₱{{ number_format((float) $reservation->cancellation_fee_amount, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-slate-700 font-bold">Refund Amount</span>
                            <span class="text-lg font-black text-emerald-700">₱{{ number_format((float) $reservation->refund_amount, 2) }}</span>
                        </div>
                    </div>

                    @if($reservation->applied_policy_description)
                        <p class="text-[11px] text-slate-500 bg-slate-50 border border-slate-100 rounded-xl p-3 leading-relaxed">
                            <strong class="text-slate-700 block mb-0.5">Policy Snapshot:</strong>
                            {{ $reservation->applied_policy_description }}
                        </p>
                    @endif

                    @if($reservation->canConfirmRefund())
                        <form action="{{ route('admin.reservations.confirmRefund', $reservation->id) }}" method="POST"
                              class="pt-4 border-t border-slate-100 space-y-3"
                              onsubmit="return confirm('Confirm that ₱{{ number_format((float) $reservation->refund_amount, 2) }} has been handed over/sent to {{ $reservation->customer_name }}? This marks the refund Completed and logs the cash outflow in the daily cash ledger.')">
                            @csrf
                            <div class="text-[11px] font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-3">
                                Refund remains <strong>Pending</strong> until physical or digital handover is confirmed below.
                            </div>

                            <div>
                                <label for="refund_method" class="block text-[11px] font-bold text-slate-700 mb-1">Refund Method</label>
                                <select name="refund_method" id="refund_method" required
                                    class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                                    <option value="Cash">Cash</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label for="refund_reference" class="block text-[11px] font-bold text-slate-700 mb-1">Reference / Receipt No. (Optional)</label>
                                <input type="text" name="refund_reference" id="refund_reference" maxlength="100"
                                    placeholder="e.g. GCash Ref # or Receipt No."
                                    class="w-full text-xs rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            </div>

                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-xs transition-all cursor-pointer">
                                <i class="bi bi-check2-circle"></i>
                                <span>Confirm Refund Handover (₱{{ number_format((float) $reservation->refund_amount, 2) }})</span>
                            </button>
                        </form>
                    @elseif($reservation->refund_status === \App\Models\Reservation::REFUND_COMPLETED)
                        <div class="pt-3 border-t border-slate-100 text-[11px] text-emerald-800 bg-emerald-50/70 border border-emerald-200 rounded-xl p-3 space-y-1">
                            <p class="font-bold flex items-center gap-1">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Refund Handover Confirmed</span>
                            </p>
                            <p>Method: <strong>{{ $reservation->refund_method ?? 'Cash' }}</strong>@if($reservation->refund_reference) &bull; Ref: <span class="font-mono">{{ $reservation->refund_reference }}</span>@endif</p>
                            <p>Confirmed by: <strong>{{ $reservation->refundConfirmer?->name ?? 'Administrator' }}</strong>@if($reservation->refund_confirmed_at) on {{ $reservation->refund_confirmed_at->format('M d, Y h:i A') }}@endif</p>
                        </div>
                    @endif
                </div>
            @endif

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
                    @if($reservation->dispatched_at)
                        <li class="flex items-center justify-between">
                            <span class="text-orange-700 font-semibold">Dispatched for Delivery</span>
                            <span class="font-mono text-[11px] text-slate-500">{{ $reservation->dispatched_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</span>
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
                    @if($reservation->refund_confirmed_at)
                        <li class="flex items-center justify-between">
                            <span class="text-emerald-700 font-semibold">Refund Confirmed</span>
                            <span class="font-mono text-[11px] text-slate-500">{{ $reservation->refund_confirmed_at->format('M d, Y h:i A') }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

@if($reservation->canCancel())
<!-- Cancel Reservation Modal -->
<div id="showCancelModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="showCancelModalTitle">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-x-circle text-rose-400"></i>
                <h3 id="showCancelModalTitle" class="text-sm font-bold">Cancel Reservation {{ $reservation->reservation_code }}</h3>
            </div>
            <button type="button" onclick="closeShowCancelModal()" class="text-slate-400 hover:text-white cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form action="{{ route('admin.reservations.cancel', $reservation->id) }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs flex items-center justify-between">
                <div>
                    <span class="font-bold text-slate-900 block">{{ $reservation->customer_name }}</span>
                    <span class="text-slate-500">{{ $reservation->quantity }} pcs &bull; {{ $reservation->product?->product_name }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Down Payment</span>
                    <span class="text-sm font-black text-emerald-700">₱{{ number_format((float) $reservation->down_payment, 2) }}</span>
                </div>
            </div>

            <div class="space-y-2">
                <span class="block text-xs font-bold text-slate-700">Cancellation Initiated By</span>
                <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:border-sky-300 cursor-pointer bg-white">
                    <input type="radio" name="cancellation_initiated_by" value="customer" checked onchange="updateShowCancelPreview()" class="mt-0.5 text-sky-600 focus:ring-sky-500">
                    <div class="text-xs">
                        <span class="font-bold text-slate-900 block">Customer-Initiated Cancellation</span>
                        <span class="text-[11px] text-slate-500">
                            @if($cancellationPolicy['enabled'])
                                Applies configured policy: {{ rtrim(rtrim(number_format($cancellationPolicy['fee_percentage'], 2, '.', ''), '0'), '.') }}% fee / {{ rtrim(rtrim(number_format($cancellationPolicy['refund_percentage'], 2, '.', ''), '0'), '.') }}% refund.
                            @else
                                Cancellation fee is currently disabled (0% fee / 100% refund).
                            @endif
                        </span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:border-emerald-300 cursor-pointer bg-white">
                    <input type="radio" name="cancellation_initiated_by" value="business" onchange="updateShowCancelPreview()" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                    <div class="text-xs">
                        <span class="font-bold text-slate-900 block">Business-Initiated (Unable to Fulfill)</span>
                        <span class="text-[11px] text-slate-500">Full refund override: ₱0.00 fee and 100% down payment refund.</span>
                    </div>
                </label>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Applied Cancellation Fee (<span id="showPreviewFeePct">0%</span>)</span>
                    <span id="showPreviewFeeAmt" class="font-bold text-rose-600">₱0.00</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-200/80">
                    <span class="font-bold text-slate-800">Refund Amount (Status: Pending)</span>
                    <span id="showPreviewRefundAmt" class="text-base font-black text-emerald-700">₱0.00</span>
                </div>
                <p class="text-[11px] text-slate-500 pt-1">
                    Reserved stock ({{ $reservation->allocated_quantity }} pcs) will be released back to Available Stock. Refund stays <strong>Pending</strong> until handover is confirmed.
                </p>
            </div>

            <div>
                <label for="show_cancellation_reason" class="block text-xs font-bold text-slate-700 mb-1">Cancellation Reason</label>
                <input type="text" name="cancellation_reason" id="show_cancellation_reason" maxlength="255"
                    placeholder="Optional reason for cancellation audit log"
                    class="w-full text-xs rounded-xl border-slate-300 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeShowCancelModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 cursor-pointer">
                    Keep Reservation
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 shadow-xs cursor-pointer">
                    Confirm Cancellation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const showPolicy = @json($cancellationPolicy);
    const showDownPayment = {{ (float) $reservation->down_payment }};

    function formatShowPeso(val) {
        return '₱' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatShowPct(val) {
        const r = Math.round(Number(val || 0) * 100) / 100;
        return (Number.isInteger(r) ? String(r) : r.toFixed(2).replace(/\.?0+$/, '')) + '%';
    }

    function updateShowCancelPreview() {
        const selected = document.querySelector('#showCancelModal input[name="cancellation_initiated_by"]:checked');
        const initiator = selected ? selected.value : 'customer';
        const feePct = (initiator === 'business' || !showPolicy.enabled) ? 0 : Number(showPolicy.fee_percentage || 0);
        const feeAmt = Math.round(showDownPayment * (feePct / 100) * 100) / 100;
        const refundAmt = Math.max(0, Math.round((showDownPayment - feeAmt) * 100) / 100);

        document.getElementById('showPreviewFeePct').textContent = formatShowPct(feePct);
        document.getElementById('showPreviewFeeAmt').textContent = formatShowPeso(feeAmt);
        document.getElementById('showPreviewRefundAmt').textContent = formatShowPeso(refundAmt);
    }

    function openShowCancelModal() {
        updateShowCancelPreview();
        document.getElementById('showCancelModal').classList.remove('hidden');
    }

    function closeShowCancelModal() {
        document.getElementById('showCancelModal').classList.add('hidden');
    }
</script>
@endif

@if($reservation->canConvertToDelivery())
@php
    $convertBreakdown = \App\Services\ReservationService::calculateDeliveryConversionBreakdown(
        $reservation->productSubtotal(),
        (float) $reservation->down_payment
    );
@endphp
<!-- Convert to Rider Delivery Modal -->
<div id="convertDeliveryModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="convertDeliveryTitle">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-truck text-amber-400"></i>
                <h3 id="convertDeliveryTitle" class="text-sm font-bold">Convert {{ $reservation->reservation_code }} to Rider Delivery</h3>
            </div>
            <button type="button" onclick="closeConvertDeliveryModal()" class="text-slate-400 hover:text-white cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form action="{{ route('admin.reservations.convertToDelivery', $reservation->id) }}" method="POST" class="p-6 space-y-4">
            @csrf

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs flex items-center justify-between">
                <div>
                    <span class="font-bold text-slate-900 block">{{ $reservation->customer_name }}</span>
                    <span class="text-slate-500">{{ $reservation->quantity }} pcs &bull; {{ $reservation->product?->product_name }}</span>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    Pickup Day Dispatch
                </span>
            </div>

            <div>
                <label for="convert_delivery_address" class="block text-xs font-bold text-slate-700 mb-1">Delivery Destination / Address <span class="text-rose-500">*</span></label>
                <input type="text" name="delivery_address" id="convert_delivery_address" required maxlength="250"
                    value="{{ old('delivery_address', $reservation->customer?->address ?? '') }}"
                    placeholder="Enter complete delivery address and landmark"
                    class="w-full text-xs rounded-xl border-slate-300 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
            </div>

            <div>
                <label for="convert_rider_id" class="block text-xs font-bold text-slate-700 mb-1">Assign Delivery Rider <span class="text-rose-500">*</span></label>
                @if($activeRiders->isEmpty())
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold">
                        No active riders are currently available. Please add or activate a rider first.
                    </div>
                @else
                    <select name="rider_id" id="convert_rider_id" required
                        class="w-full text-xs font-semibold rounded-xl border-slate-300 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                        <option value="">-- Select Active Rider --</option>
                        @foreach($activeRiders as $riderOption)
                            <option value="{{ $riderOption->id }}" {{ (string) old('rider_id') === (string) $riderOption->id ? 'selected' : '' }}>
                                {{ $riderOption->name }}@if($riderOption->phone) ({{ $riderOption->phone }})@endif
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Reserved Product Subtotal</span>
                    <span class="font-bold text-slate-900">₱{{ number_format($convertBreakdown['product_subtotal'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Tiered Delivery Fee {{ $convertBreakdown['delivery_fee'] > 0 ? '(< ₱3,000)' : '(≥ ₱3,000 Free)' }}</span>
                    <span class="font-bold {{ $convertBreakdown['delivery_fee'] > 0 ? 'text-slate-900' : 'text-emerald-700' }}">
                        {{ $convertBreakdown['delivery_fee'] > 0 ? '₱' . number_format($convertBreakdown['delivery_fee'], 2) : 'Free (₱0.00)' }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Updated Total Amount</span>
                    <span class="font-bold text-slate-900">₱{{ number_format($convertBreakdown['total_amount'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Less Down Payment Paid</span>
                    <span class="font-bold text-emerald-700">-₱{{ number_format($convertBreakdown['down_payment_paid'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-200/80">
                    <span class="font-bold text-slate-800">Pending Balance to Collect (COD)</span>
                    <span class="text-base font-black {{ $convertBreakdown['pending_balance'] > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                        @if($convertBreakdown['pending_balance'] > 0)
                            ₱{{ number_format($convertBreakdown['pending_balance'], 2) }}
                        @else
                            ₱0.00 (Fully Paid)
                        @endif
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 pt-1">
                    Stock ({{ $reservation->allocated_quantity }} pcs) stays locked in <strong>Reserved Stock</strong> while in transit and deducts from Physical Stock upon rider delivery confirmation.
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeConvertDeliveryModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 cursor-pointer">
                    Cancel
                </button>
                <button type="submit" {{ $activeRiders->isEmpty() ? 'disabled' : '' }}
                    class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 shadow-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    Dispatch to Rider
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openConvertDeliveryModal() {
        document.getElementById('convertDeliveryModal').classList.remove('hidden');
    }

    function closeConvertDeliveryModal() {
        document.getElementById('convertDeliveryModal').classList.add('hidden');
    }
</script>
@endif
@endsection
