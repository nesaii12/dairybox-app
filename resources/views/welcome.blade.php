<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>DairyBox — Fresh dairy, delivered with care.</title>
        <!-- Favicon Links -->
        <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
        <link rel="manifest" href="{{ asset('site.webmanifest') }}" />

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <!-- Tailwind CSS Assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --font-serif: 'Playfair Display', Georgia, serif;
                --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            }
            body {
                font-family: var(--font-sans);
                background-color: #0f172a;
            }
            .font-serif {
                font-family: var(--font-serif);
            }
        </style>
    </head>
    <body class="min-h-full antialiased text-slate-800 flex flex-col justify-between overflow-x-hidden relative">

        <!-- Full-Bleed Background Image Slideshow (z-0) -->
        <div id="heroSlideshowBackdrop" class="fixed inset-0 z-0 overflow-hidden pointer-events-none select-none bg-slate-950" aria-hidden="true">
            <div id="heroSlidesContainer" class="absolute inset-0 w-full h-full">
                <!-- Slides injected & shuffled via JS; fallback first image for instant paint -->
                <img src="{{ asset('images/hero/slide-03.jpg') }}" alt="DairyBox Farm & Community" class="absolute inset-0 w-full h-full object-cover object-center opacity-100 transition-opacity duration-700 ease-in-out" data-fallback-slide="true">
            </div>
            <!-- Dark Contrast Gradient Overlays for Hero Legibility -->
            <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/38 to-black/15"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-black/25 via-transparent to-black/45"></div>
        </div>

        <!-- Translucent Frosted Header Navigation -->
        <nav class="bg-white/50 backdrop-blur-md sticky top-0 z-40 border-b border-white/35 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center space-x-8">
                        <a href="{{ url('/') }}" class="flex items-center space-x-2.5 hover:opacity-90 transition-opacity">
                            <div class="w-9 h-9 rounded-xl bg-amber-50/90 flex items-center justify-center p-1 border border-amber-200/70 shadow-xs">
                                <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                            </div>
                            <span class="text-xl font-bold tracking-tight text-slate-900">Dairy<span class="text-[#0ea5e9]">Box</span></span>
                        </a>

                        <!-- Desktop Navigation Links -->
                        <div class="hidden md:flex space-x-1.5">
                            <a href="{{ url('/') }}" class="px-4 py-2 rounded-full text-xs transition-all {{ request()->is('/') ? 'font-bold bg-amber-100/80 text-amber-950 border border-amber-200/70 shadow-2xs' : 'font-semibold text-slate-700 hover:text-slate-950 hover:bg-white/60' }}">
                                Home
                            </a>
                            <a href="{{ route('customer.catalog') }}" class="px-4 py-2 rounded-full text-xs transition-all {{ request()->routeIs('customer.catalog') || request()->routeIs('customer.product') ? 'font-bold bg-amber-100/80 text-amber-950 border border-amber-200/70 shadow-2xs' : 'font-semibold text-slate-700 hover:text-slate-950 hover:bg-white/60' }}">
                                Shop
                            </a>
                            @auth
                                @if(auth()->user()->role === 'customer')
                                    <a href="{{ route('customer.orders.index') }}" class="px-4 py-2 rounded-full text-xs transition-all {{ request()->routeIs('customer.orders.*') ? 'font-bold bg-amber-100/80 text-amber-950 border border-amber-200/70 shadow-2xs' : 'font-semibold text-slate-700 hover:text-slate-950 hover:bg-white/60' }}">
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
                            $cartIds = \App\Models\Cart::where('customer_id', auth()->id())->pluck('id');
                            $cartItemCount = $cartIds->isNotEmpty() ? (int) \App\Models\CartItem::whereIn('cart_id', $cartIds)->sum('quantity') : 0;
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
                                    <button id="notificationBellBtn" class="relative p-2 text-slate-700 hover:text-sky-600 hover:bg-white/60 rounded-xl transition-all cursor-pointer focus:outline-none" title="Notifications">
                                        <i class="bi bi-bell text-xl"></i>
                                        @if($notifCount > 0)
                                            <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white animate-pulse">
                                                {{ $notifCount }}
                                            </span>
                                        @endif
                                    </button>

                                    <!-- Notifications Dropdown -->
                                    <div id="notificationsModal" class="fixed inset-x-3 top-16 mx-auto sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 w-auto sm:w-96 max-w-sm bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden hidden z-50 transform origin-top-right transition-all flex flex-col">
                                        <!-- Header -->
                                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/90">
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
                                        <div class="overflow-y-auto divide-y divide-slate-100 max-h-[350px] bg-white">
                                            @forelse($allNotifications as $notif)
                                            <div class="group relative p-3.5 flex items-start justify-between gap-3 transition-all hover:bg-sky-50/50 {{ !$notif->is_read ? 'bg-sky-50/30' : 'bg-white' }}">
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
                                                            <i class="bi bi-arrow-right text-[10px] text-slate-400 group-hover:text-sky-600 group-hover:translate-x-0.5 transition-all ml-1 flex-shrink-0"></i>
                                                        </div>
                                                        <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed break-words">{{ $notif->message }}</p>
                                                        <div class="flex items-center space-x-2 mt-1">
                                                            <span class="block text-[9px] text-slate-400 font-medium">{{ $notif->created_at->diffForHumans() }}</span>
                                                            <span class="text-[9px] font-semibold text-sky-600 opacity-0 group-hover:opacity-100 transition-opacity">View details &rarr;</span>
                                                        </div>
                                                    </div>
                                                </a>

                                                @if(!$notif->is_read)
                                                <form action="{{ route('customer.notifications.read', $notif->id) }}" method="POST" class="flex-shrink-0 z-10" onclick="event.stopPropagation();">
                                                    @csrf
                                                    <button type="submit" class="p-1 px-1.5 border border-slate-200 rounded-lg text-[9px] font-bold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-50 transition-all shadow-2xs cursor-pointer" title="Mark as read without redirecting">
                                                        Mark read
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                            @empty
                                            <div class="py-10 text-center">
                                                <i class="bi bi-bell-slash text-3xl text-slate-300 mb-2 block"></i>
                                                <p class="text-xs font-semibold text-slate-800">All caught up!</p>
                                                <p class="text-[11px] text-slate-500">You have no new notifications.</p>
                                            </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                <!-- Shopping Cart -->
                                <a href="{{ route('customer.cart.index') }}" id="navCartButton" class="nav-cart-btn relative p-2 text-slate-700 hover:text-[#0ea5e9] hover:bg-white/60 rounded-xl transition-all" title="Shopping Cart">
                                    <i class="bi bi-cart3 text-xl"></i>
                                    <span class="cart-nav-badge absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-[#0ea5e9] text-[9px] font-bold text-white ring-2 ring-white {{ $cartItemCount > 0 ? '' : 'hidden' }}">
                                        {{ $cartItemCount }}
                                    </span>
                                </a>

                                <!-- Divider -->
                                <span class="h-6 w-px bg-slate-300/70 hidden sm:block"></span>
                            @else
                                <!-- Dashboard Redirect Button for Admin / Rider -->
                                @if(auth()->user()->role === 'admin')
                                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center space-x-1 py-1.5 px-4 rounded-xl text-xs font-bold text-sky-700 bg-sky-50/90 hover:bg-sky-100 transition-all border border-sky-200/70 mr-2 shadow-2xs">
                                        <span>Dashboard</span>
                                    </a>
                                @elseif(auth()->user()->role === 'rider')
                                    <a href="{{ route('rider.dashboard') }}" class="inline-flex items-center space-x-1 py-1.5 px-4 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50/90 hover:bg-emerald-100 transition-all border border-emerald-200/70 mr-2 shadow-2xs">
                                        <span>Rider Dashboard</span>
                                    </a>
                                @endif
                            @endif

                            <!-- Profile Dropdown (Shared for all roles when logged in) -->
                            <div class="relative flex items-center space-x-3 pl-2">
                                <div class="hidden sm:block text-right">
                                    <p class="text-xs font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                    <p class="text-[10px] text-slate-600">{{ ucfirst(auth()->user()->role) }}</p>
                                </div>
                                <div class="w-9 h-9 rounded-full bg-white/80 flex items-center justify-center text-slate-800 font-bold border border-white/70 shadow-2xs">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <form action="{{ route('logout') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-slate-600 hover:text-red-600 rounded-xl hover:bg-red-50/80 transition-all cursor-pointer" title="Logout">
                                        <i class="bi bi-box-arrow-right text-lg"></i>
                                    </button>
                                </form>
                            </div>
                        @else
                            <!-- Shopping Cart Icon for Guest (Redirects to Login via Auth Middleware) -->
                            <a href="{{ route('customer.cart.index') }}" class="relative p-2 text-slate-700 hover:text-[#0ea5e9] hover:bg-white/60 rounded-xl transition-all mr-1" title="Shopping Cart">
                                <i class="bi bi-cart3 text-xl"></i>
                            </a>

                            <!-- Sign In -->
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center py-2 px-5 rounded-xl text-xs font-bold text-white bg-[#0ea5e9] hover:bg-sky-600 transition-all shadow-sm active:scale-95">
                                Sign in
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Workspace Content (z-10 over full-bleed slideshow) -->
        <main class="relative z-10 flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 pb-24 md:pb-12 space-y-10 sm:space-y-14">

            <!-- Hero Section -->
            <section id="heroInteractiveSection" class="space-y-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                    <!-- Hero Left Column -->
                    <div class="lg:col-span-6 space-y-6">
                        <!-- Tagline badge -->
                        <div class="inline-flex items-center space-x-1.5 py-1 px-3.5 rounded-full bg-amber-400/25 backdrop-blur-md border border-amber-200/40 text-[11px] font-bold text-amber-100 uppercase tracking-wide shadow-sm">
                            <i class="bi bi-sprout-fill text-amber-300"></i>
                            <span>Farm to doorstep in hours</span>
                        </div>

                        <!-- Headline -->
                        <h1 class="text-4xl sm:text-6xl font-bold text-white tracking-tight leading-[1.1] font-serif drop-shadow-md">
                            Fresh dairy, <br><span class="italic text-sky-300 font-normal">delivered</span> with care.
                        </h1>

                        <!-- Paragraph -->
                        <p class="text-amber-50/95 text-sm sm:text-base leading-relaxed max-w-lg drop-shadow-sm">
                            Carabao and cow milk, hand-churned ice cream, and creamy yogurt — ordered online, kept cold, and at your door the same day.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-wrap items-center gap-4 pt-1">
                            <a href="{{ route('customer.catalog') }}" class="inline-flex items-center space-x-2 py-3 px-6 rounded-2xl text-sm font-bold text-white bg-sky-600/90 hover:bg-sky-500 backdrop-blur-sm transition-all shadow-lg shadow-sky-950/30 hover:shadow-xl active:scale-95">
                                <span>Shop the catalog</span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                            @guest
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center py-3 px-6 rounded-2xl text-sm font-bold text-white bg-white/20 hover:bg-white/30 backdrop-blur-md transition-all border border-white/35 shadow-lg active:scale-95">
                                    Create an account
                                </a>
                            @endguest
                        </div>

                        <!-- Reservation Contact Notice (See-Through Glass Card) -->
                        <div class="bg-black/25 backdrop-blur-md border border-white/30 rounded-2xl p-4 shadow-lg max-w-lg space-y-2">
                            <div class="flex items-center gap-2 text-xs font-bold text-white">
                                <span class="w-6 h-6 rounded-lg bg-sky-400/20 text-sky-300 border border-sky-300/30 flex items-center justify-center shrink-0">
                                    <i class="bi bi-calendar2-check-fill text-xs"></i>
                                </span>
                                <span>For reservations, please contact us:</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 pl-8 text-xs text-white/90">
                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                    <i class="bi bi-facebook text-sky-300"></i>
                                    <span>FB Page: <strong class="text-white">Dairybox Trinidad</strong></span>
                                </span>
                                <a href="tel:09503845678" class="inline-flex items-center gap-1.5 font-semibold hover:text-sky-200 transition-colors">
                                    <i class="bi bi-telephone-fill text-emerald-300"></i>
                                    <span>Contact No.: <strong class="text-white font-mono">09503845678</strong></span>
                                </a>
                            </div>
                        </div>

                        <!-- Stars Review Badge -->
                        <div class="flex items-center space-x-3 pt-4 border-t border-white/25 w-fit">
                            <div class="flex text-amber-300 space-x-0.5 text-xs drop-shadow-xs">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                            </div>
                            <span class="text-xs font-semibold text-white/95 drop-shadow-xs">Loved by 2,400+ families across the region.</span>
                        </div>
                    </div>

                    <!-- Hero Right Column (4 See-Through Glass Feature Cards Stacked Vertically on Right Edge) -->
                    <div class="lg:col-span-6 flex justify-center lg:justify-end">
                        <div class="w-full sm:max-w-xs lg:w-60 flex flex-col gap-3">
                            <!-- Feature 1 -->
                            <div class="bg-black/25 backdrop-blur-md border border-white/30 rounded-2xl px-4 py-3.5 flex items-center gap-3.5 shadow-lg hover:bg-black/35 transition-all duration-300">
                                <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-300/30 text-amber-300 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 2h6M9 6h6m-7 4v10a2 2 0 002 2h6a2 2 0 002-2V10M8 10c0-2 2-3 4-3s4 1 4 3" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-white leading-tight drop-shadow-xs">Carabao Milk</h3>
                                    <p class="text-[11px] text-white/85 mt-0.5">Rich & creamy</p>
                                </div>
                            </div>

                            <!-- Feature 2 -->
                            <div class="bg-black/25 backdrop-blur-md border border-white/30 rounded-2xl px-4 py-3.5 flex items-center gap-3.5 shadow-lg hover:bg-black/35 transition-all duration-300">
                                <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-300/30 text-amber-300 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M3 12h18m-3-6L6 18M6 6l12 12" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-white leading-tight drop-shadow-xs">Cold-chain</h3>
                                    <p class="text-[11px] text-white/85 mt-0.5">0-4 °C all the way</p>
                                </div>
                            </div>

                            <!-- Feature 3 -->
                            <div class="bg-black/25 backdrop-blur-md border border-white/30 rounded-2xl px-4 py-3.5 flex items-center gap-3.5 shadow-lg hover:bg-black/35 transition-all duration-300">
                                <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-300/30 text-amber-300 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 8h7a1 1 0 011 1v7H13M13 12h8" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-white leading-tight drop-shadow-xs">Same-day</h3>
                                    <p class="text-[11px] text-white/85 mt-0.5">Delivery or pickup</p>
                                </div>
                            </div>

                            <!-- Feature 4 -->
                            <div class="bg-black/25 backdrop-blur-md border border-white/30 rounded-2xl px-4 py-3.5 flex items-center gap-3.5 shadow-lg hover:bg-black/35 transition-all duration-300">
                                <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-300/30 text-amber-300 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-white leading-tight drop-shadow-xs">Trusted</h3>
                                    <p class="text-[11px] text-white/85 mt-0.5">Pasteurized & tested</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Subscribe Call to Action Banner (See-Through Frosted Glass) -->
            <section>
                <div class="bg-black/25 backdrop-blur-md border border-white/30 rounded-3xl sm:rounded-[32px] p-6 sm:p-12 text-center space-y-6 relative overflow-hidden shadow-xl">
                    <div class="max-w-xl mx-auto space-y-3">
                        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight font-serif drop-shadow-xs">Subscribe to a weekly DairyBox</h2>
                        <p class="text-amber-50/90 text-xs sm:text-sm leading-relaxed">Skip, swap, or cancel anytime. Fresh batches every Friday.</p>
                    </div>

                    <div class="pt-2">
                        @auth
                            @if(auth()->user()->role === 'customer')
                                <a href="{{ route('customer.catalog') }}" class="inline-flex items-center justify-center py-2.5 px-6 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-950/30 active:scale-95">
                                    Start shopping
                                </a>
                            @else
                                <a href="{{ url('/') }}" class="inline-flex items-center justify-center py-2.5 px-6 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-950/30 active:scale-95 cursor-not-allowed opacity-80">
                                    Start shopping
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center py-2.5 px-6 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-950/30 active:scale-95">
                                Start shopping
                            </a>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        <!-- Translucent Frosted Footer -->
        <footer class="relative z-10 w-full bg-white/50 backdrop-blur-md border-t border-white/35 mt-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24 md:pb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <!-- Footer Logo -->
                <div class="flex items-center space-x-2.5">
                    <div class="w-7 h-7 rounded-lg bg-sky-50/90 flex items-center justify-center p-0.5 border border-sky-200/70">
                        <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                    </div>
                    <span class="text-base font-bold tracking-tight text-slate-900">Dairy<span class="text-[#0ea5e9]">Box</span></span>
                </div>
                <!-- Footer Text -->
                <p class="text-xs text-slate-700 font-medium">&copy; {{ date('Y') }} DairyBox. Farm-fresh dairy, delivered.</p>
            </div>
        </footer>

        <!-- Customer Mobile Bottom Navigation Bar -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/85 backdrop-blur-md border-t border-white/50 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] pb-safe select-none">
            <div class="flex items-center justify-around px-2 py-1.5">
                <a href="{{ url('/') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->is('/') ? 'text-[#0ea5e9] font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                    <i class="bi {{ request()->is('/') ? 'bi-house-fill' : 'bi-house' }} text-lg leading-none mb-1"></i>
                    <span class="text-[10px] tracking-tight">Home</span>
                </a>

                <a href="{{ route('customer.catalog') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.catalog') || request()->routeIs('customer.product') ? 'text-[#0ea5e9] font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                    <i class="bi {{ request()->routeIs('customer.catalog') || request()->routeIs('customer.product') ? 'bi-shop-window' : 'bi-shop' }} text-lg leading-none mb-1"></i>
                    <span class="text-[10px] tracking-tight">Shop</span>
                </a>

                @auth
                    @if(auth()->user()->role === 'customer')
                        <a href="{{ route('customer.orders.index') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.orders.*') ? 'text-[#0ea5e9] font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                            <i class="bi {{ request()->routeIs('customer.orders.*') ? 'bi-clock-history' : 'bi-clock' }} text-lg leading-none mb-1"></i>
                            <span class="text-[10px] tracking-tight">Orders</span>
                        </a>
                    @endif
                @endauth

                <!-- Shopping Cart with Live Badge Counter -->
                <a href="{{ route('customer.cart.index') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all relative {{ request()->routeIs('customer.cart.*') ? 'text-[#0ea5e9] font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                    <i class="bi {{ request()->routeIs('customer.cart.*') ? 'bi-cart-fill' : 'bi-cart3' }} text-lg leading-none mb-1"></i>
                    <span class="text-[10px] tracking-tight">Cart</span>
                    <span class="cart-nav-badge absolute top-0.5 right-2 flex h-4 w-4 items-center justify-center rounded-full bg-[#0ea5e9] text-[9px] font-bold text-white ring-2 ring-white {{ $cartItemCount > 0 ? '' : 'hidden' }}">
                        {{ $cartItemCount }}
                    </span>
                </a>

                @auth
                    @if(auth()->user()->role === 'customer')
                        <a href="{{ route('customer.settings.index') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.settings.*') ? 'text-[#0ea5e9] font-bold' : 'text-slate-600 hover:text-slate-900 font-medium' }}">
                            <i class="bi {{ request()->routeIs('customer.settings.*') ? 'bi-person-fill-gear' : 'bi-person-gear' }} text-lg leading-none mb-1"></i>
                            <span class="text-[10px] tracking-tight">Account</span>
                        </a>
                    @elseif(auth()->user()->role === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center py-1 px-3 rounded-xl text-sky-600 font-medium">
                            <i class="bi bi-grid-fill text-lg leading-none mb-1"></i>
                            <span class="text-[10px] tracking-tight">Admin</span>
                        </a>
                    @elseif(auth()->user()->role === 'rider')
                        <a href="{{ route('rider.dashboard') }}" class="flex flex-col items-center py-1 px-3 rounded-xl text-emerald-600 font-medium">
                            <i class="bi bi-bicycle text-lg leading-none mb-1"></i>
                            <span class="text-[10px] tracking-tight">Rider</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="flex flex-col items-center py-1 px-3 rounded-xl text-slate-600 hover:text-sky-600 font-medium">
                        <i class="bi bi-person text-lg leading-none mb-1"></i>
                        <span class="text-[10px] tracking-tight">Sign In</span>
                    </a>
                @endauth
            </div>
        </nav>

        <!-- Full-Bleed Shuffled Background Slideshow Script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const heroSlides = [
                    { src: @json(asset('images/hero/slide-01.jpg')), alt: 'DairyBox and Philippine Carabao Center team at launch' },
                    { src: @json(asset('images/hero/slide-02.jpg')), alt: 'DairyBox Trinidad grand opening community celebration' },
                    { src: @json(asset('images/hero/slide-03.jpg')), alt: 'DairyBox display chiller stocked with fresh carabao and flavored milk' },
                    { src: @json(asset('images/hero/slide-04.jpg')), alt: 'DairyBox 150L pasteurizer machine and holding tank' },
                    { src: @json(asset('images/hero/slide-05.jpg')), alt: 'DairyBox official ribbon-cutting ceremony' },
                    { src: @json(asset('images/hero/slide-06.jpg')), alt: 'DairyBox storefront entrance opening with partners' },
                    { src: @json(asset('images/hero/slide-07.jpg')), alt: 'DairyBox storefront blessing ceremony' },
                    { src: @json(asset('images/hero/slide-08.jpg')), alt: 'DairyBox launch program remarks' },
                    { src: @json(asset('images/hero/slide-09.jpg')), alt: 'DairyBox opening address in front of storefront sign' },
                    { src: @json(asset('images/hero/slide-10.jpg')), alt: 'DairyBox Trinidad storefront team photo' },
                ];

                // Fisher-Yates shuffle so every visit plays slides in a fresh random order
                function shuffleSlides(list) {
                    const shuffled = list.slice();
                    for (let i = shuffled.length - 1; i > 0; i--) {
                        const j = Math.floor(Math.random() * (i + 1));
                        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
                    }
                    return shuffled;
                }

                const slidesContainer = document.getElementById('heroSlidesContainer');

                if (!slidesContainer || heroSlides.length === 0) {
                    return;
                }

                const orderedSlides = shuffleSlides(heroSlides);
                let currentIndex = 0;
                const INTERVAL_MS = 10000;

                // Mount shuffled slide images
                slidesContainer.innerHTML = '';
                const slideElements = orderedSlides.map((slide, idx) => {
                    const img = document.createElement('img');
                    img.src = slide.src;
                    img.alt = slide.alt;
                    img.decoding = 'async';
                    img.loading = idx === 0 ? 'eager' : 'lazy';
                    img.className = 'absolute inset-0 w-full h-full object-cover object-center transition-opacity duration-700 ease-in-out ' + (idx === 0 ? 'opacity-100' : 'opacity-0');
                    slidesContainer.appendChild(img);
                    return img;
                });

                function goToSlide(targetIdx) {
                    const nextIdx = (targetIdx + orderedSlides.length) % orderedSlides.length;
                    if (nextIdx === currentIndex) {
                        return;
                    }

                    slideElements[currentIndex].classList.remove('opacity-100');
                    slideElements[currentIndex].classList.add('opacity-0');

                    currentIndex = nextIdx;

                    slideElements[currentIndex].classList.remove('opacity-0');
                    slideElements[currentIndex].classList.add('opacity-100');
                }

                setInterval(function() {
                    goToSlide(currentIndex + 1);
                }, INTERVAL_MS);
            });
        </script>

        @if(auth()->check())
        <!-- Notifications Toggle Script -->
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

                @if(session('success') === 'Notification marked as read.' || session('success') === 'All notifications marked as read.')
                window.toggleNotificationsModal();
                @endif
            });
        </script>
        @endif

    </body>
</html>
