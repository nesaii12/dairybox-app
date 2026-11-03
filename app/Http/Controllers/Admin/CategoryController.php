<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255|unique:categories,category_name',
        ]);

        $category = Category::create([
            'category_name' => $request->category_name,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'CATEGORY_CREATED',
            'details' => "Created category: {$category->category_name}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return redirect()->route('admin.categories.index')->with('error', 'Cannot delete a category that still contains products.');
        }

        $name = $category->category_name;
        $category->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'CATEGORY_DELETED',
            'details' => "Deleted category: {$name}",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}
