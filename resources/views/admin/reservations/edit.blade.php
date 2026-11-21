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

        <form action="{{ route('admin.reservations.update', $reservation->id) }}" method="POST" class="p-6 space-y-6">
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
                                <option value="{{ $p['id'] }}" {{ old('product_id', $reservation->product_id) == $p['id'] ? 'selected' : '' }}>
                                    {{ $p['product_name'] }} ({{ $p['size'] }}) — Avail: {{ $p['available_stock'] }} / Phys: {{ $p['physical_stock'] }}
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
                        <label for="quantity" class="block text-[11px] font-bold text-slate-600 mb-1">Quantity <span class="text-rose-500">*</span></label>
                        <input type="number" name="quantity" id="quantity" required min="1" value="{{ old('quantity', $reservation->quantity) }}"
                            oninput="recalculateEdit()"
                            class="w-full text-xs font-bold text-center rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
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
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Available Stock</span>
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
                        <label for="down_payment" class="block text-[11px] font-bold text-slate-600 mb-1">Down Payment (₱)</label>
                        <input type="number" name="down_payment" id="down_payment" step="0.01" min="0"
                            value="{{ old('down_payment', number_format((float) $reservation->down_payment, 2, '.', '')) }}"
                            oninput="recalculateEdit(true)"
                            class="w-full text-xs font-bold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Remaining Balance</label>
                        <input type="text" id="remaining_balance_display" readonly value="₱{{ number_format((float) $reservation->remaining_balance, 2) }}"
                            class="w-full text-xs font-black text-emerald-700 rounded-xl border-slate-200 bg-slate-100 py-2.5 px-3">
                    </div>

                    <div>
                        <label for="payment_status" class="block text-[11px] font-bold text-slate-600 mb-1">Payment Status</label>
                        <select name="payment_status" id="payment_status" onchange="onEditPaymentStatusChange()"
                            class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                            @foreach(\App\Models\Reservation::PAYMENT_STATUSES as $ps)
                                <option value="{{ $ps }}" {{ old('payment_status', $reservation->payment_status) === $ps ? 'selected' : '' }}>{{ $ps }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-[11px] font-bold text-slate-600 mb-1">Reservation Status <span class="text-rose-500">*</span></label>
                        <select name="status" id="status"
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
            </div>

            <!-- Footer Actions -->
            <div class="border-t border-slate-100 pt-5 flex items-center justify-end gap-2.5">
                <a href="{{ route('admin.reservations.show', $reservation->id) }}" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 border border-slate-200 transition-all">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 border border-sky-600 shadow-sm transition-all active:scale-95 cursor-pointer">
                    <i class="bi bi-check2-circle text-sm"></i>
                    <span>Update Reservation</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const editProducts = @json($products);
    const savedSize = @json(old('size', $reservation->size));

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
        document.getElementById('infoAvailable').textContent = `${product.available_stock} pcs`;
        document.getElementById('infoExpiry').textContent = product.latest_expiry
            ? `Valid through ${product.latest_expiry}`
            : 'No batch expiry recorded';

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
        const total = Math.max(0, qty * unitPrice);
        const dpInput = document.getElementById('down_payment');

        if (status === 'Fully Paid') {
            dpInput.value = total.toFixed(2);
        } else if (status === 'Unpaid') {
            dpInput.value = '0.00';
        }
        recalculateEdit(false);
    }

    function recalculateEdit(autoUpdatePaymentStatus = true) {
        const qty = Math.max(0, parseInt(document.getElementById('quantity').value || '0', 10));
        const unitPrice = Math.max(0, parseFloat(document.getElementById('unit_price').value || '0'));
        const total = Math.round(qty * unitPrice * 100) / 100;
        const dp = Math.max(0, parseFloat(document.getElementById('down_payment').value || '0'));
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
    }

    document.addEventListener('DOMContentLoaded', () => {
        onEditProductChange(true);
    });
</script>
@endsection
