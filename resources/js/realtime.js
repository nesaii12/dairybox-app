/**
 * DairyBox Real-Time Live Synchronization via Laravel Reverb & Echo
 * Handles:
 * 1. Global Live Notifications & Toasts (Admin, Customer, Rider)
 * 2. Live Orders & Deliveries (Admin tables, Customer tracker, Rider queue)
 */

document.addEventListener('DOMContentLoaded', () => {
    if (!window.Echo) {
        console.warn('[Realtime] Laravel Echo is not initialized.');
        return;
    }

    const userIdMeta = document.querySelector('meta[name="user-id"]');
    const userRoleMeta = document.querySelector('meta[name="user-role"]');

    if (!userIdMeta || !userIdMeta.content) {
        return; // Guest user, no private channels needed
    }

    const userId = userIdMeta.content;
    const userRole = userRoleMeta ? userRoleMeta.content : '';

    // Status color mapping for dynamic pills
    const statusPillClasses = {
        'Pending': 'bg-amber-50 text-amber-700 border-amber-100',
        'Confirmed': 'bg-sky-50 text-sky-700 border-sky-100',
        'Preparing': 'bg-indigo-50 text-indigo-700 border-indigo-100',
        'Ready for Pickup': 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'Out for Delivery': 'bg-purple-50 text-purple-700 border-purple-100',
        'Delivered': 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
        'Cancelled': 'bg-rose-50 text-rose-600 border-rose-100',
        'Failed Delivery': 'bg-rose-50 text-rose-600 border-rose-100',
    };

    /**
     * Show an unobtrusive, humanized SweetAlert2 Toast notification
     */
    function showRealtimeToast({ title, message, url = null, icon = 'info' }) {
        if (!window.Swal) return;

        let htmlContent = `<div class="text-left text-[11px] text-slate-600 mt-0.5 leading-relaxed">${message}</div>`;
        if (url) {
            htmlContent += `<div class="text-left mt-2"><a href="${url}" class="inline-flex items-center text-[10px] font-bold text-sky-600 hover:text-sky-700">View details &rarr;</a></div>`;
        }

        window.Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: `<span class="text-xs font-bold text-slate-900">${title}</span>`,
            html: htmlContent,
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            customClass: {
                popup: 'rounded-2xl border border-slate-100 shadow-xl bg-white/95 backdrop-blur-md'
            }
        });
    }

    /**
     * Highlight a DOM element with a subtle flash effect
     */
    function flashHighlightElement(el) {
        if (!el) return;
        el.classList.add('transition-colors', 'duration-500', 'bg-sky-50/80');
        setTimeout(() => {
            el.classList.remove('bg-sky-50/80');
        }, 3000);
    }

    /* =========================================================================
     * 1. GLOBAL LIVE NOTIFICATIONS & TOASTS (App.Models.User.{id})
     * ========================================================================= */
    window.Echo.private(`App.Models.User.${userId}`)
        .listen('.notification.created', (e) => {
            const notif = e.notification;
            const unreadCount = e.unreadCount;

            // 1.1 Show toast alert
            showRealtimeToast({
                title: notif.title,
                message: notif.message,
                url: notif.target_url || notif.link,
                icon: 'info'
            });

            // 1.2 Update Admin Notification UI
            if (userRole === 'admin') {
                // Badges
                document.querySelectorAll('.admin-notif-badge').forEach(badge => {
                    badge.textContent = unreadCount;
                    if (unreadCount > 0) badge.classList.remove('hidden');
                });
                document.querySelectorAll('.admin-notif-header-badge').forEach(badge => {
                    if (unreadCount > 0) badge.classList.remove('hidden');
                });
                document.querySelectorAll('.admin-unread-count-text').forEach(el => {
                    el.textContent = unreadCount;
                });
                document.querySelectorAll('.admin-mark-all-read-form').forEach(form => {
                    if (unreadCount > 0) form.classList.remove('hidden');
                });

                // Prepend item into list
                const notifList = document.querySelector('.admin-notif-list');
                if (notifList) {
                    const emptyState = notifList.querySelector('.text-center');
                    if (emptyState) emptyState.remove();

                    const newRow = document.createElement('div');
                    newRow.className = 'group relative admin-notif-item p-3.5 flex items-start justify-between gap-3 transition-all hover:bg-sky-50/40 bg-sky-50/20';
                    newRow.dataset.id = notif.id;
                    newRow.innerHTML = `
                        <a href="/notifications/${notif.id}/go" class="flex space-x-2.5 min-w-0 flex-1 block">
                            <div class="mt-0.5 flex-shrink-0">
                                <span class="notif-dot block w-2 h-2 mt-1 rounded-full bg-sky-500 ring-4 ring-sky-100"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs truncate text-slate-900 font-bold group-hover:text-sky-600 transition-colors">
                                        ${escapeHtml(notif.title)}
                                    </h4>
                                    <i class="bi bi-arrow-right text-[10px] text-slate-300 group-hover:text-sky-600 ml-1 flex-shrink-0"></i>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed break-words">${escapeHtml(notif.message)}</p>
                                <div class="flex items-center justify-between mt-1.5">
                                    <span class="block text-[9px] text-slate-400 font-medium">Just now</span>
                                    <span class="text-[9px] font-semibold text-sky-600 opacity-0 group-hover:opacity-100 transition-opacity">View record &rarr;</span>
                                </div>
                            </div>
                        </a>
                    `;
                    notifList.prepend(newRow);
                    flashHighlightElement(newRow);
                }
            }

            // 1.3 Update Customer Notification UI
            if (userRole === 'customer') {
                const bellBtn = document.getElementById('notificationBellBtn');
                if (bellBtn) {
                    let badge = bellBtn.querySelector('.rounded-full');
                    if (!badge && unreadCount > 0) {
                        badge = document.createElement('span');
                        badge.className = 'absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white';
                        bellBtn.appendChild(badge);
                    }
                    if (badge) {
                        badge.textContent = unreadCount;
                        if (unreadCount === 0) badge.remove();
                    }
                }

                const customerList = document.querySelector('#notificationsModal .overflow-y-auto');
                if (customerList) {
                    const emptyState = customerList.querySelector('.text-slate-400');
                    if (emptyState) emptyState.remove();

                    const item = document.createElement('div');
                    item.className = 'group relative p-3.5 flex items-start justify-between gap-3 transition-all hover:bg-sky-50/40 bg-sky-50/20';
                    item.innerHTML = `
                        <a href="/notifications/${notif.id}/go" class="flex space-x-2.5 flex-1 min-w-0 pr-2 block">
                            <div class="mt-1 flex-shrink-0">
                                <span class="block w-2 h-2 rounded-full bg-sky-500 ring-4 ring-sky-100"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-900 group-hover:text-sky-600 transition-colors truncate">
                                    ${escapeHtml(notif.title)}
                                </h4>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-relaxed break-words">${escapeHtml(notif.message)}</p>
                                <span class="block text-[8px] text-slate-400 font-medium mt-1">Just now</span>
                            </div>
                        </a>
                    `;
                    customerList.prepend(item);
                    flashHighlightElement(item);
                }
            }

            // 1.4 Update Rider Notification UI
            if (userRole === 'rider') {
                const riderBell = document.getElementById('riderNotifBellBtn');
                if (riderBell) {
                    let badge = riderBell.querySelector('.rounded-full');
                    if (!badge && unreadCount > 0) {
                        badge = document.createElement('span');
                        badge.className = 'absolute top-0.5 right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white';
                        riderBell.appendChild(badge);
                    }
                    if (badge) {
                        badge.textContent = unreadCount;
                        if (unreadCount === 0) badge.remove();
                    }
                }

                const riderList = document.querySelector('#riderNotifModal .overflow-y-auto');
                if (riderList) {
                    const emptyState = riderList.querySelector('.text-slate-400');
                    if (emptyState) emptyState.remove();

                    const item = document.createElement('div');
                    item.className = 'group relative p-3 flex items-start justify-between gap-2.5 transition-all hover:bg-emerald-50/40 bg-emerald-50/20';
                    item.innerHTML = `
                        <a href="/notifications/${notif.id}/go" class="flex space-x-2 flex-1 min-w-0 block">
                            <div class="mt-0.5 flex-shrink-0">
                                <span class="block w-2 h-2 rounded-full bg-emerald-500 ring-4 ring-emerald-100"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h5 class="text-[11px] font-bold text-slate-900 group-hover:text-emerald-600 transition-colors truncate">
                                    ${escapeHtml(notif.title)}
                                </h5>
                                <p class="text-[10px] text-slate-500 mt-0.5 leading-snug break-words">${escapeHtml(notif.message)}</p>
                                <span class="block text-[8px] text-slate-400 font-medium mt-1">Just now</span>
                            </div>
                        </a>
                    `;
                    riderList.prepend(item);
                    flashHighlightElement(item);
                }
            }
        });

    /* =========================================================================
     * 2. ADMIN REALTIME: ORDERS & DELIVERIES
     * ========================================================================= */
    if (userRole === 'admin') {
        // Admin Orders Channel
        window.Echo.private('admin.orders')
            .listen('.order.updated', (e) => {
                const order = e.order;

                // If currently on /admin/orders table
                const orderRow = document.getElementById(`order-${order.id}`) ||
                    document.querySelector(`tr[data-record-id="order-${order.id}"]`);

                if (orderRow) {
                    // Update status badge pill
                    const badge = orderRow.querySelector('.order-status-badge') ||
                        orderRow.querySelector('td:nth-child(6) span');
                    if (badge) {
                        const newColor = statusPillClasses[order.order_status] || 'bg-slate-50 text-slate-600';
                        badge.className = `order-status-badge inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ${newColor}`;
                        badge.textContent = order.order_status;
                    }

                    // Update Rider cell if assigned
                    if (order.rider_name) {
                        const riderCell = orderRow.querySelector('.order-rider-name') ||
                            orderRow.querySelector('td:nth-child(7)');
                        if (riderCell) {
                            riderCell.innerHTML = `<span class="order-rider-name font-bold text-slate-800"><i class="bi bi-person mr-1"></i>${escapeHtml(order.rider_name)}</span>`;
                        }
                    }

                    flashHighlightElement(orderRow);
                } else if (e.action === 'created') {
                    // New order placed: display notification toast with direct link
                    showRealtimeToast({
                        title: 'New Order Received!',
                        message: `Order #${order.order_number} (${order.total_formatted}) placed by ${order.customer_name}`,
                        url: `/admin/orders/${order.id}`,
                        icon: 'success'
                    });
                }
            });

        // Admin Deliveries Channel
        window.Echo.private('admin.deliveries')
            .listen('.delivery.updated', (e) => {
                const delivery = e.delivery;

                const deliveryRow = document.getElementById(`delivery-${delivery.id}`) ||
                    document.querySelector(`tr[data-record-id="delivery-${delivery.id}"]`);

                if (deliveryRow) {
                    // Update delivery status badge
                    const statusBadge = deliveryRow.querySelector('.delivery-status-badge');
                    if (statusBadge) {
                        const newColor = statusPillClasses[delivery.delivery_status] || 'bg-slate-50 text-slate-600';
                        statusBadge.className = `delivery-status-badge inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border ${newColor}`;
                        statusBadge.textContent = delivery.delivery_status;
                    }

                    // If arrived timestamp changed
                    if (delivery.arrived_at) {
                        const arrivedEl = deliveryRow.querySelector('.delivery-arrived-time');
                        if (arrivedEl) arrivedEl.textContent = `Arrived: ${delivery.arrived_at}`;
                    }

                    flashHighlightElement(deliveryRow);
                }
            });
    }

    /* =========================================================================
     * 3. CUSTOMER REALTIME: ORDERS & TRACKING
     * ========================================================================= */
    if (userRole === 'customer') {
        window.Echo.private(`customer.orders.${userId}`)
            .listen('.order.updated', (e) => {
                const order = e.order;

                // Show toast to customer
                showRealtimeToast({
                    title: `Order #${order.order_number} Updated`,
                    message: `Status: ${order.order_status}`,
                    url: `/customer/orders/${order.id}`,
                    icon: order.order_status === 'Delivered' ? 'success' : 'info'
                });

                // If on /customer/orders list
                const orderRow = document.getElementById(`order-${order.id}`) ||
                    document.querySelector(`tr[data-record-id="order-${order.id}"]`);

                if (orderRow) {
                    const badge = orderRow.querySelector('.order-status-badge') ||
                        orderRow.querySelector('td:nth-child(4) span') ||
                        orderRow.querySelector('.rounded-full');
                    if (badge) {
                        const newColor = statusPillClasses[order.order_status] || 'bg-slate-50 text-slate-600';
                        badge.className = `order-status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${newColor} shadow-2xs`;
                        badge.textContent = order.order_status;
                    }
                    flashHighlightElement(orderRow);
                }

                // If currently on /customer/orders/{id} tracking view
                if (window.location.pathname.includes(`/customer/orders/${order.id}`)) {
                    // Silently reload the page after a brief moment to update timeline stepper & status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                }
            });
    }

    /* =========================================================================
     * 4. RIDER REALTIME: DISPATCH QUEUE & DELIVERIES
     * ========================================================================= */
    if (userRole === 'rider') {
        window.Echo.private(`rider.deliveries.${userId}`)
            .listen('.delivery.updated', (e) => {
                handleRiderSync(e);
            })
            .listen('.order.updated', (e) => {
                handleRiderSync(e);
            });

        function handleRiderSync(e) {
            const delivery = e.delivery || (e.order ? e.order.delivery : null);
            const orderNumber = delivery ? (delivery.order_number || (e.order ? e.order.order_number : '')) : '';

            showRealtimeToast({
                title: 'Queue Updated',
                message: `Delivery for Order #${orderNumber} status: ${delivery ? delivery.delivery_status : 'Updated'}`,
                url: '/rider/dashboard',
                icon: 'info'
            });

            // If on rider dashboard, refresh to display the fresh assigned tasks or status
            if (window.location.pathname.includes('/rider/dashboard')) {
                const deliveryCard = document.getElementById(`delivery-${delivery ? delivery.id : ''}`);
                if (deliveryCard) {
                    flashHighlightElement(deliveryCard);
                } else {
                    // New assignment: reload active queue after brief toast view
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            }
        }
    }

    /**
     * Helper to escape HTML characters safely
     */
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
