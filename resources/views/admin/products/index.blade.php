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
            <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                <tr>
                    <th scope="col" class="px-3.5 py-3 text-left">Product</th>
                    <th scope="col" class="px-3 py-3 text-left">Category</th>
                    <th scope="col" class="px-3 py-3 text-left">Attributes</th>
                    <th scope="col" class="px-3 py-3 text-left">Price</th>
                    <th scope="col" class="px-3 py-3 text-left">Current Stock</th>
                    <th scope="col" class="px-3 py-3 text-left">Manufacture Date</th>
                    <th scope="col" class="px-3 py-3 text-left">Expiry Info</th>
                    <th scope="col" class="px-3 py-3 text-center">Status</th>
                    <th scope="col" class="px-3 py-3 text-center sticky right-0 bg-slate-50 z-20 shadow-[-6px_0_10px_-4px_rgba(0,0,0,0.06)] border-l border-slate-100/80">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse($products as $product)
                @php
                    $isOutOfStock = $product->stock <= 0;
                    $isLowStock = !$isOutOfStock && $product->stock < 10;
                    $isExpired = false;
                    $isNearExpiration = false;
                    $isNotExpired = false;
                    if ($product->expiration_date) {
                        $expiryDate = \Carbon\Carbon::parse($product->expiration_date)->startOfDay();
                        $today = now()->startOfDay();
                        if ($expiryDate->lt($today)) {
                            $isExpired = true;
                        } elseif (!$isOutOfStock && $expiryDate->lte($today->copy()->addDays(7))) {
                            $isNearExpiration = true;
                        } elseif (!$isOutOfStock) {
                            $isNotExpired = true;
                        }
                    }
                @endphp
                <tr id="product-{{ $product->id }}" data-record-id="product-{{ $product->id }}" data-record-name="{{ strtolower($product->product_name . ' (' . $product->size . ')') }}" class="group hover:bg-slate-50/50 transition-colors record-highlight-target">
                    <!-- Product details -->
                    <td class="px-3.5 py-3">
                        <div class="flex items-center space-x-2.5">
                            <div class="h-9 w-9 rounded-lg bg-slate-100 overflow-hidden border border-slate-100 flex items-center justify-center flex-shrink-0">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->product_name }}" class="h-full w-full object-cover">
                                @else
                                    <i class="bi bi-image text-slate-400 text-base"></i>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 leading-tight truncate max-w-[130px] sm:max-w-[170px]">{{ $product->product_name }}</div>
                                @if($product->description)
                                    <div class="text-[10px] text-slate-400 max-w-[130px] sm:max-w-[170px] truncate" title="{{ $product->description }}">{{ $product->description }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <!-- Category -->
                    <td class="px-3 py-3 whitespace-nowrap text-xs font-semibold text-slate-600">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700 whitespace-nowrap">
                            {{ $product->category->category_name }}
                        </span>
                    </td>
                    <!-- Attributes -->
                    <td class="px-3 py-3">
                        <div class="flex flex-col gap-0.5">
                            <div class="flex flex-wrap items-center gap-1">
                                @if($product->flavor)
                                    <span class="inline-flex items-center px-1.5 py-0.25 rounded text-[9px] font-bold bg-sky-50 text-sky-700 border border-sky-100 whitespace-nowrap">
                                        <i class="bi bi-tag-fill mr-0.5 text-[8px] text-sky-500"></i> {{ $product->flavor }}
                                    </span>
                                @endif
                                @foreach($product->available_milk_types as $mType)
                                    <span class="inline-flex items-center px-1.5 py-0.25 rounded text-[9px] font-semibold {{ $mType == 'Carabao' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }} whitespace-nowrap">
                                        {{ $mType }} Milk
                                    </span>
                                @endforeach
                            </div>
                            <span class="text-[10px] text-slate-500 font-medium whitespace-nowrap">Size: {{ implode(', ', $product->available_sizes) }}</span>
                        </div>
                    </td>
                    <!-- Price -->
                    <td class="px-3 py-3 whitespace-nowrap text-xs font-bold text-slate-950">
                        ₱{{ number_format($product->price, 2) }}
                    </td>
                    <!-- Current Stock -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="flex flex-col space-y-0.5">
                            <span class="text-xs font-bold {{ $isOutOfStock ? 'text-slate-400' : ($isLowStock ? 'text-rose-600' : 'text-slate-900') }}">{{ $product->stock }} units</span>
                            @if($isOutOfStock)
                                <span class="inline-flex items-center w-max px-1.5 py-0.25 rounded text-[8px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    <i class="bi bi-x-octagon mr-0.5"></i> Out of Stock
                                </span>
                            @elseif($isLowStock)
                                <span class="inline-flex items-center w-max px-1.5 py-0.25 rounded text-[8px] font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                    <i class="bi bi-exclamation-triangle mr-0.5"></i> Low Stock
                                </span>
                            @else
                                <span class="inline-flex items-center w-max px-1.5 py-0.25 rounded text-[8px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    <i class="bi bi-check2 mr-0.5"></i> Healthy
                                </span>
                            @endif
                        </div>
                    </td>
                    <!-- Manufacture Date -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        @if($product->manufacturing_date)
                            <div class="flex items-center space-x-1 text-xs font-medium text-slate-700">
                                <i class="bi bi-calendar2-check text-sky-500 text-[10px]"></i>
                                <span>{{ \Carbon\Carbon::parse($product->manufacturing_date)->format('M d, Y') }}</span>
                            </div>
                        @else
                            <span class="text-xs text-slate-400">N/A</span>
                        @endif
                    </td>
                    <!-- Expiry Info -->
                    <td class="px-3 py-3 whitespace-nowrap">
                        <div class="flex flex-col space-y-0.5">
                            @if($product->expiration_date)
                                <div class="flex items-center space-x-1 text-xs font-bold {{ $isExpired ? 'text-rose-600' : ($isNearExpiration ? 'text-amber-700' : 'text-slate-800') }}">
                                    <i class="bi bi-clock-history {{ $isExpired ? 'text-rose-500' : ($isNearExpiration ? 'text-amber-500' : 'text-emerald-500') }} text-[10px]"></i>
                                    <span>{{ \Carbon\Carbon::parse($product->expiration_date)->format('M d, Y') }}</span>
                                </div>
                                @if($isExpired)
                                    <span class="inline-flex items-center w-max px-1.5 py-0.25 rounded text-[8px] font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                        <i class="bi bi-x-circle mr-0.5"></i> Expired
                                    </span>
                                @elseif($isNearExpiration)
                                    <span class="inline-flex items-center w-max px-1.5 py-0.25 rounded text-[8px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                        <i class="bi bi-clock mr-0.5"></i> Near Expiry
                                    </span>
                                @else
                                    <span class="inline-flex items-center w-max px-1.5 py-0.25 rounded text-[8px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <i class="bi bi-check-circle mr-0.5"></i> Not Expired
                                    </span>
                                @endif
                            @else
                                <span class="text-xs text-slate-400">N/A</span>
                                <span class="inline-flex items-center w-max px-1.5 py-0.25 rounded text-[8px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <i class="bi bi-tag mr-0.5 text-[7px]"></i> Unlabelled
                                </span>
                            @endif
                        </div>
                    </td>
                    <!-- Status -->
                    <td class="px-3 py-3 whitespace-nowrap text-center">
                        @if(empty($product->expiration_date))
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200" title="Missing Expiration Date — Blocked from Active status until expiry is provided">
                                <i class="bi bi-slash-circle text-[9px]"></i> Inactive
                            </span>
                        @elseif($product->stock == 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-400 border border-slate-200">
                                <i class="bi bi-dash-circle text-[9px]"></i> Inactive
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $product->status == 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-50 text-slate-400 border border-slate-100' }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        @endif
                    </td>
                    <!-- Actions -->
                    <td class="px-3 py-3 whitespace-nowrap text-center text-xs font-medium sticky right-0 bg-white group-hover:bg-slate-50/90 transition-colors z-10 shadow-[-6px_0_10px_-4px_rgba(0,0,0,0.06)] border-l border-slate-100/80">
                        <div class="flex items-center justify-center space-x-1">
                            <!-- Show/QR code -->
                            <a href="{{ route('admin.products.show', $product->id) }}" class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-all" title="View Details & QR Code">
                                <i class="bi bi-qr-code text-base"></i>
                            </a>
                             <!-- Edit -->
                             <button onclick="toggleEditProductModal({{ $product->id }})" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all cursor-pointer" title="Edit Product">
                                 <i class="bi bi-pencil-square text-base"></i>
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
                                         <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" id="editProductForm_{{ $product->id }}" onsubmit="return validateProductDates({{ $product->id }}, event)" class="flex-grow overflow-y-auto p-6 space-y-5">
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

                                                 <!-- Milk Type Checklist -->
                                                 <div class="space-y-1 text-left">
                                                     <label class="block text-xs font-bold text-slate-700">Available Milk Type Source</label>
                                                     @php
                                                         $prodMilks = $product->available_milk_types;
                                                     @endphp
                                                     <div class="grid grid-cols-2 gap-2">
                                                         @foreach(['Carabao' => 'Carabao Milk', 'Cow' => "Cow's Milk"] as $mVal => $mLabel)
                                                             <label class="flex items-center space-x-2 px-3 py-2 bg-slate-50 hover:bg-sky-50/40 border border-slate-200 has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50/60 rounded-xl cursor-pointer transition-all select-none">
                                                                 <input type="checkbox" name="milk_types[]" value="{{ $mVal }}" {{ in_array($mVal, $prodMilks) ? 'checked' : '' }}
                                                                     class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer">
                                                                 <span class="text-xs font-bold text-slate-800">{{ $mLabel }}</span>
                                                             </label>
                                                         @endforeach
                                                     </div>
                                                 </div>

                                                 <!-- Size Checklist -->
                                                 <div class="space-y-1 text-left">
                                                     <label class="block text-xs font-bold text-slate-700">Available Product Size</label>
                                                     @php
                                                         $prodSizes = $product->available_sizes;
                                                     @endphp
                                                     <div class="grid grid-cols-3 gap-1.5">
                                                         @foreach(['Small', 'Medium', 'Large'] as $sVal)
                                                             <label class="flex items-center space-x-1.5 px-2.5 py-2 bg-slate-50 hover:bg-sky-50/40 border border-slate-200 has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50/60 rounded-xl cursor-pointer transition-all select-none">
                                                                 <input type="checkbox" name="sizes[]" value="{{ $sVal }}" {{ in_array($sVal, $prodSizes) ? 'checked' : '' }}
                                                                     class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer">
                                                                 <span class="text-xs font-bold text-slate-800">{{ $sVal }}</span>
                                                             </label>
                                                         @endforeach
                                                     </div>
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
                                                         oninput="syncProductDates({{ $product->id }})" onchange="syncProductDates({{ $product->id }})"
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all">
                                                 </div>

                                                 <!-- Expiration Date -->
                                                 @php
                                                     $mfgVal = old('manufacturing_date', $product->manufacturing_date ? \Carbon\Carbon::parse($product->manufacturing_date)->format('Y-m-d') : '');
                                                     $minExpiry = $mfgVal ? \Carbon\Carbon::parse($mfgVal)->addDay()->format('Y-m-d') : '';
                                                 @endphp
                                                 <div class="space-y-1 text-left">
                                                     <label for="expiration_date_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Expiration Date</label>
                                                     <input type="date" name="expiration_date" id="expiration_date_{{ $product->id }}" value="{{ old('expiration_date', $product->expiration_date ? \Carbon\Carbon::parse($product->expiration_date)->format('Y-m-d') : '') }}"
                                                         @if($minExpiry) min="{{ $minExpiry }}" @endif
                                                         oninput="syncProductDates({{ $product->id }})" onchange="syncProductDates({{ $product->id }})"
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all">
                                                     <p id="date_error_{{ $product->id }}" class="hidden text-[11px] font-semibold text-rose-600 flex items-center gap-1 mt-1">
                                                         <i class="bi bi-exclamation-circle-fill"></i>
                                                         <span id="date_error_text_{{ $product->id }}">Expiration date must be strictly after the manufacturing date.</span>
                                                     </p>
                                                 </div>

                                                 <!-- Status -->
                                                 <div class="space-y-1 sm:col-span-2 text-left">
                                                     <div class="flex items-center justify-between">
                                                         <label for="status_{{ $product->id }}" class="block text-xs font-bold text-slate-700">Catalog Visibility Status</label>
                                                         <span id="status_lock_notice_{{ $product->id }}" class="{{ $product->expiration_date ? 'hidden' : '' }} text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 flex items-center gap-1">
                                                             <i class="bi bi-lock-fill text-amber-500"></i> Expiration Date Required for Active
                                                         </span>
                                                     </div>
                                                     <select name="status" id="status_{{ $product->id }}" required onchange="handleStatusChange({{ $product->id }}, this)"
                                                         class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-slate-950 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs transition-all focus:bg-white cursor-pointer font-medium">
                                                         <option value="active" id="opt_active_{{ $product->id }}" {{ old('status', $product->status) == 'active' && $product->expiration_date ? 'selected' : '' }} {{ empty($product->expiration_date) ? 'disabled' : '' }}>
                                                             Active (Visible to Storefront)
                                                         </option>
                                                         <option value="inactive" id="opt_inactive_{{ $product->id }}" {{ old('status', $product->status) == 'inactive' || empty($product->expiration_date) ? 'selected' : '' }}>
                                                             Inactive (Hidden)
                                                         </option>
                                                     </select>
                                                     <p id="status_help_{{ $product->id }}" class="text-[10px] text-slate-400 mt-0.5">
                                                         Unlabelled products lacking expiration data cannot be set to Active or sold to customers.
                                                     </p>
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
                                                 <button type="submit" id="submitBtn_{{ $product->id }}" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-md shadow-sky-100 transition-all cursor-pointer">
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
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all cursor-pointer" title="Delete Product">
                                    <i class="bi bi-trash3 text-base"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center">
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

    function syncProductDates(productId, showErrorImmediately = true) {
        const mfgInput = document.getElementById('manufacturing_date_' + productId);
        const expInput = document.getElementById('expiration_date_' + productId);
        const errContainer = document.getElementById('date_error_' + productId);
        const errText = document.getElementById('date_error_text_' + productId);
        const submitBtn = document.getElementById('submitBtn_' + productId);
        const optActive = document.getElementById('opt_active_' + productId);
        const statusSelect = document.getElementById('status_' + productId);
        const lockNotice = document.getElementById('status_lock_notice_' + productId);

        if (!mfgInput || !expInput) return true;

        const mfgVal = mfgInput.value;
        const expVal = expInput.value;

        // Block setting Catalog Visibility Status to Active if expiration date is empty
        if (!expVal) {
            if (optActive) {
                optActive.disabled = true;
                optActive.textContent = 'Active (Requires Expiration Date)';
            }
            if (statusSelect && statusSelect.value === 'active') {
                statusSelect.value = 'inactive';
            }
            if (lockNotice) {
                lockNotice.classList.remove('hidden');
            }
        } else {
            if (optActive) {
                optActive.disabled = false;
                optActive.textContent = 'Active (Visible to Storefront)';
            }
            if (lockNotice) {
                lockNotice.classList.add('hidden');
            }
        }

        if (mfgVal) {
            // Parse manufacturing date
            const parts = mfgVal.split('-');
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const day = parseInt(parts[2], 10);

            // Earliest allowable expiration date is day + 1 (strictly after manufacturing date)
            const nextDay = new Date(year, month - 1, day + 1);
            const nextY = nextDay.getFullYear();
            const nextM = String(nextDay.getMonth() + 1).padStart(2, '0');
            const nextD = String(nextDay.getDate()).padStart(2, '0');
            const minExpiryStr = `${nextY}-${nextM}-${nextD}`;

            // This makes all dates on or before manufacturing date unclickable and greyed out in the datepicker
            expInput.min = minExpiryStr;

            // Real-time validation check: expiry date cannot be on or before manufacturing date
            if (expVal && expVal <= mfgVal) {
                if (showErrorImmediately) {
                    if (errContainer && errText) {
                        const mfgDateObj = new Date(year, month - 1, day);
                        const formattedMfg = mfgDateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        errText.textContent = `Expiration date must be strictly after the manufacturing date (${formattedMfg}).`;
                        errContainer.classList.remove('hidden');
                    }
                    expInput.classList.remove('border-slate-200', 'bg-slate-50', 'focus:border-sky-500', 'focus:ring-sky-500/20');
                    expInput.classList.add('border-rose-500', 'bg-rose-50/40', 'focus:border-rose-500', 'focus:ring-rose-500/20', 'text-rose-900');
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            }
        } else {
            expInput.removeAttribute('min');
        }

        // Valid state
        if (errContainer) {
            errContainer.classList.add('hidden');
        }
        expInput.classList.remove('border-rose-500', 'bg-rose-50/40', 'focus:border-rose-500', 'focus:ring-rose-500/20', 'text-rose-900');
        expInput.classList.add('border-slate-200', 'bg-slate-50', 'focus:border-sky-500', 'focus:ring-sky-500/20');

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        return true;
    }

    function handleStatusChange(productId, select) {
        const expInput = document.getElementById('expiration_date_' + productId);
        if (select.value === 'active' && (!expInput || !expInput.value)) {
            select.value = 'inactive';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Expiration Date Required',
                    text: 'Products cannot be set to Active without a valid expiration date. Please enter an expiration date first.',
                    confirmButtonColor: '#0284c7'
                });
            } else {
                alert('Products cannot be set to Active without a valid expiration date. Please enter an expiration date first.');
            }
            if (expInput) expInput.focus();
        }
    }

    function validateProductDates(productId, event) {
        const statusSelect = document.getElementById('status_' + productId);
        const expInput = document.getElementById('expiration_date_' + productId);

        // Disallow saving Active if expiration date is missing
        if (statusSelect && statusSelect.value === 'active' && (!expInput || !expInput.value)) {
            if (event) event.preventDefault();
            statusSelect.value = 'inactive';
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Active Status Blocked',
                    text: 'A valid expiration date is required before setting Catalog Visibility Status to Active.',
                    confirmButtonColor: '#0284c7'
                });
            } else {
                alert('A valid expiration date is required before setting Catalog Visibility Status to Active.');
            }
            if (expInput) expInput.focus();
            return false;
        }

        const isValid = syncProductDates(productId, true);
        if (!isValid) {
            if (event) event.preventDefault();
            if (expInput) {
                expInput.focus();
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Expiration Date',
                    text: 'Expiration date must be strictly after the manufacturing date.',
                    confirmButtonColor: '#0284c7'
                });
            }
            return false;
        }
        return true;
    }

    function toggleEditProductModal(id) {
        const modal = document.getElementById('editProductModal-' + id);
        if (modal) {
            modal.classList.toggle('hidden');
            if (!modal.classList.contains('hidden')) {
                syncProductDates(id, true);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Initialize date constraints for all products rendered on page
        document.querySelectorAll('[id^="manufacturing_date_"]').forEach(input => {
            const prodId = input.id.replace('manufacturing_date_', '');
            syncProductDates(prodId, false);
        });

        const urlParams = new URLSearchParams(window.location.search);
        const editProductId = urlParams.get('edit_product_id');
        if (editProductId) {
            toggleEditProductModal(editProductId);
        }
    });
</script>
@endsection
@endsection
