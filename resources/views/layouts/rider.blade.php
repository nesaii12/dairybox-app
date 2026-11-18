<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="user-role" content="{{ auth()->user()->role }}">
    @endauth
    <title>@yield('title', 'Rider Portal') | DairyBox</title>
    <!-- Favicon Links -->
    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top, #fafaf9 0%, #f1f5f9 100%);
        }
        /* Mobile touch optimization */
        button, a {
            touch-action: manipulation;
        }
        /* Hide scrollbars for device mockups */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }

        /* Premium Separated Pagination Styles */
        nav[role="navigation"] {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            background: transparent !important;
            padding: 12px 0 !important;
        }
        nav[role="navigation"] .flex-1 {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }
        /* Convert merged container to modern gap-separated flex layout */
        nav[role="navigation"] .shadow-sm {
            box-shadow: none !important;
            border: none !important;
            background: transparent !important;
            display: inline-flex !important;
            gap: 6px !important;
            border-radius: 0 !important;
            overflow: visible !important;
        }
        /* Style individual buttons */
        nav[role="navigation"] a, 
        nav[role="navigation"] span[aria-current="page"] > span,
        nav[role="navigation"] span[aria-current="page"] > a,
        nav[role="navigation"] span[aria-disabled="true"] > span {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 34px !important;
            height: 34px !important;
            padding: 0 10px !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            color: #475569 !important; /* slate-600 */
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important; /* rounded-lg */
            transition: all 0.2s ease-in-out !important;
            text-decoration: none !important;
            cursor: pointer !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        /* Standardize SVG sizes inside carets */
        nav[role="navigation"] a svg,
        nav[role="navigation"] span svg {
            width: 14px !important;
            height: 14px !important;
            display: block !important;
        }
        /* Hover states */
        nav[role="navigation"] a:hover {
            background-color: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #0ea5e9 !important; /* sky-500 */
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
        }
        /* Active page state */
        nav[role="navigation"] span[aria-current="page"] > span {
            background-color: #0ea5e9 !important; /* sky-500 */
            border-color: #0ea5e9 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 12px -2px rgba(14, 165, 233, 0.35) !important;
        }
        /* Disabled state */
        nav[role="navigation"] span[aria-disabled="true"] > span {
            color: #cbd5e1 !important;
            cursor: not-allowed !important;
            background-color: #f8fafc !important;
            border-color: #f1f5f9 !important;
            box-shadow: none !important;
            transform: none !important;
        }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
</head>
<body class="h-full min-h-[100dvh] flex flex-col items-center justify-center p-0 md:p-6 text-slate-800 antialiased overflow-hidden">

    <!-- Smartphone Device Container Mockup (Fits beautifully centered on desktop, natively full-screen on mobile) -->
    <div class="w-full h-[100dvh] md:h-[820px] md:w-[380px] md:rounded-[48px] md:border-[12px] md:border-slate-800 md:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.15)] md:ring-1 md:ring-slate-300/40 md:relative md:flex md:flex-col overflow-hidden bg-white">
        
        <!-- Simulated Hardware notch/Status bar (Desktop only) -->
        <div class="hidden md:flex bg-slate-50 h-7 px-6 items-center justify-between text-[10px] text-slate-500 select-none z-50 sticky top-0 border-b border-slate-100">
            <span class="font-semibold">09:41</span>
            <div class="w-24 h-4 bg-slate-50 rounded-b-2xl absolute left-1/2 -translate-x-1/2 top-0 border-x border-b border-slate-100"></div>
            <div class="flex items-center space-x-1.5">
                <i class="bi bi-reception-4 text-[9px]"></i>
                <i class="bi bi-wifi text-[10px]"></i>
                <i class="bi bi-battery-full text-slate-700 text-sm"></i>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-100 px-4 py-3 flex items-center justify-between sticky top-0 z-40 shadow-xs relative">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-sky-50 flex items-center justify-center p-1 border border-sky-100 shadow-xs flex-shrink-0">
                    <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                </div>
                <div>
                    <span class="text-sm font-black tracking-tight text-slate-900 leading-none">Dairy<span class="text-[#0ea5e9]">Box</span></span>
                    <span class="text-[9px] bg-sky-50 text-sky-700 border border-sky-200 px-1.5 py-0.5 rounded ml-1 font-bold uppercase tracking-wider">Rider</span>
                </div>
            </div>
            
            @php
                $riderNotifCount = auth()->check() ? \App\Models\AppNotification::where('user_id', auth()->id())->where('is_read', false)->count() : 0;
                $riderNotifications = auth()->check() ? \App\Models\AppNotification::where('user_id', auth()->id())->latest()->take(20)->get() : collect();
            @endphp
            
            <div class="flex items-center space-x-2.5">
                <!-- Rider Notifications Bell Button -->
                <button id="riderNotifBellBtn" type="button" class="relative p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-xl transition-all cursor-pointer focus:outline-none" title="Notifications">
                    <i class="bi bi-bell text-lg"></i>
                    @if($riderNotifCount > 0)
                        <span class="absolute top-0.5 right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white animate-pulse">
                            {{ $riderNotifCount }}
                        </span>
                    @endif
                </button>

                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-900 leading-none">{{ auth()->user()->name }}</p>
                    <p class="text-[8px] text-emerald-600 mt-0.5 flex items-center justify-end font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span> Active
                    </p>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to sign out?')">
                    @csrf
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 rounded-xl hover:bg-rose-50 transition-all cursor-pointer" title="Logout">
                        <i class="bi bi-power text-sm"></i>
                    </button>
                </form>
            </div>

            <!-- Rider Notifications Modal (Positioned cleanly across the phone frame) -->
            <div id="riderNotifModal" class="absolute left-3 right-3 top-full mt-2 bg-white rounded-2xl border border-slate-200/90 shadow-2xl overflow-hidden hidden z-50 flex flex-col transition-all">
                <div class="px-3.5 py-2.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                    <div class="flex items-center space-x-2">
                        <h4 class="text-xs font-bold text-slate-900">Notifications</h4>
                        @if($riderNotifCount > 0)
                            <span class="text-[9px] font-bold bg-sky-50 text-sky-700 px-2 py-0.5 rounded-full border border-sky-100">
                                {{ $riderNotifCount }} new
                            </span>
                        @endif
                    </div>
                    @if($riderNotifCount > 0)
                        <form action="{{ route('rider.notifications.readAll') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-[10px] font-bold text-sky-600 hover:text-sky-700 bg-sky-50 hover:bg-sky-100 px-2 py-0.5 rounded-lg transition-all cursor-pointer">
                                Mark all read
                            </button>
                        </form>
                    @endif
                </div>

                <div class="overflow-y-auto divide-y divide-slate-100 max-h-[340px]">
                    @forelse($riderNotifications as $notif)
                        <div class="group relative p-3 flex items-start justify-between gap-2.5 transition-all hover:bg-sky-50/40 {{ !$notif->is_read ? 'bg-sky-50/20' : '' }}">
                            <a href="{{ route('notifications.readAndRedirect', $notif->id) }}" class="flex space-x-2.5 flex-1 min-w-0 block">
                                <div class="mt-0.5 flex-shrink-0">
                                    @if(!$notif->is_read)
                                        <span class="block w-2 h-2 rounded-full bg-sky-500 ring-4 ring-sky-100"></span>
                                    @else
                                        <span class="block w-2 h-2 rounded-full bg-slate-200"></span>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-[11px] font-bold {{ !$notif->is_read ? 'text-slate-900 group-hover:text-sky-600' : 'text-slate-700 group-hover:text-sky-600' }} transition-colors truncate">
                                            {{ $notif->title }}
                                        </h5>
                                        <i class="bi bi-arrow-right text-[9px] text-slate-300 group-hover:text-sky-600 group-hover:translate-x-0.5 transition-all ml-1 flex-shrink-0"></i>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-0.5 leading-snug break-words">{{ $notif->message }}</p>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="block text-[8px] text-slate-400 font-medium">{{ $notif->created_at->diffForHumans() }}</span>
                                        <span class="text-[8px] font-semibold text-sky-600 opacity-0 group-hover:opacity-100 transition-opacity">View details &rarr;</span>
                                    </div>
                                </div>
                            </a>

                            @if(!$notif->is_read)
                                <form action="{{ route('rider.notifications.read', $notif->id) }}" method="POST" class="flex-shrink-0 z-10" onclick="event.stopPropagation();">
                                    @csrf
                                    <button type="submit" class="p-1 px-1.5 border border-slate-200 rounded-lg text-[8px] font-bold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 transition-all shadow-2xs cursor-pointer" title="Mark read">
                                        Read
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs">
                            <i class="bi bi-bell-slash text-2xl text-slate-200 mb-1 block"></i>
                            <span>No new notifications.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-grow p-4 overflow-y-auto no-scrollbar pb-24 bg-slate-50/60 relative">
            <!-- Live Floating Notification Toast for Rider -->
            <div id="riderLiveToast" class="fixed top-3 left-4 right-4 z-[9999] pointer-events-none flex flex-col items-center transition-all duration-300 transform -translate-y-24 opacity-0">
                <div class="pointer-events-auto bg-slate-900 text-white rounded-2xl p-3.5 shadow-2xl border border-slate-700/60 flex items-start space-x-3 w-full max-w-sm">
                    <div class="w-8 h-8 rounded-xl bg-sky-500 text-white flex items-center justify-center text-base shrink-0 shadow-sm">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span id="riderToastTitle" class="text-xs font-bold text-sky-400 block truncate">New Notification</span>
                            <span id="riderToastTime" class="text-[9px] text-slate-400 font-medium">Just now</span>
                        </div>
                        <p id="riderToastMessage" class="text-[11px] text-slate-200 mt-0.5 leading-snug line-clamp-2">Message details...</p>
                        <div class="mt-2 flex items-center gap-2">
                            <a id="riderToastLink" href="{{ route('rider.dashboard') }}" class="inline-flex items-center text-[10px] font-bold text-sky-400 hover:text-sky-300 underline">
                                View Dashboard &rarr;
                            </a>
                        </div>
                    </div>
                    <button type="button" onclick="dismissRiderToast()" class="text-slate-400 hover:text-white p-1 rounded-lg shrink-0 cursor-pointer">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Alert message flashes using SweetAlert2 -->
            @if(session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: @js(session('success')),
                        timer: 2500,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'rounded-2xl border border-slate-100 shadow-xl'
                        }
                    });
                });
            </script>
            @endif

            @if(session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: @js(session('error')),
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'rounded-2xl border border-slate-100 shadow-xl'
                        }
                    });
                });
            </script>
            @endif

            @yield('content')
        </main>

        <!-- Mobile Bottom Tab Navigation -->
        <nav class="absolute bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-100 px-4 py-2.5 pb-[max(0.625rem,env(safe-area-inset-bottom))] flex items-center justify-around z-40 shadow-[0_-8px_24px_rgba(0,0,0,0.03)] md:rounded-b-[36px]">
            <a href="{{ route('rider.dashboard') }}" class="flex flex-col items-center py-1 px-4 rounded-2xl transition-all {{ request()->routeIs('rider.dashboard') ? 'text-sky-600 font-extrabold bg-sky-50/70 scale-102' : 'text-slate-400 hover:text-slate-600 font-medium' }}">
                <i class="bi bi-box-seam text-lg leading-none mb-1"></i>
                <span class="text-[10px] tracking-tight">Active Queue</span>
            </a>
            <a href="{{ route('rider.history') }}" class="flex flex-col items-center py-1 px-4 rounded-2xl transition-all {{ request()->routeIs('rider.history') ? 'text-sky-600 font-extrabold bg-sky-50/70 scale-102' : 'text-slate-400 hover:text-slate-600 font-medium' }}">
                <i class="bi bi-clock-history text-lg leading-none mb-1"></i>
                <span class="text-[10px] tracking-tight">History Log</span>
            </a>
        </nav>
    </div>

    <!-- Reusable Rider Customer Details Modal -->
    <div id="riderCustomerDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center p-3.5 bg-slate-900/60 backdrop-blur-xs hidden transition-all">
        <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100 flex flex-col max-h-[90vh] overflow-hidden" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 flex-shrink-0">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-sm border border-sky-100">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 leading-tight">Customer Details</h3>
                        <p class="text-[9px] text-slate-400 font-mono" id="modalCustomerOrderRef">ORDER REF</p>
                    </div>
                </div>
                <button type="button" onclick="closeCustomerDetailsModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            <!-- Scrollable Modal Body -->
            <div class="overflow-y-auto no-scrollbar py-3.5 space-y-4 flex-1">
                <!-- Customer Identity Card -->
                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100/80">
                    <div class="flex items-center space-x-3 mb-3">
                        <div id="modalCustomerAvatar" class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-sky-500 to-sky-400 text-white font-bold flex items-center justify-center text-sm shadow-sm flex-shrink-0 uppercase">
                            <!-- Initials or Image -->
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-1.5">
                                <h4 id="modalCustomerName" class="text-xs font-bold text-slate-900 truncate">Customer Name</h4>
                                <span class="text-[8px] font-bold bg-sky-100/80 text-sky-700 px-1.5 py-0.25 rounded border border-sky-200/50">Client</span>
                            </div>
                            <p id="modalCustomerEmail" class="text-[10px] text-slate-500 truncate mt-0.5">customer@email.com</p>
                        </div>
                    </div>

                    <!-- Trust Metrics: Member Since & Orders Completed -->
                    <div class="grid grid-cols-2 gap-2 pt-2.5 border-t border-slate-200/60 text-center">
                        <div class="bg-white p-2 rounded-xl border border-slate-100 shadow-2xs">
                            <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Client Since</span>
                            <span id="modalCustomerSince" class="text-[11px] font-bold text-slate-700 mt-0.5 block">--</span>
                        </div>
                        <div class="bg-white p-2 rounded-xl border border-slate-100 shadow-2xs">
                            <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Completed Orders</span>
                            <span id="modalCustomerOrderCount" class="text-[11px] font-bold text-emerald-600 mt-0.5 block">--</span>
                        </div>
                    </div>
                </div>

                <!-- Direct Contact Actions (Call, SMS, Email) -->
                <div>
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-2">Direct Contact Actions</span>
                    <div class="grid grid-cols-3 gap-2">
                        <a id="modalActionCall" href="#" class="flex flex-col items-center justify-center p-2.5 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200/80 rounded-2xl text-emerald-700 transition active:scale-95 shadow-2xs">
                            <i class="bi bi-telephone-fill text-sm mb-1 text-emerald-600"></i>
                            <span class="text-[9px] font-bold">Call</span>
                        </a>
                        <a id="modalActionSms" href="#" class="flex flex-col items-center justify-center p-2.5 bg-sky-50 hover:bg-sky-100/80 border border-sky-200/80 rounded-2xl text-sky-700 transition active:scale-95 shadow-2xs">
                            <i class="bi bi-chat-dots-fill text-sm mb-1 text-sky-600"></i>
                            <span class="text-[9px] font-bold">SMS Text</span>
                        </a>
                        <a id="modalActionEmail" href="#" class="flex flex-col items-center justify-center p-2.5 bg-indigo-50 hover:bg-indigo-100/80 border border-indigo-200/80 rounded-2xl text-indigo-700 transition active:scale-95 shadow-2xs">
                            <i class="bi bi-envelope-fill text-sm mb-1 text-indigo-600"></i>
                            <span class="text-[9px] font-bold">Email</span>
                        </a>
                    </div>
                    <div class="mt-2 text-center">
                        <span id="modalCustomerPhoneText" class="text-[10px] font-semibold text-slate-600 select-all font-mono">--</span>
                    </div>
                </div>

                <!-- Delivery Destination & Map Navigation -->
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                            <i class="bi bi-geo-alt-fill text-sky-500"></i> Destination Address
                        </span>
                        <a id="modalActionMap" href="#" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[9px] font-bold text-sky-600 hover:text-sky-700 hover:underline">
                            <span>Open Maps</span>
                            <i class="bi bi-box-arrow-up-right text-[8px]"></i>
                        </a>
                    </div>
                    <p id="modalCustomerAddress" class="text-[11px] font-semibold text-slate-800 leading-relaxed break-words">--</p>
                    <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[10px] text-slate-500">
                        <span class="flex items-center gap-1"><i class="bi bi-calendar-event"></i> Schedule:</span>
                        <span id="modalCustomerSchedule" class="font-bold text-slate-700">--</span>
                    </div>
                </div>

                <!-- Payment & Collection Badge -->
                <div id="modalPaymentBox" class="p-3 rounded-2xl border flex items-center justify-between">
                    <div>
                        <span id="modalPaymentLabel" class="block text-[8px] font-bold uppercase tracking-wider">Payment Mode</span>
                        <span id="modalPaymentMethod" class="text-xs font-bold mt-0.5 block">COD</span>
                    </div>
                    <div class="text-right">
                        <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">To Collect</span>
                        <span id="modalPaymentAmount" class="text-xs font-black">₱0.00</span>
                    </div>
                </div>

                <!-- Ordered Items Breakdown -->
                <div class="space-y-2">
                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="bi bi-box-seam"></i> Order Package Items
                    </span>
                    <div id="modalOrderItemsContainer" class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1.5 divide-y divide-slate-100">
                        <!-- Populated dynamically -->
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-3 border-t border-slate-100 flex items-center gap-2 flex-shrink-0">
                <a id="modalViewFullOrderBtn" href="#" class="flex-1 py-2 px-3 rounded-xl text-[11px] font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 text-center transition cursor-pointer">
                    Full Order Page &rarr;
                </a>
                <button type="button" onclick="closeCustomerDetailsModal()" class="flex-1 py-2 px-3 rounded-xl text-[11px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 confirmation & query helper -->
    <script>
        // Query param helper
        function updateQueryStringParameter(uri, key, value) {
            var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            var separator = uri.indexOf('?') !== -1 ? "&" : "?";
            
            // Reset page to 1 on pagination changes
            uri = uri.replace(/([?&])page=\d+(&|$)/i, '$1page=1$2');
            
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + value + '$2');
            } else {
                return uri + separator + key + "=" + value;
            }
        }

        // Parse and convert inline confirms to SweetAlert2 on load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form[onsubmit]').forEach(form => {
                const onsubmitAttr = form.getAttribute('onsubmit');
                if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                    const match = onsubmitAttr.match(/confirm\(['"](.+)['"]\)/);
                    if (match && match[1]) {
                        const message = match[1].replace(/\\'/g, "'").replace(/\\"/g, '"');
                        form.setAttribute('data-confirm-message', message);
                    }
                    form.removeAttribute('onsubmit');
                }
            });

            // Rider notification dropdown toggle
            const riderBellBtn = document.getElementById('riderNotifBellBtn');
            const riderModal = document.getElementById('riderNotifModal');
            if (riderBellBtn && riderModal) {
                riderBellBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    riderModal.classList.toggle('hidden');
                });
                document.addEventListener('click', (e) => {
                    if (!riderModal.contains(e.target) && !riderBellBtn.contains(e.target)) {
                        riderModal.classList.add('hidden');
                    }
                });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        riderModal.classList.add('hidden');
                    }
                });
            }
        });

        // Global confirmation interceptor using SweetAlert2
        document.addEventListener('submit', function(e) {
            const form = e.target.closest('form');
            if (!form) return;
            
            if (form.dataset.swalSubmitted) {
                return;
            }
            
            const message = form.getAttribute('data-confirm-message');
            if (message) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0ea5e9',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, proceed!',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        popup: 'rounded-2xl border border-slate-100 shadow-xl'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.swalSubmitted = 'true';
                        form.submit();
                    }
                });
            }
        });

        // Global Customer Details Modal Handlers
        window.closeCustomerDetailsModal = function() {
            const modal = document.getElementById('riderCustomerDetailsModal');
            if (modal) modal.classList.add('hidden');
        };

        window.renderCustomerDetailsModal = function(order, customer) {
            const modal = document.getElementById('riderCustomerDetailsModal');
            if (!modal) return;

            document.getElementById('modalCustomerOrderRef').innerText = `ORDER #${order.order_number}`;
            document.getElementById('modalCustomerName').innerText = customer.name || 'Customer';
            document.getElementById('modalCustomerEmail').innerText = customer.email || 'No email provided';
            document.getElementById('modalCustomerSince').innerText = customer.member_since || 'Active Client';
            document.getElementById('modalCustomerOrderCount').innerText = `${customer.completed_orders_count || 0} completed`;

            // Avatar
            const avatarEl = document.getElementById('modalCustomerAvatar');
            if (customer.avatar) {
                avatarEl.innerHTML = `<img src="/storage/${customer.avatar}" alt="${customer.name}" class="w-full h-full object-cover rounded-2xl">`;
            } else {
                const initial = (customer.name || 'C').charAt(0).toUpperCase();
                avatarEl.innerHTML = `<span>${initial}</span>`;
            }

            // Phone & Communication Actions
            const phone = customer.phone || '';
            const phoneDisplay = document.getElementById('modalCustomerPhoneText');
            const callBtn = document.getElementById('modalActionCall');
            const smsBtn = document.getElementById('modalActionSms');
            const emailBtn = document.getElementById('modalActionEmail');

            if (phone) {
                phoneDisplay.innerText = `Contact: ${phone}`;
                callBtn.href = `tel:${phone}`;
                callBtn.classList.remove('opacity-50', 'pointer-events-none');
                smsBtn.href = `sms:${phone}`;
                smsBtn.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                phoneDisplay.innerText = 'No contact phone available';
                callBtn.removeAttribute('href');
                callBtn.classList.add('opacity-50', 'pointer-events-none');
                smsBtn.removeAttribute('href');
                smsBtn.classList.add('opacity-50', 'pointer-events-none');
            }

            if (customer.email) {
                emailBtn.href = `mailto:${customer.email}`;
                emailBtn.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                emailBtn.removeAttribute('href');
                emailBtn.classList.add('opacity-50', 'pointer-events-none');
            }

            // Address & Map
            const address = order.delivery_address || 'Store Pickup / No Address';
            document.getElementById('modalCustomerAddress').innerText = address;
            const mapBtn = document.getElementById('modalActionMap');
            mapBtn.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;

            // Schedule
            const schedDate = order.schedule_date || 'Today';
            const schedTime = order.schedule_time || 'Flexible Window';
            document.getElementById('modalCustomerSchedule').innerText = `${schedDate} • ${schedTime}`;

            // Payment Box
            const isPaid = order.is_paid;
            const paymentLabel = document.getElementById('modalPaymentLabel');
            const paymentMethod = document.getElementById('modalPaymentMethod');
            const paymentAmount = document.getElementById('modalPaymentAmount');
            const paymentBox = document.getElementById('modalPaymentBox');

            paymentMethod.innerText = (order.payment_method || 'COD').toUpperCase();
            if (isPaid) {
                paymentBox.className = 'p-3 rounded-2xl border bg-emerald-50/70 border-emerald-200 text-emerald-900 flex items-center justify-between';
                paymentLabel.innerText = 'ONLINE PAYMENT (PAID)';
                paymentLabel.className = 'block text-[8px] font-black uppercase tracking-wider text-emerald-700';
                paymentAmount.innerText = '₱0.00 to collect';
                paymentAmount.className = 'text-xs font-bold text-emerald-700 bg-white/80 px-2 py-0.5 rounded-lg border border-emerald-200';
            } else {
                paymentBox.className = 'p-3 rounded-2xl border bg-slate-50 border-slate-200 text-slate-800 flex items-center justify-between';
                paymentLabel.innerText = 'CASH ON DELIVERY';
                paymentLabel.className = 'block text-[8px] font-black uppercase tracking-wider text-slate-500';
                paymentAmount.innerText = `₱${order.total_amount}`;
                paymentAmount.className = 'text-sm font-black text-slate-950';
            }

            // Items list
            const itemsContainer = document.getElementById('modalOrderItemsContainer');
            itemsContainer.innerHTML = '';
            if (order.items && order.items.length > 0) {
                order.items.forEach(item => {
                    const itemRow = document.createElement('div');
                    itemRow.className = 'flex items-center justify-between text-xs py-1.5 first:pt-0 last:pb-0';
                    itemRow.innerHTML = `
                        <div class="min-w-0 pr-2">
                            <p class="font-bold text-slate-800 truncate text-[11px]">${item.name} ${item.size ? `(${item.size})` : ''}</p>
                            <p class="text-[9px] text-slate-400">₱${item.price} each</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-[9px] font-bold bg-slate-200/70 text-slate-700 px-1.5 py-0.25 rounded">x${item.quantity}</span>
                            <p class="text-[10px] font-bold text-slate-800 mt-0.5">₱${item.subtotal}</p>
                        </div>
                    `;
                    itemsContainer.appendChild(itemRow);
                });
            } else {
                itemsContainer.innerHTML = '<p class="text-[10px] text-slate-400 italic py-1">No items listed</p>';
            }

            // Dedicated order link button
            const viewFullBtn = document.getElementById('modalViewFullOrderBtn');
            if (viewFullBtn) {
                viewFullBtn.href = `/rider/orders/${order.id}`;
            }

            modal.classList.remove('hidden');
        };

        window.fetchAndShowCustomerDetails = async function(orderId) {
            try {
                const res = await fetch(`/rider/orders/${orderId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error('Could not load order details');
                const data = await res.json();
                if (data.order && data.customer) {
                    window.renderCustomerDetailsModal(data.order, data.customer);
                }
            } catch (e) {
                Swal.fire({
                    icon: 'info',
                    title: 'Customer Details',
                    text: 'Unable to retrieve details for this customer record.',
                    customClass: { popup: 'rounded-2xl' }
                });
            }
        };

        // Close on backdrop click and Escape key
        document.addEventListener('DOMContentLoaded', () => {
            const customerModal = document.getElementById('riderCustomerDetailsModal');
            if (customerModal) {
                customerModal.addEventListener('click', (e) => {
                    if (e.target === customerModal) {
                        window.closeCustomerDetailsModal();
                    }
                });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        window.closeCustomerDetailsModal();
                    }
                });
            }

            // Check if URL has ?customer_details={order_id} or ?show_order={order_id}
            const params = new URLSearchParams(window.location.search);
            const customerOrderId = params.get('customer_details') || params.get('show_order');
            if (customerOrderId) {
                window.fetchAndShowCustomerDetails(customerOrderId);
            }
        });

        // Real-Time Notification Poller & Harmonic Chime for Rider
        (function () {
            let lastNotifId = null;
            let initialLoad = true;
            let toastTimeout = null;

            function playChime() {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    const ctx = new AudioCtx();
                    const now = ctx.currentTime;

                    // First harmonic tone
                    const osc1 = ctx.createOscillator();
                    const gain1 = ctx.createGain();
                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(587.33, now); // D5
                    osc1.frequency.exponentialRampToValueAtTime(880, now + 0.12); // A5
                    gain1.gain.setValueAtTime(0.18, now);
                    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                    osc1.connect(gain1);
                    gain1.connect(ctx.destination);
                    osc1.start(now);
                    osc1.stop(now + 0.35);

                    // Second harmonic tone
                    const osc2 = ctx.createOscillator();
                    const gain2 = ctx.createGain();
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(880, now + 0.12);
                    osc2.frequency.exponentialRampToValueAtTime(1174.66, now + 0.28); // D6
                    gain2.gain.setValueAtTime(0.18, now + 0.12);
                    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                    osc2.connect(gain2);
                    gain2.connect(ctx.destination);
                    osc2.start(now + 0.12);
                    osc2.stop(now + 0.5);
                } catch (e) {
                    // Ignore user gesture restrictions
                }
            }

            window.dismissRiderToast = function () {
                const toast = document.getElementById('riderLiveToast');
                if (toast) {
                    toast.classList.add('-translate-y-24', 'opacity-0');
                }
                if (toastTimeout) clearTimeout(toastTimeout);
            };

            function showToast(notif) {
                const toast = document.getElementById('riderLiveToast');
                const titleEl = document.getElementById('riderToastTitle');
                const msgEl = document.getElementById('riderToastMessage');
                const timeEl = document.getElementById('riderToastTime');
                const linkEl = document.getElementById('riderToastLink');

                if (!toast || !notif) return;

                titleEl.textContent = notif.title || 'New Notification';
                msgEl.textContent = notif.message || '';
                timeEl.textContent = notif.time || 'Just now';
                if (linkEl && notif.link) {
                    linkEl.href = notif.link;
                }

                toast.classList.remove('-translate-y-24', 'opacity-0');
                playChime();

                if (toastTimeout) clearTimeout(toastTimeout);
                toastTimeout = setTimeout(() => {
                    dismissRiderToast();
                }, 6500);
            }

            function updateBellBadge(count) {
                const bellBtn = document.getElementById('riderNotifBellBtn');
                if (!bellBtn) return;

                let badge = bellBtn.querySelector('span');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'absolute top-0.5 right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white animate-pulse';
                        bellBtn.appendChild(badge);
                    }
                    badge.textContent = count;
                } else if (badge) {
                    badge.remove();
                }
            }

            async function checkLiveUpdates() {
                try {
                    const res = await fetch('{{ route("rider.api.liveCheck") }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    // If rider is logged out or session role changed (e.g., admin or customer)
                    if (res.status === 401 || res.status === 403) {
                        try {
                            const errorData = await res.json();
                            if (errorData && errorData.redirect) {
                                window.location.href = errorData.redirect;
                                return;
                            }
                        } catch (e) {}
                        window.location.href = '{{ route("login") }}';
                        return;
                    }

                    if (!res.ok) return;
                    const data = await res.json();

                    updateBellBadge(data.unread_count);

                    if (data.latest_notif && !data.latest_notif.is_read) {
                        if (!initialLoad && lastNotifId !== null && data.latest_notif.id !== lastNotifId) {
                            showToast(data.latest_notif);

                            // If on active dashboard and delivery count changed, auto-reload queue
                            if (window.location.pathname.endsWith('/rider/dashboard')) {
                                const activeCards = document.querySelectorAll('[id^="delivery-"]').length;
                                if (data.active_deliveries_count !== activeCards) {
                                    setTimeout(() => window.location.reload(), 1500);
                                }
                            }
                        }
                        lastNotifId = data.latest_notif.id;
                    }

                    initialLoad = false;
                } catch (e) {
                    // Silently ignore transient network blips
                }
            }

            // Initial poll after 2 seconds, then every 7 seconds
            setTimeout(checkLiveUpdates, 2000);
            setInterval(checkLiveUpdates, 7000);
        })();
    </script>
    @yield('scripts')
</body>
</html>
