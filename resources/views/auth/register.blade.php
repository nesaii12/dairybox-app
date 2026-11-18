<!DOCTYPE html>
<html lang="en" class="min-h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
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
                Join DairyBox to order fresh milk & ice creams!
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
                    <div class="flex items-center justify-between">
                        <label for="email" class="block text-sm font-semibold text-slate-700">Email Address</label>
                        <span id="email-verified-badge" class="hidden items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i class="bi bi-patch-check-fill text-emerald-600"></i> Verified
                        </span>
                    </div>
                    <div class="mt-1 flex gap-2">
                        <div class="relative flex-1 rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="bi bi-envelope text-slate-400 text-sm"></i>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                                class="block w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                                placeholder="yourname@gmail.com">
                        </div>
                        <button type="button" id="send-email-otp-btn"
                            class="px-3.5 py-2.5 rounded-xl text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 transition-all whitespace-nowrap cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5">
                            <i class="bi bi-envelope-paper-fill text-sky-600"></i>
                            <span id="send-email-otp-label">Send Code</span>
                        </button>
                    </div>
                    @error('email')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                    <p id="email-otp-feedback" class="hidden mt-1.5 text-xs font-medium"></p>

                    <!-- Email OTP Verification Input Box -->
                    <div id="email-otp-box" class="hidden mt-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                        <label for="email_otp" class="block text-xs font-semibold text-slate-700">
                            Enter 6-Digit Email Verification Code
                        </label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="bi bi-shield-lock text-slate-400 text-sm"></i>
                                </div>
                                <input id="email_otp" name="email_otp" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                                    class="block w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 tracking-widest font-bold placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                                    placeholder="000000">
                            </div>
                            <button type="button" id="verify-email-otp-btn"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all whitespace-nowrap cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                Verify Code
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold text-slate-700">Phone Number</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-telephone text-slate-400 text-sm"></i>
                        </div>
                        <input id="phone" name="phone" type="tel" inputmode="numeric" maxlength="13" required value="{{ old('phone') }}"
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
                            class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="Create a strong password">
                        <button type="button" onclick="togglePasswordVisibility('password', 'toggle-pass-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                            <i id="toggle-pass-icon" class="bi bi-eye text-sm"></i>
                        </button>
                    </div>
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

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="bi bi-shield-check text-slate-400 text-sm"></i>
                        </div>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            class="block w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-sm transition-all"
                            placeholder="Re-type password">
                        <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'toggle-confirm-icon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                            <i id="toggle-confirm-icon" class="bi bi-eye text-sm"></i>
                        </button>
                    </div>
                    <p id="match-hint" class="hidden text-[11px] font-medium mt-1"></p>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all hover:-translate-y-0.5 cursor-pointer">
                        Register Account
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

            if (passwordInput) {
                passwordInput.addEventListener('input', updatePasswordRules);
            }
            if (confirmInput) {
                confirmInput.addEventListener('input', checkMatch);
            }

            // Email (Gmail) OTP Verification
            const emailInput = document.getElementById('email');
            const sendOtpBtn = document.getElementById('send-email-otp-btn');
            const sendOtpLabel = document.getElementById('send-email-otp-label');
            const otpBox = document.getElementById('email-otp-box');
            const otpInput = document.getElementById('email_otp');
            const verifyOtpBtn = document.getElementById('verify-email-otp-btn');
            const feedbackEl = document.getElementById('email-otp-feedback');
            const verifiedBadge = document.getElementById('email-verified-badge');
            const registerForm = emailInput ? emailInput.closest('form') : null;
            const csrfToken = document.querySelector('input[name="_token"]')?.value || '';

            let verifiedEmail = null;
            let cooldownTimer = null;

            function showEmailFeedback(msg, type = 'info') {
                if (!feedbackEl) return;
                feedbackEl.classList.remove('hidden', 'text-emerald-600', 'text-rose-600', 'text-sky-600');
                if (type === 'success') {
                    feedbackEl.classList.add('text-emerald-600');
                } else if (type === 'error') {
                    feedbackEl.classList.add('text-rose-600');
                } else {
                    feedbackEl.classList.add('text-sky-600');
                }
                feedbackEl.textContent = msg;
            }

            function startCooldown(seconds) {
                if (cooldownTimer) clearInterval(cooldownTimer);
                let remaining = seconds;
                sendOtpBtn.disabled = true;
                sendOtpLabel.textContent = `Resend (${remaining}s)`;

                cooldownTimer = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(cooldownTimer);
                        cooldownTimer = null;
                        if (verifiedEmail !== emailInput.value.trim().toLowerCase()) {
                            sendOtpBtn.disabled = false;
                            sendOtpLabel.textContent = 'Resend Code';
                        }
                    } else {
                        sendOtpLabel.textContent = `Resend (${remaining}s)`;
                    }
                }, 1000);
            }

            if (emailInput) {
                emailInput.addEventListener('input', () => {
                    if (verifiedEmail && emailInput.value.trim().toLowerCase() !== verifiedEmail) {
                        verifiedEmail = null;
                        verifiedBadge.classList.add('hidden');
                        verifiedBadge.classList.remove('inline-flex');
                        sendOtpBtn.classList.remove('hidden');
                        sendOtpBtn.disabled = false;
                        sendOtpLabel.textContent = 'Send Code';
                        feedbackEl.classList.add('hidden');
                    }
                });
            }

            if (sendOtpBtn) {
                sendOtpBtn.addEventListener('click', async () => {
                    const email = emailInput.value.trim();
                    if (!email) {
                        showEmailFeedback('Please enter your email address first.', 'error');
                        emailInput.focus();
                        return;
                    }

                    sendOtpBtn.disabled = true;
                    sendOtpLabel.textContent = 'Sending...';

                    try {
                        const res = await fetch('/register/email-otp/send', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ _token: csrfToken, email }),
                        });
                        const data = await res.json();

                        if (res.ok && data.success) {
                            emailInput.value = data.email || email;
                            otpBox.classList.remove('hidden');
                            otpInput.value = '';
                            otpInput.focus();
                            showEmailFeedback(data.message, 'info');
                            startCooldown(data.cooldown || 60);
                        } else {
                            showEmailFeedback(data.message || 'Unable to send email verification code.', 'error');
                            if (data.cooldown) {
                                otpBox.classList.remove('hidden');
                                startCooldown(data.cooldown);
                            } else {
                                sendOtpBtn.disabled = false;
                                sendOtpLabel.textContent = 'Send Code';
                            }
                        }
                    } catch (err) {
                        showEmailFeedback('Network error while sending verification email. Please try again.', 'error');
                        sendOtpBtn.disabled = false;
                        sendOtpLabel.textContent = 'Send Code';
                    }
                });
            }

            if (verifyOtpBtn) {
                verifyOtpBtn.addEventListener('click', async () => {
                    const email = emailInput.value.trim();
                    const code = otpInput.value.trim();

                    if (code.length !== 6) {
                        showEmailFeedback('Please enter the 6-digit verification code sent to your email.', 'error');
                        otpInput.focus();
                        return;
                    }

                    verifyOtpBtn.disabled = true;
                    verifyOtpBtn.textContent = 'Verifying...';

                    try {
                        const res = await fetch('/register/email-otp/verify', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ _token: csrfToken, email, code }),
                        });
                        const data = await res.json();

                        if (res.ok && data.success) {
                            verifiedEmail = (data.email || email).toLowerCase();
                            emailInput.value = verifiedEmail;
                            otpBox.classList.add('hidden');
                            sendOtpBtn.classList.add('hidden');
                            verifiedBadge.classList.remove('hidden');
                            verifiedBadge.classList.add('inline-flex');
                            showEmailFeedback('Email address verified.', 'success');
                        } else {
                            showEmailFeedback(data.message || 'Invalid verification code.', 'error');
                        }
                    } catch (err) {
                        showEmailFeedback('Network error while verifying code. Please try again.', 'error');
                    } finally {
                        verifyOtpBtn.disabled = false;
                        verifyOtpBtn.textContent = 'Verify Code';
                    }
                });
            }

            if (registerForm) {
                registerForm.addEventListener('submit', (e) => {
                    const hasTypedCode = otpInput && otpInput.value.trim().length === 6;
                    if (!verifiedEmail && !hasTypedCode) {
                        e.preventDefault();
                        showEmailFeedback('Please click "Send Code" and verify your email address before registering.', 'error');
                        emailInput.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>
