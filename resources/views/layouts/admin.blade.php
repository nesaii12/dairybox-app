<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="user-role" content="{{ auth()->user()->role }}">
    @endauth
    <title>@yield('title', 'Admin Dashboard') | DairyBox</title>
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
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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
            color: #0284c7 !important; /* sky-600 */
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
        }
        /* Active page state */
        nav[role="navigation"] span[aria-current="page"] > span {
            background-color: #0284c7 !important; /* sky-600 */
            border-color: #0284c7 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 12px -2px rgba(2, 132, 199, 0.3) !important;
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
<body class="h-full flex flex-col md:flex-row text-slate-800 antialiased bg-slate-100/70">

    <!-- Mobile Header -->
    <header class="md:hidden bg-slate-900 border-b border-slate-800 px-4 py-3 flex items-center justify-between sticky top-0 z-40 shadow-md text-white">
        <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-lg bg-white p-1 flex items-center justify-center border border-slate-700/50 shadow-xs">
                <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
            </div>
            <span class="text-lg font-bold tracking-tight text-white">Dairy<span class="text-sky-400">Box</span></span>
            <span class="bg-slate-800 text-sky-400 text-[9px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full border border-slate-700/80">Admin</span>
        </div>
        <div class="flex items-center space-x-1">
            @include('admin.partials.notifications', ['isMobile' => true])
            <button id="mobile-menu-toggle" class="p-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition-colors focus:outline-none relative">
                <i class="bi bi-list text-2xl"></i>
                @if(($pendingOrdersCount ?? 0) > 0)
                    <span class="absolute top-2 right-2 w-2 h-2 bg-amber-400 rounded-full ring-2 ring-slate-900"></span>
                @endif
            </button>
        </div>
    </header>

    <!-- Sidebar Wrapper for Mobile Overlay / Desktop Fixed -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800/90 flex flex-col justify-between transform -translate-x-full md:translate-x-0 md:static transition-transform duration-300 ease-in-out shadow-2xl md:shadow-none text-slate-300 select-none">
        <div class="flex flex-col h-full">
            <!-- Sidebar Header -->
            <div class="px-6 py-5 border-b border-slate-800/80 hidden md:flex items-center justify-between bg-slate-950/40">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-white p-1.5 flex items-center justify-center border border-slate-700/50 shadow-md shadow-sky-950/50 flex-shrink-0 group-hover:scale-105 transition-transform">
                        <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <span class="text-xl font-black tracking-tight text-white block leading-tight">Dairy<span class="text-sky-400">Box</span></span>
                        <span class="text-[10px] uppercase font-bold tracking-widest text-slate-500 block">Command Center</span>
                    </div>
                </div>
                <span class="bg-slate-800 text-sky-400 text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-md border border-slate-700/80 shadow-2xs">Admin</span>
            </div>

            <!-- Mobile Sidebar close button -->
            <div class="px-6 py-4 border-b border-slate-800 flex md:hidden items-center justify-between bg-slate-950">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Navigation</span>
                </div>
                <button id="mobile-menu-close" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 px-3.5 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('admin.dashboard') }}" class="group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-grid-fill text-base {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span>Dashboard</span>
                </a>

                <div class="pt-5 pb-1.5 px-3.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Catalog</div>
                
                <a href="{{ route('admin.products.index') }}" class="group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.products.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-egg-fried text-base {{ request()->routeIs('admin.products.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span>Products</span>
                </a>
                
                <a href="{{ route('admin.categories.index') }}" class="group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.categories.index') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-tags-fill text-base {{ request()->routeIs('admin.categories.index') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span>Categories</span>
                </a>

                <div class="pt-5 pb-1.5 px-3.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Operations</div>

                <a href="{{ route('admin.inventory.index') }}" class="group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.inventory.index') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-archive-fill text-base {{ request()->routeIs('admin.inventory.index') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span>Inventory</span>
                </a>

                <a href="{{ route('admin.orders.index') }}" class="group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.orders.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <div class="flex items-center space-x-3">
                        <i class="bi bi-cart-check-fill text-base {{ request()->routeIs('admin.orders.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                        <span>Orders</span>
                    </div>
                    @if(($pendingOrdersCount ?? 0) > 0)
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold rounded-full {{ request()->routeIs('admin.orders.*') ? 'bg-sky-950/90 text-sky-200 border border-sky-400/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                            {{ $pendingOrdersCount }}
                        </span>
                    @endif
                </a>

                <div class="pt-5 pb-1.5 px-3.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Analytics & Users</div>

                <a href="{{ route('admin.customers.index') }}" class="group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.customers.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-people-fill text-base {{ request()->routeIs('admin.customers.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span>Customers</span>
                </a>

                <a href="{{ route('admin.riders.index') }}" class="group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.riders.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-bicycle text-base {{ request()->routeIs('admin.riders.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span>Riders</span>
                </a>

                <a href="{{ route('admin.deliveries.index') }}" class="group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.deliveries.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-truck text-base {{ request()->routeIs('admin.deliveries.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span>Delivery Monitor</span>
                </a>

                <a href="{{ route('admin.forecast.index') }}" class="group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.forecast.index') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-graph-up-arrow text-base {{ request()->routeIs('admin.forecast.index') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span>Forecasting</span>
                </a>

                <a href="{{ route('admin.reports.index') }}" class="group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.reports.index') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill text-base {{ request()->routeIs('admin.reports.index') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span>Reports System</span>
                </a>
            </nav>

            <!-- User profile footer (Mobile Drawer Only) -->
            <div class="p-3.5 border-t border-slate-800/80 bg-slate-950/60 flex md:hidden items-center justify-between">
                <a href="{{ route('admin.settings.index') }}" class="flex items-center space-x-3 overflow-hidden group">
                    <div class="w-9 h-9 rounded-xl bg-slate-800 text-sky-400 font-bold border border-slate-700/80 flex items-center justify-center text-xs tracking-wider shadow-2xs flex-shrink-0 group-hover:border-slate-600">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-semibold text-slate-100 truncate group-hover:text-sky-400 transition-colors">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="flex-shrink-0" onsubmit="return confirm('Are you sure you want to log out of DairyBox Admin?')">
                    @csrf
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800/80 rounded-lg transition-all" title="Logout">
                        <i class="bi bi-box-arrow-right text-lg"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Mobile Sidebar Backdrop overlay -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-950/70 z-40 hidden md:hidden backdrop-blur-xs"></div>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col overflow-y-auto">
        <!-- Top bar (Desktop only) -->
        <header class="hidden md:flex bg-white/95 backdrop-blur-xs border-b border-slate-200/90 px-8 py-3.5 items-center justify-between shadow-2xs sticky top-0 z-30">
            <div>
                <div class="flex items-center space-x-2.5">
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">@yield('page_title', 'Dashboard')</h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wider">Admin Portal</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">DairyBox Backoffice — {{ now()->format('l, F j, Y') }}</p>
            </div>
            
            <div class="flex items-center space-x-3">
                @include('admin.partials.notifications', ['isMobile' => false])

                <!-- Admin Profile Dropdown Menu Trigger -->
                <div class="relative pl-3 border-l border-slate-200" id="adminUserDropdownContainer">
                    <button id="adminUserDropdownBtn" type="button" class="flex items-center space-x-3 p-1.5 rounded-xl hover:bg-slate-50 transition-all cursor-pointer focus:outline-none group">
                        <div class="w-9 h-9 rounded-xl bg-slate-900 text-sky-400 font-bold border border-slate-800 flex items-center justify-center text-xs tracking-wider shadow-2xs flex-shrink-0 group-hover:border-slate-700">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="text-left hidden sm:block pr-1">
                            <p class="text-xs font-bold text-slate-900 leading-tight group-hover:text-sky-600 transition-colors">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-slate-500 font-medium leading-tight mt-0.5">{{ auth()->user()->email }}</p>
                        </div>
                        <i id="adminUserChevron" class="bi bi-chevron-down text-[10px] text-slate-400 group-hover:text-slate-600 transition-transform duration-200"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="adminUserDropdownMenu" class="absolute right-0 mt-2 w-64 bg-white/98 backdrop-blur-md rounded-2xl border border-slate-200/90 shadow-xl overflow-hidden hidden z-50 transform origin-top-right transition-all">
                        <!-- Dropdown Header -->
                        <div class="p-3.5 bg-slate-50/70 border-b border-slate-100 flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-900 text-sky-400 font-bold border border-slate-800 flex items-center justify-center text-xs tracking-wider shadow-2xs flex-shrink-0">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-md text-[9px] font-bold bg-sky-50 text-sky-700 border border-sky-100">
                                    Administrator
                                </span>
                            </div>
                        </div>

                        <!-- Dropdown Items -->
                        <div class="p-1.5 space-y-0.5">
                            <a href="{{ route('admin.settings.index') }}" class="flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-sky-600 hover:bg-sky-50/60 transition-colors">
                                <i class="bi bi-gear-fill text-slate-400 text-sm"></i>
                                <span>Settings</span>
                            </a>
                            <a href="{{ route('home') }}" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-sky-600 hover:bg-sky-50/60 transition-colors">
                                <div class="flex items-center space-x-2.5">
                                    <i class="bi bi-shop text-slate-400 text-sm"></i>
                                    <span>Storefront Preview</span>
                                </div>
                                <i class="bi bi-box-arrow-up-right text-[10px] text-slate-400"></i>
                            </a>
                        </div>

                        <!-- Sign Out Form -->
                        <div class="p-1.5 border-t border-slate-100 bg-slate-50/40">
                            <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Are you sure you want to log out of DairyBox Admin?')">
                                @csrf
                                <button type="submit" class="w-full flex items-center space-x-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer text-left">
                                    <i class="bi bi-box-arrow-right text-sm text-rose-500"></i>
                                    <span>Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Inner Content Window -->
        <div class="flex-1 p-4 md:p-8 max-w-[1600px] w-full mx-auto">
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
        </div>
    </main>

    <!-- Menu Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobile-menu-toggle');
            const menuClose = document.getElementById('mobile-menu-close');
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');

            function toggleSidebar() {
                sidebar.classList.toggle('-translate-x-full');
                backdrop.classList.toggle('hidden');
            }

            if (menuToggle && sidebar && backdrop) {
                menuToggle.addEventListener('click', toggleSidebar);
            }
            if (menuClose && sidebar && backdrop) {
                menuClose.addEventListener('click', toggleSidebar);
            }
            if (backdrop && sidebar) {
                backdrop.addEventListener('click', toggleSidebar);
            }

            // Notification dropdown interactions
            const desktopBtn = document.getElementById('adminNotifBtn');
            const mobileBtn = document.getElementById('adminMobileNotifBtn');
            const desktopModal = document.getElementById('adminNotifModal');
            const mobileModal = document.getElementById('adminMobileNotifModal');

            function setupNotifDropdown(btn, modal) {
                if (!btn || !modal) return;

                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Close user dropdown if open
                    const uMenu = document.getElementById('adminUserDropdownMenu');
                    const uChevron = document.getElementById('adminUserChevron');
                    if (uMenu && !uMenu.classList.contains('hidden')) {
                        uMenu.classList.add('hidden');
                        if (uChevron) uChevron.style.transform = 'rotate(0deg)';
                    }
                    modal.classList.toggle('hidden');
                });

                document.addEventListener('click', (e) => {
                    if (!modal.contains(e.target) && !btn.contains(e.target)) {
                        modal.classList.add('hidden');
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        modal.classList.add('hidden');
                    }
                });
            }

            setupNotifDropdown(desktopBtn, desktopModal);
            setupNotifDropdown(mobileBtn, mobileModal);

            // Admin user dropdown interactions
            const userBtn = document.getElementById('adminUserDropdownBtn');
            const userMenu = document.getElementById('adminUserDropdownMenu');
            const userChevron = document.getElementById('adminUserChevron');

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Close notification dropdown if open
                    if (desktopModal && !desktopModal.classList.contains('hidden')) {
                        desktopModal.classList.add('hidden');
                    }
                    const isHidden = userMenu.classList.toggle('hidden');
                    if (userChevron) {
                        userChevron.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
                    }
                });

                document.addEventListener('click', (e) => {
                    if (!userMenu.contains(e.target) && !userBtn.contains(e.target)) {
                        userMenu.classList.add('hidden');
                        if (userChevron) userChevron.style.transform = 'rotate(0deg)';
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        userMenu.classList.add('hidden');
                        if (userChevron) userChevron.style.transform = 'rotate(0deg)';
                    }
                });
            }

            function updateAdminUnreadCount(newCount) {
                document.querySelectorAll('.admin-notif-badge').forEach(badge => {
                    badge.textContent = newCount;
                    if (newCount > 0) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                });

                document.querySelectorAll('.admin-notif-header-badge').forEach(badge => {
                    if (newCount > 0) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                });

                document.querySelectorAll('.admin-unread-count-text').forEach(el => {
                    el.textContent = newCount;
                });

                if (newCount <= 0) {
                    document.querySelectorAll('.admin-mark-all-read-form').forEach(form => {
                        form.classList.add('hidden');
                    });
                }
            }

            // Mark single notification as read via AJAX
            document.addEventListener('submit', async (e) => {
                const form = e.target.closest('.admin-mark-read-form');
                if (!form) return;

                e.preventDefault();
                e.stopPropagation();

                const notifRow = form.closest('.admin-notif-item');
                const notifId = notifRow ? notifRow.dataset.id : null;
                const csrfToken = form.querySelector('input[name="_token"]').value;

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        document.querySelectorAll(`.admin-notif-item[data-id="${notifId}"]`).forEach(row => {
                            row.classList.remove('bg-sky-50/15');
                            const dot = row.querySelector('.notif-dot');
                            if (dot) {
                                dot.className = 'notif-dot block w-2 h-2 rounded-full bg-slate-200';
                            }
                            const title = row.querySelector('h4');
                            if (title) {
                                title.className = 'text-xs truncate text-slate-700 font-medium';
                            }
                            const markBtn = row.querySelector('.admin-mark-read-form');
                            if (markBtn) markBtn.remove();
                        });

                        updateAdminUnreadCount(data.unread_count);
                    }
                } catch (err) {
                    console.error('Failed to mark notification as read:', err);
                }
            });

            // Mark all notifications as read via AJAX
            document.addEventListener('submit', async (e) => {
                const form = e.target.closest('.admin-mark-all-read-form');
                if (!form) return;

                e.preventDefault();
                e.stopPropagation();

                const csrfToken = form.querySelector('input[name="_token"]').value;

                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        document.querySelectorAll('.admin-notif-item').forEach(row => {
                            row.classList.remove('bg-sky-50/15');
                            const dot = row.querySelector('.notif-dot');
                            if (dot) {
                                dot.className = 'notif-dot block w-2 h-2 rounded-full bg-slate-200';
                            }
                            const title = row.querySelector('h4');
                            if (title) {
                                title.className = 'text-xs truncate text-slate-700 font-medium';
                            }
                            const markBtn = row.querySelector('.admin-mark-read-form');
                            if (markBtn) markBtn.remove();
                        });

                        updateAdminUnreadCount(0);
                    }
                } catch (err) {
                    console.error('Failed to mark all notifications as read:', err);
                }
            });
        });
    </script>
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
                    confirmButtonColor: '#0284c7',
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
