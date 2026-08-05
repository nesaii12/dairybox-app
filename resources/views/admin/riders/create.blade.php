@extends('layouts.admin')

@section('title', 'Add New Rider')
@section('page_title', 'Add New Rider')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.riders.index') }}" class="inline-flex items-center space-x-1.5 text-xs text-slate-500 hover:text-slate-900 transition-colors font-semibold">
        <i class="bi bi-arrow-left"></i>
        <span>Back to Riders List</span>
    </a>
</div>

<div class="max-w-2xl bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/20">
        <h3 class="text-sm font-bold text-slate-950">Rider Account Details</h3>
        <p class="text-[11px] text-slate-500 mt-0.5">Please fill out the form below to create a rider login account.</p>
    </div>

    <form action="{{ route('admin.riders.store') }}" method="POST" class="p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Full Name -->
            <div class="space-y-1.5 col-span-2">
                <label for="name" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="e.g. John Doe" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400 @error('name') border-rose-300 @enderror" required>
                @error('name')
                    <p class="text-[10px] text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div class="space-y-1.5">
                <label for="email" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="e.g. rider@dairybox.com" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400 @error('email') border-rose-300 @enderror" required>
                @error('email')
                    <p class="text-[10px] text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone Number -->
            <div class="space-y-1.5">
                <label for="phone" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Phone Number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" placeholder="e.g. 09123456789" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400 @error('phone') border-rose-300 @enderror" required>
                @error('phone')
                    <p class="text-[10px] text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="space-y-1.5">
                <label for="password" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Password</label>
                <input type="password" name="password" id="password" placeholder="Minimum 8 characters" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400 @error('password') border-rose-300 @enderror" required>
                @error('password')
                    <p class="text-[10px] text-rose-500 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Re-enter password" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400" required>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
            <a href="{{ route('admin.riders.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
                Cancel
            </a>
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-100 transition-all cursor-pointer">
                Create Account
            </button>
        </div>
    </form>
</div>
@endsection
