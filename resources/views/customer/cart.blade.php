@extends('layouts.customer')

@section('title', 'My Shopping Cart')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-black text-slate-900">Shopping Cart</h2>
        <p class="text-xs text-slate-500">Review your carts and adjust quantities before placing your order.</p>
    </div>

    @if($batches->isNotEmpty())
        <div class="flex items-center gap-2.5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold">
                <i class="bi bi-layers text-sky-600"></i>
                <span>{{ $batches->count() }} {{ \Illuminate\Support\Str::plural('Cart', $batches->count()) }}</span>
            </span>
            <a href="{{ route('customer.catalog', ['new_batch' => 1]) }}" class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200/80 transition-all shadow-2xs">
                <i class="bi bi-plus-circle-fill text-sky-600"></i>
                <span>New Cart</span>
            </a>
        </div>
    @endif
</div>

@if($batches->isEmpty())
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
    <div class="space-y-6">
        @foreach($batches as $batch)
            @php
                $batchNumber = $batch->cart_number ?? ($batches->count() - $loop->index);
                $batchSubtotal = $batch->cartItems->sum('subtotal');
                $batchUnitCount = $batch->cartItems->sum('quantity');
                $batchItemCount = $batch->cartItems->count();
                $batchHasStockExceeded = $batch->cartItems->contains(function ($i) {
                    return !$i->product || $i->product->stock <= 0 || $i->quantity > $i->product->stock;
                });
                $latestActivity = $batch->updated_at ?? $batch->created_at;
            @endphp

            <!-- Distinct Cart Container Box -->
            <div class="cart-batch-box bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden transition-all" id="cart-batch-{{ $batch->id }}" data-batch-id="{{ $batch->id }}">
                
                <!-- Cart Header Bar -->
                <div class="bg-slate-50/90 border-b border-slate-200/80 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#0284c7] text-white text-xs font-black tracking-wide shadow-2xs">
                            <i class="bi bi-box-seam text-[11px]"></i>
                            <span>#{{ $batchNumber }}</span>
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-800">
                                    Cart {{ $batchNumber }} &mdash; Added {{ $latestActivity ? $latestActivity->diffForHumans() : 'recently' }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-semibold text-slate-600">
                                    {{ $batchItemCount }} {{ \Illuminate\Support\Str::plural('item', $batchItemCount) }} &bull;
                                    <span class="batch-unit-count ml-0.5" data-batch-id="{{ $batch->id }}">{{ $batchUnitCount }}</span>&nbsp;{{ \Illuminate\Support\Str::plural('unit', $batchUnitCount) }}
                                </span>
                            </div>
                            @if($latestActivity)
                                <span class="block text-[10px] text-slate-400 font-medium mt-0.5">
                                    Recorded {{ $latestActivity->format('M d, Y h:i A') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Cart Action Controls -->
                    <div class="flex items-center gap-2">
                        <a href="{{ route('customer.catalog', ['batch' => $batch->id]) }}"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-[11px] font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200/70 transition-all"
                           title="Add more products to Cart #{{ $batchNumber }}">
                            <i class="bi bi-plus-lg text-[10px]"></i>
                            <span>Add to Cart</span>
                        </a>

                        <button type="button"
                                onclick="focusBatchQuantities({{ $batch->id }})"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-[11px] font-bold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200 transition-all cursor-pointer"
                                title="Edit quantities in Cart #{{ $batchNumber }}">
                            <i class="bi bi-pencil-square text-[10px] text-slate-500"></i>
                            <span>Edit Cart</span>
                        </button>

                        <form action="{{ route('customer.cart.removeBatch', $batch->id) }}" method="POST" onsubmit="return confirm('Remove Cart #{{ $batchNumber }} and all items inside this cart?')">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-[11px] font-bold text-rose-600 bg-rose-50/80 hover:bg-rose-100 border border-rose-200/70 transition-all cursor-pointer"
                                    title="Remove entire Cart #{{ $batchNumber }}">
                                <i class="bi bi-trash3 text-[11px]"></i>
                                <span>Remove Cart</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Batch Items List Inside the Box -->
                <div class="divide-y divide-slate-100">
                    @foreach($batch->cartItems as $item)
                        <div class="cart-item-row p-4 sm:px-6 sm:py-5 flex flex-col sm:flex-row items-center justify-between gap-4 hover:bg-slate-50/40 transition-colors"
                             id="cart-item-{{ $item->id }}"
                             data-batch-id="{{ $batch->id }}">
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
                                    @php
                                        $itemCustom = \App\Models\Product::getCartItemCustomization($item->id, $item->product);
                                    @endphp
                                    <div class="flex flex-wrap gap-1.5 mt-1 items-center">
                                        <span class="bg-slate-50 border border-slate-100 text-slate-600 text-[9px] px-2 py-0.5 rounded font-bold uppercase">{{ $itemCustom['milk_type'] }} Milk</span>
                                        <span class="bg-slate-50 border border-slate-100 text-slate-600 text-[9px] px-2 py-0.5 rounded font-bold uppercase">Size: {{ $itemCustom['size'] }}</span>
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
                            <div class="flex items-center justify-between sm:justify-start gap-5 sm:gap-8 w-full sm:w-auto border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-50">
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
                                <form action="{{ route('customer.cart.update', $item->id) }}" method="POST"
                                      class="cart-qty-form flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50 w-28 justify-between focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-400/20 transition-all"
                                      data-item-id="{{ $item->id }}"
                                      data-batch-id="{{ $batch->id }}"
                                      data-stock="{{ $item->product->stock }}">
                                    @csrf
                                    <button type="button" class="btn-qty-minus px-2 py-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 transition-colors flex-shrink-0 cursor-pointer" {{ $item->quantity <= 1 ? 'disabled' : '' }} title="Decrease">
                                        <i class="bi bi-dash pointer-events-none"></i>
                                    </button>
                                    <input type="number" name="quantity" value="{{ min($item->quantity, max(1, $item->product->stock)) }}" min="1" max="{{ max(1, $item->product->stock) }}"
                                        class="qty-input w-full min-w-0 text-center text-xs font-bold text-slate-900 bg-transparent border-0 p-0 focus:ring-0 focus:outline-none appearance-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        autocomplete="off"
                                        title="Enter quantity">
                                    <button type="button" class="btn-qty-plus px-2 py-1 text-slate-500 hover:text-slate-900 disabled:opacity-30 transition-colors flex-shrink-0 cursor-pointer" {{ $item->product->stock <= $item->quantity ? 'disabled' : '' }} title="Increase">
                                        <i class="bi bi-plus pointer-events-none"></i>
                                    </button>
                                </form>

                                <div class="text-right min-w-[80px]">
                                    <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider">Subtotal</span>
                                    <span class="item-subtotal text-sm font-black text-slate-950">₱{{ number_format($item->subtotal, 2) }}</span>
                                </div>

                                <!-- Remove Single Item button -->
                                <form action="{{ route('customer.cart.remove', $item->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all cursor-pointer" title="Remove Item from Cart #{{ $batchNumber }}">
                                        <i class="bi bi-trash3 text-base"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Cart Footer with Individual Cart Subtotal & Prominent "Checkout Cart" Button -->
                <div class="bg-slate-50/70 border-t border-slate-100 px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <span class="text-xs text-slate-500 font-semibold">
                        #{{ $batchNumber }} Total (<span class="batch-unit-count font-bold text-slate-700" data-batch-id="{{ $batch->id }}">{{ $batchUnitCount }}</span> units)
                    </span>

                    <div class="flex flex-wrap items-center justify-between sm:justify-end gap-4 sm:gap-5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cart Subtotal:</span>
                            <span class="batch-subtotal text-lg font-black text-sky-600" data-batch-id="{{ $batch->id }}">₱{{ number_format($batchSubtotal, 2) }}</span>
                        </div>

                        @if(auth()->user()->isRestricted())
                            <button type="button" disabled
                                    class="inline-flex items-center space-x-2 py-2.5 px-5 rounded-xl text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed"
                                    title="Ordering restricted until {{ auth()->user()->restricted_until->format('M d, Y h:i A') }}">
                                <i class="bi bi-lock-fill"></i>
                                <span>Checkout Restricted</span>
                            </button>
                        @elseif($batchHasStockExceeded)
                            <button type="button" disabled
                                    class="inline-flex items-center space-x-2 py-2.5 px-5 rounded-xl text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 cursor-not-allowed"
                                    title="Adjust item quantities to match available warehouse stock">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <span>Stock Limit Exceeded</span>
                            </button>
                        @else
                            <a href="{{ route('customer.checkout.index', ['batch' => $batch->id]) }}"
                               class="inline-flex items-center space-x-2 py-2.5 px-5 rounded-xl text-xs font-black text-white bg-sky-500 hover:bg-sky-600 shadow-md shadow-sky-500/20 transition-all hover:-translate-y-0.5 cursor-pointer">
                                <span>Checkout Cart</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<script>
function focusBatchQuantities(batchId) {
    const batchBox = document.getElementById(`cart-batch-${batchId}`);
    if (!batchBox) return;

    batchBox.classList.add('ring-2', 'ring-sky-400', 'border-sky-400');
    setTimeout(() => {
        batchBox.classList.remove('ring-2', 'ring-sky-400', 'border-sky-400');
    }, 1800);

    const firstInput = batchBox.querySelector('.qty-input');
    if (firstInput) {
        firstInput.focus();
        firstInput.select();
    }
}

document.addEventListener('DOMContentLoaded', () => {
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
                        text: `Cannot exceed available stock of ${maxStock} units.`,
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

                    // Update this Selection Batch's Subtotal & Unit Count in the batch header & footer
                    if (data.batch_id) {
                        document.querySelectorAll(`.batch-subtotal[data-batch-id="${data.batch_id}"]`).forEach(el => {
                            el.textContent = data.batch_subtotal;
                        });
                        document.querySelectorAll(`.batch-unit-count[data-batch-id="${data.batch_id}"]`).forEach(el => {
                            el.textContent = data.batch_unit_count;
                        });
                    }

                    // Update navbar badges if available
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
