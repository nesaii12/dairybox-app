@extends('layouts.customer')

@section('title', 'Track Order ' . $order->order_number)

@section('content')
<div class="mb-6">
    <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors font-semibold">
        <i class="bi bi-arrow-left mr-1"></i> Back to Orders List
    </a>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-2">
        <div>
            <h2 class="text-2xl font-black text-slate-900 font-serif">Track Order #{{ $order->order_number }}</h2>
            <p class="text-xs text-slate-500">Placed on {{ $order->created_at->format('M d, Y h:i A') }}</p>
        </div>
        
        <div class="flex items-center gap-2 flex-wrap">
            <x-chat-button
                type="order"
                :id="$order->id"
                :label="'Order #' . $order->order_number"
                :unread="$order->unreadChatCountFor('customer')"
                size="md">
                Chat with Rider / Admin
            </x-chat-button>
            @if($order->order_status === 'Pending')
                <button type="button" onclick="openCustomerCancellationModal()" class="inline-flex items-center justify-center space-x-1.5 py-2 px-4 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100 transition-all cursor-pointer">
                    <i class="bi bi-trash"></i>
                    <span>Cancel Order</span>
                </button>
            @endif
        </div>
    </div>
</div>

<!-- Progress Stepper (Visual Progress Indicator) -->
<div id="order-{{ $order->id }}" data-record-id="order-{{ $order->id }}" data-record-number="{{ $order->order_number }}" class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm mb-8 record-highlight-target">
    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-6">Delivery Progress</h3>
    
    @php
        $statuses = ['Pending', 'Confirmed', 'Preparing'];
        if ($order->order_type === 'Delivery') {
            $statuses[] = 'Out for Delivery';
        } else {
            $statuses[] = 'Ready for Pickup';
        }
        $statuses[] = 'Delivered';

        $isDelivered = in_array($order->order_status, ['Delivered', 'Delivered - Pending Remittance', 'Paid & Remitted']);
        $effectiveStatus = $isDelivered ? 'Delivered' : $order->order_status;
        $currentIdx = array_search($effectiveStatus, $statuses);
        $isCancelled = in_array($order->order_status, ['Cancelled', 'Cancelled by Customer']) || $order->isCancelled();
    @endphp

    @if($isCancelled)
        <div class="p-4 bg-rose-50 border border-rose-200/80 rounded-2xl flex items-start space-x-3.5">
            <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                <i class="bi bi-x-circle-fill text-base"></i>
            </div>
            <div class="flex-1">
                <span class="block text-xs font-bold text-rose-800">This order was cancelled</span>
                <p class="text-[11px] text-rose-700 mt-0.5">Stock has been returned to warehouse inventory.</p>
                @if($order->cancellation_reason)
                    <div class="mt-2 pt-2 border-t border-rose-200/60 flex items-start space-x-1.5 text-xs text-rose-900">
                        <span class="font-bold">Reason:</span>
                        <span class="italic font-medium">"{{ $order->cancellation_reason }}"</span>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 md:gap-0 relative">
            @foreach($statuses as $index => $status)
                @php
                    $isPassed = ($currentIdx !== false && $currentIdx >= $index);
                    $isCurrent = ($effectiveStatus === $status);
                @endphp
                <div class="flex items-center md:flex-col md:text-center flex-1 relative z-10 w-full">
                    <!-- Step Indicator Bubble (Squircle shape) -->
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold transition-all border-2 {{ $isCurrent ? 'bg-sky-500 text-white border-sky-500 scale-110 shadow-md shadow-sky-100/50' : ($isPassed ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-slate-50 text-slate-400 border-slate-200') }}">
                        @if($isPassed && !$isCurrent)
                            <i class="bi bi-check-lg text-sm"></i>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </div>
                    
                    <!-- Label -->
                    <div class="ml-4 md:ml-0 md:mt-2.5">
                        <span class="block text-xs font-bold {{ $isCurrent ? 'text-sky-600' : ($isPassed ? 'text-slate-800' : 'text-slate-400') }}">{{ $status }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Items & Billing Details (Left columns) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 pb-3 border-b border-slate-100 mb-4">Items Summary</h3>
            
            <div class="divide-y divide-slate-100">
                @foreach($order->orderItems as $item)
                <div class="py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                            @if($item->product->image)
                                <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->product_name }}" class="h-full w-full object-cover">
                            @else
                                <i class="bi bi-image text-slate-300"></i>
                            @endif
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-slate-900">{{ $item->product->product_name }}</span>
                            <span class="block text-[10px] text-slate-500 mt-0.5">
                                Qty: <span class="font-bold text-slate-700">{{ $item->quantity }}</span> &bull; Size: <span class="font-bold text-slate-700">{{ $item->product->size }}</span> &bull; ₱{{ number_format($item->price, 2) }}
                                @if($item->product->manufacturing_date)
                                    &bull; <span class="text-slate-600 font-medium">Mfg: {{ \Carbon\Carbon::parse($item->product->manufacturing_date)->format('M d, Y') }}</span>
                                @endif
                                @if($item->product->expiration_date)
                                    &bull; <span class="text-slate-600 font-medium">Exp: {{ \Carbon\Carbon::parse($item->product->expiration_date)->format('M d, Y') }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    <span class="text-sm font-black text-slate-900">₱{{ number_format($item->subtotal, 2) }}</span>
                </div>
                @endforeach
            </div>

            <!-- Totals block -->
            <div class="border-t border-slate-100 pt-4 mt-4 space-y-2.5 text-slate-600 text-xs">
                <div class="flex items-center justify-between">
                    <span>Items Subtotal</span>
                    <span class="font-semibold text-slate-900">₱{{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Delivery Fee</span>
                    @if($order->order_type === 'Delivery' && (float) $order->delivery_fee == 0)
                        <span class="font-bold text-emerald-600">Free Delivery</span>
                    @else
                        <span class="font-semibold text-slate-900">₱{{ number_format($order->delivery_fee, 2) }}</span>
                    @endif
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
                    <span class="font-bold text-slate-900">Total Charged</span>
                    <span class="font-black text-sky-600 text-base">₱{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Rating & Review Trigger (for completed/fulfilled orders) -->
        @if(in_array($order->order_status, ['Delivered', 'Ready for Pickup', 'Delivered - Pending Remittance', 'Paid & Remitted']))
            @php
                $satisfactionLabels = [
                    5 => ['label' => 'Very Satisfied', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                    4 => ['label' => 'Satisfied', 'class' => 'bg-sky-50 text-sky-700 border-sky-200'],
                    3 => ['label' => 'Neutral', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                    2 => ['label' => 'Dissatisfied', 'class' => 'bg-orange-50 text-orange-700 border-orange-200'],
                    1 => ['label' => 'Very Dissatisfied', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
                ];
            @endphp
            <div id="review-products" class="bg-sky-50/50 border border-sky-100 p-5 rounded-2xl">
                <h3 class="text-sm font-bold text-sky-950 flex items-center space-x-2">
                    <i class="bi bi-star-fill text-amber-400"></i>
                    <span>Rate & Give Feedback on Purchased Products</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-1">Rate your satisfaction (1 to 5 stars) and share feedback on the products from this order.</p>
                
                <div class="space-y-4 mt-4">
                    @foreach($order->orderItems as $item)
                        @php
                            $existingRating = \App\Models\Rating::where('customer_id', auth()->id())
                                ->where('product_id', $item->product_id)
                                ->first();
                            $currentScore = $existingRating->rating ?? 5;
                            $satBadge = $satisfactionLabels[$currentScore] ?? $satisfactionLabels[5];
                        @endphp

                        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-2xs">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                                        @if($item->product->image)
                                            <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->product_name }}" class="h-full w-full object-cover">
                                        @else
                                            <i class="bi bi-image text-slate-300"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('customer.product', $item->product_id) }}#reviews-section" class="text-xs font-bold text-slate-900 hover:text-sky-600 transition-colors">
                                            {{ $item->product->product_name }}
                                        </a>
                                        <span class="block text-[10px] text-slate-400">
                                            {{ $item->product->milk_type }} Milk &bull; Size: {{ $item->product->size }}
                                        </span>
                                    </div>
                                </div>

                                @if($existingRating)
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full text-[10px] flex items-center">
                                            <i class="bi bi-check-circle-fill mr-1 text-emerald-600"></i> Reviewed
                                        </span>
                                        <button type="button" onclick="toggleEditOrderReview({{ $item->id }})" class="text-[11px] font-bold text-sky-600 hover:text-sky-800 cursor-pointer">
                                            Edit
                                        </button>
                                    </div>
                                @else
                                    <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full font-bold">
                                        Awaiting Feedback
                                    </span>
                                @endif
                            </div>

                            @if($existingRating)
                                <div id="review-summary-{{ $item->id }}" class="bg-slate-50/80 border border-slate-100 rounded-xl p-3 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            <div class="flex text-amber-400 text-xs">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="bi {{ $i <= $existingRating->rating ? 'bi-star-fill' : 'bi-star text-slate-300' }}"></i>
                                                @endfor
                                            </div>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $satBadge['class'] }}">
                                                {{ $satBadge['label'] }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-slate-400">{{ $existingRating->updated_at->format('M d, Y') }}</span>
                                    </div>
                                    @if($existingRating->review)
                                        <p class="text-xs text-slate-600 italic">"{{ $existingRating->review }}"</p>
                                    @else
                                        <p class="text-[11px] text-slate-400 italic">No written comment provided.</p>
                                    @endif
                                </div>
                            @endif

                            <form id="review-form-{{ $item->id }}" action="{{ route('customer.ratings.store', $item->product_id) }}" method="POST" class="space-y-3 {{ $existingRating ? 'hidden mt-3 pt-3 border-t border-slate-100' : '' }}">
                                @csrf
                                <!-- Star & Satisfaction Selector -->
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center space-x-1.5 text-lg star-group" data-item-id="{{ $item->id }}">
                                        @for($s = 1; $s <= 5; $s++)
                                            <label class="cursor-pointer transition-transform hover:scale-110">
                                                <input type="radio" name="rating" value="{{ $s }}" {{ (int) $currentScore === $s ? 'checked' : '' }} required class="sr-only">
                                                <i class="bi {{ $s <= $currentScore ? 'bi-star-fill text-amber-400' : 'bi-star text-slate-300' }}" onclick="highlightStars(this, {{ $s }}, {{ $item->id }})"></i>
                                            </label>
                                        @endfor
                                    </div>
                                    <span id="satisfaction-label-{{ $item->id }}" class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border bg-amber-50 text-amber-800 border-amber-200">
                                        {{ $satBadge['label'] }}
                                    </span>
                                </div>

                                <!-- Quick Satisfaction Feedback Chips -->
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach(['Fresh & creamy', 'Great flavor', 'High quality dairy', 'Good packaging', 'Worth the price'] as $tag)
                                        <button type="button" onclick="appendOrderFeedbackTag({{ $item->id }}, '{{ $tag }}')" class="px-2 py-0.5 rounded-lg bg-slate-50 hover:bg-sky-50 text-slate-600 hover:text-sky-700 border border-slate-200/80 text-[10px] font-semibold transition cursor-pointer">
                                            + {{ $tag }}
                                        </button>
                                    @endforeach
                                </div>

                                <textarea name="review" id="order-review-input-{{ $item->id }}" rows="2" maxlength="500" placeholder="Share your satisfaction feedback on taste, freshness, and quality..."
                                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 text-xs transition-all placeholder-slate-400 focus:bg-white resize-none">{{ $existingRating->review ?? '' }}</textarea>

                                <div class="flex items-center gap-2">
                                    <button type="submit" class="py-2 px-4 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition-all shadow-sm shadow-sky-100 cursor-pointer flex items-center gap-1.5">
                                        <i class="bi bi-send-check-fill"></i>
                                        <span>{{ $existingRating ? 'Update Feedback' : 'Submit Rating & Feedback' }}</span>
                                    </button>
                                    @if($existingRating)
                                        <button type="button" onclick="toggleEditOrderReview({{ $item->id }})" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition cursor-pointer">
                                            Cancel
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

        <!-- Payment Details Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 pb-3 border-b border-slate-100 mb-4 flex items-center justify-between">
                <span>Payment Information</span>
                @if($order->isPaid())
                    <span class="inline-flex items-center text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                        <i class="bi bi-check-circle-fill mr-1 text-emerald-600"></i> Paid
                    </span>
                @elseif($order->isOnlinePayment())
                    <span class="inline-flex items-center text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                        <i class="bi bi-clock-fill mr-1 text-amber-600"></i> Pending Payment
                    </span>
                @else
                    <span class="inline-flex items-center text-[10px] font-bold text-slate-600 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-full">
                        Cash
                    </span>
                @endif
            </h3>
            
            <div class="space-y-3.5 text-xs text-slate-600">
                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Payment Method</span>
                    <span class="font-bold text-slate-900">
                        @if($order->isOnlinePayment())
                            Online Payment (Xendit)
                        @else
                            Cash on Delivery / Pickup
                        @endif
                    </span>
                </div>

                @if($order->payment_channel)
                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Payment Channel</span>
                    <span class="font-semibold text-slate-800 uppercase px-2 py-0.5 bg-slate-100 rounded text-[11px]">{{ $order->payment_channel }}</span>
                </div>
                @endif

                @if($order->paid_at)
                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Paid At</span>
                    <span class="font-medium text-slate-700">{{ $order->paid_at->format('M d, Y h:i A') }}</span>
                </div>
                @endif

                @if($order->isOnlinePayment() && !$order->isPaid() && $order->order_status !== 'Cancelled')
                <div class="pt-2">
                    <a href="{{ route('customer.orders.payment.retry', $order->id) }}" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-sky-500 hover:bg-sky-600 shadow-sm shadow-sky-500/20 active:scale-98 transition-all">
                        <i class="bi bi-credit-card"></i>
                        <span>Pay with Xendit Now</span>
                    </a>
                    <p class="text-[10px] text-slate-400 text-center mt-1.5">You will be redirected to the secure Xendit payment portal.</p>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 pb-3 border-b border-slate-100 mb-4">Fulfillment Details</h3>
            
            <div class="space-y-4 text-slate-600 text-xs">
                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Type</span>
                    <span class="text-sm font-bold text-slate-900">{{ $order->order_type }}</span>
                </div>

                @if($order->order_type === 'Delivery')
                    <div>
                        <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Address</span>
                        <span class="text-xs font-semibold text-slate-800">{{ $order->delivery_address }}</span>
                    </div>
                @endif

                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Scheduled Date & Time</span>
                    <span class="text-xs font-semibold text-slate-800 leading-relaxed">
                        {{ \Carbon\Carbon::parse($order->schedule_date)->format('l, M d, Y') }}
                        @if($order->schedule_time)
                            &bull; {{ \Carbon\Carbon::parse($order->schedule_time)->format('h:i A') }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        @if($order->rider)
            <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 pb-3 border-b border-slate-100 mb-4">Assigned Delivery Rider</h3>
                
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white flex items-center justify-center font-bold shadow-xs shadow-emerald-100/50 flex-shrink-0">
                        <i class="bi bi-bicycle text-lg"></i>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-slate-900">{{ $order->rider->name }}</span>
                        <span class="block text-[10px] text-slate-400 mt-0.5"><i class="bi bi-telephone text-slate-300 mr-1"></i> <a href="tel:{{ $order->rider->phone }}" class="hover:underline select-all font-mono font-semibold">{{ $order->rider->phone }}</a></span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Customer Cancellation Reason Modal -->
<div id="customerCancellationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-all">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-2xl border border-slate-100 flex flex-col space-y-4">
        <!-- Header -->
        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 font-serif">Cancel Order #{{ $order->order_number }}</h3>
                <p class="text-xs text-slate-500 mt-0.5">Please let us know why you need to cancel this order</p>
            </div>
            <button type="button" onclick="closeCustomerCancellationModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <form action="{{ route('customer.orders.cancel', $order->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Select Reason (Quick Apply)</label>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" onclick="setCustomerReason('Changed my mind')" class="text-xs px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-rose-50 hover:text-rose-700 text-slate-700 border border-slate-200/80 font-medium transition cursor-pointer">
                        Changed my mind
                    </button>
                    <button type="button" onclick="setCustomerReason('Ordered wrong item or quantity')" class="text-xs px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-rose-50 hover:text-rose-700 text-slate-700 border border-slate-200/80 font-medium transition cursor-pointer">
                        Ordered wrong item / qty
                    </button>
                    <button type="button" onclick="setCustomerReason('Delivery schedule is too late')" class="text-xs px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-rose-50 hover:text-rose-700 text-slate-700 border border-slate-200/80 font-medium transition cursor-pointer">
                        Schedule too late
                    </button>
                    <button type="button" onclick="setCustomerReason('Need to change delivery address')" class="text-xs px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-rose-50 hover:text-rose-700 text-slate-700 border border-slate-200/80 font-medium transition cursor-pointer">
                        Change delivery address
                    </button>
                    <button type="button" onclick="setCustomerReason('Found alternative product')" class="text-xs px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-rose-50 hover:text-rose-700 text-slate-700 border border-slate-200/80 font-medium transition cursor-pointer">
                        Found alternative
                    </button>
                </div>
            </div>

            <div>
                <label for="customer_cancellation_reason" class="block text-xs font-semibold text-slate-700 mb-1">
                    Cancellation Reason <span class="text-rose-500">*</span>
                </label>
                <textarea name="cancellation_reason" id="customer_cancellation_reason" rows="3" required
                    class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/10 focus:border-rose-400 transition-all focus:bg-white resize-none font-medium placeholder-slate-400"
                    placeholder="Describe why you want to cancel this order..."></textarea>
            </div>

            <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl text-[11px] text-amber-800 flex items-start space-x-2">
                <i class="bi bi-info-circle-fill text-amber-500 mt-0.5"></i>
                <span>Your reserved items will be returned to stock inventory immediately.</span>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row gap-2">
                <button type="submit"
                    class="flex-1 flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 shadow-md shadow-rose-100 active:scale-98 transition-all cursor-pointer">
                    <i class="bi bi-x-circle"></i>
                    <span>Confirm Order Cancellation</span>
                </button>
                <button type="button" onclick="closeCustomerCancellationModal()"
                    class="py-2.5 px-4 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 rounded-xl transition cursor-pointer">
                    Keep Order
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function setCustomerReason(reason) {
        const textarea = document.getElementById('customer_cancellation_reason');
        textarea.value = reason;
        textarea.focus();
    }

    function openCustomerCancellationModal() {
        const modal = document.getElementById('customerCancellationModal');
        const textarea = document.getElementById('customer_cancellation_reason');
        modal.classList.remove('hidden');
        if (!textarea.value.trim()) {
            textarea.value = 'Changed my mind';
        }
    }

    function closeCustomerCancellationModal() {
        document.getElementById('customerCancellationModal').classList.add('hidden');
    }

    const orderSatisfactionMap = {
        1: 'Very Dissatisfied',
        2: 'Dissatisfied',
        3: 'Neutral',
        4: 'Satisfied',
        5: 'Very Satisfied'
    };

    function highlightStars(starElement, count, itemId) {
        const wrapper = starElement.closest('.star-group') || starElement.closest('.flex');
        const stars = wrapper.querySelectorAll('i');
        const radios = wrapper.querySelectorAll('input[type="radio"]');

        radios.forEach((radio, idx) => {
            radio.checked = (idx + 1) === count;
        });

        stars.forEach((star, index) => {
            if (index < count) {
                star.classList.remove('bi-star', 'text-slate-300');
                star.classList.add('bi-star-fill', 'text-amber-400');
            } else {
                star.classList.remove('bi-star-fill', 'text-amber-400');
                star.classList.add('bi-star', 'text-slate-300');
            }
        });

        if (itemId) {
            const labelEl = document.getElementById('satisfaction-label-' + itemId);
            if (labelEl && orderSatisfactionMap[count]) {
                labelEl.textContent = orderSatisfactionMap[count];
            }
        }
    }

    function appendOrderFeedbackTag(itemId, tagText) {
        const input = document.getElementById('order-review-input-' + itemId);
        if (!input) return;
        const current = input.value.trim();
        if (!current) {
            input.value = tagText + '. ';
        } else if (!current.toLowerCase().includes(tagText.toLowerCase())) {
            input.value = current.replace(/\.*\s*$/, '. ') + tagText + '. ';
        }
        input.focus();
    }

    function toggleEditOrderReview(itemId) {
        const form = document.getElementById('review-form-' + itemId);
        if (form) {
            form.classList.toggle('hidden');
        }
    }
</script>
@endsection
