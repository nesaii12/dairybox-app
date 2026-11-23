<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
            background-color: #0f172a;
        }

        .auth-glass-input {
            background-color: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.30);
            border-radius: 0.75rem;
            padding: 0.62rem 0.9rem;
            font-size: 0.9rem;
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
            color: rgba(255, 255, 255, 0.55);
            font-size: 0.86rem;
        }

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
<body class="min-h-screen text-white antialiased overflow-x-hidden relative">

    <!-- Full-Bleed Shuffled Background Image Slideshow (z-0, 10s interval, no arrows) -->
    <div id="registerSlideshowBackdrop" class="fixed inset-0 z-0 overflow-hidden pointer-events-none select-none bg-slate-950" aria-hidden="true">
        <div id="registerSlidesContainer" class="absolute inset-0 w-full h-full">
            <img src="{{ asset('images/hero/slide-03.jpg') }}" alt="DairyBox Farm & Community" class="absolute inset-0 w-full h-full object-cover object-center opacity-100 transition-opacity duration-700 ease-in-out">
        </div>
        <!-- Balanced Dark Contrast Overlay -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/30 to-black/50"></div>
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center py-8 px-4 sm:px-6">

        <!-- Centered See-Through Frosted Glass Create Account Card -->
        <div class="w-full max-w-md bg-black/30 backdrop-blur-md border border-white/25 rounded-3xl shadow-2xl p-6 sm:p-8">

            <!-- Top Area: Logo & Back Link (matching Login) -->
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

            <!-- Heading -->
            <div class="mb-6">
                <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight drop-shadow-sm">Create Account</h2>
                <p class="mt-1.5 text-sm text-white/80">
                    Join DairyBox to order fresh milk &amp; ice creams!
                </p>
            </div>

            <!-- Server Flash Alerts -->
            @if(session('success'))
            <div class="mb-4 bg-emerald-500/20 backdrop-blur-sm border border-emerald-300/40 text-emerald-100 px-4 py-2.5 rounded-xl flex items-center space-x-2 text-sm" role="alert">
                <i class="bi bi-check-circle-fill text-emerald-300"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4 bg-rose-500/20 backdrop-blur-sm border border-rose-300/40 text-rose-100 px-4 py-2.5 rounded-xl flex items-center space-x-2 text-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-rose-300"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            @endif

            <!-- Dynamic Form Error Alert -->
            <div id="register-alert" class="hidden mb-4 bg-rose-500/20 backdrop-blur-sm border border-rose-300/40 text-rose-100 px-4 py-2.5 rounded-xl items-start space-x-2 text-xs" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-rose-300 mt-0.5 shrink-0"></i>
                <span id="register-alert-text" class="font-medium leading-relaxed"></span>
            </div>

            <form id="register-form" class="space-y-4" action="{{ route('register') }}" method="POST" novalidate>
                @csrf

                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-xs font-semibold text-white/85 mb-1.5">Full Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="bi bi-person text-white/60 text-sm"></i>
                        </div>
                        <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}"
                            class="auth-glass-input !pl-10"
                            placeholder="Juan Dela Cruz">
                    </div>
                    <p data-field-error="name" class="hidden mt-1 text-xs text-rose-300 font-semibold"></p>
                    @error('name')
                        <p class="mt-1 text-xs text-rose-300 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-white/85 mb-1.5">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="bi bi-envelope text-white/60 text-sm"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                            class="auth-glass-input !pl-10"
                            placeholder="yourname@gmail.com">
                    </div>
                    <p data-field-error="email" class="hidden mt-1 text-xs text-rose-300 font-semibold"></p>
                    @error('email')
                        <p class="mt-1 text-xs text-rose-300 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone Number -->
                <div>
                    <label for="phone" class="block text-xs font-semibold text-white/85 mb-1.5">Contact Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="bi bi-telephone text-white/60 text-sm"></i>
                        </div>
                        <input id="phone" name="phone" type="tel" inputmode="numeric" maxlength="13" required value="{{ old('phone') }}"
                            class="auth-glass-input !pl-10"
                            placeholder="09171234567">
                    </div>
                    <p data-field-error="phone" class="hidden mt-1 text-xs text-rose-300 font-semibold"></p>
                    @error('phone')
                        <p class="mt-1 text-xs text-rose-300 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-white/85 mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="bi bi-lock text-white/60 text-sm"></i>
                        </div>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                            class="auth-glass-input !pl-10 !pr-10"
                            placeholder="Create a strong password">
                        <button type="button" onclick="togglePasswordVisibility('password', 'toggle-pass-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-white/70 hover:text-white focus:outline-none cursor-pointer">
                            <i id="toggle-pass-icon" class="bi bi-eye text-sm"></i>
                        </button>
                    </div>
                    <p data-field-error="password" class="hidden mt-1 text-xs text-rose-300 font-semibold"></p>
                    @error('password')
                        <p class="mt-1 text-xs text-rose-300 font-semibold">{{ $message }}</p>
                    @enderror

                    <!-- Password Requirements Checklist (Frosted Glass) -->
                    <div class="mt-2.5 p-3 rounded-xl bg-white/10 border border-white/20">
                        <p class="text-[11px] font-semibold text-white/85 mb-1.5 flex items-center gap-1">
                            <i class="bi bi-shield-check text-sky-300"></i> Password Requirements:
                        </p>
                        <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                            <div id="rule-length" class="flex items-center gap-1.5 text-white/70 transition-colors">
                                <i class="bi bi-circle text-[9px] text-white/50"></i>
                                <span>8+ characters</span>
                            </div>
                            <div id="rule-case" class="flex items-center gap-1.5 text-white/70 transition-colors">
                                <i class="bi bi-circle text-[9px] text-white/50"></i>
                                <span>Upper &amp; lowercase</span>
                            </div>
                            <div id="rule-number" class="flex items-center gap-1.5 text-white/70 transition-colors">
                                <i class="bi bi-circle text-[9px] text-white/50"></i>
                                <span>At least 1 number</span>
                            </div>
                            <div id="rule-symbol" class="flex items-center gap-1.5 text-white/70 transition-colors">
                                <i class="bi bi-circle text-[9px] text-white/50"></i>
                                <span>At least 1 symbol</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-white/85 mb-1.5">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="bi bi-shield-check text-white/60 text-sm"></i>
                        </div>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                            class="auth-glass-input !pl-10 !pr-10"
                            placeholder="Re-type password">
                        <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'toggle-confirm-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-white/70 hover:text-white focus:outline-none cursor-pointer">
                            <i id="toggle-confirm-icon" class="bi bi-eye text-sm"></i>
                        </button>
                    </div>
                    <p id="match-hint" class="hidden text-[11px] font-medium mt-1"></p>
                </div>

                <div class="pt-2">
                    <button type="submit" id="register-submit-btn"
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl shadow-lg shadow-sky-950/30 text-sm font-semibold text-white bg-sky-600/90 hover:bg-sky-500 active:scale-[0.995] transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                        <span id="register-submit-spinner" class="hidden w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <span id="register-submit-label">Register Account</span>
                    </button>
                </div>

                <div class="relative flex items-center justify-center my-2">
                    <div class="border-t border-white/20 w-full"></div>
                    <span class="px-3 text-[11px] font-semibold text-white/65 uppercase tracking-wider shrink-0">or</span>
                    <div class="border-t border-white/20 w-full"></div>
                </div>

                <div>
                    <a href="{{ route('auth.google') }}"
                        class="w-full flex items-center justify-center py-2.5 px-4 bg-white/15 hover:bg-white/25 backdrop-blur-sm border border-white/30 rounded-xl text-sm font-medium text-white transition-all shadow-sm">
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

            <div class="mt-6 text-center space-y-2">
                <p class="text-xs text-white/75">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-bold text-sky-300 hover:text-white ml-1 transition-colors">Log in here</a>
                </p>
                <p class="text-[11px] text-white/55">
                    &copy; {{ date('Y') }} DairyBox Management System. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal (Matching Frosted Glass Aesthetic) -->
    <div id="otp-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/55 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="otp-modal-title">
        <div class="w-full max-w-md bg-slate-950/75 backdrop-blur-lg rounded-3xl shadow-2xl border border-white/25 p-6 sm:p-7 relative text-white">
            <button type="button" id="otp-modal-close" aria-label="Close verification modal"
                class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 text-white/75 hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                <i class="bi bi-x-lg text-xs"></i>
            </button>

            <div class="text-center mb-5">
                <div class="w-12 h-12 rounded-2xl bg-sky-500/20 border border-sky-300/35 text-sky-300 flex items-center justify-center mx-auto mb-3">
                    <i id="otp-modal-icon" class="bi bi-shield-lock-fill text-xl"></i>
                </div>
                <h3 id="otp-modal-title" class="text-xl font-bold text-white tracking-tight">Verify Your Account</h3>
                <p id="otp-modal-subtitle" class="mt-1.5 text-xs sm:text-sm text-white/80 leading-relaxed">
                    We sent a 6-digit verification code to
                    <strong id="otp-masked-target" class="text-white font-bold"></strong>.
                </p>
                <p class="mt-1.5 text-[11px] text-white/55">Code expires in 5 minutes.</p>
            </div>

            <!-- Inline Modal Feedback -->
            <div id="otp-modal-error" class="hidden mb-4 bg-rose-500/20 border border-rose-300/40 text-rose-100 px-3.5 py-2.5 rounded-xl items-start gap-2 text-xs" role="alert">
                <i class="bi bi-exclamation-circle-fill text-rose-300 mt-0.5 shrink-0"></i>
                <span id="otp-modal-error-text" class="font-medium leading-relaxed"></span>
            </div>

            <div id="otp-modal-info" class="hidden mb-4 bg-emerald-500/20 border border-emerald-300/40 text-emerald-100 px-3.5 py-2.5 rounded-xl items-start gap-2 text-xs" role="status">
                <i class="bi bi-check-circle-fill text-emerald-300 mt-0.5 shrink-0"></i>
                <span id="otp-modal-info-text" class="font-medium leading-relaxed"></span>
            </div>

            <form id="otp-verify-form" class="space-y-4" novalidate>
                <div>
                    <label for="otp-code-input" class="block text-center text-xs font-semibold uppercase tracking-wider text-white/75 mb-2">
                        Enter 6-Digit Verification Code
                    </label>
                    <input id="otp-code-input" name="otp" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
                        class="block w-full py-3 px-4 text-center text-2xl font-extrabold font-mono tracking-[0.45em] pl-[calc(1rem+0.45em)] bg-white/15 border-2 border-white/30 rounded-xl text-white placeholder-white/40 focus:bg-white/20 focus:outline-none focus:border-sky-300 focus:ring-4 focus:ring-sky-400/20 transition-all"
                        placeholder="000000">
                </div>

                <button type="submit" id="verify-otp-submit-btn"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl shadow-lg shadow-sky-950/30 text-sm font-semibold text-white bg-sky-600/90 hover:bg-sky-500 transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    <span id="verify-otp-spinner" class="hidden w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                    <span id="verify-otp-label">Verify &amp; Create Account</span>
                </button>
            </form>

            <!-- 60-Second Countdown & Resend Code -->
            <div class="mt-5 pt-4 border-t border-white/15 text-center space-y-2">
                <p id="otp-countdown-row" class="text-xs text-white/75 flex items-center justify-center gap-1.5">
                    <i class="bi bi-clock text-white/60"></i>
                    <span>Resend code in</span>
                    <span id="otp-countdown-display" class="font-mono font-bold text-white bg-white/15 px-2 py-0.5 rounded-md">01:00</span>
                </p>

                <div id="otp-resend-row" class="hidden">
                    <p class="text-xs text-white/75">
                        Didn't receive the code?
                        <button type="button" id="otp-resend-btn"
                            class="font-bold text-sky-300 hover:text-white ml-1 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            Resend Code
                        </button>
                    </p>
                </div>

                <button type="button" id="otp-edit-email-btn" class="text-[11px] font-medium text-white/60 hover:text-white transition-colors cursor-pointer">
                    Wrong contact details? Edit registration details
                </button>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId, iconId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash text-sm text-sky-300';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye text-sm text-white/70';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Shuffled 10-second background slideshow matching Login & Home
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

            const slidesContainer = document.getElementById('registerSlidesContainer');
            if (slidesContainer && heroSlides.length > 0) {
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

                setInterval(() => {
                    const nextIdx = (currentIndex + 1) % orderedSlides.length;
                    slideElements[currentIndex].classList.remove('opacity-100');
                    slideElements[currentIndex].classList.add('opacity-0');
                    currentIndex = nextIdx;
                    slideElements[currentIndex].classList.remove('opacity-0');
                    slideElements[currentIndex].classList.add('opacity-100');
                }, INTERVAL_MS);
            }

            const registerForm = document.getElementById('register-form');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');
            const matchHint = document.getElementById('match-hint');
            const registerSubmitBtn = document.getElementById('register-submit-btn');
            const registerSubmitSpinner = document.getElementById('register-submit-spinner');
            const registerSubmitLabel = document.getElementById('register-submit-label');
            const registerAlert = document.getElementById('register-alert');
            const registerAlertText = document.getElementById('register-alert-text');

            // OTP Modal Elements
            const otpModal = document.getElementById('otp-modal');
            const otpModalClose = document.getElementById('otp-modal-close');
            const otpEditEmailBtn = document.getElementById('otp-edit-email-btn');
            const otpModalIcon = document.getElementById('otp-modal-icon');
            const otpModalTitle = document.getElementById('otp-modal-title');
            const otpModalSubtitle = document.getElementById('otp-modal-subtitle');
            const otpVerifyForm = document.getElementById('otp-verify-form');
            const otpCodeInput = document.getElementById('otp-code-input');
            const verifyOtpSubmitBtn = document.getElementById('verify-otp-submit-btn');
            const verifyOtpSpinner = document.getElementById('verify-otp-spinner');
            const verifyOtpLabel = document.getElementById('verify-otp-label');
            const otpModalError = document.getElementById('otp-modal-error');
            const otpModalErrorText = document.getElementById('otp-modal-error-text');
            const otpModalInfo = document.getElementById('otp-modal-info');
            const otpModalInfoText = document.getElementById('otp-modal-info-text');
            const otpCountdownRow = document.getElementById('otp-countdown-row');
            const otpCountdownDisplay = document.getElementById('otp-countdown-display');
            const otpResendRow = document.getElementById('otp-resend-row');
            const otpResendBtn = document.getElementById('otp-resend-btn');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || document.querySelector('input[name="_token"]')?.value
                || '';

            const SEND_OTP_URL = @json(route('register.sendOtp'));
            const VERIFY_OTP_URL = @json(route('register.verifyOtp'));

            let pendingEmail = '';
            let countdownTimer = null;

            // Live Password Requirements Checklist
            const rules = {
                length: { el: document.getElementById('rule-length'), test: (val) => val.length >= 8 },
                case: { el: document.getElementById('rule-case'), test: (val) => /[a-z]/.test(val) && /[A-Z]/.test(val) },
                number: { el: document.getElementById('rule-number'), test: (val) => /[0-9]/.test(val) },
                symbol: { el: document.getElementById('rule-symbol'), test: (val) => /[^A-Za-z0-9]/.test(val) },
            };

            function updatePasswordRules() {
                const val = passwordInput.value;
                for (const key in rules) {
                    const rule = rules[key];
                    if (!rule.el) continue;
                    const passed = rule.test(val);
                    const icon = rule.el.querySelector('i');
                    if (passed) {
                        rule.el.className = 'flex items-center gap-1.5 text-emerald-300 font-medium transition-colors';
                        if (icon) icon.className = 'bi bi-check-circle-fill text-[11px] text-emerald-300';
                    } else {
                        rule.el.className = 'flex items-center gap-1.5 text-white/70 transition-colors';
                        if (icon) icon.className = 'bi bi-circle text-[9px] text-white/50';
                    }
                }
                checkMatch();
            }

            function checkMatch() {
                if (!confirmInput || !matchHint) return;
                const passVal = passwordInput.value;
                const confirmVal = confirmInput.value;

                if (!confirmVal) {
                    matchHint.classList.add('hidden');
                    return;
                }

                matchHint.classList.remove('hidden');
                if (passVal === confirmVal) {
                    matchHint.className = 'text-[11px] font-semibold text-emerald-300 mt-1 flex items-center gap-1';
                    matchHint.innerHTML = '<i class="bi bi-check-circle-fill text-xs text-emerald-300"></i> Passwords match';
                } else {
                    matchHint.className = 'text-[11px] font-medium text-rose-300 mt-1 flex items-center gap-1';
                    matchHint.innerHTML = '<i class="bi bi-exclamation-circle text-xs text-rose-300"></i> Passwords do not match';
                }
            }

            passwordInput?.addEventListener('input', updatePasswordRules);
            confirmInput?.addEventListener('input', checkMatch);

            // Helpers for Form Errors & Loading States
            function clearFormErrors() {
                registerAlert.classList.add('hidden');
                registerAlert.classList.remove('flex');
                document.querySelectorAll('[data-field-error]').forEach((el) => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
            }

            function showFormAlert(message) {
                registerAlertText.textContent = message;
                registerAlert.classList.remove('hidden');
                registerAlert.classList.add('flex');
            }

            function showFieldErrors(errors) {
                if (!errors || typeof errors !== 'object') return;
                Object.entries(errors).forEach(([field, messages]) => {
                    const target = document.querySelector(`[data-field-error="${field}"]`);
                    const msg = Array.isArray(messages) ? messages[0] : String(messages);
                    if (target) {
                        target.textContent = msg;
                        target.classList.remove('hidden');
                    }
                });
            }

            function setRegisterLoading(loading) {
                registerSubmitBtn.disabled = loading;
                registerSubmitSpinner.classList.toggle('hidden', !loading);
                registerSubmitLabel.textContent = loading ? 'Sending Verification Code...' : 'Register Account';
            }

            function setVerifyLoading(loading) {
                verifyOtpSubmitBtn.disabled = loading;
                verifyOtpSpinner.classList.toggle('hidden', !loading);
                verifyOtpLabel.textContent = loading ? 'Verifying...' : 'Verify & Create Account';
            }

            function showModalError(message) {
                otpModalInfo.classList.add('hidden');
                otpModalInfo.classList.remove('flex');
                otpModalErrorText.textContent = message;
                otpModalError.classList.remove('hidden');
                otpModalError.classList.add('flex');
            }

            function showModalInfo(message) {
                otpModalError.classList.add('hidden');
                otpModalError.classList.remove('flex');
                otpModalInfoText.textContent = message;
                otpModalInfo.classList.remove('hidden');
                otpModalInfo.classList.add('flex');
            }

            function clearModalAlerts() {
                otpModalError.classList.add('hidden');
                otpModalError.classList.remove('flex');
                otpModalInfo.classList.add('hidden');
                otpModalInfo.classList.remove('flex');
            }

            function formatTimer(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            }

            function startResendCountdown(seconds) {
                if (countdownTimer) clearInterval(countdownTimer);
                let remaining = Math.max(0, parseInt(seconds, 10) || 0);

                if (remaining <= 0) {
                    otpCountdownRow.classList.add('hidden');
                    otpResendRow.classList.remove('hidden');
                    otpResendBtn.disabled = false;
                    return;
                }

                otpCountdownRow.classList.remove('hidden');
                otpResendRow.classList.add('hidden');
                otpCountdownDisplay.textContent = formatTimer(remaining);

                countdownTimer = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(countdownTimer);
                        countdownTimer = null;
                        otpCountdownDisplay.textContent = '00:00';
                        otpCountdownRow.classList.add('hidden');
                        otpResendRow.classList.remove('hidden');
                        otpResendBtn.disabled = false;
                    } else {
                        otpCountdownDisplay.textContent = formatTimer(remaining);
                    }
                }, 1000);
            }

            function updateModalDeliveryCopy(data) {
                const isLiveSms = data.delivery_channel === 'sms';
                const target = isLiveSms
                    ? (data.masked_phone || phoneInput.value.trim())
                    : (data.masked_email || emailInput.value.trim().toLowerCase());

                otpModalIcon.className = isLiveSms
                    ? 'bi bi-chat-dots-fill text-xl'
                    : 'bi bi-envelope-check-fill text-xl';
                otpModalTitle.textContent = isLiveSms ? 'Verify Your Phone Number' : 'Verify Your Email';
                otpModalSubtitle.innerHTML = '';
                otpModalSubtitle.append(isLiveSms ? 'We sent a 6-digit SMS verification code to ' : 'We sent a 6-digit verification code to ');
                const strong = document.createElement('strong');
                strong.className = 'text-white font-bold';
                strong.textContent = target;
                otpModalSubtitle.append(strong, '.');
            }

            function openOtpModal(data, cooldownSeconds) {
                updateModalDeliveryCopy(data);
                otpCodeInput.value = '';
                clearModalAlerts();
                otpModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                startResendCountdown(cooldownSeconds);
                setTimeout(() => otpCodeInput.focus(), 60);
            }

            function closeOtpModal() {
                otpModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                clearModalAlerts();
            }

            function getRegistrationPayload(isResend = false) {
                return {
                    _token: csrfToken,
                    name: nameInput.value.trim(),
                    email: emailInput.value.trim(),
                    phone: phoneInput.value.trim(),
                    password: passwordInput.value,
                    password_confirmation: confirmInput.value,
                    resend: isResend,
                };
            }

            // Step 1: Submit Registration Form -> POST /register/send-otp
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearFormErrors();
                setRegisterLoading(true);

                try {
                    const res = await fetch(SEND_OTP_URL, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(getRegistrationPayload(false)),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (res.ok && data.success) {
                        pendingEmail = data.email || emailInput.value.trim().toLowerCase();
                        openOtpModal(data, data.cooldown || 60);
                    } else if (res.status === 429 && data.cooldown && (data.masked_phone || data.masked_email)) {
                        pendingEmail = data.email || emailInput.value.trim().toLowerCase();
                        openOtpModal(data, data.cooldown);
                        showModalError(data.message || 'Please wait before requesting another code.');
                    } else {
                        showFieldErrors(data.errors);
                        showFormAlert(data.message || 'Please check your registration details and try again.');
                    }
                } catch (err) {
                    showFormAlert('Network error while sending verification code. Please try again.');
                } finally {
                    setRegisterLoading(false);
                }
            });

            // Numeric-only & Paste Support for 6-Digit OTP Input
            otpCodeInput.addEventListener('input', () => {
                otpCodeInput.value = otpCodeInput.value.replace(/\D/g, '').slice(0, 6);
            });

            otpCodeInput.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                otpCodeInput.value = pasted;
            });

            // Step 2: Verify OTP & Create Account -> POST /register/verify-otp
            otpVerifyForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearModalAlerts();

                const otp = otpCodeInput.value.replace(/\D/g, '');
                if (otp.length !== 6) {
                    showModalError('Please enter the 6-digit verification code sent to you.');
                    otpCodeInput.focus();
                    return;
                }

                setVerifyLoading(true);

                try {
                    const res = await fetch(VERIFY_OTP_URL, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            _token: csrfToken,
                            email: pendingEmail || emailInput.value.trim().toLowerCase(),
                            otp,
                        }),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (res.ok && data.success) {
                        verifyOtpLabel.textContent = 'Account Created! Redirecting...';
                        window.location.href = data.redirect || @json(route('home'));
                        return;
                    }

                    showModalError(data.message || 'Invalid or expired OTP code. Please try again.');
                    otpCodeInput.select();
                    otpCodeInput.focus();
                } catch (err) {
                    showModalError('Network error while verifying your code. Please try again.');
                } finally {
                    setVerifyLoading(false);
                }
            });

            // Resend OTP Code inside Modal
            otpResendBtn.addEventListener('click', async () => {
                clearModalAlerts();
                otpResendBtn.disabled = true;
                otpResendBtn.textContent = 'Sending...';

                try {
                    const res = await fetch(SEND_OTP_URL, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(getRegistrationPayload(true)),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (res.ok && data.success) {
                        pendingEmail = data.email || pendingEmail;
                        updateModalDeliveryCopy(data);
                        otpCodeInput.value = '';
                        otpCodeInput.focus();
                        showModalInfo(data.delivery_channel === 'sms'
                            ? 'A new 6-digit SMS verification code has been sent.'
                            : 'A new 6-digit verification code has been sent.');
                        startResendCountdown(data.cooldown || 60);
                    } else if (res.status === 429 && data.cooldown) {
                        showModalError(data.message || 'Please wait before resending.');
                        startResendCountdown(data.cooldown);
                    } else {
                        showModalError(data.message || 'Unable to resend verification code. Please try again.');
                        otpResendBtn.disabled = false;
                    }
                } catch (err) {
                    showModalError('Network error while resending code. Please try again.');
                    otpResendBtn.disabled = false;
                } finally {
                    otpResendBtn.textContent = 'Resend Code';
                }
            });

            otpModalClose.addEventListener('click', closeOtpModal);
            otpEditEmailBtn.addEventListener('click', () => {
                closeOtpModal();
                phoneInput.focus();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !otpModal.classList.contains('hidden')) {
                    closeOtpModal();
                }
            });
        });
    </script>
</body>
</html>
