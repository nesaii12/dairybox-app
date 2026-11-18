<!DOCTYPE html>
<html lang="en" class="min-h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Forgot Password | DairyBox</title>
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
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Forgot Password?</h2>
            <p class="mt-2 text-sm text-slate-600 max-w-sm mx-auto">
                No worries! Enter your registered email address and we'll send you a link to reset your password (valid for 60 seconds).
            </p>
        </div>

        <div class="bg-white pt-12 pb-8 px-6 shadow-xl shadow-slate-100 rounded-2xl border border-slate-100 relative">
            <!-- Back to Login Arrow -->
            <a href="{{ route('login') }}" class="absolute top-4 left-4 inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 border border-slate-200 text-slate-400 hover:text-slate-800 transition-all hover:bg-slate-100 shadow-2xs hover:scale-105" title="Back to Login">
                <i class="bi bi-arrow-left text-sm font-bold"></i>
            </a>

            <!-- Status Alerts -->
            @if(session('status'))
            <div class="mb-5 bg-emerald-50 border border-emerald-100 text-emerald-800 px-4 py-3 rounded-xl flex items-start space-x-2.5 text-sm" role="alert">
                <i class="bi bi-check-circle-fill text-emerald-500 mt-0.5 flex-shrink-0"></i>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
            @endif

            @if($errors->has('email'))
            <div class="mb-5 bg-rose-50 border border-rose-100 text-rose-800 px-4 py-3 rounded-xl flex items-start space-x-2.5 text-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0"></i>
                <span class="font-medium">{{ $errors->first('email') }}</span>
            </div>
            @endif

            @if(session('dev_reset_url'))
            <div class="mb-5 p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900">
                <div class="flex items-center gap-1.5 font-bold mb-1">
                    <i class="bi bi-info-circle-fill text-amber-600"></i>
                    <span>Local Test Shortcut:</span>
                </div>
                <p class="text-[11px] text-amber-800 mb-2.5">Since email sending is set to local log, you can test directly by clicking below:</p>
                <a href="{{ session('dev_reset_url') }}" class="inline-flex items-center gap-1.5 font-bold text-sky-700 bg-white px-3 py-1.5 rounded-lg border border-amber-200 shadow-2xs hover:bg-amber-50 hover:text-sky-800 transition-colors">
                    <span>Open Reset Password Form</span>
                    <i class="bi bi-arrow-right text-xs"></i>
                </a>
            </div>
            @endif

            <form class="space-y-5" action="{{ route('password.email') }}" method="POST">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700">Email Address</label>
                    <div class="mt-1.5 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-envelope text-slate-400 text-sm"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required autofocus
                            value="{{ old('email') }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="you@example.com">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all hover:-translate-y-0.5 cursor-pointer">
                        Send Reset Link
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-xs text-slate-500">
                    Remember your password?
                    <a href="{{ route('login') }}" class="font-bold text-sky-600 hover:text-sky-500 ml-1">Log in here</a>
                </p>
            </div>

        </div>
    </div>

</body>
</html>
