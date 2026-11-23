@extends('layouts.admin')

@section('title', 'Admin Settings')
@section('page_title', 'System & Account Settings')

@section('content')
<div class="space-y-8">
    <!-- Top Overview Card -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-slate-900 text-sky-400 font-black border border-slate-800 flex items-center justify-center text-lg tracking-wider shadow-sm flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center space-x-2.5">
                    <h2 class="text-lg font-extrabold text-slate-900">{{ $user->name }}</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 mr-1.5"></span>
                        System Administrator
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">{{ $user->email }} &bull; Account Active since {{ $user->created_at->format('M Y') }}</p>
            </div>
        </div>

        <div class="flex items-center space-x-3 text-xs text-slate-500">
            <span class="bg-slate-100 text-slate-700 font-semibold px-3 py-1.5 rounded-xl border border-slate-200/80 flex items-center space-x-1.5">
                <i class="bi bi-shield-check text-emerald-600"></i>
                <span>Role: {{ ucfirst($user->role) }}</span>
            </span>
        </div>
    </div>

    <!-- Forms Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- 1. Profile Information Form -->
        <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100">
                            <i class="bi bi-person-fill-gear text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Administrator Profile</h3>
                            <p class="text-[11px] text-slate-500">Update your primary admin name, email, and contact phone.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.settings.updateProfile') }}" method="POST" class="p-6 space-y-4">
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
                        <label for="phone" class="block text-xs font-bold text-slate-700 mb-1.5">Contact Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. 0917-123-4567"
                            class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                        @error('phone')
                            <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex items-center space-x-2 cursor-pointer">
                            <i class="bi bi-check2"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Security & Password Update -->
        <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-6 border-b border-slate-100 bg-slate-50/40">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                            <i class="bi bi-shield-lock-fill text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Security & Password</h3>
                            <p class="text-[11px] text-slate-500">Ensure your administrative account remains protected with a strong password.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.settings.updatePassword') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1.5">Current Password</label>
                        <div class="relative">
                            <input type="password" id="current_password" name="current_password" required
                                class="block w-full pr-10 px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                            <button type="button" onclick="togglePasswordVisibility('current_password', 'admin-curr-toggle')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                <i id="admin-curr-toggle" class="bi bi-eye text-sm"></i>
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
                            <button type="button" onclick="togglePasswordVisibility('password', 'admin-pass-toggle')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                <i id="admin-pass-toggle" class="bi bi-eye text-sm"></i>
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
                                <div id="admin-rule-length" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                    <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                    <span>8+ characters</span>
                                </div>
                                <div id="admin-rule-case" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                    <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                    <span>Upper & lowercase</span>
                                </div>
                                <div id="admin-rule-number" class="flex items-center gap-1.5 text-slate-500 transition-colors">
                                    <i class="bi bi-circle text-[9px] text-slate-400"></i>
                                    <span>At least 1 number</span>
                                </div>
                                <div id="admin-rule-symbol" class="flex items-center gap-1.5 text-slate-500 transition-colors">
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
                            <button type="button" onclick="togglePasswordVisibility('password_confirmation', 'admin-confirm-toggle')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
                                <i id="admin-confirm-toggle" class="bi bi-eye text-sm"></i>
                            </button>
                        </div>
                        <p id="admin-match-hint" class="hidden text-[11px] font-medium mt-1"></p>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex items-center space-x-2 cursor-pointer">
                            <i class="bi bi-shield-check"></i>
                            <span>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. Store & Operational Threshold Settings -->
    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/40">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                    <i class="bi bi-sliders text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Store & Operations Configuration</h3>
                    <p class="text-[11px] text-slate-500">Fine-tune system alert triggers, inventory thresholds, and store hub information.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.settings.updateStore') }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Store Name -->
                <div>
                    <label for="store_name" class="block text-xs font-bold text-slate-700 mb-1.5">Store Brand Name</label>
                    <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $settings['store_name']) }}" required
                        class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                    @error('store_name')
                        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Store Support Email -->
                <div>
                    <label for="store_email" class="block text-xs font-bold text-slate-700 mb-1.5">Support & Inquiry Email</label>
                    <input type="email" id="store_email" name="store_email" value="{{ old('store_email', $settings['store_email']) }}" required
                        class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                    @error('store_email')
                        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Support Phone -->
                <div>
                    <label for="store_phone" class="block text-xs font-bold text-slate-700 mb-1.5">Customer Support Hotline</label>
                    <input type="text" id="store_phone" name="store_phone" value="{{ old('store_phone', $settings['store_phone']) }}"
                        class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                    @error('store_phone')
                        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Operating Hours -->
                <div>
                    <label for="operating_hours" class="block text-xs font-bold text-slate-700 mb-1.5">Store Operating Hours</label>
                    <input type="text" id="operating_hours" name="operating_hours" value="{{ old('operating_hours', $settings['operating_hours'] ?? '8:00 AM - 6:00 PM (Mon - Sat)') }}"
                        class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                    @error('operating_hours')
                        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="store_address" class="block text-xs font-bold text-slate-700 mb-1.5">Main Hub / Station Address</label>
                    <input type="text" id="store_address" name="store_address" value="{{ old('store_address', $settings['store_address']) }}"
                        class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                    @error('store_address')
                        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Thresholds Sub-grid -->
            <div class="border-t border-slate-100 pt-6">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Inventory & Delivery Alert Thresholds</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Low stock threshold -->
                    <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                        <label for="low_stock_threshold" class="block text-xs font-bold text-slate-800 mb-1">Low Stock Warning Level</label>
                        <p class="text-[10px] text-slate-500 mb-2">Triggers low stock warnings when catalog units fall below this.</p>
                        <div class="relative">
                            <input type="number" id="low_stock_threshold" name="low_stock_threshold" value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" min="1" max="1000" required
                                class="block w-full pr-12 pl-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] font-bold text-slate-400">units</span>
                        </div>
                        @error('low_stock_threshold')
                            <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Expiration threshold -->
                    <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                        <label for="near_expiry_days" class="block text-xs font-bold text-slate-800 mb-1">Near-Expiration Warning Window</label>
                        <p class="text-[10px] text-slate-500 mb-2">Days before batch expiration to trigger administrative alerts.</p>
                        <div class="relative">
                            <input type="number" id="near_expiry_days" name="near_expiry_days" value="{{ old('near_expiry_days', $settings['near_expiry_days']) }}" min="1" max="90" required
                                class="block w-full pr-12 pl-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] font-bold text-slate-400">days</span>
                        </div>
                        @error('near_expiry_days')
                            <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Auto cancel threshold -->
                    <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                        <label for="order_auto_cancel_hours" class="block text-xs font-bold text-slate-800 mb-1">Unverified Order Auto-Cancel</label>
                        <p class="text-[10px] text-slate-500 mb-2">Hours before unconfirmed or unassigned orders expire.</p>
                        <div class="relative">
                            <input type="number" id="order_auto_cancel_hours" name="order_auto_cancel_hours" value="{{ old('order_auto_cancel_hours', $settings['order_auto_cancel_hours']) }}" min="1" max="168" required
                                class="block w-full pr-12 pl-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-[10px] font-bold text-slate-400">hours</span>
                        </div>
                        @error('order_auto_cancel_hours')
                            <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-sky-600/20 flex items-center space-x-2 cursor-pointer">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Save Store Preferences</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 3B. Reservation Cancellation Policy Settings -->
    @php
        $policyEnabledOld = old('cancellation_fee_enabled', $cancellationPolicy['enabled'] ? '1' : '0');
        $policyEnabled = filter_var($policyEnabledOld, FILTER_VALIDATE_BOOLEAN);
        $policyFeeValue = old('cancellation_fee_percentage', $cancellationPolicy['fee_percentage']);
        $policyFeeFloat = max(0, min(100, (float) $policyFeeValue));
        $policyRefundFloat = round(100 - $policyFeeFloat, 2);
        $formatPct = fn (float $n) => fmod($n, 1.0) === 0.0 ? (string) (int) $n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    @endphp
    <div id="cancellation-policy" class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 flex-shrink-0">
                    <i class="bi bi-calendar2-x-fill text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Reservation Cancellation Policy</h3>
                    <p class="text-[11px] text-slate-500">Configure cancellation fee deductions and refund rules for cancelled reservations. Future edits do not alter past cancelled records.</p>
                </div>
            </div>
            <span id="policyStatusBadge" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $policyEnabled ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                {{ $policyEnabled ? 'Fee Enabled' : 'Fee Disabled (100% Refund)' }}
            </span>
        </div>

        <form action="{{ route('admin.settings.updateReservationPolicy') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Enable/Disable Toggle + Percentage Fields -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Toggle -->
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80 flex flex-col justify-between">
                    <div>
                        <span class="block text-xs font-bold text-slate-800 mb-1">Cancellation Fee Status</span>
                        <p class="text-[10px] text-slate-500 mb-3">When disabled, customer-initiated cancellations receive a 100% full refund of the down payment.</p>
                    </div>
                    <label for="cancellation_fee_enabled" id="policyToggleCard" class="inline-flex items-center justify-between gap-3 px-3.5 py-2.5 bg-white border {{ $policyEnabled ? 'border-emerald-200' : 'border-slate-200' }} rounded-xl cursor-pointer select-none transition-colors">
                        <span id="policyToggleText" class="text-xs font-bold {{ $policyEnabled ? 'text-emerald-700' : 'text-slate-500' }}">
                            {{ $policyEnabled ? 'Cancellation Fee Enabled' : 'Cancellation Fee Disabled' }}
                        </span>
                        <span class="relative inline-flex items-center shrink-0">
                            <input type="hidden" name="cancellation_fee_enabled" value="0">
                            <input type="checkbox" id="cancellation_fee_enabled" name="cancellation_fee_enabled" value="1"
                                {{ $policyEnabled ? 'checked' : '' }}
                                class="sr-only">
                            <span id="policySwitchTrack"
                                style="width: 44px; height: 24px; background-color: {{ $policyEnabled ? '#059669' : '#cbd5e1' }};"
                                class="relative inline-flex items-center rounded-full transition-colors duration-200 shadow-inner">
                                <span id="policySwitchThumb"
                                    style="width: 18px; height: 18px; transform: translateX({{ $policyEnabled ? '23px' : '3px' }});"
                                    class="inline-block rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                            </span>
                        </span>
                    </label>
                </div>

                <!-- Cancellation Fee Percentage -->
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                    <label for="cancellation_fee_percentage" class="block text-xs font-bold text-slate-800 mb-1">Cancellation Fee Percentage</label>
                    <p class="text-[10px] text-slate-500 mb-2">Percentage deducted from the down payment on customer-initiated cancellations (0–100).</p>
                    <div class="relative">
                        <input type="number" id="cancellation_fee_percentage" name="cancellation_fee_percentage"
                            value="{{ $formatPct($policyFeeFloat) }}"
                            min="0" max="100" step="0.01" required
                            class="block w-full pr-10 pl-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-xs font-bold text-slate-400">%</span>
                    </div>
                    @error('cancellation_fee_percentage')
                        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Auto-Calculated Refund Percentage -->
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                    <label for="refund_percentage_display" class="block text-xs font-bold text-slate-800 mb-1">Refund Percentage (Auto-calculated)</label>
                    <p class="text-[10px] text-slate-500 mb-2">Automatically computed as 100% minus the cancellation fee percentage.</p>
                    <div class="relative">
                        <input type="text" id="refund_percentage_display" readonly
                            value="{{ $formatPct($policyRefundFloat) }}%"
                            class="block w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-xs font-black text-emerald-700 cursor-not-allowed">
                    </div>
                </div>
            </div>

            <!-- Live Policy Summary Strip -->
            <div id="policyLiveSummaryBox" class="rounded-xl border border-sky-200/80 bg-sky-50/60 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                <div class="flex items-center gap-2 font-bold text-slate-800">
                    <i class="bi bi-info-circle-fill text-sky-600"></i>
                    <span id="policyLiveSummaryText">
                        @if($policyEnabled)
                            Cancellation Fee: {{ $formatPct($policyFeeFloat) }}% | Refund Percentage: {{ $formatPct($policyRefundFloat) }}%
                        @else
                            Cancellation Fee: Disabled (0% Applied) | Refund Percentage: 100%
                        @endif
                    </span>
                </div>
                <span class="text-[11px] text-slate-600">
                    Business-initiated cancellations always issue a 100% full refund (₱0.00 fee).
                </span>
            </div>

            <!-- Policy Description -->
            <div>
                <label for="cancellation_policy_description" class="block text-xs font-bold text-slate-700 mb-1.5">Cancellation Policy Description</label>
                <textarea id="cancellation_policy_description" name="cancellation_policy_description" rows="3" maxlength="1000"
                    placeholder="Describe the reservation cancellation fee and refund guidelines shown to staff..."
                    class="block w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 shadow-2xs">{{ old('cancellation_policy_description', $cancellationPolicy['description']) }}</textarea>
                @error('cancellation_policy_description')
                    <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Real-Time Refund Computation Preview -->
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 sm:p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Refund Computation Preview</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Test any down payment amount to preview cancellation fee and refund calculations before saving.</p>
                    </div>
                    <div class="w-full sm:w-64 shrink-0">
                        <label for="preview_down_payment" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Down Payment Amount</label>
                        <div class="flex items-center bg-white border border-slate-300 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-sky-500/20 focus-within:border-sky-500 shadow-2xs">
                            <span class="px-3 py-2 bg-slate-100 border-r border-slate-200 text-xs font-black text-slate-700 select-none">₱</span>
                            <input type="number" id="preview_down_payment" min="0" step="0.01" placeholder="Enter down payment amount"
                                class="block w-full px-3 py-2 bg-white border-0 text-sm font-bold text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-0">
                        </div>
                    </div>
                </div>

                <div id="previewEmptyHint" class="text-xs text-slate-500 bg-white border border-slate-200/70 rounded-xl px-4 py-3">
                    Enter a down payment amount above to preview customer-initiated and business-initiated refund computations.
                </div>

                <div id="previewResultGrid" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">Customer-Initiated Cancellation</span>
                            <span id="previewCustomerRateBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200"></span>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                            <span class="text-slate-500">Cancellation Fee</span>
                            <span id="previewCustomerFee" class="font-bold text-rose-600">₱0.00</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Refund Amount (Pending Handover)</span>
                            <span id="previewCustomerRefund" class="font-black text-emerald-700">₱0.00</span>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">Business-Initiated Cancellation</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Full Refund Override</span>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                            <span class="text-slate-500">Cancellation Fee (0%)</span>
                            <span class="font-bold text-slate-700">₱0.00</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Refund Amount (100%)</span>
                            <span id="previewBusinessRefund" class="font-black text-emerald-700">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex items-center space-x-2 cursor-pointer">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>

    <!-- 4. Payment Gateway (Xendit) Status Card -->
    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden lg:col-span-2">
        <div class="p-6 border-b border-slate-100 bg-slate-50/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100 flex-shrink-0">
                    <i class="bi bi-credit-card-2-front text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Payment Gateway Configuration (Xendit)</h3>
                    <p class="text-[11px] text-slate-500">Enable seamless customer checkout via GCash, Maya, Debit/Credit Card, and QR Ph.</p>
                </div>
            </div>
            <div>
                @if($xenditConfigured)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                        Gateway Active
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                        Credentials Pending in .env
                    </span>
                @endif
            </div>
        </div>

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Supported Channels</span>
                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                        <span class="px-2 py-0.5 rounded bg-white text-[10px] font-bold text-sky-600 border border-slate-200">GCash</span>
                        <span class="px-2 py-0.5 rounded bg-white text-[10px] font-bold text-emerald-600 border border-slate-200">Maya</span>
                        <span class="px-2 py-0.5 rounded bg-white text-[10px] font-bold text-slate-700 border border-slate-200">Visa / Mastercard</span>
                        <span class="px-2 py-0.5 rounded bg-white text-[10px] font-bold text-slate-700 border border-slate-200">QR Ph</span>
                        <span class="px-2 py-0.5 rounded bg-white text-[10px] font-bold text-slate-700 border border-slate-200">GrabPay</span>
                    </div>
                </div>

                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80 md:col-span-2">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Xendit Webhook Endpoint</span>
                    <p class="text-[10px] text-slate-500 mb-2">Configure this callback URL in your Xendit Dashboard &rarr; Settings &rarr; Webhooks (Invoices):</p>
                    <div class="flex items-center space-x-2">
                        <input type="text" readonly value="{{ url('/api/webhooks/xendit') }}" id="xenditWebhookInput"
                            class="block w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-800 select-all focus:outline-none">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('xenditWebhookInput').value); Swal.fire({ icon: 'success', title: 'Copied!', text: 'Webhook URL copied to clipboard.', timer: 1500, showConfirmButton: false });"
                            class="px-3 py-1.5 bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 rounded-lg text-xs font-bold transition-all flex items-center space-x-1 cursor-pointer flex-shrink-0">
                            <i class="bi bi-clipboard"></i>
                            <span>Copy</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-xl border border-slate-200/80 bg-slate-50/40 text-xs text-slate-600 space-y-2">
                <span class="font-bold text-slate-800 block">Configuration Instructions:</span>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    To activate live or test payments, obtain your Secret Key and Webhook Verification Token from your <a href="https://dashboard.xendit.co" target="_blank" class="text-sky-600 font-bold hover:underline">Xendit Dashboard</a> and set them in your project's <code class="font-mono bg-slate-200/60 px-1.5 py-0.5 rounded text-slate-800">.env</code> file:
                </p>
                <pre class="bg-slate-900 text-sky-300 p-3 rounded-xl font-mono text-[11px] overflow-x-auto leading-relaxed">XENDIT_SECRET_KEY=xnd_development_...
XENDIT_PUBLIC_KEY=xnd_public_development_...
XENDIT_WEBHOOK_TOKEN=your_webhook_verification_token</pre>
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
            icon.className = 'bi bi-eye-slash text-sm text-sky-600';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye text-sm text-slate-400';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirmation');
        const matchHint = document.getElementById('admin-match-hint');

        const rules = {
            length: { el: document.getElementById('admin-rule-length'), test: (val) => val.length >= 8 },
            case: { el: document.getElementById('admin-rule-case'), test: (val) => /[a-z]/.test(val) && /[A-Z]/.test(val) },
            number: { el: document.getElementById('admin-rule-number'), test: (val) => /[0-9]/.test(val) },
            symbol: { el: document.getElementById('admin-rule-symbol'), test: (val) => /[^A-Za-z0-9]/.test(val) },
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

        // Reservation Cancellation Policy Live Preview
        const feeToggle = document.getElementById('cancellation_fee_enabled');
        const feeInput = document.getElementById('cancellation_fee_percentage');
        const refundDisplay = document.getElementById('refund_percentage_display');
        const toggleCard = document.getElementById('policyToggleCard');
        const toggleText = document.getElementById('policyToggleText');
        const switchTrack = document.getElementById('policySwitchTrack');
        const switchThumb = document.getElementById('policySwitchThumb');
        const statusBadge = document.getElementById('policyStatusBadge');
        const summaryText = document.getElementById('policyLiveSummaryText');
        const previewDownPayment = document.getElementById('preview_down_payment');
        const previewEmptyHint = document.getElementById('previewEmptyHint');
        const previewResultGrid = document.getElementById('previewResultGrid');
        const previewCustomerRateBadge = document.getElementById('previewCustomerRateBadge');
        const previewCustomerFee = document.getElementById('previewCustomerFee');
        const previewCustomerRefund = document.getElementById('previewCustomerRefund');
        const previewBusinessRefund = document.getElementById('previewBusinessRefund');

        function formatPctLabel(num) {
            const rounded = Math.round(num * 100) / 100;
            return Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(2).replace(/\.?0+$/, '');
        }

        function formatCurrency(num) {
            return '₱' + Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function updateCancellationPolicyPreview() {
            if (!feeToggle || !feeInput) return;

            const enabled = feeToggle.checked;
            let rawFee = parseFloat(feeInput.value);
            if (isNaN(rawFee)) rawFee = 0;
            const feePct = Math.max(0, Math.min(100, Math.round(rawFee * 100) / 100));
            const refundPct = Math.max(0, Math.round((100 - feePct) * 100) / 100);

            if (refundDisplay) {
                refundDisplay.value = `${formatPctLabel(refundPct)}%`;
            }

            if (toggleText) {
                toggleText.textContent = enabled ? 'Cancellation Fee Enabled' : 'Cancellation Fee Disabled';
                toggleText.className = enabled ? 'text-xs font-bold text-emerald-700' : 'text-xs font-bold text-slate-500';
            }

            if (toggleCard) {
                toggleCard.className = enabled
                    ? 'inline-flex items-center justify-between gap-3 px-3.5 py-2.5 bg-white border border-emerald-200 rounded-xl cursor-pointer select-none transition-colors'
                    : 'inline-flex items-center justify-between gap-3 px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl cursor-pointer select-none transition-colors';
            }

            if (switchTrack) {
                switchTrack.style.backgroundColor = enabled ? '#059669' : '#cbd5e1';
            }

            if (switchThumb) {
                switchThumb.style.transform = enabled ? 'translateX(23px)' : 'translateX(3px)';
            }

            if (statusBadge) {
                statusBadge.textContent = enabled ? 'Fee Enabled' : 'Fee Disabled (100% Refund)';
                statusBadge.className = enabled
                    ? 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border bg-emerald-50 text-emerald-700 border-emerald-200'
                    : 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border bg-slate-100 text-slate-600 border-slate-200';
            }

            if (summaryText) {
                summaryText.textContent = enabled
                    ? `Cancellation Fee: ${formatPctLabel(feePct)}% | Refund Percentage: ${formatPctLabel(refundPct)}%`
                    : 'Cancellation Fee: Disabled (0% Applied) | Refund Percentage: 100%';
            }

            if (previewDownPayment && previewEmptyHint && previewResultGrid) {
                const rawDp = previewDownPayment.value;
                const dp = parseFloat(rawDp);

                if (rawDp === '' || isNaN(dp) || dp <= 0) {
                    previewEmptyHint.classList.remove('hidden');
                    previewResultGrid.classList.add('hidden');
                    return;
                }

                previewEmptyHint.classList.add('hidden');
                previewResultGrid.classList.remove('hidden');

                const appliedFeePct = enabled ? feePct : 0;
                const feeAmount = Math.round(dp * (appliedFeePct / 100) * 100) / 100;
                const customerRefund = Math.max(0, Math.round((dp - feeAmount) * 100) / 100);

                previewCustomerRateBadge.textContent = enabled
                    ? `${formatPctLabel(appliedFeePct)}% Fee`
                    : 'Fee Disabled (0%)';
                previewCustomerFee.textContent = formatCurrency(feeAmount);
                previewCustomerRefund.textContent = formatCurrency(customerRefund);
                previewBusinessRefund.textContent = formatCurrency(dp);
            }
        }

        if (feeToggle) feeToggle.addEventListener('change', updateCancellationPolicyPreview);
        if (feeInput) feeInput.addEventListener('input', updateCancellationPolicyPreview);
        if (previewDownPayment) previewDownPayment.addEventListener('input', updateCancellationPolicyPreview);
        updateCancellationPolicyPreview();
    });
</script>
@endsection
