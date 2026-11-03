@extends('layouts.admin')

@section('title', 'Product Categories')
@section('page_title', 'Product Categories')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column: Add Category Card -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sticky top-24">
            <h3 class="text-sm font-bold text-slate-950 mb-1">Add New Category</h3>
            <p class="text-xs text-slate-500 mb-6">Create a new product grouping category for the storefront.</p>
            
            <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="category_name" class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Category Name</label>
                    <input type="text" name="category_name" id="category_name" required value="{{ old('category_name') }}"
                        class="mt-1.5 block w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500/10 focus:border-sky-500 text-xs transition-all placeholder-slate-400 focus:bg-white"
                        placeholder="e.g. Cheese, Yogurts">
                    @error('category_name')
                        <p class="mt-1 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                
                <button type="submit" class="w-full flex items-center justify-center space-x-1.5 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-sky-600 hover:bg-sky-500 transition-all shadow-md shadow-sky-100 cursor-pointer">
                    <i class="bi bi-plus-lg"></i>
                    <span>Save Category</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Category List Table -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/20">
                <div>
                    <h3 class="text-sm font-bold text-slate-950">Existing Categories</h3>
                    <p class="text-xs text-slate-500">List of all product categories registered in the system.</p>
                </div>
                <span class="bg-slate-100 text-slate-600 text-xs px-2.5 py-1 rounded-full font-semibold border border-slate-200/50">
                    {{ $categories->count() }} Total
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-4 px-6">ID</th>
                            <th class="py-4 px-6">Category Name</th>
                            <th class="py-4 px-6 text-center">Products Linked</th>
                            <th class="py-4 px-6">Date Created</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white text-xs text-slate-600">
                        @forelse($categories as $category)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6 font-mono text-slate-400">#{{ $category->id }}</td>
                            <td class="py-4 px-6 font-bold text-slate-900">{{ $category->category_name }}</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $category->products_count > 0 ? 'bg-sky-50 text-sky-700 border border-sky-100/50 shadow-2xs' : 'bg-slate-50 text-slate-400 border border-slate-100/50' }}">
                                    {{ $category->products_count }} {{ Str::plural('product', $category->products_count) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-500">{{ $category->created_at->format('M d, Y') }}</td>
                            <td class="py-4 px-6 text-right">
                                @if($category->products_count == 0)
                                    <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this category?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all cursor-pointer" title="Delete Category">
                                            <i class="bi bi-trash text-sm"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[10px] text-slate-400 font-medium">In Use</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i class="bi bi-tags text-4xl text-slate-200"></i>
                                    <p class="text-sm font-semibold text-slate-800">No Categories Found</p>
                                    <p class="text-xs text-slate-500">Create a new category using the form on the left.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
