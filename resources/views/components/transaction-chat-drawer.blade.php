<div id="transactionChatDrawer" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex flex-col items-end gap-3 pointer-events-none">
    <!-- Floating Chat Popover Window -->
    <div id="floatingChatWindow"
         class="hidden pointer-events-auto w-[calc(100vw-2rem)] sm:w-[390px] h-[520px] max-h-[calc(100vh-6.5rem)] bg-white rounded-3xl shadow-2xl border border-slate-200/90 flex flex-col overflow-hidden transform transition-all duration-200 origin-bottom-right scale-95 opacity-0"
         role="dialog"
         aria-modal="false"
         aria-labelledby="chatDrawerTitle">

        <!-- Header -->
        <div class="px-4 py-3 border-b border-slate-100 bg-gradient-to-r from-sky-600 to-sky-500 text-white shrink-0">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <button type="button"
                            id="chatBackToThreadsBtn"
                            class="hidden w-7 h-7 rounded-xl bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition-colors cursor-pointer shrink-0"
                            title="All conversations">
                        <i class="bi bi-arrow-left text-xs"></i>
                    </button>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <h3 id="chatDrawerTitle" class="text-xs font-black tracking-tight truncate">
                                DairyBox Live Chat
                            </h3>
                            <span id="chatDrawerStatusBadge" class="hidden text-[9px] font-bold px-2 py-0.5 rounded-full bg-white/20 text-white backdrop-blur-xs"></span>
                        </div>
                        <p id="chatHeaderSubtitle" class="text-[10px] text-sky-100 truncate">
                            Orders &amp; Reservations
                        </p>
                    </div>
                </div>

                <button type="button"
                        id="chatDrawerCloseBtn"
                        class="w-7 h-7 rounded-xl bg-white/15 hover:bg-white/25 text-white flex items-center justify-center transition-colors cursor-pointer shrink-0"
                        aria-label="Minimize chat">
                    <i class="bi bi-dash-lg text-sm"></i>
                </button>
            </div>

            <!-- Participant Status Pills (visible in conversation view) -->
            <div id="chatParticipantsBar" class="hidden items-center gap-1.5 flex-wrap mt-2 text-[9px]">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/15 text-white font-semibold">
                    <i class="bi bi-person-fill text-[8px]"></i>
                    <span id="chatCustomerLabel">Customer</span>
                </span>
                <span id="chatRiderPill" class="hidden inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-400/25 text-amber-50 font-semibold">
                    <i class="bi bi-bicycle text-[9px]"></i>
                    <span id="chatRiderLabel">Rider</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/15 text-white font-semibold">
                    <i class="bi bi-shield-check text-[8px]"></i>
                    <span>Admin</span>
                </span>
            </div>
        </div>

        <!-- View 1: Thread List -->
        <div id="chatThreadsView" class="flex-1 overflow-y-auto bg-slate-50/60 divide-y divide-slate-100">
            <div class="py-12 text-center text-xs text-slate-400">
                <i class="bi bi-arrow-repeat animate-spin text-lg block mb-1.5 text-sky-500"></i>
                <span>Loading conversations...</span>
            </div>
        </div>

        <!-- View 2: Active Conversation -->
        <div id="chatConversationView" class="hidden flex-1 flex flex-col min-h-0">
            <!-- Messages Container -->
            <div id="chatMessagesContainer"
                 class="flex-1 overflow-y-auto p-3.5 space-y-3 bg-slate-50/70">
            </div>

            <!-- Quick Reply Chips -->
            <div id="chatQuickReplies" class="px-3 py-2 bg-white border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0"></div>

            <!-- Composer Footer -->
            <form id="chatMessageForm" class="p-3 bg-white border-t border-slate-200/80 shrink-0 space-y-2">
                <!-- Attachment Preview -->
                <div id="chatAttachmentPreview" class="hidden items-center justify-between gap-2 px-2.5 py-2 bg-sky-50 border border-sky-100 rounded-xl text-[11px] text-sky-800">
                    <div class="flex items-center gap-2 min-w-0">
                        <img id="chatAttachmentThumb" src="" alt="Selected photo" class="w-10 h-10 rounded-lg object-cover border border-sky-200 shrink-0 hidden">
                        <span id="chatAttachmentName" class="truncate font-medium"></span>
                    </div>
                    <button type="button" id="chatRemoveAttachmentBtn" class="text-slate-400 hover:text-rose-600 cursor-pointer shrink-0">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>

                <!-- Error Banner -->
                <div id="chatErrorBanner" class="hidden px-3 py-1.5 bg-rose-50 border border-rose-200 rounded-xl text-[11px] text-rose-700 font-medium"></div>

                <div class="flex items-end gap-1.5">
                    <input type="file" id="chatAttachmentInput" accept="image/png,image/jpeg,image/webp,image/gif,image/*" class="hidden">
                    <button type="button"
                            id="chatAttachBtn"
                            title="Attach landmark or delivery photo"
                            class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-sky-600 border border-slate-200 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                        <i class="bi bi-camera text-sm"></i>
                    </button>

                    <textarea id="chatMessageInput"
                              rows="1"
                              maxlength="2000"
                              placeholder="Type a message..."
                              class="flex-1 resize-none rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-800 placeholder:text-slate-400 focus:bg-white focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/15 max-h-24"></textarea>

                    <button type="submit"
                            id="chatSendBtn"
                            class="h-8 px-3 rounded-xl bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white text-xs font-bold flex items-center gap-1 shadow-xs transition-all cursor-pointer shrink-0">
                        <i class="bi bi-send-fill text-[11px]"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Chat Bubble Button -->
    <button type="button"
            id="floatingChatBubble"
            aria-label="Open live chat"
            class="pointer-events-auto relative w-14 h-14 rounded-full bg-gradient-to-tr from-sky-600 to-sky-500 hover:from-sky-500 hover:to-sky-400 text-white shadow-xl shadow-sky-500/30 hover:shadow-sky-500/40 active:scale-95 transition-all flex items-center justify-center cursor-pointer border-2 border-white">
        <i id="floatingChatBubbleIcon" class="bi bi-chat-dots-fill text-xl"></i>
        <span id="floatingChatUnreadBadge"
              class="hidden absolute -top-1 -right-1 min-w-[20px] h-5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-black items-center justify-center border-2 border-white shadow-sm">
            0
        </span>
    </button>
</div>
