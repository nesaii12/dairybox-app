@extends('layouts.admin')

@section('title', 'New Walk-in Sale')
@section('page_title', 'New Walk-in Sale')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Back Link -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Dashboard</span>
        </a>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold">
            <i class="bi bi-person-walking"></i>
            <span>Direct Walk-in Transaction</span>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <!-- Card Header -->
        <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-emerald-500/[0.04] via-transparent to-purple-500/[0.03]">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-base">
                            <i class="bi bi-bag-plus-fill"></i>
                        </span>
                        <span>Record Walk-in Counter Sale</span>
                    </h2>
                    <p class="text-xs text-slate-500">
                        Process in-store orders immediately. Deducts FIFO product stock levels and updates sales revenue and demand forecasts.
                    </p>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <span class="text-[11px] font-semibold text-slate-500">Mode:</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                        <i class="bi bi-shop"></i>
                        <span>Walk-in</span>
                    </span>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="mx-6 mt-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center gap-2">
                <i class="bi bi-exclamation-octagon-fill text-base text-rose-600 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Form Body -->
        <form action="{{ route('admin.walkInSales.store') }}" method="POST" id="walkInForm" class="p-6 space-y-6">
            @csrf

            <!-- Section 1: Customer & Payment Details -->
            <div class="bg-slate-50/70 border border-slate-200/70 rounded-2xl p-4 sm:p-5 space-y-4">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="bi bi-person-badge text-slate-500"></i>
                    <span>Customer & Payment Information</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Customer Type -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Customer Type</label>
                        <select name="customer_type" id="page_customer_type" onchange="toggleCustomerSelect(this.value)" class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                            <option value="walkin" selected>Walk-in Guest</option>
                            <option value="registered">Registered Customer</option>
                        </select>
                    </div>

                    <!-- Customer Field (Walk-in Name / Registered Select) -->
                    <div id="page_walkin_name_field">
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Customer Reference / Name (Optional)</label>
                        <input type="text" name="customer_name" placeholder="e.g. Maria Clara / Counter Guest" class="w-full text-xs rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                    </div>

                    <div id="page_registered_field" class="hidden">
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Select Customer</label>
                        <select name="customer_id" class="w-full text-xs rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                            <option value="">-- Choose Registered Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full text-xs font-semibold rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                            <option value="cash" selected>Cash</option>
                            <option value="gcash">GCash</option>
                            <option value="card">Card</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Transaction Notes (Optional)</label>
                    <input type="text" name="notes" placeholder="e.g. Paid in exact cash, receipt issued" class="w-full text-xs rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                </div>
            </div>

            <!-- Section 2: Product Line Items -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="bi bi-box-seam text-slate-500"></i>
                        <span>Selected Products & Pricing</span>
                    </h3>
                    <button type="button" onclick="addProductRow()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-all cursor-pointer">
                        <i class="bi bi-plus-circle"></i>
                        <span>Add Product</span>
                    </button>
                </div>

                <!-- Table Header on desktop -->
                <div class="hidden sm:grid grid-cols-12 gap-3 px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50 rounded-xl">
                    <div class="col-span-5">Product (Current Stock)</div>
                    <div class="col-span-2">Quantity</div>
                    <div class="col-span-2">Unit Price (₱)</div>
                    <div class="col-span-2 text-right">Subtotal</div>
                    <div class="col-span-1 text-center">Action</div>
                </div>

                <!-- Rows Container -->
                <div id="itemsContainer" class="space-y-3">
                    <!-- Dynamic Rows Injected Here -->
                </div>
            </div>

            <!-- Section 3: Summary & Submission Bar -->
            <div class="border-t border-slate-100 pt-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="w-full sm:w-auto flex items-center justify-between sm:justify-start gap-4">
                    <div class="text-xs text-slate-500">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Total Quantity</span>
                        <span id="grandQty" class="text-sm font-black text-slate-900">0 units</span>
                    </div>
                    <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>
                    <div class="text-xs text-slate-500">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Total Sale Amount</span>
                        <span id="grandTotal" class="text-xl sm:text-2xl font-black text-emerald-600">₱0.00</span>
                    </div>
                </div>

                <div class="w-full sm:w-auto flex items-center gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="flex-1 sm:flex-initial text-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 border border-slate-200 transition-all">
                        Cancel
                    </a>
                    <button type="submit" id="submitBtn" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 border border-emerald-600 shadow-sm transition-all active:scale-95 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="bi bi-check2-circle text-base"></i>
                        <span>Record Walk-in Sale</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const availableProducts = @json($products);
    let rowCounter = 0;

    function toggleCustomerSelect(val) {
        const walkinField = document.getElementById('page_walkin_name_field');
        const regField = document.getElementById('page_registered_field');
        if (val === 'registered') {
            walkinField.classList.add('hidden');
            regField.classList.remove('hidden');
        } else {
            walkinField.classList.remove('hidden');
            regField.classList.add('hidden');
        }
    }

    function addProductRow(preselectedKey = '', quantity = 1, customPrice = '') {
        rowCounter++;
        const rowId = `item_row_${rowCounter}`;
        const container = document.getElementById('itemsContainer');

        let optionsHtml = '<option value="">-- Choose Active Product --</option>';
        availableProducts.forEach(p => {
            const isOutOfStock = p.stock <= 0;
            const hasMultipleSizes = p.sizes && p.sizes.length > 1;

            if (hasMultipleSizes) {
                optionsHtml += `<optgroup label="${p.product_name} — Current Stock: ${p.stock} (${p.sizes_summary})">`;
                p.sizes.forEach(s => {
                    const optKey = `${p.id}:${s.size}`;
                    const isSelected = optKey === preselectedKey ? 'selected' : '';
                    optionsHtml += `
                        <option value="${optKey}" ${isSelected} ${isOutOfStock ? 'disabled class="text-slate-400"' : ''} data-product-id="${p.id}" data-size="${s.size}" data-price="${s.price}" data-stock="${p.stock}">
                            ${p.product_name} (${s.size}) — ${s.formatted_price} (Current Stock: ${p.stock})
                        </option>
                    `;
                });
                optionsHtml += `</optgroup>`;
            } else {
                const singleSize = p.sizes && p.sizes[0] ? p.sizes[0] : { size: p.size, price: p.base_price, formatted_price: '₱' + p.base_price.toFixed(2) };
                const optKey = `${p.id}:${singleSize.size}`;
                const isSelected = optKey === preselectedKey ? 'selected' : '';
                optionsHtml += `
                    <option value="${optKey}" ${isSelected} ${isOutOfStock ? 'disabled class="text-slate-400"' : ''} data-product-id="${p.id}" data-size="${singleSize.size}" data-price="${singleSize.price}" data-stock="${p.stock}">
                        ${p.product_name} (${singleSize.size}) — ${singleSize.formatted_price} (Current Stock: ${p.stock})
                    </option>
                `;
            }
        });

        const row = document.createElement('div');
        row.id = rowId;
        row.className = "grid grid-cols-1 sm:grid-cols-12 gap-3 p-3.5 sm:p-3 bg-slate-50/60 sm:bg-transparent rounded-2xl sm:rounded-none border border-slate-200/80 sm:border-0 sm:border-b sm:border-slate-100 items-start transition-colors";
        row.innerHTML = `
            <!-- Product Select & Size Selection -->
            <div class="sm:col-span-5 space-y-1.5">
                <label class="block sm:hidden text-[10px] font-bold uppercase text-slate-400">Product</label>
                <select name="items[${rowCounter}][selection]" required onchange="onProductChange('${rowId}')" class="product-select w-full text-xs font-medium rounded-xl border-slate-200 bg-white py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                    ${optionsHtml}
                </select>
                <input type="hidden" name="items[${rowCounter}][product_id]" class="product-id-input" value="">
                <input type="hidden" name="items[${rowCounter}][size]" class="size-input" value="">

                <div class="stock-badge text-[10px] font-semibold text-slate-500 px-0.5"></div>
                <div class="size-pills flex items-center gap-1 flex-wrap pt-0.5"></div>
            </div>

            <!-- Quantity -->
            <div class="sm:col-span-2 pt-0.5">
                <label class="block sm:hidden text-[10px] font-bold uppercase text-slate-400 mb-1">Quantity</label>
                <div class="flex items-center">
                    <input type="number" name="items[${rowCounter}][quantity]" value="${quantity}" min="1" required oninput="calculateTotals()" class="qty-input w-full text-xs font-bold text-center rounded-xl border-slate-200 bg-white py-2 px-2 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                </div>
            </div>

            <!-- Unit Price -->
            <div class="sm:col-span-2 pt-0.5">
                <label class="block sm:hidden text-[10px] font-bold uppercase text-slate-400 mb-1">Unit Price (₱)</label>
                <div class="relative">
                    <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">₱</span>
                    <input type="number" step="0.01" min="0" name="items[${rowCounter}][price]" value="${customPrice}" required oninput="calculateTotals()" class="price-input w-full text-xs font-bold rounded-xl border-slate-200 bg-white py-2 pl-6 pr-2 focus:border-emerald-500 focus:ring-emerald-500 shadow-2xs">
                </div>
            </div>

            <!-- Subtotal -->
            <div class="sm:col-span-2 flex items-center justify-between sm:justify-end pt-2 sm:pt-2.5">
                <span class="block sm:hidden text-[10px] font-bold uppercase text-slate-400">Subtotal</span>
                <span class="subtotal-display text-sm font-black text-slate-900">₱0.00</span>
            </div>

            <!-- Action -->
            <div class="sm:col-span-1 text-right sm:text-center pt-1 sm:pt-1.5">
                <button type="button" onclick="removeProductRow('${rowId}')" class="remove-btn p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Remove product">
                    <i class="bi bi-trash3"></i>
                </button>
            </div>
        `;

        container.appendChild(row);

        if (preselectedKey) {
            onProductChange(rowId);
        } else {
            calculateTotals();
        }
    }

    function onProductChange(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const select = row.querySelector('.product-select');
        const priceInput = row.querySelector('.price-input');
        const qtyInput = row.querySelector('.qty-input');
        const stockBadge = row.querySelector('.stock-badge');
        const sizePills = row.querySelector('.size-pills');
        const productIdInput = row.querySelector('.product-id-input');
        const sizeInput = row.querySelector('.size-input');

        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            stockBadge.innerHTML = '';
            sizePills.innerHTML = '';
            productIdInput.value = '';
            sizeInput.value = '';
            calculateTotals();
            return;
        }

        const productId = parseInt(selectedOption.getAttribute('data-product-id') || '0', 10);
        const currentSize = selectedOption.getAttribute('data-size') || '';
        const stock = parseInt(selectedOption.getAttribute('data-stock') || '0', 10);
        const defaultPrice = parseFloat(selectedOption.getAttribute('data-price') || '0');

        productIdInput.value = productId;
        sizeInput.value = currentSize;

        // Pre-fill price if empty or unchanged
        if (!priceInput.value || parseFloat(priceInput.value) <= 0 || !priceInput.dataset.manualOverride) {
            priceInput.value = defaultPrice.toFixed(2);
        }

        // Adjust qty limit
        qtyInput.max = stock > 0 ? stock : 1;
        if (parseInt(qtyInput.value, 10) > stock) {
            qtyInput.value = stock;
        }

        // Show live stock badge
        if (stock <= 5) {
            stockBadge.innerHTML = `<span class="text-rose-600 font-bold"><i class="bi bi-exclamation-triangle-fill"></i> Low Stock: ${stock} units remaining</span>`;
        } else {
            stockBadge.innerHTML = `<span class="text-emerald-700 font-bold"><i class="bi bi-check-circle-fill"></i> Current Stock: ${stock} units available</span>`;
        }

        // Render size pills if product has size choices
        const productData = availableProducts.find(p => p.id === productId);
        if (productData && productData.sizes && productData.sizes.length > 1) {
            let pillsHtml = '<span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mr-0.5">Size:</span>';
            productData.sizes.forEach(s => {
                const isSelectedSize = s.size === currentSize;
                pillsHtml += `
                    <button type="button" onclick="selectRowSize('${rowId}', ${productId}, '${s.size}', ${s.price})" class="px-2 py-0.5 rounded-lg text-[10px] transition-all cursor-pointer ${isSelectedSize ? 'font-black bg-emerald-600 text-white shadow-2xs' : 'font-semibold bg-white text-slate-700 border border-slate-200 hover:border-emerald-500 hover:text-emerald-700'}">
                        ${s.size} (${s.formatted_price})
                    </button>
                `;
            });
            sizePills.innerHTML = pillsHtml;
        } else {
            sizePills.innerHTML = '';
        }

        calculateTotals();
    }

    function selectRowSize(rowId, productId, sizeName, sizePrice) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const select = row.querySelector('.product-select');
        const priceInput = row.querySelector('.price-input');
        const sizeInput = row.querySelector('.size-input');

        // Select the matching option in dropdown
        const targetValue = `${productId}:${sizeName}`;
        select.value = targetValue;
        sizeInput.value = sizeName;
        priceInput.value = parseFloat(sizePrice).toFixed(2);
        delete priceInput.dataset.manualOverride;

        // Refresh pills UI
        onProductChange(rowId);
    }

    function removeProductRow(rowId) {
        const container = document.getElementById('itemsContainer');
        const rows = container.querySelectorAll('[id^="item_row_"]');
        if (rows.length <= 1) {
            alert('A walk-in sale requires at least one product.');
            return;
        }
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            calculateTotals();
        }
    }

    function calculateTotals() {
        const container = document.getElementById('itemsContainer');
        const rows = container.querySelectorAll('[id^="item_row_"]');

        let grandQty = 0;
        let grandTotal = 0.0;
        let hasError = false;

        rows.forEach(row => {
            const select = row.querySelector('.product-select');
            const qtyInput = row.querySelector('.qty-input');
            const priceInput = row.querySelector('.price-input');
            const subtotalDisplay = row.querySelector('.subtotal-display');

            const selectedOption = select.options[select.selectedIndex];
            const stock = selectedOption && selectedOption.value ? parseInt(selectedOption.getAttribute('data-stock') || '0', 10) : 0;

            const qty = parseInt(qtyInput.value, 10) || 0;
            const price = parseFloat(priceInput.value) || 0.0;

            if (selectedOption && selectedOption.value && qty > stock) {
                qtyInput.classList.add('border-rose-500', 'bg-rose-50');
                hasError = true;
            } else {
                qtyInput.classList.remove('border-rose-500', 'bg-rose-50');
            }

            const lineTotal = Math.max(0, qty * price);
            subtotalDisplay.innerText = `₱${lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

            grandQty += qty;
            grandTotal += lineTotal;
        });

        document.getElementById('grandQty').innerText = `${grandQty} unit${grandQty === 1 ? '' : 's'}`;
        document.getElementById('grandTotal').innerText = `₱${grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        const submitBtn = document.getElementById('submitBtn');
        if (grandQty <= 0 || grandTotal < 0 || hasError) {
            submitBtn.disabled = true;
        } else {
            submitBtn.disabled = false;
        }
    }

    // Initialize first row
    document.addEventListener('DOMContentLoaded', function() {
        addProductRow();
    });
</script>
@endsection
