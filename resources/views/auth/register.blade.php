<!DOCTYPE html>
<html lang="en" class="min-h-full">
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
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Create Account</h2>
            <p class="mt-2 text-sm text-slate-600">
                Join DairyBox to order fresh milk &amp; ice creams!
            </p>
        </div>

        <div class="bg-white pt-12 pb-8 px-6 shadow-xl shadow-slate-100 rounded-2xl border border-slate-100 relative">
            <!-- Back to Home Arrow -->
            <a href="{{ url('/') }}" class="absolute top-4 left-4 inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 border border-slate-200 text-slate-400 hover:text-slate-800 transition-all hover:bg-slate-100 shadow-2xs hover:scale-105" title="Back to Home">
                <i class="bi bi-arrow-left text-sm font-bold"></i>
            </a>

            <!-- Server Flash Alerts -->
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

            <!-- Dynamic Form Error Alert -->
            <div id="register-alert" class="hidden mb-4 bg-rose-50 border border-rose-200/80 text-rose-800 px-4 py-2.5 rounded-xl items-start space-x-2 text-xs" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 shrink-0"></i>
                <span id="register-alert-text" class="font-medium leading-relaxed"></span>
            </div>

            <form id="register-form" class="space-y-4" action="{{ route('register') }}" method="POST" novalidate>
                @csrf

                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-700">Full Name</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-person text-slate-400 text-sm"></i>
                        </div>
                        <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="Juan Dela Cruz">
                    </div>
                    <p data-field-error="name" class="hidden mt-1 text-xs text-rose-600 font-semibold"></p>
                    @error('name')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700">Email Address</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-envelope text-slate-400 text-sm"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="yourname@gmail.com">
                    </div>
                    <p data-field-error="email" class="hidden mt-1 text-xs text-rose-600 font-semibold"></p>
                    @error('email')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone Number -->
                <div>
                    <label for="phone" class="block text-sm font-semibold text-slate-700">Contact Number</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-telephone text-slate-400 text-sm"></i>
                        </div>
                        <input id="phone" name="phone" type="tel" inputmode="numeric" maxlength="13" required value="{{ old('phone') }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="09171234567">
                    </div>
                    <p data-field-error="phone" class="hidden mt-1 text-xs text-rose-600 font-semibold"></p>
                    @error('phone')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-lock text-slate-400 text-sm"></i>
                        </div>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                            class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="Create a strong password">
                        <button type="button" onclick="togglePasswordVisibility('password', 'toggle-pass-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                            <i id="toggle-pass-icon" class="bi bi-eye text-sm"></i>
                        </button>
                    </div>
                    <p data-field-error="password" class="hidden mt-1 text-xs text-rose-600 font-semibold"></p>
                    @error('password')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror

                    <!-- Password Requirements Checklist -->
                    <div class="mt-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                        <p class="text-[11px] font-semibold text-slate-600 mb-1.5 flex items-center gap-1">
                            <i class="bi bi-shield-check text-sky-600"></i> Password Requirements:
                        </p>
                        <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                            <div id="rule-length" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                <span>8+ characters</span>
                            </div>
                            <div id="rule-case" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                <span>Upper &amp; lowercase</span>
                            </div>
                            <div id="rule-number" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                <span>At least 1 number</span>
                            </div>
                            <div id="rule-symbol" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                <span>At least 1 symbol</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-shield-check text-slate-400 text-sm"></i>
                        </div>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                            class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="Re-type password">
                        <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'toggle-confirm-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                            <i id="toggle-confirm-icon" class="bi bi-eye text-sm"></i>
                        </button>
                    </div>
                    <p id="match-hint" class="hidden text-[11px] font-medium mt-1"></p>
                </div>

                <div class="pt-2">
                    <button type="submit" id="register-submit-btn"
                        class="w-full flex items-center justify-center gap-2 py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all hover:-translate-y-0.5 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                        <span id="register-submit-spinner" class="hidden w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <span id="register-submit-label">Register Account</span>
                    </button>
                </div>

                <div class="relative flex items-center justify-center my-3">
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

            <div class="mt-6 text-center">
                <p class="text-xs text-slate-500">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-bold text-sky-600 hover:text-sky-500 ml-1">Log in here</a>
                </p>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal -->
    <div id="otp-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/55 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="otp-modal-title">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-100 p-6 sm:p-7 relative">
            <button type="button" id="otp-modal-close" aria-label="Close verification modal"
                class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-colors cursor-pointer">
                <i class="bi bi-x-lg text-xs"></i>
            </button>

            <div class="text-center mb-5">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 border border-sky-100 text-sky-600 flex items-center justify-center mx-auto mb-3">
                    <i class="bi bi-chat-dots-fill text-xl"></i>
                </div>
                <h3 id="otp-modal-title" class="text-xl font-extrabold text-slate-900 tracking-tight">Verify Your Phone Number</h3>
                <p class="mt-1.5 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    We sent a 6-digit SMS verification code to
                    <strong id="otp-masked-target" class="text-slate-900 font-bold"></strong>.
                </p>
                <p id="otp-fallback-notice" class="hidden mt-2 text-[11px] text-amber-700 bg-amber-50 border border-amber-200/80 rounded-lg px-3 py-1.5 leading-snug"></p>
                <p class="mt-1.5 text-[11px] text-slate-400">Code expires in 5 minutes.</p>
            </div>

            <!-- Inline Modal Feedback -->
            <div id="otp-modal-error" class="hidden mb-4 bg-rose-50 border border-rose-200/80 text-rose-700 px-3.5 py-2.5 rounded-xl items-start gap-2 text-xs" role="alert">
                <i class="bi bi-exclamation-circle-fill text-rose-500 mt-0.5 shrink-0"></i>
                <span id="otp-modal-error-text" class="font-medium leading-relaxed"></span>
            </div>

            <div id="otp-modal-info" class="hidden mb-4 bg-emerald-50 border border-emerald-200/80 text-emerald-700 px-3.5 py-2.5 rounded-xl items-start gap-2 text-xs" role="status">
                <i class="bi bi-check-circle-fill text-emerald-500 mt-0.5 shrink-0"></i>
                <span id="otp-modal-info-text" class="font-medium leading-relaxed"></span>
            </div>

            <form id="otp-verify-form" class="space-y-4" novalidate>
                <div>
                    <label for="otp-code-input" class="block text-center text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">
                        Enter 6-Digit Verification Code
                    </label>
                    <input id="otp-code-input" name="otp" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
                        class="block w-full py-3 px-4 text-center text-2xl font-extrabold font-mono tracking-[0.45em] pl-[calc(1rem+0.45em)] bg-slate-50 border-2 border-slate-200 rounded-xl text-slate-900 placeholder-slate-300 focus:bg-white focus:outline-none focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15 transition-all"
                        placeholder="000000">
                </div>

                <button type="submit" id="verify-otp-submit-btn"
                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                    <span id="verify-otp-spinner" class="hidden w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                    <span id="verify-otp-label">Verify &amp; Create Account</span>
                </button>
            </form>

            <!-- 60-Second Countdown & Resend Code -->
            <div class="mt-5 pt-4 border-t border-slate-100 text-center space-y-2">
                <p id="otp-countdown-row" class="text-xs text-slate-500 flex items-center justify-center gap-1.5">
                    <i class="bi bi-clock text-slate-400"></i>
                    <span>Resend code in</span>
                    <span id="otp-countdown-display" class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded-md">01:00</span>
                </p>

                <div id="otp-resend-row" class="hidden">
                    <p class="text-xs text-slate-500">
                        Didn't receive the code?
                        <button type="button" id="otp-resend-btn"
                            class="font-bold text-sky-600 hover:text-sky-500 ml-1 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            Resend Code
                        </button>
                    </p>
                </div>

                <button type="button" id="otp-edit-email-btn" class="text-[11px] font-medium text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
                    Wrong phone number? Edit registration details
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
                icon.className = 'bi bi-eye-slash text-sm text-sky-600';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye text-sm text-slate-400';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
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
            const otpMaskedTarget = document.getElementById('otp-masked-target');
            const otpFallbackNotice = document.getElementById('otp-fallback-notice');
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
                        rule.el.className = 'flex items-center gap-1.5 text-emerald-600 font-medium transition-colors';
                        if (icon) icon.className = 'bi bi-check-circle-fill text-[11px] text-emerald-600';
                    } else {
                        rule.el.className = 'flex items-center gap-1.5 text-slate-500 transition-colors';
                        if (icon) icon.className = 'bi bi-circle text-[9px] text-slate-400';
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
                    matchHint.className = 'text-[11px] font-semibold text-emerald-600 mt-1 flex items-center gap-1';
                    matchHint.innerHTML = '<i class="bi bi-check-circle-fill text-xs text-emerald-600"></i> Passwords match';
                } else {
                    matchHint.className = 'text-[11px] font-medium text-rose-500 mt-1 flex items-center gap-1';
                    matchHint.innerHTML = '<i class="bi bi-exclamation-circle text-xs text-rose-500"></i> Passwords do not match';
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

            function openOtpModal(maskedTarget, cooldownSeconds, fallbackNotice = null) {
                otpMaskedTarget.textContent = maskedTarget;
                if (fallbackNotice) {
                    otpFallbackNotice.textContent = fallbackNotice;
                    otpFallbackNotice.classList.remove('hidden');
                } else {
                    otpFallbackNotice.textContent = '';
                    otpFallbackNotice.classList.add('hidden');
                }
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
                        openOtpModal(data.masked_phone || phoneInput.value.trim(), data.cooldown || 60, data.fallback_notice || null);
                    } else if (res.status === 429 && data.cooldown && (data.masked_phone || data.masked_email)) {
                        // A valid OTP was already sent within the cooldown window; let the user enter it
                        pendingEmail = data.email || emailInput.value.trim().toLowerCase();
                        openOtpModal(data.masked_phone || data.masked_email, data.cooldown, null);
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
                    showModalError('Please enter the 6-digit verification code sent to your phone number.');
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
                        otpMaskedTarget.textContent = data.masked_phone || otpMaskedTarget.textContent;
                        if (data.fallback_notice) {
                            otpFallbackNotice.textContent = data.fallback_notice;
                            otpFallbackNotice.classList.remove('hidden');
                        } else {
                            otpFallbackNotice.textContent = '';
                            otpFallbackNotice.classList.add('hidden');
                        }
                        otpCodeInput.value = '';
                        otpCodeInput.focus();
                        showModalInfo('A new 6-digit SMS verification code has been sent.');
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
