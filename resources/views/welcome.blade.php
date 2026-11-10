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
                background: radial-gradient(at 10% 10%, rgba(254, 240, 138, 0.22) 0px, transparent 45%), 
                            radial-gradient(at 90% 25%, rgba(253, 230, 138, 0.18) 0px, transparent 50%), 
                            radial-gradient(at 50% 90%, rgba(254, 243, 199, 0.25) 0px, transparent 60%), 
                            linear-gradient(135deg, #fffdf5 0%, #fefcf3 50%, #faf6ea 100%);
            }
            .font-serif {
                font-family: var(--font-serif);
            }
        </style>
    </head>
    <body class="h-full antialiased text-slate-800 flex flex-col justify-between overflow-x-hidden">

        <!-- Header Navigation -->
        <nav class="bg-[#fffdf8]/90 backdrop-blur-md sticky top-0 z-40 border-b border-amber-200/50 shadow-2xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center space-x-8">
                        <a href="{{ url('/') }}" class="flex items-center space-x-2.5 hover:opacity-90 transition-opacity">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center p-1 border border-amber-100 shadow-xs">
                                <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                            </div>
                            <span class="text-xl font-bold tracking-tight text-slate-900">Dairy<span class="text-[#0ea5e9]">Box</span></span>
                        </a>

                        <!-- Desktop Navigation Links -->
                        <div class="hidden md:flex space-x-1.5">
                            <a href="{{ url('/') }}" class="px-4 py-2 rounded-full text-xs transition-all {{ request()->is('/') ? 'font-bold bg-amber-100/70 text-amber-950 border border-amber-200/60 shadow-2xs' : 'font-semibold text-slate-600 hover:text-slate-900 hover:bg-amber-50/50' }}">
                                Home
                            </a>
                            <a href="{{ route('customer.catalog') }}" class="px-4 py-2 rounded-full text-xs transition-all {{ request()->routeIs('customer.catalog') || request()->routeIs('customer.product') ? 'font-bold bg-amber-100/70 text-amber-950 border border-amber-200/60 shadow-2xs' : 'font-semibold text-slate-600 hover:text-slate-900 hover:bg-amber-50/50' }}">
                                Shop
                            </a>
                            @auth
                                @if(auth()->user()->role === 'customer')
                                    <a href="{{ route('customer.orders.index') }}" class="px-4 py-2 rounded-full text-xs transition-all {{ request()->routeIs('customer.orders.*') ? 'font-bold bg-amber-100/70 text-amber-950 border border-amber-200/60 shadow-2xs' : 'font-semibold text-slate-600 hover:text-slate-900 hover:bg-amber-50/50' }}">
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
                                    <button id="notificationBellBtn" class="relative p-2 text-slate-500 hover:text-sky-600 hover:bg-sky-50 rounded-xl transition-all cursor-pointer focus:outline-none" title="Notifications">
                                        <i class="bi bi-bell text-xl"></i>
                                        @if($notifCount > 0)
                                            <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white ring-2 ring-white animate-pulse">
                                                {{ $notifCount }}
                                            </span>
                                        @endif
                                    </button>

                                    <!-- Notifications Dropdown -->
                                    <div id="notificationsModal" class="absolute right-0 mt-2 w-[calc(100vw-1.5rem)] sm:w-96 max-w-sm bg-white/95 backdrop-blur-md rounded-2xl border border-slate-100 shadow-xl overflow-hidden hidden z-50 transform origin-top-right transition-all flex flex-col">
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

                            <!-- Profile Dropdown (Shared for all roles when logged in) -->
                            <div class="relative flex items-center space-x-3 pl-2">
                                <div class="hidden sm:block text-right">
                                    <p class="text-xs font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ ucfirst(auth()->user()->role) }}</p>
                                </div>
                                <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 font-bold border border-slate-200">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <form action="{{ route('logout') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-red-500 rounded-xl hover:bg-red-50 transition-all" title="Logout">
                                        <i class="bi bi-box-arrow-right text-lg"></i>
                                    </button>
                                </form>
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
            </div>
        </nav>

        <!-- Main Workspace content -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-24 md:pb-12 space-y-14 sm:space-y-20">

            <!-- Hero Section -->
            <section class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Hero Left Column -->
                <div class="lg:col-span-6 space-y-6">
                    <!-- Tagline badge -->
                    <div class="inline-flex items-center space-x-1.5 py-1 px-3.5 rounded-full bg-amber-100/80 border border-amber-200/90 text-[11px] font-bold text-amber-900 uppercase tracking-wide shadow-2xs">
                        <i class="bi bi-sprout-fill text-amber-600"></i>
                        <span>Farm to doorstep in hours</span>
                    </div>

                    <!-- Headline -->
                    <h1 class="text-4xl sm:text-6xl font-bold text-slate-900 tracking-tight leading-[1.1] font-serif">
                        Fresh dairy, <br><span class="italic text-sky-600 font-normal">delivered</span> with care.
                    </h1>

                    <!-- Paragraph -->
                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-lg">
                        Carabao and cow milk, hand-churned ice cream, and creamy yogurt — ordered online, kept cold, and at your door the same day.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <a href="{{ route('customer.catalog') }}" class="inline-flex items-center space-x-2 py-3 px-6 rounded-2xl text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-lg shadow-sky-200 hover:shadow-xl hover:shadow-sky-200 active:scale-95">
                            <span>Shop the catalog</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                        @guest
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center py-3 px-6 rounded-2xl text-sm font-bold text-slate-800 bg-white hover:bg-slate-50 transition-all border border-slate-200 shadow-sm active:scale-95">
                                Create an account
                            </a>
                        @endguest
                    </div>

                    <!-- Stars Review Badge -->
                    <div class="flex items-center space-x-3 pt-4 border-t border-slate-100 w-fit">
                        <div class="flex text-amber-400 space-x-0.5 text-xs">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <span class="text-xs font-semibold text-slate-500">Loved by 2,400+ families across the region.</span>
                    </div>
                </div>

                <!-- Hero Right Column (Features card block) -->
                <div class="lg:col-span-6 flex justify-center lg:justify-end">
                    <div class="w-full max-w-lg bg-white/95 rounded-3xl sm:rounded-[32px] border border-amber-100/90 shadow-xl shadow-amber-950/5 p-4 sm:p-8 grid grid-cols-2 gap-3 sm:gap-6 relative">
                        <!-- Background glow element with warm light yellow ambiance -->
                        <div class="absolute -inset-1 bg-gradient-to-r from-amber-300/20 via-yellow-200/20 to-sky-300/10 rounded-[34px] -z-10 blur-xl"></div>
                        
                        <!-- Feature 1 -->
                        <div class="bg-[#fefce8] border border-amber-100 rounded-2xl p-5 space-y-4 hover:shadow-sm hover:border-amber-200 transition-all duration-300">
                            <div class="w-10 h-10 rounded-xl bg-amber-100/90 text-amber-700 flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 2h6M9 6h6m-7 4v10a2 2 0 002 2h6a2 2 0 002-2V10M8 10c0-2 2-3 4-3s4 1 4 3" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Carabao Milk</h3>
                                <p class="text-[11px] text-slate-500 mt-1">Rich & creamy</p>
                            </div>
                        </div>

                        <!-- Feature 2 -->
                        <div class="bg-[#fefce8] border border-amber-100 rounded-2xl p-5 space-y-4 hover:shadow-sm hover:border-amber-200 transition-all duration-300">
                            <div class="w-10 h-10 rounded-xl bg-amber-100/90 text-amber-700 flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M3 12h18m-3-6L6 18M6 6l12 12" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Cold-chain</h3>
                                <p class="text-[11px] text-slate-500 mt-1">0-4 °C all the way</p>
                            </div>
                        </div>

                        <!-- Feature 3 -->
                        <div class="bg-[#fefce8] border border-amber-100 rounded-2xl p-5 space-y-4 hover:shadow-sm hover:border-amber-200 transition-all duration-300">
                            <div class="w-10 h-10 rounded-xl bg-amber-100/90 text-amber-700 flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 8h7a1 1 0 011 1v7H13M13 12h8" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Same-day</h3>
                                <p class="text-[11px] text-slate-500 mt-1">Delivery or pickup</p>
                            </div>
                        </div>

                        <!-- Feature 4 -->
                        <div class="bg-[#fefce8] border border-amber-100 rounded-2xl p-5 space-y-4 hover:shadow-sm hover:border-amber-200 transition-all duration-300">
                            <div class="w-10 h-10 rounded-xl bg-amber-100/90 text-amber-700 flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Trusted</h3>
                                <p class="text-[11px] text-slate-500 mt-1">Pasteurized & tested</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Product Showcase / "This week's picks" -->
            <section class="space-y-8">
                <div class="flex items-end justify-between border-b border-slate-100 pb-4">
                    <div class="space-y-1">
                        <h2 class="text-3xl font-bold text-slate-900 tracking-tight font-serif">This week's picks</h2>
                        <p class="text-slate-500 text-xs sm:text-sm">Hand-selected from our small herd.</p>
                    </div>
                    <a href="{{ route('customer.catalog') }}" class="text-xs sm:text-sm font-bold text-sky-600 hover:text-sky-500 transition-colors flex items-center space-x-1">
                        <span>Browse all</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <!-- Product Catalog Grid -->
                <div class="grid grid-cols-1 min-[420px]:grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    @forelse($products as $product)
                        @php
                            $outOfStock = $product->stock <= 0;
                        @endphp
                        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                            <!-- Image / Illustration Container -->
                            <div class="aspect-square bg-slate-50 relative flex items-center justify-center overflow-hidden border-b border-slate-50">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->product_name }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <!-- Custom illustration box for dairy catalog matching design specifications -->
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-14 h-14 rounded-full bg-white shadow-sm flex items-center justify-center text-sky-500">
                                            @if(str_contains(strtolower($product->product_name), 'ice cream'))
                                                <i class="bi bi-egg-fried text-3xl"></i>
                                            @else
                                                <i class="bi bi-droplet-fill text-2xl"></i>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Stock status label overlay -->
                                @if($outOfStock)
                                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center">
                                        <span class="bg-rose-600 text-white text-[10px] px-3 py-1.5 rounded-full font-bold shadow-md uppercase tracking-wider">Sold Out</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Details -->
                            @php
                                $allowedCardMilks = !$outOfStock ? $product->available_milk_types : [];
                                $allowedCardSizes = !$outOfStock ? $product->available_sizes : [];
                                $defaultCardMilk = in_array($product->milk_type, $allowedCardMilks) ? $product->milk_type : ($allowedCardMilks[0] ?? $product->milk_type);
                                $defaultCardSize = in_array($product->size, $allowedCardSizes) ? $product->size : ($allowedCardSizes[0] ?? $product->size);
                                $cardMilkOptions = ['Carabao' => 'Carabao', 'Cow' => "Cow's Milk"];
                                $cardSizeOptions = ['Small', 'Medium', 'Large'];
                            @endphp
                            <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                                @php
                                    $ratingCount = (int) ($product->ratings_count ?? $product->ratings()->count());
                                    $ratingAvg = (float) ($product->ratings_avg_rating ?? ($ratingCount > 0 ? $product->ratings()->avg('rating') : 0));
                                @endphp
                                <div class="space-y-2">
                                    <div>
                                        <!-- Product Rating Reference -->
                                        <a href="{{ route('customer.product', $product->id) }}#reviews-section" class="inline-flex items-center gap-1.5 text-[11px] mb-1 w-max group/rating" title="{{ $ratingCount > 0 ? number_format($ratingAvg, 1) . ' out of 5 (' . $ratingCount . ' ' . Str::plural('review', $ratingCount) . ')' : 'No reviews yet' }}">
                                            <span class="flex items-center text-amber-400 space-x-0.5 text-[10px]">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= round($ratingAvg))
                                                        <i class="bi bi-star-fill"></i>
                                                    @else
                                                        <i class="bi bi-star text-slate-300"></i>
                                                    @endif
                                                @endfor
                                            </span>
                                            <span class="font-bold {{ $ratingCount > 0 ? 'text-slate-700' : 'text-slate-400' }}">{{ number_format($ratingAvg, 1) }}</span>
                                            <span class="text-[10px] text-slate-400 font-medium group-hover/rating:text-sky-600 transition-colors">
                                                ({{ $ratingCount > 0 ? $ratingCount . ' ' . Str::plural('review', $ratingCount) : 'No reviews yet' }})
                                            </span>
                                        </a>

                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm sm:text-base font-bold text-slate-800 tracking-tight group-hover:text-sky-600 transition-colors truncate">
                                                <a href="{{ route('customer.product', $product->id) }}">{{ $product->product_name }}</a>
                                            </h3>
                                        </div>
                                    </div>
                                    <!-- Milk Type & Size Customization Options -->
                                    <div class="space-y-1.5 pt-0.5">
                                        <div class="flex items-center gap-1 flex-wrap" data-card-milk-group="{{ $product->id }}">
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 w-9 shrink-0">Milk:</span>
                                            @foreach($cardMilkOptions as $mKey => $mLabel)
                                                @php
                                                    $milkAvail = in_array($mKey, $allowedCardMilks);
                                                    $isCurrMilk = ($defaultCardMilk === $mKey) && $milkAvail;
                                                @endphp
                                                @if($milkAvail)
                                                    <button type="button"
                                                        data-card-option="milk"
                                                        data-product-id="{{ $product->id }}"
                                                        data-value="{{ $mKey }}"
                                                        class="card-milk-pill px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer {{ $isCurrMilk ? 'font-bold bg-sky-600 text-white border border-sky-600 shadow-2xs' : 'font-semibold bg-white text-slate-700 border border-slate-200 hover:border-sky-400 hover:text-sky-600' }}">{{ $mLabel }}</button>
                                                @else
                                                    <button type="button" disabled aria-disabled="true" title="{{ $mLabel }} unavailable" class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-400 border border-slate-200/70 line-through opacity-55 cursor-not-allowed select-none">{{ $mLabel }}</button>
                                                @endif
                                            @endforeach
                                        </div>
                                        <div class="flex items-center gap-1 flex-wrap" data-card-size-group="{{ $product->id }}">
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 w-9 shrink-0">Size:</span>
                                            @foreach($cardSizeOptions as $sOpt)
                                                @php
                                                    $sizeAvail = in_array($sOpt, $allowedCardSizes);
                                                    $isCurrSize = ($defaultCardSize === $sOpt) && $sizeAvail;
                                                @endphp
                                                @if($sizeAvail)
                                                    <button type="button"
                                                        data-card-option="size"
                                                        data-product-id="{{ $product->id }}"
                                                        data-value="{{ $sOpt }}"
                                                        class="card-size-pill px-2 py-0.5 rounded-md text-[10px] transition-all cursor-pointer {{ $isCurrSize ? 'font-bold bg-sky-600 text-white border border-sky-600 shadow-2xs' : 'font-semibold bg-white text-slate-700 border border-slate-200 hover:border-sky-400 hover:text-sky-600' }}">{{ $sOpt }}</button>
                                                @else
                                                    <button type="button" disabled aria-disabled="true" title="{{ $sOpt }} unavailable" class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-400 border border-slate-200/70 line-through opacity-55 cursor-not-allowed select-none">{{ $sOpt }}</button>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-slate-50">
                                    <span class="text-sm font-bold text-sky-600">₱{{ number_format($product->price, 2) }}</span>
                                    
                                    @auth
                                        @if(auth()->user()->role === 'customer' && !$outOfStock)
                                            <form action="{{ route('customer.cart.add', $product->id) }}" method="POST" class="inline-flex items-center gap-1.5 add-to-cart-form" data-product-id="{{ $product->id }}" data-product-name="{{ $product->product_name }}" data-stock="{{ $product->stock }}">
                                                @csrf
                                                <input type="hidden" name="milk_type" class="card-selected-milk" value="{{ $defaultCardMilk }}">
                                                <input type="hidden" name="size" class="card-selected-size" value="{{ $defaultCardSize }}">
                                                <div class="catalog-qty-stepper flex items-center border border-slate-200 rounded-xl bg-slate-50/90 overflow-hidden h-8">
                                                    <button type="button" class="catalog-qty-minus px-2 h-full text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer text-xs" title="Decrease quantity">
                                                        <i class="bi bi-dash pointer-events-none"></i>
                                                    </button>
                                                    <input type="number" name="quantity" value="1" min="1" max="{{ max(1, $product->stock) }}"
                                                        class="catalog-qty-input w-7 text-center text-xs font-bold text-slate-800 bg-transparent border-0 p-0 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                        aria-label="Quantity">
                                                    <button type="button" class="catalog-qty-plus px-2 h-full text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer text-xs" title="Increase quantity">
                                                        <i class="bi bi-plus pointer-events-none"></i>
                                                    </button>
                                                </div>
                                                <button type="submit" class="add-to-cart-btn w-8 h-8 rounded-xl bg-[#0ea5e9] hover:bg-sky-600 text-white flex items-center justify-center transition-colors shadow-md shadow-sky-100 cursor-pointer animate-none hover:scale-105 active:scale-95 flex-shrink-0" title="Add to selection batch">
                                                    <i class="bi bi-cart-plus-fill text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        @if(!$outOfStock)
                                            <a href="{{ route('login') }}" class="w-8 h-8 rounded-full bg-[#0ea5e9] hover:bg-sky-600 text-white flex items-center justify-center transition-colors shadow-md shadow-sky-100" title="Add to cart">
                                                <i class="bi bi-plus text-lg font-bold"></i>
                                            </a>
                                        @endif
                                    @endauth
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center bg-white rounded-3xl border border-slate-100 p-8 shadow-sm">
                            <i class="bi bi-basket text-3xl text-slate-300 mb-2 block"></i>
                            <p class="text-xs font-semibold text-slate-700">Catalog is empty</p>
                            <p class="text-[10px] text-slate-400">Products will appear here once registered.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <!-- Subscribe Call to Action Banner -->
            <section>
                <div class="bg-gradient-to-br from-amber-500/[0.08] via-yellow-400/[0.05] to-amber-200/[0.04] border border-amber-300/35 rounded-3xl sm:rounded-[32px] p-6 sm:p-12 text-center space-y-6 relative overflow-hidden shadow-xs">
                    <div class="absolute -right-16 -top-16 w-44 h-44 rounded-full bg-yellow-300/20 blur-3xl"></div>
                    <div class="absolute -left-16 -bottom-16 w-44 h-44 rounded-full bg-amber-400/20 blur-3xl"></div>
                    
                    <div class="max-w-xl mx-auto space-y-3">
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight font-serif">Subscribe to a weekly DairyBox</h2>
                        <p class="text-slate-600 text-xs sm:text-sm leading-relaxed">Skip, swap, or cancel anytime. Fresh batches every Friday.</p>
                    </div>

                    <div class="pt-2">
                        @auth
                            @if(auth()->user()->role === 'customer')
                                <a href="{{ route('customer.catalog') }}" class="inline-flex items-center justify-center py-2.5 px-6 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100 active:scale-95">
                                    Start shopping
                                </a>
                            @else
                                <a href="{{ url('/') }}" class="inline-flex items-center justify-center py-2.5 px-6 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100 active:scale-95 cursor-not-allowed opacity-80">
                                    Start shopping
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center py-2.5 px-6 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100 active:scale-95">
                                Start shopping
                            </a>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-28 md:pb-8 border-t border-slate-100/60 mt-12 flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Footer Logo -->
            <div class="flex items-center space-x-2.5">
                <div class="w-7 h-7 rounded-lg bg-sky-50 flex items-center justify-center p-0.5 border border-sky-100">
                    <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                </div>
                <span class="text-base font-bold tracking-tight text-slate-800">Dairy<span class="text-[#0ea5e9]">Box</span></span>
            </div>
            <!-- Footer Text -->
            <p class="text-xs text-slate-500 font-medium">&copy; {{ date('Y') }} DairyBox. Farm-fresh dairy, delivered.</p>
        </footer>

        <!-- Customer Mobile Bottom Navigation Bar -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] pb-safe select-none">
            <div class="flex items-center justify-around px-2 py-1.5">
                <a href="{{ url('/') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->is('/') ? 'text-[#0ea5e9] font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                    <i class="bi {{ request()->is('/') ? 'bi-house-fill' : 'bi-house' }} text-lg leading-none mb-1"></i>
                    <span class="text-[10px] tracking-tight">Home</span>
                </a>

                <a href="{{ route('customer.catalog') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.catalog') || request()->routeIs('customer.product') ? 'text-[#0ea5e9] font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                    <i class="bi {{ request()->routeIs('customer.catalog') || request()->routeIs('customer.product') ? 'bi-shop-window' : 'bi-shop' }} text-lg leading-none mb-1"></i>
                    <span class="text-[10px] tracking-tight">Shop</span>
                </a>

                @auth
                    @if(auth()->user()->role === 'customer')
                        <a href="{{ route('customer.orders.index') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.orders.*') ? 'text-[#0ea5e9] font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                            <i class="bi {{ request()->routeIs('customer.orders.*') ? 'bi-clock-history' : 'bi-clock' }} text-lg leading-none mb-1"></i>
                            <span class="text-[10px] tracking-tight">Orders</span>
                        </a>
                    @endif
                @endauth

                <!-- Shopping Cart with Live Badge Counter -->
                <a href="{{ route('customer.cart.index') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all relative {{ request()->routeIs('customer.cart.*') ? 'text-[#0ea5e9] font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                    <i class="bi {{ request()->routeIs('customer.cart.*') ? 'bi-cart-fill' : 'bi-cart3' }} text-lg leading-none mb-1"></i>
                    <span class="text-[10px] tracking-tight">Cart</span>
                    <span class="cart-nav-badge absolute top-0.5 right-2 flex h-4 w-4 items-center justify-center rounded-full bg-[#0ea5e9] text-[9px] font-bold text-white ring-2 ring-white {{ $cartItemCount > 0 ? '' : 'hidden' }}">
                        {{ $cartItemCount }}
                    </span>
                </a>

                @auth
                    @if(auth()->user()->role === 'customer')
                        <a href="{{ route('customer.settings.index') }}" class="flex flex-col items-center py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.settings.*') ? 'text-[#0ea5e9] font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
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
                    <a href="{{ route('login') }}" class="flex flex-col items-center py-1 px-3 rounded-xl text-slate-500 hover:text-sky-600 font-medium">
                        <i class="bi bi-person text-lg leading-none mb-1"></i>
                        <span class="text-[10px] tracking-tight">Sign In</span>
                    </a>
                @endauth
            </div>
        </nav>

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
