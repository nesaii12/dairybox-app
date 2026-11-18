<!DOCTYPE html>
<html lang="en" class="min-h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title>Verify Email OTP | DairyBox</title>
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
        /* Hide arrows on number inputs */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
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
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Verify Your Email</h2>
            <p class="mt-2 text-sm text-slate-600 max-w-sm mx-auto">
                We sent a 6-digit verification code to
                <span class="font-bold text-slate-800 block mt-0.5">{{ $email }}</span>
            </p>
        </div>

        <div class="bg-white pt-10 pb-8 px-6 shadow-xl shadow-slate-100 rounded-2xl border border-slate-100 relative">
            <!-- Back Arrow -->
            <a href="{{ route('register') }}" class="absolute top-4 left-4 inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 border border-slate-200 text-slate-400 hover:text-slate-800 transition-all hover:bg-slate-100 shadow-2xs hover:scale-105" title="Back to Register">
                <i class="bi bi-arrow-left text-sm font-bold"></i>
            </a>

            <!-- Status Alerts -->
            @if(session('success'))
            <div class="mb-5 bg-emerald-50 border border-emerald-100 text-emerald-800 px-4 py-3 rounded-xl flex items-start space-x-2.5 text-sm" role="alert">
                <i class="bi bi-check-circle-fill text-emerald-500 mt-0.5 flex-shrink-0"></i>
                <span class="font-medium text-xs leading-relaxed">{{ session('success') }}</span>
            </div>
            @endif

            @if(session('info'))
            <div class="mb-5 bg-sky-50 border border-sky-100 text-sky-800 px-4 py-3 rounded-xl flex items-start space-x-2.5 text-sm" role="alert">
                <i class="bi bi-info-circle-fill text-sky-500 mt-0.5 flex-shrink-0"></i>
                <span class="font-medium text-xs leading-relaxed">{{ session('info') }}</span>
            </div>
            @endif

            @if($errors->has('otp'))
            <div class="mb-5 bg-rose-50 border border-rose-100 text-rose-800 px-4 py-3 rounded-xl flex items-start space-x-2.5 text-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 mt-0.5 flex-shrink-0"></i>
                <span class="font-medium text-xs leading-relaxed">{{ $errors->first('otp') }}</span>
            </div>
            @endif

            <!-- OTP Submission Form -->
            <form id="otp-form" action="{{ route('otp.submit') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="otp" id="otp-unified" value="">

                <div>
                    <label class="block text-center text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">
                        Enter 6-Digit Code
                    </label>

                    <!-- 6 Digit Input Boxes -->
                    <div class="flex justify-between items-center gap-2 max-w-xs mx-auto">
                        @for($i = 0; $i < 6; $i++)
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                            class="otp-digit w-11 h-13 text-center text-xl font-bold font-mono rounded-xl bg-slate-50 border-2 border-slate-200 text-slate-900 focus:bg-white focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 focus:outline-none transition-all shadow-2xs"
                            data-index="{{ $i }}">
                        @endfor
                    </div>
                </div>

                <div>
                    <button type="submit" id="verify-btn"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all hover:-translate-y-0.5 cursor-pointer">
                        Verify & Access App
                    </button>
                </div>
            </form>

            <!-- Resend Section with Cooldown -->
            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <div id="resend-countdown-wrap" class="{{ ($remainingCooldown ?? 0) > 0 ? '' : 'hidden' }}">
                    <p class="text-xs text-slate-500 flex items-center justify-center gap-1.5">
                        <i class="bi bi-clock-history text-slate-400"></i>
                        <span>Resend available in</span>
                        <span id="cooldown-timer" class="font-mono font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded text-[11px]">{{ $remainingCooldown ?? 0 }}s</span>
                    </p>
                </div>

                <div id="resend-button-wrap" class="{{ ($remainingCooldown ?? 0) > 0 ? 'hidden' : '' }}">
                    <form action="{{ route('otp.resend') }}" method="POST">
                        @csrf
                        <p class="text-xs text-slate-500">
                            Didn't receive the code?
                            <button type="submit" class="font-bold text-sky-600 hover:text-sky-500 ml-1 cursor-pointer transition-colors">
                                Resend Code
                            </button>
                        </p>
                    </form>
                </div>

                <div class="mt-4">
                    <a href="{{ route('register') }}" class="text-xs text-slate-400 hover:text-slate-600 transition-colors">
                        Wrong email address? Register again
                    </a>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const digitInputs = Array.from(document.querySelectorAll('.otp-digit'));
            const unifiedInput = document.getElementById('otp-unified');
            const otpForm = document.getElementById('otp-form');

            // Focus on first box
            if (digitInputs.length > 0) {
                digitInputs[0].focus();
            }

            function syncUnified() {
                const code = digitInputs.map(input => input.value).join('');
                if (unifiedInput) unifiedInput.value = code;
                return code;
            }

            digitInputs.forEach((input, index) => {
                // Keydown navigation
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace') {
                        if (!input.value && index > 0) {
                            digitInputs[index - 1].focus();
                            digitInputs[index - 1].value = '';
                        }
                    } else if (e.key === 'ArrowLeft' && index > 0) {
                        digitInputs[index - 1].focus();
                    } else if (e.key === 'ArrowRight' && index < digitInputs.length - 1) {
                        digitInputs[index + 1].focus();
                    }
                });

                // Input handling
                input.addEventListener('input', (e) => {
                    const val = input.value.replace(/\D/g, '');
                    input.value = val ? val[val.length - 1] : '';

                    if (input.value && index < digitInputs.length - 1) {
                        digitInputs[index + 1].focus();
                    }

                    const code = syncUnified();
                    if (code.length === 6) {
                        otpForm.submit();
                    }
                });

                // Paste support (e.g. user copies 6-digit OTP from email)
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                    if (!pastedData) return;

                    const chars = pastedData.slice(0, 6).split('');
                    chars.forEach((char, i) => {
                        if (digitInputs[i]) {
                            digitInputs[i].value = char;
                        }
                    });

                    const lastIndex = Math.min(chars.length, digitInputs.length) - 1;
                    if (lastIndex >= 0 && lastIndex < digitInputs.length - 1) {
                        digitInputs[lastIndex + 1].focus();
                    } else if (digitInputs[5]) {
                        digitInputs[5].focus();
                    }

                    const code = syncUnified();
                    if (code.length === 6) {
                        otpForm.submit();
                    }
                });
            });

            // Form submit validation
            if (otpForm) {
                otpForm.addEventListener('submit', (e) => {
                    const code = syncUnified();
                    if (code.length !== 6) {
                        e.preventDefault();
                        alert('Please enter all 6 digits of your verification code.');
                        const emptyIndex = digitInputs.findIndex(i => !i.value);
                        if (emptyIndex !== -1) {
                            digitInputs[emptyIndex].focus();
                        }
                    }
                });
            }

            // Resend Countdown Timer
            let remainingCooldown = {{ (int) ($remainingCooldown ?? 0) }};
            const countdownEl = document.getElementById('cooldown-timer');
            const countdownWrap = document.getElementById('resend-countdown-wrap');
            const buttonWrap = document.getElementById('resend-button-wrap');

            if (remainingCooldown > 0 && countdownEl) {
                const timer = setInterval(() => {
                    remainingCooldown--;
                    if (remainingCooldown <= 0) {
                        clearInterval(timer);
                        if (countdownWrap) countdownWrap.classList.add('hidden');
                        if (buttonWrap) buttonWrap.classList.remove('hidden');
                    } else {
                        countdownEl.textContent = remainingCooldown + 's';
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html>
