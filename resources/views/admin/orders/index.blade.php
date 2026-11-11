@extends('layouts.admin')

@section('title', 'Manage Orders')
@section('page_title', 'Manage Orders')

@section('content')
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <!-- Header with Filters -->
    <div class="px-6 py-5 border-b border-slate-100 flex flex-col gap-4">
        <div>
            <h3 class="text-lg font-bold text-slate-950 font-bold">Customer Orders</h3>
            <p class="text-xs text-slate-500">Track and fulfill client orders, assign riders, and update progress pipelines.</p>
        </div>
        
        <!-- Filters Form -->
        <form action="{{ route('admin.orders.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Filter Status</label>
                <select name="status" id="status" onchange="this.form.submit()"
                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all">
                    <option value="">All Statuses</option>
                    <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Confirmed" {{ request('status') === 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="Preparing" {{ request('status') === 'Preparing' ? 'selected' : '' }}>Preparing</option>
                    <option value="Ready for Pickup" {{ request('status') === 'Ready for Pickup' ? 'selected' : '' }}>Ready for Pickup</option>
                    <option value="Out for Delivery" {{ request('status') === 'Out for Delivery' ? 'selected' : '' }}>Out for Delivery</option>
                    <option value="Delivered" {{ request('status') === 'Delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="Delivered - Pending Remittance" {{ request('status') === 'Delivered - Pending Remittance' ? 'selected' : '' }}>Delivered - Pending Remittance</option>
                    <option value="Paid & Remitted" {{ request('status') === 'Paid & Remitted' ? 'selected' : '' }}>Paid & Remitted</option>
                    <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Fulfillment Mode Filter -->
            <div>
                <label for="type" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Fulfillment Mode</label>
                <select name="type" id="type" onchange="this.form.submit()"
                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all">
                    <option value="">All Fulfillment Types</option>
                    <option value="Delivery" {{ request('type') === 'Delivery' ? 'selected' : '' }}>Delivery</option>
                    <option value="Pickup" {{ request('type') === 'Pickup' ? 'selected' : '' }}>Pickup</option>
                </select>
            </div>

            <!-- Rows per page Filter -->
            <div>
                <label for="per_page" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Rows Per Page</label>
                <select name="per_page" id="per_page" onchange="this.form.submit()"
                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all cursor-pointer">
                    <option value="5" {{ request('per_page', session('per_page_orders', 10)) == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ request('per_page', session('per_page_orders', 10)) == 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', session('per_page_orders', 10)) == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page', session('per_page_orders', 10)) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', session('per_page_orders', 10)) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Order No.</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Schedule Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Total Amount</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Rider</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($orders as $order)
                <tr id="order-{{ $order->id }}" data-record-id="order-{{ $order->id }}" data-record-number="{{ $order->order_number }}" class="hover:bg-slate-50/50 transition-colors record-highlight-target">
                    <!-- Order number -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">
                        {{ $order->order_number }}
                    </td>
                    <!-- Customer -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                            <span>{{ $order->customer->name }}</span>
                            @if($order->customer->isRestricted())
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-700" title="Restricted from ordering until {{ $order->customer->restricted_until->format('M d, Y h:i A') }}">
                                    <i class="bi bi-shield-slash-fill mr-0.5"></i> Restricted (3-Day)
                                </span>
                            @endif
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono">{{ $order->customer->email }}</div>
                    </td>
                    <!-- Fulfillment -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                        <span class="inline-flex items-center space-x-1">
                            @if($order->order_type === 'Delivery')
                                <i class="bi bi-truck text-sky-500"></i>
                                <span>Delivery</span>
                            @else
                                <i class="bi bi-shop text-amber-500"></i>
                                <span>Pickup</span>
                            @endif
                        </span>
                    </td>
                    <!-- Schedule Date -->
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                        {{ \Carbon\Carbon::parse($order->schedule_date)->format('M d, Y') }} 
                        @if($order->schedule_time)
                            &bull; {{ \Carbon\Carbon::parse($order->schedule_time)->format('h:i A') }}
                        @endif
                    </td>
                    <!-- Total Amount -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-bold text-slate-950 block">₱{{ number_format($order->total_amount, 2) }}</span>
                        @if($order->isPaid())
                            <span class="inline-flex items-center text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100 mt-0.5">
                                <i class="bi bi-check-circle-fill text-[8px] mr-1 text-emerald-600"></i> Paid
                            </span>
                        @elseif($order->isOnlinePayment())
                            <span class="inline-flex items-center text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100 mt-0.5">
                                <i class="bi bi-clock-fill text-[8px] mr-1 text-amber-600"></i> Xendit Unpaid
                            </span>
                        @else
                            <span class="inline-flex items-center text-[9px] font-medium text-slate-500 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-200 mt-0.5">
                                Cash
                            </span>
                        @endif
                    </td>
                    <!-- Status Badge -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'Pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                'Confirmed' => 'bg-sky-50 text-sky-700 border-sky-100',
                                'Preparing' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                'Ready for Pickup' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'Out for Delivery' => 'bg-purple-50 text-purple-700 border-purple-100',
                                'Delivered' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
                                'Delivered - Pending Remittance' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'Paid & Remitted' => 'bg-emerald-50 text-emerald-800 border-emerald-200 font-bold',
                                'Cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                            ];
                            $color = $statusColors[$order->order_status] ?? 'bg-slate-50 text-slate-600';
                        @endphp
                        <span class="order-status-badge inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border {{ $color }}">
                            {{ $order->order_status }}
                        </span>
                    </td>
                    <!-- Rider -->
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                        @if($order->rider)
                            <div class="flex items-center space-x-1.5">
                                <div class="w-6 h-6 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-[10px] font-bold">
                                    <i class="bi bi-bicycle"></i>
                                </div>
                                <div>
                                    <span class="order-rider-name font-bold text-slate-900 block">{{ $order->rider->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono block">{{ $order->rider->phone }}</span>
                                </div>
                            </div>
                        @elseif($order->order_type === 'Pickup')
                            <span class="text-slate-400 italic text-[11px] flex items-center gap-1">
                                <i class="bi bi-shop text-slate-400"></i> Storefront Pickup
                            </span>
                        @else
                            <div class="space-y-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <i class="bi bi-exclamation-triangle-fill mr-1 text-amber-500"></i> Unassigned
                                </span>
                                @if(!in_array($order->order_status, ['Cancelled', 'Paid & Remitted']))
                                <form action="{{ route('admin.orders.assignRider', $order->id) }}" method="POST" class="flex items-center space-x-1" onsubmit="return confirmTableRiderAssign(event, this, '{{ $order->order_number }}')">
                                    @csrf
                                    <select name="rider_id" required onchange="this.form.requestSubmit()"
                                        class="text-[11px] font-bold py-1 px-2 bg-amber-50/70 hover:bg-amber-50 border border-amber-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 cursor-pointer shadow-2xs">
                                        <option value="">-- Assign Rider --</option>
                                        @foreach($riders as $rider)
                                            <option value="{{ $rider->id }}">{{ $rider->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                @endif
                            </div>
                        @endif
                    </td>
                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        @php
                            $isUnassignedDelivery = ($order->order_type === 'Delivery' && !$order->rider_id && !in_array($order->order_status, ['Cancelled', 'Paid & Remitted']));
                        @endphp

                        @if($isUnassignedDelivery)
                            <div class="flex flex-col items-center justify-center space-y-1">
                                <span class="inline-flex items-center text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                                    <i class="bi bi-exclamation-triangle-fill mr-1 text-amber-500 text-[9px]"></i> Rider Required
                                </span>
                                <button type="button" onclick="showUnassignedRiderPrompt('{{ $order->order_number }}', '{{ route('admin.orders.show', $order->id) }}')"
                                    class="inline-flex items-center space-x-1 py-1.5 px-3 rounded-lg text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed opacity-80 shadow-2xs"
                                    title="Unassigned Rider: Choose a delivery driver from the dropdown to enable processing">
                                    <i class="bi bi-lock-fill text-[10px] mr-0.5"></i>
                                    <span>Process Order</span>
                                </button>
                            </div>
                        @else
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="inline-flex items-center space-x-1 py-1.5 px-3 rounded-lg text-xs font-bold text-sky-600 bg-sky-50 hover:bg-sky-100 transition-all border border-sky-100 shadow-2xs">
                                <span>Process Order</span>
                                <i class="bi bi-arrow-right-short text-base"></i>
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <i class="bi bi-cart-x text-4xl text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-800">No Orders Found</p>
                            <p class="text-xs text-slate-500">Wait for customer orders to be placed or relax.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($orders->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $orders->links() }}
    </div>
    @endif
</div>

@section('scripts')
<script>
    function confirmTableRiderAssign(event, form, orderNumber) {
        const select = form.querySelector('select[name="rider_id"]');
        if (!select || !select.value) {
            event.preventDefault();
            return false;
        }
        const riderName = select.options[select.selectedIndex].text;

        if (typeof Swal !== 'undefined') {
            event.preventDefault();
            Swal.fire({
                title: 'Assign Rider & Dispatch?',
                text: `Assign ${riderName} to Order ${orderNumber} and update status to Out for Delivery?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Assign Rider',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#0284c7'
            }).then(result => {
                if (result.isConfirmed) {
                    form.submit();
                } else {
                    select.value = '';
                }
            });
            return false;
        }
        return true;
    }

    function showUnassignedRiderNotice(orderNumber) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Rider Required',
                html: `<p class="text-xs text-slate-600 mb-2">Order <strong>${orderNumber}</strong> is a delivery order that cannot be processed or set to Out for Delivery / Delivered while its rider status is <strong>Unassigned</strong>.</p><p class="text-xs text-amber-800 font-semibold bg-amber-50 p-2.5 rounded-xl border border-amber-200">Please choose an active delivery driver from the dropdown in the Rider column to assign and enable order processing.</p>`,
                confirmButtonText: 'Understood',
                confirmButtonColor: '#0284c7'
            });
        } else {
            alert(`Order ${orderNumber} cannot be processed: Please select a delivery driver from the dropdown first.`);
        }
    }
</script>
@endsection
@endsection
