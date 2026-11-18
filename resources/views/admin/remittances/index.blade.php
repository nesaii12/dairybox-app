@extends('layouts.admin')

@section('page_title', 'Rider Cash Remittance')

@section('content')
<div class="space-y-8 pb-12">
    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200">
                    <i class="bi bi-cash-stack mr-1"></i> Cashier & Operations
                </span>
                <span class="text-xs text-slate-400 font-medium">• Daily Reconciliation</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Rider Cash Remittance</h1>
            <p class="text-xs text-slate-500 mt-0.5">Reconcile cash-on-delivery (COD) handovers from delivery riders upon fulfillment run completion.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="#remittanceFormSection" class="inline-flex items-center space-x-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition shadow-sm shadow-sky-600/30">
                <i class="bi bi-plus-circle-fill"></i>
                <span>New Remittance Handover</span>
            </a>
            <a href="#cashLedgerSection" class="inline-flex items-center space-x-1.5 px-3.5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition shadow-2xs">
                <i class="bi bi-journal-text text-sky-600"></i>
                <span>Cash Ledger</span>
            </a>
        </div>
    </div>

    <!-- Top KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Remitted Today -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Remitted Today</span>
                <span class="text-2xl font-black text-slate-900 block mt-1">₱{{ number_format($totalRemittedToday, 2) }}</span>
                <span class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1 mt-0.5">
                    <i class="bi bi-check-circle-fill text-[9px]"></i> Logged into Cash Ledger
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-2xs">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>

        <!-- Pending Verification -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Pending Verification</span>
                <span class="text-2xl font-black text-slate-900 block mt-1">{{ $pendingVerificationsCount }}</span>
                <span class="text-[10px] text-amber-600 font-semibold flex items-center gap-1 mt-0.5">
                    <i class="bi bi-clock-history text-[9px]"></i> Requires Manager Audit
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-2xs">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>

        <!-- Today Net Variance -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Today's Net Variance</span>
                <span class="text-2xl font-black {{ $todayNetVariance < 0 ? 'text-rose-600' : ($todayNetVariance > 0 ? 'text-sky-600' : 'text-emerald-600') }} block mt-1">
                    {{ $todayNetVariance > 0 ? '+' : '' }}₱{{ number_format($todayNetVariance, 2) }}
                </span>
                <span class="text-[10px] text-slate-500 font-semibold flex items-center gap-1 mt-0.5">
                    @if($todayNetVariance == 0)
                        <i class="bi bi-check2 text-emerald-600"></i> Exactly Balanced
                    @elseif($todayNetVariance < 0)
                        <i class="bi bi-arrow-down-right text-rose-600"></i> Net Shortage
                    @else
                        <i class="bi bi-arrow-up-right text-sky-600"></i> Net Overage
                    @endif
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl {{ $todayNetVariance < 0 ? 'bg-rose-50 text-rose-600' : ($todayNetVariance > 0 ? 'bg-sky-50 text-sky-600' : 'bg-emerald-50 text-emerald-600') }} flex items-center justify-center text-xl shadow-2xs">
                <i class="bi bi-calculator"></i>
            </div>
        </div>

        <!-- Unremitted COD in Fleet -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
            <div>
                <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Pending Fleet Cash</span>
                <span class="text-2xl font-black text-slate-900 block mt-1">₱{{ number_format($totalUnremittedCash, 2) }}</span>
                <span class="text-[10px] text-sky-600 font-semibold flex items-center gap-1 mt-0.5">
                    <i class="bi bi-bicycle text-[9px]"></i> In riders' possession
                </span>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-2xs">
                <i class="bi bi-cash-coin"></i>
            </div>
        </div>
    </div>

    <!-- Remittance Input Form Section -->
    <div id="remittanceFormSection" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 border-b border-slate-800 flex items-center justify-between text-white">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-sky-500/20 text-sky-400 flex items-center justify-center text-lg border border-sky-500/30">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold tracking-tight">Record Rider Cash Remittance</h2>
                    <p class="text-[11px] text-slate-400">Select an active rider, attach fulfilled COD orders, and reconcile physical cash received.</p>
                </div>
            </div>
            <span class="text-[10px] uppercase font-bold tracking-wider px-2.5 py-1 bg-sky-500/10 border border-sky-400/20 text-sky-300 rounded-lg">
                Admin Form
            </span>
        </div>

        <form id="remittanceForm" action="{{ route('admin.remittances.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            <input type="hidden" name="action_type" id="action_type" value="confirm">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left: Rider Selection & Order/Batch Checklist (7 cols) -->
                <div class="lg:col-span-7 space-y-5">
                    <!-- Step 1: Select Rider -->
                    <div>
                        <label for="rider_select" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>1. Select Delivery Driver / Rider <span class="text-rose-500">*</span></span>
                            <span id="rider_order_badge" class="hidden text-[10px] font-bold px-2 py-0.5 bg-amber-50 text-amber-700 rounded-md border border-amber-200">
                                0 Orders Pending
                            </span>
                        </label>
                        <select name="rider_id" id="rider_select" required
                            class="block w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition cursor-pointer">
                            <option value="">-- Choose an active rider --</option>
                            @foreach($activeRiders as $rider)
                                <option value="{{ $rider->id }}" 
                                    data-orders-count="{{ $rider->pending_orders_count }}" 
                                    data-pending-total="{{ $rider->pending_cod_total }}"
                                    data-phone="{{ $rider->phone }}"
                                    {{ (string) old('rider_id', $selectedRiderId) === (string) $rider->id ? 'selected' : '' }}>
                                    {{ $rider->name }} ({{ $rider->pending_orders_count }} orders pending • ₱{{ number_format($rider->pending_cod_total, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Selecting a rider loads all completed COD deliveries waiting for cash handover.</p>
                    </div>

                    <!-- Step 2: Order / Batch Selector -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                2. Order / Batch Selector <span class="text-rose-500">*</span>
                            </label>
                            <div id="batch_controls" class="hidden flex items-center space-x-2">
                                <button type="button" id="selectAllOrdersBtn" class="text-[11px] font-bold text-sky-600 hover:text-sky-700 bg-sky-50 hover:bg-sky-100 px-2.5 py-1 rounded-lg border border-sky-100 transition cursor-pointer">
                                    <i class="bi bi-check-all mr-0.5"></i> Select All
                                </button>
                                <button type="button" id="deselectAllOrdersBtn" class="text-[11px] font-bold text-slate-500 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                    Deselect
                                </button>
                            </div>
                        </div>

                        <!-- Orders Container -->
                        <div id="ordersLoadingSpinner" class="hidden py-10 text-center bg-slate-50 rounded-xl border border-slate-200">
                            <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-sky-600 border-t-transparent"></div>
                            <p class="text-xs text-slate-500 mt-2 font-medium">Fetching rider's delivered runs...</p>
                        </div>

                        <div id="noRiderSelectedState" class="py-12 text-center bg-slate-50/70 rounded-xl border-2 border-dashed border-slate-200">
                            <i class="bi bi-bicycle text-3xl text-slate-300"></i>
                            <p class="text-xs font-bold text-slate-700 mt-2">No Rider Selected</p>
                            <p class="text-[11px] text-slate-400">Choose a rider above to inspect their delivered orders.</p>
                        </div>

                        <div id="emptyOrdersState" class="hidden py-12 text-center bg-slate-50 rounded-xl border border-slate-200">
                            <i class="bi bi-check-circle-fill text-3xl text-emerald-400"></i>
                            <p class="text-xs font-bold text-slate-800 mt-2">All Caught Up!</p>
                            <p class="text-[11px] text-slate-500">This rider currently has no unremitted completed COD deliveries.</p>
                        </div>

                        <!-- Orders Checklist List -->
                        <div id="ordersListWrapper" class="hidden space-y-2 max-h-72 overflow-y-auto pr-1">
                            <!-- Injected dynamically by JS -->
                        </div>
                    </div>
                </div>

                <!-- Right: Financial Handover & Reconciliation Panel (5 cols) -->
                <div class="lg:col-span-5 bg-slate-50/80 rounded-2xl p-5 border border-slate-200 space-y-4 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center space-x-1.5">
                                <i class="bi bi-calculator text-sky-600"></i>
                                <span>3. Reconciliation Math</span>
                            </h3>
                            <span id="selectedOrdersCountBadge" class="text-[11px] font-bold text-slate-600 bg-white px-2 py-0.5 rounded-md border border-slate-200">
                                0 Orders Selected
                            </span>
                        </div>

                        <!-- Expected Cash Auto-Calculated -->
                        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cash Expected (COD Sum)</span>
                                <span class="text-[10px] font-bold text-sky-600 bg-sky-50 px-1.5 py-0.5 rounded">Auto Calculated</span>
                            </div>
                            <div class="text-2xl font-black text-slate-900 mt-1 flex items-baseline">
                                <span>₱</span>
                                <span id="cashExpectedDisplay">0.00</span>
                            </div>
                            <input type="hidden" name="expected_amount" id="expected_amount_input" value="0.00">
                        </div>

                        <!-- Actual Cash Handed Over -->
                        <div>
                            <label for="actual_amount" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Cash Handed Over (Actual Remittance) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-sm">₱</span>
                                <input type="number" step="0.01" min="0" name="actual_amount" id="actual_amount" required placeholder="0.00"
                                    class="block w-full pl-8 pr-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-900 font-black text-lg focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                            </div>
                            <!-- Quick helper buttons -->
                            <div class="flex items-center gap-1.5 mt-2">
                                <button type="button" id="matchExactBtn" class="text-[10px] font-bold px-2 py-1 bg-white border border-slate-200 hover:bg-slate-100 rounded-md text-slate-700 transition cursor-pointer">
                                    Match Expected
                                </button>
                                <button type="button" onclick="addAmount(50)" class="text-[10px] font-bold px-2 py-1 bg-white border border-slate-200 hover:bg-slate-100 rounded-md text-slate-700 transition cursor-pointer">+₱50</button>
                                <button type="button" onclick="addAmount(100)" class="text-[10px] font-bold px-2 py-1 bg-white border border-slate-200 hover:bg-slate-100 rounded-md text-slate-700 transition cursor-pointer">+₱100</button>
                                <button type="button" onclick="addAmount(500)" class="text-[10px] font-bold px-2 py-1 bg-white border border-slate-200 hover:bg-slate-100 rounded-md text-slate-700 transition cursor-pointer">+₱500</button>
                                <button type="button" id="clearAmountBtn" class="text-[10px] font-bold px-2 py-1 bg-white border border-slate-200 hover:bg-rose-50 hover:text-rose-600 rounded-md text-slate-400 transition cursor-pointer ml-auto">
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Variance / Discrepancy Field -->
                        <div id="varianceBox" class="p-3.5 rounded-xl border border-slate-200 bg-white transition-all">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Variance / Discrepancy</span>
                                <span id="varianceBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                                    Balanced
                                </span>
                            </div>
                            <div id="varianceValueText" class="text-xl font-black text-slate-700 mt-1">
                                ₱0.00
                            </div>
                            <p id="varianceHelpText" class="text-[10px] text-slate-400 mt-1">
                                Actual physical cash remitted minus expected COD total.
                            </p>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label for="payment_method" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Payment Method <span class="text-rose-500">*</span>
                            </label>
                            <select name="payment_method" id="payment_method" required
                                class="block w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition cursor-pointer">
                                <option value="Cash">Physical Cash</option>
                                <option value="GCash Transfer">GCash Transfer</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Other">Other Mode</option>
                            </select>
                        </div>

                        <!-- Notes & Remarks -->
                        <div>
                            <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Admin Notes & Handover Remarks
                            </label>
                            <textarea name="notes" id="notes" rows="2" placeholder="e.g., Shortage justified due to change shortage; rider will settle balance tomorrow..."
                                class="block w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-slate-200 space-y-2">
                        <button type="button" id="confirmRemittanceBtn" class="w-full flex items-center justify-center space-x-2 py-3 px-4 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-600/30 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Confirm & Record Remittance</span>
                        </button>
                        <button type="button" id="pendingVerificationBtn" class="w-full flex items-center justify-center space-x-2 py-2.5 px-4 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="bi bi-hourglass-split text-amber-500"></i>
                            <span>Save as Pending Verification</span>
                        </button>
                        <p class="text-[10px] text-center text-slate-400">
                            "Confirm" automatically marks attached orders as <strong class="text-slate-600 font-bold">Paid & Remitted</strong> and writes entry to Daily Cash Ledger.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Remittance History Table Section -->
    <div id="remittanceHistorySection" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Section Header with Filters & Export -->
        <div class="p-6 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <i class="bi bi-clock-history text-sky-600"></i>
                        <span>Remittance History &amp; Audit Log</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Comprehensive audit trail of all physical and electronic cash handovers recorded across the fleet.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-100">
                        <i class="bi bi-calendar3 mr-1.5 text-sky-500"></i> {{ $dateRangeLabel ?? 'Monthly Logs (This Month)' }}
                    </span>
                    <span class="text-xs text-slate-400 font-mono">{{ $remittances->total() }} {{ Str::plural('record', $remittances->total()) }}</span>
                </div>
            </div>

            <!-- Filter Controls Form -->
            <form action="{{ route('admin.remittances.index') }}" method="GET" id="remittanceFilterForm" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <!-- 1. WINDOW Timeframe Filter Dropdown -->
                    <div>
                        <label for="remit_filter" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">WINDOW</label>
                        <select name="filter_type" id="remit_filter" onchange="toggleRemitDateFields(); this.form.submit();"
                            class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 cursor-pointer">
                            <option value="daily" {{ request('filter_type') === 'daily' ? 'selected' : '' }}>Daily Logs (Today)</option>
                            <option value="weekly" {{ request('filter_type') === 'weekly' ? 'selected' : '' }}>Weekly Logs (This Week)</option>
                            <option value="monthly" {{ request('filter_type', 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly Logs (This Month)</option>
                            <option value="annual" {{ request('filter_type') === 'annual' ? 'selected' : '' }}>Annual Logs (This Year)</option>
                            <option value="all" {{ request('filter_type') === 'all' ? 'selected' : '' }}>All Logs</option>
                            <option value="custom" {{ request('filter_type') === 'custom' ? 'selected' : '' }}>Custom Date Range...</option>
                        </select>
                    </div>

                    <!-- 2. Delivery Rider Filter -->
                    <div>
                        <label for="filter_rider" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Filter Rider</label>
                        <select name="rider_id" id="filter_rider" onchange="this.form.submit()"
                            class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 cursor-pointer">
                            <option value="">All Delivery Riders</option>
                            @foreach($activeRiders as $r)
                                <option value="{{ $r->id }}" {{ (string) request('rider_id') === (string) $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Status Filter -->
                    <div>
                        <label for="filter_status" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" id="filter_status" onchange="this.form.submit()"
                            class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 cursor-pointer">
                            <option value="all">All Statuses</option>
                            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="pending_verification" {{ request('status') === 'pending_verification' ? 'selected' : '' }}>Pending Verification</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected / Void</option>
                        </select>
                    </div>

                    <!-- 4. Search Filter -->
                    <div>
                        <label for="history_search" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Search</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i class="bi bi-search text-xs"></i>
                            </span>
                            <input type="text" name="search" id="history_search" value="{{ request('search') }}" placeholder="Ref#, notes..."
                                class="block w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                        </div>
                    </div>
                </div>

                <!-- Custom Date Range Picker Fields (visible only when custom is chosen) -->
                <div id="remit_custom_dates" class="grid grid-cols-1 sm:grid-cols-2 gap-3" style="{{ request('filter_type') === 'custom' ? '' : 'display: none;' }}">
                    <div>
                        <label for="remit_start" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Start Date</label>
                        <input type="date" name="start_date" id="remit_start" value="{{ request('start_date') }}"
                            class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                    </div>
                    <div>
                        <label for="remit_end" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">End Date</label>
                        <input type="date" name="end_date" id="remit_end" value="{{ request('end_date') }}"
                            class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                    </div>
                </div>

                <!-- Export Buttons and Form Action Controls -->
                <div class="pt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-slate-100">
                    <!-- Export Remittance Excel and PDF Buttons -->
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" onclick="submitRemittanceExport('excel')"
                            class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200 transition-colors shadow-2xs cursor-pointer">
                            <i class="bi bi-file-earmark-excel-fill text-sm"></i>
                            <span>Export Remittance Excel</span>
                        </button>
                        <button type="button" onclick="submitRemittanceExport('pdf')"
                            class="inline-flex items-center space-x-1.5 py-2 px-3.5 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100/80 border border-rose-200 transition-colors shadow-2xs cursor-pointer">
                            <i class="bi bi-file-earmark-pdf-fill text-sm"></i>
                            <span>Export Remittance PDF</span>
                        </button>
                    </div>

                    <!-- Filter & Reset Buttons -->
                    <div class="flex items-center gap-2">
                        <button type="submit" class="py-2 px-4 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold transition shadow-2xs cursor-pointer">
                            <i class="bi bi-funnel mr-1"></i> Apply Filter
                        </button>
                        @if(request()->hasAny(['search', 'rider_id', 'status', 'date', 'filter_type', 'start_date', 'end_date']))
                            <a href="{{ route('admin.remittances.index') }}" class="py-2 px-3.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition shadow-2xs">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- History Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-10 px-3 py-3.5 text-center text-xs font-bold text-slate-400 uppercase tracking-wider"></th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Reference &amp; Date</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Delivery Rider</th>
                        <th class="px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Orders Attached</th>
                        <th class="px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Total Sales Value</th>
                        <th class="px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Expected COD</th>
                        <th class="px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Actual Remitted</th>
                        <th class="px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Variance</th>
                        <th class="px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Method</th>
                        <th class="px-4 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-xs">
                    @forelse($remittances as $remittance)
                    @php
                        $totalSalesValue = (float) $remittance->orders->sum('total_amount');
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition-colors group">
                        <!-- Expand Toggle Button -->
                        <td class="w-10 px-3 py-4 text-center whitespace-nowrap">
                            <button type="button" onclick="toggleBreakdownRow({{ $remittance->id }})" id="btn-toggle-{{ $remittance->id }}"
                                class="w-7 h-7 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-sky-600 hover:bg-sky-50 transition cursor-pointer"
                                title="Toggle itemized product receipt" aria-expanded="false">
                                <i class="bi bi-chevron-down text-xs transition-transform duration-200" id="chevron-{{ $remittance->id }}"></i>
                            </button>
                        </td>

                        <!-- Ref & Date -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <span class="font-black text-slate-900 block font-mono">{{ $remittance->reference_no }}</span>
                            <span class="text-[11px] text-slate-400 block">{{ $remittance->created_at->format('M d, Y • h:i A') }}</span>
                            <span class="text-[10px] text-slate-500 block">By: {{ $remittance->admin?->name ?? 'Admin' }}</span>
                        </td>

                        <!-- Rider -->
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-700 font-bold flex items-center justify-center text-xs">
                                    {{ strtoupper(substr($remittance->rider?->name ?? 'R', 0, 2)) }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 block">{{ $remittance->rider?->name ?? 'Unknown Rider' }}</span>
                                    <span class="text-[11px] text-slate-400 block">{{ $remittance->rider?->phone ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Orders Count -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            <button type="button" onclick="toggleBreakdownRow({{ $remittance->id }})"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-slate-100 hover:bg-sky-100 text-slate-700 hover:text-sky-700 transition cursor-pointer">
                                <i class="bi bi-box-seam mr-1 text-[10px]"></i> {{ $remittance->orders_count }} {{ Str::plural('order', $remittance->orders_count) }}
                            </button>
                        </td>

                        <!-- Total Order Sales Value -->
                        <td class="px-4 py-4 whitespace-nowrap font-black text-slate-900">
                            ₱{{ number_format($totalSalesValue, 2) }}
                        </td>

                        <!-- Expected COD -->
                        <td class="px-4 py-4 whitespace-nowrap font-bold text-slate-600">
                            ₱{{ number_format($remittance->expected_amount, 2) }}
                        </td>

                        <!-- Actual Remitted -->
                        <td class="px-4 py-4 whitespace-nowrap font-black text-slate-900">
                            ₱{{ number_format($remittance->actual_amount, 2) }}
                        </td>

                        <!-- Variance -->
                        <td class="px-4 py-4 whitespace-nowrap font-bold">
                            @if($remittance->variance == 0)
                                <span class="text-emerald-600 inline-flex items-center gap-1">
                                    <i class="bi bi-check-circle-fill text-[10px]"></i> ₱0.00
                                </span>
                            @elseif($remittance->variance < 0)
                                <span class="text-rose-600 inline-flex items-center gap-1 bg-rose-50 px-2 py-0.5 rounded-md">
                                    <i class="bi bi-arrow-down-right text-[10px]"></i> -₱{{ number_format(abs($remittance->variance), 2) }}
                                </span>
                            @else
                                <span class="text-sky-600 inline-flex items-center gap-1 bg-sky-50 px-2 py-0.5 rounded-md">
                                    <i class="bi bi-arrow-up-right text-[10px]"></i> +₱{{ number_format($remittance->variance, 2) }}
                                </span>
                            @endif
                        </td>

                        <!-- Method -->
                        <td class="px-4 py-4 whitespace-nowrap text-slate-700 font-medium">
                            <span class="inline-flex items-center gap-1">
                                @if($remittance->payment_method === 'Cash')
                                    <i class="bi bi-cash text-emerald-500"></i>
                                @elseif(str_contains($remittance->payment_method, 'GCash'))
                                    <i class="bi bi-phone text-blue-500"></i>
                                @else
                                    <i class="bi bi-bank text-indigo-500"></i>
                                @endif
                                <span>{{ $remittance->payment_method }}</span>
                            </span>
                        </td>

                        <!-- Status Badge -->
                        <td class="px-4 py-4 whitespace-nowrap">
                            @if($remittance->status === 'confirmed')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="bi bi-patch-check-fill mr-1 text-[10px]"></i> Confirmed
                                </span>
                            @elseif($remittance->status === 'pending_verification')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                    <i class="bi bi-hourglass-split mr-1 text-[10px]"></i> Pending Audit
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <i class="bi bi-x-circle-fill mr-1 text-[10px]"></i> Rejected
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="px-5 py-4 whitespace-nowrap text-center space-x-1.5">
                            <button type="button" onclick="toggleBreakdownRow({{ $remittance->id }})" class="p-1.5 text-slate-600 hover:text-sky-700 bg-slate-100 hover:bg-sky-50 rounded-lg transition" title="Inspect Products Breakdown">
                                <i class="bi bi-receipt-cutoff text-sm"></i>
                            </button>

                            <button type="button" onclick="openRemittanceModal({{ $remittance->id }})" class="p-1.5 text-sky-600 hover:text-sky-800 bg-sky-50 hover:bg-sky-100 rounded-lg transition" title="Full Audit View">
                                <i class="bi bi-eye text-sm"></i>
                            </button>

                            @if($remittance->status === 'pending_verification')
                                <form action="{{ route('admin.remittances.verify', $remittance->id) }}" method="POST" class="inline" onsubmit="return confirmAction(event, 'Confirm this remittance now? Attached orders will be marked Paid & Remitted.')">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition" title="Confirm Remittance">
                                        <i class="bi bi-check-lg text-sm"></i>
                                    </button>
                                </form>

                                <form action="{{ route('admin.remittances.reject', $remittance->id) }}" method="POST" class="inline" onsubmit="return confirmAction(event, 'Reject / void this remittance? Attached orders will be returned to the pending remittance pool.')">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 rounded-lg transition" title="Reject / Void">
                                        <i class="bi bi-x-lg text-sm"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>

                    <!-- Expandable Itemized Products Breakdown Drawer -->
                    <tr id="breakdown-row-{{ $remittance->id }}" class="hidden bg-slate-50/60 border-y border-slate-200/90 transition-all">
                        <td colspan="11" class="p-4 sm:p-6">
                            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-4">
                                <!-- Top Reconciliation Summary Strip -->
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 pb-4 border-b border-slate-100">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-base shrink-0">
                                            <i class="bi bi-receipt-cutoff"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-slate-900 tracking-tight flex items-center gap-2">
                                                <span>Itemized Receipt &amp; Product Breakdown</span>
                                                <span class="font-mono text-sky-700 bg-sky-50 px-2 py-0.5 rounded text-[11px]">{{ $remittance->reference_no }}</span>
                                            </h4>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                Rider: <strong class="text-slate-800 font-semibold">{{ $remittance->rider?->name ?? 'Unknown Rider' }}</strong> &bull; Handover: {{ $remittance->created_at->format('M d, Y • h:i A') }} &bull; Audited By: {{ $remittance->admin?->name ?? 'Admin' }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- 4 Financial Reconciliation Metrics -->
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                        <div class="px-3 py-2 bg-slate-50 rounded-xl border border-slate-200">
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Total Sales Value</span>
                                            <span class="text-xs font-black text-slate-900 block mt-0.5">₱{{ number_format($totalSalesValue, 2) }}</span>
                                        </div>
                                        <div class="px-3 py-2 bg-slate-50 rounded-xl border border-slate-200">
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Expected COD</span>
                                            <span class="text-xs font-bold text-slate-700 block mt-0.5">₱{{ number_format($remittance->expected_amount, 2) }}</span>
                                        </div>
                                        <div class="px-3 py-2 bg-sky-50/70 rounded-xl border border-sky-100">
                                            <span class="text-[9px] font-bold text-sky-700 uppercase tracking-wider block">Actual Turned In</span>
                                            <span class="text-xs font-black text-sky-900 block mt-0.5">₱{{ number_format($remittance->actual_amount, 2) }}</span>
                                        </div>
                                        <div class="px-3 py-2 rounded-xl border {{ $remittance->variance == 0 ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($remittance->variance < 0 ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-sky-50 border-sky-200 text-sky-800') }}">
                                            <span class="text-[9px] font-bold uppercase tracking-wider block">Calculated Variance</span>
                                            <span class="text-xs font-black block mt-0.5">
                                                @if($remittance->variance == 0)
                                                    ₱0.00 (Balanced)
                                                @elseif($remittance->variance < 0)
                                                    -₱{{ number_format(abs($remittance->variance), 2) }} (Shortage)
                                                @else
                                                    +₱{{ number_format($remittance->variance, 2) }} (Overage)
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Remarks if present -->
                                @if($remittance->notes)
                                <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs text-amber-900 flex items-start space-x-2">
                                    <i class="bi bi-chat-left-quote text-amber-600 mt-0.5 text-xs shrink-0"></i>
                                    <div>
                                        <span class="font-bold text-[10px] uppercase tracking-wider text-amber-800">Admin Remarks:</span>
                                        <span class="italic text-amber-950 ml-1">{{ $remittance->notes }}</span>
                                    </div>
                                </div>
                                @endif

                                <!-- Product Breakdown Grouped by Order -->
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-1.5">
                                            <i class="bi bi-box-seam text-sky-600"></i>
                                            <span>Delivered Products Breakdown ({{ $remittance->orders->count() }} {{ Str::plural('Order', $remittance->orders->count()) }})</span>
                                        </h5>
                                        <span class="text-[11px] font-semibold text-slate-500">
                                            Total Units: {{ $remittance->orders->sum(fn($o) => $o->orderItems->sum('quantity')) }} pcs
                                        </span>
                                    </div>

                                    @forelse($remittance->orders as $order)
                                    <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-50/40">
                                        <!-- Order Sub-Header -->
                                        <div class="px-4 py-2.5 bg-slate-100/80 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="font-black text-xs font-mono text-slate-900">{{ $order->order_number }}</span>
                                                <span class="text-slate-300">&bull;</span>
                                                <span class="text-xs font-bold text-slate-700">
                                                    <i class="bi bi-person text-slate-400 mr-0.5"></i> {{ $order->customer?->name ?? 'Customer' }}
                                                </span>
                                                @if($order->delivery_address)
                                                <span class="text-slate-300 hidden sm:inline">&bull;</span>
                                                <span class="text-[11px] text-slate-500 hidden sm:inline truncate max-w-xs">
                                                    <i class="bi bi-geo-alt text-slate-400 mr-0.5"></i> {{ $order->delivery_address }}
                                                </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center space-x-3 text-xs">
                                                <span class="text-slate-500 text-[11px]">Subtotal: <strong class="text-slate-800 font-bold">₱{{ number_format($order->subtotal, 2) }}</strong></span>
                                                @if($order->delivery_fee > 0)
                                                <span class="text-slate-500 text-[11px]">Fee: <strong class="text-slate-800 font-bold">₱{{ number_format($order->delivery_fee, 2) }}</strong></span>
                                                @endif
                                                <span class="text-slate-800 font-black bg-white px-2 py-0.5 rounded border border-slate-200 text-xs">
                                                    Total Sales: ₱{{ number_format($order->total_amount, 2) }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Itemized Products Table -->
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-slate-100 text-left text-xs bg-white">
                                                <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                                    <tr>
                                                        <th class="px-4 py-2">Product Name</th>
                                                        <th class="px-4 py-2">Size / Variant</th>
                                                        <th class="px-4 py-2 text-center">Delivered Qty</th>
                                                        <th class="px-4 py-2 text-right">Individual Unit Value</th>
                                                        <th class="px-4 py-2 text-right">Item Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @forelse($order->orderItems as $item)
                                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                                        <td class="px-4 py-2.5 font-bold text-slate-900">
                                                            <div class="flex items-center space-x-2">
                                                                @if($item->product?->image)
                                                                    <img src="{{ asset('storage/' . $item->product->image) }}" class="w-6 h-6 rounded-md object-cover border border-slate-200" alt="{{ $item->product->product_name }}">
                                                                @else
                                                                    <div class="w-6 h-6 rounded-md bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]">
                                                                        <i class="bi bi-box"></i>
                                                                    </div>
                                                                @endif
                                                                <div>
                                                                    <span>{{ $item->product?->product_name ?? 'Delivered Product' }}</span>
                                                                    @if($item->product?->milk_type)
                                                                        <span class="text-[10px] text-slate-400 font-normal block">{{ $item->product->milk_type }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-2.5 text-slate-600">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                                                {{ $item->product?->size ?? 'Standard' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2.5 text-center font-black text-slate-900">
                                                            {{ $item->quantity }}
                                                        </td>
                                                        <td class="px-4 py-2.5 text-right font-semibold text-slate-600">
                                                            ₱{{ number_format($item->price, 2) }}
                                                        </td>
                                                        <td class="px-4 py-2.5 text-right font-black text-slate-900">
                                                            ₱{{ number_format($item->subtotal, 2) }}
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="5" class="px-4 py-3 text-center text-slate-400 italic">
                                                            No product items registered for this order.
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="py-4 text-center text-xs text-slate-400 bg-white rounded-xl border border-slate-200">
                                        No attached orders recorded for this remittance.
                                    </div>
                                    @endforelse
                                </div>

                                <!-- Bottom Close Drawer Toggle -->
                                <div class="pt-2 flex justify-end">
                                    <button type="button" onclick="toggleBreakdownRow({{ $remittance->id }})" class="text-[11px] font-bold text-slate-500 hover:text-slate-800 transition">
                                        <i class="bi bi-chevron-up mr-1"></i> Collapse Breakdown
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <i class="bi bi-journal-x text-4xl text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-800">No Remittance Records Found</p>
                                <p class="text-xs text-slate-500">Record a new rider remittance using the form above.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($remittances->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $remittances->links() }}
        </div>
        @endif
    </div>

    <!-- Daily Cash Ledger Section -->
    <div id="cashLedgerSection" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="bi bi-journal-text text-emerald-600"></i>
                    <span>Official Daily Cash Ledger</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Audited register recording verified cash intake, variances, cashier/admin IDs, and rider references.</p>
            </div>

            <!-- Ledger Date Filter -->
            <form action="{{ route('admin.remittances.index') }}" method="GET" class="flex items-center gap-2">
                <input type="date" name="ledger_date" value="{{ request('ledger_date') }}" onchange="this.form.submit()"
                    class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                @if(request()->has('ledger_date'))
                    <a href="{{ route('admin.remittances.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-bold px-2 py-1">Clear</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Timestamp</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Admin Handover ID</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Rider Name</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Remittance Ref</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Expected (₱)</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Remitted (₱)</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Variance (₱)</th>
                        <th class="px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Payment Mode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-xs">
                    @forelse($ledgerEntries as $entry)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap font-mono text-slate-600">
                            {{ $entry->recorded_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 block">{{ $entry->admin?->name ?? 'Admin #' . $entry->admin_id }}</span>
                            <span class="text-[10px] text-slate-400 font-mono">ID: #{{ $entry->admin_id }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-800">
                            {{ $entry->rider_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-sky-700">
                            {{ $entry->remittance?->reference_no ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                            ₱{{ number_format($entry->amount_expected, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-black text-emerald-700">
                            ₱{{ number_format($entry->amount_remitted, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap font-bold">
                            @if($entry->variance == 0)
                                <span class="text-emerald-600">₱0.00</span>
                            @elseif($entry->variance < 0)
                                <span class="text-rose-600">-₱{{ number_format(abs($entry->variance), 2) }}</span>
                            @else
                                <span class="text-sky-600">+₱{{ number_format($entry->variance, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-slate-700">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-800 text-[11px] font-medium">
                                {{ $entry->payment_method }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <i class="bi bi-wallet-fill text-4xl text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-800">Daily Cash Ledger is Empty</p>
                                <p class="text-xs text-slate-500">Confirmed remittances will automatically write tamper-evident ledger entries here.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ledgerEntries->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $ledgerEntries->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal: Remittance Detail Breakdown -->
<div id="remittanceDetailModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all my-8">
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="bi bi-receipt-cutoff text-sky-400 text-lg"></i>
                <h3 class="text-sm font-black tracking-tight" id="modalRefTitle">Remittance Details &amp; Product Receipt</h3>
            </div>
            <button type="button" onclick="closeRemittanceModal()" class="text-slate-400 hover:text-white transition p-1">
                <i class="bi bi-x-lg text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-5 max-h-[80vh] overflow-y-auto">
            <!-- Top Summary Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Rider</span>
                    <span id="modalRiderName" class="text-xs font-bold text-slate-900 block mt-0.5 truncate">-</span>
                    <span id="modalRiderPhone" class="text-[10px] text-slate-500 block truncate">-</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Recorded By</span>
                    <span id="modalAdminName" class="text-xs font-bold text-slate-900 block mt-0.5 truncate">-</span>
                    <span id="modalDate" class="text-[10px] text-slate-500 block truncate">-</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status</span>
                    <div id="modalStatusBadge" class="mt-1">-</div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Method</span>
                    <span id="modalMethod" class="text-xs font-bold text-slate-900 block mt-0.5 truncate">-</span>
                </div>
            </div>

            <!-- Reconciliation Financial Banner -->
            <div class="bg-slate-900 text-white rounded-xl p-4 grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Sales Value</span>
                    <span id="modalTotalSales" class="text-lg font-black text-slate-100 block mt-0.5">₱0.00</span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Expected COD</span>
                    <span id="modalExpected" class="text-lg font-black text-slate-200 block mt-0.5">₱0.00</span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-sky-400 block">Actual Turned In</span>
                    <span id="modalActual" class="text-lg font-black text-white block mt-0.5">₱0.00</span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Calculated Variance</span>
                    <span id="modalVariance" class="text-lg font-black block mt-0.5">₱0.00</span>
                </div>
            </div>

            <!-- Notes Section -->
            <div id="modalNotesSection" class="hidden p-3.5 bg-amber-50/80 border border-amber-200 rounded-xl text-xs text-amber-900">
                <span class="font-bold text-[10px] uppercase tracking-wider text-amber-700 block mb-1">Admin Notes / Remarks</span>
                <p id="modalNotesText" class="italic whitespace-pre-line leading-relaxed"></p>
            </div>

            <!-- Attached Orders & Itemized Products Breakdown -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="bi bi-box-seam text-sky-600"></i>
                        <span>Itemized Orders &amp; Delivered Products (<span id="modalOrdersCount">0</span>)</span>
                    </h4>
                </div>
                <div id="modalOrdersContainer" class="space-y-3">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2">
            <button type="button" onclick="closeRemittanceModal()" class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-xl text-xs font-bold transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    // State
    let currentRiderOrders = [];
    let selectedOrderIds = new Set();
    let expectedAmount = 0.00;

    const riderSelect = document.getElementById('rider_select');
    const ordersListWrapper = document.getElementById('ordersListWrapper');
    const ordersLoadingSpinner = document.getElementById('ordersLoadingSpinner');
    const noRiderSelectedState = document.getElementById('noRiderSelectedState');
    const emptyOrdersState = document.getElementById('emptyOrdersState');
    const batchControls = document.getElementById('batch_controls');
    const riderOrderBadge = document.getElementById('rider_order_badge');
    const cashExpectedDisplay = document.getElementById('cashExpectedDisplay');
    const expectedAmountInput = document.getElementById('expected_amount_input');
    const actualAmountInput = document.getElementById('actual_amount');
    const varianceBox = document.getElementById('varianceBox');
    const varianceBadge = document.getElementById('varianceBadge');
    const varianceValueText = document.getElementById('varianceValueText');
    const varianceHelpText = document.getElementById('varianceHelpText');
    const selectedOrdersCountBadge = document.getElementById('selectedOrdersCountBadge');
    const matchExactBtn = document.getElementById('matchExactBtn');
    const clearAmountBtn = document.getElementById('clearAmountBtn');
    const confirmRemittanceBtn = document.getElementById('confirmRemittanceBtn');
    const pendingVerificationBtn = document.getElementById('pendingVerificationBtn');
    const remittanceForm = document.getElementById('remittanceForm');
    const actionTypeInput = document.getElementById('action_type');

    // On rider selection change
    riderSelect.addEventListener('change', function () {
        const riderId = this.value;
        if (!riderId) {
            resetOrdersUI();
            return;
        }

        loadRiderOrders(riderId);
    });

    // Helper: Reset Orders UI
    function resetOrdersUI() {
        ordersListWrapper.classList.add('hidden');
        ordersListWrapper.innerHTML = '';
        emptyOrdersState.classList.add('hidden');
        noRiderSelectedState.classList.remove('hidden');
        batchControls.classList.add('hidden');
        riderOrderBadge.classList.add('hidden');
        currentRiderOrders = [];
        selectedOrderIds.clear();
        updateCalculations();
    }

    // Load orders via AJAX
    function loadRiderOrders(riderId) {
        noRiderSelectedState.classList.add('hidden');
        emptyOrdersState.classList.add('hidden');
        ordersListWrapper.classList.add('hidden');
        ordersLoadingSpinner.classList.remove('hidden');

        fetch(`/admin/remittances/rider/${riderId}/orders`)
            .then(res => res.json())
            .then(data => {
                ordersLoadingSpinner.classList.add('hidden');
                currentRiderOrders = data.orders || [];

                if (currentRiderOrders.length === 0) {
                    emptyOrdersState.classList.remove('hidden');
                    batchControls.classList.add('hidden');
                    riderOrderBadge.textContent = '0 Orders Pending';
                    riderOrderBadge.classList.remove('hidden');
                    selectedOrderIds.clear();
                    updateCalculations();
                    return;
                }

                // Show orders
                riderOrderBadge.textContent = `${currentRiderOrders.length} Orders Pending`;
                riderOrderBadge.classList.remove('hidden');
                batchControls.classList.remove('hidden');
                ordersListWrapper.classList.remove('hidden');

                // Render orders
                renderOrderCards();

                // Select all by default for seamless flow
                selectAllOrders();
            })
            .catch(err => {
                ordersLoadingSpinner.classList.add('hidden');
                noRiderSelectedState.classList.remove('hidden');
                Swal.fire({
                    icon: 'error',
                    title: 'Fetch Error',
                    text: 'Unable to load orders for the selected rider.'
                });
            });
    }

    // Render Order Cards with Run/Batch indicators
    function renderOrderCards() {
        ordersListWrapper.innerHTML = '';

        currentRiderOrders.forEach(order => {
            const isChecked = selectedOrderIds.has(order.id);
            const card = document.createElement('label');
            card.className = `flex items-start space-x-3 p-3.5 rounded-xl border transition cursor-pointer ${
                isChecked ? 'bg-sky-50/60 border-sky-300 ring-1 ring-sky-300/40' : 'bg-white border-slate-200 hover:bg-slate-50'
            }`;
            card.innerHTML = `
                <input type="checkbox" name="order_ids[]" value="${order.id}" ${isChecked ? 'checked' : ''}
                    onchange="toggleOrder(${order.id}, this.checked)"
                    class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="font-black text-xs text-slate-900 font-mono">${order.order_number}</span>
                        <span class="font-black text-xs text-slate-900">₱${Number(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex items-center justify-between mt-1 text-[11px] text-slate-500">
                        <span class="truncate"><i class="bi bi-person mr-1"></i>${order.customer_name}</span>
                        <span class="font-bold text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded text-[10px]">${order.batch_id}</span>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5 truncate">
                        <i class="bi bi-geo-alt mr-1"></i>${order.delivery_address} • ${order.schedule_date}
                    </div>
                </div>
            `;
            ordersListWrapper.appendChild(card);
        });
    }

    // Toggle Order
    window.toggleOrder = function (orderId, isChecked) {
        if (isChecked) {
            selectedOrderIds.add(orderId);
        } else {
            selectedOrderIds.delete(orderId);
        }
        renderOrderCards();
        updateCalculations();
    };

    // Select All
    function selectAllOrders() {
        selectedOrderIds.clear();
        currentRiderOrders.forEach(o => selectedOrderIds.add(o.id));
        renderOrderCards();
        updateCalculations();
    }

    // Deselect All
    function deselectAllOrders() {
        selectedOrderIds.clear();
        renderOrderCards();
        updateCalculations();
    }

    document.getElementById('selectAllOrdersBtn').addEventListener('click', selectAllOrders);
    document.getElementById('deselectAllOrdersBtn').addEventListener('click', deselectAllOrders);

    // Update Calculations & Variance
    function updateCalculations() {
        expectedAmount = 0.00;
        currentRiderOrders.forEach(o => {
            if (selectedOrderIds.has(o.id)) {
                expectedAmount += Number(o.total_amount);
            }
        });

        expectedAmount = Math.round(expectedAmount * 100) / 100;
        cashExpectedDisplay.textContent = expectedAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        expectedAmountInput.value = expectedAmount.toFixed(2);
        selectedOrdersCountBadge.textContent = `${selectedOrderIds.size} Orders Selected`;

        // Calculate variance
        const rawActual = parseFloat(actualAmountInput.value);
        const actual = isNaN(rawActual) ? 0.00 : Math.round(rawActual * 100) / 100;
        const variance = Math.round((actual - expectedAmount) * 100) / 100;

        // Variance UI Styling
        if (selectedOrderIds.size === 0) {
            varianceBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-md bg-slate-100 text-slate-500';
            varianceBadge.textContent = 'Awaiting Orders';
            varianceValueText.className = 'text-xl font-black text-slate-400 mt-1';
            varianceValueText.textContent = '₱0.00';
            varianceBox.className = 'p-3.5 rounded-xl border border-slate-200 bg-white transition-all';
            varianceHelpText.textContent = 'Select one or more orders above to calculate variance.';
            return;
        }

        if (Math.abs(variance) < 0.01) {
            varianceBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800';
            varianceBadge.textContent = 'Balanced (₱0.00)';
            varianceValueText.className = 'text-xl font-black text-emerald-600 mt-1';
            varianceValueText.textContent = '₱0.00';
            varianceBox.className = 'p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/50 transition-all';
            varianceHelpText.textContent = 'Cash remitted precisely matches the expected COD total.';
        } else if (variance < 0) {
            varianceBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-md bg-rose-100 text-rose-800';
            varianceBadge.textContent = `Shortage (-₱${Math.abs(variance).toFixed(2)})`;
            varianceValueText.className = 'text-xl font-black text-rose-600 mt-1';
            varianceValueText.textContent = `-₱${Math.abs(variance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            varianceBox.className = 'p-3.5 rounded-xl border border-rose-200 bg-rose-50/50 transition-all';
            varianceHelpText.textContent = 'Physical cash is LESS than expected. Please document remarks in notes.';
        } else {
            varianceBadge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-md bg-sky-100 text-sky-800';
            varianceBadge.textContent = `Overage (+₱${variance.toFixed(2)})`;
            varianceValueText.className = 'text-xl font-black text-sky-600 mt-1';
            varianceValueText.textContent = `+₱${variance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            varianceBox.className = 'p-3.5 rounded-xl border border-sky-200 bg-sky-50/50 transition-all';
            varianceHelpText.textContent = 'Physical cash is MORE than expected. Excess recorded in cash ledger.';
        }
    }

    // Input Listeners
    actualAmountInput.addEventListener('input', updateCalculations);

    matchExactBtn.addEventListener('click', function () {
        actualAmountInput.value = expectedAmount.toFixed(2);
        updateCalculations();
    });

    clearAmountBtn.addEventListener('click', function () {
        actualAmountInput.value = '';
        updateCalculations();
    });

    window.addAmount = function (increment) {
        const curr = parseFloat(actualAmountInput.value) || 0.00;
        actualAmountInput.value = (curr + increment).toFixed(2);
        updateCalculations();
    };

    // Submit Validation & Confirmation
    function validateForm() {
        if (!riderSelect.value) {
            Swal.fire({ icon: 'warning', title: 'Rider Required', text: 'Please select a delivery rider first.' });
            return false;
        }

        if (selectedOrderIds.size === 0) {
            Swal.fire({ icon: 'warning', title: 'Orders Required', text: 'Please attach at least one fulfilled delivery order.' });
            return false;
        }

        const raw = parseFloat(actualAmountInput.value);
        if (isNaN(raw) || raw < 0) {
            Swal.fire({ icon: 'warning', title: 'Cash Amount Required', text: 'Please enter the physical cash amount received.' });
            return false;
        }

        return true;
    }

    confirmRemittanceBtn.addEventListener('click', function () {
        if (!validateForm()) return;

        const rawActual = parseFloat(actualAmountInput.value);
        const variance = rawActual - expectedAmount;
        let warningText = `Confirm remittance of ₱${rawActual.toFixed(2)} for ${selectedOrderIds.size} orders?`;
        
        if (Math.abs(variance) > 0.01) {
            const diffType = variance < 0 ? 'SHORTAGE' : 'OVERAGE';
            warningText += `\n\nNotice: There is a ${diffType} of ₱${Math.abs(variance).toFixed(2)}. This will be permanently recorded into the Daily Cash Ledger.`;
        }

        Swal.fire({
            title: 'Confirm Cash Remittance?',
            text: warningText,
            icon: Math.abs(variance) > 0.01 ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Confirm & Record',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0284c7'
        }).then(result => {
            if (result.isConfirmed) {
                actionTypeInput.value = 'confirm';
                remittanceForm.submit();
            }
        });
    });

    pendingVerificationBtn.addEventListener('click', function () {
        if (!validateForm()) return;

        Swal.fire({
            title: 'Save as Pending Verification?',
            text: 'This remittance will be held for manager/cashier review before recording into the official cash ledger.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes, Save as Pending',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#f59e0b'
        }).then(result => {
            if (result.isConfirmed) {
                actionTypeInput.value = 'pending';
                remittanceForm.submit();
            }
        });
    });

    // Toggle Expandable Itemized Products Breakdown Row
    window.toggleBreakdownRow = function (remittanceId) {
        const row = document.getElementById(`breakdown-row-${remittanceId}`);
        const chevron = document.getElementById(`chevron-${remittanceId}`);
        const btn = document.getElementById(`btn-toggle-${remittanceId}`);
        if (!row) return;

        const isHidden = row.classList.contains('hidden');
        if (isHidden) {
            row.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        } else {
            row.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
    };

    // Remittance Detail Modal
    window.openRemittanceModal = function (remittanceId) {
        fetch(`/admin/remittances/${remittanceId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) throw new Error();
                const rem = data.remittance;

                document.getElementById('modalRefTitle').textContent = `Remittance Breakdown — ${rem.reference_no}`;
                document.getElementById('modalRiderName').textContent = rem.rider_name;
                document.getElementById('modalRiderPhone').textContent = rem.rider_phone;
                document.getElementById('modalAdminName').textContent = rem.admin_name;
                document.getElementById('modalDate').textContent = rem.created_at;
                document.getElementById('modalMethod').textContent = rem.payment_method;
                document.getElementById('modalOrdersCount').textContent = rem.orders_count;

                // Status Badge
                const badgeContainer = document.getElementById('modalStatusBadge');
                if (rem.status === 'confirmed') {
                    badgeContainer.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Confirmed</span>';
                } else if (rem.status === 'pending_verification') {
                    badgeContainer.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Pending Verification</span>';
                } else {
                    badgeContainer.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">Rejected</span>';
                }

                // Financial Numbers
                const totalSales = Number(rem.total_order_sales_value || 0);
                document.getElementById('modalTotalSales').textContent = `₱${totalSales.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                document.getElementById('modalExpected').textContent = `₱${Number(rem.expected_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                document.getElementById('modalActual').textContent = `₱${Number(rem.actual_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}`;

                const varEl = document.getElementById('modalVariance');
                if (rem.variance == 0) {
                    varEl.className = 'text-lg font-black text-emerald-400 block mt-0.5';
                    varEl.textContent = '₱0.00 (Balanced)';
                } else if (rem.variance < 0) {
                    varEl.className = 'text-lg font-black text-rose-400 block mt-0.5';
                    varEl.textContent = `-₱${Math.abs(rem.variance).toFixed(2)} (Shortage)`;
                } else {
                    varEl.className = 'text-lg font-black text-sky-400 block mt-0.5';
                    varEl.textContent = `+₱${Number(rem.variance).toFixed(2)} (Overage)`;
                }

                // Notes
                const notesSection = document.getElementById('modalNotesSection');
                if (rem.notes && rem.notes.trim()) {
                    document.getElementById('modalNotesText').textContent = rem.notes;
                    notesSection.classList.remove('hidden');
                } else {
                    notesSection.classList.add('hidden');
                }

                // Render Attached Orders with Itemized Products Breakdown
                const ordersContainer = document.getElementById('modalOrdersContainer');
                ordersContainer.innerHTML = '';

                if (!rem.orders || rem.orders.length === 0) {
                    ordersContainer.innerHTML = `
                        <div class="py-6 text-center text-xs text-slate-400 bg-slate-50 rounded-xl border border-slate-200">
                            No attached orders found for this remittance record.
                        </div>
                    `;
                } else {
                    rem.orders.forEach(o => {
                        const orderCard = document.createElement('div');
                        orderCard.className = 'border border-slate-200 rounded-xl overflow-hidden bg-slate-50/50';

                        let itemsRowsHtml = '';
                        if (o.items && o.items.length > 0) {
                            itemsRowsHtml = o.items.map(item => `
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-3.5 py-2 font-bold text-slate-900">
                                        ${item.product_name}
                                        ${item.milk_type ? `<span class="text-[10px] text-slate-400 font-normal block">${item.milk_type}</span>` : ''}
                                    </td>
                                    <td class="px-3.5 py-2 text-slate-600">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                            ${item.size || 'Standard'}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-2 text-center font-black text-slate-900">${item.quantity}</td>
                                    <td class="px-3.5 py-2 text-right text-slate-600 font-semibold">₱${Number(item.unit_price).toFixed(2)}</td>
                                    <td class="px-3.5 py-2 text-right font-black text-slate-900">₱${Number(item.subtotal).toFixed(2)}</td>
                                </tr>
                            `).join('');
                        } else {
                            itemsRowsHtml = `
                                <tr>
                                    <td colspan="5" class="px-3.5 py-2 text-center text-slate-400 italic">No products registered for this order.</td>
                                </tr>
                            `;
                        }

                        orderCard.innerHTML = `
                            <div class="px-4 py-2.5 bg-slate-100/90 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono font-bold text-slate-900">${o.order_number}</span>
                                    <span class="text-slate-300">&bull;</span>
                                    <span class="font-semibold text-slate-700">${o.customer_name}</span>
                                    <span class="text-slate-300 hidden sm:inline">&bull;</span>
                                    <span class="text-[11px] text-slate-400 hidden sm:inline truncate max-w-xs">${o.delivery_address}</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="text-slate-500 text-[11px]">Subtotal: <strong class="text-slate-700 font-bold">₱${Number(o.subtotal).toFixed(2)}</strong></span>
                                    ${o.delivery_fee > 0 ? `<span class="text-slate-500 text-[11px]">Fee: <strong class="text-slate-700 font-bold">₱${Number(o.delivery_fee).toFixed(2)}</strong></span>` : ''}
                                    <span class="font-black text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-200 text-xs">
                                        Sales: ₱${Number(o.total_sales_value || o.amount).toFixed(2)}
                                    </span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-left text-xs bg-white">
                                    <thead class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                        <tr>
                                            <th class="px-3.5 py-1.5">Product</th>
                                            <th class="px-3.5 py-1.5">Size</th>
                                            <th class="px-3.5 py-1.5 text-center">Delivered Qty</th>
                                            <th class="px-3.5 py-1.5 text-right">Unit Value</th>
                                            <th class="px-3.5 py-1.5 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-xs">
                                        ${itemsRowsHtml}
                                    </tbody>
                                </table>
                            </div>
                        `;
                        ordersContainer.appendChild(orderCard);
                    });
                }

                document.getElementById('remittanceDetailModal').classList.remove('hidden');
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load remittance details.' });
            });
    };

    window.closeRemittanceModal = function () {
        document.getElementById('remittanceDetailModal').classList.add('hidden');
    };

    // Generic Action Confirmation Helper
    window.confirmAction = function (e, message) {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: 'Are you sure?',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#0284c7'
        }).then(result => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    };

    // Remittance Timeframe Window & Export Handlers
    window.toggleRemitDateFields = function () {
        const val = document.getElementById('remit_filter')?.value;
        const customDiv = document.getElementById('remit_custom_dates');
        if (customDiv) {
            customDiv.style.display = (val === 'custom') ? 'grid' : 'none';
        }
    };

    window.submitRemittanceExport = function (type) {
        const form = document.getElementById('remittanceFilterForm');
        if (!form) return;

        const oldAction = form.action;
        const oldTarget = form.target;

        if (type === 'excel') {
            form.action = "{{ route('admin.remittances.export.excel') }}";
        } else {
            form.action = "{{ route('admin.remittances.export.pdf') }}";
        }

        form.target = "_blank";
        form.submit();

        // Restore back to standard index action
        setTimeout(() => {
            form.action = oldAction;
            form.target = oldTarget;
        }, 400);
    };

    // Auto-trigger rider load if pre-selected via query string
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof riderSelect !== 'undefined' && riderSelect && riderSelect.value) {
            loadRiderOrders(riderSelect.value);
        }
    });
</script>
@endsection

