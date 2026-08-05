<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<body class="h-full flex flex-col md:flex-row text-slate-800 antialiased">

    <!-- Mobile Header -->
    <header class="md:hidden bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between sticky top-0 z-40 shadow-sm">
        <div class="flex items-center space-x-2">
            <div class="w-8 h-8 rounded-lg bg-sky-600 flex items-center justify-center text-white shadow-md shadow-sky-200">
                <i class="bi bi-box-seam-fill text-sm"></i>
            </div>
            <span class="text-lg font-bold tracking-tight text-slate-900">Dairy<span class="text-sky-600">Box</span></span>
        </div>
        <button id="mobile-menu-toggle" class="p-2 text-slate-500 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none">
            <i class="bi bi-list text-2xl"></i>
        </button>
    </header>

    <!-- Sidebar Wrapper for Mobile Overlay / Desktop Fixed -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex flex-col justify-between transform -translate-x-full md:translate-x-0 md:static transition-transform duration-300 ease-in-out">
        <div class="flex flex-col h-full">
            <!-- Sidebar Header -->
            <div class="px-6 py-5 border-b border-slate-100 hidden md:flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-600 flex items-center justify-center text-white shadow-lg shadow-sky-200">
                        <i class="bi bi-box-seam-fill text-lg"></i>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-slate-950">Dairy<span class="text-sky-600">Box</span></span>
                </div>
                <span class="bg-sky-50 text-sky-700 text-xs px-2 py-0.5 rounded-full font-medium border border-sky-100">Admin</span>
            </div>

            <!-- Mobile Sidebar close button -->
            <div class="px-6 py-4 border-b border-slate-100 flex md:hidden items-center justify-between bg-slate-50">
                <span class="font-bold text-slate-950">Navigation Menu</span>
                <button id="mobile-menu-close" class="p-1.5 text-slate-500 hover:bg-slate-200 rounded-lg">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bi bi-grid-fill text-lg {{ request()->routeIs('admin.dashboard') ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    <span>Dashboard</span>
                </a>

                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Catalog</div>
                
                <a href="{{ route('admin.products.index') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.products.*') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bi bi-egg-fried text-lg {{ request()->routeIs('admin.products.*') ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    <span>Products</span>
                </a>
                
                <a href="{{ route('admin.categories.index') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.categories.index') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bi bi-tags-fill text-lg {{ request()->routeIs('admin.categories.index') ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    <span>Categories</span>
                </a>

                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Operations</div>

                <a href="{{ route('admin.inventory.index') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.inventory.index') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bi bi-archive-fill text-lg {{ request()->routeIs('admin.inventory.index') ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    <span>Inventory</span>
                </a>

                <a href="{{ route('admin.orders.index') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.orders.*') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bi bi-cart-check-fill text-lg {{ request()->routeIs('admin.orders.*') ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    <span>Orders</span>
                </a>

                <div class="pt-4 pb-2 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Analytics & Users</div>

                <a href="{{ route('admin.customers.index') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.customers.*') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bi bi-people-fill text-lg {{ request()->routeIs('admin.customers.*') ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    <span>Customers</span>
                </a>

                <a href="{{ route('admin.riders.index') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.riders.*') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bi bi-bicycle text-lg {{ request()->routeIs('admin.riders.*') ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    <span>Riders</span>
                </a>

                <a href="{{ route('admin.forecast.index') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.forecast.index') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bi bi-graph-up-arrow text-lg {{ request()->routeIs('admin.forecast.index') ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    <span>Forecasting</span>
                </a>

                <a href="{{ route('admin.reports.index') }}" class="flex items-center space-x-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.reports.index') ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill text-lg {{ request()->routeIs('admin.reports.index') ? 'text-sky-600' : 'text-slate-400' }}"></i>
                    <span>Reports System</span>
                </a>
            </nav>

            <!-- User profile footer -->
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-full bg-sky-100 flex items-center justify-center text-sky-700 font-bold border border-sky-200">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-semibold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Logout">
                        <i class="bi bi-box-arrow-right text-lg"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Mobile Sidebar Backdrop overlay -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/40 z-40 hidden md:hidden"></div>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col overflow-y-auto">
        <!-- Top bar (Desktop only) -->
        <header class="hidden md:flex bg-white border-b border-slate-200 px-8 py-4 items-center justify-between shadow-sm sticky top-0 z-30">
            <div>
                <h1 class="text-xl font-bold text-slate-950">@yield('page_title', 'Dashboard')</h1>
                <p class="text-xs text-slate-500">DairyBox Admin Portal — {{ now()->format('l, M d, Y') }}</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- System Status / Date -->
                <div class="text-right">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                        System Online
                    </span>
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
