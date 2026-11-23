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
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400..700;1,400..700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0f172a;
        }

        .auth-glass-input {
            background-color: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.30);
            border-radius: 0.75rem;
            padding: 0.65rem 0.9rem;
            font-size: 0.92rem;
            color: #ffffff;
            width: 100%;
            outline: none;
            transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .auth-glass-input:focus {
            border-color: rgba(125, 211, 252, 0.85);
            background-color: rgba(255, 255, 255, 0.22);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .auth-glass-input::placeholder {
            color: rgba(255, 255, 255, 0.58);
            font-size: 0.88rem;
        }

        /* Keep browser autofill transparent and readable over glass */
        .auth-glass-input:-webkit-autofill,
        .auth-glass-input:-webkit-autofill:hover,
        .auth-glass-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #ffffff;
            -webkit-box-shadow: 0 0 0 1000px rgba(15, 23, 42, 0.45) inset;
            transition: background-color 9999s ease-in-out 0s;
            caret-color: #ffffff;
        }
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full min-h-screen text-white antialiased overflow-x-hidden relative">

    <!-- Full-Bleed Shuffled Background Image Slideshow (z-0, 10s interval, no arrows) -->
    <div id="loginSlideshowBackdrop" class="fixed inset-0 z-0 overflow-hidden pointer-events-none select-none bg-slate-950" aria-hidden="true">
        <div id="loginSlidesContainer" class="absolute inset-0 w-full h-full">
            <img src="{{ asset('images/hero/slide-03.jpg') }}" alt="DairyBox Farm & Community" class="absolute inset-0 w-full h-full object-cover object-center opacity-100 transition-opacity duration-700 ease-in-out">
        </div>
        <!-- Balanced Dark Contrast Overlay -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/30 to-black/50"></div>
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center p-4 sm:p-6">
        
        <!-- Centered See-Through Frosted Glass Authentication Card -->
        <div class="w-full max-w-md bg-black/30 backdrop-blur-md border border-white/25 rounded-3xl shadow-2xl flex flex-col justify-between p-6 sm:p-8">
            
            <!-- Top Area: Logo & Back Link -->
            <div class="flex items-center justify-between mb-6">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5 group transition-opacity hover:opacity-90" title="DairyBox Home">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/35 flex items-center justify-center p-1.5 shadow-sm">
                        <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox Logo" class="w-full h-full object-contain">
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white drop-shadow-xs">Dairy<span class="text-sky-300">Box</span></span>
                </a>

                <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-white/80 hover:text-white transition-colors py-1.5 px-3 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Store</span>
                </a>
            </div>

            <!-- Middle Area: Welcome & Form -->
            <div class="my-auto py-2">
                
                <!-- Heading -->
                <div class="mb-7">
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white drop-shadow-sm">
                        Welcome to DairyBox
                    </h1>
                    <p class="mt-2 text-sm text-white/80 font-normal">
                        Enter your credentials to access your account
                    </p>
                </div>

                <!-- Flash Notifications -->
                @if(session('success'))
                <div class="mb-6 bg-emerald-500/20 backdrop-blur-sm border border-emerald-300/40 text-emerald-100 px-4 py-3 rounded-xl flex items-center space-x-2 text-sm" role="alert">
                    <i class="bi bi-check-circle-fill text-emerald-300 shrink-0"></i>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 bg-rose-500/20 backdrop-blur-sm border border-rose-300/40 text-rose-100 px-4 py-3 rounded-xl flex items-center space-x-2 text-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-rose-300 shrink-0"></i>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                @endif

                <!-- Login Form -->
                <form class="space-y-5" action="{{ route('login') }}" method="POST">
                    @csrf

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-white/85 mb-1.5">
                            Email
                        </label>
                        <input id="email" 
                               name="email" 
                               type="email" 
                               autocomplete="email" 
                               required 
                               value="{{ old('email') }}"
                               class="auth-glass-input"
                               placeholder="you@example.com">
                        @error('email')
                            <p class="mt-1.5 text-xs text-rose-300 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-white/85 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <input id="password" 
                                   name="password" 
                                   type="password" 
                                   autocomplete="current-password" 
                                   required
                                   class="auth-glass-input pr-10"
                                   placeholder="••••••••">
                            <button type="button" 
                                    id="togglePassword" 
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-white/70 hover:text-white p-1.5 focus:outline-none transition-colors cursor-pointer"
                                    title="Show/Hide Password">
                                <i class="bi bi-eye text-sm" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-rose-300 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password Row -->
                    <div class="flex items-center justify-between pt-1">
                        <label for="remember" class="flex items-center gap-2 cursor-pointer select-none group">
                            <input id="remember" 
                                   name="remember" 
                                   type="checkbox"
                                   class="w-4 h-4 rounded border-white/40 bg-white/15 text-sky-500 accent-sky-500 focus:ring-sky-400/40 cursor-pointer">
                            <span class="text-xs text-white/80 group-hover:text-white transition-colors">
                                Remember me
                            </span>
                        </label>

                        <a href="{{ route('password.request') }}" class="text-xs text-sky-200 hover:text-white transition-colors font-medium">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Primary Action Button -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full py-3 px-4 rounded-xl font-semibold text-sm tracking-wide text-white bg-sky-600/90 hover:bg-sky-500 active:scale-[0.995] transition-all shadow-lg shadow-sky-950/30 cursor-pointer flex items-center justify-center gap-2">
                            <span>Log In</span>
                        </button>
                    </div>

                    <!-- Social / Alternative Actions -->
                    <div class="space-y-3 pt-2">
                        <!-- Google OAuth -->
                        <a href="{{ route('auth.google') }}"
                           class="w-full flex items-center justify-center py-2.5 px-4 rounded-xl text-sm font-medium text-white bg-white/15 hover:bg-white/25 backdrop-blur-sm border border-white/30 transition-all shadow-sm group cursor-pointer">
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
                           class="w-full flex items-center justify-center py-2.5 px-4 rounded-xl text-sm font-medium text-white bg-white/15 hover:bg-white/25 backdrop-blur-sm border border-white/30 transition-all shadow-sm group cursor-pointer">
                            <i class="bi bi-person-plus text-white/80 group-hover:text-white mr-2.5 text-base transition-colors"></i>
                            <span>Create account</span>
                        </a>
                    </div>
                </form>

            </div>

            <!-- Bottom Area: Footer & Copyright -->
            <div class="pt-6 text-center">
                <p class="text-xs text-white/65">
                    &copy; {{ date('Y') }} DairyBox Management System. All rights reserved.
                </p>
            </div>

        </div>

    </div>

    <!-- Shuffled 10-Second Slideshow & Password Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Password visibility toggle
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

            // Shuffled 10-second background slideshow matching homepage
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

            function shuffleSlides(list) {
                const shuffled = list.slice();
                for (let i = shuffled.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
                }
                return shuffled;
            }

            const slidesContainer = document.getElementById('loginSlidesContainer');
            if (!slidesContainer || heroSlides.length === 0) {
                return;
            }

            const orderedSlides = shuffleSlides(heroSlides);
            let currentIndex = 0;
            const INTERVAL_MS = 10000;

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

            setInterval(function () {
                const nextIdx = (currentIndex + 1) % orderedSlides.length;
                slideElements[currentIndex].classList.remove('opacity-100');
                slideElements[currentIndex].classList.add('opacity-0');
                currentIndex = nextIdx;
                slideElements[currentIndex].classList.remove('opacity-0');
                slideElements[currentIndex].classList.add('opacity-100');
            }, INTERVAL_MS);
        });
    </script>
</body>
</html>
