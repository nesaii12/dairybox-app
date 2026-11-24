/**
 * Floating Bubble Transaction-Scoped Live Chat (Customer, Rider & Admin)
 * Supports Laravel Echo (Reverb) private channels with automatic 3-second polling fallback.
 */
(function () {
    const root = document.getElementById('transactionChatDrawer');
    if (!root) return;

    const bubbleBtn = document.getElementById('floatingChatBubble');
    const bubbleIcon = document.getElementById('floatingChatBubbleIcon');
    const bubbleUnreadBadge = document.getElementById('floatingChatUnreadBadge');
    const chatWindow = document.getElementById('floatingChatWindow');

    const backBtn = document.getElementById('chatBackToThreadsBtn');
    const closeBtn = document.getElementById('chatDrawerCloseBtn');
    const titleEl = document.getElementById('chatDrawerTitle');
    const subtitleEl = document.getElementById('chatHeaderSubtitle');
    const statusBadgeEl = document.getElementById('chatDrawerStatusBadge');
    const participantsBar = document.getElementById('chatParticipantsBar');
    const customerLabelEl = document.getElementById('chatCustomerLabel');
    const riderPillEl = document.getElementById('chatRiderPill');
    const riderLabelEl = document.getElementById('chatRiderLabel');

    const threadsView = document.getElementById('chatThreadsView');
    const conversationView = document.getElementById('chatConversationView');
    const messagesContainer = document.getElementById('chatMessagesContainer');
    const quickRepliesEl = document.getElementById('chatQuickReplies');
    const form = document.getElementById('chatMessageForm');
    const inputEl = document.getElementById('chatMessageInput');
    const sendBtn = document.getElementById('chatSendBtn');
    const attachBtn = document.getElementById('chatAttachBtn');
    const attachmentInput = document.getElementById('chatAttachmentInput');
    const attachmentPreview = document.getElementById('chatAttachmentPreview');
    const attachmentThumb = document.getElementById('chatAttachmentThumb');
    const attachmentNameEl = document.getElementById('chatAttachmentName');
    const removeAttachmentBtn = document.getElementById('chatRemoveAttachmentBtn');
    const errorBanner = document.getElementById('chatErrorBanner');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const currentUserId = Number(document.querySelector('meta[name="user-id"]')?.content || 0);
    const currentUserRole = document.querySelector('meta[name="user-role"]')?.content || 'customer';

    const state = {
        isOpen: false,
        mode: 'threads', // 'threads' | 'conversation'
        type: null,
        id: null,
        threadId: null,
        channelName: null,
        pollTimer: null,
        badgePollTimer: null,
        renderedIds: new Set(),
        isSending: false,
        pendingPrefill: null,
        autoOpenRecentOnLoad: false,
    };

    const roleBadges = {
        customer: { label: 'Customer', classes: 'bg-sky-100 text-sky-800 border-sky-200' },
        rider: { label: 'Rider', classes: 'bg-amber-100 text-amber-800 border-amber-200' },
        admin: { label: 'Admin', classes: 'bg-indigo-100 text-indigo-800 border-indigo-200' },
    };

    const quickRepliesByRole = {
        rider: [
            'I am outside your delivery address',
            'Order is on the way',
            'Please prepare exact COD balance',
        ],
        admin: [
            'Order is on the way',
            'Please prepare exact COD balance',
            'Your request has been noted by our team',
        ],
        customer: [
            'Here is my gate landmark',
            'I am available to receive now',
            'Please let me know once the rider is on the way',
        ],
    };

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function showError(msg) {
        if (!msg) {
            errorBanner.classList.add('hidden');
            errorBanner.textContent = '';
            return;
        }
        errorBanner.textContent = msg;
        errorBanner.classList.remove('hidden');
    }

    function updateBubbleUnread(count) {
        const num = Number(count || 0);
        if (num > 0) {
            bubbleUnreadBadge.textContent = num > 99 ? '99+' : String(num);
            bubbleUnreadBadge.classList.remove('hidden');
            bubbleUnreadBadge.classList.add('inline-flex');
        } else {
            bubbleUnreadBadge.textContent = '0';
            bubbleUnreadBadge.classList.add('hidden');
            bubbleUnreadBadge.classList.remove('inline-flex');
        }
    }

    function clearRowBadges(type, id) {
        document.querySelectorAll(`[data-chat-badge="${type}-${id}"]`).forEach((badge) => {
            badge.textContent = '0';
            badge.classList.add('hidden');
            badge.classList.remove('inline-flex');
        });
    }

    function renderQuickReplies() {
        const chips = quickRepliesByRole[currentUserRole] || quickRepliesByRole.customer;
        quickRepliesEl.innerHTML = chips.map((text) => `
            <button type="button"
                    data-quick-reply="${escapeHtml(text)}"
                    class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-sky-700 border border-slate-200 hover:border-sky-200 transition-colors cursor-pointer">
                ${escapeHtml(text)}
            </button>
        `).join('');

        quickRepliesEl.querySelectorAll('[data-quick-reply]').forEach((btn) => {
            btn.addEventListener('click', () => {
                inputEl.value = btn.getAttribute('data-quick-reply') || '';
                inputEl.focus();
                autoResizeInput();
            });
        });
    }

    function autoResizeInput() {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 96) + 'px';
    }

    let attachmentObjectUrl = null;

    function clearAttachment() {
        attachmentInput.value = '';
        if (attachmentObjectUrl) {
            URL.revokeObjectURL(attachmentObjectUrl);
            attachmentObjectUrl = null;
        }
        if (attachmentThumb) {
            attachmentThumb.src = '';
            attachmentThumb.classList.add('hidden');
        }
        attachmentPreview.classList.add('hidden');
        attachmentPreview.classList.remove('flex');
        attachmentNameEl.textContent = '';
    }

    function setAttachmentFile(file) {
        if (!file) {
            clearAttachment();
            return;
        }
        if (attachmentObjectUrl) {
            URL.revokeObjectURL(attachmentObjectUrl);
        }
        attachmentObjectUrl = URL.createObjectURL(file);
        if (attachmentThumb) {
            attachmentThumb.src = attachmentObjectUrl;
            attachmentThumb.classList.remove('hidden');
        }
        attachmentNameEl.textContent = file.name || 'image.png';
        attachmentPreview.classList.remove('hidden');
        attachmentPreview.classList.add('flex');
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function createMessageElement(msg) {
        const isSelf = Number(msg.sender_id) === currentUserId;
        const badge = roleBadges[msg.sender_role] || { label: msg.sender_role, classes: 'bg-slate-100 text-slate-700 border-slate-200' };

        const wrapper = document.createElement('div');
        wrapper.dataset.messageId = String(msg.id);
        wrapper.className = `flex flex-col ${isSelf ? 'items-end' : 'items-start'}`;

        const metaHtml = `
            <div class="flex items-center gap-1.5 mb-1 px-1 ${isSelf ? 'flex-row-reverse' : ''}">
                <span class="inline-flex items-center px-1.5 py-0.25 rounded text-[9px] font-bold border ${badge.classes}">
                    ${escapeHtml(badge.label)}
                </span>
                <span class="text-[10px] font-semibold text-slate-600">${escapeHtml(msg.sender_name)}</span>
                <span class="text-[9px] text-slate-400 font-mono">${escapeHtml(msg.time_formatted || msg.created_at_formatted)}</span>
            </div>
        `;

        const bubbleClasses = isSelf
            ? 'bg-sky-600 text-white rounded-2xl rounded-tr-xs shadow-xs'
            : 'bg-white text-slate-800 border border-slate-200/80 rounded-2xl rounded-tl-xs shadow-2xs';

        let bodyHtml = '';
        if (msg.attachment_url) {
            bodyHtml += `
                <a href="${escapeHtml(msg.attachment_url)}" target="_blank" rel="noopener noreferrer" class="block ${msg.body ? 'mb-1.5' : ''} overflow-hidden rounded-xl border ${isSelf ? 'border-sky-400/40' : 'border-slate-200'}">
                    <img src="${escapeHtml(msg.attachment_url)}" alt="Chat attachment" class="max-h-48 w-full object-cover">
                </a>
            `;
        }
        if (msg.body) {
            bodyHtml += `<p class="text-xs leading-relaxed whitespace-pre-wrap break-words">${escapeHtml(msg.body)}</p>`;
        }

        wrapper.innerHTML = `
            ${metaHtml}
            <div class="max-w-[85%] px-3 py-2 ${bubbleClasses}">
                ${bodyHtml}
            </div>
        `;

        return wrapper;
    }

    function appendMessages(messages, forceScroll = false) {
        if (!Array.isArray(messages)) return;

        const emptyState = messagesContainer.querySelector('[data-chat-empty]');
        let addedAny = false;

        messages.forEach((msg) => {
            if (state.renderedIds.has(msg.id)) return;
            if (emptyState) emptyState.remove();
            state.renderedIds.add(msg.id);
            messagesContainer.appendChild(createMessageElement(msg));
            addedAny = true;
        });

        if (state.renderedIds.size === 0 && !emptyState) {
            messagesContainer.innerHTML = `
                <div data-chat-empty="true" class="py-12 px-4 text-center">
                    <div class="w-10 h-10 rounded-2xl bg-white border border-slate-200 text-slate-300 flex items-center justify-center mx-auto mb-2 shadow-2xs">
                        <i class="bi bi-chat-square-text text-lg"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-700">No messages yet</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Coordinate landmarks, schedule updates, or COD balance here.</p>
                </div>
            `;
        }

        if (addedAny || forceScroll) {
            scrollToBottom();
        }
    }

    function updateConversationHeader(thread) {
        if (!thread) return;
        titleEl.textContent = thread.reference || 'Transaction Chat';
        subtitleEl.textContent = thread.fulfillment ? `${thread.fulfillment} Transaction` : 'Live Coordination';

        if (thread.status_label) {
            statusBadgeEl.textContent = thread.status_label;
            statusBadgeEl.classList.remove('hidden');
        } else {
            statusBadgeEl.classList.add('hidden');
        }

        customerLabelEl.textContent = thread.customer_name || 'Customer';

        if (thread.has_rider && thread.rider_name) {
            riderLabelEl.textContent = `Rider: ${thread.rider_name}`;
            riderPillEl.classList.remove('hidden');
            riderPillEl.classList.add('inline-flex');
        } else if (thread.fulfillment === 'Delivery') {
            riderLabelEl.textContent = 'Awaiting Rider';
            riderPillEl.classList.remove('hidden');
            riderPillEl.classList.add('inline-flex');
        } else {
            riderPillEl.classList.add('hidden');
            riderPillEl.classList.remove('inline-flex');
        }
    }

    function leaveEchoChannel() {
        if (window.Echo && state.channelName) {
            try {
                window.Echo.leave(state.channelName);
            } catch (e) {}
        }
        state.channelName = null;
    }

    function subscribeToThread(threadId) {
        if (!window.Echo || !threadId) return;
        const targetChannel = `transaction.chat.${threadId}`;
        if (state.channelName === targetChannel) return;

        leaveEchoChannel();
        state.channelName = targetChannel;

        try {
            window.Echo.private(targetChannel)
                .listen('.message.sent', (e) => {
                    if (e && e.message && state.isOpen && state.mode === 'conversation' && Number(e.message.chat_thread_id) === Number(state.threadId)) {
                        appendMessages([e.message], true);
                    }
                });
        } catch (err) {
            console.warn('[Chat] Echo subscription failed, relying on polling fallback.', err);
        }
    }

    async function fetchThreadsList(updateView = true) {
        try {
            const res = await fetch('/chat/threads', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) return;
            const data = await res.json();
            updateBubbleUnread(data.total_unread || 0);

            if (!updateView) return;

            const threads = data.threads || [];
            if (state.autoOpenRecentOnLoad && threads.length > 0) {
                state.autoOpenRecentOnLoad = false;
                const topThread = threads[0];
                openConversation(topThread.type, topThread.id, topThread.reference);
                if (state.pendingPrefill) {
                    inputEl.value = state.pendingPrefill;
                    autoResizeInput();
                    setTimeout(() => inputEl.focus(), 200);
                }
                return;
            }

            if (threads.length === 0) {
                threadsView.innerHTML = `
                    <div class="py-14 px-5 text-center">
                        <div class="w-11 h-11 rounded-2xl bg-white border border-slate-200 text-slate-300 flex items-center justify-center mx-auto mb-2.5 shadow-2xs">
                            <i class="bi bi-chat-dots text-xl"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-700">No active transactions yet</p>
                        <p class="text-[11px] text-slate-400 mt-1">Once you have an order or reservation, you can chat with the rider and admin here.</p>
                    </div>
                `;
                return;
            }

            threadsView.innerHTML = threads.map((t) => {
                const unreadHtml = t.unread_count > 0
                    ? `<span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[9px] font-black">${t.unread_count > 99 ? '99+' : t.unread_count}</span>`
                    : '';
                const iconClass = t.type === 'reservation' ? 'bi-calendar2-check text-indigo-600 bg-indigo-50 border-indigo-100' : 'bi-bag-check text-sky-600 bg-sky-50 border-sky-100';

                return `
                    <button type="button"
                            data-thread-item
                            data-chat-type="${escapeHtml(t.type)}"
                            data-chat-id="${escapeHtml(t.id)}"
                            data-chat-label="${escapeHtml(t.reference)}"
                            class="w-full text-left px-4 py-3 hover:bg-white transition-colors flex items-start gap-3 cursor-pointer">
                        <div class="w-9 h-9 rounded-2xl border flex items-center justify-center shrink-0 mt-0.5 ${iconClass}">
                            <i class="bi ${iconClass.split(' ')[0]} text-sm"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-slate-900 font-mono truncate">${escapeHtml(t.reference)}</span>
                                <span class="text-[9px] text-slate-400 shrink-0">${escapeHtml(t.last_time)}</span>
                            </div>
                            <div class="flex items-center justify-between gap-2 mt-0.5">
                                <span class="text-[10px] font-semibold text-slate-500 truncate">${escapeHtml(t.subtitle)} &bull; ${escapeHtml(t.status_label)}</span>
                                ${unreadHtml}
                            </div>
                            <p class="text-[11px] text-slate-500 truncate mt-1">${escapeHtml(t.last_message)}</p>
                        </div>
                    </button>
                `;
            }).join('');

            threadsView.querySelectorAll('[data-thread-item]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    openConversation(
                        btn.getAttribute('data-chat-type'),
                        btn.getAttribute('data-chat-id'),
                        btn.getAttribute('data-chat-label')
                    );
                });
            });
        } catch (e) {}
    }

    async function fetchConversation(isInitial = false) {
        if (!state.isOpen || state.mode !== 'conversation' || !state.type || !state.id) return;

        try {
            const res = await fetch(`/chat/thread/${state.type}/${state.id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!res.ok) {
                if (isInitial) {
                    const err = await res.json().catch(() => ({}));
                    showError(err.message || 'Unable to load chat thread.');
                    messagesContainer.innerHTML = '';
                }
                return;
            }

            const data = await res.json();
            showError(null);

            if (isInitial) {
                messagesContainer.innerHTML = '';
                state.renderedIds.clear();
            }

            if (data.thread) {
                state.threadId = data.thread.id;
                updateConversationHeader(data.thread);
                subscribeToThread(data.thread.id);
            }

            appendMessages(data.messages || [], isInitial);
            clearRowBadges(state.type, state.id);
            fetchThreadsList(false);
        } catch (e) {
            if (isInitial) {
                showError('Network error while loading chat.');
            }
        }
    }

    function startPolling() {
        stopPolling();
        state.pollTimer = setInterval(() => {
            if (!state.isOpen) return;
            if (state.mode === 'conversation') {
                fetchConversation(false);
            } else {
                fetchThreadsList(true);
            }
        }, 3000);
    }

    function stopPolling() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
            state.pollTimer = null;
        }
    }

    function showWindow() {
        state.isOpen = true;
        chatWindow.classList.remove('hidden');
        requestAnimationFrame(() => {
            chatWindow.classList.remove('scale-95', 'opacity-0');
        });
        bubbleIcon.className = 'bi bi-x-lg text-lg';
        startPolling();
    }

    function hideWindow() {
        state.isOpen = false;
        stopPolling();
        leaveEchoChannel();

        chatWindow.classList.add('scale-95', 'opacity-0');
        bubbleIcon.className = 'bi bi-chat-dots-fill text-xl';
        setTimeout(() => {
            if (!state.isOpen) {
                chatWindow.classList.add('hidden');
            }
        }, 200);
    }

    function showThreadsList() {
        state.mode = 'threads';
        state.type = null;
        state.id = null;
        state.threadId = null;
        leaveEchoChannel();

        backBtn.classList.add('hidden');
        statusBadgeEl.classList.add('hidden');
        participantsBar.classList.add('hidden');
        participantsBar.classList.remove('flex');
        titleEl.textContent = 'DairyBox Live Chat';
        subtitleEl.textContent = 'Orders & Reservations';

        conversationView.classList.add('hidden');
        threadsView.classList.remove('hidden');

        if (!state.isOpen) {
            showWindow();
        }
        fetchThreadsList(true);
    }

    function openConversation(type, id, initialTitle = null) {
        state.mode = 'conversation';
        state.type = type;
        state.id = id;
        state.threadId = null;
        state.renderedIds.clear();

        showError(null);
        clearAttachment();
        renderQuickReplies();
        if (state.pendingPrefill && !inputEl.value) {
            inputEl.value = state.pendingPrefill;
            autoResizeInput();
        }

        backBtn.classList.remove('hidden');
        participantsBar.classList.remove('hidden');
        participantsBar.classList.add('flex');
        titleEl.textContent = initialTitle || `${type === 'reservation' ? 'Reservation' : 'Order'} #${id}`;
        subtitleEl.textContent = 'Live Coordination';
        statusBadgeEl.classList.add('hidden');

        threadsView.classList.add('hidden');
        conversationView.classList.remove('hidden');

        messagesContainer.innerHTML = `
            <div class="py-12 text-center text-xs text-slate-400">
                <i class="bi bi-arrow-repeat animate-spin text-lg block mb-1.5 text-sky-500"></i>
                <span>Loading messages...</span>
            </div>
        `;

        if (!state.isOpen) {
            showWindow();
        }
        fetchConversation(true);
        setTimeout(() => inputEl.focus(), 180);
    }

    async function sendMessage(e) {
        e.preventDefault();
        if (state.isSending || !state.threadId) return;

        const body = inputEl.value.trim();
        const file = attachmentInput.files[0] || null;
        if (!body && !file) {
            showError('Please enter a message or attach a photo.');
            return;
        }

        state.isSending = true;
        sendBtn.disabled = true;
        showError(null);

        try {
            const formData = new FormData();
            if (body) formData.append('body', body);
            if (file) formData.append('attachment', file);

            const res = await fetch(`/chat/thread/${state.threadId}/messages`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const firstErr = data.errors ? Object.values(data.errors).flat()[0] : null;
                showError(firstErr || data.message || 'Failed to send message.');
                return;
            }

            if (data.message) {
                appendMessages([data.message], true);
            }

            inputEl.value = '';
            autoResizeInput();
            clearAttachment();
        } catch (err) {
            showError('Network error while sending message.');
        } finally {
            state.isSending = false;
            sendBtn.disabled = false;
            inputEl.focus();
        }
    }

    // Event bindings
    bubbleBtn.addEventListener('click', () => {
        if (state.isOpen) {
            hideWindow();
        } else if (state.type && state.id) {
            openConversation(state.type, state.id, titleEl.textContent);
        } else {
            showThreadsList();
        }
    });

    backBtn.addEventListener('click', showThreadsList);
    closeBtn.addEventListener('click', hideWindow);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && state.isOpen) {
            hideWindow();
        }
    });

    attachBtn.addEventListener('click', () => attachmentInput.click());
    attachmentInput.addEventListener('change', () => {
        setAttachmentFile(attachmentInput.files[0] || null);
    });
    removeAttachmentBtn.addEventListener('click', clearAttachment);

    inputEl.addEventListener('paste', (e) => {
        const items = e.clipboardData?.items;
        if (!items) return;
        for (const item of items) {
            if (item.type.startsWith('image/')) {
                const file = item.getAsFile();
                if (file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    attachmentInput.files = dt.files;
                    setAttachmentFile(file);
                    e.preventDefault();
                    break;
                }
            }
        }
    });

    inputEl.addEventListener('input', autoResizeInput);
    inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });
    form.addEventListener('submit', sendMessage);

    // Delegate clicks on [data-open-chat] buttons across all views
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-open-chat]');
        if (!trigger) return;
        e.preventDefault();
        const type = trigger.getAttribute('data-chat-type');
        const id = trigger.getAttribute('data-chat-id');
        const label = trigger.getAttribute('data-chat-label');
        if (type && id) {
            openConversation(type, id, label);
        }
    });

    // Initial & background unread badge check for the floating bubble
    fetchThreadsList(false);
    state.badgePollTimer = setInterval(() => {
        if (!state.isOpen) {
            fetchThreadsList(false);
        }
    }, 10000);

    window.openLiveChat = function (prefill = null) {
        if (prefill) {
            state.pendingPrefill = prefill;
            inputEl.value = prefill;
            autoResizeInput();
        }
        if (!state.isOpen) {
            showWindow();
        }
        if (state.mode === 'conversation') {
            setTimeout(() => inputEl.focus(), 150);
        } else {
            const firstThreadBtn = threadsView.querySelector('[data-thread-item]');
            if (firstThreadBtn) {
                firstThreadBtn.click();
            } else {
                state.autoOpenRecentOnLoad = true;
                showThreadsList();
            }
        }
    };

    window.openTransactionChat = openConversation;
    window.closeTransactionChat = hideWindow;
})();
