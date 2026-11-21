<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\ForecastService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalkInSaleController extends Controller
{
    /**
     * Display a standalone walk-in sale form page.
     */
    public function create()
    {
        $products = Product::where('status', 'active')
            ->with('category')
            ->orderBy('product_name')
            ->get()
            ->map(function ($product) {
                $sizesWithPrices = [];
                foreach ($product->available_sizes as $size) {
                    $sizesWithPrices[] = [
                        'size' => $size,
                        'price' => (float) $product->getPriceForSize($size),
                        'formatted_price' => '₱' . number_format($product->getPriceForSize($size), 2),
                    ];
                }

                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'category_name' => $product->category?->category_name ?? '',
                    'milk_type' => $product->milk_type ?? 'Cow',
                    'size' => $product->size ?? 'Medium',
                    'base_price' => (float) $product->price,
                    'stock' => InventoryService::getAvailableStock($product),
                    'physical_stock' => (int) $product->stock,
                    'reserved_stock' => (int) ($product->reserved_stock ?? 0),
                    'sizes' => $sizesWithPrices,
                    'sizes_summary' => implode(' | ', array_map(fn($sp) => "{$sp['size']}: {$sp['formatted_price']}", $sizesWithPrices)),
                ];
            });

        $customers = User::where('role', 'customer')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.walk_in.create', compact('products', 'customers'));
    }

    /**
     * Store a newly recorded walk-in sale transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_type' => 'nullable|in:walkin,registered',
            'customer_id' => 'nullable|exists:users,id',
            'customer_name' => 'nullable|string|max:100',
            'payment_method' => 'required|in:cash,gcash,card,other',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'nullable|string|max:20',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ], [
            'items.required' => 'Please select at least one product for the walk-in sale.',
            'items.min' => 'Please select at least one product for the walk-in sale.',
            'items.*.product_id.required' => 'Please choose a valid product.',
            'items.*.quantity.min' => 'Product quantity must be at least 1.',
            'items.*.price.min' => 'Unit price cannot be negative.',
        ]);

        // Aggregate requested quantities per product to ensure total doesn't exceed stock
        $aggregatedQuantities = [];
        foreach ($validated['items'] as $item) {
            $pId = (int) $item['product_id'];
            $qty = (int) $item['quantity'];
            $aggregatedQuantities[$pId] = ($aggregatedQuantities[$pId] ?? 0) + $qty;
        }

        $productIds = array_keys($aggregatedQuantities);
        sort($productIds);

        try {
            DB::beginTransaction();

            // Lock products in deterministic order and enforce Available Stock = Physical Stock - Reserved Stock
            $products = Product::whereIn('id', $productIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($aggregatedQuantities as $pId => $totalRequestedQty) {
                $product = $products->get($pId);
                if (!$product) {
                    throw new \Exception('Selected product was not found.');
                }
                $availableStock = InventoryService::getAvailableStock($product);
                if ($availableStock < $totalRequestedQty) {
                    throw new \Exception(InventoryService::insufficientStockMessage($product, $availableStock));
                }
            }

            // 1. Resolve Customer Account
            if (($validated['customer_type'] ?? 'walkin') === 'registered' && !empty($validated['customer_id'])) {
                $customer = User::findOrFail($validated['customer_id']);
            } else {
                $customer = User::firstOrCreate(
                    ['email' => 'walkin@dairybox.local'],
                    [
                        'name' => 'Walk-in Customer',
                        'role' => 'customer',
                        'status' => 'active',
                        'password' => bcrypt(Str::random(32)),
                    ]
                );
            }

            // 2. Compute Totals
            $subtotal = 0.0;
            $totalUnits = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = round(((int) $item['quantity']) * ((float) $item['price']), 2);
                $subtotal += $lineTotal;
                $totalUnits += (int) $item['quantity'];
            }
            $totalAmount = $subtotal; // Walk-in has no delivery fee

            // 3. Generate Order Number
            $orderNumber = 'WALK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
            while (Order::where('order_number', $orderNumber)->exists()) {
                $orderNumber = 'WALK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
            }

            // 4. Fulfillment Details
            $customerNote = !empty($validated['customer_name']) ? trim($validated['customer_name']) : null;
            $address = 'Store Walk-in Counter' . ($customerNote ? " ({$customerNote})" : '');
            if (!empty($validated['notes'])) {
                $address .= ' - Note: ' . trim($validated['notes']);
            }

            // 5. Create Order with order_type = 'Walk-in'
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'order_type' => 'Walk-in',
                'payment_method' => strtolower($validated['payment_method']),
                'payment_status' => 'paid',
                'paid_at' => now(),
                'delivery_address' => $address,
                'schedule_date' => now()->toDateString(),
                'schedule_time' => now()->format('H:i:s'),
                'subtotal' => $subtotal,
                'delivery_fee' => 0.00,
                'total_amount' => $totalAmount,
                'order_status' => 'Delivered',
            ]);

            // 6. Deduct FIFO stock and create OrderItems
            foreach ($validated['items'] as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $item['price'];
                $itemSubtotal = round($quantity * $unitPrice, 2);

                // Deduct stock immediately using existing FIFO logic
                InventoryService::deductStock($productId, $quantity, "Walk-in Sale #{$orderNumber}");

                // Record item in order
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                ]);

                $p = $products->get($productId);
                if ($p) {
                    $itemSize = !empty($item['size']) ? trim($item['size']) : ($p->size ?? 'Medium');
                    Product::saveOrderItemCustomization($orderItem->id, $p->milk_type ?? 'Cow', $itemSize);
                }
            }

            // 7. Record in Sales for immediate revenue and daily sales summaries
            Sale::create([
                'order_id' => $order->id,
                'total_amount' => $totalAmount,
                'sales_date' => now()->toDateString(),
            ]);

            // 8. Record Audit Log
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'WALK_IN_SALE_RECORDED',
                'details' => "Walk-in sale {$orderNumber} recorded: {$totalUnits} item(s), Total: ₱" . number_format($totalAmount, 2) . " via " . strtoupper($validated['payment_method']),
                'ip_address' => $request->ip(),
            ]);

            // 9. Immediately refresh demand forecasts for overall revenue forecast
            try {
                $targetMonth = Carbon::now()->addMonth()->format('Y-m');
                ForecastService::generateAllProducts($targetMonth);
            } catch (\Throwable $th) {
                \Illuminate\Support\Facades\Log::warning('Forecast auto-refresh after walk-in sale failed: ' . $th->getMessage());
            }

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Walk-in sale {$orderNumber} recorded successfully for ₱" . number_format($totalAmount, 2) . "!",
                    'order_number' => $orderNumber,
                    'total_amount' => $totalAmount,
                ]);
            }

            return redirect()->route('admin.dashboard')->with('success', "Walk-in Sale #{$orderNumber} (₱" . number_format($totalAmount, 2) . ") successfully recorded! Stock levels updated and sale included in revenue and forecasts.");

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}
