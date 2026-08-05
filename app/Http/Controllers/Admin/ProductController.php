<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\AuditLog;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', session('per_page_products', 10));
        session(['per_page_products' => $perPage]);

        $products = Product::with('category')->latest()->paginate($perPage)->withQueryString();
        $categories = Category::all();
        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();
        if ($categories->isEmpty()) {
            return redirect()->route('admin.categories.index')->with('error', 'Please create a category first before adding products.');
        }
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'milk_type' => 'required|in:Carabao,Cow',
            'size' => 'required|in:Small,Medium,Large',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'initial_stock' => 'required|integer|min:0',
            'expiration_date' => 'nullable|date|after:today',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'product_name' => $request->product_name,
            'description' => $request->description,
            'milk_type' => $request->milk_type,
            'size' => $request->size,
            'price' => $request->price,
            'image' => $imagePath,
            'stock' => 0, // Stock-in using FIFO service below will sync this correctly
            'expiration_date' => $request->expiration_date,
            'status' => 'active',
        ]);

        // If initial stock is specified, log it in the FIFO inventory batches
        if ($request->initial_stock > 0) {
            InventoryService::addStock(
                $product->id, 
                $request->initial_stock, 
                $request->expiration_date ? $request->expiration_date : null,
                'Initial stock upload'
            );
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'PRODUCT_CREATED',
            'details' => "Created product: {$product->product_name} (Size: {$product->size}, Milk: {$product->milk_type}, Initial Stock: {$request->initial_stock})",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully with FIFO batch registration.');
    }

    public function show(Product $product)
    {
        $qrCode = QrCode::size(200)->generate(route('customer.product', $product->id));
        return view('admin.products.show', compact('product', 'qrCode'));
    }



    public function update(Request $request, Product $product)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'milk_type' => 'required|in:Carabao,Cow',
            'size' => 'required|in:Small,Medium,Large',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'category_id' => $request->category_id,
            'product_name' => $request->product_name,
            'description' => $request->description,
            'milk_type' => $request->milk_type,
            'size' => $request->size,
            'price' => $request->price,
            'status' => $request->status,
        ];

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'PRODUCT_UPDATED',
            'details' => "Updated product: {$product->product_name} (#{$product->id})",
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $name = $product->product_name;
        
        // Delete image if exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'PRODUCT_DELETED',
            'details' => "Deleted product: {$name} (#{$product->id})",
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
