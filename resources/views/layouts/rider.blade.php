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
                <i class="bi bi-battery-full text-slate-700 text-sm"></i>
            </div>
        </div>

        <!-- Header -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-100 px-4 py-3 flex items-center justify-between sticky top-0 z-40 shadow-xs">
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
                <!-- Rider Notifications -->
                <div class="relative">
                    <button id="riderNotifBellBtn" class="relative p-1.5 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-xl transition-all cursor-pointer focus:outline-none" title="Notifications">
                        <i class="bi bi-bell text-lg"></i>
                        @if($riderNotifCount > 0)
                            <span class="absolute top-0.5 right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white animate-pulse">
                                {{ $riderNotifCount }}
                            </span>
                        @endif
                    </button>

                    <!-- Rider Notifications Modal -->
                    <div id="riderNotifModal" class="absolute right-0 mt-2 w-72 bg-white/95 backdrop-blur-md rounded-2xl border border-slate-100 shadow-xl overflow-hidden hidden z-50 transform origin-top-right transition-all flex flex-col">
                        <div class="px-3.5 py-2.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                            <h4 class="text-xs font-bold text-slate-900">Notifications</h4>
                            @if($riderNotifCount > 0)
                                <span class="text-[9px] font-bold bg-sky-50 text-sky-700 px-2 py-0.5 rounded-full border border-sky-100">
                                    {{ $riderNotifCount }} new
                                </span>
                            @endif
                        </div>

                        <div class="overflow-y-auto divide-y divide-slate-100 max-h-[300px]">
                            @forelse($riderNotifications as $notif)
                                <div class="group relative p-3 flex items-start justify-between gap-2.5 transition-all hover:bg-sky-50/40 {{ !$notif->is_read ? 'bg-sky-50/15' : '' }}">
                                    <a href="{{ route('notifications.readAndRedirect', $notif->id) }}" class="flex space-x-2 flex-1 min-w-0 block">
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
                                            <div class="flex items-center space-x-2 mt-1">
                                                <span class="block text-[8px] text-slate-400 font-medium">{{ $notif->created_at->diffForHumans() }}</span>
                                                <span class="text-[8px] font-semibold text-sky-600 opacity-0 group-hover:opacity-100 transition-opacity">View record &rarr;</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @empty
                                <div class="py-8 text-center text-slate-400 text-xs">
                                    <i class="bi bi-bell-slash text-2xl text-slate-200 mb-1 block"></i>
                                    <span>No new notifications.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-900 leading-none">{{ auth()->user()->name }}</p>
                    <p class="text-[8px] text-emerald-600 mt-0.5 flex items-center justify-end font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span> Active
                    </p>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-500 rounded-xl hover:bg-rose-50 transition-all cursor-pointer" title="Logout">
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
        <nav class="absolute bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-100 px-4 py-2.5 flex items-center justify-around z-40 shadow-[0_-8px_24px_rgba(0,0,0,0.03)] md:rounded-b-[36px]">
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
    </script>
    @yield('scripts')
</body>
</html>
