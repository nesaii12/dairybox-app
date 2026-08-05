@extends('layouts.admin')

@section('title', 'Rider Accounts')
@section('page_title', 'Rider Accounts')

@section('content')
<!-- Rider Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
    <!-- Card 1: Total Riders -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
        <div class="space-y-1.5">
            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Total Riders</span>
            <span class="block text-2xl font-black text-slate-900 leading-none">
                {{ \App\Models\User::where('role', 'rider')->count() }}
            </span>
        </div>
        <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100 shadow-2xs">
            <i class="bi bi-bicycle text-lg"></i>
        </div>
    </div>
    
    <!-- Card 2: Active Riders -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
        <div class="space-y-1.5">
            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Active Riders</span>
            <span class="block text-2xl font-black text-emerald-600 leading-none">
                {{ \App\Models\User::where('role', 'rider')->where('status', 'active')->count() }}
            </span>
        </div>
        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-2xs">
            <i class="bi bi-patch-check-fill text-lg"></i>
        </div>
    </div>
    
    <!-- Card 3: Restricted Riders -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between">
        <div class="space-y-1.5">
            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Restricted Riders</span>
            <span class="block text-2xl font-black text-rose-600 leading-none">
                {{ \App\Models\User::where('role', 'rider')->where('status', 'restricted')->count() }}
            </span>
        </div>
        <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shadow-2xs">
            <i class="bi bi-shield-x text-lg"></i>
        </div>
    </div>
</div>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <p class="text-xs text-slate-500">Create, update, and manage rider accounts for deliveries.</p>
    </div>
    <div class="flex items-center space-x-3 self-start sm:self-center">
        <a href="{{ route('admin.riders.create') }}" class="inline-flex items-center space-x-2 bg-sky-600 hover:bg-sky-700 text-white px-4 py-2.5 rounded-xl text-xs font-semibold shadow-md shadow-sky-100 transition-all cursor-pointer">
            <i class="bi bi-person-plus text-sm"></i>
            <span>Add New Rider</span>
        </a>
    </div>
</div>

<!-- Riders Table Card -->
<div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
    <!-- Table Header with Search & Filters -->
    <div class="p-5 border-b border-slate-100 bg-slate-50/20 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search form -->
        <form action="{{ route('admin.riders.index') }}" method="GET" class="relative w-full md:max-w-xs">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="bi bi-search text-xs"></i>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search riders..."
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
                    <option value="5" {{ request('per_page', session('per_page_riders', 10)) == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ request('per_page', session('per_page_riders', 10)) == 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', session('per_page_riders', 10)) == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page', session('per_page_riders', 10)) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', session('per_page_riders', 10)) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/75 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <th class="py-4 px-6">Rider Name</th>
                    <th class="py-4 px-6">Email Address</th>
                    <th class="py-4 px-6">Phone Number</th>
                    <th class="py-4 px-6">Status</th>
                    <th class="py-4 px-6">Registered On</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs text-slate-600">
                @forelse($riders as $rider)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="py-4 px-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-sky-400 flex items-center justify-center text-white font-bold shadow-xs shadow-sky-100/50 text-xs tracking-wider">
                                {{ strtoupper(substr($rider->name, 0, 2)) }}
                            </div>
                            <div>
                                <span class="block font-bold text-slate-900 text-sm">{{ $rider->name }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="py-4 px-6 font-medium text-slate-900">{{ $rider->email }}</td>
                    <td class="py-4 px-6 font-mono text-slate-500">{{ $rider->phone ?? 'N/A' }}</td>
                    <td class="py-4 px-6">
                        @if($rider->status === 'active')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100/50 shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-100/50 shadow-2xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                Restricted
                            </span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-slate-500">{{ $rider->created_at->format('M d, Y') }}</td>
                    <td class="py-4 px-6 text-right">
                        <div class="flex items-center justify-end space-x-2">
                            <!-- Edit -->
                            <button onclick="toggleEditRiderModal({{ $rider->id }})" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-xl border border-slate-100 bg-white transition-all shadow-2xs cursor-pointer" title="Edit Rider">
                                <i class="bi bi-pencil-square text-sm"></i>
                            </button>

                            <!-- Edit Rider Modal -->
                            <div id="editRiderModal-{{ $rider->id }}" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
                                <!-- Backdrop -->
                                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" onclick="toggleEditRiderModal({{ $rider->id }})"></div>
                                
                                <!-- Modal Content -->
                                <div class="fixed inset-0 flex items-center justify-center p-4">
                                    <div class="bg-white rounded-2xl border border-slate-100 shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh] transform transition-all text-left">
                                        <!-- Header -->
                                        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                                            <div>
                                                <h3 class="text-sm font-bold text-slate-900">Update Rider: {{ $rider->name }}</h3>
                                                <p class="text-[10px] text-slate-500 mt-0.5">Modify account settings, password, or toggle active status.</p>
                                            </div>
                                            <button type="button" onclick="toggleEditRiderModal({{ $rider->id }})" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition-all cursor-pointer">
                                                <i class="bi bi-x-lg text-xs"></i>
                                            </button>
                                        </div>

                                        <!-- Body Form -->
                                        <form action="{{ route('admin.riders.update', $rider->id) }}" method="POST" class="flex-grow overflow-y-auto p-6 space-y-4">
                                            @csrf
                                            @method('PUT')

                                            <div class="space-y-4">
                                                <!-- Full Name -->
                                                <div class="space-y-1.5 text-left">
                                                    <label for="name_{{ $rider->id }}" class="block text-xs font-bold text-slate-700">Full Name</label>
                                                    <input type="text" name="name" id="name_{{ $rider->id }}" required value="{{ old('name', $rider->name) }}" placeholder="e.g. John Doe"
                                                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white">
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <!-- Email Address -->
                                                    <div class="space-y-1.5 text-left">
                                                        <label for="email_{{ $rider->id }}" class="block text-xs font-bold text-slate-700">Email Address</label>
                                                        <input type="email" name="email" id="email_{{ $rider->id }}" required value="{{ old('email', $rider->email) }}" placeholder="e.g. rider@dairybox.com"
                                                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white">
                                                    </div>

                                                    <!-- Phone Number -->
                                                    <div class="space-y-1.5 text-left">
                                                        <label for="phone_{{ $rider->id }}" class="block text-xs font-bold text-slate-700">Phone Number</label>
                                                        <input type="text" name="phone" id="phone_{{ $rider->id }}" required value="{{ old('phone', $rider->phone) }}" placeholder="e.g. 09123456789"
                                                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white">
                                                    </div>
                                                </div>

                                                <!-- Status -->
                                                <div class="space-y-1.5 text-left">
                                                    <label for="status_{{ $rider->id }}" class="block text-xs font-bold text-slate-700">Account Status</label>
                                                    <select name="status" id="status_{{ $rider->id }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white cursor-pointer" required>
                                                        <option value="active" {{ old('status', $rider->status) === 'active' ? 'selected' : '' }}>Active (Can deliver orders)</option>
                                                        <option value="restricted" {{ old('status', $rider->status) === 'restricted' ? 'selected' : '' }}>Restricted (Suspended from deliveries)</option>
                                                    </select>
                                                </div>

                                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-[10px] text-slate-500 leading-relaxed text-left flex items-start space-x-2">
                                                    <i class="bi bi-info-circle text-slate-400 mt-0.5"></i> 
                                                    <span>Leave the password fields blank if you do not wish to change the rider's current password.</span>
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <!-- Password -->
                                                    <div class="space-y-1.5 text-left">
                                                        <label for="password_{{ $rider->id }}" class="block text-xs font-bold text-slate-700">New Password</label>
                                                        <input type="password" name="password" id="password_{{ $rider->id }}" placeholder="Minimum 8 characters"
                                                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white">
                                                    </div>

                                                    <!-- Confirm Password -->
                                                    <div class="space-y-1.5 text-left">
                                                        <label for="password_confirmation_{{ $rider->id }}" class="block text-xs font-bold text-slate-700">Confirm Password</label>
                                                        <input type="password" name="password_confirmation" id="password_confirmation_{{ $rider->id }}" placeholder="Re-enter password"
                                                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Footer Buttons -->
                                            <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100">
                                                <button type="button" onclick="toggleEditRiderModal({{ $rider->id }})" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 text-xs font-semibold transition-all cursor-pointer">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-md shadow-sky-100 transition-all cursor-pointer">
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Delete -->
                            <form action="{{ route('admin.riders.destroy', $rider->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete rider account \'{{ $rider->name }}\'? All assigned deliveries will be unassigned.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl border border-slate-100 bg-white transition-all shadow-2xs cursor-pointer" title="Delete Rider">
                                    <i class="bi bi-trash text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-16 text-center text-slate-400">
                        <i class="bi bi-bicycle text-4xl block mb-2 text-slate-200 animate-bounce"></i>
                        <p class="text-sm font-semibold text-slate-800">No riders registered yet.</p>
                        <p class="text-xs text-slate-400 mt-0.5">Click "Add New Rider" to setup a delivery rider account.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($riders->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $riders->links() }}
    </div>
    @endif
</div>

@section('scripts')
<script>
    function toggleEditRiderModal(id) {
        const modal = document.getElementById('editRiderModal-' + id);
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const editRiderId = urlParams.get('edit_rider_id');
        if (editRiderId) {
            toggleEditRiderModal(editRiderId);
        }
    });

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
