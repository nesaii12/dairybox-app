@extends('layouts.customer')

@section('title', 'Account Settings')

@section('content')
<div class="space-y-8">
    <!-- Breadcrumbs & Heading -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ url('/') }}" class="hover:text-slate-900 transition-colors">Home</a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-800 font-semibold">Account Settings</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 font-serif">Account Settings</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage your personal profile, contact information, and security preferences.</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('customer.orders.index') }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:text-sky-600 transition-all shadow-2xs">
                <i class="bi bi-bag-check text-slate-400"></i>
                <span>My Orders</span>
            </a>
            <a href="{{ route('customer.catalog') }}" class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#0ea5e9] hover:bg-sky-600 transition-all shadow-2xs">
                <i class="bi bi-shop"></i>
                <span>Continue Shopping</span>
            </a>
        </div>
    </div>

    <!-- Account Overview Card -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-sky-50 text-sky-600 font-black border border-sky-100 flex items-center justify-center text-lg tracking-wider shadow-2xs flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center space-x-2.5">
                    <h2 class="text-lg font-bold text-slate-900">{{ $user->name }}</h2>
                    @if($user->status === 'restricted' && $user->isRestricted())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                            Account Restricted
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                            Active Customer
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">{{ $user->email }} &bull; Member since {{ $user->created_at->format('M Y') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 w-full md:w-auto">
            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/70 text-center md:text-left">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Orders</span>
                <span class="block text-lg font-extrabold text-slate-900 mt-0.5">{{ $ordersCount }}</span>
            </div>
            <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/70 text-center md:text-left">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Registered Phone</span>
                <span class="block text-xs font-semibold text-slate-800 mt-1 truncate">{{ $user->phone ?? 'Not set' }}</span>
            </div>
        </div>
    </div>

    <!-- Forms Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- 1. Profile Information -->
        <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100">
                            <i class="bi bi-person-badge text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Personal Information</h3>
                            <p class="text-[11px] text-slate-500">Update your contact information for order delivery notifications.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('customer.settings.updateProfile') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                            class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                        @error('name')
                            <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                        @error('email')
                            <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-bold text-slate-700 mb-1.5">Contact Phone Number</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. 0917-123-4567"
                            class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                        <p class="text-[10px] text-slate-400 mt-1">Our riders will use this contact number to update you on dispatch and arrival.</p>
                        @error('phone')
                            <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-[#0ea5e9] hover:bg-sky-600 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex items-center space-x-2 cursor-pointer">
                            <i class="bi bi-check2"></i>
                            <span>Save Profile</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Security & Password -->
        <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                            <i class="bi bi-shield-lock-fill text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Security & Password</h3>
                            <p class="text-[11px] text-slate-500">Change your password regularly to keep your DairyBox account secure.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('customer.settings.updatePassword') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1.5">Current Password</label>
                        <div class="relative">
                            <input type="password" id="current_password" name="current_password" required
                                class="block w-full pr-10 px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                            <button type="button" onclick="togglePasswordVisibility('current_password', 'cust-curr-toggle')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                <i id="cust-curr-toggle" class="bi bi-eye text-sm"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">New Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required minlength="8"
                                class="block w-full pr-10 px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                            <button type="button" onclick="togglePasswordVisibility('password', 'cust-pass-toggle')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                <i id="cust-pass-toggle" class="bi bi-eye text-sm"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror

                        <!-- Live Password Requirements Checklist -->
                        <div class="mt-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <p class="text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1">
                                <i class="bi bi-shield-lock text-sky-600"></i> Password Complexity:
                            </p>
                            <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                                <div id="cust-rule-length" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                    <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                    <span>8+ characters</span>
                                </div>
                                <div id="cust-rule-case" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                    <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                    <span>Upper & lowercase</span>
                                </div>
                                <div id="cust-rule-number" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                    <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                    <span>At least 1 number</span>
                                </div>
                                <div id="cust-rule-symbol" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                    <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                    <span>At least 1 symbol</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">Confirm New Password</label>
                        <div class="relative">
                            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                                class="block w-full pr-10 px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                            <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'cust-confirm-toggle')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                <i id="cust-confirm-toggle" class="bi bi-eye text-sm"></i>
                            </button>
                        </div>
                        <p id="cust-match-hint" class="hidden text-[11px] font-medium mt-1"></p>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex items-center space-x-2 cursor-pointer">
                            <i class="bi bi-shield-check"></i>
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePasswordVisibility(fieldId, iconId) {
        const input = document.getElementById(fieldId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash text-sm text-[#0ea5e9]';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye text-sm text-slate-400';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const matchHint = document.getElementById('cust-match-hint');

        const rules = {
            length: { el: document.getElementById('cust-rule-length'), test: (val) => val.length >= 8 },
            case: { el: document.getElementById('cust-rule-case'), test: (val) => /[a-z]/.test(val) && /[A-Z]/.test(val) },
            number: { el: document.getElementById('cust-rule-number'), test: (val) => /[0-9]/.test(val) },
            symbol: { el: document.getElementById('cust-rule-symbol'), test: (val) => /[^A-Za-z0-9]/.test(val) },
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
    });
</script>
@endsection
