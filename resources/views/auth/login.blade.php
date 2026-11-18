<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
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
            background-color: #FAF8F2;
        }

        .auth-input {
            border: none;
            border-bottom: 1.5px solid #E2E8F0;
            background-color: transparent;
            border-radius: 0;
            padding: 0.5rem 0;
            font-size: 0.95rem;
            color: #1E293B;
            width: 100%;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-input:focus {
            border-bottom-color: #01565B;
            box-shadow: 0 1px 0 0 #01565B;
        }

        .auth-input::placeholder {
            color: #94A3B8;
            font-size: 0.9rem;
        }

        .btn-teal {
            background-color: #01565B;
            color: #FFFFFF;
            transition: all 0.2s ease;
        }

        .btn-teal:hover {
            background-color: #004347;
            box-shadow: 0 4px 12px rgba(1, 86, 91, 0.25);
        }

        .btn-teal:active {
            background-color: #003336;
            transform: scale(0.995);
        }

        .btn-social {
            background-color: #FFFFFF;
            border: 1px solid #E2E8F0;
            color: #475569;
            transition: all 0.2s ease;
        }

        .btn-social:hover {
            background-color: #F8FAFC;
            border-color: #CBD5E1;
            color: #1E293B;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full min-h-screen text-slate-800 antialiased overflow-x-hidden">

    <div class="min-h-screen lg:h-screen flex flex-col lg:flex-row">
        
        <!-- Left Column: Authentication Form -->
        <div class="w-full lg:w-[460px] xl:w-[500px] 2xl:w-[540px] lg:min-w-[420px] bg-[#FAF8F2] flex flex-col justify-between p-6 sm:p-10 lg:p-12 xl:p-14 overflow-y-auto">
            
            <!-- Top Area: Logo & Back Link -->
            <div class="flex items-center justify-between mb-8 lg:mb-12">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 group transition-opacity hover:opacity-85" title="DairyBox Home">
                    <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox Logo" class="h-10 w-10 object-contain drop-shadow-2xs">
                </a>

                <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 hover:text-slate-700 transition-colors py-1.5 px-2.5 rounded-lg hover:bg-slate-200/50">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Store</span>
                </a>
            </div>

            <!-- Middle Area: Welcome & Form -->
            <div class="my-auto py-2">
                
                <!-- Heading -->
                <div class="mb-8">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                        Welcome to DairyBox
                    </h1>
                    <p class="mt-2 text-sm text-slate-500 font-normal">
                        Enter your credentials to access your account
                    </p>
                </div>

                <!-- Flash Notifications -->
                @if(session('success'))
                <div class="mb-6 bg-emerald-50 border border-emerald-200/80 text-emerald-800 px-4 py-3 rounded-xl flex items-center space-x-2 text-sm" role="alert">
                    <i class="bi bi-check-circle-fill text-emerald-600 shrink-0"></i>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 bg-rose-50 border border-rose-200/80 text-rose-800 px-4 py-3 rounded-xl flex items-center space-x-2 text-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-rose-600 shrink-0"></i>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                @endif

                <!-- Login Form -->
                <form class="space-y-6" action="{{ route('login') }}" method="POST">
                    @csrf

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-500 mb-1">
                            Email
                        </label>
                        <input id="email" 
                               name="email" 
                               type="email" 
                               autocomplete="email" 
                               required 
                               value="{{ old('email') }}"
                               class="auth-input"
                               placeholder="you@example.com">
                        @error('email')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-500 mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <input id="password" 
                                   name="password" 
                                   type="password" 
                                   autocomplete="current-password" 
                                   required
                                   class="auth-input pr-9"
                                   placeholder="••••••••">
                            <button type="button" 
                                    id="togglePassword" 
                                    class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1.5 focus:outline-none transition-colors"
                                    title="Show/Hide Password">
                                <i class="bi bi-eye text-sm" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password Row -->
                    <div class="flex items-center justify-between pt-1">
                        <label for="remember" class="flex items-center gap-2 cursor-pointer select-none group">
                            <input id="remember" 
                                   name="remember" 
                                   type="checkbox"
                                   class="w-4 h-4 rounded border-slate-300 text-[#01565B] accent-[#01565B] focus:ring-[#01565B]/30 cursor-pointer">
                            <span class="text-xs text-slate-500 group-hover:text-slate-700 transition-colors">
                                Remember me
                            </span>
                        </label>

                        <a href="{{ route('password.request') }}" class="text-xs text-slate-500 hover:text-[#01565B] transition-colors font-medium">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Primary Action Button -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="btn-teal w-full py-3 px-4 rounded-lg font-medium text-sm tracking-wide shadow-sm cursor-pointer flex items-center justify-center gap-2">
                            <span>Log In</span>
                        </button>
                    </div>

                    <!-- Social / Alternative Actions -->
                    <div class="space-y-3 pt-2">
                        <!-- Google OAuth -->
                        <a href="{{ route('auth.google') }}"
                           class="btn-social w-full flex items-center justify-center py-2.5 px-4 rounded-lg text-sm font-medium shadow-2xs group cursor-pointer">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                            </svg>
                            <span>Sign in with Google</span>
                        </a>

                        <!-- Register Customer Account -->
                        <a href="{{ route('register') }}"
                           class="btn-social w-full flex items-center justify-center py-2.5 px-4 rounded-lg text-sm font-medium shadow-2xs group cursor-pointer">
                            <i class="bi bi-person-plus text-slate-400 group-hover:text-slate-600 mr-2.5 text-base transition-colors"></i>
                            <span>Create account</span>
                        </a>
                    </div>
                </form>

            </div>

            <!-- Bottom Area: Footer & Copyright -->
            <div class="pt-8 text-center sm:text-left">
                <p class="text-xs text-slate-400">
                    &copy; {{ date('Y') }} DairyBox Management System. All rights reserved.
                </p>
            </div>

        </div>

        <!-- Right Column: DairyBox Mascot Visual (dairy.png) -->
        <div class="hidden lg:flex lg:flex-1 relative h-full min-h-screen bg-[#F7AC07] items-center justify-center overflow-hidden select-none">
            <img src="{{ asset('images/dairy.png') }}" 
                 alt="DairyBox Holiday Mascot" 
                 class="absolute inset-0 w-full h-full object-cover object-center transition-transform duration-700 hover:scale-[1.01]">
        </div>

    </div>

    <!-- Password visibility toggle script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (toggleBtn && passwordInput && toggleIcon) {
                toggleBtn.addEventListener('click', function () {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    toggleIcon.className = isPassword ? 'bi bi-eye-slash text-sm' : 'bi bi-eye text-sm';
                });
            }
        });
    </script>
</body>
</html>
