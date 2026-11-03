@extends('layouts.customer')

@section('title', 'My Shopping Cart')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-black text-slate-900">Shopping Cart</h2>
    <p class="text-xs text-slate-500">Review your selected items and adjust quantities before placing order.</p>
</div>

@if($items->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 p-12 text-center shadow-sm max-w-lg mx-auto mt-12">
        <i class="bi bi-cart-x text-5xl text-slate-300 mb-4 block"></i>
        <h3 class="text-base font-bold text-slate-800">Your Cart is Empty</h3>
        <p class="text-xs text-slate-500 mt-1">Explore our delicious milk flavors and ice creams to add them here.</p>
        <a href="{{ route('customer.catalog') }}" class="mt-6 inline-flex items-center space-x-2 py-2.5 px-5 rounded-xl text-xs font-bold text-white bg-sky-500 hover:bg-sky-600 transition-all shadow-md shadow-sky-100">
            <i class="bi bi-shop"></i>
            <span>Browse Products</span>
        </a>
    </div>
@else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Items List (Left Columns) -->
        <div class="lg:col-span-2 space-y-4">
            @foreach($items as $item)
                <div class="cart-item-row bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4" id="cart-item-{{ $item->id }}">
                    <!-- Product Thumb & Names -->
                    <div class="flex items-center space-x-4 w-full sm:w-auto">
                        <div class="h-16 w-16 rounded-xl overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0">
                            @if($item->product->image)
                                <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->product_name }}" class="h-full w-full object-cover">
                            @else
                                <i class="bi bi-image text-slate-300 text-xl"></i>
                            @endif
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 hover:text-sky-600">
                                <a href="{{ route('customer.product', $item->product->id) }}">{{ $item->product->product_name }}</a>
                            </h4>
                            <div class="flex flex-wrap gap-1.5 mt-1 items-center">
                                <span class="bg-slate-50 border border-slate-100 text-slate-600 text-[9px] px-2 py-0.5 rounded font-bold uppercase">{{ $item->product->milk_type }} Milk</span>
                                <span class="bg-slate-50 border border-slate-100 text-slate-600 text-[9px] px-2 py-0.5 rounded font-bold uppercase">Size: {{ $item->product->size }}</span>
                                @if($item->product->manufacturing_date)
                                    <span class="bg-sky-50 border border-sky-100 text-sky-700 text-[9px] px-2 py-0.5 rounded font-medium">Mfg: {{ \Carbon\Carbon::parse($item->product->manufacturing_date)->format('M d, Y') }}</span>
                                @endif
                                @if($item->product->expiration_date)
                                    <span class="bg-amber-50 border border-amber-100 text-amber-700 text-[9px] px-2 py-0.5 rounded font-medium">Exp: {{ \Carbon\Carbon::parse($item->product->expiration_date)->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Adjust Controls -->
                    <div class="flex items-center justify-between sm:justify-start gap-6 w-full sm:w-auto border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-50">
                        <div class="text-left sm:text-right">
                            <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider">Unit Price</span>
                            <span class="text-sm font-bold text-slate-900">₱{{ number_format($item->product->price, 2) }}</span>
                            @if($item->product->stock <= 0)
                                <span class="block text-[9px] font-bold text-rose-600">Out of stock</span>
                            @elseif($item->product->stock <= 5)
                                <span class="block text-[9px] font-bold text-amber-600">Only {{ $item->product->stock }} left</span>
                            @else
                                <span class="block text-[9px] text-slate-400 font-medium">{{ $item->product->stock }} in stock</span>
                            @endif
                        </div>
                        
                        <!-- Form with inline post submission / AJAX handling -->
                        <form action="{{ route('customer.cart.update', $item->id) }}" method="POST" class="cart-qty-form flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50 w-28 justify-between focus-within:border-sky-400 focus-within:ring-1 focus-within:ring-sky-400 transition-all" data-item-id="{{ $item->id }}" data-stock="{{ $item->product->stock }}">
                            @csrf
                            <button type="button" class="btn-qty-minus px-2 py-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 transition-colors flex-shrink-0" {{ $item->quantity <= 1 ? 'disabled' : '' }} title="Decrease">
                                <i class="bi bi-dash pointer-events-none"></i>
                            </button>
                            <input type="number" name="quantity" value="{{ min($item->quantity, $item->product->stock) }}" min="1" max="{{ $item->product->stock }}"
                                class="qty-input w-full min-w-0 text-center text-xs font-bold text-slate-900 bg-transparent border-0 p-0 focus:ring-0 focus:outline-none appearance-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                autocomplete="off"
                                title="Enter quantity">
                            <button type="button" class="btn-qty-plus px-2 py-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 transition-colors flex-shrink-0" {{ $item->product->stock <= $item->quantity ? 'disabled' : '' }} title="Increase">
                                <i class="bi bi-plus pointer-events-none"></i>
                            </button>
                        </form>

                        <div class="text-right min-w-[70px]">
                            <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider">Subtotal</span>
                            <span class="item-subtotal text-sm font-black text-slate-950">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>

                        <!-- Delete button -->
                        <form action="{{ route('customer.cart.remove', $item->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all" title="Remove Item">
                                <i class="bi bi-trash3 text-base"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Order Summary Column (Right) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-24">
                <h3 class="text-base font-bold text-slate-950 mb-4 pb-3 border-b border-slate-50">Order Summary</h3>
                
                <div class="space-y-3 mb-6">
                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <span>Items Subtotal</span>
                        <span id="summary-items-subtotal" class="font-semibold text-slate-900">₱{{ number_format($total, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <span>Delivery Fee</span>
                        <span class="font-semibold text-slate-900 italic">Calculated at next step</span>
                    </div>
                </div>

                <div class="border-t border-slate-50 pt-4 mb-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900">Subtotal Amount</span>
                        <span id="summary-total-subtotal" class="text-xl font-black text-sky-600">₱{{ number_format($total, 2) }}</span>
                    </div>
                </div>

                @php
                    $hasStockExceeded = $items->contains(function($i) {
                        return $i->quantity > $i->product->stock || $i->product->stock <= 0;
                    });
                @endphp

                @if(auth()->user()->isRestricted())
                    <div class="mb-3 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700">
                        <div class="flex items-start space-x-2">
                            <i class="bi bi-shield-slash-fill text-rose-600 mt-0.5 flex-shrink-0"></i>
                            <div>
                                <span class="font-bold block text-rose-900">Ordering Restricted</span>
                                <span class="text-[11px] leading-relaxed">Your account cannot place orders until {{ auth()->user()->restricted_until->format('M d, Y h:i A') }} due to order policy restrictions.</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" disabled class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-sm font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed">
                        <i class="bi bi-lock-fill"></i>
                        <span>Checkout Restricted</span>
                    </button>
                @elseif($hasStockExceeded)
                    <div class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
                        <div class="flex items-start space-x-2">
                            <i class="bi bi-exclamation-triangle-fill text-amber-600 mt-0.5 flex-shrink-0"></i>
                            <div>
                                <span class="font-bold block text-amber-900">Stock Limit Exceeded</span>
                                <span class="text-[11px] leading-relaxed">Some items in your cart exceed available warehouse stock. Please adjust their quantities to proceed.</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" disabled class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-sm font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed">
                        <i class="bi bi-slash-circle"></i>
                        <span>Cannot Checkout (Stock Limit)</span>
                    </button>
                @else
                    <a href="{{ route('customer.checkout.index') }}" class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-sm font-black text-white bg-sky-500 hover:bg-sky-600 shadow-md shadow-sky-100 transition-all hover:-translate-y-0.5">
                        <span>Proceed to Checkout</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                @endif
                
                <div class="mt-4 text-[10px] text-slate-400 text-center font-medium">
                    Prices are tax inclusive. Double check items before continuing.
                </div>
            </div>
        </div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const qtyForms = document.querySelectorAll('.cart-qty-form');

    qtyForms.forEach(form => {
        const minusBtn = form.querySelector('.btn-qty-minus');
        const plusBtn = form.querySelector('.btn-qty-plus');
        const qtyInput = form.querySelector('.qty-input');
        const parentRow = form.closest('.cart-item-row');
        const subtotalEl = parentRow ? parentRow.querySelector('.item-subtotal') : null;
        const maxStock = parseInt(form.dataset.stock, 10);
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
                        text: `Cannot exceed available stock of ${maxStock} units.`,
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-2xl border border-slate-100 shadow-xl' }
                    });
                }
            }

            qtyInput.value = sanitizedQty;

            // If quantity hasn't changed from the last successful update, avoid redundant request
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
                    const summaryItemsSubtotal = document.getElementById('summary-items-subtotal');
                    const summaryTotalSubtotal = document.getElementById('summary-total-subtotal');
                    if (summaryItemsSubtotal) summaryItemsSubtotal.textContent = data.total;
                    if (summaryTotalSubtotal) summaryTotalSubtotal.textContent = data.total;

                    // Update navbar badge if available
                    const badge = document.querySelector('.cart-nav-badge');
                    if (badge) {
                        badge.textContent = data.cart_count;
                        if (data.cart_count > 0) {
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }

                    minusBtn.disabled = (data.quantity <= 1);
                    plusBtn.disabled = (data.quantity >= data.max_stock);
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

        // Stepper buttons
        minusBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const current = parseInt(qtyInput.value, 10) || 1;
            if (current > 1) {
                updateQuantity(current - 1);
            }
        });

        plusBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const current = parseInt(qtyInput.value, 10) || 1;
            if (current < maxStock) {
                updateQuantity(current + 1);
            }
        });

        // Quick input interaction: auto-select on focus
        qtyInput.addEventListener('focus', () => {
            qtyInput.select();
        });

        // Real-time stock limit clamping on input
        qtyInput.addEventListener('input', () => {
            let val = parseInt(qtyInput.value, 10);
            if (val > maxStock) {
                qtyInput.value = maxStock;
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
            } else if (val < 1 && qtyInput.value !== '') {
                qtyInput.value = 1;
            }
        });

        // Handle Enter key on input
        qtyInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                qtyInput.blur();
            }
        });

        // Handle blur to commit value
        qtyInput.addEventListener('blur', () => {
            updateQuantity(qtyInput.value);
        });

        // Prevent standard form submit reload
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            updateQuantity(qtyInput.value);
        });
    });
});
</script>
@endsection
