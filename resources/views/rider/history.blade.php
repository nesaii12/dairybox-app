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
        <div class="bg-white rounded-2xl border border-slate-100 p-4 flex items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center space-x-3.5 overflow-hidden">
                <!-- Status icon bubble -->
                <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 border 
                    @if($delivery->delivery_status === 'Delivered') bg-emerald-50 text-emerald-600 border-emerald-100
                    @elseif($delivery->delivery_status === 'Failed Delivery') bg-rose-50 text-rose-600 border-rose-100
                    @else bg-slate-50 text-slate-500 border-slate-200/50
                    @endif shadow-inner">
                    <i class="bi @if($delivery->delivery_status === 'Delivered') bi-check-lg @else bi-x-lg @endif text-sm font-bold"></i>
                </div>
                <div class="overflow-hidden">
                    <span class="block text-xs font-bold text-slate-800 font-mono leading-none">{{ $order->order_number }}</span>
                    <span class="block text-[9px] text-slate-400 mt-1 truncate max-w-[180px]" title="{{ $order->delivery_address }}">Dest: {{ $order->delivery_address }}</span>
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
    @empty
        <!-- Empty State -->
        <div class="py-12 text-center bg-white rounded-2xl border border-slate-100 p-6 text-slate-400 shadow-sm">
            <i class="bi bi-journal-x text-3xl mb-2 block text-slate-300"></i>
            <p class="text-xs font-semibold text-slate-500">No logs found</p>
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
