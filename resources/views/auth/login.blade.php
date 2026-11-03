<!DOCTYPE html>
<html lang="en" class="min-h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | DairyBox</title>
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
            background: linear-gradient(135deg, #FAF6F0 0%, #E6F0FA 100%);
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
    
    <div class="sm:mx-auto sm:w-full sm:max-w-md my-auto">
        <!-- Logo & Header -->
        <div class="text-center mb-6">
            <a href="{{ url('/') }}" class="inline-flex flex-col items-center group">
                <div class="w-16 h-16 rounded-2xl bg-white p-2.5 flex items-center justify-center shadow-lg shadow-sky-100 border border-slate-100 mb-3 group-hover:scale-105 transition-transform">
                    <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox Logo" class="w-full h-full object-contain">
                </div>
            </a>
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Welcome to DairyBox</h2>
            <p class="mt-2 text-sm text-slate-600">
                Fresh Milk, Ice Cream, & Sales Inventory System
            </p>
        </div>

        <div class="bg-white pt-12 pb-8 px-6 shadow-xl shadow-slate-100 rounded-2xl border border-slate-100 relative">
            <!-- Back to Home Arrow -->
            <a href="{{ url('/') }}" class="absolute top-4 left-4 inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 border border-slate-200 text-slate-400 hover:text-slate-800 transition-all hover:bg-slate-100 shadow-2xs hover:scale-105" title="Back to Home">
                <i class="bi bi-arrow-left text-sm font-bold"></i>
            </a>
            
            <!-- Alert message flashes -->
            @if(session('success'))
            <div class="mb-4 bg-emerald-50 border border-emerald-100 text-emerald-800 px-4 py-2.5 rounded-xl flex items-center space-x-2 text-sm" role="alert">
                <i class="bi bi-check-circle-fill text-emerald-500"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4 bg-rose-50 border border-rose-100 text-rose-800 px-4 py-2.5 rounded-xl flex items-center space-x-2 text-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-rose-500"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            @endif

            <form class="space-y-6" action="{{ route('login') }}" method="POST">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700">Email Address</label>
                    <div class="mt-1.5 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-envelope text-slate-400 text-sm"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="you@example.com">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                        <div class="text-xs">
                            <a href="{{ route('password.request') }}" class="font-semibold text-sky-600 hover:text-sky-500">Forgot password?</a>
                        </div>
                    </div>
                    <div class="mt-1.5 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-lock text-slate-400 text-sm"></i>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                            class="h-4.5 w-4.5 text-sky-600 focus:ring-sky-500 border-slate-300 rounded-lg cursor-pointer">
                        <label for="remember" class="ml-2 block text-xs text-slate-600 cursor-pointer font-medium select-none">Remember my device</label>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all hover:-translate-y-0.5 cursor-pointer">
                        Log In
                    </button>
                </div>

                <div class="relative flex items-center justify-center my-4">
                    <div class="border-t border-slate-200/80 w-full"></div>
                    <span class="absolute px-3 bg-white text-[11px] font-semibold text-slate-400 uppercase tracking-wider">or</span>
                </div>

                <div>
                    <a href="{{ route('auth.google') }}"
                        class="w-full flex items-center justify-center py-2.5 px-4 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300 transition-all shadow-xs hover:shadow-sm">
                        <svg class="w-4 h-4 mr-2.5 flex-shrink-0" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </a>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative flex items-center justify-center my-4">
                    <div class="border-t border-slate-100 w-full"></div>
                    <span class="absolute px-3 bg-white text-xs text-slate-400 font-medium">New to DairyBox?</span>
                </div>

                <a href="{{ route('register') }}"
                    class="w-full flex justify-center py-2.5 px-4 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-all text-center">
                    Create a Customer Account
                </a>
            </div>



        </div>
    </div>

</body>
</html>
