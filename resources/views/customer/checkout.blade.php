@extends('layouts.customer')

@section('title', 'Order Checkout')

@section('content')
<div class="mb-6">
    <a href="{{ route('customer.cart.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
        <i class="bi bi-arrow-left mr-1"></i> Back to Shopping Cart
    </a>
    <h2 class="text-2xl font-black text-slate-900 mt-2">Checkout Scheduling</h2>
    <p class="text-xs text-slate-500">Configure your order type, address, and select a preferred delivery/pickup slot.</p>
</div>

<form action="{{ route('customer.checkout.placeOrder') }}" method="POST" id="checkout-form">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Input Details (Left Columns) -->
        <div class="lg:col-span-2 space-y-6">
            
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
                        <span class="text-[10px] text-slate-500 font-semibold mt-1">₱50.00 Delivery Fee</span>
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
                    <span>Delivery Address</span>
                </h3>
                
                <div>
                    <label for="delivery_address" class="block text-xs font-semibold text-slate-700">Detailed Address</label>
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
                    <span>Schedule Date & Time</span>
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="schedule_date" class="block text-xs font-semibold text-slate-700">Select Date</label>
                        <input type="date" name="schedule_date" id="schedule_date" required min="{{ date('Y-m-d') }}" value="{{ old('schedule_date', date('Y-m-d')) }}"
                            class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                        @error('schedule_date')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="schedule_time" class="block text-xs font-semibold text-slate-700">Select Time Slot</label>
                        <select name="schedule_time" id="schedule_time" required
                            class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all">
                            <option value="08:00:00" {{ old('schedule_time') == '08:00:00' ? 'selected' : '' }}>Morning (08:00 AM - 12:00 PM)</option>
                            <option value="13:00:00" {{ old('schedule_time') == '13:00:00' ? 'selected' : '' }}>Afternoon (01:00 PM - 05:00 PM)</option>
                        </select>
                        @error('schedule_time')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout Order Summary (Right) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-24">
                <h3 class="text-base font-bold text-slate-950 mb-4 pb-3 border-b border-slate-50">Items Snapshot</h3>
                
                <!-- Items list -->
                <div class="max-h-36 overflow-y-auto mb-6 pr-1 divide-y divide-slate-50">
                    @foreach($items as $item)
                    <div class="py-2.5 flex items-center justify-between text-xs text-slate-600">
                        <div class="max-w-[150px] truncate">
                            <span class="font-bold text-slate-900">{{ $item->product->product_name }}</span>
                            <span class="block text-[10px] text-slate-400 font-medium">Qty: {{ $item->quantity }}</span>
                        </div>
                        <span class="font-semibold text-slate-800">₱{{ number_format($item->subtotal, 2) }}</span>
                    </div>
                    @endforeach
                </div>

                <div class="space-y-3 mb-6 border-t border-slate-50 pt-4">
                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <span>Subtotal</span>
                        <span class="font-semibold text-slate-900">₱{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <span>Delivery Fee</span>
                        <span class="font-semibold text-slate-900" id="delivery-fee-display">₱50.00</span>
                    </div>
                </div>

                <div class="border-t border-slate-50 pt-4 mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900">Total Amount</span>
                        <span class="text-xl font-black text-sky-600" id="total-amount-display">₱{{ number_format($subtotal + 50.00, 2) }}</span>
                    </div>
                </div>

                <!-- Payment Method Info -->
                <div class="mb-6 p-3 bg-amber-50/50 border border-amber-100 rounded-xl text-[10px] text-slate-600 space-y-1">
                    <span class="font-bold text-amber-800"><i class="bi bi-wallet2 mr-1"></i> Payment Mode:</span>
                    <p class="font-medium">Cash on Delivery / Cash on Store Pickup only.</p>
                </div>

                <button type="submit" class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-sm font-black text-white bg-sky-500 hover:bg-sky-600 shadow-md shadow-sky-100 transition-all hover:bg-sky-600">
                    <i class="bi bi-check2-square"></i>
                    <span>Place Final Order</span>
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
    const subtotal = {{ $subtotal }};
    const deliveryFeeDisplay = document.getElementById('delivery-fee-display');
    const totalAmountDisplay = document.getElementById('total-amount-display');
    const addressSection = document.getElementById('address-section');
    const addressInput = document.getElementById('delivery_address');
    const deliveryCard = document.getElementById('delivery-card');
    const pickupCard = document.getElementById('pickup-card');

    function toggleFulfillmentMode() {
        const orderTypeInput = document.querySelector('input[name="order_type"]:checked').value;

        if (orderTypeInput === 'Pickup') {
            addressSection.style.display = 'none';
            addressInput.removeAttribute('required');
            
            deliveryFeeDisplay.textContent = '₱0.00';
            totalAmountDisplay.textContent = '₱' + formatCurrency(subtotal);

            // Active Pickup Card styling
            pickupCard.className = "relative border-2 border-sky-500 bg-sky-50/30 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition-all select-none";
            pickupCard.querySelector('i').className = "bi bi-shop text-3xl text-sky-600 mb-2";
            pickupCard.querySelector('span.text-sm').className = "text-sm font-black text-slate-900";
            
            // Inactive Delivery Card styling
            deliveryCard.className = "relative border border-slate-200 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50/50 transition-all select-none";
            deliveryCard.querySelector('i').className = "bi bi-truck text-3xl text-slate-400 mb-2";
            deliveryCard.querySelector('span.text-sm').className = "text-sm font-bold text-slate-700";
        } else {
            addressSection.style.display = 'block';
            addressInput.setAttribute('required', 'required');
            
            deliveryFeeDisplay.textContent = '₱50.00';
            totalAmountDisplay.textContent = '₱' + formatCurrency(subtotal + 50.00);

            // Active Delivery Card styling
            deliveryCard.className = "relative border-2 border-sky-500 bg-sky-50/30 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer transition-all select-none";
            deliveryCard.querySelector('i').className = "bi bi-truck text-3xl text-sky-600 mb-2";
            deliveryCard.querySelector('span.text-sm').className = "text-sm font-black text-slate-900";
            
            // Inactive Pickup Card styling
            pickupCard.className = "relative border border-slate-200 rounded-2xl p-5 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50/50 transition-all select-none";
            pickupCard.querySelector('i').className = "bi bi-shop text-3xl text-slate-400 mb-2";
            pickupCard.querySelector('span.text-sm').className = "text-sm font-bold text-slate-700";
        }
    }

    function formatCurrency(number) {
        return number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Call on load
    document.addEventListener('DOMContentLoaded', toggleFulfillmentMode);
</script>
@endsection
