<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            color: #10b981 !important; /* emerald-500 */
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
        }
        /* Active page state */
        nav[role="navigation"] span[aria-current="page"] > span {
            background-color: #10b981 !important; /* emerald-500 */
            border-color: #10b981 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 12px -2px rgba(16, 185, 129, 0.3) !important;
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
<body class="h-full flex flex-col items-center justify-center p-0 md:p-6 text-slate-800 antialiased overflow-hidden">

    <!-- Smartphone Device Container Mockup (Fits beautifully centered on desktop, natively full-screen on mobile) -->
    <div class="w-full h-full md:h-[820px] md:w-[380px] md:rounded-[48px] md:border-[12px] md:border-slate-800 md:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.15)] md:ring-1 md:ring-slate-300/40 md:relative md:flex md:flex-col overflow-hidden bg-white">
        
        <!-- Simulated Hardware notch/Status bar (Desktop only) -->
        <div class="hidden md:flex bg-slate-50 h-7 px-6 items-center justify-between text-[10px] text-slate-500 select-none z-50 sticky top-0 border-b border-slate-100">
            <span class="font-semibold">09:41</span>
            <div class="w-24 h-4 bg-slate-50 rounded-b-2xl absolute left-1/2 -translate-x-1/2 top-0 border-x border-b border-slate-100"></div>
            <div class="flex items-center space-x-1.5">
                <i class="bi bi-reception-4 text-[9px]"></i>
                <i class="bi bi-wifi text-[10px]"></i>
                <i class="bi bi-battery-full text-emerald-600 text-sm"></i>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-100 px-4 py-3 flex items-center justify-between sticky top-0 z-40 shadow-sm">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-slate-950 shadow-md shadow-emerald-500/10">
                    <i class="bi bi-bicycle text-sm font-bold text-white"></i>
                </div>
                <div>
                    <span class="text-sm font-black tracking-tight text-slate-900">Dairy<span class="text-emerald-500">Box</span></span>
                    <span class="text-[9px] bg-emerald-50 text-emerald-600 border border-emerald-200 px-1.5 py-0.5 rounded ml-1 font-bold uppercase tracking-wider">Rider</span>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-900 leading-none">{{ auth()->user()->name }}</p>
                    <p class="text-[8px] text-emerald-500 mt-0.5 flex items-center justify-end font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span> Active
                    </p>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-500 rounded-xl hover:bg-rose-50 transition-all cursor-pointer" title="Logout">
                        <i class="bi bi-power text-sm"></i>
                    </button>
                </form>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-grow p-4 overflow-y-auto no-scrollbar pb-24 bg-slate-50/60 relative">
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
        <nav class="absolute bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-100 px-4 py-3 flex items-center justify-around z-40 shadow-[0_-8px_24px_rgba(0,0,0,0.03)] md:rounded-b-[36px]">
            <a href="{{ route('rider.dashboard') }}" class="flex flex-col items-center space-y-1 text-[10px] font-bold transition-all {{ request()->routeIs('rider.dashboard') ? 'text-emerald-600 scale-105' : 'text-slate-400 hover:text-slate-600' }}">
                <i class="bi bi-journal-text text-lg"></i>
                <span>Active Queue</span>
            </a>
            <a href="{{ route('rider.history') }}" class="flex flex-col items-center space-y-1 text-[10px] font-bold transition-all {{ request()->routeIs('rider.history') ? 'text-emerald-600 scale-105' : 'text-slate-400 hover:text-slate-600' }}">
                <i class="bi bi-clock-history text-lg"></i>
                <span>History Log</span>
            </a>
        </nav>
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
                    confirmButtonColor: '#10b981',
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
