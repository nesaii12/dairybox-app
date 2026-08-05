@extends('layouts.admin')

@section('title', 'Manage Customers')
@section('page_title', 'Manage Customers')

@section('content')
<!-- Customer Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
    <!-- Card 1: Total Customers -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
        <div class="space-y-1.5">
            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Total Customers</span>
            <span class="block text-2xl font-black text-slate-900 leading-none">
                {{ \App\Models\User::where('role', 'customer')->count() }}
            </span>
        </div>
        <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100 shadow-2xs">
            <i class="bi bi-people text-lg"></i>
        </div>
    </div>
    
    <!-- Card 2: Restricted Accounts -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
        <div class="space-y-1.5">
            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Restricted Accounts</span>
            <span class="block text-2xl font-black text-rose-600 leading-none">
                {{ \App\Models\User::where('role', 'customer')->where('status', 'restricted')->count() }}
            </span>
        </div>
        <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shadow-2xs">
            <i class="bi-shield-slash text-lg"></i>
        </div>
    </div>
    
    <!-- Card 3: Total Orders Placed -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
        <div class="space-y-1.5">
            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Total Orders Placed</span>
            <span class="block text-2xl font-black text-emerald-600 leading-none">
                {{ \App\Models\Order::count() }}
            </span>
        </div>
        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-2xs">
            <i class="bi bi-cart-check-fill text-lg"></i>
        </div>
    </div>
</div>

<div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
    <!-- Table Header with Search & Filters -->
    <div class="p-5 border-b border-slate-100 bg-slate-50/20 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search bar -->
        <form action="{{ route('admin.customers.index') }}" method="GET" class="relative w-full md:max-w-xs">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="bi bi-search text-xs"></i>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customers..."
                class="block w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 placeholder-slate-400 text-slate-800 transition-all shadow-2xs">
            @if(request('per_page'))
                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
            @endif
        </form>

        <div class="flex items-center justify-between sm:justify-end gap-4">
            <!-- Rows per page selection -->
            <div class="flex items-center space-x-2 text-xs text-slate-500">
                <span>Rows:</span>
                <select onchange="window.location.href = updateQueryStringParameter(window.location.href, 'per_page', this.value)" class="bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 font-semibold text-slate-700 cursor-pointer text-xs shadow-2xs transition-all">
                    <option value="5" {{ request('per_page', session('per_page_customers', 10)) == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ request('per_page', session('per_page_customers', 10)) == 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', session('per_page_customers', 10)) == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page', session('per_page_customers', 10)) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', session('per_page_customers', 10)) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr class="bg-slate-50/75 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <th scope="col" class="px-6 py-4 text-left">Customer</th>
                    <th scope="col" class="px-6 py-4 text-left">Email Address</th>
                    <th scope="col" class="px-6 py-4 text-left">Contact Phone</th>
                    <th scope="col" class="px-6 py-4 text-center">Orders Count</th>
                    <th scope="col" class="px-6 py-4 text-left">Account Status</th>
                    <th scope="col" class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-xs text-slate-600">
                @forelse($customers as $client)
                @php
                    $isSuspended = $client->status === 'restricted' && $client->isRestricted();
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <!-- Name -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-sky-400 flex items-center justify-center text-white font-bold shadow-xs shadow-sky-100/50 text-xs tracking-wider">
                                {{ strtoupper(substr($client->name, 0, 2)) }}
                            </div>
                            <span class="text-sm font-bold text-slate-900">{{ $client->name }}</span>
                        </div>
                    </td>
                    <!-- Email -->
                    <td class="px-6 py-4 whitespace-nowrap text-slate-600 font-medium">
                        {{ $client->email }}
                    </td>
                    <!-- Phone -->
                    <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-mono">
                        {{ $client->phone ?? 'N/A' }}
                    </td>
                    <!-- Orders Count -->
                    <td class="px-6 py-4 whitespace-nowrap text-center font-bold text-slate-800">
                        {{ $client->orders_count }}
                    </td>
                    <!-- Status -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($isSuspended)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-100/50 shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                Restricted
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100/50 shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                Active
                            </span>
                        @endif
                    </td>
                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-right font-medium">
                        <a href="{{ route('admin.customers.show', $client->id) }}" class="inline-flex items-center space-x-1.5 py-1.5 px-3 rounded-xl text-xs font-bold text-sky-600 bg-sky-50 hover:bg-sky-100 transition-all border border-sky-100 mr-2 shadow-2xs cursor-pointer">
                            <span>View Profile</span>
                            <i class="bi bi-chevron-right text-[10px]"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center text-slate-400">
                        <i class="bi bi-people text-4xl block mb-2 text-slate-200"></i>
                        <p class="text-sm font-semibold text-slate-800">No customers registered yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $customers->links() }}
    </div>
    @endif
</div>

@section('scripts')
<script>
    // Helper function to update query string parameters in pagination link clicks
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
            return uri + separator + key + "=" + value;
        }
    }
</script>
@endsection
@endsection
