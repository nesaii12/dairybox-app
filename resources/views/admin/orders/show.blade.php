@extends('layouts.admin')

@section('title', 'Process Order ' . $order->order_number)
@section('page_title', 'Process Order')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
        <i class="bi bi-arrow-left mr-1"></i> Back to Orders List
    </a>
    <div class="flex items-center justify-between flex-wrap gap-3 mt-2">
        <div class="flex items-center space-x-3">
            <h2 class="text-2xl font-black text-slate-900">Order #{{ $order->order_number }}</h2>
            <span class="bg-sky-50 text-sky-700 text-xs px-2.5 py-0.5 rounded-full font-bold border border-sky-100">Fulfillment Panel</span>
        </div>
        @if($order->order_type === 'Delivery')
            <a href="{{ route('admin.deliveries.index', ['search' => $order->order_number]) }}" class="inline-flex items-center text-xs font-semibold text-sky-700 hover:text-sky-900 bg-sky-50 px-3 py-1.5 rounded-xl border border-sky-200 transition-colors">
                <i class="bi bi-truck mr-1.5"></i> Track in Delivery Monitor
            </a>
        @endif
    </div>
    <p class="text-xs text-slate-500">Processing dashboard for administrator review.</p>
</div>

@if($order->order_status === 'Cancelled' && !$order->cancelled_after_arrival && !($order->delivery && $order->delivery->cancelled_after_arrival))
<div class="mb-6 bg-rose-50 border border-rose-200 rounded-2xl p-5 shadow-xs">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start space-x-3.5">
            <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                <i class="bi bi-x-circle-fill text-lg font-bold"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-rose-900">Order Cancelled</h3>
                    @if($order->customer->isRestricted())
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-rose-200 text-rose-800 px-2 py-0.5 rounded-full">Customer Restricted (3-Day Ban)</span>
                    @endif
                </div>
                <p class="text-xs text-rose-700 mt-0.5">This order has been cancelled and stock has been restored to inventory.</p>
                @if($order->cancellation_reason || $order->delivery?->cancellation_reason)
                    <p class="text-xs text-rose-800 font-medium mt-1">
                        <span class="font-bold">Cancellation Reason:</span> "{{ $order->cancellation_reason ?? $order->delivery->cancellation_reason }}"
                    </p>
                @endif
                <div class="mt-2 text-xs">
                    @if($order->customer->isRestricted())
                        <span class="inline-flex items-center text-rose-900 font-semibold">
                            <i class="bi bi-person-x-fill mr-1"></i> Customer is restricted from ordering until {{ $order->customer->restricted_until->format('M d, Y h:i A') }}
                        </span>
                    @else
                        <span class="text-slate-600">
                            Per store policy, admins can temporarily restrict this customer from placing orders for three (3) days.
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            @if($order->customer->isRestricted())
                <form action="{{ route('admin.customers.unrestrict', $order->customer->id) }}" method="POST" onsubmit="return confirmLiftRestriction(this, '{{ addslashes($order->customer->name) }}');">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer flex items-center">
                        <i class="bi bi-arrow-counterclockwise mr-1.5"></i> Lift Restriction
                    </button>
                </form>
            @else
                <form action="{{ route('admin.customers.restrict', $order->customer->id) }}" method="POST" onsubmit="return confirmRestrictCustomer(this, '{{ addslashes($order->customer->name) }}', '{{ addslashes('Cancelled Order #' . $order->order_number . ': ' . ($order->cancellation_reason ?? 'Customer cancellation')) }}');">
                    @csrf
                    <input type="hidden" name="reason" value="Cancelled Order #{{ $order->order_number }}: {{ $order->cancellation_reason ?? 'Customer cancellation' }}">
                    <button type="submit" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer flex items-center">
                        <i class="bi bi-shield-slash mr-1.5"></i> Restrict Customer (3 Days)
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endif

@if($order->cancelled_after_arrival || ($order->delivery && $order->delivery->cancelled_after_arrival))
<div class="mb-6 bg-rose-50 border border-rose-200 rounded-2xl p-5 shadow-xs">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start space-x-3.5">
            <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center flex-shrink-0 mt-0.5">
                <i class="bi bi-exclamation-octagon text-lg font-bold"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-rose-900">Post-Arrival Cancellation Incident</h3>
                    @if($order->delivery && $order->delivery->admin_verified_at)
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-rose-200 text-rose-800 px-2 py-0.5 rounded-full">Verified by Admin</span>
                    @else
                        <span class="text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">Awaiting Admin Verification</span>
                    @endif
                </div>
                <p class="text-xs text-rose-700 mt-1">
                    The rider reported that the customer cancelled this order after arrival at the delivery destination
                    @if($order->delivery?->arrived_at)
                        ({{ $order->delivery->arrived_at->format('M d, Y h:i A') }})
                    @endif.
                </p>
                @if($order->cancellation_reason || $order->delivery?->cancellation_reason)
                    <p class="text-xs text-rose-800 font-medium mt-1">
                        <span class="font-bold">Stated Reason:</span> "{{ $order->cancellation_reason ?? $order->delivery->cancellation_reason }}"
                    </p>
                @endif
                <div class="mt-2 text-xs">
                    @if($order->customer->isRestricted())
                        <span class="inline-flex items-center text-rose-900 font-semibold">
                            <i class="bi bi-person-x-fill mr-1"></i> Customer is restricted until {{ $order->customer->restricted_until->format('M d, Y h:i A') }}
                        </span>
                    @else
                        <span class="text-slate-600">
                            Per store policy, verified post-arrival cancellations permit restricting the customer from placing orders for three (3) days.
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            @if($order->customer->isRestricted())
                <form action="{{ route('admin.customers.unrestrict', $order->customer->id) }}" method="POST" onsubmit="return confirmLiftRestriction(this, '{{ addslashes($order->customer->name) }}');">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer flex items-center">
                        <i class="bi bi-arrow-counterclockwise mr-1.5"></i> Lift Restriction
                    </button>
                </form>
            @else
                @if($order->delivery)
                    <form action="{{ route('admin.deliveries.verifyRestrict', $order->delivery->id) }}" method="POST" onsubmit="return confirmRestrictCustomer(this, '{{ addslashes($order->customer->name) }}', '{{ addslashes('Cancelled Order #' . $order->order_number . ' after rider arrived at destination.') }}');">
                        @csrf
                        <input type="hidden" name="reason" value="Cancelled Order #{{ $order->order_number }} after rider arrived at destination.">
                        <button type="submit" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer flex items-center">
                            <i class="bi bi-shield-slash mr-1.5"></i> Verify & Restrict Customer (3 Days)
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.customers.restrict', $order->customer->id) }}" method="POST" onsubmit="return confirmRestrictCustomer(this, '{{ addslashes($order->customer->name) }}', '{{ addslashes('Cancelled Order #' . $order->order_number . ' after rider arrived at destination.') }}');">
                        @csrf
                        <input type="hidden" name="reason" value="Cancelled Order #{{ $order->order_number }} after rider arrived at destination.">
                        <button type="submit" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer flex items-center">
                            <i class="bi bi-shield-slash mr-1.5"></i> Restrict Customer (3 Days)
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Panel: Order Items and Summary (Columns 1-2) -->
    <div class="lg:col-span-2 space-y-6">
        <div id="order-{{ $order->id }}" data-record-id="order-{{ $order->id }}" data-record-number="{{ $order->order_number }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 record-highlight-target">
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
                            <span class="block text-[10px] text-slate-500 mt-0.5">
                                Qty: <span class="font-bold text-slate-700">{{ $item->quantity }}</span> &bull; Size: <span class="font-bold text-slate-700">{{ $item->product->size }}</span> &bull; Price: ₱{{ number_format($item->price, 2) }}
                                @if($item->product->manufacturing_date)
                                    &bull; <span class="text-slate-500 font-medium">Mfg: {{ \Carbon\Carbon::parse($item->product->manufacturing_date)->format('M d, Y') }}</span>
                                @endif
                                @if($item->product->expiration_date)
                                    &bull; <span class="text-slate-500 font-medium">Exp: {{ \Carbon\Carbon::parse($item->product->expiration_date)->format('M d, Y') }}</span>
                                @endif
                            </span>
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
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-slate-900">{{ $order->customer->name }}</span>
                        @if($order->customer->isRestricted())
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                Restricted (3-Day Ban)
                            </span>
                        @endif
                    </div>
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
        <!-- Payment Details Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
            <h3 class="text-sm font-bold text-slate-950 pb-3 border-b border-slate-100 mb-4 flex items-center justify-between">
                <span>Payment Details</span>
                @if($order->isPaid())
                    <span class="inline-flex items-center text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                        <i class="bi bi-check-circle-fill mr-1 text-emerald-600"></i> Paid
                    </span>
                @elseif($order->isOnlinePayment())
                    <span class="inline-flex items-center text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                        <i class="bi bi-clock-fill mr-1 text-amber-600"></i> Pending Payment
                    </span>
                @else
                    <span class="inline-flex items-center text-[10px] font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full">
                        Cash on Delivery
                    </span>
                @endif
            </h3>
            
            <div class="space-y-3.5 text-xs text-slate-600">
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Method</span>
                    <span class="font-bold text-slate-900">
                        @if($order->isOnlinePayment())
                            Online (Xendit)
                        @else
                            Cash on Delivery / Pickup
                        @endif
                    </span>
                </div>

                @if($order->payment_channel)
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Channel / Wallet</span>
                    <span class="font-semibold text-slate-800 uppercase px-2 py-0.5 bg-slate-100 rounded text-[11px]">{{ $order->payment_channel }}</span>
                </div>
                @endif

                @if($order->paid_at)
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Paid At</span>
                    <span class="font-medium text-slate-700">{{ $order->paid_at->format('M d, Y h:i A') }}</span>
                </div>
                @endif

                @if($order->xendit_invoice_id)
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Invoice ID</span>
                    <span class="font-mono text-[10px] text-slate-600 truncate max-w-[150px]" title="{{ $order->xendit_invoice_id }}">{{ $order->xendit_invoice_id }}</span>
                </div>
                @endif
            </div>
        </div>

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
                @if($order->order_status === 'Cancelled' && ($order->cancellation_reason || $order->delivery?->cancellation_reason))
                    <div class="mt-2 text-xs text-rose-800 bg-rose-50 border border-rose-100 p-2.5 rounded-xl">
                        <span class="font-bold block text-[10px] uppercase tracking-wider text-rose-500">Cancellation Reason</span>
                        <span class="italic font-medium">"{{ $order->cancellation_reason ?? $order->delivery->cancellation_reason }}"</span>
                    </div>
                @endif
            </div>

            <!-- Progression Form -->
            @if($order->order_status !== 'Delivered' && $order->order_status !== 'Cancelled')
            <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="space-y-4 pt-4 border-t border-slate-100">
                @csrf
                <div>
                    <label for="order_status" class="block text-xs font-semibold text-slate-700">Update Status To</label>
                    <select name="order_status" id="order_status" required onchange="toggleAdminCancellationReason(this.value)"
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

                <!-- Admin Cancellation Reason Input Box (Hidden by default unless Cancelled is chosen) -->
                <div id="admin_cancellation_reason_box" class="hidden space-y-2 pt-2 border-t border-slate-100">
                    <label for="admin_cancellation_reason" class="block text-xs font-semibold text-rose-700">
                        Cancellation Reason <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-1.5 mb-1.5">
                        <button type="button" onclick="setAdminReason('Out of stock / Production shortage')" class="px-2 py-1 text-[10px] rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">Out of stock</button>
                        <button type="button" onclick="setAdminReason('Customer requested cancellation via call')" class="px-2 py-1 text-[10px] rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">Customer request</button>
                        <button type="button" onclick="setAdminReason('Delivery address unreachable / Out of range')" class="px-2 py-1 text-[10px] rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">Unreachable address</button>
                        <button type="button" onclick="setAdminReason('Duplicate or fraudulent order')" class="px-2 py-1 text-[10px] rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition cursor-pointer">Duplicate order</button>
                    </div>
                    <textarea name="cancellation_reason" id="admin_cancellation_reason" rows="2"
                        placeholder="State reason why this order is being cancelled..."
                        class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-rose-500/10 focus:border-rose-500 text-xs transition-all focus:bg-white resize-none font-medium"></textarea>

                    <div class="pt-2 flex items-center space-x-2 border-t border-slate-100">
                        <input type="checkbox" name="restrict_customer" id="admin_restrict_customer" value="1"
                            class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500 cursor-pointer">
                        <label for="admin_restrict_customer" class="text-xs font-semibold text-rose-800 cursor-pointer">
                            Restrict customer from ordering for 3 days
                        </label>
                    </div>
                </div>
                
                <button type="submit" id="adminSubmitStatusBtn" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100 cursor-pointer">
                    <i class="bi bi-arrow-right-circle"></i>
                    <span id="adminSubmitStatusText">Apply Pipeline Update</span>
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
                <div class="p-3 bg-sky-50 border border-sky-100 rounded-xl text-xs text-sky-800 mb-4 shadow-2xs">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="block font-bold">Assigned Rider:</span>
                            <span class="font-semibold">{{ $order->rider->name }} ({{ $order->rider->phone }})</span>
                        </div>
                        <i class="bi bi-bicycle text-xl text-sky-600"></i>
                    </div>
                    @if($order->delivery?->arrived_at)
                        <div class="mt-2 pt-2 border-t border-sky-200/60 flex items-center text-[11px] text-emerald-700 font-semibold">
                            <i class="bi bi-geo-alt-fill mr-1 text-emerald-600"></i> Arrived at Destination ({{ $order->delivery->arrived_at->format('h:i A') }})
                        </div>
                    @endif
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

@section('scripts')
<script>
    function toggleAdminCancellationReason(status) {
        const box = document.getElementById('admin_cancellation_reason_box');
        const textarea = document.getElementById('admin_cancellation_reason');
        const btn = document.getElementById('adminSubmitStatusBtn');
        const btnText = document.getElementById('adminSubmitStatusText');

        if (!box) return;

        if (status === 'Cancelled') {
            box.classList.remove('hidden');
            textarea.setAttribute('required', 'required');
            if (!textarea.value.trim()) {
                textarea.value = 'Out of stock / Production shortage';
            }
            btn.classList.replace('bg-sky-600', 'bg-rose-600');
            btn.classList.replace('hover:bg-sky-500', 'hover:bg-rose-500');
            btn.classList.replace('shadow-sky-100', 'shadow-rose-100');
            btnText.innerText = 'Confirm Order Cancellation';
        } else {
            box.classList.add('hidden');
            textarea.removeAttribute('required');
            btn.classList.replace('bg-rose-600', 'bg-sky-600');
            btn.classList.replace('hover:bg-rose-500', 'hover:bg-sky-500');
            btn.classList.replace('shadow-rose-100', 'shadow-sky-100');
            btnText.innerText = 'Apply Pipeline Update';
        }
    }

    function setAdminReason(reason) {
        const textarea = document.getElementById('admin_cancellation_reason');
        if (textarea) {
            textarea.value = reason;
            textarea.focus();
        }
    }

    function confirmRestrictCustomer(form, customerName, defaultReason) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `Restrict ${customerName}?`,
                text: 'This will temporarily suspend this customer from placing orders for three (3) days.',
                input: 'textarea',
                inputValue: defaultReason || '',
                inputLabel: 'Reason for Restriction',
                inputPlaceholder: 'State reason for 3-day restriction...',
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'A reason is required to restrict the customer.';
                    }
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Restrict (3 Days)',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
                    cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const reasonInput = form.querySelector('input[name="reason"]');
                    if (reasonInput) {
                        reasonInput.value = result.value.trim();
                    }
                    form.submit();
                }
            });
            return false;
        }
        return confirm(`Restrict ${customerName} from ordering for three (3) days?`);
    }

    function confirmLiftRestriction(form, customerName) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `Lift Restriction for ${customerName}?`,
                text: 'This will restore their ability to place orders immediately.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Lift Restriction',
                cancelButtonText: 'Keep Restricted',
                confirmButtonColor: '#0ea5e9',
                cancelButtonColor: '#64748b',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
                    cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }
        return confirm(`Restore ordering privileges for ${customerName}?`);
    }
</script>
@endsection
