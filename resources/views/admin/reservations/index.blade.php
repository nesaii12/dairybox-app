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
        <div class="flex items-center gap-2 shrink-0 flex-wrap">
            <a href="{{ route('admin.settings.index') }}#cancellation-policy" class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-all shadow-2xs">
                <i class="bi bi-sliders"></i>
                <span>Cancellation Policy</span>
            </a>
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
                                'Out for Delivery' => 'bg-orange-50 text-orange-700 border-orange-200',
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
                                @if(($reservation->fulfillment_type ?? 'Pickup') === 'Delivery')
                                    <span class="block mt-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-orange-50 text-orange-700 border border-orange-200">
                                            <i class="bi bi-truck"></i>
                                            <span>{{ $reservation->rider?->name ?? 'Rider Delivery' }}</span>
                                        </span>
                                    </span>
                                @endif
                                @if($reservation->status === 'Cancelled' && $reservation->refund_status)
                                    @php
                                        $refundMiniStyles = [
                                            'Pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'Not Applicable' => 'bg-slate-100 text-slate-500 border-slate-200',
                                        ];
                                    @endphp
                                    <span class="block mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $refundMiniStyles[$reservation->refund_status] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                            @if($reservation->refund_status === 'Pending')
                                                Refund Pending: ₱{{ number_format((float) $reservation->refund_amount, 2) }}
                                            @elseif($reservation->refund_status === 'Completed')
                                                Refunded: ₱{{ number_format((float) $reservation->refund_amount, 2) }}
                                            @else
                                                No Refund
                                            @endif
                                        </span>
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                <div class="inline-flex items-center justify-center gap-1 flex-wrap">
                                    <x-chat-button
                                        type="reservation"
                                        :id="$reservation->id"
                                        :label="$reservation->reservation_code"
                                        :unread="$reservation->unreadChatCountFor('admin')"
                                        size="xs"
                                        variant="indigo" />

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

                                    @if($reservation->canConvertToDelivery())
                                        @php
                                            $convertData = [
                                                'url' => route('admin.reservations.convertToDelivery', $reservation->id),
                                                'code' => $reservation->reservation_code,
                                                'customer' => $reservation->customer_name,
                                                'product' => $reservation->product?->product_name ?? 'Product',
                                                'quantity' => (int) $reservation->quantity,
                                                'allocated' => (int) $reservation->allocated_quantity,
                                                'subtotal' => $reservation->productSubtotal(),
                                                'down_payment' => (float) $reservation->down_payment,
                                                'address' => $reservation->customer?->address ?? '',
                                            ];
                                        @endphp
                                        <button type="button"
                                            data-convert="{{ json_encode($convertData) }}"
                                            onclick="openIndexConvertModal(this)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold text-white bg-amber-600 hover:bg-amber-500 transition-colors cursor-pointer"
                                            title="Convert to Rider Delivery">
                                            <i class="bi bi-truck"></i>
                                            <span>Deliver</span>
                                        </button>
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
                                        @php
                                            $cancelPayload = [
                                                'url' => route('admin.reservations.cancel', $reservation->id),
                                                'code' => $reservation->reservation_code,
                                                'customer' => $reservation->customer_name,
                                                'product' => $reservation->product?->product_name ?? 'Product',
                                                'quantity' => (int) $reservation->quantity,
                                                'allocated' => (int) $reservation->allocated_quantity,
                                                'down_payment' => (float) $reservation->down_payment,
                                            ];
                                        @endphp
                                        <button type="button"
                                            data-cancel="{{ json_encode($cancelPayload) }}"
                                            onclick="openIndexCancelModal(this)"
                                            class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/70 transition-colors cursor-pointer"
                                            title="Cancel Reservation">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
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

<!-- Cancel Reservation Modal -->
<div id="indexCancelModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="indexCancelTitle">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-x-circle text-rose-400"></i>
                <h3 id="indexCancelTitle" class="text-sm font-bold">Cancel Reservation</h3>
            </div>
            <button type="button" onclick="closeIndexCancelModal()" class="text-slate-400 hover:text-white cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="indexCancelForm" action="#" method="POST" class="p-6 space-y-4">
            @csrf

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs flex items-center justify-between">
                <div>
                    <span id="idxCancelCustomer" class="font-bold text-slate-900 block">—</span>
                    <span id="idxCancelProduct" class="text-slate-500">—</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Down Payment</span>
                    <span id="idxCancelDownPayment" class="text-sm font-black text-emerald-700">₱0.00</span>
                </div>
            </div>

            <div class="space-y-2">
                <span class="block text-xs font-bold text-slate-700">Cancellation Initiated By</span>
                <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:border-sky-300 cursor-pointer bg-white">
                    <input type="radio" name="cancellation_initiated_by" id="idxInitCustomer" value="customer" checked onchange="updateIndexCancelPreview()" class="mt-0.5 text-sky-600 focus:ring-sky-500">
                    <div class="text-xs">
                        <span class="font-bold text-slate-900 block">Customer-Initiated Cancellation</span>
                        <span class="text-[11px] text-slate-500">
                            @if($cancellationPolicy['enabled'])
                                Applies configured policy: {{ rtrim(rtrim(number_format($cancellationPolicy['fee_percentage'], 2, '.', ''), '0'), '.') }}% fee / {{ rtrim(rtrim(number_format($cancellationPolicy['refund_percentage'], 2, '.', ''), '0'), '.') }}% refund.
                            @else
                                Cancellation fee is currently disabled (0% fee / 100% refund).
                            @endif
                        </span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 hover:border-emerald-300 cursor-pointer bg-white">
                    <input type="radio" name="cancellation_initiated_by" id="idxInitBusiness" value="business" onchange="updateIndexCancelPreview()" class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                    <div class="text-xs">
                        <span class="font-bold text-slate-900 block">Business-Initiated (Unable to Fulfill)</span>
                        <span class="text-[11px] text-slate-500">Full refund override: ₱0.00 fee and 100% down payment refund.</span>
                    </div>
                </label>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Applied Cancellation Fee (<span id="idxPreviewFeePct">0%</span>)</span>
                    <span id="idxPreviewFeeAmt" class="font-bold text-rose-600">₱0.00</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-200/80">
                    <span class="font-bold text-slate-800">Refund Amount (Status: Pending)</span>
                    <span id="idxPreviewRefundAmt" class="text-base font-black text-emerald-700">₱0.00</span>
                </div>
                <p id="idxCancelStockNote" class="text-[11px] text-slate-500 pt-1"></p>
            </div>

            <div>
                <label for="idx_cancellation_reason" class="block text-xs font-bold text-slate-700 mb-1">Cancellation Reason</label>
                <input type="text" name="cancellation_reason" id="idx_cancellation_reason" maxlength="255"
                    placeholder="Optional reason for cancellation audit log"
                    class="w-full text-xs rounded-xl border-slate-300 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeIndexCancelModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 cursor-pointer">
                    Keep Reservation
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 shadow-xs cursor-pointer">
                    Confirm Cancellation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const indexPolicy = @json($cancellationPolicy);
    let activeCancelDownPayment = 0;

    function formatIdxPeso(val) {
        return '₱' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatIdxPct(val) {
        const r = Math.round(Number(val || 0) * 100) / 100;
        return (Number.isInteger(r) ? String(r) : r.toFixed(2).replace(/\.?0+$/, '')) + '%';
    }

    function updateIndexCancelPreview() {
        const selected = document.querySelector('#indexCancelModal input[name="cancellation_initiated_by"]:checked');
        const initiator = selected ? selected.value : 'customer';
        const feePct = (initiator === 'business' || !indexPolicy.enabled) ? 0 : Number(indexPolicy.fee_percentage || 0);
        const feeAmt = Math.round(activeCancelDownPayment * (feePct / 100) * 100) / 100;
        const refundAmt = Math.max(0, Math.round((activeCancelDownPayment - feeAmt) * 100) / 100);

        document.getElementById('idxPreviewFeePct').textContent = formatIdxPct(feePct);
        document.getElementById('idxPreviewFeeAmt').textContent = formatIdxPeso(feeAmt);
        document.getElementById('idxPreviewRefundAmt').textContent = formatIdxPeso(refundAmt);
    }

    function openIndexCancelModal(trigger) {
        const payload = typeof trigger === 'string'
            ? JSON.parse(trigger)
            : (trigger && trigger.dataset && trigger.dataset.cancel ? JSON.parse(trigger.dataset.cancel) : trigger);
        activeCancelDownPayment = Number(payload.down_payment || 0);
        document.getElementById('indexCancelTitle').textContent = `Cancel Reservation ${payload.code}`;
        document.getElementById('indexCancelForm').action = payload.url;
        document.getElementById('idxCancelCustomer').textContent = payload.customer;
        document.getElementById('idxCancelProduct').textContent = `${payload.quantity} pcs • ${payload.product}`;
        document.getElementById('idxCancelDownPayment').textContent = formatIdxPeso(activeCancelDownPayment);
        document.getElementById('idxCancelStockNote').textContent = `Reserved stock (${payload.allocated} pcs) will be released back to Available Stock. Refund stays Pending until handover is confirmed.`;
        document.getElementById('idxInitCustomer').checked = true;
        document.getElementById('idx_cancellation_reason').value = '';
        updateIndexCancelPreview();
        document.getElementById('indexCancelModal').classList.remove('hidden');
    }

    function closeIndexCancelModal() {
        document.getElementById('indexCancelModal').classList.add('hidden');
    }

    function openIndexConvertModal(trigger) {
        const data = JSON.parse(trigger.dataset.convert);
        const subtotal = Number(data.subtotal || 0);
        const downPayment = Number(data.down_payment || 0);
        const deliveryFee = subtotal >= 3000 ? 0 : 25;
        const total = Math.round((subtotal + deliveryFee) * 100) / 100;
        const pendingCod = Math.max(0, Math.round((total - downPayment) * 100) / 100);

        document.getElementById('idxConvertTitle').textContent = `Convert ${data.code} to Rider Delivery`;
        document.getElementById('idxConvertForm').action = data.url;
        document.getElementById('idxConvertCustomer').textContent = data.customer;
        document.getElementById('idxConvertProduct').textContent = `${data.quantity} pcs • ${data.product}`;
        document.getElementById('idxConvertAddress').value = data.address || '';
        document.getElementById('idxConvertSubtotal').textContent = formatIdxPeso(subtotal);
        document.getElementById('idxConvertFee').textContent = deliveryFee > 0 ? formatIdxPeso(deliveryFee) : 'Free (₱0.00)';
        document.getElementById('idxConvertTotal').textContent = formatIdxPeso(total);
        document.getElementById('idxConvertDownPayment').textContent = '-' + formatIdxPeso(downPayment);
        document.getElementById('idxConvertPendingCod').textContent = pendingCod > 0 ? formatIdxPeso(pendingCod) : '₱0.00 (Fully Paid)';
        document.getElementById('idxConvertStockNote').textContent = `Reserved stock (${data.allocated} pcs) stays locked while in transit until rider confirms delivery.`;
        document.getElementById('indexConvertModal').classList.remove('hidden');
    }

    function closeIndexConvertModal() {
        document.getElementById('indexConvertModal').classList.add('hidden');
    }
</script>

<!-- Convert to Rider Delivery Modal -->
<div id="indexConvertModal" class="hidden fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="idxConvertTitle">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="bi bi-truck text-amber-400"></i>
                <h3 id="idxConvertTitle" class="text-sm font-bold">Convert to Rider Delivery</h3>
            </div>
            <button type="button" onclick="closeIndexConvertModal()" class="text-slate-400 hover:text-white cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="idxConvertForm" action="#" method="POST" class="p-6 space-y-4">
            @csrf

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs flex items-center justify-between">
                <div>
                    <span id="idxConvertCustomer" class="font-bold text-slate-900 block">—</span>
                    <span id="idxConvertProduct" class="text-slate-500">—</span>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    Pickup Day Dispatch
                </span>
            </div>

            <div>
                <label for="idxConvertAddress" class="block text-xs font-bold text-slate-700 mb-1">Delivery Destination / Address <span class="text-rose-500">*</span></label>
                <input type="text" name="delivery_address" id="idxConvertAddress" required maxlength="250"
                    placeholder="Enter complete delivery address and landmark"
                    class="w-full text-xs rounded-xl border-slate-300 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
            </div>

            <div>
                <label for="idxConvertRider" class="block text-xs font-bold text-slate-700 mb-1">Assign Delivery Rider <span class="text-rose-500">*</span></label>
                @if($activeRiders->isEmpty())
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold">
                        No active riders available.
                    </div>
                @else
                    <select name="rider_id" id="idxConvertRider" required
                        class="w-full text-xs font-semibold rounded-xl border-slate-300 bg-white py-2.5 px-3 focus:border-sky-500 focus:ring-sky-500 shadow-2xs">
                        <option value="">-- Select Active Rider --</option>
                        @foreach($activeRiders as $riderOption)
                            <option value="{{ $riderOption->id }}">
                                {{ $riderOption->name }}@if($riderOption->phone) ({{ $riderOption->phone }})@endif
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Product Subtotal</span>
                    <span id="idxConvertSubtotal" class="font-bold text-slate-900">₱0.00</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Delivery Fee</span>
                    <span id="idxConvertFee" class="font-bold text-slate-900">₱0.00</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Updated Total Amount</span>
                    <span id="idxConvertTotal" class="font-bold text-slate-900">₱0.00</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Less Down Payment Paid</span>
                    <span id="idxConvertDownPayment" class="font-bold text-emerald-700">-₱0.00</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-200/80">
                    <span class="font-bold text-slate-800">Pending Balance to Collect (COD)</span>
                    <span id="idxConvertPendingCod" class="text-base font-black text-amber-700">₱0.00</span>
                </div>
                <p id="idxConvertStockNote" class="text-[11px] text-slate-500 pt-1"></p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeIndexConvertModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 cursor-pointer">
                    Cancel
                </button>
                <button type="submit" {{ $activeRiders->isEmpty() ? 'disabled' : '' }}
                    class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 shadow-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    Dispatch to Rider
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
