@extends('layouts.customer')

@section('title', 'My Shopping Cart')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-black text-slate-900">Shopping Cart</h2>
        <p class="text-xs text-slate-500">Review your products, adjust quantities, and select items to checkout.</p>
    </div>

    @if($items->isNotEmpty())
        <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold">
                <i class="bi bi-cart-check text-sky-600"></i>
                <span><span id="totalItemsHeaderCount">{{ $items->count() }}</span> {{ \Illuminate\Support\Str::plural('Product', $items->count()) }}</span>
            </span>
            <a href="{{ route('customer.catalog') }}" class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200/80 transition-all shadow-2xs">
                <i class="bi bi-shop text-sky-600"></i>
                <span>Add More Products</span>
            </a>
        </div>
    @endif
</div>

@if($items->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 p-12 text-center shadow-sm max-w-lg mx-auto mt-12">
        <i class="bi bi-cart-x text-5xl text-slate-300 mb-4 block"></i>
        <h3 class="text-base font-bold text-slate-800">Your Cart is Empty</h3>
        <p class="text-xs text-slate-500 mt-1">Explore our delicious fresh milks, yogurts, and ice creams to add them to your cart.</p>
        <a href="{{ route('customer.catalog') }}" class="mt-6 inline-flex items-center space-x-2 py-2.5 px-5 rounded-xl text-xs font-bold text-white bg-sky-500 hover:bg-sky-600 transition-all shadow-md shadow-sky-100">
            <i class="bi bi-shop"></i>
            <span>Browse Products</span>
        </a>
    </div>
@else
    <!-- Consolidated Merged Shopping Cart Layout -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden mb-8">
        
        <!-- Cart Table Header Bar with Select All -->
        <div class="bg-slate-50/90 border-b border-slate-200/80 px-4 sm:px-6 py-3.5 flex items-center justify-between gap-3">
            <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                <input type="checkbox" id="selectAllCheckbox" checked class="h-4.5 w-4.5 rounded text-sky-600 focus:ring-sky-500 border-slate-300 cursor-pointer">
                <span class="text-xs font-bold text-slate-800">Select All (<span id="selectAllLabelCount">{{ $items->count() }}</span> items)</span>
            </label>
            <span class="text-[11px] text-slate-400 font-medium hidden sm:inline">Choose specific products to checkout</span>
        </div>

        <!-- Merged Product Items List -->
        <div class="divide-y divide-slate-100" id="cartItemsList">
            @foreach($items as $item)
                @php
                    $itemCustom = \App\Models\Product::getCartItemCustomization($item->id, $item->product);
                    $itemAvailStock = $item->product ? $item->product->available_stock : 0;
                    $isExpired = $item->product?->expiration_date && \Carbon\Carbon::parse($item->product->expiration_date)->startOfDay()->lt(now()->startOfDay());
                    $isOutOfStock = !$item->product || $itemAvailStock <= 0;
                    $isItemUnavailable = $isExpired || $isOutOfStock || ($item->product?->status !== 'active');
                @endphp
                <div class="cart-item-row p-4 sm:px-6 sm:py-5 flex flex-col sm:flex-row items-center justify-between gap-4 hover:bg-slate-50/40 transition-colors {{ $isItemUnavailable ? 'opacity-70 bg-slate-50/30' : '' }}"
                     id="cart-item-{{ $item->id }}"
                     data-item-id="{{ $item->id }}"
                     data-subtotal="{{ (float) $item->subtotal }}"
                     data-qty="{{ $item->quantity }}"
                     data-unavailable="{{ $isItemUnavailable ? '1' : '0' }}">
                    
                    <!-- Checkbox, Image & Product Details -->
                    <div class="flex items-center space-x-3.5 w-full sm:w-auto flex-1 min-w-0">
                        <!-- Checkbox to select specific product -->
                        <div class="flex items-center flex-shrink-0">
                            <input type="checkbox"
                                   name="selected_items[]"
                                   value="{{ $item->id }}"
                                   class="item-checkbox h-4.5 w-4.5 rounded text-sky-600 focus:ring-sky-500 border-slate-300 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                   {{ $isItemUnavailable ? 'disabled' : 'checked' }}
                                   data-id="{{ $item->id }}"
                                   data-subtotal="{{ (float) $item->subtotal }}"
                                   data-qty="{{ $item->quantity }}"
                                   title="{{ $isExpired ? 'Product expired' : ($isOutOfStock ? 'Out of stock' : 'Select product for checkout') }}">
                        </div>

                        <!-- Product Thumbnail -->
                        <a href="{{ route('customer.product', $item->product->id) }}" class="h-16 w-16 rounded-xl overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0 hover:opacity-90 transition-opacity">
                            @if($item->product->image)
                                <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->product_name }}" class="h-full w-full object-cover">
                            @else
                                <i class="bi bi-image text-slate-300 text-xl"></i>
                            @endif
                        </a>

                        <!-- Name and Customization Options -->
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-slate-900 hover:text-sky-600 truncate">
                                <a href="{{ route('customer.product', $item->product->id) }}">{{ $item->product->product_name }}</a>
                            </h4>
                            <div class="flex flex-wrap gap-1.5 mt-1 items-center">
                                <span class="bg-slate-100 border border-slate-200/70 text-slate-700 text-[9px] px-2 py-0.5 rounded-md font-bold uppercase">{{ $itemCustom['milk_type'] }} Milk</span>
                                <span class="bg-sky-50 border border-sky-200/70 text-sky-800 text-[9px] px-2 py-0.5 rounded-md font-bold uppercase">Size: {{ $itemCustom['size'] }}</span>
                                @if($item->product->manufacturing_date)
                                    <span class="bg-slate-50 border border-slate-100 text-slate-500 text-[9px] px-2 py-0.5 rounded font-medium">Mfg: {{ \Carbon\Carbon::parse($item->product->manufacturing_date)->format('M d, Y') }}</span>
                                @endif
                                @if($item->product->expiration_date)
                                    <span class="bg-amber-50 border border-amber-100 text-amber-700 text-[9px] px-2 py-0.5 rounded font-medium">Exp: {{ \Carbon\Carbon::parse($item->product->expiration_date)->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Unit Price, Stepper, Subtotal, Remove Action -->
                    <div class="flex items-center justify-between sm:justify-end gap-4 sm:gap-6 w-full sm:w-auto border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100">
                        <!-- Unit Price -->
                        <div class="text-left sm:text-right min-w-[70px]">
                            <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider">Unit Price</span>
                            <span class="text-sm font-bold text-slate-900">₱{{ number_format($item->unit_price, 2) }}</span>
                            @if($isExpired)
                                <span class="block text-[9px] font-bold text-rose-600">Expired</span>
                            @elseif($isOutOfStock)
                                <span class="block text-[9px] font-bold text-rose-600">Out of stock</span>
                            @elseif($itemAvailStock <= 5)
                                <span class="block text-[9px] font-bold text-amber-600">Only {{ $itemAvailStock }} left</span>
                            @else
                                <span class="block text-[9px] text-slate-400 font-medium">{{ $itemAvailStock }} in stock</span>
                            @endif
                        </div>

                        <!-- Stepper Form -->
                        <form action="{{ route('customer.cart.update', $item->id) }}" method="POST"
                              class="cart-qty-form flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50 w-28 justify-between focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-400/20 transition-all"
                              data-item-id="{{ $item->id }}"
                              data-stock="{{ $itemAvailStock }}">
                            @csrf
                            <button type="button" class="btn-qty-minus px-2 py-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 transition-colors flex-shrink-0 cursor-pointer" {{ $item->quantity <= 1 ? 'disabled' : '' }} title="Decrease">
                                <i class="bi bi-dash pointer-events-none"></i>
                            </button>
                            <input type="number" name="quantity" value="{{ min($item->quantity, max(1, $itemAvailStock)) }}" min="1" max="{{ max(1, $itemAvailStock) }}"
                                class="qty-input w-full min-w-0 text-center text-xs font-bold text-slate-900 bg-transparent border-0 p-0 focus:ring-0 focus:outline-none appearance-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                autocomplete="off"
                                title="Enter quantity">
                            <button type="button" class="btn-qty-plus px-2 py-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 transition-colors flex-shrink-0 cursor-pointer" {{ $itemAvailStock <= $item->quantity ? 'disabled' : '' }} title="Increase">
                                <i class="bi bi-plus pointer-events-none"></i>
                            </button>
                        </form>

                        <!-- Subtotal -->
                        <div class="text-right min-w-[80px]">
                            <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider">Subtotal</span>
                            <span class="item-subtotal text-sm font-black text-slate-950">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>

                        <!-- Remove Item Button -->
                        <form action="{{ route('customer.cart.remove', $item->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all cursor-pointer" title="Remove item from cart">
                                <i class="bi bi-trash3 text-base"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sticky / Bottom Consolidated Checkout Bar -->
        <div class="bg-slate-50/95 border-t border-slate-200/90 px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sticky bottom-0 z-20 shadow-md">
            <!-- Left: Checkbox & Count Info -->
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="bottomSelectAllCheckbox" checked class="h-4.5 w-4.5 rounded text-sky-600 focus:ring-sky-500 border-slate-300 cursor-pointer">
                    <span class="text-xs font-bold text-slate-700">Select All</span>
                </label>
                <span class="text-slate-300">|</span>
                <span class="text-xs text-slate-600">
                    Selected: <span id="selectedCountDisplay" class="font-bold text-slate-900">{{ $items->where('product.stock', '>', 0)->count() }}</span> of {{ $items->count() }} items
                    (<span id="selectedUnitsDisplay" class="font-bold text-slate-900">{{ $items->where('product.stock', '>', 0)->sum('quantity') }}</span> units)
                </span>
            </div>

            <!-- Right: Subtotal & Checkout Action Button -->
            <div class="flex items-center justify-between sm:justify-end gap-5">
                <div class="text-right">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Total Selected</span>
                    <span class="text-xl font-black text-sky-600" id="selectedTotalDisplay">₱{{ number_format($total, 2) }}</span>
                </div>

                @if(auth()->user()->isRestricted())
                    <button type="button" disabled
                            class="inline-flex items-center space-x-2 py-2.5 px-6 rounded-xl text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed"
                            title="Ordering restricted until {{ auth()->user()->restricted_until->format('M d, Y h:i A') }}">
                        <i class="bi bi-lock-fill"></i>
                        <span>Checkout Restricted</span>
                    </button>
                @else
                    <button type="button"
                            id="checkoutSelectedBtn"
                            onclick="proceedToCheckout()"
                            class="inline-flex items-center space-x-2 py-3 px-7 rounded-xl text-xs font-black text-white bg-sky-500 hover:bg-sky-600 shadow-md shadow-sky-500/20 transition-all hover:-translate-y-0.5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                        <span>Checkout Selected (<span id="btnSelectedCount">{{ $items->where('product.stock', '>', 0)->count() }}</span>)</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>
@endif

<script>
// Calculate and update selection statistics in real time
function updateSelection() {
    const checkedBoxes = Array.from(document.querySelectorAll('.item-checkbox:checked'));
    const totalSelectableBoxes = document.querySelectorAll('.item-checkbox:not(:disabled)');

    let totalSubtotal = 0;
    let totalUnits = 0;

    checkedBoxes.forEach(cb => {
        const row = cb.closest('.cart-item-row');
        if (row) {
            const subtotal = parseFloat(row.dataset.subtotal) || 0;
            const qty = parseInt(row.dataset.qty, 10) || 0;
            totalSubtotal += subtotal;
            totalUnits += qty;
        }
    });

    // Update count labels
    const selectedCountEl = document.getElementById('selectedCountDisplay');
    const selectedUnitsEl = document.getElementById('selectedUnitsDisplay');
    const btnCountEl = document.getElementById('btnSelectedCount');
    const totalDisplayEl = document.getElementById('selectedTotalDisplay');

    if (selectedCountEl) selectedCountEl.textContent = checkedBoxes.length;
    if (selectedUnitsEl) selectedUnitsEl.textContent = totalUnits;
    if (btnCountEl) btnCountEl.textContent = checkedBoxes.length;
    if (totalDisplayEl) {
        totalDisplayEl.textContent = '₱' + totalSubtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Sync Master Checkboxes state
    const allChecked = totalSelectableBoxes.length > 0 && checkedBoxes.length === totalSelectableBoxes.length;
    const someChecked = checkedBoxes.length > 0 && !allChecked;

    const topMaster = document.getElementById('selectAllCheckbox');
    const bottomMaster = document.getElementById('bottomSelectAllCheckbox');

    if (topMaster) {
        topMaster.checked = allChecked;
        topMaster.indeterminate = someChecked;
    }
    if (bottomMaster) {
        bottomMaster.checked = allChecked;
        bottomMaster.indeterminate = someChecked;
    }

    // Toggle checkout button state
    const checkoutBtn = document.getElementById('checkoutSelectedBtn');
    if (checkoutBtn) {
        checkoutBtn.disabled = (checkedBoxes.length === 0);
    }
}

// Master checkbox toggle handler
function toggleSelectAll(checked) {
    const checkboxes = document.querySelectorAll('.item-checkbox:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = checked;
    });
    updateSelection();
}

// Proceed to checkout with only the checked products
function proceedToCheckout() {
    const checkedBoxes = Array.from(document.querySelectorAll('.item-checkbox:checked'));
    if (checkedBoxes.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'No Products Selected',
                text: 'Please select at least one product using the checkbox to checkout.',
                confirmButtonColor: '#0ea5e9'
            });
        } else {
            alert('Please select at least one product using the checkbox to checkout.');
        }
        return;
    }

    const itemIds = checkedBoxes.map(cb => cb.value).join(',');
    window.location.href = "{{ route('customer.checkout.index') }}?items=" + encodeURIComponent(itemIds);
}

document.addEventListener('DOMContentLoaded', () => {
    // Checkbox listeners
    const topMaster = document.getElementById('selectAllCheckbox');
    const bottomMaster = document.getElementById('bottomSelectAllCheckbox');

    if (topMaster) {
        topMaster.addEventListener('change', () => toggleSelectAll(topMaster.checked));
    }
    if (bottomMaster) {
        bottomMaster.addEventListener('change', () => toggleSelectAll(bottomMaster.checked));
    }

    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });

    // Initialize selection count & total on load
    updateSelection();

    // Live AJAX quantity stepper updates
    const qtyForms = document.querySelectorAll('.cart-qty-form');

    qtyForms.forEach(form => {
        const minusBtn = form.querySelector('.btn-qty-minus');
        const plusBtn = form.querySelector('.btn-qty-plus');
        const qtyInput = form.querySelector('.qty-input');
        const parentRow = form.closest('.cart-item-row');
        const subtotalEl = parentRow ? parentRow.querySelector('.item-subtotal') : null;
        const maxStock = parseInt(form.dataset.stock, 10) || 1;
        const updateUrl = form.action;
        const csrfToken = form.querySelector('input[name="_token"]').value;

        let isUpdating = false;
        let lastCommittedQty = parseInt(qtyInput.value, 10) || 1;

        async function updateQuantity(targetQty) {
            if (isUpdating) return;

            let sanitizedQty = parseInt(targetQty, 10);
            if (isNaN(sanitizedQty) || sanitizedQty < 1) {
                sanitizedQty = 1;
            } else if (sanitizedQty > maxStock) {
                sanitizedQty = maxStock;
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Stock Limit',
                        text: `Cannot exceed available warehouse stock of ${maxStock} units.`,
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-2xl border border-slate-100 shadow-xl' }
                    });
                }
            }

            qtyInput.value = sanitizedQty;

            if (sanitizedQty === lastCommittedQty) {
                minusBtn.disabled = (sanitizedQty <= 1);
                plusBtn.disabled = (sanitizedQty >= maxStock);
                return;
            }

            isUpdating = true;
            minusBtn.disabled = true;
            plusBtn.disabled = true;
            qtyInput.disabled = true;

            try {
                const response = await fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ quantity: sanitizedQty })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    lastCommittedQty = data.quantity;
                    qtyInput.value = data.quantity;
                    if (subtotalEl) {
                        subtotalEl.textContent = data.subtotal;
                    }

                    // Update parent row dataset for instant live calculation
                    if (parentRow) {
                        const rawSubtotal = data.subtotal_raw !== undefined ? data.subtotal_raw : (data.unit_price * data.quantity);
                        parentRow.dataset.subtotal = rawSubtotal;
                        parentRow.dataset.qty = data.quantity;
                    }

                    // Update navbar cart counters
                    document.querySelectorAll('.cart-nav-badge').forEach(badge => {
                        badge.textContent = data.cart_count;
                        if (data.cart_count > 0) {
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    });

                    minusBtn.disabled = (data.quantity <= 1);
                    plusBtn.disabled = (data.quantity >= data.max_stock);

                    // Re-calculate live selected total
                    updateSelection();
                } else {
                    if (data.message && typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stock Limit',
                            text: data.message,
                            timer: 2500,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-2xl border border-slate-100 shadow-xl' }
                        });
                    }
                    qtyInput.value = lastCommittedQty;
                    minusBtn.disabled = (lastCommittedQty <= 1);
                    plusBtn.disabled = (lastCommittedQty >= maxStock);
                }
            } catch (err) {
                console.error('Cart quantity update error:', err);
                qtyInput.value = lastCommittedQty;
                minusBtn.disabled = (lastCommittedQty <= 1);
                plusBtn.disabled = (lastCommittedQty >= maxStock);
            } finally {
                qtyInput.disabled = false;
                isUpdating = false;
            }
        }

        minusBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const current = parseInt(qtyInput.value, 10) || 1;
            if (current > 1) updateQuantity(current - 1);
        });

        plusBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const current = parseInt(qtyInput.value, 10) || 1;
            if (current < maxStock) updateQuantity(current + 1);
        });

        qtyInput.addEventListener('focus', () => {
            qtyInput.select();
        });

        qtyInput.addEventListener('input', () => {
            let val = parseInt(qtyInput.value, 10);
            if (val > maxStock) {
                qtyInput.value = maxStock;
            } else if (val < 1 && qtyInput.value !== '') {
                qtyInput.value = 1;
            }
        });

        qtyInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                qtyInput.blur();
            }
        });

        qtyInput.addEventListener('blur', () => {
            updateQuantity(qtyInput.value);
        });

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            updateQuantity(qtyInput.value);
        });
    });
});
</script>
@endsection
