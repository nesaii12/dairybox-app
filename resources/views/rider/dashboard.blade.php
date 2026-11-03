@extends('layouts.rider')

@section('title', 'Active Queue')

@section('content')
<!-- Top Queue Header & Statistics -->
<div class="mb-5 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-black text-slate-800 tracking-tight flex items-center space-x-2">
            <span>Active Queue</span>
            <span class="bg-sky-50 text-sky-600 border border-sky-200 text-xs px-2 py-0.5 rounded-full font-bold">
                {{ $deliveries->count() }}
            </span>
        </h2>
        <p class="text-[10px] text-slate-500 font-medium">Assigned delivery tasks for today.</p>
    </div>
</div>

<div class="space-y-5">
    @forelse($deliveries as $delivery)
        @php
            $order = $delivery->order;
            $customer = $order->customer;
        @endphp
        <!-- Modern Tactile Card -->
        <div id="delivery-{{ $delivery->id }}" data-record-id="delivery-{{ $delivery->id }}" data-record-number="{{ $order->order_number }}" class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex flex-col justify-between hover:border-slate-200/80 hover:shadow-md transition-all duration-300 record-highlight-target">
            <!-- Card Header -->
            <div class="flex items-center justify-between border-b border-slate-100 pb-3.5 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                    <div>
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">ORDER REF</span>
                        <h3 class="text-xs font-bold text-slate-800 font-mono leading-none mt-0.5">{{ $order->order_number }}</h3>
                    </div>
                </div>
                <div class="text-right">
                    @if($order->isPaid())
                        <span class="text-[8px] font-black text-emerald-600 uppercase tracking-widest block flex items-center justify-end">
                            <i class="bi bi-check-circle-fill mr-1 text-[8px]"></i> PAID ONLINE
                        </span>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-lg inline-block mt-0.5">
                            ₱0.00 to collect
                        </span>
                    @else
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">COLLECT CASH</span>
                        <span class="text-sm font-black text-slate-950">
                            ₱{{ number_format($order->total_amount, 2) }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Customer & Delivery Specs -->
            <div class="space-y-3.5 mb-5 text-[11px] text-slate-600">
                <!-- Customer Name -->
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500 flex-shrink-0 border border-slate-100">
                        <i class="bi bi-person text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <span class="block text-[8px] text-slate-400 font-bold uppercase tracking-wider">Client Name</span>
                        <span class="text-xs font-semibold text-slate-800">{{ $customer->name }}</span>
                    </div>
                </div>

                <!-- Contact Phone -->
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500 flex-shrink-0 border border-slate-100">
                        <i class="bi bi-telephone text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <span class="block text-[8px] text-slate-400 font-bold uppercase tracking-wider">Contact Phone</span>
                        <a href="tel:{{ $customer->phone }}" class="text-xs font-semibold text-sky-600 hover:text-sky-700 hover:underline select-all">{{ $customer->phone ?? 'N/A' }}</a>
                    </div>
                </div>

                <!-- Destination Address -->
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500 flex-shrink-0 border border-slate-100">
                        <i class="bi bi-geo-alt text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <span class="block text-[8px] text-slate-400 font-bold uppercase tracking-wider">Delivery Destination</span>
                        <span class="text-xs font-semibold text-slate-700 leading-relaxed">{{ $order->delivery_address }}</span>
                    </div>
                </div>
                
                <!-- Package Contents details -->
                <div class="p-3 bg-slate-50 rounded-2xl mt-4 space-y-2 border border-slate-100">
                    <span class="block text-[8px] text-slate-400 font-black uppercase tracking-widest border-b border-slate-100 pb-1.5 mb-1.5"><i class="bi bi-box-seam mr-1 text-slate-400"></i> Package Contents</span>
                    @foreach($order->orderItems as $item)
                        <div class="flex items-center justify-between text-[10px] text-slate-500">
                            <span class="truncate pr-4">{{ $item->product->product_name }} ({{ $item->product->size }})</span>
                            <span class="font-bold text-slate-700 whitespace-nowrap bg-slate-200/60 px-1.5 py-0.25 rounded">x{{ $item->quantity }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Action Status Form Triggers -->
            <div class="pt-4 border-t border-slate-100 flex flex-col gap-2">
                @if(!$delivery->arrived_at)
                    <!-- Step 1: Mark Arrived -->
                    <form action="{{ route('rider.deliveries.markArrived', $delivery->id) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 hover:bg-sky-100/80 active:scale-98 transition-all cursor-pointer shadow-2xs">
                            <i class="bi bi-geo-alt-fill text-sm text-sky-600"></i>
                            <span>Mark Arrived at Destination</span>
                        </button>
                    </form>
                @else
                    <!-- Arrival Badge -->
                    <div class="bg-sky-50/80 border border-sky-200/70 rounded-xl py-1.5 px-3 flex items-center justify-between text-[11px] text-sky-900 font-semibold mb-1">
                        <span class="flex items-center gap-1.5">
                            <i class="bi bi-geo-alt-fill text-sky-600"></i>
                            <span>Arrived at Customer Location</span>
                        </span>
                        <span class="text-[10px] text-sky-700 font-mono">{{ $delivery->arrived_at->format('h:i A') }}</span>
                    </div>
                @endif

                <!-- Confirm Delivered -->
                <form action="{{ route('rider.deliveries.updateStatus', $delivery->id) }}" method="POST" class="w-full">
                    @csrf
                    <input type="hidden" name="status" value="Delivered">
                    <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700 shadow-md shadow-sky-500/20 active:scale-98 transition-all cursor-pointer">
                        <i class="bi bi-check2-circle text-sm"></i>
                        <span>Confirm Delivered</span>
                    </button>
                </form>

                <!-- Cancellation / Failure Options with Reason Modal -->
                <div class="flex gap-2">
                    @if($delivery->arrived_at)
                        <!-- Customer Cancelled After Arrival Button -->
                        <button type="button" onclick="openRiderCancellationModal('{{ $delivery->id }}', '{{ $order->order_number }}', 'arrival', 1)"
                            class="flex-1 flex items-center justify-center space-x-1 py-2 px-2 rounded-xl text-[10px] font-bold text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 active:scale-98 transition-all cursor-pointer" title="Report customer cancellation after arrival">
                            <i class="bi bi-person-x-fill text-xs text-rose-600"></i>
                            <span>Cancelled Upon Arrival</span>
                        </button>
                    @else
                        <button type="button" onclick="openRiderCancellationModal('{{ $delivery->id }}', '{{ $order->order_number }}', 'cancel', 0)"
                            class="flex-1 flex items-center justify-center space-x-1 py-2 px-2 rounded-xl text-[10px] font-bold text-slate-500 bg-slate-50 border border-slate-200 hover:bg-slate-100/80 hover:text-slate-700 active:scale-98 transition-all cursor-pointer">
                            <i class="bi bi-slash-circle text-xs"></i>
                            <span>Cancel</span>
                        </button>
                    @endif

                    <button type="button" onclick="openRiderCancellationModal('{{ $delivery->id }}', '{{ $order->order_number }}', 'failed', '{{ $delivery->arrived_at ? 1 : 0 }}')"
                        class="flex-1 flex items-center justify-center space-x-1 py-2 px-2 rounded-xl text-[10px] font-bold text-slate-600 bg-slate-50 border border-slate-200 hover:bg-slate-100/80 active:scale-98 transition-all cursor-pointer">
                        <i class="bi bi-x-circle text-xs"></i>
                        <span>Failed Delivery</span>
                    </button>
                </div>
            </div>
        </div>
    @empty
        <!-- Empty State card matching DairyBox Brand UI -->
        <div class="py-16 px-6 text-center bg-white rounded-3xl border border-slate-100 shadow-xs flex flex-col items-center justify-center relative overflow-hidden mt-6">
            <!-- Subtle brand ambient aura -->
            <div class="absolute w-36 h-36 rounded-full bg-sky-100/50 blur-2xl pointer-events-none"></div>
            
            <!-- DairyBox Icon Mark Container -->
            <div class="relative w-20 h-20 mb-4 flex items-center justify-center">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-b from-sky-50/90 to-white border border-sky-100 p-2.5 flex items-center justify-center shadow-md shadow-sky-500/10 relative z-10">
                    <img src="{{ asset('images/dairybox-icon.png') }}" alt="DairyBox" class="w-full h-full object-contain">
                </div>
                <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs shadow-sm ring-2 ring-white z-20" title="All dispatches completed">
                    <i class="bi bi-check-lg font-bold"></i>
                </div>
            </div>
            
            <h3 class="text-sm font-bold text-slate-900 relative z-10">All Dispatches Completed!</h3>
            <p class="text-[10px] text-slate-500 mt-1 max-w-[220px] leading-relaxed relative z-10">No pending delivery assignments in your queue. Grab a fresh drink and take a break!</p>
        </div>
    @endforelse
</div>

<!-- Rider Cancellation / Failure Reason Modal -->
<div id="riderCancellationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden transition-all">
    <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl border border-slate-100 flex flex-col space-y-4">
        <!-- Header -->
        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
            <div>
                <h3 id="riderModalTitle" class="text-sm font-bold text-slate-900">Cancel Delivery</h3>
                <p id="riderModalSubtitle" class="text-[10px] text-slate-500 font-medium mt-0.5">Please provide reason for cancellation</p>
            </div>
            <button type="button" onclick="closeRiderCancellationModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>

        <form id="riderCancellationForm" action="" method="POST" class="space-y-3.5">
            @csrf
            <input type="hidden" name="status" id="rider_modal_status" value="Cancelled">
            <input type="hidden" name="cancelled_after_arrival" id="rider_modal_arrival" value="0">

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Quick Reasons (Tap to apply)</label>
                <div id="riderPresetContainer" class="flex flex-wrap gap-1.5 mb-2">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <div>
                <label for="rider_reason_text" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Cancellation Reason / Notes</label>
                <textarea name="cancellation_reason" id="rider_reason_text" rows="3" required
                    class="block w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500/10 focus:border-rose-400 transition-all focus:bg-white resize-none font-medium"
                    placeholder="Enter or select reason for cancellation..."></textarea>
            </div>

            <div class="pt-2 flex flex-col gap-2">
                <button type="submit" id="riderModalSubmitBtn"
                    class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 shadow-md shadow-rose-100 active:scale-98 transition-all cursor-pointer">
                    <i class="bi bi-check2-circle text-sm"></i>
                    <span id="riderSubmitBtnText">Confirm Cancellation</span>
                </button>
                <button type="button" onclick="closeRiderCancellationModal()"
                    class="w-full py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-50 rounded-xl transition-all cursor-pointer">
                    Keep Delivery Active
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const riderPresets = {
        arrival: [
            "Customer refused delivery upon arrival",
            "Customer cancelled order at the door",
            "Customer unavailable / not at home",
            "Customer refused payment / cash not ready",
            "Customer requested return / exchange"
        ],
        failed: [
            "Customer unreachable after multiple calls",
            "Incorrect or incomplete delivery address",
            "Delivery address inaccessible or gated",
            "Inclement weather / road impassable",
            "Customer requested rescheduling"
        ],
        cancel: [
            "Customer requested cancellation before arrival",
            "Unable to contact customer (phone unreachable)",
            "Address cannot be located",
            "Vehicle emergency / mechanical breakdown",
            "Package damaged during transit"
        ]
    };

    function openRiderCancellationModal(deliveryId, orderNumber, type, isAfterArrival) {
        const modal = document.getElementById('riderCancellationModal');
        const form = document.getElementById('riderCancellationForm');
        const title = document.getElementById('riderModalTitle');
        const subtitle = document.getElementById('riderModalSubtitle');
        const statusInput = document.getElementById('rider_modal_status');
        const arrivalInput = document.getElementById('rider_modal_arrival');
        const presetContainer = document.getElementById('riderPresetContainer');
        const textarea = document.getElementById('rider_reason_text');
        const submitBtnText = document.getElementById('riderSubmitBtnText');

        form.action = `/rider/deliveries/${deliveryId}/update-status`;
        arrivalInput.value = isAfterArrival ? '1' : '0';

        if (type === 'arrival') {
            statusInput.value = 'Cancelled';
            title.innerText = 'Report Cancellation Upon Arrival';
            subtitle.innerText = `Order #${orderNumber} • Post-arrival incident report`;
            submitBtnText.innerText = 'Submit Post-Arrival Cancellation';
        } else if (type === 'failed') {
            statusInput.value = 'Failed Delivery';
            title.innerText = 'Report Failed Delivery';
            subtitle.innerText = `Order #${orderNumber} • Log delivery failure`;
            submitBtnText.innerText = 'Confirm Failed Delivery';
        } else {
            statusInput.value = 'Cancelled';
            title.innerText = 'Cancel Delivery Assignment';
            subtitle.innerText = `Order #${orderNumber} • State cancellation reason`;
            submitBtnText.innerText = 'Confirm Order Cancellation';
        }

        // Populate preset buttons
        presetContainer.innerHTML = '';
        const presets = riderPresets[type] || riderPresets.cancel;
        presets.forEach((preset) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'text-[10px] px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-700 font-medium transition-colors cursor-pointer text-left';
            btn.innerText = preset;
            btn.onclick = () => {
                textarea.value = preset;
                textarea.focus();
            };
            presetContainer.appendChild(btn);
        });

        textarea.value = presets[0] || '';
        modal.classList.remove('hidden');
    }

    function closeRiderCancellationModal() {
        document.getElementById('riderCancellationModal').classList.add('hidden');
    }
</script>
@endsection
