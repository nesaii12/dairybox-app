@extends('layouts.rider')

@section('title', 'History Log')

@section('content')
<!-- Header -->
<div class="mb-5">
    <h2 class="text-lg font-black text-slate-800 tracking-tight">Delivery Logs</h2>
    <p class="text-[10px] text-slate-500 font-medium">Review your past completed, failed, or cancelled delivery records.</p>
</div>

<div class="space-y-3">
    @forelse($deliveries as $delivery)
        @php
            $order = $delivery->order;
        @endphp
        <!-- Modern Transaction Row -->
        <div id="delivery-{{ $delivery->id }}" data-record-id="delivery-{{ $delivery->id }}" data-record-number="{{ $order->order_number }}" class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm hover:border-slate-200 transition-all record-highlight-target">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center space-x-3 overflow-hidden flex-1 min-w-0">
                    <!-- Status icon bubble -->
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 border 
                        @if($delivery->delivery_status === 'Delivered') bg-emerald-50 text-emerald-600 border-emerald-100
                        @elseif($delivery->delivery_status === 'Failed Delivery') bg-rose-50 text-rose-600 border-rose-100
                        @else bg-slate-50 text-slate-500 border-slate-200/50
                        @endif shadow-inner">
                        <i class="bi @if($delivery->delivery_status === 'Delivered') bi-check-lg @else bi-x-lg @endif text-sm font-bold"></i>
                    </div>
                    <div class="overflow-hidden flex-1 min-w-0">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-xs font-bold text-slate-800 font-mono leading-none">{{ $order->order_number }}</span>
                            <span class="text-[8px] bg-slate-100 text-slate-600 px-1 py-0.25 rounded font-semibold">{{ $order->order_type }}</span>
                        </div>
                        <p class="text-[10px] text-slate-700 font-medium truncate mt-0.5">
                            <span class="text-slate-400">Client:</span> <strong class="text-slate-800">{{ $order->customer->name }}</strong>
                        </p>
                        <span class="block text-[9px] text-slate-400 truncate" title="{{ $order->delivery_address }}">Dest: {{ $order->delivery_address }}</span>
                        @if($delivery->cancellation_reason || $order->cancellation_reason)
                            <span class="block text-[9px] text-rose-600 font-medium mt-0.5 truncate" title="{{ $delivery->cancellation_reason ?? $order->cancellation_reason }}">
                                Reason: {{ $delivery->cancellation_reason ?? $order->cancellation_reason }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="text-right flex-shrink-0">
                    <span class="text-xs font-black text-slate-800 block">₱{{ number_format($order->total_amount, 2) }}</span>
                    <span class="inline-flex items-center text-[8px] font-bold uppercase mt-1 px-1.5 py-0.25 rounded border 
                        @if($delivery->delivery_status === 'Delivered') bg-emerald-50 text-emerald-600 border-emerald-100
                        @elseif($delivery->delivery_status === 'Failed Delivery') bg-rose-50 text-rose-600 border-rose-100
                        @else bg-slate-50 text-slate-500 border-slate-200/50
                        @endif">
                        {{ $delivery->delivery_status }}
                    </span>
                </div>
            </div>

            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400 font-medium">{{ $delivery->updated_at->diffForHumans() }}</span>
                <button type="button" onclick="showHistoryCustomerDetails({{ $delivery->id }})" class="font-bold text-sky-600 hover:text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200/80 px-2.5 py-1 rounded-lg transition cursor-pointer flex items-center gap-1 shadow-2xs">
                    <i class="bi bi-person-lines-fill text-[10px]"></i>
                    <span>Customer Details</span>
                    <i class="bi bi-chevron-right text-[8px]"></i>
                </button>
            </div>
        </div>
    @empty
        <!-- Empty State -->
        <div class="py-12 text-center bg-white rounded-2xl border border-slate-100 p-6 text-slate-400 shadow-xs flex flex-col items-center justify-center">
            <div class="w-12 h-12 rounded-xl bg-sky-50/80 border border-sky-100 flex items-center justify-center p-2 mb-2">
                <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain opacity-70">
            </div>
            <p class="text-xs font-semibold text-slate-600">No logs found</p>
            <p class="text-[10px] text-slate-400 mt-0.5">Your delivery history will show up here.</p>
        </div>
    @endforelse

    @if($deliveries->hasPages())
    <div class="pt-4">
        {{ $deliveries->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    const historyDataMap = {
        @foreach($deliveries as $delivery)
        {{ $delivery->id }}: {
            order: {
                id: {{ $delivery->order->id }},
                order_number: @js($delivery->order->order_number),
                order_status: @js($delivery->order->order_status),
                order_type: @js($delivery->order->order_type),
                is_reservation: {{ $delivery->order->isReservationOrder() ? 'true' : 'false' }},
                payment_method: @js($delivery->order->payment_method),
                payment_status: @js($delivery->order->payment_status),
                is_paid: {{ ($delivery->order->isPaid() || !$delivery->order->hasPendingBalance()) ? 'true' : 'false' }},
                has_pending_balance: {{ $delivery->order->hasPendingBalance() ? 'true' : 'false' }},
                down_payment_paid: @js(number_format((float) ($delivery->order->down_payment_paid ?? 0), 2)),
                collectible_cod: @js(number_format($delivery->order->collectibleCodAmount(), 2)),
                delivery_address: @js($delivery->order->delivery_address),
                schedule_date: @js($delivery->order->schedule_date ? $delivery->order->schedule_date->format('M d, Y') : null),
                schedule_time: @js($delivery->order->schedule_time ? \Carbon\Carbon::parse($delivery->order->schedule_time)->format('h:i A') : 'Standard Window'),
                subtotal: @js(number_format($delivery->order->subtotal, 2)),
                delivery_fee: @js(number_format($delivery->order->delivery_fee, 2)),
                total_amount: @js(number_format($delivery->order->total_amount, 2)),
                created_at: @js($delivery->order->created_at->format('M d, Y h:i A')),
                items: [
                    @foreach($delivery->order->orderItems as $item)
                    {
                        name: @js($item->product ? $item->product->product_name : 'Item'),
                        size: @js($item->product ? $item->product->size : null),
                        quantity: {{ $item->quantity }},
                        price: @js(number_format((float) ($item->price ?? $item->unit_price ?? 0), 2)),
                        subtotal: @js(number_format($item->subtotal, 2)),
                    },
                    @endforeach
                ]
            },
            customer: {
                id: {{ $delivery->order->customer->id }},
                name: @js($delivery->order->reservation?->customer_name ?? $delivery->order->customer->name),
                phone: @js($delivery->order->reservation?->contact_number ?? $delivery->order->customer->phone),
                email: @js($delivery->order->customer->email),
                avatar: @js($delivery->order->customer->avatar),
                member_since: @js($delivery->order->customer->created_at->format('M Y')),
                completed_orders_count: {{ $delivery->order->customer->orders()->whereIn('order_status', ['Delivered', 'Paid & Remitted'])->count() }},
                status: @js($delivery->order->customer->status ?? 'active'),
            }
        },
        @endforeach
    };

    function showHistoryCustomerDetails(deliveryId) {
        const data = historyDataMap[deliveryId];
        if (data && window.renderCustomerDetailsModal) {
            window.renderCustomerDetailsModal(data.order, data.customer);
        } else if (data) {
            window.location.href = `/rider/orders/${data.order.id}`;
        }
    }
</script>
@endsection
