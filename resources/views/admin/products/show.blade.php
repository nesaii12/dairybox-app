@extends('layouts.admin')

@section('title', $product->product_name . ' - Product Details')
@section('page_title', 'Product Details')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
            <i class="bi bi-arrow-left mr-1"></i> Back to Products List
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Left: Product Specifications -->
        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-6 border-b border-slate-100">
                <div class="flex items-center space-x-4">
                    <div class="h-16 w-16 rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center flex-shrink-0">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->product_name }}" class="h-full w-full object-cover">
                        @else
                            <i class="bi bi-image text-slate-400 text-2xl"></i>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">{{ $product->product_name }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Category: <span class="font-semibold text-slate-800">{{ $product->category->category_name }}</span></p>
                    </div>
                </div>
                <div class="text-left sm:text-right">
                    <span class="text-2xl font-black text-slate-950">₱{{ number_format($product->price, 2) }}</span>
                    <p class="text-[10px] text-slate-400 uppercase font-bold mt-0.5 tracking-wider">Unit Selling Price</p>
                </div>
            </div>

            <div class="py-6 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Specifications</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="block text-[10px] font-semibold text-slate-400 uppercase">Milk Type</span>
                        <span class="text-sm font-bold text-slate-800">{{ $product->milk_type }} Milk</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="block text-[10px] font-semibold text-slate-400 uppercase">Product Size</span>
                        <span class="text-sm font-bold text-slate-800">{{ $product->size }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="block text-[10px] font-semibold text-slate-400 uppercase">Current Stock</span>
                        <span class="text-sm font-bold {{ $product->stock < 10 ? 'text-rose-600' : 'text-slate-800' }}">{{ $product->stock }} units</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="block text-[10px] font-semibold text-slate-400 uppercase">Nearest Expiration</span>
                        <span class="text-sm font-bold text-slate-800">{{ $product->expiration_date ? \Carbon\Carbon::parse($product->expiration_date)->format('M d, Y') : 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Description</h3>
                <p class="text-sm text-slate-600 leading-relaxed bg-slate-50/50 p-4 rounded-xl">
                    {{ $product->description ? $product->description : 'No description provided.' }}
                </p>
            </div>
            
            <div class="flex items-center justify-end space-x-3 mt-8 pt-6 border-t border-slate-100">
                <a href="{{ route('admin.products.index', ['edit_product_id' => $product->id]) }}" class="inline-flex items-center justify-center space-x-2 py-2 px-4 rounded-xl text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-all">
                    <i class="bi bi-pencil-square"></i>
                    <span>Edit Product</span>
                </a>
            </div>
        </div>

        <!-- Right: QR Code Card -->
        <div class="md:col-span-1 bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col items-center justify-center text-center">
            <h3 class="text-sm font-bold text-slate-900 mb-1">Product QR Link</h3>
            <p class="text-[10px] text-slate-500 mb-6 max-w-[180px]">Scan this code on any camera device to redirect to the customer storefront detail page.</p>
            
            <div class="p-4 bg-sky-50 border border-sky-100 rounded-2xl flex items-center justify-center shadow-inner mb-6">
                <!-- Output QrCode SVG -->
                {!! $qrCode !!}
            </div>

            <div class="w-full space-y-2">
                <button onclick="window.print()" class="w-full inline-flex items-center justify-center space-x-2 py-2 px-4 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-all">
                    <i class="bi bi-printer"></i>
                    <span>Print QR Label</span>
                </button>
                <div class="text-[9px] text-slate-400 font-medium break-all select-all font-mono p-2 bg-slate-50 rounded-lg">
                    {{ route('customer.product', $product->id) }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
