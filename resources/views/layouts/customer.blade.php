<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="user-role" content="{{ auth()->user()->role }}">
    @endauth
    <title>@yield('title', 'DairyBox Storefront') | DairyBox</title>
    <!-- Favicon Links -->
    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #ffffff; /* White background */
        }
        .font-serif {
            font-family: 'Playfair Display', Georgia, serif;
        }
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 4px;
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
            min-width: 38px !important;
            height: 38px !important;
            padding: 0 12px !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            color: #475569 !important; /* slate-600 */
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important; /* rounded-xl */
            transition: all 0.2s ease-in-out !important;
            text-decoration: none !important;
            cursor: pointer !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        /* Standardize SVG sizes inside carets */
        nav[role="navigation"] a svg,
        nav[role="navigation"] span svg {
            width: 16px !important;
            height: 16px !important;
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
            box-shadow: 0 4px 12px -2px rgba(14, 165, 233, 0.3) !important;
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
<body class="h-full flex flex-col text-slate-800 antialiased">

    <!-- Top Navigation Bar -->
    <nav class="bg-white/90 backdrop-blur-md sticky top-0 z-40 border-b border-slate-100/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-8">
                    <a href="{{ url('/') }}" class="flex items-center space-x-2.5 hover:opacity-90 transition-opacity">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 flex items-center justify-center p-1 border border-sky-100 shadow-xs">
                            <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                        </div>
                        <span class="text-xl font-bold tracking-tight text-slate-900">
                            Dairy<span class="text-[#0ea5e9]">Box</span>
                        </span>
                    </a>

                    <!-- Desktop Navigation Links -->
                    <div class="hidden md:flex space-x-1.5">
                        <a href="{{ url('/') }}" class="px-4 py-2 rounded-full text-xs transition-all {{ request()->is('/') ? 'font-bold bg-slate-100 text-slate-900' : 'font-semibold text-slate-600 hover:text-slate-900' }}">
                            Home
                        </a>
                        <a href="{{ route('customer.catalog') }}" class="px-4 py-2 rounded-full text-xs transition-all {{ request()->routeIs('customer.catalog') || request()->routeIs('customer.product') ? 'font-bold bg-slate-100 text-slate-900' : 'font-semibold text-slate-600 hover:text-slate-900' }}">
                            Shop
                        </a>
                        @auth
                            @if(auth()->user()->role === 'customer')
                                <a href="{{ route('customer.orders.index') }}" class="px-4 py-2 rounded-full text-xs transition-all {{ request()->routeIs('customer.orders.*') ? 'font-bold bg-slate-100 text-slate-900' : 'font-semibold text-slate-600 hover:text-slate-900' }}">
                                    My Orders
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>

                @php
                    $cartItemCount = 0;
                    $notifCount = 0;
                    $allNotifications = collect();
                    if (auth()->check()) {
                        $cart = \App\Models\Cart::where('customer_id', auth()->id())->first();
                        $cartItemCount = $cart ? $cart->cartItems()->sum('quantity') : 0;
                        $notifCount = \App\Models\AppNotification::where('user_id', auth()->id())->where('is_read', false)->count();
                        $allNotifications = \App\Models\AppNotification::where('user_id', auth()->id())
                            ->orderBy('created_at', 'desc')
                            ->take(50)
                            ->get();
                    }
                @endphp

                <!-- Right Menu -->
                <div class="flex items-center space-x-4">
                    @auth
                        @if(auth()->user()->role === 'customer')
                            <!-- Notifications -->
                            <div class="relative">
                                <button id="notificationBellBtn" class="relative p-2 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-xl transition-all cursor-pointer focus:outline-none" title="Notifications">
                                    <i class="bi bi-bell text-xl"></i>
                                    @if($notifCount > 0)
                                        <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white animate-pulse">
                                            {{ $notifCount }}
                                        </span>
                                    @endif
                                </button>

                                <!-- Notifications Dropdown -->
                                <div id="notificationsModal" class="absolute right-0 mt-2 w-80 sm:w-96 bg-white/95 backdrop-blur-md rounded-2xl border border-slate-100 shadow-xl overflow-hidden hidden z-50 transform origin-top-right transition-all flex flex-col">
                                    <!-- Header -->
                                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                                        <div>
                                            <h3 class="text-xs font-bold text-slate-900">Notifications</h3>
                                        </div>
                                        <div class="flex items-center space-x-1.5">
                                            @if($notifCount > 0)
                                            <form action="{{ route('customer.notifications.readAll') }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-[10px] font-bold text-sky-600 hover:text-sky-700 bg-sky-50 hover:bg-sky-100/80 px-2.5 py-1.5 rounded-lg transition-all cursor-pointer">
                                                    Mark all as read
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Body (Scrollable list) -->
                                    <div class="overflow-y-auto divide-y divide-slate-100 max-h-[350px]">
                                        @forelse($allNotifications as $notif)
                                        <div class="group relative p-3.5 flex items-start justify-between gap-3 transition-all hover:bg-sky-50/40 {{ !$notif->is_read ? 'bg-sky-50/15' : '' }}">
                                            <a href="{{ route('notifications.readAndRedirect', $notif->id) }}" class="flex space-x-2.5 flex-1 min-w-0 pr-2 block">
                                                <!-- Read indicator dot -->
                                                <div class="mt-1 flex-shrink-0">
                                                    @if(!$notif->is_read)
                                                        <span class="block w-2 h-2 rounded-full bg-sky-500 ring-4 ring-sky-100"></span>
                                                    @else
                                                        <span class="block w-2 h-2 rounded-full bg-slate-200"></span>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between">
                                                        <h4 class="text-xs font-bold {{ !$notif->is_read ? 'text-slate-900 group-hover:text-sky-600' : 'text-slate-700 group-hover:text-sky-600' }} transition-colors">
                                                            {{ $notif->title }}
                                                        </h4>
                                                        <i class="bi bi-arrow-right text-[10px] text-slate-300 group-hover:text-sky-600 group-hover:translate-x-0.5 transition-all ml-1 flex-shrink-0"></i>
                                                    </div>
                                                    <p class="text-[10px] text-slate-500 mt-0.5 leading-relaxed break-words">{{ $notif->message }}</p>
                                                    <div class="flex items-center space-x-2 mt-1">
                                                        <span class="block text-[8px] text-slate-400 font-medium">{{ $notif->created_at->diffForHumans() }}</span>
                                                        <span class="text-[8px] font-semibold text-sky-600 opacity-0 group-hover:opacity-100 transition-opacity">View details &rarr;</span>
                                                    </div>
                                                </div>
                                            </a>

                                            @if(!$notif->is_read)
                                            <form action="{{ route('customer.notifications.read', $notif->id) }}" method="POST" class="flex-shrink-0 z-10" onclick="event.stopPropagation();">
                                                @csrf
                                                <button type="submit" class="p-1 px-1.5 border border-slate-200 rounded-lg text-[8px] font-bold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 transition-all shadow-2xs cursor-pointer" title="Mark as read without redirecting">
                                                    Mark read
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

                            <!-- Shopping Cart -->
                            <a href="{{ route('customer.cart.index') }}" id="navCartButton" class="nav-cart-btn relative p-2 text-slate-500 hover:text-[#0ea5e9] hover:bg-sky-50/50 rounded-xl transition-all" title="Shopping Cart">
                                <i class="bi bi-cart3 text-xl"></i>
                                <span class="cart-nav-badge absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#0ea5e9] text-[9px] font-bold text-white ring-2 ring-white {{ $cartItemCount > 0 ? '' : 'hidden' }}">
                                    {{ $cartItemCount }}
                                </span>
                            </a>

                            <!-- Divider -->
                            <span class="h-6 w-px bg-slate-200 hidden sm:block"></span>
                        @else
                            <!-- Dashboard Redirect Button for Admin / Rider -->
                            @if(auth()->user()->role === 'admin')
                                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center space-x-1 py-1.5 px-4 rounded-xl text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 transition-all border border-sky-100 mr-2 shadow-2xs">
                                    <span>Dashboard</span>
                                </a>
                            @elseif(auth()->user()->role === 'rider')
                                <a href="{{ route('rider.dashboard') }}" class="inline-flex items-center space-x-1 py-1.5 px-4 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-all border border-emerald-100 mr-2 shadow-2xs">
                                    <span>Rider Dashboard</span>
                                </a>
                            @endif
                        @endif

                        <!-- Profile Dropdown Menu Trigger -->
                        <div class="relative pl-2" id="customerUserDropdownContainer">
                            <button id="customerUserDropdownBtn" type="button" class="flex items-center space-x-2.5 p-1 rounded-xl hover:bg-slate-100/70 transition-all cursor-pointer focus:outline-none group">
                                <div class="hidden sm:block text-right">
                                    <p class="text-xs font-bold text-slate-800 leading-tight group-hover:text-sky-600 transition-colors">{{ auth()->user()->name }}</p>
                                    <p class="text-[10px] text-slate-400 font-medium leading-tight mt-0.5">{{ ucfirst(auth()->user()->role) }}</p>
                                </div>
                                <div class="w-9 h-9 rounded-full bg-sky-50 text-sky-700 flex items-center justify-center font-bold text-xs tracking-wider border border-sky-200/80 shadow-2xs group-hover:border-sky-300">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <i id="customerUserChevron" class="bi bi-chevron-down text-[10px] text-slate-400 group-hover:text-slate-600 transition-transform duration-200"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="customerUserDropdownMenu" class="absolute right-0 mt-2 w-64 bg-white/98 backdrop-blur-md rounded-2xl border border-slate-200/90 shadow-xl overflow-hidden hidden z-50 transform origin-top-right transition-all">
                                <!-- Dropdown Header -->
                                <div class="p-4 bg-slate-50/70 border-b border-slate-100 flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-sky-50 text-sky-700 font-bold border border-sky-200 flex items-center justify-center text-xs tracking-wider shadow-2xs flex-shrink-0">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                        <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-md text-[9px] font-bold bg-sky-50 text-sky-700 border border-sky-100">
                                            {{ ucfirst(auth()->user()->role) }} Account
                                        </span>
                                    </div>
                                </div>

                                <!-- Dropdown Navigation Links -->
                                <div class="p-1.5 space-y-0.5">
                                    @if(auth()->user()->isCustomer())
                                        <a href="{{ route('customer.settings.index') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-sky-600 hover:bg-sky-50/60 transition-colors">
                                            <i class="bi bi-gear-fill text-slate-400 text-sm"></i>
                                            <span>Account Settings</span>
                                        </a>
                                    @elseif(auth()->user()->isAdmin())
                                        <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-sky-600 hover:bg-sky-50/60 transition-colors">
                                            <i class="bi bi-grid-fill text-slate-400 text-sm"></i>
                                            <span>Admin Dashboard</span>
                                        </a>
                                        <a href="{{ route('admin.settings.index') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-sky-600 hover:bg-sky-50/60 transition-colors">
                                            <i class="bi bi-gear-fill text-slate-400 text-sm"></i>
                                            <span>Admin Settings</span>
                                        </a>
                                    @elseif(auth()->user()->isRider())
                                        <a href="{{ route('rider.dashboard') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-emerald-600 hover:bg-emerald-50/60 transition-colors">
                                            <i class="bi bi-bicycle text-slate-400 text-sm"></i>
                                            <span>Rider Dashboard</span>
                                        </a>
                                    @endif
                                </div>

                                <!-- Divider & Sign Out Form -->
                                <div class="p-1.5 border-t border-slate-100 bg-slate-50/40">
                                    <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Are you sure you want to sign out?')">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer text-left">
                                            <i class="bi bi-box-arrow-right text-sm text-rose-500"></i>
                                            <span>Sign Out</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Shopping Cart Icon for Guest (Redirects to Login via Auth Middleware) -->
                        <a href="{{ route('customer.cart.index') }}" class="relative p-2 text-slate-500 hover:text-[#0ea5e9] hover:bg-sky-50/50 rounded-xl transition-all mr-1" title="Shopping Cart">
                            <i class="bi bi-cart3 text-xl"></i>
                        </a>

                        <!-- Sign In -->
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center py-2 px-5 rounded-xl text-xs font-bold text-white bg-[#0ea5e9] hover:bg-sky-600 transition-all shadow-xs active:scale-95">
                            Sign in
                        </a>
                    @endauth
                </div>
            </div>
            
            <!-- Mobile Navigation Links (Bottom Navbar style on mobile for better touch access) -->
            <div class="md:hidden flex items-center justify-around py-2 border-t border-slate-100 bg-white">
                <a href="{{ url('/') }}" class="flex flex-col items-center space-y-0.5 text-xs font-semibold {{ request()->is('/') ? 'text-[#0ea5e9]' : 'text-slate-500' }}">
                    <i class="bi bi-house text-lg"></i>
                    <span>Home</span>
                </a>
                <a href="{{ route('customer.catalog') }}" class="flex flex-col items-center space-y-0.5 text-xs font-semibold {{ request()->routeIs('customer.catalog') || request()->routeIs('customer.product') ? 'text-[#0ea5e9]' : 'text-slate-500' }}">
                    <i class="bi bi-shop text-lg"></i>
                    <span>Shop</span>
                </a>
                @auth
                    @if(auth()->user()->role === 'customer')
                        <a href="{{ route('customer.orders.index') }}" class="flex flex-col items-center space-y-0.5 text-xs font-semibold {{ request()->routeIs('customer.orders.*') ? 'text-[#0ea5e9]' : 'text-slate-500' }}">
                            <i class="bi bi-clock-history text-lg"></i>
                            <span>Orders</span>
                        </a>
                        <a href="{{ route('customer.settings.index') }}" class="flex flex-col items-center space-y-0.5 text-xs font-semibold {{ request()->routeIs('customer.settings.*') ? 'text-[#0ea5e9]' : 'text-slate-500' }}">
                            <i class="bi bi-person-gear text-lg"></i>
                            <span>Settings</span>
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    @auth
        @if(auth()->user()->isCustomer() && auth()->user()->isRestricted())
            <div class="bg-rose-50 border-b border-rose-200 text-rose-800 text-xs py-2.5 px-4 font-medium">
                <div class="max-w-7xl mx-auto flex items-center justify-center gap-2">
                    <i class="bi bi-shield-slash-fill text-rose-600 text-sm"></i>
                    <span>Your ordering privileges are temporarily suspended until <strong class="font-bold">{{ auth()->user()->restricted_until->format('M d, Y h:i A') }}</strong> due to an order cancellation policy violation.</span>
                </div>
            </div>
        @endif
    @endauth

    <!-- Main Container -->
    <main class="flex-grow py-8 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
        
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

    <!-- Footer -->
    <footer class="bg-transparent mt-auto py-8 border-t border-slate-100/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Footer Logo -->
            <div class="flex items-center space-x-2.5">
                <div class="w-7 h-7 rounded-full bg-[#0ea5e9] flex items-center justify-center text-white">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 2h6M9 6h6m-7 4v10a2 2 0 002 2h6a2 2 0 002-2V10M8 10c0-2 2-3 4-3s4 1 4 3" />
                    </svg>
                </div>
                <span class="text-base font-bold tracking-tight text-slate-800 font-serif">DairyBox</span>
            </div>
            <!-- Footer Text -->
            <p class="text-xs text-slate-500 font-medium">&copy; {{ date('Y') }} DairyBox. Farm-fresh dairy, delivered.</p>
        </div>
    </footer>

    @if(auth()->check())
    <!-- Toggle Script -->
    <script>
        window.toggleNotificationsModal = function() {
            const modal = document.getElementById('notificationsModal');
            if (modal) {
                if (modal.classList.contains('hidden')) {
                    modal.classList.remove('hidden');
                } else {
                    modal.classList.add('hidden');
                }
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const bellBtn = document.getElementById('notificationBellBtn');
            const dropdown = document.getElementById('notificationsModal');

            if (bellBtn) {
                bellBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Close user dropdown if open
                    const uMenu = document.getElementById('customerUserDropdownMenu');
                    const uChevron = document.getElementById('customerUserChevron');
                    if (uMenu && !uMenu.classList.contains('hidden')) {
                        uMenu.classList.add('hidden');
                        if (uChevron) uChevron.style.transform = 'rotate(0deg)';
                    }
                    window.toggleNotificationsModal();
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    if (!dropdown.contains(event.target) && !bellBtn.contains(event.target)) {
                        dropdown.classList.add('hidden');
                    }
                }
            });

            // Customer user dropdown interactions
            const userBtn = document.getElementById('customerUserDropdownBtn');
            const userMenu = document.getElementById('customerUserDropdownMenu');
            const userChevron = document.getElementById('customerUserChevron');

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // Close notification modal if open
                    if (dropdown && !dropdown.classList.contains('hidden')) {
                        dropdown.classList.add('hidden');
                    }
                    const isHidden = userMenu.classList.toggle('hidden');
                    if (userChevron) {
                        userChevron.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!userMenu.contains(e.target) && !userBtn.contains(e.target)) {
                        userMenu.classList.add('hidden');
                        if (userChevron) userChevron.style.transform = 'rotate(0deg)';
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        userMenu.classList.add('hidden');
                        if (userChevron) userChevron.style.transform = 'rotate(0deg)';
                    }
                });
            }

            @if(session('success') === 'Notification marked as read.' || session('success') === 'All notifications marked as read.')
            window.toggleNotificationsModal();
            @endif
        });
    </script>
    @endif

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
    </script>
    @yield('scripts')
</body>
</html>
