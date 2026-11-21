<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\InventoryService;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CartController extends Controller
{
    /**
     * Retrieve or create the customer's consolidated cart, merging any fragmented carts and duplicate items.
     */
    protected function getCustomerCart(int $customerId): Cart
    {
        $carts = Cart::where('customer_id', $customerId)->orderBy('id', 'asc')->get();

        if ($carts->isEmpty()) {
            return Cart::create(['customer_id' => $customerId]);
        }

        $primaryCart = $carts->first();

        // If customer had fragmented carts (e.g. from previous sessions), merge all items into primaryCart
        if ($carts->count() > 1) {
            $otherCarts = $carts->slice(1);
            foreach ($otherCarts as $other) {
                foreach ($other->cartItems as $item) {
                    $item->update(['cart_id' => $primaryCart->id]);
                }
                $other->delete();
            }
        }

        // Merge duplicate line items with matching product_id, milk_type, and size
        $items = CartItem::where('cart_id', $primaryCart->id)->with('product')->get();
        $grouped = [];

        foreach ($items as $item) {
            $custom = Product::getCartItemCustomization($item->id, $item->product);
            $key = $item->product_id . '_' . ($custom['milk_type'] ?? '') . '_' . ($custom['size'] ?? '');

            if (!isset($grouped[$key])) {
                $grouped[$key] = $item;
            } else {
                $existing = $grouped[$key];
                $freshProduct = $item->product ? $item->product->fresh() : null;
                $maxStock = $freshProduct ? $freshProduct->available_stock : 9999;
                $newQty = min($existing->quantity + $item->quantity, $maxStock);
                $unitPrice = $existing->unit_price;
                $existing->update([
                    'quantity' => $newQty,
                    'subtotal' => $unitPrice * $newQty,
                ]);
                $item->delete();
            }
        }

        return $primaryCart;
    }

    public function index()
    {
        session()->forget('active_cart_batch_id');

        $cart = $this->getCustomerCart(auth()->id());
        $items = $cart->cartItems()->with('product')->orderBy('id', 'desc')->get();

        $adjustedItems = [];

        foreach ($items as $item) {
            $freshProduct = $item->product ? $item->product->fresh() : null;
            $freshStock = $freshProduct ? $freshProduct->available_stock : 0;

            if (!$freshProduct || empty($freshProduct->expiration_date) || $freshProduct->status !== 'active') {
                $pName = $freshProduct ? $freshProduct->product_name : 'An item';
                $adjustedItems[] = "{$pName} lacks verified expiration date info and cannot be checked out.";
            } elseif ($freshStock > 0 && $item->quantity > $freshStock) {
                $custom = Product::getCartItemCustomization($item->id, $item->product);
                $unitPrice = $item->product ? $item->product->getPriceForSize($custom['size'] ?? 'Medium') : (float) $item->product->price;
                $item->update([
                    'quantity' => $freshStock,
                    'subtotal' => $unitPrice * $freshStock,
                ]);
                $adjustedItems[] = "{$item->product->product_name} quantity was adjusted to the maximum available stock ({$freshStock}).";
            }
        }

        if (!empty($adjustedItems)) {
            session()->flash('warning', implode(' ', array_unique($adjustedItems)));
            $items = $cart->cartItems()->with('product')->orderBy('id', 'desc')->get();
        }

        $total = $items->sum('subtotal');
        $cartCount = (int) $items->sum('quantity');

        // Maintain batches for backward compatibility if any view helper needs it
        $batches = collect([$cart]);

        return view('customer.cart', compact('cart', 'items', 'total', 'cartCount', 'batches'));
    }

    public function add(Request $request, Product $product)
    {
        $product = $product->fresh();

        if (auth()->user()->isRestricted()) {
            $until = auth()->user()->restricted_until->format('M d, Y h:i A');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Your account is temporarily restricted from ordering until {$until}."
                ], 403);
            }
            return redirect()->back()->with('error', "Your account is temporarily restricted from ordering until {$until}.");
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $qty = (int) $request->quantity;

        // Block products without valid expiration info or inactive
        if (empty($product->expiration_date) || $product->status !== 'active') {
            $msg = "{$product->product_name} cannot be added to cart because it lacks verified expiration data.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'available_left' => 0,
                    'stock' => 0,
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Check if product is expired
        if ($product->expiration_date && \Carbon\Carbon::parse($product->expiration_date)->startOfDay()->lt(now()->startOfDay())) {
            $msg = "{$product->product_name} is no longer available as it has expired.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'available_left' => 0,
                    'stock' => 0,
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $availableStock = InventoryService::getAvailableStock($product);

        // Check if product is completely out of available stock
        if ($availableStock <= 0) {
            $msg = InventoryService::insufficientStockMessage($product, 0);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'available_left' => 0,
                    'stock' => 0,
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Retrieve customer's consolidated cart early to check combined product quantity
        $cart = $this->getCustomerCart(auth()->id());
        $alreadyInCartForProduct = (int) CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->sum('quantity');

        if (($alreadyInCartForProduct + $qty) > $availableStock) {
            $msg = InventoryService::insufficientStockMessage($product, $availableStock);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'available_to_add' => max(0, $availableStock - $alreadyInCartForProduct),
                    'stock' => $availableStock,
                    'available_left' => $availableStock,
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $selectedMilk = $request->input('milk_type', $product->milk_type);
        $selectedSize = $request->input('size', $product->size);
        if (!in_array($selectedMilk, $product->available_milk_types, true)) {
            $selectedMilk = $product->available_milk_types[0] ?? $product->milk_type;
        }
        if (!in_array($selectedSize, $product->available_sizes, true)) {
            $selectedSize = $product->available_sizes[0] ?? $product->size;
        }

        $unitPrice = $product->getPriceForSize($selectedSize);

        // Check if identical item (same product, milk type, and size) already exists in cart -> merge!
        $existingItem = null;
        $itemsInCart = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->get();

        foreach ($itemsInCart as $ci) {
            $cust = Product::getCartItemCustomization($ci->id, $product);
            if ($cust['milk_type'] === $selectedMilk && $cust['size'] === $selectedSize) {
                $existingItem = $ci;
                break;
            }
        }

        if ($existingItem) {
            // Merge: increase quantity capped at available stock
            $newQty = min($existingItem->quantity + $qty, $availableStock);
            $existingItem->update([
                'quantity' => $newQty,
                'subtotal' => $unitPrice * $newQty,
            ]);
            $cartItem = $existingItem;
        } else {
            // Add new line item to the same cart layout
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'subtotal' => $unitPrice * $qty,
            ]);
            Product::saveCartItemCustomization($cartItem->id, $selectedMilk, $selectedSize);
        }

        $totalItemsCount = (int) CartItem::where('cart_id', $cart->id)->sum('quantity');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$qty}x {$product->product_name} added to cart.",
                'cart_count' => $totalItemsCount,
                'added_quantity' => $qty,
                'current_in_cart' => $totalItemsCount,
                'product_name' => $product->product_name,
                'product_image' => $product->image ? asset('storage/' . $product->image) : null,
                'product_price' => number_format($unitPrice, 2),
                'product_id' => $product->id,
                'stock' => $availableStock,
                'available_left' => $availableStock,
            ]);
        }

        return redirect()->back()->with('success', "{$qty}x {$product->product_name} added to cart.");
    }

    public function update(Request $request, CartItem $item)
    {
        if (!$item->cart || $item->cart->customer_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $qty = (int) $request->quantity;
        $product = $item->product->fresh();
        $availableStock = InventoryService::getAvailableStock($product);
        $otherSameProductQty = (int) CartItem::where('cart_id', $item->cart_id)
            ->where('product_id', $product->id)
            ->where('id', '!=', $item->id)
            ->sum('quantity');

        if (($otherSameProductQty + $qty) > $availableStock) {
            $msg = InventoryService::insufficientStockMessage($product, $availableStock);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'max_stock' => max(0, $availableStock - $otherSameProductQty),
                    'quantity' => min($item->quantity, max(1, $availableStock - $otherSameProductQty)),
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $custom = Product::getCartItemCustomization($item->id, $product);
        $unitPrice = $product->getPriceForSize($custom['size'] ?? 'Medium');

        $item->update([
            'quantity' => $qty,
            'subtotal' => $unitPrice * $qty,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            $batchCart = $item->cart;
            $batchItems = $batchCart->cartItems()->get();
            $batchSubtotal = $batchItems->sum('subtotal');
            $batchUnitCount = (int) $batchItems->sum('quantity');

            $customerCartIds = Cart::where('customer_id', auth()->id())->pluck('id');
            $allItems = CartItem::whereIn('cart_id', $customerCartIds)->get();
            $total = $allItems->sum('subtotal');
            $cartCount = (int) $allItems->sum('quantity');

            return response()->json([
                'success' => true,
                'item_id' => $item->id,
                'quantity' => $item->quantity,
                'unit_price' => $unitPrice,
                'subtotal' => '₱' . number_format($item->subtotal, 2),
                'subtotal_raw' => (float) $item->subtotal,
                'batch_id' => $batchCart->id,
                'batch_subtotal' => '₱' . number_format($batchSubtotal, 2),
                'batch_unit_count' => $batchUnitCount,
                'total' => '₱' . number_format($total, 2),
                'total_raw' => (float) $total,
                'cart_count' => $cartCount,
                'max_stock' => $availableStock,
            ]);
        }

        return redirect()->route('customer.cart.index');
    }

    public function remove(CartItem $item)
    {
        if (!$item->cart || $item->cart->customer_id !== auth()->id()) {
            abort(403);
        }

        $batchCart = $item->cart;
        $item->delete();

        if ($batchCart->cartItems()->count() === 0) {
            if (session('active_cart_batch_id') == $batchCart->id) {
                session()->forget('active_cart_batch_id');
            }
            $batchCart->delete();
        }

        return redirect()->route('customer.cart.index')->with('success', 'Item removed from cart.');
    }

    public function removeBatch(Cart $cart)
    {
        if ($cart->customer_id !== auth()->id()) {
            abort(403);
        }

        if (session('active_cart_batch_id') == $cart->id) {
            session()->forget('active_cart_batch_id');
        }

        $cart->cartItems()->delete();
        $cart->delete();

        return redirect()->route('customer.cart.index')->with('success', 'Cart removed.');
    }

    public function startNewBatch(Request $request)
    {
        session()->forget('active_cart_batch_id');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Started a new cart.',
            ]);
        }

        return redirect()->route('customer.catalog')->with('success', 'Ready to create a new cart.');
    }

    public function checkout(Request $request)
    {
        $cart = $this->getCustomerCart(auth()->id());

        // Support selecting specific item IDs via ?items=1,2,3 or items[]
        $selectedItemsParam = $request->query('items') ?? $request->input('items');
        if (!empty($selectedItemsParam)) {
            $itemIds = is_array($selectedItemsParam) ? $selectedItemsParam : explode(',', $selectedItemsParam);
            $itemIds = array_filter(array_map('intval', $itemIds));
            $items = CartItem::where('cart_id', $cart->id)
                ->whereIn('id', $itemIds)
                ->with('product')
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $items = $cart->cartItems()->with('product')->orderBy('id', 'desc')->get();
        }

        if ($items->isEmpty()) {
            return redirect()->route('customer.cart.index')->with('error', 'Please select at least one product using the checkbox to checkout.');
        }

        // Double check available stock and expiration availability before checkout screen
        foreach ($items as $item) {
            $freshProduct = $item->product ? $item->product->fresh() : null;
            $freshStock   = $freshProduct ? $freshProduct->available_stock : 0;

            if (!$freshProduct || empty($freshProduct->expiration_date) || $freshProduct->status !== 'active') {
                $pName = $freshProduct ? $freshProduct->product_name : 'An item';
                return redirect()->route('customer.cart.index')
                    ->with('error', "{$pName} cannot be checked out because its expiration date information is missing or unverified. Please remove it from your cart.");
            }

            if ($freshProduct && $freshProduct->expiration_date &&
                \Carbon\Carbon::parse($freshProduct->expiration_date)->startOfDay()->lt(now()->startOfDay())) {
                return redirect()->route('customer.cart.index')
                    ->with('error', "{$freshProduct->product_name} has expired and cannot be ordered. Please remove it from your cart.");
            }

            if ($freshStock < $item->quantity) {
                if ($freshStock > 0) {
                    $custom = Product::getCartItemCustomization($item->id, $item->product);
                    $unitPrice = $item->product ? $item->product->getPriceForSize($custom['size'] ?? 'Medium') : (float) $item->product->price;
                    $item->update([
                        'quantity' => $freshStock,
                        'subtotal' => $unitPrice * $freshStock,
                    ]);
                    return redirect()->route('customer.cart.index')->with('warning', "Stock changed for {$item->product->product_name}. Quantity was adjusted to {$freshStock}. Please review before continuing.");
                } else {
                    return redirect()->route('customer.cart.index')->with('error', "{$item->product->product_name} is now out of stock. Please remove it from your cart to proceed.");
                }
            }
        }

        $subtotal = $items->sum('subtotal');
        $deliveryFee = Order::calculateDeliveryFee((float) $subtotal, 'Delivery');
        $xenditConfigured = app(XenditService::class)->isConfigured();

        $selectedBatch = $cart;
        $batchNumber = 1;

        return view('customer.checkout', compact('cart', 'selectedBatch', 'batchNumber', 'items', 'subtotal', 'deliveryFee', 'xenditConfigured'));
    }

    public function placeOrder(Request $request, XenditService $xenditService)
    {
        $nowInManila = Carbon::now('Asia/Manila');
        $todayStr = $nowInManila->format('Y-m-d');

        $request->validate([
            'batch_id' => 'nullable|integer',
            'order_type' => 'required|in:Delivery,Pickup',
            'delivery_address' => 'required_if:order_type,Delivery|nullable|string|max:255',
            'schedule_date' => 'required|date|after_or_equal:' . $todayStr,
            'schedule_time' => 'required|in:08:00:00,13:00:00',
            'payment_method' => 'required|in:cod,xendit',
        ]);

        // Validate time slot cutoff if schedule_date is today
        if ($request->schedule_date === $todayStr) {
            $currentTimeMinutes = ((int)$nowInManila->format('G')) * 60 + ((int)$nowInManila->format('i'));

            if ($request->schedule_time === '08:00:00' && $currentTimeMinutes >= 720) {
                return back()->withInput()->withErrors([
                    'schedule_time' => 'The Morning time slot (08:00 AM - 12:00 PM) has already passed for today. Please select the Afternoon slot or choose a future delivery date.'
                ]);
            }

            if ($request->schedule_time === '13:00:00' && $currentTimeMinutes >= 1020) {
                return back()->withInput()->withErrors([
                    'schedule_time' => 'All delivery slots for today have ended (cut-off 5:00 PM). Please select tomorrow or a later delivery date.'
                ]);
            }
        }

        // Resolve specific checked items or fallback to all cart items
        $itemIds = $request->input('item_ids', []);
        if (!empty($itemIds)) {
            $cartItems = CartItem::whereIn('id', (array) $itemIds)
                ->whereHas('cart', fn($q) => $q->where('customer_id', auth()->id()))
                ->with('product')
                ->orderBy('id', 'asc')
                ->get();
        } else {
            $cart = $this->getCustomerCart(auth()->id());
            $cartItems = $cart->cartItems()->with('product')->orderBy('id', 'asc')->get();
        }

        if ($cartItems->isEmpty()) {
            return redirect()->route('customer.cart.index')->with('error', 'No items selected or available to place order.');
        }

        // Check if user is restricted
        if (auth()->user()->isRestricted()) {
            $until = auth()->user()->restricted_until->format('M d, Y h:i A');
            return redirect()->route('customer.cart.index')->with('error', "Your account is temporarily restricted from placing orders until {$until}.");
        }

        try {
            DB::beginTransaction();

            // Aggregate quantities per product and lock rows in deterministic order to prevent overselling
            $aggregatedQuantities = [];
            foreach ($cartItems as $item) {
                $pId = (int) $item->product_id;
                $aggregatedQuantities[$pId] = ($aggregatedQuantities[$pId] ?? 0) + (int) $item->quantity;
            }
            $productIds = array_keys($aggregatedQuantities);
            sort($productIds);

            $lockedProducts = Product::whereIn('id', $productIds)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($aggregatedQuantities as $pId => $totalRequestedQty) {
                $product = $lockedProducts->get($pId);
                if (!$product) {
                    throw new \Exception('Selected product was not found.');
                }

                // Block products without valid expiration info or inactive at order placement
                if (empty($product->expiration_date) || $product->status !== 'active') {
                    throw new \Exception("'{$product->product_name}' cannot be ordered because its expiration date information is missing or unverified. Please remove it from your cart.");
                }

                // Block expired products at order placement
                if ($product->expiration_date && \Carbon\Carbon::parse($product->expiration_date)->startOfDay()->lt(now()->startOfDay())) {
                    throw new \Exception("'{$product->product_name}' has expired and cannot be ordered. Please remove it from your cart.");
                }

                $available = InventoryService::getAvailableStock($product);
                if ($available < $totalRequestedQty) {
                    throw new \Exception(InventoryService::insufficientStockMessage($product, $available));
                }
            }

            // Unique Order Number resolution DB-YYYYMMDD-XXXX
            $today = Carbon::today()->format('Ymd');
            $ordersTodayCount = Order::whereDate('created_at', Carbon::today())->count();
            $orderNumber = 'DB-' . $today . '-' . str_pad($ordersTodayCount + 1, 4, '0', STR_PAD_LEFT);
            while (Order::where('order_number', $orderNumber)->exists()) {
                $ordersTodayCount++;
                $orderNumber = 'DB-' . $today . '-' . str_pad($ordersTodayCount + 1, 4, '0', STR_PAD_LEFT);
            }

            $subtotal = $cartItems->sum('subtotal');
            $deliveryFee = Order::calculateDeliveryFee((float) $subtotal, $request->order_type);
            $totalAmount = $subtotal + $deliveryFee;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => auth()->id(),
                'order_type' => $request->order_type,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'delivery_address' => ($request->order_type === 'Delivery') ? $request->delivery_address : null,
                'schedule_date' => $request->schedule_date,
                'schedule_time' => $request->schedule_time,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'order_status' => 'Pending',
            ]);

            foreach ($cartItems as $item) {
                $product = $lockedProducts->get($item->product_id);

                $itemCust = Product::getCartItemCustomization($item->id, $item->product);
                $unitPrice = $item->quantity > 0 ? ((float) $item->subtotal / $item->quantity) : $product->getPriceForSize($itemCust['size'] ?? 'Medium');

                // Create Order Item
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $unitPrice,
                    'subtotal' => $item->subtotal,
                ]);

                Product::saveOrderItemCustomization($orderItem->id, $itemCust['milk_type'], $itemCust['size']);

                // Deduct stock using FIFO logic (also enforces Available Stock under lockForUpdate)
                InventoryService::deductStock($item->product_id, $item->quantity, "Order checkout #{$orderNumber}");

                // Delete only the checked-out item from the cart
                $item->delete();
            }

            session()->forget('active_cart_batch_id');

            // Record audit log
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'ORDER_PLACED',
                'details' => "Order {$orderNumber} placed with " . $cartItems->sum('quantity') . " items (Total: ₱" . number_format($totalAmount, 2) . ", Method: " . strtoupper($request->payment_method) . ")",
                'ip_address' => $request->ip(),
            ]);

            // Handle Xendit Online Payment initiation
            $xenditInvoiceUrl = null;
            if ($request->payment_method === 'xendit') {
                if (!$xenditService->isConfigured()) {
                    throw new \Exception("Xendit payment gateway is not yet configured with an API key. Please choose Cash on Delivery or contact store support.");
                }

                $invoice = $xenditService->createInvoice($order);

                $order->update([
                    'xendit_invoice_id' => $invoice['id'],
                    'xendit_invoice_url' => $invoice['invoice_url'],
                ]);

                $xenditInvoiceUrl = $invoice['invoice_url'];
            }

            // Create notification for customer
            AppNotification::create([
                'user_id' => auth()->id(),
                'title' => 'Order Received',
                'message' => ($request->payment_method === 'xendit')
                    ? "Your order {$orderNumber} has been received. Please complete online payment via Xendit."
                    : "Your order {$orderNumber} has been received and is pending admin approval.",
                'link' => route('customer.orders.show', $order->id),
                'is_read' => false,
            ]);

            // Create notification for admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                AppNotification::create([
                    'user_id' => $admin->id,
                    'title' => 'New Order Received',
                    'message' => "New order {$orderNumber} placed by " . auth()->user()->name . " (₱" . number_format($totalAmount, 2) . " via " . strtoupper($request->payment_method) . ").",
                    'link' => route('admin.orders.show', $order->id),
                    'is_read' => false,
                ]);
            }

            // Create notification for active delivery fleet riders if this is a delivery order
            if ($request->order_type === 'Delivery') {
                $activeRiders = User::where('role', 'rider')->where('status', 'active')->get();
                foreach ($activeRiders as $rider) {
                    AppNotification::create([
                        'user_id' => $rider->id,
                        'title' => 'New Customer Delivery Order',
                        'message' => "New delivery order {$orderNumber} placed by " . auth()->user()->name . " to {$order->delivery_address} (₱" . number_format($totalAmount, 2) . ").",
                        'link' => route('rider.orders.show', $order->id),
                        'is_read' => false,
                    ]);
                }
            }

            DB::commit();

            if ($request->payment_method === 'xendit' && !empty($xenditInvoiceUrl)) {
                return redirect()->away($xenditInvoiceUrl);
            }

            return redirect()->route('customer.orders.index')->with('success', "Order placed successfully! Order Number: {$orderNumber}");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('customer.cart.index')->with('error', $e->getMessage());
        }
    }
}
