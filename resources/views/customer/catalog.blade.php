@extends('layouts.customer')

@section('title', 'Shop')

@section('styles')
<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">

<style>
    body {
        background-color: #ffffff !important;
    }
    .font-serif {
        font-family: 'Playfair Display', Georgia, serif;
    }
</style>
@endsection

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="space-y-1">
        <h1 class="text-4xl font-bold text-slate-900 tracking-tight font-serif">Shop</h1>
        <p class="text-slate-500 text-sm">Fresh from the farm. Updated daily.</p>
    </div>

    <!-- Search & Dynamic Category Pills Form -->
    <form id="filterForm" action="{{ route('customer.catalog') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
        <!-- Search bar input -->
        <div class="relative w-full md:max-w-md">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="bi bi-search text-sm"></i>
            </div>
            <input type="text" name="search" id="search" value="{{ request('search') }}"
                class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all shadow-2xs"
                placeholder="Search milk, ice cream, yogurt...">
        </div>

        <!-- Hidden inputs for active category filter -->
        <input type="hidden" name="category_id" id="category_id_input" value="{{ request('category_id') }}">
        
        <!-- Category Pill Buttons -->
        <div class="flex flex-wrap gap-2 items-center">
            <!-- All Pill -->
            <button type="button" data-category-id="" onclick="selectCategory('', this)" 
                class="category-pill px-4 py-2 rounded-full text-xs font-semibold border transition-all cursor-pointer {{ !request('category_id') ? 'bg-sky-600 border-sky-600 text-white font-bold shadow-sm' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300' }}">
                All
            </button>
            <!-- Dynamic Category Pills from DB -->
            @foreach($categories as $cat)
                <button type="button" data-category-id="{{ $cat->id }}" onclick="selectCategory('{{ $cat->id }}', this)" 
                    class="category-pill px-4 py-2 rounded-full text-xs font-semibold border transition-all cursor-pointer {{ request('category_id') == $cat->id ? 'bg-sky-600 border-sky-600 text-white font-bold shadow-sm' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300' }}">
                    {{ $cat->category_name }}
                </button>
            @endforeach
        </div>
    </form>

    <!-- Product Grid & Pagination Container -->
    <div id="product-grid-container" class="transition-all duration-200">
        @include('customer.partials.product_grid')
    </div>
</div>

<script>
    let debounceTimer;
    const searchInput = document.getElementById('search');
    const filterForm = document.getElementById('filterForm');
    const gridContainer = document.getElementById('product-grid-container');

    function fetchProducts(url = null) {
        // Show loading state (fade out opacity)
        gridContainer.classList.add('opacity-45', 'pointer-events-none');
        
        // Build url from form if not provided
        if (!url) {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                if (value !== '') {
                    params.append(key, value);
                }
            }
            url = `${filterForm.action}?${params.toString()}`;
        }
        
        // Fetch via AJAX
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            gridContainer.innerHTML = data.html;
            gridContainer.classList.remove('opacity-45', 'pointer-events-none');
            
            // Update browser address bar query string
            history.pushState(null, '', url);
        })
        .catch(error => {
            console.error('Error fetching products:', error);
            gridContainer.classList.remove('opacity-45', 'pointer-events-none');
        });
    }

    function selectCategory(id, button) {
        document.getElementById('category_id_input').value = id;
        
        // Update all category pills styles
        const pills = document.querySelectorAll('.category-pill');
        pills.forEach(pill => {
            pill.className = "category-pill px-4 py-2 rounded-full text-xs font-semibold border transition-all cursor-pointer bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300";
        });
        
        // Set clicked pill active styles
        button.className = "category-pill px-4 py-2 rounded-full text-xs font-semibold border transition-all cursor-pointer bg-sky-600 border-sky-600 text-white font-bold shadow-sm";
        
        fetchProducts();
    }

    // Debounce search input
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchProducts();
            }, 350); // 350ms debounce
        });
        
        // Prevent form submit reloading the page on Enter key press
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                fetchProducts();
            }
        });
    }

    // Intercept clicks on pagination links inside the container
    if (gridContainer) {
        gridContainer.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && link.href) {
                const url = new URL(link.href);
                if (url.searchParams.has('page')) {
                    e.preventDefault();
                    fetchProducts(link.href);
                    // Smooth scroll back to filter section
                    filterForm.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    }
</script>
@endsection
