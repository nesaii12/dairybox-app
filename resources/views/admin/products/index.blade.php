@extends('layouts.admin')

@section('title', 'Products Management')
@section('page_title', 'Products Management')

@section('content')
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-slate-950">Product Catalog</h3>
            <p class="text-xs text-slate-500">Manage your dairy products, sizes, pricing, and check QR-code linkages.</p>
        </div>
        <div class="flex items-center space-x-4 self-start sm:self-center">
            <!-- Rows per page selection -->
            <div class="flex items-center space-x-2 text-xs text-slate-500">
                <span>Rows per page:</span>
                <select onchange="window.location.href = updateQueryStringParameter(window.location.href, 'per_page', this.value)" class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 font-semibold text-slate-700 cursor-pointer">
                    <option value="5" {{ request('per_page', session('per_page_products', 10)) == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ request('per_page', session('per_page_products', 10)) == 10 ? 'selected' : '' }}>10</option>
                    <option value="20" {{ request('per_page', session('per_page_products', 10)) == 20 ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page', session('per_page_products', 10)) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', session('per_page_products', 10)) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center justify-center space-x-2 py-2 px-4 rounded-xl text-sm font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100 hover:shadow-lg hover:shadow-sky-100">
                <i class="bi bi-plus-lg"></i>
                <span>Add Product</span>
            </a>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Product</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Category</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Attributes</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Price</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Current Stock</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Dates</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($products as $product)
                @php
                    $isLowStock = $product->stock < 10;
                    $isExpired = false;
                    $isNearExpiration = false;
                    if ($product->expiration_date && $product->stock > 0) {
                        $expiryDate = \Carbon\Carbon::parse($product->expiration_date)->startOfDay();
                        $today = now()->startOfDay();
                        if ($expiryDate->lt($today)) {
                            $isExpired = true;
                        } elseif ($expiryDate->diffInDays($today) <= 7) {
                            $isNearExpiration = true;
                        }
                    }
                @endphp
                <tr id="product-{{ $product->id }}" data-record-id="product-{{ $product->id }}" data-record-name="{{ strtolower($product->product_name . ' (' . $product->size . ')') }}" class="hover:bg-slate-50/50 transition-colors record-highlight-target">
                    <!-- Product details -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            <div class="h-10 w-10 rounded-lg bg-slate-100 overflow-hidden border border-slate-100 flex items-center justify-center flex-shrink-0">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->product_name }}" class="h-full w-full object-cover">
                                @else
                                    <i class="bi bi-image text-slate-400 text-lg"></i>
                                @endif
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900">{{ $product->product_name }}</div>
                                <div class="text-xs text-slate-500 max-w-[200px] truncate" title="{{ $product->description }}">{{ $product->description }}</div>
                            </div>
                        </div>
                    </td>
                    <!-- Category -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                        {{ $product->category->category_name }}
                    </td>
                    <!-- Attributes -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            @if($product->flavor)
                                <span class="inline-flex items-center w-max px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-100">
                                    <i class="bi bi-tag-fill mr-1 text-[9px] text-sky-500"></i> {{ $product->flavor }}
                                </span>
                            @endif
                            <span class="inline-flex items-center w-max px-2 py-0.5 rounded-md text-[10px] font-bold {{ $product->milk_type == 'Carabao' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-800' }}">
                                {{ $product->milk_type }} Milk
                            </span>
                            <span class="text-[10px] text-slate-500 font-medium">Size: {{ $product->size }}</span>
                        </div>
                    </td>
                    <!-- Price -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-950">
                        ₱{{ number_format($product->price, 2) }}
                    </td>
                    <!-- Current Stock -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            <span class="text-sm font-bold {{ $isLowStock ? 'text-rose-600' : 'text-slate-900' }}">{{ $product->stock }} units</span>
                            @if($isLowStock)
                                <span class="inline-flex items-center w-max px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                    <i class="bi bi-exclamation-triangle mr-1"></i> Low Stock
                                </span>
                            @else
                                <span class="inline-flex items-center w-max px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    <i class="bi bi-check2 mr-1"></i> Healthy
                                </span>
                            @endif
                        </div>
                    </td>
                    <!-- Dates -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-0.5">
                            @if($product->manufacturing_date)
                                <span class="text-[10px] text-slate-500 font-medium">
                                    <span class="font-bold text-slate-400">Mfg:</span> {{ \Carbon\Carbon::parse($product->manufacturing_date)->format('M d, Y') }}
                                </span>
                            @endif
                            <span class="text-xs text-slate-700 font-semibold">
                                <span class="font-bold text-slate-400 text-[10px]">Exp:</span> {{ $product->expiration_date ? \Carbon\Carbon::parse($product->expiration_date)->format('M d, Y') : 'N/A' }}
                            </span>
                            @if($isExpired)
                                <span class="inline-flex items-center w-max px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                    <i class="bi bi-x-circle mr-1"></i> Expired
                                </span>
                            @elseif($isNearExpiration)
                                <span class="inline-flex items-center w-max px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                    <i class="bi bi-clock mr-1"></i> Near Expiry
                                </span>
                            @endif
                        </div>
                    </td>
                    <!-- Status -->
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $product->status == 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-50 text-slate-400 border border-slate-100' }}">
                            {{ ucfirst($product->status) }}
                        </span>
                    </td>
                    <!-- Actions -->
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <div class="flex items-center justify-center space-x-2.5">
                            <!-- Show/QR code -->
                            <a href="{{ route('admin.products.show', $product->id) }}" class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-all" title="View Details & QR Code">
                                <i class="bi bi-qr-code text-lg"></i>
                            </a>
                             <!-- Edit -->
                             <button onclick="toggleEditProductModal({{ $product->id }})" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all cursor-pointer" title="Edit Product">
                                 <i class="bi bi-pencil-square text-lg"></i>
                             </button>

                             <!-- Edit Product Modal -->
                             <div id="editProductModal-{{ $product->id }}" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
                                 <!-- Backdrop -->
                                 <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="toggleEditProductModal({{ $product->id }})"></div>
                                 
                                 <!-- Modal Content -->
                                 <div class="fixed inset-0 flex items-center justify-center p-4">
                                     <div class="bg-white rounded-2xl border border-slate-100 shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh] transform transition-all text-left">
                                         <!-- Header -->
                                         <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                                             <div>
                                                 <h3 class="text-base font-bold text-slate-900">Modify Product Details</h3>
                                                 <p class="text-[11px] text-slate-500 mt-0.5">Update naming, pricing, specifications, or status attributes.</p>
                                             </div>
                                             <button type="button" onclick="toggleEditProductModal({{ $product->id }})" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50 transition-all cursor-pointer">
                                                 <i class="bi bi-x-lg text-sm"></i>
                                             </button>
                                         </div>

                                         <!-- Body Form -->
                                         <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="flex-grow overflow-y-auto p-6 space-y-5">
                                             @csrf
                                             @method('PUT')

                                             <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                 <!-- Product Name -->
                                                 <div class="sm:col-span-2 space-y-1 text-left">
                                                     <label for="product_name_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Product Name</label>
                                                     <input type="text" name="product_name" id="product_name_{{ $product->id }}" required value="{{ old('product_name', $product->product_name) }}"
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all"
                                                         placeholder="e.g. Premium Mango Ice Cream">
                                                 </div>

                                                 <!-- Category -->
                                                 <div class="space-y-1 text-left">
                                                     <label for="category_id_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Category</label>
                                                     <select name="category_id" id="category_id_{{ $product->id }}" required
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white">
                                                         @foreach($categories as $category)
                                                             <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                                                 {{ $category->category_name }}
                                                             </option>
                                                         @endforeach
                                                     </select>
                                                 </div>

                                                 <!-- Price -->
                                                 <div class="space-y-1 text-left">
                                                     <label for="price_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Price (PHP)</label>
                                                     <div class="relative rounded-md shadow-sm">
                                                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                             <span class="text-slate-400 text-xs font-bold">₱</span>
                                                         </div>
                                                         <input type="number" step="0.01" name="price" id="price_{{ $product->id }}" required value="{{ old('price', $product->price) }}"
                                                             class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all"
                                                             placeholder="0.00">
                                                     </div>
                                                 </div>

                                                 <!-- Milk Type -->
                                                 <div class="space-y-1 text-left">
                                                     <label for="milk_type_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Milk Type Source</label>
                                                     <select name="milk_type" id="milk_type_{{ $product->id }}" required
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white">
                                                         <option value="Carabao" {{ old('milk_type', $product->milk_type) == 'Carabao' ? 'selected' : '' }}>Carabao Milk</option>
                                                         <option value="Cow" {{ old('milk_type', $product->milk_type) == 'Cow' ? 'selected' : '' }}>Cow Milk</option>
                                                     </select>
                                                 </div>

                                                 <!-- Size -->
                                                 <div class="space-y-1 text-left">
                                                     <label for="size_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Product Size</label>
                                                     <select name="size" id="size_{{ $product->id }}" required
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white">
                                                         <option value="Small" {{ old('size', $product->size) == 'Small' ? 'selected' : '' }}>Small</option>
                                                         <option value="Medium" {{ old('size', $product->size) == 'Medium' ? 'selected' : '' }}>Medium</option>
                                                         <option value="Large" {{ old('size', $product->size) == 'Large' ? 'selected' : '' }}>Large</option>
                                                     </select>
                                                 </div>

<!-- Product Flavor -->
                                                 <div class="sm:col-span-2 space-y-1 text-left p-3.5 bg-slate-50/70 border border-slate-200/80 rounded-xl">
                                                     <div class="flex items-center justify-between mb-1">
                                                         <label for="flavor_{{ $product->id }}" class="block text-xs font-bold text-slate-800 flex items-center space-x-1.5">
                                                             <i class="bi bi-palette2 text-sky-600"></i>
                                                             <span>Product Flavor</span>
                                                         </label>
                                                         <span class="text-[10px] text-slate-400">Preset or custom</span>
                                                     </div>
                                                     <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                         <select onchange="handleEditFlavorSelect({{ $product->id }}, this)"
                                                             class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all cursor-pointer font-medium">
                                                             <option value="">-- Choose Preset Flavor --</option>
                                                             @foreach($flavorPresets as $fp)
                                                                 <option value="{{ $fp['name'] }}" data-image="{{ $fp['image'] }}" {{ old('flavor', $product->flavor) == $fp['name'] ? 'selected' : '' }}>
                                                                     {{ $fp['name'] }}
                                                                 </option>
                                                             @endforeach
                                                             <option value="CUSTOM">Custom Flavor...</option>
                                                         </select>
                                                         <input type="text" name="flavor" id="flavor_{{ $product->id }}" value="{{ old('flavor', $product->flavor) }}"
                                                             class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all font-semibold"
                                                             placeholder="Flavor name...">
                                                     </div>
                                                 </div>


                                                 <!-- Manufacturing Date -->
                                                 <div class="space-y-1 text-left">
                                                     <label for="manufacturing_date_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Manufacturing Date</label>
                                                     <input type="date" name="manufacturing_date" id="manufacturing_date_{{ $product->id }}" value="{{ old('manufacturing_date', $product->manufacturing_date ? \Carbon\Carbon::parse($product->manufacturing_date)->format('Y-m-d') : '') }}" max="{{ date('Y-m-d') }}"
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all">
                                                 </div>

                                                 <!-- Expiration Date -->
                                                 <div class="space-y-1 text-left">
                                                     <label for="expiration_date_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Expiration Date</label>
                                                     <input type="date" name="expiration_date" id="expiration_date_{{ $product->id }}" value="{{ old('expiration_date', $product->expiration_date ? \Carbon\Carbon::parse($product->expiration_date)->format('Y-m-d') : '') }}"
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all">
                                                 </div>

                                                 <!-- Status -->
                                                 <div class="space-y-1 sm:col-span-2 text-left">
                                                     <label for="status_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Catalog Visibility Status</label>
                                                     <select name="status" id="status_{{ $product->id }}" required
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white">
                                                         <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Active (Visible to Storefront)</option>
                                                         <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Inactive (Hidden)</option>
                                                     </select>
                                                 </div>

                                                 <!-- Description -->
                                                 <div class="sm:col-span-2 space-y-1 text-left">
                                                     <label for="description_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Description</label>
                                                     <textarea name="description" id="description_{{ $product->id }}" rows="2"
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all"
                                                         placeholder="Write descriptions detailing flavor, freshness...">{{ old('description', $product->description) }}</textarea>
                                                 </div>
                                             </div>

                                             <!-- Image Upload and Preview -->
                                             <div class="space-y-1.5 text-left">
                                                 <label class="block text-xs font-bold text-slate-700">Product Image</label>
                                                 @if($product->image)
                                                     <div class="flex items-center space-x-3 mb-2">
                                                         <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0">
                                                             <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->product_name }}" class="h-full w-full object-cover">
                                                         </div>
                                                         <span class="text-[10px] text-slate-500">Current uploaded image. To replace, upload a new file below.</span>
                                                     </div>
                                                 @endif
                                                 <input type="file" name="image" id="image_{{ $product->id }}" accept="image/*"
                                                     class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 transition-all cursor-pointer">
                                                 <p class="text-[9px] text-slate-400">Supported formats: JPEG, JPG, PNG, WEBP. Max size: 2MB.</p>
                                             </div>

                                             <!-- Footer Buttons -->
                                             <div class="flex items-center justify-end space-x-2 pt-4 border-t border-slate-100">
                                                 <button type="button" onclick="toggleEditProductModal({{ $product->id }})" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 text-xs font-semibold transition-all cursor-pointer">
                                                     Cancel
                                                 </button>
                                                 <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-md shadow-sky-100 transition-all cursor-pointer">
                                                     Update Product
                                                 </button>
                                             </div>
                                         </form>
                                     </div>
                                 </div>
                             </div>

                             <!-- Delete -->
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this product? This will also remove inventory history.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Delete Product">
                                    <i class="bi bi-trash3 text-lg"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <i class="bi bi-egg-fried text-4xl text-slate-300"></i>
                            <p class="text-sm font-semibold text-slate-800">No Products Registered</p>
                            <p class="text-xs text-slate-500">Get started by creating a new product catalog entry.</p>
                            <a href="{{ route('admin.products.create') }}" class="mt-2 inline-flex items-center space-x-1 py-1.5 px-3 rounded-lg text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100">
                                <i class="bi bi-plus-lg"></i>
                                <span>Create First Product</span>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        {{ $products->links() }}
    </div>
    @endif
</div>
@section('scripts')
<script>
    function handleEditFlavorSelect(productId, select) {
        const val = select.value;
        const flavorInput = document.getElementById('flavor_' + productId);
        if (!val) return;
        if (val === 'CUSTOM') {
            flavorInput.value = '';
            flavorInput.focus();
            return;
        }
        flavorInput.value = val;
        const selectedOpt = select.options[select.selectedIndex];
        const imgPath = selectedOpt.getAttribute('data-image');
        if (imgPath) {
            selectEditPreset(productId, imgPath, val);
        }
    }

    function selectEditPreset(productId, imagePath, flavorName) {
        const presetInput = document.getElementById('edit_preset_image_' + productId);
        const fileInput = document.getElementById('image_' + productId);
        const previewImg = document.getElementById('edit_preview_' + productId);
        const previewPlaceholder = document.getElementById('edit_preview_placeholder_' + productId);
        const previewTitle = document.getElementById('edit_preview_title_' + productId);
        const previewSub = document.getElementById('edit_preview_sub_' + productId);

        if (presetInput) presetInput.value = imagePath;
        if (fileInput) fileInput.value = '';
        if (previewImg) {
            previewImg.src = '{{ asset("storage") }}/' + imagePath;
            previewImg.classList.remove('hidden');
        }
        if (previewPlaceholder) previewPlaceholder.classList.add('hidden');
        if (previewTitle) previewTitle.textContent = 'Selected Preset: ' + flavorName;
        if (previewSub) previewSub.textContent = 'Path: storage/' + imagePath;

        const flavorInput = document.getElementById('flavor_' + productId);
        if (flavorInput && (!flavorInput.value || flavorInput.value.trim() === '')) {
            flavorInput.value = flavorName;
        }
    }

    function previewEditCustomImage(productId, input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const presetInput = document.getElementById('edit_preset_image_' + productId);
            const previewImg = document.getElementById('edit_preview_' + productId);
            const previewPlaceholder = document.getElementById('edit_preview_placeholder_' + productId);
            const previewTitle = document.getElementById('edit_preview_title_' + productId);
            const previewSub = document.getElementById('edit_preview_sub_' + productId);

            if (presetInput) presetInput.value = '';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('hidden');
                }
                if (previewPlaceholder) previewPlaceholder.classList.add('hidden');
                if (previewTitle) previewTitle.textContent = 'Custom Upload: ' + file.name;
                if (previewSub) previewSub.textContent = (file.size / 1024).toFixed(1) + ' KB';
            };
            reader.readAsDataURL(file);
        }
    }

    function toggleEditProductModal(id) {
        const modal = document.getElementById('editProductModal-' + id);
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const editProductId = urlParams.get('edit_product_id');
        if (editProductId) {
            toggleEditProductModal(editProductId);
        }
    });
</script>
@endsection
@endsection
