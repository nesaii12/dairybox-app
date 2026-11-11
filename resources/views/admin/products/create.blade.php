@extends('layouts.admin')

@section('title', 'Add New Product')
@section('page_title', 'Add New Product')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
            <i class="bi bi-arrow-left mr-1"></i> Back to Products List
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <div>
            <h3 class="text-sm font-bold text-slate-950">Register New Dairy Product</h3>
            <p class="text-xs text-slate-500">Configure product specifications, select or upload flavor photos, and set initial FIFO stock.</p>
        </div>

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-6" id="productForm" onsubmit="return validateCreateDates(event)">
            @csrf

            <!-- Product Details Group -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Product Name -->
                <div class="sm:col-span-2">
                    <label for="product_name" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Product Name</label>
                    <input type="text" name="product_name" id="product_name" required value="{{ old('product_name') }}"
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400"
                        placeholder="e.g. Carabao Milk Ice Cream - Mango">
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

                <!-- Milk Type Checklist -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Available Milk Type Source</label>
                    @php
                        $oldMilks = (array) old('milk_types', ['Cow']);
                    @endphp
                    <div class="mt-1.5 grid grid-cols-2 gap-2.5">
                        @foreach(['Carabao' => 'Carabao Milk', 'Cow' => "Cow's Milk"] as $mVal => $mLabel)
                            <label class="flex items-center space-x-2.5 px-3 py-2.5 bg-slate-50 hover:bg-sky-50/40 border border-slate-200 has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50/60 rounded-xl cursor-pointer transition-all select-none">
                                <input type="checkbox" name="milk_types[]" value="{{ $mVal }}" {{ in_array($mVal, $oldMilks) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer">
                                <span class="text-xs font-bold text-slate-800">{{ $mLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('milk_types')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Size Checklist -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Available Product Size</label>
                    @php
                        $oldSizes = (array) old('sizes', ['Medium']);
                    @endphp
                    <div class="mt-1.5 grid grid-cols-3 gap-2">
                        @foreach(['Small', 'Medium', 'Large'] as $sVal)
                            <label class="flex items-center space-x-2 px-3 py-2.5 bg-slate-50 hover:bg-sky-50/40 border border-slate-200 has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50/60 rounded-xl cursor-pointer transition-all select-none">
                                <input type="checkbox" name="sizes[]" value="{{ $sVal }}" {{ in_array($sVal, $oldSizes) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer">
                                <span class="text-xs font-bold text-slate-800">{{ $sVal }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('sizes')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Product Flavor Selection -->
                <div class="sm:col-span-2 p-4 bg-slate-50/70 border border-slate-200/80 rounded-2xl">
                    <div class="flex items-center justify-between mb-2">
                        <label for="flavor" class="block text-[11px] font-bold text-slate-800 uppercase tracking-wider flex items-center space-x-1.5">
                            <i class="bi bi-palette2 text-sky-600"></i>
                            <span>Product Flavor</span>
                        </label>
                        <span class="text-[10px] text-slate-400">Select preset or type a custom flavor</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                        <div>
                            <select id="flavor_preset_select" onchange="handlePresetDropdownChange(this)"
                                class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs font-medium cursor-pointer">
                                <option value="">-- Choose Common Flavor --</option>
                                @foreach($flavorPresets as $fp)
                                    <option value="{{ $fp['name'] }}" data-image="{{ $fp['image'] }}" {{ old('flavor') == $fp['name'] ? 'selected' : '' }}>
                                        {{ $fp['name'] }} ({{ $fp['category'] }})
                                    </option>
                                @endforeach
                                <option value="CUSTOM">Custom Flavor (Type below)...</option>
                            </select>
                        </div>

                        <div>
                            <input type="text" name="flavor" id="flavor" value="{{ old('flavor') }}"
                                class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all placeholder-slate-400 font-semibold"
                                placeholder="Flavor name (e.g. Ube, Matcha, Mango...)">
                        </div>
                    </div>
                    @error('flavor')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="sm:col-span-2">
                    <label for="description" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Description</label>
                    <textarea name="description" id="description" rows="2"
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all focus:bg-white placeholder-slate-400"
                        placeholder="Write dynamic descriptions detailing flavor, freshness...">{{ old('description') }}</textarea>
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
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                    <div>
                        <label for="initial_stock" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Initial Stock Count</label>
                        <input type="number" name="initial_stock" id="initial_stock" required value="{{ old('initial_stock', 0) }}" min="0"
                            class="mt-1.5 block w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all placeholder-slate-400">
                        @error('initial_stock')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="manufacturing_date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Manufacturing Date</label>
                        <input type="date" name="manufacturing_date" id="manufacturing_date" value="{{ old('manufacturing_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}"
                            oninput="syncCreateDates()" onchange="syncCreateDates()"
                            class="mt-1.5 block w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all">
                        @error('manufacturing_date')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    @php
                        $createMfgVal = old('manufacturing_date', date('Y-m-d'));
                        $createMinExpiry = $createMfgVal ? \Carbon\Carbon::parse($createMfgVal)->addDay()->format('Y-m-d') : '';
                    @endphp
                    <div>
                        <label for="expiration_date" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Expiration Date</label>
                        <input type="date" name="expiration_date" id="expiration_date" value="{{ old('expiration_date') }}"
                            @if($createMinExpiry) min="{{ $createMinExpiry }}" @endif
                            oninput="syncCreateDates()" onchange="syncCreateDates()"
                            class="mt-1.5 block w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all">
                        <p id="create_date_error" class="hidden text-[11px] font-semibold text-rose-600 flex items-center gap-1 mt-1">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span id="create_date_error_text">Expiration date must be strictly after the manufacturing date.</span>
                        </p>
                        @error('expiration_date')
                            <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Product Image / Custom Upload Section -->
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-900 uppercase tracking-wider">Product Photo</label>
                    <p class="text-[11px] text-slate-500">Upload a custom high-resolution photo for this product.</p>
                </div>

                <!-- Custom File Upload Dropzone -->
                <div id="uploadDropzoneContainer">
                    <div id="dropzone"
                        class="relative border-2 border-dashed border-slate-300 hover:border-sky-400 rounded-2xl p-6 sm:p-8 text-center bg-slate-50/50 hover:bg-sky-50/20 transition-all cursor-pointer">
                        <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/webp,image/jpg" onchange="handleCustomFileUpload(this)"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="flex flex-col items-center justify-center space-y-2 pointer-events-none">
                            <div class="h-12 w-12 rounded-2xl bg-sky-100/70 text-sky-600 flex items-center justify-center text-xl shadow-2xs">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                            </div>
                            <div class="text-xs font-bold text-slate-700">
                                <span>Click to browse</span> or drag and drop photo
                            </div>
                            <p class="text-[10px] text-slate-400">Supports PNG, JPG, JPEG, WEBP (Max 2MB)</p>
                        </div>
                    </div>
                </div>

                <!-- Active Photo Live Preview Banner -->
                <div id="previewContainer" class="p-4 bg-slate-50 border border-slate-200/90 rounded-2xl flex items-center justify-between hidden">
                    <div class="flex items-center space-x-3.5">
                        <div class="h-14 w-14 rounded-xl overflow-hidden bg-white border border-slate-200 shadow-2xs flex-shrink-0">
                            <img id="previewImage" src="" alt="Selected Product Photo" class="h-full w-full object-cover">
                        </div>
                        <div>
                            <span class="inline-flex items-center text-[10px] font-bold text-sky-700 bg-sky-100/80 px-2 py-0.5 rounded-md mb-1">
                                <i class="bi bi-image mr-1"></i> Active Selected Photo
                            </span>
                            <h5 id="previewTitle" class="text-xs font-bold text-slate-900">Custom Photo Selected</h5>
                            <p id="previewSubtitle" class="text-[10px] text-slate-500">This photo will be registered for the product.</p>
                        </div>
                    </div>
                    <div>
                        <button type="button" onclick="clearSelectedPhoto()" class="px-3 py-1.5 rounded-lg border border-slate-200 text-rose-600 bg-white hover:bg-rose-50 text-[11px] font-bold transition-all cursor-pointer">
                            <i class="bi bi-x-circle mr-1"></i> Clear
                        </button>
                    </div>
                </div>

                @error('image')
                    <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.products.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 text-xs font-bold transition-all">
                    Cancel
                </a>
                <button type="submit" id="createSubmitBtn" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 shadow-md shadow-sky-100 transition-all cursor-pointer">
                    Register Product
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Dropdown change handler
    function handlePresetDropdownChange(select) {
        const val = select.value;
        const flavorInput = document.getElementById('flavor');
        if (!val) return;
        
        if (val === 'CUSTOM') {
            flavorInput.value = '';
            flavorInput.focus();
            return;
        }

        flavorInput.value = val;
    }

    // Custom file upload handler
    function handleCustomFileUpload(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];

            const reader = new FileReader();
            reader.onload = function(e) {
                const previewContainer = document.getElementById('previewContainer');
                const previewImage = document.getElementById('previewImage');
                const previewTitle = document.getElementById('previewTitle');
                const previewSubtitle = document.getElementById('previewSubtitle');

                previewImage.src = e.target.result;
                previewTitle.textContent = 'Custom Upload: ' + file.name;
                previewSubtitle.textContent = (file.size / 1024).toFixed(1) + ' KB (' + file.type + ')';
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    // Clear selected photo
    function clearSelectedPhoto() {
        const fileInput = document.getElementById('imageInput');
        if (fileInput) fileInput.value = '';
        document.getElementById('previewContainer').classList.add('hidden');
    }

    function syncCreateDates(showErrorImmediately = true) {
        const mfgInput = document.getElementById('manufacturing_date');
        const expInput = document.getElementById('expiration_date');
        const errContainer = document.getElementById('create_date_error');
        const errText = document.getElementById('create_date_error_text');
        const submitBtn = document.getElementById('createSubmitBtn');

        if (!mfgInput || !expInput) return true;

        const mfgVal = mfgInput.value;
        const expVal = expInput.value;

        if (mfgVal) {
            const parts = mfgVal.split('-');
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const day = parseInt(parts[2], 10);

            const nextDay = new Date(year, month - 1, day + 1);
            const nextY = nextDay.getFullYear();
            const nextM = String(nextDay.getMonth() + 1).padStart(2, '0');
            const nextD = String(nextDay.getDate()).padStart(2, '0');
            const minExpiryStr = `${nextY}-${nextM}-${nextD}`;

            expInput.min = minExpiryStr;

            if (expVal && expVal <= mfgVal) {
                if (showErrorImmediately) {
                    if (errContainer && errText) {
                        const mfgDateObj = new Date(year, month - 1, day);
                        const formattedMfg = mfgDateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        errText.textContent = `Expiration date must be strictly after the manufacturing date (${formattedMfg}).`;
                        errContainer.classList.remove('hidden');
                    }
                    expInput.classList.remove('border-slate-200', 'bg-white', 'focus:border-sky-500', 'focus:ring-sky-500/10');
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

        if (errContainer) {
            errContainer.classList.add('hidden');
        }
        expInput.classList.remove('border-rose-500', 'bg-rose-50/40', 'focus:border-rose-500', 'focus:ring-rose-500/20', 'text-rose-900');
        expInput.classList.add('border-slate-200', 'bg-white', 'focus:border-sky-500', 'focus:ring-sky-500/10');

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        return true;
    }

    function validateCreateDates(event) {
        const isValid = syncCreateDates(true);
        if (!isValid) {
            if (event) event.preventDefault();
            const expInput = document.getElementById('expiration_date');
            if (expInput) expInput.focus();

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

    document.addEventListener('DOMContentLoaded', () => {
        syncCreateDates(false);
    });
</script>
@endsection
