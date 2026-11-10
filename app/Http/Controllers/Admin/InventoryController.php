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

        $filter = $request->input('filter', 'all');

        $today        = now()->toDateString();
        $sevenDaysOut = now()->addDays(7)->toDateString();
        $recentCutoff = now()->subDay();

        // Build product query filtered by inventory batch criteria
        $productQuery = Product::with(['category', 'inventories' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]);

        if ($filter === 'near_expiry') {
            // Products with at least one active batch expiring within 7 days
            $productQuery->where('stock', '>', 0)
                ->whereHas('inventories', function ($q) use ($today, $sevenDaysOut) {
                    $q->where('remaining_stock', '>', 0)
                      ->whereNotNull('expiration_date')
                      ->whereDate('expiration_date', '>=', $today)
                      ->whereDate('expiration_date', '<=', $sevenDaysOut);
                })
                ->orderBy('expiration_date', 'asc')
                ->orderBy('created_at', 'desc');

        } elseif ($filter === 'new_stock') {
            // Only products with genuinely recent stock-in batches (last 24 hours)
            // that are not cancelled-delivery returns and not already near-expiry or expired
            $productQuery->where('stock', '>', 0)
                ->whereHas('inventories', function ($q) use ($recentCutoff, $sevenDaysOut) {
                    $q->where('remaining_stock', '>', 0)
                      ->where('stock_in', '>', 0)
                      ->where('created_at', '>=', $recentCutoff)
                      ->where(function ($rq) {
                          $rq->whereNull('remarks')
                             ->orWhere('remarks', 'not like', 'Delivery Cancelled/Failed%');
                      })
                      ->where(function ($eq) use ($sevenDaysOut) {
                          $eq->whereNull('expiration_date')
                             ->orWhereDate('expiration_date', '>', $sevenDaysOut);
                      });
                })
                ->orderByDesc(
                    Inventory::select('created_at')
                        ->whereColumn('inventories.product_id', 'products.id')
                        ->where('remaining_stock', '>', 0)
                        ->where('stock_in', '>', 0)
                        ->where('created_at', '>=', $recentCutoff)
                        ->where(function ($rq) {
                            $rq->whereNull('remarks')
                               ->orWhere('remarks', 'not like', 'Delivery Cancelled/Failed%');
                        })
                        ->latest('created_at')
                        ->limit(1)
                )
                ->orderBy('products.created_at', 'desc');

        } else {
            // "All" filter — sort by priority:
            // 1. Near Expiry  2. Not Expired  3. Expired  4. Out of Stock
            $productQuery->orderByRaw("
                CASE
                    WHEN products.stock > 0 AND (
                        (products.expiration_date IS NOT NULL AND DATE(products.expiration_date) >= ? AND DATE(products.expiration_date) <= ?)
                        OR EXISTS (
                            SELECT 1 FROM inventories
                            WHERE inventories.product_id = products.id
                              AND inventories.remaining_stock > 0
                              AND inventories.expiration_date IS NOT NULL
                              AND DATE(inventories.expiration_date) >= ?
                              AND DATE(inventories.expiration_date) <= ?
                        )
                    ) THEN 1
                    WHEN products.stock > 0 AND (
                        (products.expiration_date IS NULL OR DATE(products.expiration_date) > ?)
                        AND NOT EXISTS (
                            SELECT 1 FROM inventories
                            WHERE inventories.product_id = products.id
                              AND inventories.remaining_stock > 0
                              AND inventories.expiration_date IS NOT NULL
                              AND DATE(inventories.expiration_date) < ?
                        )
                    ) THEN 2
                    WHEN products.stock > 0 THEN 3
                    ELSE 4
                END ASC
            ", [$today, $sevenDaysOut, $today, $sevenDaysOut, $sevenDaysOut, $today])
            ->orderByRaw("CASE WHEN products.expiration_date IS NULL THEN 1 ELSE 0 END ASC")
            ->orderBy('products.expiration_date', 'asc')
            ->orderBy('products.created_at', 'desc');
        }

        $products = $productQuery->paginate($perPage)->withQueryString();

        // Get recent inventory transaction history
        $recentTransactions = Inventory::with('product')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.inventory.index', compact('products', 'productsForForm', 'recentTransactions', 'filter'));
    }

    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'manufacturing_date' => 'nullable|date|before_or_equal:today',
            'expiration_date' => 'required_if:adjustment_type,in|nullable|date|after_or_equal:manufacturing_date',
            'remarks' => 'required|string|max:255',
        ]);

        $productId = $request->product_id;
        $qty = $request->quantity;
        $remarks = $request->remarks;
        $product = Product::findOrFail($productId);

        try {
            if ($request->adjustment_type === 'in') {
                InventoryService::addStock($productId, $qty, $request->expiration_date, $remarks, $request->manufacturing_date);
                
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'STOCK_ADJUSTMENT_IN',
                    'details' => "Stock added: {$product->product_name} (+{$qty} units, Mfg: {$request->manufacturing_date}, Expiry: {$request->expiration_date}, Remarks: {$remarks})",
                    'ip_address' => $request->ip(),
                ]);

                \App\Services\ExpiryNotificationService::checkProduct($product->fresh());

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
