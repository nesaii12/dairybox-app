<!DOCTYPE html>
<html lang="en" class="min-h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Set New Password | DairyBox</title>
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
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Set New Password</h2>
            <p class="mt-2 text-sm text-slate-600">
                Please enter a strong new password for your account.
            </p>
        </div>

        <div class="bg-white pt-12 pb-8 px-6 shadow-xl shadow-slate-100 rounded-2xl border border-slate-100 relative">
            <!-- Back to Login Arrow -->
            <a href="{{ route('login') }}" class="absolute top-4 left-4 inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 border border-slate-200 text-slate-400 hover:text-slate-800 transition-all hover:bg-slate-100 shadow-2xs hover:scale-105" title="Back to Login">
                <i class="bi bi-arrow-left text-sm font-bold"></i>
            </a>

            @if(!empty($isExpired))
            <!-- Expired State (Over 60 seconds) -->
            <div class="text-center py-4 space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 text-rose-500 mx-auto flex items-center justify-center text-2xl shadow-xs">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Reset Link Expired</h3>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                        For security reasons, DairyBox password reset links expire after <strong>60 seconds</strong>. This link is no longer valid.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="{{ route('password.request') }}"
                        class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all hover:-translate-y-0.5">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Request a New Reset Link</span>
                    </a>
                </div>
            </div>
            @else

            <!-- Active Countdown Alert (60 Seconds) -->
            <div id="countdown-banner" class="mb-5 p-3.5 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-between text-amber-900">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700">
                        <i class="bi bi-hourglass-split text-sm animate-spin" style="animation-duration: 3s;"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight">Security Countdown</div>
                        <div class="text-[11px] text-amber-800">Link expires in:</div>
                    </div>
                </div>
                <div class="flex items-center gap-1 font-mono font-black text-sm text-amber-900 bg-white px-2.5 py-1 rounded-lg border border-amber-200/80 shadow-2xs">
                    <span id="countdown-display" class="text-amber-600 text-base">{{ $remainingSeconds ?? 60 }}</span>
                    <span class="text-xs font-normal text-slate-400">sec</span>
                </div>
            </div>

            <!-- Dynamic Expired Container (revealed when countdown hits 0) -->
            <div id="expired-overlay" class="hidden text-center py-4 space-y-4">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-100 text-rose-500 mx-auto flex items-center justify-center text-2xl shadow-xs">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Time's Up! Link Expired</h3>
                    <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">
                        The 60-second expiration window for this reset link has elapsed. Please request a new link to proceed.
                    </p>
                </div>
                <div class="pt-2">
                    <a href="{{ route('password.request') }}"
                        class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all hover:-translate-y-0.5">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Request a New Reset Link</span>
                    </a>
                </div>
            </div>

            @if($errors->any())
            <div class="mb-5 bg-rose-50 border border-rose-100 text-rose-800 px-4 py-3 rounded-xl flex items-start space-x-2.5 text-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0"></i>
                <div class="space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="font-medium text-xs">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            <form id="reset-form" class="space-y-5" action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <!-- Email (Readonly) -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700">Account Email</label>
                    <div class="mt-1.5 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-envelope text-slate-400 text-sm"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            value="{{ old('email', $email) }}"
                            class="block w-full pl-10 pr-3 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-slate-700 text-sm transition-all focus:outline-none cursor-not-allowed"
                            readonly>
                    </div>
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700">New Password</label>
                    <div class="mt-1.5 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-lock text-slate-400 text-sm"></i>
                        </div>
                        <input id="password" name="password" type="password" required autofocus
                            class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePasswordVisibility('password', 'toggle-pass-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                            <i id="toggle-pass-icon" class="bi bi-eye text-sm"></i>
                        </button>
                    </div>

                    <!-- Strong Password Requirements Indicator -->
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
                                <span>Upper & lowercase</span>
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
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirm New Password</label>
                    <div class="mt-1.5 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-shield-check text-slate-400 text-sm"></i>
                        </div>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="Re-type new password">
                        <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'toggle-confirm-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                            <i id="toggle-confirm-icon" class="bi bi-eye text-sm"></i>
                        </button>
                    </div>
                    <p id="match-hint" class="hidden text-[11px] font-medium mt-1"></p>
                </div>

                <div class="pt-2">
                    <button id="submit-btn" type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all hover:-translate-y-0.5 cursor-pointer">
                        Update & Save Password
                    </button>
                </div>
            </form>
            @endif

            <div class="mt-6 text-center">
                <p class="text-xs text-slate-500">
                    Remember your password?
                    <a href="{{ route('login') }}" class="font-bold text-sky-600 hover:text-sky-500 ml-1">Log in here</a>
                </p>
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
            // Countdown Timer Logic
            let remainingSeconds = {{ (int) ($remainingSeconds ?? 0) }};
            const displayEl = document.getElementById('countdown-display');
            const bannerEl = document.getElementById('countdown-banner');
            const overlayEl = document.getElementById('expired-overlay');
            const formEl = document.getElementById('reset-form');

            if (displayEl && remainingSeconds > 0) {
                const interval = setInterval(() => {
                    remainingSeconds--;
                    if (remainingSeconds <= 0) {
                        clearInterval(interval);
                        if (displayEl) displayEl.textContent = '0';
                        if (bannerEl) bannerEl.classList.add('hidden');
                        if (formEl) formEl.classList.add('hidden');
                        if (overlayEl) overlayEl.classList.remove('hidden');
                    } else {
                        displayEl.textContent = remainingSeconds;
                        if (remainingSeconds <= 15) {
                            displayEl.className = 'text-rose-600 text-base font-bold animate-pulse';
                        }
                    }
                }, 1000);
            }

            // Strong Password Validator
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');
            const matchHint = document.getElementById('match-hint');

            const rules = {
                length: { el: document.getElementById('rule-length'), test: (val) => val.length >= 8 },
                case: { el: document.getElementById('rule-case'), test: (val) => /[a-z]/.test(val) && /[A-Z]/.test(val) },
                number: { el: document.getElementById('rule-number'), test: (val) => /[0-9]/.test(val) },
                symbol: { el: document.getElementById('rule-symbol'), test: (val) => /[^A-Za-z0-9]/.test(val) },
            };

            function updatePasswordRules() {
                if (!passwordInput) return;
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
                if (!confirmInput || !matchHint || !passwordInput) return;
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

            if (passwordInput) {
                passwordInput.addEventListener('input', updatePasswordRules);
            }
            if (confirmInput) {
                confirmInput.addEventListener('input', checkMatch);
            }
        });
    </script>
</body>
</html>
