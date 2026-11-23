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

        } elseif ($filter === 'expired') {
            // Products with at least one active batch already past its expiration date
            $productQuery->where('stock', '>', 0)
                ->whereHas('inventories', function ($q) use ($today) {
                    $q->where('remaining_stock', '>', 0)
                      ->whereNotNull('expiration_date')
                      ->whereDate('expiration_date', '<', $today);
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
            // "All" filter — place 0 stock products in the first rows, then arrange by date manufactured
            $mfgDateExpr = "COALESCE((
                SELECT inventories.manufacturing_date
                FROM inventories
                WHERE inventories.product_id = products.id
                  AND inventories.remaining_stock > 0
                  AND inventories.manufacturing_date IS NOT NULL
                ORDER BY inventories.created_at ASC
                LIMIT 1
            ), products.manufacturing_date)";

            $productQuery
                ->orderByRaw("CASE WHEN products.stock <= 0 THEN 0 ELSE 1 END ASC")
                ->orderByRaw("CASE WHEN {$mfgDateExpr} IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("{$mfgDateExpr} ASC")
                ->orderBy('products.expiration_date', 'asc')
                ->orderBy('products.id', 'asc');
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
        $isStockIn = $request->input('adjustment_type') === 'in';

        $expirationRules = ['exclude_unless:adjustment_type,in', 'required_if:adjustment_type,in', 'nullable', 'date'];
        if ($isStockIn && $request->filled('manufacturing_date')) {
            $expirationRules[] = 'after_or_equal:manufacturing_date';
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'manufacturing_date' => 'exclude_unless:adjustment_type,in|nullable|date|before_or_equal:today',
            'expiration_date' => $expirationRules,
            'remarks' => 'required|string|max:255',
        ], [
            'expiration_date.after_or_equal' => 'Expiration date must be on or after the manufacturing date.',
        ]);

        $productId = $request->product_id;
        $qty = $request->quantity;
        $remarks = $request->remarks;
        $product = Product::findOrFail($productId);

        try {
            if ($request->adjustment_type === 'in') {
                if (((int) $product->stock + (int) $qty) > Product::MAX_STOCK_LIMIT) {
                    $limitMsg = InventoryService::stockLimitExceededMessage($product, (int) $qty);
                    return redirect()->back()
                        ->withErrors(['quantity' => $limitMsg])
                        ->with('error', $limitMsg)
                        ->withInput();
                }

                InventoryService::addStock($productId, $qty, $request->expiration_date, $remarks, $request->manufacturing_date, true);
                
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'STOCK_ADJUSTMENT_IN',
                    'details' => "Stock added: {$product->product_name} (+{$qty} units, Mfg: {$request->manufacturing_date}, Expiry: {$request->expiration_date}, Remarks: {$remarks})",
                    'ip_address' => $request->ip(),
                ]);

                \App\Services\ExpiryNotificationService::checkProduct($product->fresh());

                return redirect()->route('admin.inventory.index')->with('success', "Stock added successfully for {$product->product_name}.");
            } else {
                // Check if available stock (Physical - Reserved) is sufficient
                $availableStock = InventoryService::getAvailableStock($product);
                if ($availableStock < $qty) {
                    return redirect()->back()->with('error', InventoryService::insufficientStockMessage($product, $availableStock))
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
            return redirect()->back()->with('error', "Inventory adjustment failed: " . $e->getMessage())
                ->withInput();
        }
    }
}
