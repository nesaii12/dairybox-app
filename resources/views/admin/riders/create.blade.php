@extends('layouts.admin')

@section('title', 'Add New Rider')
@section('page_title', 'Add New Rider')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.riders.index') }}" class="inline-flex items-center space-x-1.5 text-xs text-slate-500 hover:text-slate-900 transition-colors font-semibold">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Riders List</span>
    </a>
</div>

<div class="max-w-2xl bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/20">
        <h3 class="text-sm font-bold text-slate-950">Rider Account Details</h3>
        <p class="text-[11px] text-slate-500 mt-0.5">Please fill out the form below to create a rider login account.</p>
    </div>

    <form action="{{ route('admin.riders.store') }}" method="POST" class="p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Full Name -->
            <div class="space-y-1.5 col-span-2">
                <label for="name" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="e.g. John Doe" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400 @error('name') border-rose-300 @enderror" required>
                @error('name')
                    <p class="text-[10px] text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div class="space-y-1.5">
                <label for="email" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="e.g. rider@dairybox.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400 @error('email') border-rose-300 @enderror" required>
                @error('email')
                    <p class="text-[10px] text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone Number -->
            <div class="space-y-1.5">
                <label for="phone" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Phone Number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="e.g. 09123456789" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400 @error('phone') border-rose-300 @enderror" required>
                @error('phone')
                    <p class="text-[10px] text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <label for="password" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Password</label>
                <div class="relative">
                    <input type="password" name="password" id="password" placeholder="Create strong password" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400 @error('password') border-rose-300 @enderror" required>
                    <button type="button" onclick="togglePasswordVisibility('password', 'rider-pass-toggle')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                        <i id="rider-pass-toggle" class="bi bi-eye text-sm"></i>
                    </button>
                </div>
                @error('password')
                    <p class="text-[10px] text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror

                <!-- Password Requirements Checklist -->
                <div class="mt-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1 flex items-center gap-1">
                        <i class="bi bi-shield-check text-sky-600"></i> Password Rules:
                    </p>
                    <div class="grid grid-cols-2 gap-1 text-[10px]">
                        <div id="rider-rule-length" class="flex items-center gap-1 text-slate-500 transition-colors">
                            <i class="bi bi-circle text-[8px] text-slate-400"></i>
                            <span>8+ characters</span>
                        </div>
                        <div id="rider-rule-case" class="flex items-center gap-1 text-slate-500 transition-colors">
                            <i class="bi bi-circle text-[8px] text-slate-400"></i>
                            <span>Upper & lowercase</span>
                        </div>
                        <div id="rider-rule-number" class="flex items-center gap-1 text-slate-500 transition-colors">
                            <i class="bi bi-circle text-[8px] text-slate-400"></i>
                            <span>At least 1 number</span>
                        </div>
                        <div id="rider-rule-symbol" class="flex items-center gap-1 text-slate-500 transition-colors">
                            <i class="bi bi-circle text-[8px] text-slate-400"></i>
                            <span>At least 1 symbol</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Confirm Password</label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Re-enter password" class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400" required>
                    <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'rider-confirm-toggle')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                        <i id="rider-confirm-toggle" class="bi bi-eye text-sm"></i>
                    </button>
                </div>
                <p id="rider-match-hint" class="hidden text-[10px] font-medium mt-1"></p>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
            <a href="{{ route('admin.riders.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-100 transition-all cursor-pointer">
                Create Account
            </button>
        </div>
    </form>
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
            icon.className = 'bi bi-eye-slash text-sm text-sky-600';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye text-sm text-slate-400';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const matchHint = document.getElementById('rider-match-hint');

        const rules = {
            length: { el: document.getElementById('rider-rule-length'), test: (val) => val.length >= 8 },
            case: { el: document.getElementById('rider-rule-case'), test: (val) => /[a-z]/.test(val) && /[A-Z]/.test(val) },
            number: { el: document.getElementById('rider-rule-number'), test: (val) => /[0-9]/.test(val) },
            symbol: { el: document.getElementById('rider-rule-symbol'), test: (val) => /[^A-Za-z0-9]/.test(val) },
        };

        function updatePasswordRules() {
            const val = passwordInput.value;
            for (const key in rules) {
                const rule = rules[key];
                if (!rule.el) continue;
                const passed = rule.test(val);
                const icon = rule.el.querySelector('i');

                if (passed) {
                    rule.el.className = 'flex items-center gap-1 text-emerald-600 font-medium transition-colors';
                    if (icon) icon.className = 'bi bi-check-circle-fill text-[10px] text-emerald-600';
                } else {
                    rule.el.className = 'flex items-center gap-1 text-slate-500 transition-colors';
                    if (icon) icon.className = 'bi bi-circle text-[8px] text-slate-400';
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
                matchHint.className = 'text-[10px] font-semibold text-emerald-600 mt-1 flex items-center gap-1';
                matchHint.innerHTML = '<i class="bi bi-check-circle-fill text-xs text-emerald-600"></i> Passwords match';
            } else {
                matchHint.className = 'text-[10px] font-medium text-rose-500 mt-1 flex items-center gap-1';
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
