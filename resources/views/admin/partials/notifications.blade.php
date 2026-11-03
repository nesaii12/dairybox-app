@props(['isMobile' => false])

@php
    $wrapperId = $isMobile ? 'adminMobileNotifWrapper' : 'adminNotifWrapper';
    $btnId = $isMobile ? 'adminMobileNotifBtn' : 'adminNotifBtn';
    $modalId = $isMobile ? 'adminMobileNotifModal' : 'adminNotifModal';
    $modalClass = $isMobile 
        ? 'fixed inset-x-4 top-16 bg-white/98 backdrop-blur-md rounded-2xl border border-slate-100 shadow-2xl overflow-hidden hidden z-50 flex flex-col max-w-sm mx-auto'
        : 'absolute right-0 mt-2 w-80 sm:w-96 bg-white/95 backdrop-blur-md rounded-2xl border border-slate-100 shadow-xl overflow-hidden hidden z-50 transform origin-top-right transition-all flex flex-col';
@endphp

<div class="relative" id="{{ $wrapperId }}">
    <button id="{{ $btnId }}" class="relative p-2 {{ $isMobile ? 'text-slate-300 hover:text-white hover:bg-slate-800' : 'text-slate-500 hover:text-sky-600 hover:bg-slate-100' }} rounded-xl transition-all cursor-pointer focus:outline-none" title="Notifications">
        <i class="bi bi-bell text-xl"></i>
        <span class="{{ ($adminNotifCount ?? 0) > 0 ? '' : 'hidden' }} admin-notif-badge absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 {{ $isMobile ? 'ring-slate-900' : 'ring-white' }}">
            {{ $adminNotifCount ?? 0 }}
        </span>
    </button>

    <!-- Dropdown Modal -->
    <div id="{{ $modalId }}" class="{{ $modalClass }}">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
            <div class="flex items-center space-x-2">
                <h3 class="text-xs font-bold text-slate-900">Notifications</h3>
                <span class="{{ ($adminNotifCount ?? 0) > 0 ? '' : 'hidden' }} admin-notif-header-badge text-[10px] font-bold bg-rose-50 text-rose-600 px-2 py-0.5 rounded-full border border-rose-100">
                    <span class="admin-unread-count-text">{{ $adminNotifCount ?? 0 }}</span> new
                </span>
            </div>
            <form action="{{ route('admin.notifications.readAll') }}" method="POST" class="admin-mark-all-read-form {{ ($adminNotifCount ?? 0) > 0 ? 'inline' : 'hidden' }}">
                @csrf
                <button type="submit" class="text-[10px] font-bold text-sky-600 hover:text-sky-700 bg-sky-50 hover:bg-sky-100/80 px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                    Mark all read
                </button>
            </form>
        </div>

        <!-- Body (Scrollable list) -->
        <div class="admin-notif-list overflow-y-auto divide-y divide-slate-100 max-h-[360px]">
            @forelse($adminNotifications ?? [] as $notif)
                @php
                    $isExpiryAlert = str_contains(strtolower($notif->title), 'expir');
                    $isCriticalExpiry = str_contains(strtolower($notif->title), 'critical') || str_contains(strtolower($notif->title), 'expired') || str_contains(strtolower($notif->title), 'urgent');
                    $isLowStock = str_contains(strtolower($notif->title), 'low stock');
                @endphp
                <div class="group relative admin-notif-item p-3.5 flex items-start justify-between gap-3 transition-all hover:bg-sky-50/40 {{ !$notif->is_read ? 'bg-sky-50/15' : '' }}" data-id="{{ $notif->id }}">
                    <a href="{{ route('notifications.readAndRedirect', $notif->id) }}" class="flex space-x-2.5 min-w-0 flex-1 block">
                        <div class="mt-0.5 flex-shrink-0">
                            @if($isExpiryAlert)
                                <div class="w-6 h-6 rounded-lg flex items-center justify-center {{ $isCriticalExpiry ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-amber-50 text-amber-600 border border-amber-100' }}">
                                    <i class="bi {{ $isCriticalExpiry ? 'bi-exclamation-octagon-fill' : 'bi-clock-history' }} text-xs"></i>
                                </div>
                            @elseif($isLowStock)
                                <div class="w-6 h-6 rounded-lg flex items-center justify-center bg-rose-50 text-rose-600 border border-rose-100">
                                    <i class="bi bi-box-seam text-xs"></i>
                                </div>
                            @elseif(!$notif->is_read)
                                <span class="notif-dot block w-2 h-2 mt-1 rounded-full bg-sky-500 ring-4 ring-sky-100"></span>
                            @else
                                <span class="notif-dot block w-2 h-2 mt-1 rounded-full bg-slate-200"></span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs truncate {{ !$notif->is_read ? 'text-slate-900 font-bold group-hover:text-sky-600' : 'text-slate-700 font-medium group-hover:text-sky-600' }} transition-colors">
                                    {{ $notif->title }}
                                </h4>
                                <i class="bi bi-arrow-right text-[10px] text-slate-300 group-hover:text-sky-600 group-hover:translate-x-0.5 transition-all ml-1 flex-shrink-0"></i>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed break-words">{{ $notif->message }}</p>
                            <div class="flex items-center justify-between mt-1.5">
                                <span class="block text-[9px] text-slate-400 font-medium">{{ $notif->created_at->diffForHumans() }}</span>
                                <span class="text-[9px] font-semibold text-sky-600 opacity-0 group-hover:opacity-100 transition-opacity">View record &rarr;</span>
                            </div>
                        </div>
                    </a>

                    @if(!$notif->is_read)
                        <form action="{{ route('admin.notifications.read', $notif->id) }}" method="POST" class="admin-mark-read-form flex-shrink-0 z-10" onclick="event.stopPropagation();">
                            @csrf
                            <button type="submit" class="p-1 px-2 border border-slate-200 rounded-lg text-[9px] font-bold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 transition-all shadow-2xs cursor-pointer" title="Mark as read without redirecting">
                                Read
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="py-10 text-center">
                    <i class="bi bi-bell-slash text-3xl text-slate-200 mb-2 block"></i>
                    <p class="text-xs font-semibold text-slate-800">All caught up!</p>
                    <p class="text-[10px] text-slate-400">You have no new notifications.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
