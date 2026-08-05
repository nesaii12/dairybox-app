@extends('layouts.rider')

@section('title', 'Active Queue')

@section('content')
<!-- Top Queue Header & Statistics -->
<div class="mb-5 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-black text-slate-800 tracking-tight flex items-center space-x-2">
            <span>Active Queue</span>
            <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-xs px-2 py-0.5 rounded-full font-bold">
                {{ $deliveries->count() }}
            </span>
        </h2>
        <p class="text-[10px] text-slate-500 font-medium">Assigned delivery tasks for today.</p>
    </div>
</div>

<div class="space-y-5">
    @forelse($deliveries as $delivery)
        @php
            $order = $delivery->order;
            $customer = $order->customer;
        @endphp
        <!-- Modern Tactile Card -->
        <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex flex-col justify-between hover:border-slate-200/80 hover:shadow-md transition-all duration-300">
            <!-- Card Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <div>
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">ORDER REF</span>
                        <h3 class="text-xs font-bold text-slate-800 font-mono leading-none mt-0.5">{{ $order->order_number }}</h3>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">COLLECT</span>
                    <span class="text-sm font-black text-slate-950">
                        ₱{{ number_format($order->total_amount, 2) }}
                    </span>
                </div>
            </div>

            <!-- Customer & Delivery Specs -->
            <div class="space-y-3.5 mb-5 text-[11px] text-slate-600">
                <!-- Customer Name -->
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500 flex-shrink-0 border border-slate-100">
                        <i class="bi bi-person text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <span class="block text-[8px] text-slate-400 font-bold uppercase tracking-wider">Client Name</span>
                        <span class="text-xs font-semibold text-slate-800">{{ $customer->name }}</span>
                    </div>
                </div>

                <!-- Contact Phone -->
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500 flex-shrink-0 border border-slate-100">
                        <i class="bi bi-telephone text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <span class="block text-[8px] text-slate-400 font-bold uppercase tracking-wider">Contact Phone</span>
                        <a href="tel:{{ $customer->phone }}" class="text-xs font-semibold text-emerald-600 hover:underline select-all">{{ $customer->phone ?? 'N/A' }}</a>
                    </div>
                </div>

                <!-- Destination Address -->
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500 flex-shrink-0 border border-slate-100">
                        <i class="bi bi-geo-alt text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <span class="block text-[8px] text-slate-400 font-bold uppercase tracking-wider">Delivery Destination</span>
                        <span class="text-xs font-semibold text-slate-700 leading-relaxed">{{ $order->delivery_address }}</span>
                    </div>
                </div>
                
                <!-- Package Contents details -->
                <div class="p-3 bg-slate-50 rounded-2xl mt-4 space-y-2 border border-slate-100">
                    <span class="block text-[8px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100 pb-1.5 mb-1.5"><i class="bi bi-box-seam mr-1 text-slate-400"></i> Package Contents</span>
                    @foreach($order->orderItems as $item)
                        <div class="flex items-center justify-between text-[10px] text-slate-500">
                            <span class="truncate pr-4">{{ $item->product->product_name }} ({{ $item->product->size }})</span>
                            <span class="font-bold text-slate-700 whitespace-nowrap bg-slate-200/60 px-1.5 py-0.25 rounded">x{{ $item->quantity }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Action Status Form Triggers -->
            <div class="pt-4 border-t border-slate-100 flex flex-col gap-2">
                <!-- Confirm Delivered -->
                <form action="{{ route('rider.deliveries.updateStatus', $delivery->id) }}" method="POST" class="w-full">
                    @csrf
                    <input type="hidden" name="status" value="Delivered">
                    <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 shadow-sm active:scale-98 transition-all cursor-pointer">
                        <i class="bi bi-check2-circle text-sm"></i>
                        <span>Confirm Delivered</span>
                    </button>
                </form>

                <!-- Failure / Cancel layout buttons -->
                <div class="flex gap-2">
                    <form action="{{ route('rider.deliveries.updateStatus', $delivery->id) }}" method="POST" class="flex-1">
                        @csrf
                        <input type="hidden" name="status" value="Failed Delivery">
                        <button type="submit" class="w-full flex items-center justify-center space-x-1 py-2 px-2 rounded-xl text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-100 hover:bg-rose-100/60 active:scale-98 transition-all cursor-pointer">
                            <i class="bi bi-x-circle text-xs"></i>
                            <span>Failed</span>
                        </button>
                    </form>
                    
                    <form action="{{ route('rider.deliveries.updateStatus', $delivery->id) }}" method="POST" class="flex-1">
                        @csrf
                        <input type="hidden" name="status" value="Cancelled">
                        <button type="submit" class="w-full flex items-center justify-center space-x-1 py-2 px-2 rounded-xl text-[10px] font-bold text-slate-500 bg-slate-50 border border-slate-200 hover:bg-slate-100/80 hover:text-slate-700 active:scale-98 transition-all cursor-pointer">
                            <i class="bi bi-slash-circle text-xs"></i>
                            <span>Cancel</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <!-- Empty State card with pulsing glow -->
        <div class="py-16 px-6 text-center bg-white rounded-3xl border border-slate-100 shadow-sm flex flex-col items-center justify-center relative overflow-hidden mt-6">
            <!-- Pulsing outer circle glow -->
            <div class="absolute w-24 h-24 rounded-full bg-emerald-50/50 blur-xl animate-pulse"></div>
            
            <div class="w-16 h-16 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 mb-4 shadow-inner relative z-10">
                <i class="bi bi-patch-check text-3xl"></i>
            </div>
            
            <h3 class="text-sm font-bold text-slate-800 relative z-10">All Dispatches Completed!</h3>
            <p class="text-[10px] text-slate-500 mt-1 max-w-[200px] leading-relaxed relative z-10">No pending delivery assignments in your queue. Grab a fresh drink and take a break!</p>
        </div>
    @endforelse
</div>
@endsection
