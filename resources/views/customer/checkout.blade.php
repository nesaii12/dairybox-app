@extends('layouts.customer')

@section('title', 'Order Summary & Checkout')

@section('content')
@php
    $batchItemCount = $items->count();
    $batchUnitCount = $items->sum('quantity');
@endphp

<div class="mb-6">
    <a href="{{ route('customer.cart.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
        <i class="bi bi-arrow-left mr-1"></i> Back to Shopping Cart
    </a>
    <div class="flex flex-wrap items-center gap-3 mt-2">
        <h2 class="text-2xl font-black text-slate-900">Order Summary &amp; Checkout</h2>
        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold">
            {{ $batchItemCount }} {{ \Illuminate\Support\Str::plural('item', $batchItemCount) }} &bull; {{ $batchUnitCount }} {{ \Illuminate\Support\Str::plural('unit', $batchUnitCount) }}
        </span>
    </div>
    <p class="text-xs text-slate-500 mt-1">Review your selected products and complete your shipping and payment details.</p>
</div>

<form action="{{ route('customer.checkout.placeOrder') }}" method="POST" id="checkout-form">
    @csrf
    @foreach($items as $item)
        <input type="hidden" name="item_ids[]" value="{{ $item->id }}">
    @endforeach
    @if(isset($selectedBatch))
        <input type="hidden" name="batch_id" value="{{ $selectedBatch->id }}">
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Left Columns (lg:col-span-2): Customer, Shipping, Schedule & Payment Details -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Customer Information Card -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h3 class="text-sm font-bold text-slate-950 mb-4 flex items-center space-x-2">
                    <i class="bi bi-person-badge text-sky-600"></i>
                    <span>Customer Information</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Customer Name</span>
                        <span class="font-bold text-slate-900 mt-0.5 block truncate">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Email Address</span>
                        <span class="font-bold text-slate-900 mt-0.5 block truncate">{{ auth()->user()->email }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Contact Number</span>
                        <span class="font-bold text-slate-900 mt-0.5 block truncate">{{ auth()->user()->phone ?: 'Not provided' }}</span>
                    </div>
                </div>
            </div>
            
            <!-- 1. Order Type Selection -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h3 class="text-sm font-bold text-slate-950 mb-4 flex items-center space-x-2">
                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px] font-bold">1</span>
                    <span>Select Fulfillment Mode</span>
                </h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <label id="delivery-card" class="relative border-2 border-sky-500 bg-sky-50/30 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition-all select-none">
                        <input type="radio" name="order_type" value="Delivery" checked onchange="toggleFulfillmentMode()" class="absolute right-4 top-4 h-4 w-4 text-sky-500 border-slate-300 focus:ring-sky-500">
                        <i class="bi bi-truck text-3xl text-sky-600 mb-2"></i>
                        <span class="text-sm font-black text-slate-900">Home Delivery</span>
                        @if($deliveryFee == 0)
                            <span class="text-[10px] text-emerald-600 font-bold mt-1">Free Delivery (₱1,000+ Order)</span>
                        @else
                            <span class="text-[10px] text-slate-500 font-semibold mt-1">₱{{ number_format($deliveryFee, 2) }} Delivery Fee</span>
                        @endif
                    </label>

                    <label id="pickup-card" class="relative border border-slate-200 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50/50 transition-all select-none">
                        <input type="radio" name="order_type" value="Pickup" onchange="toggleFulfillmentMode()" class="absolute right-4 top-4 h-4 w-4 text-sky-500 border-slate-300 focus:ring-sky-500">
                        <i class="bi bi-shop text-3xl text-slate-400 mb-2"></i>
                        <span class="text-sm font-bold text-slate-700">Store Pickup</span>
                        <span class="text-[10px] text-slate-400 mt-1">Free of charge</span>
                    </label>
                </div>
            </div>

            <!-- 2. Address Details (Toggled dynamically) -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6" id="address-section">
                <h3 class="text-sm font-bold text-slate-950 mb-4 flex items-center space-x-2">
                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px] font-bold">2</span>
                    <span>Shipping / Delivery Address</span>
                </h3>
                
                <div>
                    <label for="delivery_address" class="block text-xs font-semibold text-slate-700">Detailed Shipping Address</label>
                    <input type="text" name="delivery_address" id="delivery_address" required value="{{ old('delivery_address') }}"
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                        placeholder="House No, Barangay, Street Name, City">
                    @error('delivery_address')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- 3. Scheduling Date & Time -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h3 class="text-sm font-bold text-slate-950 mb-4 flex items-center space-x-2">
                    <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px] font-bold">3</span>
                    <span>Schedule Date &amp; Time</span>
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="schedule_date" class="block text-xs font-semibold text-slate-700">Select Date</label>
                        <input type="date" name="schedule_date" id="schedule_date" required min="{{ \Carbon\Carbon::now('Asia/Manila')->format('Y-m-d') }}" value="{{ old('schedule_date', \Carbon\Carbon::now('Asia/Manila')->format('Y-m-d')) }}"
                            class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                        @error('schedule_date')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="schedule_time" class="block text-xs font-semibold text-slate-700">Select Time Slot</label>
                        <select name="schedule_time" id="schedule_time" required
                            class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                            <option value="08:00:00" id="slot-morning" {{ old('schedule_time') == '08:00:00' ? 'selected' : '' }}>Morning (08:00 AM - 12:00 PM)</option>
                            <option value="13:00:00" id="slot-afternoon" {{ old('schedule_time') == '13:00:00' ? 'selected' : '' }}>Afternoon (01:00 PM - 05:00 PM)</option>
                        </select>
                        @error('schedule_time')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Dynamic Notice / Warning Container -->
                <div id="schedule_notice" class="mt-3 hidden"></div>
            </div>

            <!-- 4. Payment Method Selection -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-950 flex items-center space-x-2">
                        <span class="w-5 h-5 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px] font-bold">4</span>
                        <span>Select Payment Method</span>
                    </h3>
                    <span class="inline-flex items-center text-[10px] font-bold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full">
                        <i class="bi bi-shield-check text-emerald-600 mr-1"></i> Secured Checkout
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Primary Option: Cash on Delivery (Default Selected) -->
                    <label id="payment-cod-card" class="relative border-2 border-sky-500 bg-sky-50/30 rounded-2xl p-5 flex flex-col justify-between cursor-pointer transition-all select-none">
                        <input type="radio" name="payment_method" value="cod" checked class="absolute right-4 top-4 h-4 w-4 text-sky-600 border-slate-300 focus:ring-sky-500 accent-sky-600">
                        
                        <div>
                            <div class="flex items-center space-x-2.5 mb-2">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-500 to-sky-600 text-white flex items-center justify-center text-base shadow-sm">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <div>
                                    <span class="text-sm font-black text-slate-900 block leading-tight">Cash on Delivery</span>
                                    <span class="text-[9px] font-bold text-sky-600 uppercase tracking-wider" id="cod-label-subtitle">Pay upon Delivery</span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed mt-2.5" id="cod-desc">
                                Prepare exact cash payment upon receiving your order from the dispatch rider.
                            </p>
                        </div>

                        <div class="flex items-center space-x-2 mt-4 pt-3 border-t border-sky-100/70 text-[10px] text-sky-800 font-semibold">
                            <i class="bi bi-check-circle-fill text-sky-600"></i>
                            <span>Default checkout payment method</span>
                        </div>
                    </label>

                    <!-- Secondary Info Card: Online Payment via GCash QR (Live Chat Redirect) -->
                    <div class="relative border border-slate-200/90 bg-white rounded-2xl p-5 flex flex-col justify-between shadow-2xs hover:border-sky-300 transition-all">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 text-white flex items-center justify-center text-base shadow-sm">
                                        <i class="bi bi-qr-code-scan"></i>
                                    </div>
                                    <div>
                                        <span class="text-sm font-black text-slate-900 block leading-tight">Online Payment via GCash QR</span>
                                        <span class="text-[9px] font-bold text-sky-600 uppercase tracking-wider">Live Chat Assistance</span>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200 text-[10px] font-bold">
                                    Info
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed mt-2.5">
                                Want to pay via GCash QR? Place your order as Cash on Delivery first, or contact our Admin in Live Chat to request the GCash QR code and verify your payment.
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100">
                            <button type="button"
                                    id="btn-contact-admin-chat"
                                    onclick="contactAdminForGcashQR()"
                                    class="w-full py-2.5 px-3.5 rounded-xl bg-sky-50 hover:bg-sky-100 active:scale-[0.98] text-sky-700 hover:text-sky-800 border border-sky-200 font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-2xs cursor-pointer">
                                <i class="bi bi-chat-dots-fill text-sky-600"></i>
                                <span>Contact Admin via Live Chat</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column (lg:col-span-1): Exclusive Batch Order Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 sticky top-24">
                <!-- Cart Order Summary Header -->
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-950">Order Summary</h3>
                        <p class="text-[10px] text-slate-400 font-medium">{{ $batchItemCount }} {{ \Illuminate\Support\Str::plural('item', $batchItemCount) }} selected</p>
                    </div>
                </div>

                <!-- Customer & Shipping Summary Snapshot -->
                <div class="mb-4 p-3 rounded-xl bg-slate-50 border border-slate-100 text-[11px] space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider">Customer</span>
                        <span class="font-bold text-slate-800 truncate max-w-[170px]">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider">Fulfillment</span>
                        <span id="summary-fulfillment-mode" class="font-bold text-sky-700">Home Delivery</span>
                    </div>
                    <div class="flex items-start justify-between gap-2" id="summary-address-row">
                        <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider flex-shrink-0">Ship To</span>
                        <span id="summary-address-preview" class="font-medium text-slate-600 text-right break-words">Enter address on the left</span>
                    </div>
                </div>
                
                <!-- Itemized List of Products in This Cart -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cart Items ({{ $batchItemCount }})</span>
                        <span class="text-[10px] font-bold text-slate-600">{{ $batchUnitCount }} {{ \Illuminate\Support\Str::plural('unit', $batchUnitCount) }} total</span>
                    </div>

                    <div class="max-h-60 overflow-y-auto pr-1 divide-y divide-slate-100 border border-slate-100 rounded-xl px-3">
                        @foreach($items as $item)
                            <div class="py-2.5 flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                                        @if($item->product->image)
                                            <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->product_name }}" class="h-full w-full object-cover">
                                        @else
                                            <i class="bi bi-image text-slate-300 text-sm"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate">{{ $item->product->product_name }}</span>
                                        <span class="block text-[10px] text-slate-500 font-medium truncate">
                                            ₱{{ number_format($item->unit_price, 2) }} &times; <strong class="text-slate-800">{{ $item->quantity }}</strong>
                                        </span>
                                    </div>
                                </div>
                                <span class="font-bold text-slate-900 flex-shrink-0">₱{{ number_format($item->subtotal, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Price Breakdown (Subtotal, Shipping, Taxes, Total Units) -->
                <div class="space-y-2.5 mb-5 border-t border-slate-100 pt-4 text-xs">
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Total Unit Count</span>
                        <span class="font-bold text-slate-800">{{ $batchUnitCount }} {{ \Illuminate\Support\Str::plural('unit', $batchUnitCount) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Cart Subtotal</span>
                        <span class="font-semibold text-slate-900">₱{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-600">
                        <span>Shipping / Delivery Fee</span>
                        <span class="font-semibold {{ $deliveryFee == 0 ? 'text-emerald-600 font-bold' : 'text-slate-900' }}" id="delivery-fee-display">
                            {{ $deliveryFee == 0 ? 'Free Delivery' : '₱' . number_format($deliveryFee, 2) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Taxes (VAT)</span>
                        <span class="font-medium text-slate-700">₱0.00 (Tax Inclusive)</span>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900">Total Amount</span>
                        <span class="text-xl font-black text-sky-600" id="total-amount-display">₱{{ number_format($subtotal + $deliveryFee, 2) }}</span>
                    </div>
                </div>

                <!-- Cash on Delivery Payment Badge / Notice Box -->
                <div id="payment-method-indicator" class="mb-6 p-3.5 bg-slate-50 border border-slate-200/90 rounded-2xl text-[11px] text-slate-700 space-y-1.5">
                    <div class="flex items-center justify-between font-bold text-slate-900">
                        <span class="flex items-center"><i class="bi bi-cash-stack text-sky-600 mr-1.5"></i> Payment Method</span>
                        <span id="gateway-tag" class="text-[9px] uppercase px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-700 font-bold">Cash on Delivery</span>
                    </div>
                    <p class="text-[10px] text-slate-500 leading-relaxed" id="payment-hint-text">
                        Payment Method: Cash on Delivery — Pay upon delivery or coordinate GCash QR with Admin via Live Chat.
                    </p>
                </div>

                <button type="submit" id="submit-order-btn" class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-sm font-black text-white bg-sky-500 hover:bg-sky-600 shadow-md shadow-sky-500/20 transition-all cursor-pointer">
                    <i class="bi bi-check2-circle text-base" id="submit-btn-icon"></i>
                    <span id="submit-btn-text">Place Order (COD) — ₱{{ number_format($subtotal + $deliveryFee, 2) }}</span>
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    const subtotal = {{ $subtotal }};
    const calculatedDeliveryFee = {{ $deliveryFee }};
    const deliveryFeeDisplay = document.getElementById('delivery-fee-display');
    const totalAmountDisplay = document.getElementById('total-amount-display');
    const addressSection = document.getElementById('address-section');
    const addressInput = document.getElementById('delivery_address');
    const deliveryCard = document.getElementById('delivery-card');
    const pickupCard = document.getElementById('pickup-card');
    const paymentCodCard = document.getElementById('payment-cod-card');
    const paymentHintText = document.getElementById('payment-hint-text');
    const gatewayTag = document.getElementById('gateway-tag');
    const submitBtnText = document.getElementById('submit-btn-text');
    const submitBtnIcon = document.getElementById('submit-btn-icon');
    const codLabelSubtitle = document.getElementById('cod-label-subtitle');
    const codDesc = document.getElementById('cod-desc');
    const summaryFulfillmentMode = document.getElementById('summary-fulfillment-mode');
    const summaryAddressRow = document.getElementById('summary-address-row');
    const summaryAddressPreview = document.getElementById('summary-address-preview');

    function syncAddressPreview() {
        if (!summaryAddressPreview || !addressInput) return;
        const val = addressInput.value.trim();
        summaryAddressPreview.textContent = val ? val : 'Enter address on the left';
    }

    if (addressInput) {
        addressInput.addEventListener('input', syncAddressPreview);
    }

    function toggleFulfillmentMode() {
        const orderTypeInput = document.querySelector('input[name="order_type"]:checked').value;

        if (orderTypeInput === 'Pickup') {
            addressSection.style.display = 'none';
            addressInput.removeAttribute('required');
            
            deliveryFeeDisplay.textContent = '₱0.00';
            deliveryFeeDisplay.className = 'font-semibold text-slate-900';
            const totalText = '₱' + formatCurrency(subtotal);
            totalAmountDisplay.textContent = totalText;

            if (summaryFulfillmentMode) summaryFulfillmentMode.textContent = 'Store Pickup';
            if (summaryAddressRow) summaryAddressRow.style.display = 'none';

            // Active Pickup Card styling
            pickupCard.className = "relative border-2 border-sky-500 bg-sky-50/30 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition-all select-none";
            pickupCard.querySelector('i').className = "bi bi-shop text-3xl text-sky-600 mb-2";
            pickupCard.querySelector('span.text-sm').className = "text-sm font-black text-slate-900";
            
            // Inactive Delivery Card styling
            deliveryCard.className = "relative border border-slate-200 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50/50 transition-all select-none";
            deliveryCard.querySelector('i').className = "bi bi-truck text-3xl text-slate-400 mb-2";
            deliveryCard.querySelector('span.text-sm').className = "text-sm font-bold text-slate-700";

            if (codLabelSubtitle) codLabelSubtitle.textContent = "Pay upon Store Pickup";
            if (codDesc) codDesc.textContent = "Pay cash directly at the store counter upon picking up your order.";
            if (paymentHintText) paymentHintText.textContent = "Payment Method: Cash on Delivery — Pay upon store pickup or coordinate GCash QR with Admin via Live Chat.";
            if (submitBtnText) submitBtnText.textContent = `Place Order (COD) — ${totalText}`;
        } else {
            addressSection.style.display = 'block';
            addressInput.setAttribute('required', 'required');
            
            if (calculatedDeliveryFee === 0) {
                deliveryFeeDisplay.textContent = 'Free Delivery';
                deliveryFeeDisplay.className = 'font-bold text-emerald-600';
            } else {
                deliveryFeeDisplay.textContent = '₱' + formatCurrency(calculatedDeliveryFee);
                deliveryFeeDisplay.className = 'font-semibold text-slate-900';
            }
            const totalText = '₱' + formatCurrency(subtotal + calculatedDeliveryFee);
            totalAmountDisplay.textContent = totalText;

            if (summaryFulfillmentMode) summaryFulfillmentMode.textContent = 'Home Delivery';
            if (summaryAddressRow) summaryAddressRow.style.display = 'flex';
            syncAddressPreview();

            // Active Delivery Card styling
            deliveryCard.className = "relative border-2 border-sky-500 bg-sky-50/30 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition-all select-none";
            deliveryCard.querySelector('i').className = "bi bi-truck text-3xl text-sky-600 mb-2";
            deliveryCard.querySelector('span.text-sm').className = "text-sm font-black text-slate-900";
            
            // Inactive Pickup Card styling
            pickupCard.className = "relative border border-slate-200 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50/50 transition-all select-none";
            pickupCard.querySelector('i').className = "bi bi-shop text-3xl text-slate-400 mb-2";
            pickupCard.querySelector('span.text-sm').className = "text-sm font-bold text-slate-700";

            if (codLabelSubtitle) codLabelSubtitle.textContent = "Pay upon Delivery";
            if (codDesc) codDesc.textContent = "Prepare exact cash payment upon receiving your order from the dispatch rider.";
            if (paymentHintText) paymentHintText.textContent = "Payment Method: Cash on Delivery — Pay upon delivery or coordinate GCash QR with Admin via Live Chat.";
            if (submitBtnText) submitBtnText.textContent = `Place Order (COD) — ${totalText}`;
        }
    }

    function contactAdminForGcashQR() {
        const prefillMessage = "Hi Admin, I would like to pay for my order via GCash QR.";
        if (typeof window.openLiveChat === 'function') {
            window.openLiveChat(prefillMessage);
        } else {
            const bubble = document.getElementById('floatingChatBubble');
            if (bubble) bubble.click();
            const input = document.getElementById('chatMessageInput');
            if (input) {
                input.value = prefillMessage;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                setTimeout(() => input.focus(), 200);
            }
        }
    }

    function formatCurrency(number) {
        return Number(number).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Schedule Time Slot Availability & Validation Logic
    function updateScheduleSlots() {
        const dateInput = document.getElementById('schedule_date');
        const timeSelect = document.getElementById('schedule_time');
        const morningOption = document.getElementById('slot-morning');
        const afternoonOption = document.getElementById('slot-afternoon');
        const noticeContainer = document.getElementById('schedule_notice');

        if (!dateInput || !timeSelect || !morningOption || !afternoonOption) return;

        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${day}`;

        // Ensure date input min is at least today
        if (!dateInput.min || dateInput.min < todayStr) {
            dateInput.min = todayStr;
        }

        const selectedDate = dateInput.value;
        const morningBaseText = "Morning (08:00 AM - 12:00 PM)";
        const afternoonBaseText = "Afternoon (01:00 PM - 05:00 PM)";

        if (selectedDate === todayStr) {
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            const currentMinutes = currentHour * 60 + currentMinute;

            const morningCutoff = 12 * 60; // 12:00 PM
            const afternoonCutoff = 17 * 60; // 05:00 PM (17:00)

            if (currentMinutes >= afternoonCutoff) {
                // All slots for today have closed
                morningOption.disabled = true;
                morningOption.textContent = `${morningBaseText} — Closed`;

                afternoonOption.disabled = true;
                afternoonOption.textContent = `${afternoonBaseText} — Closed`;

                timeSelect.value = "";

                // Calculate tomorrow's date for convenient 1-click action
                const tomorrow = new Date(now);
                tomorrow.setDate(now.getDate() + 1);
                const tYear = tomorrow.getFullYear();
                const tMonth = String(tomorrow.getMonth() + 1).padStart(2, '0');
                const tDay = String(tomorrow.getDate()).padStart(2, '0');
                const tomorrowStr = `${tYear}-${tMonth}-${tDay}`;
                const tomorrowFormatted = tomorrow.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                if (noticeContainer) {
                    noticeContainer.className = "mt-3 block";
                    noticeContainer.innerHTML = `
                        <div class="p-3 bg-amber-50 border border-amber-200/80 rounded-xl text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-xs">
                            <div class="flex items-start gap-2.5">
                                <i class="bi bi-clock-history text-amber-600 text-sm mt-0.5 shrink-0"></i>
                                <div>
                                    <p class="font-bold text-amber-950">All delivery time slots for today have ended.</p>
                                    <p class="text-amber-800 text-[11px] mt-0.5">Orders for today close at 5:00 PM. Please select tomorrow or a later date to schedule fulfillment.</p>
                                </div>
                            </div>
                            <button type="button" id="btn-pick-tomorrow" class="shrink-0 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-bold rounded-lg text-xs transition-all shadow-xs flex items-center justify-center gap-1.5 cursor-pointer">
                                <i class="bi bi-calendar-check text-xs"></i>
                                <span>Choose Tomorrow (${tomorrowFormatted})</span>
                            </button>
                        </div>
                    `;

                    const btnTomorrow = document.getElementById('btn-pick-tomorrow');
                    if (btnTomorrow) {
                        btnTomorrow.onclick = function() {
                            dateInput.value = tomorrowStr;
                            updateScheduleSlots();
                        };
                    }
                }
            } else if (currentMinutes >= morningCutoff) {
                // Morning slot has passed; Afternoon is open
                morningOption.disabled = true;
                morningOption.textContent = `${morningBaseText} — Unavailable (Passed)`;

                afternoonOption.disabled = false;
                afternoonOption.textContent = afternoonBaseText;

                // Auto-select the next valid available time slot (Afternoon)
                timeSelect.value = "13:00:00";

                if (noticeContainer) {
                    noticeContainer.className = "mt-3 block";
                    noticeContainer.innerHTML = `
                        <div class="p-2.5 bg-sky-50 border border-sky-100 rounded-xl text-xs text-sky-800 flex items-center gap-2">
                            <i class="bi bi-info-circle-fill text-sky-600 shrink-0"></i>
                            <span>The morning delivery window has passed for today. <strong>Afternoon (01:00 PM - 05:00 PM)</strong> has been auto-selected.</span>
                        </div>
                    `;
                }
            } else {
                // Both Morning and Afternoon slots are open
                morningOption.disabled = false;
                morningOption.textContent = morningBaseText;

                afternoonOption.disabled = false;
                afternoonOption.textContent = afternoonBaseText;

                if (!timeSelect.value || (timeSelect.selectedOptions[0] && timeSelect.selectedOptions[0].disabled)) {
                    timeSelect.value = "08:00:00";
                }

                if (noticeContainer) {
                    noticeContainer.className = "mt-3 hidden";
                    noticeContainer.innerHTML = "";
                }
            }
        } else {
            // Future date selected: all time slots available
            morningOption.disabled = false;
            morningOption.textContent = morningBaseText;

            afternoonOption.disabled = false;
            afternoonOption.textContent = afternoonBaseText;

            if (!timeSelect.value || (timeSelect.selectedOptions[0] && timeSelect.selectedOptions[0].disabled)) {
                timeSelect.value = "08:00:00";
            }

            if (noticeContainer) {
                noticeContainer.className = "mt-3 hidden";
                noticeContainer.innerHTML = "";
            }
        }
    }

    const scheduleDateInput = document.getElementById('schedule_date');
    const scheduleTimeSelect = document.getElementById('schedule_time');

    if (scheduleDateInput) {
        scheduleDateInput.addEventListener('change', updateScheduleSlots);
        scheduleDateInput.addEventListener('input', updateScheduleSlots);
    }

    if (scheduleTimeSelect) {
        scheduleTimeSelect.addEventListener('change', function() {
            const selectedOpt = scheduleTimeSelect.options[scheduleTimeSelect.selectedIndex];
            if (selectedOpt && selectedOpt.disabled) {
                updateScheduleSlots();
            }
        });
    }

    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const timeSelect = document.getElementById('schedule_time');
            const selectedOpt = timeSelect ? timeSelect.options[timeSelect.selectedIndex] : null;
            if (!timeSelect || !timeSelect.value || (selectedOpt && selectedOpt.disabled)) {
                e.preventDefault();
                alert('Please select an available delivery date and time slot.');
                if (scheduleDateInput) scheduleDateInput.focus();
                return false;
            }
        });
    }

    // Call on load
    document.addEventListener('DOMContentLoaded', () => {
        toggleFulfillmentMode();
        updateScheduleSlots();
    });
</script>
@endsection
