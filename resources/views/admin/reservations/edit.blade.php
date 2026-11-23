@extends('layouts.admin')

@section('title', 'Edit Reservation ' . $reservation->reservation_code)
@section('page_title', 'Edit Reservation')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Top Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.reservations.show', $reservation->id) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Reservation Details</span>
        </a>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-50 text-sky-700 border border-sky-200 text-xs font-bold">
            <i class="bi bi-pencil-square"></i>
            <span>{{ $reservation->reservation_code }}</span>
        </span>
    </div>

    <!-- Main Form Card -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-sky-500/[0.05] via-transparent to-amber-500/[0.04]">
            <h2 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center text-base">
                    <i class="bi bi-pencil-square"></i>
                </span>
                <span>Edit Reservation {{ $reservation->reservation_code }}</span>
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Update reservation details, payment progress, or status. Stock allocations are adjusted atomically without duplicate deductions.
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

        <form action="{{ route('admin.reservations.update', $reservation->id) }}" method="POST" id="editReservationForm" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Reservation & Customer Details -->
            <div class="bg-slate-50/70 border border-slate-200/70 rounded-2xl p-4 sm:p-5 space-y-4">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-person-vcard text-slate-500"></i>
                    <span>Reservation & Customer Details</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Reservation ID</label>
                        <input type="text" value="{{ $reservation->reservation_code }}" readonly
                            class="w-full text-xs font-bold text-slate-700 rounded-xl border-slate-200 bg-slate-100 py-2.5 px-3 cursor-not-allowed">
                    </div>

                    <div>
                        <label for="customer_id" class="block text-[11px] font-bold text-slate-600 mb-1">Link Registered Customer (Optional)</label>
                        <select name="customer_id" id="customer_id" onchange="fillRegisteredCustomer(this)"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            <option value="">-- Walk-in / Manual Customer Entry --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}"
                                        data-name="{{ $c->name }}"
                                        data-phone="{{ $c->phone ?? '' }}"
                                        {{ old('customer_id', $reservation->customer_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="customer_name" class="block text-[11px] font-bold text-slate-600 mb-1">Customer Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" required value="{{ old('customer_name', $reservation->customer_name) }}"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>

                    <div>
                        <label for="contact_number" class="block text-[11px] font-bold text-slate-600 mb-1">Contact Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="contact_number" id="contact_number" required value="{{ old('contact_number', $reservation->contact_number) }}"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>
                </div>
            </div>

            <!-- Section 2: Product & Schedule -->
            <div class="bg-slate-50/70 border border-slate-200/70 rounded-2xl p-4 sm:p-5 space-y-4">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-box-seam text-slate-500"></i>
                    <span>Product & Pickup Schedule</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-6">
                        <label for="product_id" class="block text-[11px] font-bold text-slate-600 mb-1">Product <span class="text-rose-500">*</span></label>
                        <select name="product_id" id="product_id" required onchange="onEditProductChange(false)"
                            class="w-full text-xs font-medium rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            @foreach($products as $p)
                                @php
                                    $isCurrentProd = (int) $reservation->product_id === (int) $p['id'];
                                    $isBlockedProd = $p['is_expired'] && !$isCurrentProd;
                                @endphp
                                <option value="{{ $p['id'] }}"
                                        {{ old('product_id', $reservation->product_id) == $p['id'] ? 'selected' : '' }}
                                        {{ $isBlockedProd ? 'disabled' : '' }}>
                                    @if($p['is_expired'])
                                        {{ $p['product_name'] }} ({{ $p['size'] }}) — Expired (Not Reservable)
                                    @else
                                        {{ $p['product_name'] }} ({{ $p['size'] }}) — Reservable: {{ $p['reservable_stock'] }} pcs (Phys: {{ $p['physical_stock'] }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="size" class="block text-[11px] font-bold text-slate-600 mb-1">Size</label>
                        <select name="size" id="size" onchange="onEditSizeChange()"
                            class="w-full text-xs font-medium rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            <option value="{{ old('size', $reservation->size) }}">{{ old('size', $reservation->size) }}</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="quantity" class="block text-[11px] font-bold text-slate-600 mb-1">Quantity (Min. 15) <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity" id="quantity" required min="15" value="{{ old('quantity', $reservation->quantity) }}"
                            oninput="recalculateEdit()"
                            class="w-full text-xs font-bold text-center rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                        <p id="editQuantityError" class="hidden mt-1 text-[10px] font-bold text-rose-600"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="unit_price" class="block text-[11px] font-bold text-slate-600 mb-1">Unit Price (₱)</label>
                        <input type="number" name="unit_price" id="unit_price" step="0.01" min="0" value="{{ old('unit_price', number_format((float) $reservation->unit_price, 2, '.', '')) }}"
                            oninput="recalculateEdit()"
                            class="w-full text-xs font-bold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>
                </div>

                <!-- Live Stock Info -->
                <div id="productStockInfo" class="rounded-xl border border-slate-200 bg-white p-3.5 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-4 flex-wrap">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Physical Stock</span>
                                <span id="infoPhysical" class="font-black text-slate-900">—</span>
                            </div>
                            <div class="h-6 w-px bg-slate-200"></div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Reserved Stock</span>
                                <span id="infoReserved" class="font-black text-amber-600">—</span>
                            </div>
                            <div class="h-6 w-px bg-slate-200"></div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Reservable for Pickup</span>
                                <span id="infoAvailable" class="font-black text-emerald-600">—</span>
                            </div>
                            @if($reservation->allocated_quantity > 0)
                                <div class="h-6 w-px bg-slate-200"></div>
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-sky-500 block">Currently Held by This</span>
                                    <span class="font-black text-sky-700">{{ $reservation->allocated_quantity }} pcs</span>
                                </div>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Batch Expiry Coverage</span>
                            <span id="infoExpiry" class="font-semibold text-slate-700">—</span>
                        </div>
                    </div>
                    <p id="editStockWarningMsg" class="hidden mt-2 text-[11px] font-bold text-rose-600"></p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="reservation_date" class="block text-[11px] font-bold text-slate-600 mb-1">Reservation Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="reservation_date" id="reservation_date" required
                            value="{{ old('reservation_date', $reservation->reservation_date->toDateString()) }}"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>
                    <div>
                        <label for="pickup_date" class="block text-[11px] font-bold text-slate-600 mb-1">Scheduled Pickup Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="pickup_date" id="pickup_date" required
                            value="{{ old('pickup_date', $reservation->pickup_date->toDateString()) }}"
                            onchange="recalculateEdit(false)"
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
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Total Amount</label>
                        <input type="text" id="total_amount_display" readonly value="₱{{ number_format((float) $reservation->total_amount, 2) }}"
                            class="w-full text-xs font-black text-slate-900 rounded-xl border-slate-200 bg-slate-100 py-2.5 px-3">
                    </div>

                    <div>
                        <label for="down_payment" class="block text-[11px] font-bold text-slate-600 mb-1">Down Payment (₱) <span class="text-rose-500">*</span></label>
                        <input type="number" name="down_payment" id="down_payment" required step="0.01" min="0.01"
                            value="{{ old('down_payment', (float) $reservation->down_payment > 0 ? number_format((float) $reservation->down_payment, 2, '.', '') : '') }}"
                            placeholder="Required (> ₱0.00)"
                            oninput="recalculateEdit(true)"
                            class="w-full text-xs font-bold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                        <p id="editDownPaymentError" class="hidden mt-1 text-[10px] font-bold text-rose-600"></p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Remaining Balance</label>
                        <input type="text" id="remaining_balance_display" readonly value="₱{{ number_format((float) $reservation->remaining_balance, 2) }}"
                            class="w-full text-xs font-black text-emerald-700 rounded-xl border-slate-200 bg-slate-100 py-2.5 px-3">
                    </div>

                    <div>
                        <label for="payment_status" class="block text-[11px] font-bold text-slate-600 mb-1">Payment Status <span class="text-rose-500">*</span></label>
                        <select name="payment_status" id="payment_status" required onchange="onEditPaymentStatusChange()"
                            class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            <option value="Partially Paid" {{ old('payment_status', $reservation->payment_status) === 'Partially Paid' ? 'selected' : '' }}>Partially Paid</option>
                            <option value="Fully Paid" {{ old('payment_status', $reservation->payment_status) === 'Fully Paid' ? 'selected' : '' }}>Fully Paid</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">Reservation Status <span class="text-rose-500">*</span></label>
                        <select name="status" id="status" onchange="recalculateEdit(false)"
                            class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            @foreach(\App\Models\Reservation::STATUSES as $st)
                                <option value="{{ $st }}" {{ old('status', $reservation->status) === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-[11px] font-bold text-slate-600 mb-1">Notes / Purpose</label>
                        <input type="text" name="notes" id="notes" value="{{ old('notes', $reservation->notes) }}"
                            placeholder="Event purpose, packaging instructions, or pickup note"
                            class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>
                </div>

                <!-- Conditional Cancellation Policy Panel (shown when Status = Cancelled) -->
                <div id="editCancellationPolicyBox" class="hidden rounded-xl border border-rose-200 bg-rose-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-rose-800 flex items-center gap-1.5">
                            <i class="bi bi-x-circle-fill text-rose-600"></i>
                            <span>Cancellation &amp; Refund Policy Details</span>
                        </span>
                        <span id="editCancelPreviewBadge" class="text-[11px] font-bold text-rose-700"></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="cancellation_initiated_by" class="block text-[11px] font-bold text-slate-700 mb-1">Cancellation Initiated By</label>
                            <select name="cancellation_initiated_by" id="cancellation_initiated_by" onchange="recalculateEdit(false)"
                                class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                                <option value="customer" {{ old('cancellation_initiated_by', 'customer') === 'customer' ? 'selected' : '' }}>Customer-Initiated</option>
                                <option value="business" {{ old('cancellation_initiated_by') === 'business' ? 'selected' : '' }}>Business-Initiated (Unable to Fulfill — 100% Refund)</option>
                            </select>
                        </div>

                        <div>
                            <label for="cancellation_reason" class="block text-[11px] font-bold text-slate-700 mb-1">Cancellation Reason</label>
                            <input type="text" name="cancellation_reason" id="cancellation_reason" value="{{ old('cancellation_reason', $reservation->cancellation_reason) }}"
                                maxlength="255" placeholder="Optional cancellation reason"
                                class="w-full text-xs rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs bg-white rounded-xl border border-rose-100 p-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Applied Fee</span>
                            <span id="editCancelFeeAmount" class="font-bold text-rose-600">₱0.00</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Refund Amount (Pending)</span>
                            <span id="editCancelRefundAmount" class="font-black text-emerald-700">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="border-t border-slate-100 pt-5 flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.reservations.show', $reservation->id) }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 border border-slate-200 transition-all">
                    Cancel
                </a>
                <button type="submit" id="updateReservationBtn" class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 border border-sky-600 shadow-sm transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="bi bi-check2-circle text-sm"></i>
                    <span>Update Reservation</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const editProducts = @json($products);
    const editCancellationPolicy = @json($cancellationPolicy);
    const todayDateStr = @json(now()->toDateString());
    const savedSize = @json(old('size', $reservation->size));
    const currentReservationProductId = {{ (int) $reservation->product_id }};
    const currentReservationAllocatedQty = {{ (int) $reservation->allocated_quantity }};

    function computeEditReservableForPickup(product, pickupDate) {
        if (!product || product.is_expired || (product.non_expired_stock || 0) <= 0) {
            return 0;
        }
        const effectiveDate = pickupDate && pickupDate > todayDateStr ? pickupDate : todayDateStr;
        const batches = product.valid_batches || [];
        let eligibleBatchQty = 0;
        batches.forEach(b => {
            if (b.expiration_date && b.expiration_date >= effectiveDate) {
                eligibleBatchQty += parseInt(b.remaining_stock || 0, 10);
            }
        });

        // active_allocations already excludes this reservation ID
        const allocations = product.active_allocations || [];
        let otherAllocatedForDateOrLater = 0;
        allocations.forEach(a => {
            if (a.pickup_date && a.pickup_date >= effectiveDate) {
                otherAllocatedForDateOrLater += parseInt(a.allocated_quantity || 0, 10);
            }
        });

        const netEligible = Math.max(0, eligibleBatchQty - otherAllocatedForDateOrLater);
        const ownReleaseBonus = (product.id === currentReservationProductId) ? currentReservationAllocatedQty : 0;
        const effectiveAvailable = parseInt(product.available_stock || 0, 10) + ownReleaseBonus;
        return Math.max(0, Math.min(effectiveAvailable, netEligible));
    }

    function fillRegisteredCustomer(select) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;
        if (opt.dataset.name) document.getElementById('customer_name').value = opt.dataset.name;
        if (opt.dataset.phone) document.getElementById('contact_number').value = opt.dataset.phone;
    }

    function onEditProductChange(preservePrice = true) {
        const productId = parseInt(document.getElementById('product_id').value || '0', 10);
        const product = editProducts.find(p => p.id === productId);
        const sizeSelect = document.getElementById('size');
        const priceInput = document.getElementById('unit_price');

        if (!product) return;

        const sizes = product.sizes && product.sizes.length ? product.sizes : [{ size: product.size || 'Medium', price: product.base_price }];
        const targetSize = preservePrice && savedSize ? savedSize : (product.size || sizes[0].size);

        sizeSelect.innerHTML = '';
        sizes.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.size;
            opt.textContent = s.size;
            opt.dataset.price = s.price;
            if (s.size === targetSize) opt.selected = true;
            sizeSelect.appendChild(opt);
        });

        if (!preservePrice) {
            const selectedOpt = sizeSelect.options[sizeSelect.selectedIndex];
            const resolvedPrice = selectedOpt ? parseFloat(selectedOpt.dataset.price || product.base_price) : product.base_price;
            priceInput.value = Number(resolvedPrice).toFixed(2);
        }

        document.getElementById('infoPhysical').textContent = `${product.physical_stock} pcs`;
        document.getElementById('infoReserved').textContent = `${product.reserved_stock} pcs`;
        document.getElementById('infoExpiry').textContent = product.latest_expiry
            ? `Valid through ${product.latest_expiry}`
            : 'Expired — No valid non-expired batches';

        recalculateEdit(false);
    }

    function onEditSizeChange() {
        const sizeSelect = document.getElementById('size');
        const selectedOpt = sizeSelect.options[sizeSelect.selectedIndex];
        if (selectedOpt && selectedOpt.dataset.price) {
            document.getElementById('unit_price').value = Number(selectedOpt.dataset.price).toFixed(2);
        }
        recalculateEdit(true);
    }

    function onEditPaymentStatusChange() {
        const status = document.getElementById('payment_status').value;
        const qty = parseInt(document.getElementById('quantity').value || '0', 10);
        const unitPrice = parseFloat(document.getElementById('unit_price').value || '0');
        const total = Math.max(0, Math.round(qty * unitPrice * 100) / 100);
        const dpInput = document.getElementById('down_payment');

        if (status === 'Fully Paid') {
            dpInput.value = total.toFixed(2);
        }
        recalculateEdit(false);
    }

    function recalculateEdit(autoUpdatePaymentStatus = true) {
        const qty = Math.max(0, parseInt(document.getElementById('quantity').value || '0', 10));
        const unitPrice = Math.max(0, parseFloat(document.getElementById('unit_price').value || '0'));
        const total = Math.round(qty * unitPrice * 100) / 100;
        const dpInput = document.getElementById('down_payment');
        const dpRaw = dpInput.value;
        const dp = dpRaw === '' ? 0 : parseFloat(dpRaw);
        const remaining = Math.max(0, Math.round((total - (isNaN(dp) ? 0 : dp)) * 100) / 100);

        dpInput.max = total > 0 ? total.toFixed(2) : '';

        document.getElementById('total_amount_display').value = `₱${total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        document.getElementById('remaining_balance_display').value = `₱${remaining.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        if (autoUpdatePaymentStatus) {
            const paySelect = document.getElementById('payment_status');
            if (total > 0 && !isNaN(dp) && dp >= total) {
                paySelect.value = 'Fully Paid';
            } else {
                paySelect.value = 'Partially Paid';
            }
        }

        const statusVal = document.getElementById('status').value;
        const isCancelling = statusVal === 'Cancelled';
        const cancelBox = document.getElementById('editCancellationPolicyBox');
        if (cancelBox) {
            if (isCancelling) {
                cancelBox.classList.remove('hidden');
                const initiator = document.getElementById('cancellation_initiated_by')?.value || 'customer';
                const effectiveDp = isNaN(dp) || dp < 0 ? 0 : dp;
                const feePct = (initiator === 'business' || !editCancellationPolicy.enabled)
                    ? 0
                    : Number(editCancellationPolicy.fee_percentage || 0);
                const feeAmt = Math.round(effectiveDp * (feePct / 100) * 100) / 100;
                const refundAmt = Math.max(0, Math.round((effectiveDp - feeAmt) * 100) / 100);
                const pctText = Number.isInteger(feePct) ? `${feePct}%` : `${feePct.toFixed(2).replace(/\.?0+$/, '')}%`;

                document.getElementById('editCancelPreviewBadge').textContent = `Applied Fee: ${pctText}`;
                document.getElementById('editCancelFeeAmount').textContent = `₱${feeAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                document.getElementById('editCancelRefundAmount').textContent = `₱${refundAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            } else {
                cancelBox.classList.add('hidden');
            }
        }
        const productId = parseInt(document.getElementById('product_id').value || '0', 10);
        const product = editProducts.find(p => p.id === productId);
        const warningEl = document.getElementById('editStockWarningMsg');
        const pickupDate = document.getElementById('pickup_date').value;
        let hasBlockingError = false;

        // Minimum reservation quantity guardrail (15 pieces)
        const qtyInput = document.getElementById('quantity');
        const qtyErrorEl = document.getElementById('editQuantityError');
        if (isNaN(qty) || qty < 15) {
            if (qtyErrorEl) {
                qtyErrorEl.textContent = 'Minimum reservation is 15 pieces of products.';
                qtyErrorEl.classList.remove('hidden');
            }
            qtyInput?.classList.add('border-rose-400', 'bg-rose-50/40');
            hasBlockingError = true;
        } else {
            if (qtyErrorEl) {
                qtyErrorEl.textContent = '';
                qtyErrorEl.classList.add('hidden');
            }
            qtyInput?.classList.remove('border-rose-400', 'bg-rose-50/40');
        }

        if (product) {
            const reservable = computeEditReservableForPickup(product, pickupDate);
            const availEl = document.getElementById('infoAvailable');
            availEl.textContent = `${reservable} pcs`;
            availEl.className = reservable > 0 ? 'font-black text-emerald-600' : 'font-black text-rose-600';

            let warnings = [];
            if (!isCancelling) {
                if (product.is_expired || (product.non_expired_stock || 0) <= 0) {
                    warnings.push(`${product.product_name} cannot be reserved because its remaining stock has expired.`);
                    hasBlockingError = true;
                } else if (qty > reservable) {
                    warnings.push(`Only ${reservable} non-expired pieces of ${product.product_name} are reservable for ${pickupDate || 'this date'}.`);
                    hasBlockingError = true;
                }
            }

            if (warnings.length > 0) {
                warningEl.textContent = warnings.join(' ');
                warningEl.classList.remove('hidden');
            } else {
                warningEl.textContent = '';
                warningEl.classList.add('hidden');
            }
        }

        const dpErrorEl = document.getElementById('editDownPaymentError');
        let dpError = '';
        if (!isCancelling) {
            if (isNaN(dp) || dp <= 0) {
                dpError = 'Down payment must be greater than ₱0.00.';
                hasBlockingError = true;
            } else if (total > 0 && dp > total) {
                dpError = `Down payment cannot exceed ₱${total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}.`;
                hasBlockingError = true;
            }
        }

        if (dpError) {
            dpErrorEl.textContent = dpError;
            dpErrorEl.classList.remove('hidden');
            dpInput.classList.add('border-rose-400', 'bg-rose-50/40');
        } else {
            dpErrorEl.textContent = '';
            dpErrorEl.classList.add('hidden');
            dpInput.classList.remove('border-rose-400', 'bg-rose-50/40');
        }

        const updateBtn = document.getElementById('updateReservationBtn');
        if (updateBtn) {
            updateBtn.disabled = hasBlockingError || qty < 15;
        }

        return !hasBlockingError;
    }

    document.addEventListener('DOMContentLoaded', () => {
        onEditProductChange(true);

        document.getElementById('editReservationForm')?.addEventListener('submit', (e) => {
            if (!recalculateEdit(false)) {
                e.preventDefault();
            }
        });
    });
</script>
@endsection
