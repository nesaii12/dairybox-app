@extends('layouts.admin')

@section('title', 'Delivery Monitoring')
@section('page_title', 'Delivery Fleet & Incident Monitor')

@section('content')
<!-- Header Stats Overview -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Active Dispatches</span>
            <span class="text-2xl font-black text-slate-900 block mt-1">{{ $activeCount }}</span>
            <span class="text-[10px] text-sky-600 font-semibold">Currently on the road</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100">
            <i class="bi bi-bicycle text-lg"></i>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Arrived at Destination</span>
            <span class="text-2xl font-black text-slate-900 block mt-1">{{ $arrivedCount }}</span>
            <span class="text-[10px] text-amber-600 font-semibold">At customer location</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
            <i class="bi bi-geo-alt-fill text-lg"></i>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Delivered Today</span>
            <span class="text-2xl font-black text-slate-900 block mt-1">{{ $deliveredTodayCount }}</span>
            <span class="text-[10px] text-emerald-600 font-semibold">Completed handovers</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
            <i class="bi bi-check2-circle text-lg"></i>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-slate-400 text-[10px] font-bold uppercase tracking-wider block">Post-Arrival Incidents</span>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-2xl font-black {{ $incidentsCount > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $incidentsCount }}</span>
                @if($unverifiedIncidentsCount > 0)
                    <span class="text-[9px] font-bold bg-rose-50 text-rose-600 px-2 py-0.5 rounded-full border border-rose-100 animate-pulse">
                        {{ $unverifiedIncidentsCount }} Pending Verification
                    </span>
                @endif
            </div>
            <span class="text-[10px] text-rose-600 font-semibold">Cancelled after arrival</span>
        </div>
        <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
            <i class="bi bi-exclamation-triangle-fill text-lg"></i>
        </div>
    </div>
</div>

<!-- Main Monitor Table Card -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <!-- Header with Filters & Search -->
    <div class="px-6 py-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <!-- Status Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2">
            @php
                $tabs = [
                    'all' => 'All Deliveries',
                    'active' => 'Out for Delivery',
                    'arrived' => 'Arrived at Destination',
                    'incidents' => 'Post-Arrival Incidents',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled / Failed',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.deliveries.index', array_merge(request()->query(), ['status' => $key, 'page' => 1])) }}"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all {{ $statusFilter === $key ? 'bg-sky-600 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900 border border-slate-200/60' }}">
                    <span>{{ $label }}</span>
                    @if($key === 'arrived' && $arrivedCount > 0)
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[9px] font-black {{ $statusFilter === 'arrived' ? 'bg-white text-amber-600' : 'bg-amber-500 text-white' }}">
                            {{ $arrivedCount }}
                        </span>
                    @endif
                    @if($key === 'incidents' && $incidentsCount > 0)
                        <span class="ml-1 px-1.5 py-0.2 rounded-full text-[9px] font-black {{ $statusFilter === 'incidents' ? 'bg-white text-rose-600' : 'bg-rose-500 text-white' }}">
                            {{ $incidentsCount }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        <!-- Search Form -->
        <form action="{{ route('admin.deliveries.index') }}" method="GET" class="flex items-center space-x-2">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Order #, customer, or rider..."
                    class="px-3 py-1.5 pl-8 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 w-60 transition-all">
                <i class="bi bi-search absolute left-2.5 top-2 text-slate-400 text-xs"></i>
            </div>
            @if($search)
                <a href="{{ route('admin.deliveries.index', ['status' => $statusFilter]) }}" class="px-2 py-1.5 text-xs text-slate-400 hover:text-slate-600" title="Clear search">
                    <i class="bi bi-x-circle-fill"></i>
                </a>
            @endif
        </form>
    </div>

    <!-- Deliveries Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50/75">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Order & Schedule</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Customer & Destination</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Assigned Rider</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Progress Timeline</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status & Incidents</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Management</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-xs">
                @forelse($deliveries as $delivery)
                @php
                    $order = $delivery->order;
                    $customer = $order->customer;
                    $rider = $delivery->rider;
                    $isPostArrivalCancel = $delivery->cancelled_after_arrival;
                    $isCustomerRestricted = $customer->status === 'restricted' && $customer->restricted_until && $customer->restricted_until->isFuture();
                    $displayCustomerName = $order->reservation?->customer_name ?? $customer->name;
                    $displayCustomerPhone = $order->reservation?->contact_number ?? $customer->phone;
                @endphp
                <tr id="delivery-{{ $delivery->id }}" data-record-id="delivery-{{ $delivery->id }}" data-record-number="{{ $order->order_number }}" class="hover:bg-slate-50/50 transition-colors record-highlight-target {{ $isPostArrivalCancel && !$delivery->admin_verified_at ? 'bg-rose-50/30' : '' }}">
                    <!-- Order Info -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="font-bold text-slate-900 hover:text-sky-600 font-mono">
                                {{ $order->order_number }}
                            </a>
                            <span class="text-[10px] text-slate-500">
                                ₱{{ number_format($order->total_amount, 2) }} &bull; {{ $order->orderItems->count() }} item(s)
                            </span>
                            @if($order->isReservationOrder())
                                <span class="inline-flex items-center w-max px-1.5 py-0.5 rounded text-[9px] font-bold {{ $order->hasPendingBalance() ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                                    @if($order->hasPendingBalance())
                                        Reservation COD: ₱{{ number_format($order->collectibleCodAmount(), 2) }}
                                    @else
                                        Reservation Fully Paid
                                    @endif
                                </span>
                            @endif
                            <span class="text-[9px] text-slate-400">
                                Scheduled: {{ \Carbon\Carbon::parse($order->schedule_date)->format('M d, Y') }}
                            </span>
                        </div>
                    </td>

                    <!-- Customer -->
                    <td class="px-6 py-4">
                        <div class="flex flex-col space-y-1 max-w-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-slate-900">{{ $displayCustomerName }}</span>
                                @if($isCustomerRestricted)
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black bg-rose-100 text-rose-700 border border-rose-200">
                                        Restricted (3-Day Ban)
                                    </span>
                                @endif
                            </div>
                            <span class="text-[10px] text-slate-500 font-mono">{{ $displayCustomerPhone ?? 'No phone' }}</span>
                            <p class="text-[11px] text-slate-600 truncate" title="{{ $order->delivery_address }}">
                                <i class="bi bi-geo-alt text-slate-400 mr-0.5"></i>{{ $order->delivery_address }}
                            </p>
                        </div>
                    </td>

                    <!-- Rider -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($rider)
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-700 border border-sky-100 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    {{ strtoupper(substr($rider->name, 0, 2)) }}
                                </div>
                                <div>
                                    <span class="block font-bold text-slate-900">{{ $rider->name }}</span>
                                    <span class="block text-[10px] text-slate-500 font-mono">{{ $rider->phone ?? 'Rider' }}</span>
                                </div>
                            </div>
                        @else
                            <span class="text-xs text-slate-400 italic">Unassigned</span>
                        @endif
                    </td>

                    <!-- Timeline -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1 text-[11px]">
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <i class="bi bi-box-arrow-right text-slate-400 text-[10px]"></i>
                                <span>Dispatched: {{ $delivery->created_at->timezone('Asia/Manila')->format('M d, h:i A') }}</span>
                            </div>
                            @if($delivery->arrived_at)
                                <div class="flex items-center gap-1.5 text-amber-700 font-medium">
                                    <i class="bi bi-geo-alt-fill text-amber-500 text-[10px]"></i>
                                    <span class="delivery-arrived-time">Arrived: {{ $delivery->arrived_at->timezone('Asia/Manila')->format('M d, h:i A') }}</span>
                                </div>
                            @else
                                <div class="flex items-center gap-1.5 text-slate-400 italic">
                                    <i class="bi bi-clock text-[10px]"></i>
                                    <span>In transit to destination</span>
                                </div>
                            @endif
                            @if($delivery->delivered_at)
                                <div class="flex items-center gap-1.5 text-emerald-700 font-medium">
                                    <i class="bi bi-check-circle-fill text-emerald-500 text-[10px]"></i>
                                    <span>Completed: {{ $delivery->delivered_at->timezone('Asia/Manila')->format('M d, h:i A') }}</span>
                                </div>
                            @endif
                        </div>
                    </td>

                    <!-- Status & Incidents -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            @if($delivery->delivery_status === 'Delivered')
                                <span class="delivery-status-badge inline-flex items-center w-max px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    <i class="bi bi-check-circle-fill mr-1 text-emerald-500"></i> Delivered
                                </span>
                            @elseif($delivery->delivery_status === 'Out for Delivery')
                                @if($delivery->arrived_at)
                                    <span class="delivery-status-badge inline-flex items-center w-max px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">
                                        <i class="bi bi-geo-alt-fill mr-1 text-amber-600"></i> At Customer Location
                                    </span>
                                @else
                                    <span class="delivery-status-badge inline-flex items-center w-max px-2.5 py-1 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-100">
                                        <i class="bi bi-truck mr-1 text-sky-500"></i> Out for Delivery
                                    </span>
                                @endif
                            @elseif($delivery->delivery_status === 'Cancelled')
                                <span class="delivery-status-badge inline-flex items-center w-max px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                    <i class="bi bi-x-circle-fill mr-1 text-rose-500"></i> Cancelled
                                </span>
                            @else
                                <span class="delivery-status-badge inline-flex items-center w-max px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $delivery->delivery_status }}
                                </span>
                            @endif

                            @if($isPostArrivalCancel)
                                <span class="inline-flex items-center w-max px-2 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800 border border-rose-200 mt-1">
                                    <i class="bi bi-shield-exclamation mr-1"></i> Cancelled After Rider Arrived
                                </span>
                            @endif

                            @if($delivery->cancellation_reason || $order->cancellation_reason)
                                <span class="text-[9px] text-slate-500 italic max-w-[180px] truncate block mt-0.5" title="{{ $delivery->cancellation_reason ?? $order->cancellation_reason }}">
                                    Reason: {{ $delivery->cancellation_reason ?? $order->cancellation_reason }}
                                </span>
                            @endif
                        </div>
                    </td>

                    <!-- Management Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex flex-col items-center gap-1.5">
                            @if($isPostArrivalCancel)
                                @if(!$isCustomerRestricted)
                                    <!-- Verify and restrict customer for 3 days -->
                                    <form action="{{ route('admin.deliveries.verifyRestrict', $delivery->id) }}" method="POST"
                                        onsubmit="return confirm('Verify that {{ $customer->name }} cancelled Order #{{ $order->order_number }} after rider arrival?\n\nThis will suspend this customer from ordering for three (3) days.');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center space-x-1 py-1.5 px-3 rounded-xl text-[10px] font-bold text-white bg-rose-600 hover:bg-rose-500 shadow-sm transition-all cursor-pointer">
                                            <i class="bi bi-shield-slash"></i>
                                            <span>Verify & Restrict (3 Days)</span>
                                        </button>
                                    </form>
                                @else
                                    <!-- Already Restricted; option to unrestrict if resolved -->
                                    <div class="flex items-center gap-1">
                                        <span class="text-[9px] font-bold text-slate-500">Restricted until {{ $customer->restricted_until->format('M d') }}</span>
                                        <form action="{{ route('admin.customers.unrestrict', $customer->id) }}" method="POST" onsubmit="return confirm('Restore ordering access for {{ $customer->name }} early?');">
                                            @csrf
                                            <button type="submit" class="text-[9px] font-bold text-sky-600 hover:underline cursor-pointer ml-1">Lift</button>
                                        </form>
                                    </div>
                                @endif
                            @elseif($delivery->delivery_status === 'Out for Delivery')
                                <!-- Cancel Delivery Modal trigger -->
                                <button type="button" onclick="openCancelModal({{ $delivery->id }}, '{{ $order->order_number }}', {{ $delivery->arrived_at ? 'true' : 'false' }})"
                                    class="inline-flex items-center space-x-1 py-1 px-2.5 rounded-lg text-[10px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100 transition-all cursor-pointer">
                                    <i class="bi bi-slash-circle"></i>
                                    <span>Cancel Delivery</span>
                                </button>
                            @endif

                            <a href="{{ route('admin.orders.show', $order->id) }}" class="text-[10px] font-bold text-slate-500 hover:text-sky-600 transition-colors">
                                View Order Details &rarr;
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <i class="bi bi-truck text-4xl text-slate-200 block mb-2"></i>
                        <p class="text-xs font-semibold text-slate-700">No deliveries found matching this filter.</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Dispatched deliveries will automatically populate this monitoring center.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($deliveries->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $deliveries->links() }}
    </div>
    @endif
</div>

<!-- Admin Cancel Delivery Modal -->
<div id="cancelDeliveryModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs hidden">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full mx-4 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <h3 class="text-sm font-bold text-slate-900">Cancel Delivery</h3>
            <button type="button" onclick="closeCancelModal()" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <form id="cancelDeliveryForm" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Order Reference</span>
                <span id="modalOrderNumber" class="text-sm font-bold text-slate-900 font-mono"></span>
            </div>

            <div>
                <label for="cancel_reason" class="block text-xs font-semibold text-slate-700 mb-1">Cancellation Reason</label>
                <textarea name="reason" id="cancel_reason" required rows="3"
                    class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500"
                    placeholder="e.g. Customer cancelled upon arrival, unreachable, refused delivery..."></textarea>
            </div>

            <div class="p-3 bg-rose-50/70 border border-rose-100 rounded-xl space-y-2">
                <label class="flex items-start space-x-2 cursor-pointer">
                    <input type="checkbox" name="cancelled_after_arrival" id="modalPostArrivalCheck" value="1" class="mt-0.5 rounded text-rose-600 focus:ring-rose-500 border-rose-300">
                    <div class="text-[11px] text-rose-800 leading-tight">
                        <span class="font-bold block">Customer Cancelled After Rider Arrived</span>
                        <span class="text-[10px] text-rose-600 font-normal">Flag this incident to allow restricting the customer for 3 days.</span>
                    </div>
                </label>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeCancelModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition-all cursor-pointer">
                    Abort
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-xl transition-all cursor-pointer shadow-sm">
                    Confirm Cancellation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCancelModal(deliveryId, orderNumber, hasArrived) {
        const form = document.getElementById('cancelDeliveryForm');
        form.action = `/admin/deliveries/${deliveryId}/cancel`;
        document.getElementById('modalOrderNumber').textContent = orderNumber;
        const check = document.getElementById('modalPostArrivalCheck');
        check.checked = hasArrived;
        document.getElementById('cancelDeliveryModal').classList.remove('hidden');
    }

    function closeCancelModal() {
        document.getElementById('cancelDeliveryModal').classList.add('hidden');
    }
</script>
@endsection
