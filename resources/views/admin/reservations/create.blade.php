@extends('layouts.admin')

@section('title', 'New Reservation')
@section('page_title', 'New Reservation')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Top Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.reservations.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Reservations</span>
        </a>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50 text-sky-700 border border-sky-200 text-xs font-bold">
            <i class="bi bi-calendar-check"></i>
            <span>{{ $nextReservationCode }}</span>
        </span>
    </div>

    <!-- Main Form Card -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-sky-500/[0.05] via-transparent to-indigo-500/[0.04]">
            <h2 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center text-base">
                    <i class="bi bi-calendar-plus-fill"></i>
                </span>
                <span>Create Product Reservation</span>
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Schedule an advance order for store pickup. Pending reservations do not hold stock; confirming allocates quantity to Reserved Stock.
            </p>
        </div>

        @if($errors->any())
            <div class="mx-6 mt-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
                <p class="font-bold flex items-center gap-1.5">
                    <i class="bi bi-exclamation-octagon-fill text-rose-600"></i>
                    <span>Please check the following errors:</span>
                </p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.reservations.store') }}" method="POST" id="reservationForm" class="p-6 space-y-6">
            @csrf

            <!-- Section 1: Reservation & Customer Information -->
            <div class="bg-slate-50/70 border border-slate-200/70 rounded-2xl p-4 sm:p-5 space-y-4">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-person-vcard text-slate-500"></i>
                    <span>Reservation & Customer Details</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Auto-generated Reservation ID -->
                    <div>
                        <label for="reservation_code" class="block text-[11px] font-bold text-slate-600 mb-1">Reservation ID (Auto-generated)</label>
                        <input type="text" name="reservation_code" id="reservation_code" value="{{ old('reservation_code', $nextReservationCode) }}" readonly
                            class="w-full text-xs font-bold text-slate-700 rounded-xl border-slate-200 bg-slate-100 py-2.5 px-3 cursor-not-allowed">
                    </div>

                    <!-- Optional Registered Customer Quick-Fill -->
                    <div>
                        <label for="customer_id" class="block text-[11px] font-bold text-slate-600 mb-1">Link Registered Customer (Optional)</label>
                        <select name="customer_id" id="customer_id" onchange="fillRegisteredCustomer(this)"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            <option value="">-- Walk-in / Manual Customer Entry --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}"
                                        data-name="{{ $c->name }}"
                                        data-phone="{{ $c->phone ?? '' }}"
                                        {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Customer Name -->
                    <div>
                        <label for="customer_name" class="block text-[11px] font-bold text-slate-600 mb-1">Customer Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" required value="{{ old('customer_name') }}"
                            placeholder="Enter customer full name"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>

                    <!-- Contact Number -->
                    <div>
                        <label for="contact_number" class="block text-[11px] font-bold text-slate-600 mb-1">Contact Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="contact_number" id="contact_number" required value="{{ old('contact_number') }}"
                            placeholder="Enter mobile or phone number"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>
                </div>
            </div>

            <!-- Section 2: Product, Quantity & Schedule -->
            <div class="bg-slate-50/70 border border-slate-200/70 rounded-2xl p-4 sm:p-5 space-y-4">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-box-seam text-slate-500"></i>
                    <span>Product & Pickup Schedule</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <!-- Product -->
                    <div class="sm:col-span-6">
                        <label for="product_id" class="block text-[11px] font-bold text-slate-600 mb-1">Product <span class="text-rose-500">*</span></label>
                        <select name="product_id" id="product_id" required onchange="onReservationProductChange()"
                            class="w-full text-xs font-medium rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            <option value="">-- Choose Product --</option>
                            @foreach($products as $p)
                                <option value="{{ $p['id'] }}" {{ old('product_id') == $p['id'] ? 'selected' : '' }}>
                                    {{ $p['product_name'] }} ({{ $p['size'] }}) — Avail: {{ $p['available_stock'] }} / Phys: {{ $p['physical_stock'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Size -->
                    <div class="sm:col-span-2">
                        <label for="size" class="block text-[11px] font-bold text-slate-600 mb-1">Size</label>
                        <select name="size" id="size" onchange="onReservationSizeChange()"
                            class="w-full text-xs font-medium rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            <option value="{{ old('size', 'Medium') }}">{{ old('size', 'Medium') }}</option>
                        </select>
                    </div>

                    <!-- Quantity -->
                    <div class="sm:col-span-2">
                        <label for="quantity" class="block text-[11px] font-bold text-slate-600 mb-1">Quantity <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity" id="quantity" required min="1" value="{{ old('quantity', 1) }}"
                            oninput="recalculateReservation()"
                            class="w-full text-xs font-bold text-center rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>

                    <!-- Unit Price -->
                    <div class="sm:col-span-2">
                        <label for="unit_price" class="block text-[11px] font-bold text-slate-600 mb-1">Unit Price (₱)</label>
                        <input type="number" name="unit_price" id="unit_price" step="0.01" min="0" value="{{ old('unit_price') }}"
                            oninput="recalculateReservation()"
                            class="w-full text-xs font-bold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>
                </div>

                <!-- Live Stock & Batch Expiry Banner -->
                <div id="productStockInfo" class="hidden rounded-xl border border-slate-200 bg-white p-3.5 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-4 flex-wrap">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Physical Stock</span>
                                <span id="infoPhysical" class="font-black text-slate-900">0 pcs</span>
                            </div>
                            <div class="h-6 w-px bg-slate-200"></div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Reserved Stock</span>
                                <span id="infoReserved" class="font-black text-amber-600">0 pcs</span>
                            </div>
                            <div class="h-6 w-px bg-slate-200"></div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Available Stock</span>
                                <span id="infoAvailable" class="font-black text-emerald-600">0 pcs</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Batch Expiry Coverage</span>
                            <span id="infoExpiry" class="font-semibold text-slate-700">—</span>
                        </div>
                    </div>
                    <p id="stockWarningMsg" class="hidden mt-2 text-[11px] font-bold text-rose-600"></p>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="reservation_date" class="block text-[11px] font-bold text-slate-600 mb-1">Reservation Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="reservation_date" id="reservation_date" required
                            value="{{ old('reservation_date', now()->toDateString()) }}"
                            onchange="syncPickupMinDate()"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>
                    <div>
                        <label for="pickup_date" class="block text-[11px] font-bold text-slate-600 mb-1">Scheduled Pickup Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="pickup_date" id="pickup_date" required
                            value="{{ old('pickup_date', now()->addDay()->toDateString()) }}"
                            min="{{ old('reservation_date', now()->toDateString()) }}"
                            onchange="recalculateReservation()"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>
                </div>
            </div>

            <!-- Section 3: Payment & Status -->
            <div class="bg-slate-50/70 border border-slate-200/70 rounded-2xl p-4 sm:p-5 space-y-4">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-wallet2 text-slate-500"></i>
                    <span>Payment & Reservation Status</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <!-- Total Amount (Computed) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Total Amount</label>
                        <input type="text" id="total_amount_display" readonly value="₱0.00"
                            class="w-full text-xs font-black text-slate-900 rounded-xl border-slate-200 bg-slate-100 py-2.5 px-3">
                    </div>

                    <!-- Down Payment -->
                    <div>
                        <label for="down_payment" class="block text-[11px] font-bold text-slate-600 mb-1">Down Payment (₱)</label>
                        <input type="number" name="down_payment" id="down_payment" step="0.01" min="0"
                            value="{{ old('down_payment', '0.00') }}"
                            oninput="recalculateReservation(true)"
                            class="w-full text-xs font-bold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>

                    <!-- Remaining Balance (Computed) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Remaining Balance</label>
                        <input type="text" id="remaining_balance_display" readonly value="₱0.00"
                            class="w-full text-xs font-black text-emerald-700 rounded-xl border-slate-200 bg-slate-100 py-2.5 px-3">
                    </div>

                    <!-- Payment Status -->
                    <div>
                        <label for="payment_status" class="block text-[11px] font-bold text-slate-600 mb-1">Payment Status</label>
                        <select name="payment_status" id="payment_status" onchange="onPaymentStatusChange()"
                            class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            <option value="Unpaid" {{ old('payment_status', 'Unpaid') === 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="Partially Paid" {{ old('payment_status') === 'Partially Paid' ? 'selected' : '' }}>Partially Paid</option>
                            <option value="Fully Paid" {{ old('payment_status') === 'Fully Paid' ? 'selected' : '' }}>Fully Paid</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Reservation Status -->
                    <div>
                        <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">Reservation Status <span class="text-rose-500">*</span></label>
                        <select name="status" id="status" onchange="recalculateReservation()"
                            class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            <option value="Pending" {{ old('status', 'Pending') === 'Pending' ? 'selected' : '' }}>Pending (Does not reserve stock yet)</option>
                            <option value="Confirmed" {{ old('status') === 'Confirmed' ? 'selected' : '' }}>Confirmed (Allocates Reserved Stock)</option>
                            <option value="Ready for Pickup" {{ old('status') === 'Ready for Pickup' ? 'selected' : '' }}>Ready for Pickup (Allocates Reserved Stock)</option>
                        </select>
                    </div>

                    <!-- Notes / Purpose -->
                    <div>
                        <label for="notes" class="block text-[11px] font-bold text-slate-600 mb-1">Notes / Purpose</label>
                        <input type="text" name="notes" id="notes" value="{{ old('notes') }}"
                            placeholder="Event purpose, packaging instructions, or pickup note"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="border-t border-slate-100 pt-5 flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.reservations.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 border border-slate-200 transition-all">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 border border-sky-600 shadow-sm transition-all active:scale-95 cursor-pointer">
                    <i class="bi bi-check2-circle text-sm"></i>
                    <span>Save Reservation</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const reservationProducts = @json($products);
    const initialOldSize = @json(old('size'));
    const initialOldPrice = @json(old('unit_price'));

    function fillRegisteredCustomer(select) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;
        const nameInput = document.getElementById('customer_name');
        const phoneInput = document.getElementById('contact_number');
        if (opt.dataset.name) nameInput.value = opt.dataset.name;
        if (opt.dataset.phone) phoneInput.value = opt.dataset.phone;
    }

    function syncPickupMinDate() {
        const resDate = document.getElementById('reservation_date').value;
        const pickupInput = document.getElementById('pickup_date');
        if (resDate) {
            pickupInput.min = resDate;
            if (pickupInput.value && pickupInput.value < resDate) {
                pickupInput.value = resDate;
            }
        }
        recalculateReservation();
    }

    function onReservationProductChange(preserveExistingPrice = false) {
        const productId = parseInt(document.getElementById('product_id').value || '0', 10);
        const product = reservationProducts.find(p => p.id === productId);
        const sizeSelect = document.getElementById('size');
        const priceInput = document.getElementById('unit_price');
        const infoBox = document.getElementById('productStockInfo');

        if (!product) {
            infoBox.classList.add('hidden');
            recalculateReservation();
            return;
        }

        // Populate sizes
        const sizes = product.sizes && product.sizes.length ? product.sizes : [{ size: product.size || 'Medium', price: product.base_price }];
        const preferredSize = initialOldSize && preserveExistingPrice ? initialOldSize : (product.size || sizes[0].size);
        sizeSelect.innerHTML = '';
        sizes.forEach(s => {
            const option = document.createElement('option');
            option.value = s.size;
            option.textContent = s.size;
            option.dataset.price = s.price;
            if (s.size === preferredSize) option.selected = true;
            sizeSelect.appendChild(option);
        });

        if (!preserveExistingPrice || !priceInput.value) {
            const selectedOpt = sizeSelect.options[sizeSelect.selectedIndex];
            const resolvedPrice = selectedOpt ? parseFloat(selectedOpt.dataset.price || product.base_price) : product.base_price;
            priceInput.value = Number(resolvedPrice).toFixed(2);
        }

        // Populate Stock Info Banner
        document.getElementById('infoPhysical').textContent = `${product.physical_stock} pcs`;
        document.getElementById('infoReserved').textContent = `${product.reserved_stock} pcs`;
        document.getElementById('infoAvailable').textContent = `${product.available_stock} pcs`;
        document.getElementById('infoExpiry').textContent = product.latest_expiry
            ? `Valid through ${product.latest_expiry}`
            : 'No batch expiry recorded';
        infoBox.classList.remove('hidden');

        recalculateReservation();
    }

    function onReservationSizeChange() {
        const sizeSelect = document.getElementById('size');
        const selectedOpt = sizeSelect.options[sizeSelect.selectedIndex];
        if (selectedOpt && selectedOpt.dataset.price) {
            document.getElementById('unit_price').value = Number(selectedOpt.dataset.price).toFixed(2);
        }
        recalculateReservation();
    }

    function onPaymentStatusChange() {
        const status = document.getElementById('payment_status').value;
        const qty = parseInt(document.getElementById('quantity').value || '0', 10);
        const unitPrice = parseFloat(document.getElementById('unit_price').value || '0');
        const total = Math.max(0, qty * unitPrice);
        const dpInput = document.getElementById('down_payment');

        if (status === 'Fully Paid') {
            dpInput.value = total.toFixed(2);
        } else if (status === 'Unpaid') {
            dpInput.value = '0.00';
        }
        recalculateReservation(false);
    }

    function recalculateReservation(autoUpdatePaymentStatus = true) {
        const qty = Math.max(0, parseInt(document.getElementById('quantity').value || '0', 10));
        const unitPrice = Math.max(0, parseFloat(document.getElementById('unit_price').value || '0'));
        const total = Math.round(qty * unitPrice * 100) / 100;

        const dpInput = document.getElementById('down_payment');
        let dp = Math.max(0, parseFloat(dpInput.value || '0'));
        const remaining = Math.max(0, Math.round((total - dp) * 100) / 100);

        document.getElementById('total_amount_display').value = `₱${total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        document.getElementById('remaining_balance_display').value = `₱${remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        if (autoUpdatePaymentStatus) {
            const paySelect = document.getElementById('payment_status');
            if (dp <= 0) {
                paySelect.value = 'Unpaid';
            } else if (total > 0 && dp >= total) {
                paySelect.value = 'Fully Paid';
            } else {
                paySelect.value = 'Partially Paid';
            }
        }

        // Live availability / expiry warning
        const productId = parseInt(document.getElementById('product_id').value || '0', 10);
        const product = reservationProducts.find(p => p.id === productId);
        const warningEl = document.getElementById('stockWarningMsg');
        const statusVal = document.getElementById('status').value;
        const pickupDate = document.getElementById('pickup_date').value;

        if (product && warningEl) {
            let warnings = [];
            if (qty > product.available_stock && (statusVal === 'Confirmed' || statusVal === 'Ready for Pickup')) {
                warnings.push(`Sorry! Only ${product.available_stock} pieces of ${product.product_name} are currently available.`);
            }
            if (pickupDate && product.latest_expiry && pickupDate > product.latest_expiry) {
                warnings.push(`Scheduled pickup date (${pickupDate}) is after the latest active batch expiry (${product.latest_expiry}).`);
            }

            if (warnings.length > 0) {
                warningEl.textContent = warnings.join(' ');
                warningEl.classList.remove('hidden');
            } else {
                warningEl.textContent = '';
                warningEl.classList.add('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('product_id').value) {
            onReservationProductChange(Boolean(initialOldPrice));
        } else {
            recalculateReservation(false);
        }
    });
</script>
@endsection
