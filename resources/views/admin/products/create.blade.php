@extends('layouts.admin')

@section('title', 'Add New Product')
@section('page_title', 'Add New Product')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
            <i class="bi bi-arrow-left mr-1"></i> Back to Products List
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div>
            <h3 class="text-sm font-bold text-slate-950">Register New Dairy Product</h3>
            <p class="text-xs text-slate-500">Fill in the fields to add a new product. Initial stock will be logged as a FIFO inventory batch.</p>
        </div>

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-6">
            @csrf

            <!-- Product Details Group -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Product Name -->
                <div class="sm:col-span-2">
                    <label for="product_name" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Product Name</label>
                    <input type="text" name="product_name" id="product_name" required value="{{ old('product_name') }}"
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400"
                        placeholder="e.g. Premium Mango Ice Cream">
                    @error('product_name')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Category</label>
                    <select name="category_id" id="category_id" required
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white cursor-pointer font-semibold">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price -->
                <div>
                    <label for="price" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Price (PHP)</label>
                    <div class="mt-1.5 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-slate-400 text-xs font-bold">₱</span>
                        </div>
                        <input type="number" step="0.01" name="price" id="price" required value="{{ old('price') }}"
                            class="block w-full pl-8 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400"
                            placeholder="0.00">
                    </div>
                    @error('price')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Milk Type -->
                <div>
                    <label for="milk_type" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Milk Type Source</label>
                    <select name="milk_type" id="milk_type" required
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white cursor-pointer font-semibold">
                        <option value="Carabao" {{ old('milk_type') == 'Carabao' ? 'selected' : '' }}>Carabao Milk</option>
                        <option value="Cow" {{ old('milk_type', 'Cow') == 'Cow' ? 'selected' : '' }}>Cow Milk</option>
                    </select>
                    @error('milk_type')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Size -->
                <div>
                    <label for="size" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Product Size</label>
                    <select name="size" id="size" required
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white cursor-pointer font-semibold">
                        <option value="Small" {{ old('size') == 'Small' ? 'selected' : '' }}>Small</option>
                        <option value="Medium" {{ old('size', 'Medium') == 'Medium' ? 'selected' : '' }}>Medium</option>
                        <option value="Large" {{ old('size') == 'Large' ? 'selected' : '' }}>Large</option>
                    </select>
                    @error('size')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="sm:col-span-2">
                    <label for="description" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400"
                        placeholder="Write dynamic descriptions detailing flavor, freshness..."></textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Initial Inventory Setup -->
            <div class="p-5 bg-sky-50/40 border border-sky-100/80 rounded-2xl shadow-2xs">
                <h4 class="text-sm font-bold text-sky-950 flex items-center space-x-2">
                    <i class="bi bi-archive-fill text-sky-600"></i>
                    <span>Initial FIFO Inventory Load</span>
                </h4>
                <p class="text-[11px] text-slate-500 mt-1">This registers the initial stock batch with its own expiration date under FIFO logic.</p>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="initial_stock" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Initial Stock Count</label>
                        <input type="number" name="initial_stock" id="initial_stock" required value="{{ old('initial_stock', 0) }}"
                            class="mt-1.5 block w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all placeholder-slate-400">
                        @error('initial_stock')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="expiration_date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Expiration Date (FIFO Batch)</label>
                        <input type="date" name="expiration_date" id="expiration_date" value="{{ old('expiration_date') }}"
                            class="mt-1.5 block w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all">
                        @error('expiration_date')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Image Upload -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Product Image</label>
                <input type="file" name="image" id="image" accept="image/*"
                    class="mt-1.5 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 transition-all cursor-pointer">
                <p class="mt-1 text-[9px] text-slate-400">Supported formats: JPEG, PNG, JPG, WEBP. Max size: 2MB.</p>
                @error('image')
                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.products.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 text-xs font-bold transition-all">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-md shadow-sky-100 transition-all cursor-pointer">
                    Register Product
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
