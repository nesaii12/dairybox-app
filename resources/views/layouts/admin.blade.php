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
    
    <!-- Instant Sidebar Collapse State Restore (Prevents Layout Flash) -->
    <script>
        (function() {
            try {
                if (localStorage.getItem('admin_sidebar_collapsed') === 'true' && window.innerWidth >= 768) {
                    document.documentElement.classList.add('sidebar-collapsed-mode');
                }
            } catch (e) {}
        })();
    </script>

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

        /* ========================================================
           ADMIN SIDEBAR COLLAPSE & EXPAND STYLING
           ======================================================== */
        #sidebar {
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (min-width: 768px) {
            html.sidebar-collapsed-mode #sidebar,
            #sidebar.is-collapsed {
                width: 5rem !important; /* 80px */
            }

            html.sidebar-collapsed-mode #sidebar .sidebar-text,
            #sidebar.is-collapsed .sidebar-text,
            html.sidebar-collapsed-mode #sidebar .sidebar-badge-full,
            #sidebar.is-collapsed .sidebar-badge-full,
            html.sidebar-collapsed-mode #sidebar .sidebar-admin-badge,
            #sidebar.is-collapsed .sidebar-admin-badge,
            html.sidebar-collapsed-mode #sidebar .sidebar-divider-text,
            #sidebar.is-collapsed .sidebar-divider-text {
                display: none !important;
            }

            html.sidebar-collapsed-mode #sidebar .sidebar-divider-line,
            #sidebar.is-collapsed .sidebar-divider-line {
                display: block !important;
            }

            html.sidebar-collapsed-mode #sidebar .sidebar-section-header,
            #sidebar.is-collapsed .sidebar-section-header {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }

            html.sidebar-collapsed-mode #sidebar .sidebar-nav-link,
            #sidebar.is-collapsed .sidebar-nav-link {
                justify-content: center !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }

            html.sidebar-collapsed-mode #sidebar .sidebar-nav-link i,
            #sidebar.is-collapsed .sidebar-nav-link i {
                margin: 0 !important;
                font-size: 1.25rem !important;
            }

            html.sidebar-collapsed-mode #sidebar .sidebar-badge-dot,
            #sidebar.is-collapsed .sidebar-badge-dot {
                display: block !important;
            }

            html.sidebar-collapsed-mode #sidebar #quickAccessHeaderBtn,
            #sidebar.is-collapsed #quickAccessHeaderBtn {
                justify-content: center !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            html.sidebar-collapsed-mode #sidebar #sidebarHeaderContent,
            #sidebar.is-collapsed #sidebarHeaderContent {
                flex-direction: column !important;
                gap: 0.5rem !important;
                padding: 0.75rem 0.25rem !important;
                align-items: center !important;
                justify-content: center !important;
            }

            html.sidebar-collapsed-mode #sidebar #sidebarHeaderActions,
            #sidebar.is-collapsed #sidebarHeaderActions {
                width: 100% !important;
                justify-content: center !important;
            }
        }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
</head>
<body class="h-full flex flex-col md:flex-row text-slate-800 antialiased bg-[#FAF8F2]">

    <!-- Mobile Header -->
    <header class="md:hidden bg-slate-900 border-b border-slate-800 px-4 py-3 flex items-center justify-between sticky top-0 z-40 shadow-md text-white">
        <button type="button" id="quickAccessMobileHeaderBtn" class="flex items-center space-x-2.5 text-left focus:outline-none cursor-pointer group" title="Open Quick-Access Shortcuts">
            <div class="w-8 h-8 rounded-lg bg-white p-1 flex items-center justify-center border border-slate-700/50 shadow-xs group-hover:scale-105 transition-transform">
                <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
            </div>
            <span class="text-lg font-bold tracking-tight text-white">Dairy<span class="text-sky-400">Box</span></span>
            <span class="bg-slate-800 text-sky-400 text-[9px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full border border-slate-700/80">Admin</span>
        </button>
        <div class="flex items-center space-x-1">
            @include('admin.partials.notifications', ['isMobile' => true])
            <button id="mobile-menu-toggle" class="p-2 text-slate-300 hover:text-white hover:bg-slate-800 rounded-lg transition-colors focus:outline-none relative" aria-label="Open mobile menu">
                <i class="bi bi-list text-2xl"></i>
                @if(($pendingOrdersCount ?? 0) > 0)
                    <span class="absolute top-2 right-2 w-2 h-2 bg-amber-400 rounded-full ring-2 ring-slate-900"></span>
                @endif
            </button>
        </div>
    </header>

    <!-- Mobile Page Sub-header / Context Bar -->
    <div class="md:hidden bg-[#fffdf8]/95 backdrop-blur-xs border-b border-amber-200/50 px-4 py-2 flex items-center justify-between sticky top-[53px] z-30 shadow-2xs">
        <div class="flex items-center space-x-2 min-w-0">
            <h1 class="text-xs font-black text-slate-900 tracking-tight truncate">@yield('page_title', 'Dashboard')</h1>
        </div>
        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[8px] font-bold bg-amber-100/80 text-amber-900 border border-amber-200/90 uppercase tracking-wider shrink-0">
            Command Center
        </span>
    </div>

    <!-- Sidebar Wrapper for Mobile Overlay / Desktop Fixed -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800/90 flex flex-col justify-between transform -translate-x-full md:translate-x-0 md:static transition-all duration-300 ease-in-out shadow-2xl md:shadow-none text-slate-300 select-none">
        <div class="flex flex-col h-full">
            <!-- Sidebar Header -->
            <div id="sidebarHeaderContent" class="px-5 py-4 border-b border-slate-800/80 hidden md:flex items-center justify-between bg-slate-950/40 transition-all duration-200">
                <!-- Clickable Brand / Quick Access Trigger -->
                <button type="button" id="quickAccessHeaderBtn" class="flex items-center space-x-3 group text-left p-1.5 -ml-1 rounded-xl hover:bg-slate-800/60 focus:bg-slate-800/60 transition-all cursor-pointer focus:outline-none" title="DairyBox Command Center — Click for Quick-Access Shortcuts (Ctrl+K)">
                    <div class="w-10 h-10 rounded-xl bg-white p-1.5 flex items-center justify-center border border-slate-700/50 shadow-md shadow-sky-950/50 shrink-0 group-hover:scale-105 group-hover:shadow-sky-500/20 transition-all">
                        <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                    </div>
                    <div class="sidebar-text overflow-hidden">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl font-black tracking-tight text-white block leading-tight">Dairy<span class="text-sky-400">Box</span></span>
                            <i class="bi bi-lightning-charge-fill text-[11px] text-amber-400 opacity-75 group-hover:opacity-100 group-hover:scale-110 transition-all" title="Quick-Access Shortcuts"></i>
                        </div>
                        <span class="text-[10px] uppercase font-bold tracking-widest text-slate-400 block group-hover:text-sky-300 transition-colors">Command Center</span>
                    </div>
                </button>

                <!-- Header Actions: Admin Badge & Hide/Show Arrow Button -->
                <div class="flex items-center space-x-1.5" id="sidebarHeaderActions">
                    <span class="sidebar-admin-badge bg-amber-400/20 text-amber-300 text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-md border border-amber-400/30 shadow-2xs">Admin</span>
                    <button type="button" id="sidebarCollapseBtn" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800/90 rounded-lg transition-all cursor-pointer focus:outline-none" title="Collapse navigation menu (get more screen space) [Ctrl+B]" aria-label="Toggle Navigation Menu">
                        <i id="sidebarCollapseIcon" class="bi bi-chevron-left text-sm transition-transform duration-200"></i>
                    </button>
                </div>
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
                <a href="{{ route('admin.dashboard') }}" title="Dashboard" class="sidebar-nav-link group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-grid-fill text-base {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span class="sidebar-text truncate">Dashboard</span>
                </a>

                <div class="sidebar-section-header pt-4 pb-1.5 px-3.5">
                    <span class="sidebar-divider-text text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Catalog</span>
                    <div class="sidebar-divider-line hidden border-t border-slate-800 my-1"></div>
                </div>
                
                <a href="{{ route('admin.products.index') }}" title="Products" class="sidebar-nav-link group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.products.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-egg-fried text-base {{ request()->routeIs('admin.products.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span class="sidebar-text truncate">Products</span>
                </a>
                
                <a href="{{ route('admin.categories.index') }}" title="Categories" class="sidebar-nav-link group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.categories.index') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-tags-fill text-base {{ request()->routeIs('admin.categories.index') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span class="sidebar-text truncate">Categories</span>
                </a>

                <div class="sidebar-section-header pt-4 pb-1.5 px-3.5">
                    <span class="sidebar-divider-text text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Operations</span>
                    <div class="sidebar-divider-line hidden border-t border-slate-800 my-1"></div>
                </div>

                <a href="{{ route('admin.inventory.index') }}" title="Inventory" class="sidebar-nav-link group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.inventory.index') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-archive-fill text-base {{ request()->routeIs('admin.inventory.index') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span class="sidebar-text truncate">Inventory</span>
                </a>

                <a href="{{ route('admin.orders.index') }}" title="Orders @if(($pendingOrdersCount ?? 0) > 0)({{ $pendingOrdersCount }} pending)@endif" class="sidebar-nav-link group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all relative {{ request()->routeIs('admin.orders.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <div class="flex items-center space-x-3 min-w-0">
                        <i class="bi bi-cart-check-fill text-base shrink-0 {{ request()->routeIs('admin.orders.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                        <span class="sidebar-text truncate">Orders</span>
                    </div>
                    @if(($pendingOrdersCount ?? 0) > 0)
                        <span class="sidebar-badge-full inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold rounded-full {{ request()->routeIs('admin.orders.*') ? 'bg-sky-950/90 text-sky-200 border border-sky-400/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                            {{ $pendingOrdersCount }}
                        </span>
                        <span class="sidebar-badge-dot hidden absolute top-1.5 right-2 w-2 h-2 rounded-full bg-amber-400 ring-2 ring-slate-900 animate-pulse"></span>
                    @endif
                </a>

                <a href="{{ route('admin.walkInSales.create') }}" title="Walk-in Sale" class="sidebar-nav-link group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.walkInSales.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-person-walking text-base {{ request()->routeIs('admin.walkInSales.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span class="sidebar-text truncate">Walk-in Sale</span>
                </a>

                <a href="{{ route('admin.reservations.index') }}" title="Reservations @if(($activeReservationsCount ?? 0) > 0)({{ $activeReservationsCount }} active)@endif" class="sidebar-nav-link group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all relative {{ request()->routeIs('admin.reservations.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <div class="flex items-center space-x-3 min-w-0">
                        <i class="bi bi-calendar2-check-fill text-base shrink-0 {{ request()->routeIs('admin.reservations.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                        <span class="sidebar-text truncate">Reservations</span>
                    </div>
                    @if(($activeReservationsCount ?? 0) > 0)
                        <span class="sidebar-badge-full inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold rounded-full {{ request()->routeIs('admin.reservations.*') ? 'bg-sky-950/90 text-sky-200 border border-sky-400/30' : 'bg-sky-500/20 text-sky-300 border border-sky-500/30' }}">
                            {{ $activeReservationsCount }}
                        </span>
                        <span class="sidebar-badge-dot hidden absolute top-1.5 right-2 w-2 h-2 rounded-full bg-sky-400 ring-2 ring-slate-900"></span>
                    @endif
                </a>

                <div class="sidebar-section-header pt-4 pb-1.5 px-3.5">
                    <span class="sidebar-divider-text text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Analytics & Users</span>
                    <div class="sidebar-divider-line hidden border-t border-slate-800 my-1"></div>
                </div>

                <a href="{{ route('admin.customers.index') }}" title="Customers" class="sidebar-nav-link group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.customers.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-people-fill text-base {{ request()->routeIs('admin.customers.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span class="sidebar-text truncate">Customers</span>
                </a>

                <a href="{{ route('admin.riders.index') }}" title="Riders" class="sidebar-nav-link group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.riders.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-bicycle text-base {{ request()->routeIs('admin.riders.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span class="sidebar-text truncate">Riders</span>
                </a>

                <a href="{{ route('admin.remittances.index') }}" title="Rider Remittance @if(($pendingRemittancesCount ?? 0) > 0)({{ $pendingRemittancesCount }} rider(s) with pending cash)@endif" class="sidebar-nav-link group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all relative {{ request()->routeIs('admin.remittances.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <div class="flex items-center space-x-3 min-w-0">
                        <i class="bi bi-cash-stack text-base shrink-0 {{ request()->routeIs('admin.remittances.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                        <span class="sidebar-text truncate">Rider Remittance</span>
                    </div>
                    @if(($pendingRemittancesCount ?? 0) > 0)
                        <span class="sidebar-badge-full inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold rounded-full {{ request()->routeIs('admin.remittances.*') ? 'bg-sky-950/90 text-amber-300 border border-amber-400/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1 animate-pulse"></span>
                            {{ $pendingRemittancesCount }}
                        </span>
                        <span class="sidebar-badge-dot hidden absolute top-1.5 right-2 w-2 h-2 rounded-full bg-amber-400 ring-2 ring-slate-900 animate-pulse"></span>
                    @endif
                </a>

                <a href="{{ route('admin.deliveries.index') }}" title="Delivery Monitor @if(($arrivedDeliveriesCount ?? 0) > 0)({{ $arrivedDeliveriesCount }} arrived)@endif" class="sidebar-nav-link group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all relative {{ request()->routeIs('admin.deliveries.*') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <div class="flex items-center space-x-3 min-w-0">
                        <i class="bi bi-truck text-base shrink-0 {{ request()->routeIs('admin.deliveries.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                        <span class="sidebar-text truncate">Delivery Monitor</span>
                    </div>
                    @if(($arrivedDeliveriesCount ?? 0) > 0)
                        <span class="sidebar-badge-full inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold rounded-full {{ request()->routeIs('admin.deliveries.*') ? 'bg-sky-950/90 text-amber-300 border border-amber-400/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1 animate-pulse"></span>
                            {{ $arrivedDeliveriesCount }}
                        </span>
                        <span class="sidebar-badge-dot hidden absolute top-1.5 right-2 w-2 h-2 rounded-full bg-amber-400 ring-2 ring-slate-900 animate-pulse"></span>
                    @endif
                </a>

                <a href="{{ route('admin.forecast.index') }}" title="Forecasting" class="sidebar-nav-link group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.forecast.index') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-graph-up-arrow text-base {{ request()->routeIs('admin.forecast.index') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span class="sidebar-text truncate">Forecasting</span>
                </a>

                <a href="{{ route('admin.reports.index') }}" title="Reports System" class="sidebar-nav-link group flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('admin.reports.index') ? 'bg-sky-600 text-white font-semibold shadow-md shadow-sky-950/40' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill text-base {{ request()->routeIs('admin.reports.index') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                    <span class="sidebar-text truncate">Reports System</span>
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
        <header class="hidden md:flex bg-[#fffdf8]/95 backdrop-blur-xs border-b border-amber-200/50 px-8 py-3.5 items-center justify-between shadow-2xs sticky top-0 z-30">
            <div>
                <div class="flex items-center space-x-2.5">
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">@yield('page_title', 'Dashboard')</h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100/80 text-amber-900 border border-amber-200/90 uppercase tracking-wider">Admin Portal</span>
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
                    <div id="adminUserDropdownMenu" class="absolute right-0 mt-2 w-64 max-w-[calc(100vw-1.5rem)] bg-white/98 backdrop-blur-md rounded-2xl border border-slate-200/90 shadow-xl overflow-hidden hidden z-50 transform origin-top-right transition-all">
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
        <div class="flex-1 p-3.5 sm:p-6 md:p-8 pb-28 md:pb-8 max-w-[1600px] w-full mx-auto">
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

    <!-- Admin Mobile Bottom Quick-Dock Bar -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 text-slate-300 pb-safe shadow-2xl px-2 py-1.5 flex items-center justify-around select-none">
        <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->routeIs('admin.dashboard') ? 'text-sky-400 font-bold bg-slate-800/80 shadow-xs' : 'text-slate-400 hover:text-slate-200' }}">
            <i class="bi bi-grid-fill text-base leading-none mb-1"></i>
            <span class="text-[10px] tracking-tight">Overview</span>
        </a>

        <a href="{{ route('admin.orders.index') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all relative {{ request()->routeIs('admin.orders.*') ? 'text-sky-400 font-bold bg-slate-800/80 shadow-xs' : 'text-slate-400 hover:text-slate-200' }}">
            <i class="bi bi-cart-check-fill text-base leading-none mb-1"></i>
            <span class="text-[10px] tracking-tight">Orders</span>
            @if(($pendingOrdersCount ?? 0) > 0)
                <span class="absolute top-0 right-1 px-1.5 py-0.2 text-[8px] font-black rounded-full bg-amber-400 text-slate-950 ring-2 ring-slate-900">
                    {{ $pendingOrdersCount }}
                </span>
            @endif
        </a>

        <a href="{{ route('admin.inventory.index') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->routeIs('admin.inventory.*') ? 'text-sky-400 font-bold bg-slate-800/80 shadow-xs' : 'text-slate-400 hover:text-slate-200' }}">
            <i class="bi bi-archive-fill text-base leading-none mb-1"></i>
            <span class="text-[10px] tracking-tight">Inventory</span>
        </a>

        <a href="{{ route('admin.customers.index') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->routeIs('admin.customers.*') ? 'text-sky-400 font-bold bg-slate-800/80 shadow-xs' : 'text-slate-400 hover:text-slate-200' }}">
            <i class="bi bi-people-fill text-base leading-none mb-1"></i>
            <span class="text-[10px] tracking-tight">Customers</span>
        </a>

        <button type="button" id="mobile-dock-menu-toggle" class="flex flex-col items-center py-1 px-3 rounded-xl text-slate-400 hover:text-slate-200 transition-all cursor-pointer">
            <i class="bi bi-list-nested text-base leading-none mb-1"></i>
            <span class="text-[10px] tracking-tight">More</span>
        </button>
    </nav>

    <!-- Quick-Access Pop-up Modal -->
    <div id="quickAccessModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 sm:p-6 bg-slate-950/75 backdrop-blur-sm transition-all duration-200 select-none" role="dialog" aria-modal="true" aria-labelledby="quickAccessTitle">
        <div id="quickAccessBackdrop" class="fixed inset-0"></div>

        <div id="quickAccessDialog" class="relative w-full max-w-2xl bg-slate-900 border border-slate-700/80 rounded-3xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 z-10">
            <!-- Modal Top Ambient Glow -->
            <div class="absolute -top-16 -left-16 w-48 h-48 rounded-full bg-sky-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-16 -right-16 w-48 h-48 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>

            <!-- Header -->
            <div class="px-6 py-5 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between relative z-10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-white p-1.5 flex items-center justify-center border border-slate-700/50 shadow-md shadow-sky-950/50 shrink-0">
                        <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 id="quickAccessTitle" class="text-base font-extrabold text-white tracking-tight">DairyBox Command Center</h3>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 uppercase tracking-wider">Quick Access</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Instant shortcuts for frequent daily backoffice operations</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="hidden sm:inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-mono text-slate-400 bg-slate-800/80 border border-slate-700/80">Esc to close</span>
                    <button type="button" id="quickAccessCloseBtn" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800/80 rounded-xl transition-colors cursor-pointer" aria-label="Close Quick Access">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Shortcuts Grid -->
            <div class="p-6 relative z-10 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                    <span>Daily Operations Shortcuts</span>
                    <span class="text-[10px] text-slate-500 font-normal">Click any card to launch</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- 1. Walk-in Sale -->
                    <a href="{{ route('admin.walkInSales.create') }}" onclick="if(typeof openWalkInModal === 'function') { event.preventDefault(); if (window.closeQuickAccessModal) window.closeQuickAccessModal(); openWalkInModal(); }" class="group p-4 rounded-2xl bg-slate-800/60 hover:bg-emerald-950/30 border border-slate-700/70 hover:border-emerald-500/50 transition-all flex items-start space-x-3.5 shadow-xs hover:shadow-md cursor-pointer">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-emerald-500/20 transition-all">
                            <i class="bi bi-person-walking text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-white group-hover:text-emerald-300 transition-colors">Walk-in Sale</span>
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">Counter POS</span>
                            </div>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">Record counter cash sale, update stock levels and generate printable receipt.</p>
                        </div>
                    </a>

                    <!-- 2. Reservations -->
                    <a href="{{ route('admin.reservations.index') }}" class="group p-4 rounded-2xl bg-slate-800/60 hover:bg-sky-950/30 border border-slate-700/70 hover:border-sky-500/50 transition-all flex items-start space-x-3.5 shadow-xs hover:shadow-md cursor-pointer">
                        <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-400 border border-sky-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-sky-500/20 transition-all">
                            <i class="bi bi-calendar2-check-fill text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-white group-hover:text-sky-300 transition-colors">Reservations</span>
                                @if(($activeReservationsCount ?? 0) > 0)
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-sky-500/20 text-sky-300 border border-sky-500/30">{{ $activeReservationsCount }} Active</span>
                                @else
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-sky-500/10 text-sky-300 border border-sky-500/20">Pickups</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">Reserve stock for scheduled pickups, manage down payments, and fulfill orders.</p>
                        </div>
                    </a>

                    <!-- 3. Manage Orders -->
                    <a href="{{ route('admin.orders.index') }}" class="group p-4 rounded-2xl bg-slate-800/60 hover:bg-sky-950/30 border border-slate-700/70 hover:border-sky-500/50 transition-all flex items-start space-x-3.5 shadow-xs hover:shadow-md cursor-pointer">
                        <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-400 border border-sky-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-sky-500/20 transition-all">
                            <i class="bi bi-cart-check-fill text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-white group-hover:text-sky-300 transition-colors">Orders</span>
                                @if(($pendingOrdersCount ?? 0) > 0)
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30">{{ $pendingOrdersCount }} Pending</span>
                                @else
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-sky-500/10 text-sky-300 border border-sky-500/20">Fulfillment</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">Review customer orders, verify online payments, and dispatch for delivery.</p>
                        </div>
                    </a>

                    <!-- 3. Rider Remittance -->
                    <a href="{{ route('admin.remittances.index') }}" class="group p-4 rounded-2xl bg-slate-800/60 hover:bg-amber-950/30 border border-slate-700/70 hover:border-amber-500/50 transition-all flex items-start space-x-3.5 shadow-xs hover:shadow-md cursor-pointer">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-amber-500/20 transition-all">
                            <i class="bi bi-cash-stack text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-white group-hover:text-amber-300 transition-colors">Rider Remittance</span>
                                @if(($pendingFleetCash ?? 0) > 0)
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        <span class="w-1 h-1 rounded-full bg-amber-400 inline-block mr-1 animate-pulse"></span>
                                        ₱{{ number_format($pendingFleetCash, 2) }} Pending
                                    </span>
                                @else
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-300 border border-amber-500/20">COD Cash</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">Collect, verify, and acknowledge cash-on-delivery payments submitted by riders.</p>
                        </div>
                    </a>

                    <!-- 4. Demand Forecasting -->
                    <a href="{{ route('admin.forecast.index') }}" class="group p-4 rounded-2xl bg-slate-800/60 hover:bg-indigo-950/30 border border-slate-700/70 hover:border-indigo-500/50 transition-all flex items-start space-x-3.5 shadow-xs hover:shadow-md cursor-pointer">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-indigo-500/20 transition-all">
                            <i class="bi bi-graph-up-arrow text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-white group-hover:text-indigo-300 transition-colors">Demand Forecasting</span>
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">AI Demand</span>
                            </div>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">Predict next-month product demand, analyze seasonal patterns & restock risks.</p>
                        </div>
                    </a>

                    <!-- 5. Inventory & Batches -->
                    <a href="{{ route('admin.inventory.index') }}" class="group p-4 rounded-2xl bg-slate-800/60 hover:bg-rose-950/30 border border-slate-700/70 hover:border-rose-500/50 transition-all flex items-start space-x-3.5 shadow-xs hover:shadow-md cursor-pointer">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-rose-500/20 transition-all">
                            <i class="bi bi-archive-fill text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-white group-hover:text-rose-300 transition-colors">Inventory</span>
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-300 border border-rose-500/20">Stock Alerts</span>
                            </div>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">Track real-time stock counts, upcoming batch expirations, and log restocks.</p>
                        </div>
                    </a>

                    <!-- 6. Delivery Monitor -->
                    <a href="{{ route('admin.deliveries.index') }}" class="group p-4 rounded-2xl bg-slate-800/60 hover:bg-teal-950/30 border border-slate-700/70 hover:border-teal-500/50 transition-all flex items-start space-x-3.5 shadow-xs hover:shadow-md cursor-pointer">
                        <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 border border-teal-500/20 flex items-center justify-center shrink-0 group-hover:scale-105 group-hover:bg-teal-500/20 transition-all">
                            <i class="bi bi-truck text-lg"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-white group-hover:text-teal-300 transition-colors">Delivery Monitor</span>
                                @if(($arrivedDeliveriesCount ?? 0) > 0)
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30">{{ $arrivedDeliveriesCount }} Arrived</span>
                                @else
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-teal-500/10 text-teal-300 border border-teal-500/20">Live Transit</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">Live dispatch tracking, route updates, and rider arrival alerts in real time.</p>
                        </div>
                    </a>
                </div>

                <!-- Additional Quick Actions & Sidebar Toggle -->
                <div class="pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2.5">
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 transition-all">
                            <i class="bi bi-file-earmark-bar-graph text-slate-400"></i>
                            <span>Reports System</span>
                        </a>
                        <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/80 transition-all">
                            <i class="bi bi-shop text-slate-400"></i>
                            <span>Storefront Preview</span>
                        </a>
                    </div>
                    <button type="button" id="quickAccessToggleSidebarBtn" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-sky-300 hover:text-white bg-sky-950/70 hover:bg-sky-900 border border-sky-700/60 transition-all cursor-pointer">
                        <i class="bi bi-layout-sidebar-inset text-xs"></i>
                        <span id="quickAccessToggleSidebarText">Collapse Sidebar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobile-menu-toggle');
            const menuDockToggle = document.getElementById('mobile-dock-menu-toggle');
            const menuClose = document.getElementById('mobile-menu-close');
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');

            function toggleSidebar() {
                const isOpening = sidebar.classList.contains('-translate-x-full');
                sidebar.classList.toggle('-translate-x-full');
                backdrop.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden', isOpening);
            }

            function closeSidebar() {
                if (!sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            }

            if (menuToggle) menuToggle.addEventListener('click', toggleSidebar);
            if (menuDockToggle) menuDockToggle.addEventListener('click', toggleSidebar);
            if (menuClose) menuClose.addEventListener('click', closeSidebar);
            if (backdrop) backdrop.addEventListener('click', closeSidebar);

            // ========================================================
            // SIDEBAR COLLAPSE & EXPAND INTERACTION
            // ========================================================
            const collapseBtn = document.getElementById('sidebarCollapseBtn');
            const collapseIcon = document.getElementById('sidebarCollapseIcon');
            const quickAccessToggleBtn = document.getElementById('quickAccessToggleSidebarBtn');
            const quickAccessToggleText = document.getElementById('quickAccessToggleSidebarText');

            function setSidebarCollapsedState(collapsed, persist = true) {
                if (!sidebar) return;

                if (collapsed) {
                    sidebar.classList.add('is-collapsed');
                    document.documentElement.classList.add('sidebar-collapsed-mode');
                    if (collapseIcon) {
                        collapseIcon.className = 'bi bi-chevron-right text-sm transition-transform duration-200';
                    }
                    if (collapseBtn) {
                        collapseBtn.title = 'Expand navigation menu [Ctrl+B]';
                    }
                    if (quickAccessToggleText) {
                        quickAccessToggleText.textContent = 'Expand Sidebar';
                    }
                    if (persist) {
                        localStorage.setItem('admin_sidebar_collapsed', 'true');
                    }
                } else {
                    sidebar.classList.remove('is-collapsed');
                    document.documentElement.classList.remove('sidebar-collapsed-mode');
                    if (collapseIcon) {
                        collapseIcon.className = 'bi bi-chevron-left text-sm transition-transform duration-200';
                    }
                    if (collapseBtn) {
                        collapseBtn.title = 'Collapse navigation menu (get more screen space) [Ctrl+B]';
                    }
                    if (quickAccessToggleText) {
                        quickAccessToggleText.textContent = 'Collapse Sidebar';
                    }
                    if (persist) {
                        localStorage.setItem('admin_sidebar_collapsed', 'false');
                    }
                }
            }

            // Sync initial state from localStorage or pre-hydration class
            const isPersistedCollapsed = localStorage.getItem('admin_sidebar_collapsed') === 'true' && window.innerWidth >= 768;
            setSidebarCollapsedState(isPersistedCollapsed, false);

            if (collapseBtn) {
                collapseBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const currentlyCollapsed = sidebar.classList.contains('is-collapsed') || document.documentElement.classList.contains('sidebar-collapsed-mode');
                    setSidebarCollapsedState(!currentlyCollapsed, true);
                });
            }

            if (quickAccessToggleBtn) {
                quickAccessToggleBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const currentlyCollapsed = sidebar.classList.contains('is-collapsed') || document.documentElement.classList.contains('sidebar-collapsed-mode');
                    setSidebarCollapsedState(!currentlyCollapsed, true);
                });
            }

            // ========================================================
            // QUICK-ACCESS POP-UP MENU INTERACTION
            // ========================================================
            const quickModal = document.getElementById('quickAccessModal');
            const quickDialog = document.getElementById('quickAccessDialog');
            const quickHeaderBtn = document.getElementById('quickAccessHeaderBtn');
            const quickMobileHeaderBtn = document.getElementById('quickAccessMobileHeaderBtn');
            const quickCloseBtn = document.getElementById('quickAccessCloseBtn');
            const quickBackdrop = document.getElementById('quickAccessBackdrop');

            function openQuickAccessModal() {
                if (!quickModal || !quickDialog) return;
                quickModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                requestAnimationFrame(() => {
                    quickDialog.classList.remove('scale-95', 'opacity-0');
                    quickDialog.classList.add('scale-100', 'opacity-100');
                });
            }

            function closeQuickAccessModal() {
                if (!quickModal || !quickDialog) return;
                quickDialog.classList.remove('scale-100', 'opacity-100');
                quickDialog.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    quickModal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }, 180);
            }

            window.closeQuickAccessModal = closeQuickAccessModal;

            if (quickHeaderBtn) {
                quickHeaderBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openQuickAccessModal();
                });
            }

            if (quickMobileHeaderBtn) {
                quickMobileHeaderBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openQuickAccessModal();
                });
            }

            if (quickCloseBtn) quickCloseBtn.addEventListener('click', closeQuickAccessModal);
            if (quickBackdrop) quickBackdrop.addEventListener('click', closeQuickAccessModal);

            // Global Keyboard Shortcuts (Esc, Ctrl+K, Ctrl+B)
            document.addEventListener('keydown', (e) => {
                // Esc key closes Quick Access modal or mobile sidebar
                if (e.key === 'Escape') {
                    if (quickModal && !quickModal.classList.contains('hidden')) {
                        closeQuickAccessModal();
                    }
                    closeSidebar();
                }

                // Ctrl+K or Cmd+K toggles Quick Access modal
                if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    if (quickModal && !quickModal.classList.contains('hidden')) {
                        closeQuickAccessModal();
                    } else {
                        openQuickAccessModal();
                    }
                }

                // Ctrl+B or Cmd+B toggles Sidebar collapse/expand
                if ((e.ctrlKey || e.metaKey) && (e.key === 'b' || e.key === 'B')) {
                    e.preventDefault();
                    if (sidebar && window.innerWidth >= 768) {
                        const currentlyCollapsed = sidebar.classList.contains('is-collapsed') || document.documentElement.classList.contains('sidebar-collapsed-mode');
                        setSidebarCollapsedState(!currentlyCollapsed, true);
                    }
                }
            });

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
    @auth
        <x-transaction-chat-drawer />
    @endauth
    @yield('scripts')
</body>
</html>
