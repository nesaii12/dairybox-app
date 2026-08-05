<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\AuditLog;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        // Get all products to select from
        $productsForForm = Product::where('status', 'active')->get();

        $perPage = $request->input('per_page', session('per_page_inventory', 10));
        session(['per_page_inventory' => $perPage]);

        // Get products with their batches for listing
        $products = Product::with(['category', 'inventories' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])->latest()->paginate($perPage)->withQueryString();

        // Get recent inventory transaction history
        $recentTransactions = Inventory::with('product')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.inventory.index', compact('products', 'productsForForm', 'recentTransactions'));
    }

    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'expiration_date' => 'required_if:adjustment_type,in|nullable|date|after:today',
            'remarks' => 'required|string|max:255',
        ]);

        $productId = $request->product_id;
        $qty = $request->quantity;
        $remarks = $request->remarks;
        $product = Product::findOrFail($productId);

        try {
            if ($request->adjustment_type === 'in') {
                InventoryService::addStock($productId, $qty, $request->expiration_date, $remarks);
                
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'STOCK_ADJUSTMENT_IN',
                    'details' => "Stock added: {$product->product_name} (+{$qty} units, Expiry: {$request->expiration_date}, Remarks: {$remarks})",
                    'ip_address' => $request->ip(),
                ]);

                return redirect()->route('admin.inventory.index')->with('success', "Stock added successfully for {$product->product_name}.");
            } else {
                // Check if total stock is sufficient
                if ($product->stock < $qty) {
                    return redirect()->back()->with('error', "Insufficient total stock for {$product->product_name}. Available: {$product->stock} units.")
                        ->withInput();
                }

                InventoryService::deductStock($productId, $qty, $remarks);

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'STOCK_ADJUSTMENT_OUT',
                    'details' => "Stock removed: {$product->product_name} (-{$qty} units, Remarks: {$remarks})",
                    'ip_address' => $request->ip(),
                ]);

                return redirect()->route('admin.inventory.index')->with('success', "Stock deducted successfully for {$product->product_name} under FIFO resolution.");
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Inventory deduction failed: " . $e->getMessage())
                ->withInput();
        }
    }
}
