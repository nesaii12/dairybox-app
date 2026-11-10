/**
 * DairyBox - Professional Fly-to-Cart Animation & Live Counter
 */

document.addEventListener('DOMContentLoaded', () => {
    initFlyToCart();
});

function initFlyToCart() {
    // Delegated click handlers for product card Milk Type & Size customization pills
    document.addEventListener('click', (e) => {
        const optionBtn = e.target.closest('button[data-card-option]');
        if (!optionBtn || optionBtn.disabled) return;

        e.preventDefault();
        const optionType = optionBtn.getAttribute('data-card-option');
        const value = optionBtn.getAttribute('data-value');
        const card = optionBtn.closest('.group, [data-record-id]') || optionBtn.parentElement.parentElement;
        if (!card) return;

        if (optionType === 'milk') {
            card.querySelectorAll('.card-milk-pill').forEach(btn => {
                btn.className = 'card-milk-pill px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer font-semibold bg-white text-slate-700 border border-slate-200 hover:border-sky-400 hover:text-sky-600';
            });
            optionBtn.className = 'card-milk-pill px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer font-bold bg-sky-600 text-white border border-sky-600 shadow-2xs';
            const milkInput = card.querySelector('.card-selected-milk');
            if (milkInput) milkInput.value = value;
        } else if (optionType === 'size') {
            card.querySelectorAll('.card-size-pill').forEach(btn => {
                btn.className = 'card-size-pill px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer font-semibold bg-white text-slate-700 border border-slate-200 hover:border-sky-400 hover:text-sky-600';
            });
            optionBtn.className = 'card-size-pill px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer font-bold bg-sky-600 text-white border border-sky-600 shadow-2xs';
            const sizeInput = card.querySelector('.card-selected-size');
            if (sizeInput) sizeInput.value = value;
        }
    });

    // Delegated quantity stepper handlers for catalog/landing product cards
    document.addEventListener('click', (e) => {
        const minusBtn = e.target.closest('.catalog-qty-minus');
        const plusBtn = e.target.closest('.catalog-qty-plus');

        if (!minusBtn && !plusBtn) return;

        const form = (minusBtn || plusBtn).closest('form.add-to-cart-form');
        if (!form) return;

        const input = form.querySelector('.catalog-qty-input');
        if (!input) return;

        e.preventDefault();
        const maxAllowed = parseInt(input.max, 10) || parseInt(form.dataset.stock, 10) || 1;
        let current = parseInt(input.value, 10) || 1;

        if (minusBtn) {
            if (current > 1) {
                input.value = current - 1;
            }
        } else if (plusBtn) {
            if (current < maxAllowed) {
                input.value = current + 1;
            } else if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stock Limit',
                    text: `Only ${maxAllowed} more unit(s) available to add.`,
                    timer: 2000,
                    showConfirmButton: false,
                    customClass: { popup: 'rounded-2xl border border-slate-100 shadow-xl' }
                });
            }
        }
    });

    document.addEventListener('input', (e) => {
        const input = e.target.closest('.catalog-qty-input');
        if (!input) return;

        const maxAllowed = parseInt(input.max, 10) || 1;
        let val = parseInt(input.value, 10);
        if (val > maxAllowed) {
            input.value = maxAllowed;
        } else if (val < 1 && input.value !== '') {
            input.value = 1;
        }
    });

    document.addEventListener('blur', (e) => {
        if (!e.target || !e.target.classList || !e.target.classList.contains('catalog-qty-input')) return;
        const input = e.target;
        const maxAllowed = parseInt(input.max, 10) || 1;
        let val = parseInt(input.value, 10);
        if (isNaN(val) || val < 1) input.value = 1;
        if (val > maxAllowed) input.value = maxAllowed;
    }, true);

    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('form.add-to-cart-form, form[action*="/customer/cart/add"]');
        if (!form) return;

        // Ensure this is an add-to-cart action
        if (!form.action || !form.action.includes('/customer/cart/add')) return;

        // Prevent default navigation to cart page
        e.preventDefault();

        // Prevent concurrent double submissions
        if (form.dataset.submitting === 'true') return;
        form.dataset.submitting = 'true';

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

        // Add subtle loading state on the button
        if (submitBtn) {
            submitBtn.style.pointerEvents = 'none';
            submitBtn.classList.add('opacity-80', 'scale-95');
        }

        // Find visual source element (product image or button)
        const productCard = form.closest('.record-highlight-target, .group, [data-record-id]') || form.closest('.grid > div') || form.parentElement;
        const mainDetailImg = document.getElementById('productMainImage') || document.querySelector('.product-main-img');
        const cardImg = productCard ? productCard.querySelector('img') : null;
        const sourceImg = mainDetailImg || cardImg;
        const sourceElement = (sourceImg && sourceImg.getBoundingClientRect().width > 20) ? sourceImg : submitBtn;

        // Find target cart icon in the navbar
        const cartBtn = document.getElementById('navCartButton') || document.querySelector('.nav-cart-btn') || document.querySelector('a[href*="/customer/cart"]');

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Run the flying animation ONLY on verified successful addition
                if (sourceElement && cartBtn) {
                    animateFlyToCart(sourceElement, cartBtn);
                }

                if (data.current_in_cart !== undefined) {
                    form.dataset.inCart = data.current_in_cart;
                }

                const catalogInput = form.querySelector('.catalog-qty-input');
                if (catalogInput && data.available_left !== undefined) {
                    catalogInput.max = Math.max(1, data.available_left);
                    catalogInput.value = 1;
                }

                if (typeof window.updateProductDetailStockState === 'function' && data.available_left !== undefined) {
                    window.updateProductDetailStockState(data.available_left, data.current_in_cart || 0);
                }

                // If no more units are available to add, update button to show max reached
                if (data.available_left === 0 && submitBtn) {
                    const stepper = form.querySelector('.catalog-qty-stepper');
                    if (stepper) stepper.classList.add('hidden');
                    submitBtn.disabled = true;
                    submitBtn.classList.remove('bg-[#0ea5e9]', 'hover:bg-sky-600', 'text-white');
                    submitBtn.classList.add('bg-sky-50', 'text-sky-600', 'border', 'border-sky-200', 'cursor-not-allowed');
                    submitBtn.title = 'All available units are now in your cart';
                    submitBtn.innerHTML = '<i class="bi bi-check2 text-base font-bold"></i>';
                }

                // Wait for the flyer to land before triggering cart bounce & badge update
                setTimeout(() => {
                    // 1. Bounce cart icon
                    bounceCartIcon(cartBtn);

                    // 2. Update cart badges
                    updateCartBadges(data.cart_count);

                    // 3. Show sleek floating toast notification with Selection Batch info
                    showFlyCartToast(
                        data.product_name,
                        data.cart_count,
                        data.product_image,
                        data.batch_number,
                        data.added_quantity
                    );
                }, 600);
            } else {
                // If restricted or stock limit error
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: response.status === 403 ? 'Ordering Restricted' : 'Stock Limit',
                        text: data.message || 'Cannot add more units than available in stock.',
                        confirmButtonColor: '#0ea5e9',
                        customClass: {
                            popup: 'rounded-2xl border border-slate-100 shadow-xl'
                        }
                    });
                } else {
                    alert(data.message || 'Cannot add more units than available in stock.');
                }
            }
        } catch (error) {
            console.error('Add to cart failed:', error);
            // Fallback: If network fails or non-JSON returned, submit normally
            form.submit();
        } finally {
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.style.pointerEvents = '';
                submitBtn.classList.remove('opacity-80', 'scale-95');
                submitBtn.innerHTML = originalBtnHtml;
            }
            delete form.dataset.submitting;
        }
    });
}

/**
 * Creates and animates the flying product thumbnail into the navbar cart
 */
function animateFlyToCart(sourceEl, targetCartEl) {
    const sourceRect = sourceEl.getBoundingClientRect();
    const targetRect = targetCartEl.getBoundingClientRect();

    const startX = sourceRect.left + sourceRect.width / 2;
    const startY = sourceRect.top + sourceRect.height / 2;
    const targetX = targetRect.left + targetRect.width / 2;
    const targetY = targetRect.top + targetRect.height / 2;

    const flyer = document.createElement('div');
    flyer.className = 'fly-to-cart-thumbnail';

    // Style flyer capsule
    const size = Math.min(Math.max(sourceRect.width * 0.4, 52), 80);
    Object.assign(flyer.style, {
        position: 'fixed',
        zIndex: '999999',
        pointerEvents: 'none',
        width: `${size}px`,
        height: `${size}px`,
        left: `${startX - size / 2}px`,
        top: `${startY - size / 2}px`,
        borderRadius: '50%',
        overflow: 'hidden',
        border: '2.5px solid #0ea5e9',
        boxShadow: '0 12px 30px -4px rgba(14, 165, 233, 0.55), 0 0 15px rgba(56, 189, 248, 0.45)',
        backgroundColor: '#ffffff',
        transform: 'translate(0px, 0px) scale(1) rotate(0deg)',
        opacity: '1',
        transition: 'transform 0.65s cubic-bezier(0.2, 0.85, 0.25, 1), opacity 0.65s cubic-bezier(0.4, 0, 1, 1), scale 0.65s cubic-bezier(0.2, 0.85, 0.25, 1)',
        willChange: 'transform, opacity'
    });

    if (sourceEl.tagName === 'IMG' && sourceEl.src) {
        flyer.innerHTML = `<img src="${sourceEl.src}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
    } else {
        flyer.innerHTML = `<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white;"><i class="bi bi-cart-plus-fill" style="font-size: 1.35rem;"></i></div>`;
    }

    document.body.appendChild(flyer);

    // Trigger curve transition in next animation frames
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            const deltaX = targetX - startX;
            const deltaY = targetY - startY;

            flyer.style.transform = `translate(${deltaX}px, ${deltaY}px) scale(0.22) rotate(18deg)`;
            flyer.style.opacity = '0.35';
        });
    });

    // Remove flyer upon landing
    setTimeout(() => {
        if (flyer.parentElement) {
            flyer.remove();
        }
    }, 700);
}

/**
 * Triggers a satisfying pop/bounce animation on the cart icon
 */
function bounceCartIcon(cartBtn) {
    if (!cartBtn) return;
    cartBtn.classList.remove('cart-icon-pop');
    void cartBtn.offsetWidth; // Force reflow
    cartBtn.classList.add('cart-icon-pop');

    setTimeout(() => {
        cartBtn.classList.remove('cart-icon-pop');
    }, 650);
}

/**
 * Updates all cart badge counters in headers and navbars
 */
function updateCartBadges(newCount) {
    document.querySelectorAll('.cart-nav-badge').forEach(badge => {
        badge.textContent = newCount;
        if (newCount > 0) {
            badge.classList.remove('hidden');
        }
        badge.classList.remove('cart-badge-pop');
        void badge.offsetWidth; // Force reflow
        badge.classList.add('cart-badge-pop');

        setTimeout(() => {
            badge.classList.remove('cart-badge-pop');
        }, 550);
    });
}

/**
 * Displays a sleek, non-blocking floating toast in the top right
 */
function showFlyCartToast(productName, cartCount, productImgUrl, batchNumber, addedQty) {
    let container = document.getElementById('dairybox-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'dairybox-toast-container';
        container.className = 'fixed top-20 right-4 sm:right-6 z-[99999] flex flex-col items-end space-y-2 pointer-events-none';
        document.body.appendChild(container);
    }

    const batchLabel = batchNumber ? `Cart #${batchNumber}` : 'Cart';
    const qtyPrefix = (addedQty && addedQty > 1) ? `${addedQty}x ` : '';

    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto flex items-center gap-3 bg-white/95 backdrop-blur-md px-4 py-3 rounded-2xl border border-sky-100 shadow-xl shadow-sky-500/10 toast-enter max-w-sm transition-all';
    toast.innerHTML = `
        <div class="w-10 h-10 rounded-xl bg-sky-50 border border-sky-100/80 flex items-center justify-center flex-shrink-0 overflow-hidden text-sky-600">
            ${productImgUrl ? `<img src="${productImgUrl}" alt="" class="w-full h-full object-cover">` : `<i class="bi bi-cart-check-fill text-xl text-sky-600"></i>`}
        </div>
        <div class="min-w-0 pr-1">
            <h4 class="text-xs font-bold text-slate-900 truncate">${qtyPrefix}${productName}</h4>
            <p class="text-[11px] text-slate-500 font-medium">Added to <span class="font-bold text-sky-600">${batchLabel}</span> &bull; ${cartCount} ${cartCount === 1 ? 'unit' : 'units'} in cart</p>
        </div>
        <a href="/customer/cart" class="text-xs font-bold text-sky-600 hover:text-sky-700 bg-sky-50 hover:bg-sky-100/80 px-3 py-1.5 rounded-xl transition-all flex items-center gap-1 flex-shrink-0 cursor-pointer">
            <span>View</span>
            <i class="bi bi-arrow-right text-xs"></i>
        </a>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('toast-enter');
        toast.classList.add('toast-exit');
        setTimeout(() => toast.remove(), 320);
    }, 3200);
}

export { initFlyToCart };
