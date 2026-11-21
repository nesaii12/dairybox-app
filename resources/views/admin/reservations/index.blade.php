@extends('layouts.admin')

@section('title', 'Reservation Management')
@section('page_title', 'Reservation Management')

@section('content')
<div class="space-y-6">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Product Reservations</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage advance orders, track reserved inventory allocations, and fulfill scheduled pickups without overselling.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-2xs">
                <i class="bi bi-archive"></i>
                <span>View Inventory</span>
            </a>
            <a href="{{ route('admin.reservations.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 border border-sky-600 transition-all shadow-sm active:scale-95">
                <i class="bi bi-calendar-plus-fill"></i>
                <span>New Reservation</span>
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-2">
                <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Reserved Units</span>
                <span class="text-2xl font-black text-slate-900 block mt-1">{{ number_format($activeReservedUnits) }} <span class="text-xs font-semibold text-slate-400">pcs</span></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100 shrink-0">
                <i class="bi bi-lock-fill text-lg"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-2">
                <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Pending Approval</span>
                <span class="text-2xl font-black text-amber-600 block mt-1">{{ $pendingCount }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 shrink-0">
                <i class="bi bi-hourglass-split text-lg"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-2">
                <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Confirmed / Ready</span>
                <span class="text-2xl font-black text-indigo-600 block mt-1">{{ $confirmedCount }} <span class="text-xs font-semibold text-slate-400">/ {{ $readyCount }} ready</span></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 shrink-0">
                <i class="bi bi-bag-check-fill text-lg"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-xs flex items-center justify-between">
            <div class="min-w-0 pr-2">
                <span class="text-slate-500 text-[11px] font-bold uppercase tracking-wider block">Remaining Balance</span>
                <span class="text-2xl font-black text-emerald-700 block mt-1 truncate">₱{{ number_format($outstandingBalance, 2) }}</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
                <i class="bi bi-cash-coin text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Main History Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Header & Filters -->
        <div class="px-6 py-5 border-b border-slate-100 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="text-base font-bold text-slate-950">Reservation History</h3>
                    <p class="text-xs text-slate-500">Filter by customer, reservation ID, product, status, or scheduled pickup date.</p>
                </div>
                <span class="text-xs text-slate-500 font-medium">{{ $reservations->total() }} {{ Str::plural('record', $reservations->total()) }}</span>
            </div>

            <form action="{{ route('admin.reservations.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                <!-- Search -->
                <div class="lg:col-span-4">
                    <label for="search" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Search</label>
                    <div class="relative">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                            placeholder="Reservation ID, customer, contact, or product..."
                            class="block w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 focus:bg-white transition-all">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="lg:col-span-3">
                    <label for="status" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status</label>
                    <select name="status" id="status" onchange="this.form.submit()"
                        class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 transition-all cursor-pointer">
                        <option value="">All Statuses</option>
                        @foreach(\App\Models\Reservation::STATUSES as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Pickup Date Filter -->
                <div class="lg:col-span-2">
                    <label for="pickup_date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Pickup Date</label>
                    <input type="date" name="pickup_date" id="pickup_date" value="{{ request('pickup_date') }}"
                        onchange="this.form.submit()"
                        class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 focus:bg-white transition-all">
                </div>

                <!-- Rows Per Page -->
                <div class="lg:col-span-1">
                    <label for="per_page" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Rows</label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()"
                        class="block w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/15 focus:border-sky-500 cursor-pointer">
                        @foreach([5, 10, 20, 50, 100] as $sizeOpt)
                            <option value="{{ $sizeOpt }}" {{ request('per_page', session('per_page_reservations', 10)) == $sizeOpt ? 'selected' : '' }}>{{ $sizeOpt }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
                <div class="lg:col-span-2 flex items-center gap-1.5">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1 py-2 px-3 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 transition-all cursor-pointer">
                        <i class="bi bi-funnel-fill text-[11px]"></i>
                        <span>Filter</span>
                    </button>
                    @if(request()->hasAny(['search', 'status', 'pickup_date', 'date_from', 'date_to']))
                        <a href="{{ route('admin.reservations.index') }}" class="inline-flex items-center justify-center py-2 px-2.5 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all" title="Reset filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Reservation ID</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Customer Name</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Product</th>
                        <th scope="col" class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Quantity</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pickup Date</th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Amount</th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Down Payment</th>
                        <th scope="col" class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">Remaining Balance</th>
                        <th scope="col" class="px-4 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-xs">
                    @forelse($reservations as $reservation)
                        @php
                            $statusStyles = [
                                'Pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'Confirmed' => 'bg-sky-50 text-sky-700 border-sky-200',
                                'Ready for Pickup' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-200',
                            ];
                            $paymentStyles = [
                                'Unpaid' => 'bg-slate-100 text-slate-600',
                                'Partially Paid' => 'bg-amber-50 text-amber-700',
                                'Fully Paid' => 'bg-emerald-50 text-emerald-700',
                            ];
                            $badgeClass = $statusStyles[$reservation->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                            $payBadgeClass = $paymentStyles[$reservation->payment_status] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <!-- Reservation ID -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <a href="{{ route('admin.reservations.show', $reservation->id) }}" class="font-bold text-slate-900 hover:text-sky-600 transition-colors">
                                    {{ $reservation->reservation_code }}
                                </a>
                                <span class="block text-[10px] text-slate-400">
                                    Booked {{ $reservation->reservation_date->format('M d, Y') }}
                                </span>
                            </td>

                            <!-- Customer Name -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="font-semibold text-slate-800 block">{{ $reservation->customer_name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $reservation->contact_number }}</span>
                            </td>

                            <!-- Product -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="font-semibold text-slate-900 block">{{ $reservation->product?->product_name ?? 'Deleted Product' }}</span>
                                <span class="text-[10px] text-slate-400">
                                    {{ $reservation->size ?? $reservation->product?->size ?? 'Standard' }}
                                    @if($reservation->product?->milk_type)
                                        &bull; {{ $reservation->product->milk_type }}
                                    @endif
                                </span>
                            </td>

                            <!-- Quantity -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                <span class="font-black text-slate-900">{{ $reservation->quantity }}</span>
                                <span class="text-[10px] text-slate-400">pcs</span>
                                @if($reservation->allocated_quantity > 0)
                                    <span class="block text-[9px] font-semibold text-sky-600">Reserved</span>
                                @endif
                            </td>

                            <!-- Pickup Date -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="font-semibold text-slate-800 block">{{ $reservation->pickup_date->format('M d, Y') }}</span>
                                <span class="text-[10px] text-slate-400">{{ $reservation->pickup_date->format('l') }}</span>
                            </td>

                            <!-- Total Amount -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-slate-900">
                                ₱{{ number_format((float) $reservation->total_amount, 2) }}
                            </td>

                            <!-- Down Payment -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-right font-semibold text-emerald-700">
                                ₱{{ number_format((float) $reservation->down_payment, 2) }}
                            </td>

                            <!-- Remaining Balance -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                <span class="font-bold {{ (float) $reservation->remaining_balance > 0 ? 'text-slate-900' : 'text-emerald-600' }} block">
                                    ₱{{ number_format((float) $reservation->remaining_balance, 2) }}
                                </span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold {{ $payBadgeClass }} mt-0.5">
                                    {{ $reservation->payment_status }}
                                </span>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClass }}">
                                    {{ $reservation->status }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                <div class="inline-flex items-center justify-center gap-1 flex-wrap">
                                    <a href="{{ route('admin.reservations.show', $reservation->id) }}"
                                       class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors"
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    @if($reservation->canEdit())
                                        <a href="{{ route('admin.reservations.edit', $reservation->id) }}"
                                           class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200/70 transition-colors"
                                           title="Edit Reservation">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    @endif

                                    @if($reservation->canConfirm())
                                        <form action="{{ route('admin.reservations.confirm', $reservation->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold text-white bg-sky-600 hover:bg-sky-500 transition-colors cursor-pointer"
                                                title="Confirm & Reserve Stock">
                                                <i class="bi bi-check-lg"></i>
                                                <span>Confirm</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($reservation->canMarkReady())
                                        <form action="{{ route('admin.reservations.ready', $reservation->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition-colors cursor-pointer"
                                                title="Mark Ready for Pickup">
                                                <i class="bi bi-box2-heart"></i>
                                                <span>Ready</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($reservation->canComplete())
                                        <form action="{{ route('admin.reservations.complete', $reservation->id) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Mark {{ $reservation->reservation_code }} as Completed? This deducts {{ $reservation->quantity }} pcs from physical stock and records the full sale.')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold text-white bg-emerald-600 hover:bg-emerald-500 transition-colors cursor-pointer"
                                                title="Complete Pickup & Log Sale">
                                                <i class="bi bi-check2-circle"></i>
                                                <span>Complete</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($reservation->canCancel())
                                        <form action="{{ route('admin.reservations.cancel', $reservation->id) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Cancel reservation {{ $reservation->reservation_code }}? Any reserved stock will be released back to available inventory.')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/70 transition-colors cursor-pointer"
                                                title="Cancel Reservation">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i class="bi bi-calendar2-x text-4xl text-slate-300"></i>
                                    <p class="text-sm font-semibold text-slate-800">No reservations found</p>
                                    <p class="text-xs text-slate-500">
                                        @if(request()->hasAny(['search', 'status', 'pickup_date', 'date_from', 'date_to']))
                                            Try adjusting your search or filter criteria.
                                        @else
                                            Create your first product reservation to start holding inventory for scheduled pickups.
                                        @endif
                                    </p>
                                    @if(!request()->hasAny(['search', 'status', 'pickup_date', 'date_from', 'date_to']))
                                        <a href="{{ route('admin.reservations.create') }}" class="mt-2 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all">
                                            <i class="bi bi-calendar-plus-fill"></i>
                                            <span>Create Reservation</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reservations->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $reservations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
