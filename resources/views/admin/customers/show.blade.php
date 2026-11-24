@extends('layouts.admin')

@section('title', 'Customer Profile - ' . $customer->name)
@section('page_title', 'Customer Profile')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
        <i class="bi bi-arrow-left mr-1"></i> Back to Customers List
    </a>
    <h2 class="text-2xl font-black text-slate-900 mt-2">{{ $customer->name }}</h2>
    <p class="text-xs text-slate-500">Overview of purchases, reviews, and account parameters.</p>
</div>

@php
    $isSuspended = $customer->status === 'restricted' && $customer->isRestricted();
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column: Profile Card & Suspension Form (Column 1) -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 text-center">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-sky-500 to-sky-400 text-white flex items-center justify-center font-bold text-2xl mx-auto shadow-md shadow-sky-100/50 mb-4 tracking-wider">
                {{ strtoupper(substr($customer->name, 0, 2)) }}
            </div>
            
            <h3 class="text-base font-bold text-slate-900">{{ $customer->name }}</h3>
            <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $customer->email }}</p>
            
            <div class="mt-4 pt-4 border-t border-slate-100 text-left text-xs space-y-3 text-slate-600">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Contact Phone</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $customer->phone ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Account Status</span>
                    @if($isSuspended)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-100/50 shadow-2xs">Restricted</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100/50 shadow-2xs">Active</span>
                    @endif
                </div>
                @if($isSuspended)
                <div class="flex flex-col p-3 bg-rose-50/50 border border-rose-100 rounded-xl text-rose-800 text-[10px] space-y-1 mt-2 shadow-2xs">
                    <span class="font-bold flex items-center"><i class="bi bi-clock-history mr-1"></i> Suspended Until:</span>
                    <span class="font-mono text-rose-700 font-semibold">{{ $customer->restricted_until->format('M d, Y h:i A') }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Abuse Verification Suspension Form / Lift Restriction -->
        @if(!$isSuspended)
        <div class="bg-white rounded-2xl border border-rose-100/80 shadow-sm p-6 relative overflow-hidden">
            <!-- Warning indicator strip -->
            <div class="absolute top-0 inset-x-0 h-1 bg-rose-500"></div>
            
            <h3 class="text-sm font-bold text-slate-950 mb-1 flex items-center space-x-2 text-rose-700 mt-1">
                <i class="bi bi-shield-slash"></i>
                <span>Enforce Account Restriction</span>
            </h3>
            <p class="text-[11px] text-slate-500 mb-5 leading-relaxed">If customer abuse is verified (e.g. repeated fake orders, physical rider reports), apply a 3-day lockout ban.</p>
            
            <form action="{{ route('admin.customers.restrict', $customer->id) }}" method="POST" class="space-y-4" onsubmit="return confirm('Suspend customer account for 3 days? All checkout capabilities will be blocked.');">
                @csrf
                <div>
                    <label for="reason" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Reason for Restriction</label>
                    <textarea name="reason" id="reason" required rows="3"
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-rose-500/10 focus:border-rose-500 text-xs transition-all placeholder-slate-400 focus:bg-white"
                        placeholder="Detail verified policy violation..."></textarea>
                    @error('reason')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                
                <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 transition-all shadow-md shadow-rose-100 cursor-pointer">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Enforce 3-day lockout</span>
                </button>
            </form>
        </div>
        @else
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 relative overflow-hidden">
            <h3 class="text-sm font-bold text-slate-950 mb-1 flex items-center space-x-2 text-slate-800">
                <i class="bi bi-shield-check text-emerald-600"></i>
                <span>Ordering Privileges Restricted</span>
            </h3>
            <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">
                This customer is currently restricted until <strong class="text-slate-700">{{ $customer->restricted_until->format('M d, Y h:i A') }}</strong>. You can restore ordering access early if the issue has been resolved.
            </p>
            <form action="{{ route('admin.customers.unrestrict', $customer->id) }}" method="POST" onsubmit="return confirm('Restore ordering privileges for {{ $customer->name }} early?');">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 transition-all shadow-xs cursor-pointer">
                    <i class="bi bi-arrow-counterclockwise mr-1"></i>
                    <span>Lift Restriction Early</span>
                </button>
            </form>
        </div>
        @endif

        <!-- Delete Customer Card -->
        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm p-6 relative overflow-hidden">
            <h3 class="text-sm font-bold text-slate-950 mb-1 flex items-center space-x-2 text-rose-700">
                <i class="bi bi-trash"></i>
                <span>Delete Customer Account</span>
            </h3>
            <p class="text-[11px] text-slate-500 mb-4 leading-relaxed">
                Permanently delete this customer account and all associated order history. This action cannot be reversed.
            </p>
            <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete customer \'{{ addslashes($customer->name) }}\'? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-all shadow-2xs cursor-pointer">
                    <i class="bi bi-trash"></i>
                    <span>Delete Customer Permanently</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Purchase History & Product Reviews (Columns 2-3) -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Orders History -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-950">Purchase History</h3>
                <p class="text-xs text-slate-500">Record of all orders placed by this customer.</p>
            </div>
            
            <div class="overflow-x-auto max-h-[300px] overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Order No.</th>
                            <th scope="col" class="px-6 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Fulfillment</th>
                            <th scope="col" class="px-6 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Amount</th>
                            <th scope="col" class="px-6 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">Status</th>
                            <th scope="col" class="px-6 py-2.5 text-center text-xs font-bold text-slate-500 uppercase">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-xs">
                        @forelse($orders as $order)
                        <tr>
                            <td class="px-6 py-3 font-bold text-slate-900">{{ $order->order_number }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $order->order_type }}</td>
                            <td class="px-6 py-3 font-semibold text-slate-800">₱{{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border {{ $order->statusBadgeClasses() }}">
                                    {{ $order->statusBadgeLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="text-sky-600 hover:text-sky-500 font-bold">Process</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400 italic">No orders logged.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Product Reviews Submitted -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-950">Ratings & Reviews Log</h3>
                <p class="text-xs text-slate-500">Feedback comments left by the customer on products.</p>
            </div>
            
            <div class="p-6 space-y-4 max-h-[300px] overflow-y-auto">
                @forelse($reviews as $rev)
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-slate-800">{{ $rev->product->product_name }}</span>
                        <div class="flex text-amber-400 text-[10px]">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $rev->rating)
                                    <i class="bi bi-star-fill"></i>
                                @else
                                    <i class="bi bi-star text-slate-300"></i>
                                @endif
                            @endfor
                        </div>
                    </div>
                    @if($rev->review)
                        <p class="text-slate-600 italic bg-white p-2 border border-slate-200/50 rounded-lg">"{{ $rev->review }}"</p>
                    @endif
                </div>
                @empty
                <p class="text-center text-slate-400 italic text-xs py-6">No product reviews submitted by this customer.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
