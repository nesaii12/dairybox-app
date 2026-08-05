@extends('layouts.admin')

@section('title', 'Process Order ' . $order->order_number)
@section('page_title', 'Process Order')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
        <i class="bi bi-arrow-left mr-1"></i> Back to Orders List
    </a>
    <div class="flex items-center space-x-3 mt-2">
        <h2 class="text-2xl font-black text-slate-900">Order #{{ $order->order_number }}</h2>
        <span class="bg-sky-50 text-sky-700 text-xs px-2.5 py-0.5 rounded-full font-bold border border-sky-100">Fulfillment Panel</span>
    </div>
    <p class="text-xs text-slate-500">Processing dashboard for administrator review.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Panel: Order Items and Summary (Columns 1-2) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h3 class="text-sm font-bold text-slate-950 pb-3 border-b border-slate-100 mb-4 flex items-center justify-between">
                <span>Cart Items</span>
                <span class="text-xs text-slate-400 font-normal font-mono">{{ $order->orderItems->count() }} {{ Str::plural('item', $order->orderItems->count()) }}</span>
            </h3>
            
            <div class="divide-y divide-slate-100">
                @foreach($order->orderItems as $item)
                <div class="py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="h-12 w-12 rounded-xl overflow-hidden bg-slate-50 border border-slate-200 flex items-center justify-center flex-shrink-0">
                            @if($item->product->image)
                                <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->product_name }}" class="h-full w-full object-cover">
                            @else
                                <i class="bi bi-image text-slate-300"></i>
                            @endif
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-slate-900">{{ $item->product->product_name }}</span>
                            <span class="block text-[10px] text-slate-500 mt-0.5">Qty: <span class="font-bold text-slate-700">{{ $item->quantity }}</span> &bull; Size: <span class="font-bold text-slate-700">{{ $item->product->size }}</span> &bull; Price: ₱{{ number_format($item->price, 2) }}</span>
                        </div>
                    </div>
                    <span class="text-sm font-black text-slate-900">₱{{ number_format($item->subtotal, 2) }}</span>
                </div>
                @endforeach
            </div>

            <!-- Totals block -->
            <div class="border-t border-slate-100 pt-4 mt-4 space-y-3 text-slate-600 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Items Subtotal</span>
                    <span class="font-semibold text-slate-900">₱{{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Delivery Fee</span>
                    <span class="font-semibold text-slate-900">₱{{ number_format($order->delivery_fee, 2) }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
                    <span class="font-bold text-slate-900">Total Charges</span>
                    <span class="font-black text-sky-600 text-base">₱{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Customer Profile Summary -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <h3 class="text-sm font-bold text-slate-950 pb-3 border-b border-slate-100 mb-4">Customer Contact Information</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs text-slate-600">
                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Client Name</span>
                    <span class="text-sm font-bold text-slate-900">{{ $order->customer->name }}</span>
                </div>
                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Contact Phone</span>
                    <span class="text-sm font-bold text-slate-900 font-mono">{{ $order->customer->phone ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Email Address</span>
                    <span class="text-xs font-semibold text-slate-800 font-mono">{{ $order->customer->email }}</span>
                </div>
                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Fulfillment Method</span>
                    <span class="text-xs font-semibold text-slate-800">{{ $order->order_type }}</span>
                </div>
                
                @if($order->order_type === 'Delivery')
                <div class="sm:col-span-2">
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Delivery Destination</span>
                    <span class="text-xs font-semibold text-slate-800 leading-relaxed">{{ $order->delivery_address }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Panel: Status Control & Rider Assign (Column 3) -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Status Progression Panel -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <h3 class="text-sm font-bold text-slate-950 pb-3 border-b border-slate-100 mb-4">Order Pipeline Status</h3>
            
            <div class="mb-4">
                <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider">Current Status</span>
                @php
                    $statusColors = [
                        'Pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                        'Confirmed' => 'bg-sky-50 text-sky-700 border-sky-100',
                        'Preparing' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                        'Ready for Pickup' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        'Out for Delivery' => 'bg-purple-50 text-purple-700 border-purple-100',
                        'Delivered' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                        'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                    ];
                    $color = $statusColors[$order->order_status] ?? 'bg-slate-50 text-slate-600';
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border {{ $color }} mt-1">
                    {{ $order->order_status }}
                </span>
            </div>

            <!-- Progression Form -->
            @if($order->order_status !== 'Delivered' && $order->order_status !== 'Cancelled')
            <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="space-y-4 pt-4 border-t border-slate-100">
                @csrf
                <div>
                    <label for="order_status" class="block text-xs font-semibold text-slate-700">Update Status To</label>
                    <select name="order_status" id="order_status" required
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white cursor-pointer font-semibold">
                        
                        @if($order->order_status === 'Pending')
                            <option value="Confirmed">Confirm Order</option>
                            <option value="Cancelled">Cancel Order</option>
                        @elseif($order->order_status === 'Confirmed')
                            <option value="Preparing">Start Preparing</option>
                            <option value="Cancelled">Cancel Order</option>
                        @elseif($order->order_status === 'Preparing')
                            @if($order->order_type === 'Pickup')
                                <option value="Ready for Pickup">Set Ready for Pickup</option>
                            @else
                                <option value="Out for Delivery">Set Out for Delivery</option>
                            @endif
                            <option value="Cancelled">Cancel Order</option>
                        @elseif($order->order_status === 'Ready for Pickup')
                            <option value="Delivered">Mark Delivered (Picked Up)</option>
                            <option value="Cancelled">Cancel Order</option>
                        @elseif($order->order_status === 'Out for Delivery')
                            <option value="Delivered">Mark Delivered</option>
                            <option value="Cancelled">Cancel Order</option>
                        @endif
                    </select>
                </div>
                
                <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100 cursor-pointer">
                    <i class="bi bi-arrow-right-circle"></i>
                    <span>Apply Pipeline Update</span>
                </button>
            </form>
            @else
            <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs text-slate-500 italic mt-4 text-center">
                Order status is final. No further changes can be performed.
            </div>
            @endif
        </div>

        <!-- Rider Assignment Panel (For Delivery orders only) -->
        @if($order->order_type === 'Delivery' && $order->order_status !== 'Delivered' && $order->order_status !== 'Cancelled')
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <h3 class="text-sm font-bold text-slate-950 pb-3 border-b border-slate-100 mb-4">Assign Delivery Rider</h3>
            
            @if($order->rider)
                <div class="p-3 bg-sky-50 border border-sky-100 rounded-xl text-xs text-sky-800 mb-4 flex items-center justify-between shadow-2xs">
                    <div>
                        <span class="block font-bold">Assigned Rider:</span>
                        <span class="font-semibold">{{ $order->rider->name }} ({{ $order->rider->phone }})</span>
                    </div>
                    <i class="bi bi-bicycle text-xl text-sky-600"></i>
                </div>
            @endif

            <form action="{{ route('admin.orders.assignRider', $order->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="rider_id" class="block text-xs font-semibold text-slate-700">Choose Rider</label>
                    <select name="rider_id" id="rider_id" required
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white cursor-pointer font-semibold">
                        <option value="">-- Choose Rider --</option>
                        @foreach($riders as $rider)
                            <option value="{{ $rider->id }}" {{ $order->rider_id == $rider->id ? 'selected' : '' }}>
                                {{ $rider->name }} ({{ $rider->phone }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-all shadow-md shadow-indigo-100 cursor-pointer">
                    <i class="bi bi-person-fill-add"></i>
                    <span>{{ $order->rider ? 'Reassign Rider' : 'Assign & Dispatch' }}</span>
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
