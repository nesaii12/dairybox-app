<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Register | DairyBox</title>
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
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">

        <!-- Logo -->
        <div class="inline-flex w-14 h-14 rounded-2xl bg-sky-600 items-center justify-center text-white shadow-xl shadow-sky-200 mb-4">
            <i class="bi bi-box-seam-fill text-2xl"></i>
        </div>
        <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Create Account</h2>
        <p class="mt-2 text-sm text-slate-600">
            Join DairyBox to order fresh milk & ice creams!
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white pt-14 pb-8 px-6 shadow-xl shadow-slate-100 sm:rounded-2xl border border-slate-100 relative">
            <!-- Back to Home Arrow -->
            <a href="{{ url('/') }}" class="absolute top-4 left-4 inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 border border-slate-200 text-slate-400 hover:text-slate-800 transition-all hover:bg-slate-100 shadow-2xs hover:scale-105" title="Back to Home">
                <i class="bi bi-arrow-left text-sm font-bold"></i>
            </a>
            
            <form class="space-y-4" action="{{ route('register') }}" method="POST">
                @csrf
                
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-700">Full Name</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-person text-slate-400 text-sm"></i>
                        </div>
                        <input id="name" name="name" type="text" required value="{{ old('name') }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="John Doe">
                    </div>
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700">Email Address</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-envelope text-slate-400 text-sm"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="john@example.com">
                    </div>
                    @error('email')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold text-slate-700">Phone Number</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-telephone text-slate-400 text-sm"></i>
                        </div>
                        <input id="phone" name="phone" type="text" required value="{{ old('phone') }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="09171234567">
                    </div>
                    @error('phone')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-lock text-slate-400 text-sm"></i>
                        </div>
                        <input id="password" name="password" type="password" required
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="Min. 8 characters">
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-shield-check text-slate-400 text-sm"></i>
                        </div>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="Re-type password">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all hover:-translate-y-0.5">
                        Register Account
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-xs text-slate-500">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="font-bold text-sky-600 hover:text-sky-500 ml-1">Log in here</a>
                </p>
            </div>

        </div>
    </div>

</body>
</html>
