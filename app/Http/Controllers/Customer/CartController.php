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
    public function index()
    {
        // Viewing the cart seals the current active selection batch so subsequent shop additions start a new batch
        session()->forget('active_cart_batch_id');

        // Clean up any empty batch containers for this customer
        Cart::where('customer_id', auth()->id())->doesntHave('cartItems')->delete();

        $chronologicalIds = Cart::where('customer_id', auth()->id())
            ->has('cartItems')
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->values();

        $batches = Cart::where('customer_id', auth()->id())
            ->has('cartItems')
            ->with(['cartItems' => function ($q) {
                $q->orderBy('id', 'desc')->with('product');
            }])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $adjustedItems = [];

        foreach ($batches as $batch) {
            foreach ($batch->cartItems as $item) {
                $freshProduct = $item->product ? $item->product->fresh() : null;
                $freshStock = $freshProduct ? $freshProduct->stock : 0;

                if (!$freshProduct || empty($freshProduct->expiration_date) || $freshProduct->status !== 'active') {
                    $pName = $freshProduct ? $freshProduct->product_name : 'An item';
                    $adjustedItems[] = "{$pName} lacks verified expiration date info and cannot be checked out.";
                } elseif ($freshStock > 0 && $item->quantity > $freshStock) {
                    $item->update([
                        'quantity' => $freshStock,
                        'subtotal' => $item->product->price * $freshStock,
                    ]);
                    $adjustedItems[] = "{$item->product->product_name} quantity was adjusted to the maximum available stock ({$freshStock}).";
                }
            }
        }

        if (!empty($adjustedItems)) {
            session()->flash('warning', implode(' ', array_unique($adjustedItems)));
            $batches = Cart::where('customer_id', auth()->id())
                ->has('cartItems')
                ->with(['cartItems' => function ($q) {
                    $q->orderBy('id', 'desc')->with('product');
                }])
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();
        }

        foreach ($batches as $batch) {
            $idx = $chronologicalIds->search($batch->id);
            $batch->cart_number = ($idx !== false) ? ($idx + 1) : 1;
        }

        $items = $batches->pluck('cartItems')->flatten(1);
        $total = $items->sum('subtotal');

        return view('customer.cart', compact('batches', 'items', 'total'));
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

        // Check if product is completely out of stock
        if ($product->stock <= 0) {
            $msg = "{$product->product_name} is currently out of stock.";
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

        if ($qty > $product->stock) {
            $msg = "Cannot add {$qty} unit(s). Maximum available warehouse stock for {$product->product_name} is {$product->stock} unit" . ($product->stock > 1 ? 's' : '') . ".";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'available_to_add' => $product->stock,
                    'stock' => $product->stock,
                    'available_left' => $product->stock,
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Resolve Selection Batch (Batch Isolation: never auto-merge with previous items of the same product)
        $forceNewBatch = $request->boolean('new_batch');
        $activeBatchId = session('active_cart_batch_id');
        $targetBatch = null;

        if (!$forceNewBatch && $activeBatchId) {
            $candidateBatch = Cart::where('id', $activeBatchId)
                ->where('customer_id', auth()->id())
                ->first();

            if ($candidateBatch) {
                $alreadyInBatch = CartItem::where('cart_id', $candidateBatch->id)
                    ->where('product_id', $product->id)
                    ->exists();

                if (!$alreadyInBatch) {
                    $targetBatch = $candidateBatch;
                }
            }
        }

        if (!$targetBatch) {
            $targetBatch = Cart::where('customer_id', auth()->id())
                ->doesntHave('cartItems')
                ->first();

            if ($targetBatch) {
                $targetBatch->touch();
            } else {
                $targetBatch = Cart::create(['customer_id' => auth()->id()]);
            }

            session(['active_cart_batch_id' => $targetBatch->id]);
        } else {
            $targetBatch->touch();
        }

        $selectedMilk = $request->input('milk_type', $product->milk_type);
        $selectedSize = $request->input('size', $product->size);
        if (!in_array($selectedMilk, $product->available_milk_types, true)) {
            $selectedMilk = $product->available_milk_types[0] ?? $product->milk_type;
        }
        if (!in_array($selectedSize, $product->available_sizes, true)) {
            $selectedSize = $product->available_sizes[0] ?? $product->size;
        }

        $cartItem = CartItem::create([
            'cart_id' => $targetBatch->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'subtotal' => $product->price * $qty,
        ]);

        Product::saveCartItemCustomization($cartItem->id, $selectedMilk, $selectedSize);

        $nonEmptyBatchIds = Cart::where('customer_id', auth()->id())
            ->has('cartItems')
            ->orderBy('id', 'asc')
            ->pluck('id');

        $batchIndex = $nonEmptyBatchIds->search($targetBatch->id);
        $batchNumber = ($batchIndex !== false) ? ($batchIndex + 1) : $nonEmptyBatchIds->count();

        $totalItemsCount = (int) CartItem::whereIn('cart_id', $nonEmptyBatchIds)->sum('quantity');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$qty}x {$product->product_name} added to Cart #{$batchNumber}.",
                'cart_count' => $totalItemsCount,
                'batch_id' => $targetBatch->id,
                'batch_number' => $batchNumber,
                'added_quantity' => $qty,
                'current_in_cart' => $totalItemsCount,
                'product_name' => $product->product_name,
                'product_image' => $product->image ? asset('storage/' . $product->image) : null,
                'product_price' => number_format($product->price, 2),
                'product_id' => $product->id,
                'stock' => $product->stock,
                'available_left' => $product->stock,
            ]);
        }

        return redirect()->back()->with('success', "{$qty}x {$product->product_name} added to Cart #{$batchNumber}.");
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

        if ($qty > $product->stock) {
            $msg = "Cannot set quantity to {$qty}. Maximum available stock is {$product->stock} units.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'max_stock' => $product->stock,
                    'quantity' => min($item->quantity, max(1, $product->stock)),
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $item->update([
            'quantity' => $qty,
            'subtotal' => $product->price * $qty,
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
                'quantity' => $item->quantity,
                'subtotal' => '₱' . number_format($item->subtotal, 2),
                'batch_id' => $batchCart->id,
                'batch_subtotal' => '₱' . number_format($batchSubtotal, 2),
                'batch_unit_count' => $batchUnitCount,
                'total' => '₱' . number_format($total, 2),
                'cart_count' => $cartCount,
                'max_stock' => $product->stock,
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
        $allBatches = Cart::where('customer_id', auth()->id())
            ->has('cartItems')
            ->with(['cartItems' => function ($q) {
                $q->orderBy('id', 'desc')->with('product');
            }])
            ->orderBy('id', 'asc')
            ->get();

        if ($allBatches->isEmpty()) {
            return redirect()->route('customer.catalog')->with('error', 'Your shopping cart is empty.');
        }

        $requestedBatchId = $request->query('batch');
        $selectedBatch = $requestedBatchId
            ? $allBatches->firstWhere('id', (int) $requestedBatchId)
            : $allBatches->last();

        if (!$selectedBatch) {
            return redirect()->route('customer.cart.index')->with('error', 'The selected cart could not be found.');
        }

        $batchIndex = $allBatches->search(fn($b) => $b->id === $selectedBatch->id);
        $batchNumber = ($batchIndex !== false) ? ($batchIndex + 1) : 1;

        $items = $selectedBatch->cartItems;
        
        // Double check stock and expiration availability for this batch before checkout screen
        foreach ($items as $item) {
            $freshProduct = $item->product ? $item->product->fresh() : null;
            $freshStock   = $freshProduct ? $freshProduct->stock : 0;

            // Block products without valid expiration info or inactive
            if (!$freshProduct || empty($freshProduct->expiration_date) || $freshProduct->status !== 'active') {
                $pName = $freshProduct ? $freshProduct->product_name : 'An item';
                return redirect()->route('customer.cart.index')
                    ->with('error', "{$pName} in Cart #{$batchNumber} cannot be checked out because its expiration date information is missing or unverified. Please remove it from your cart.");
            }

            // Block expired products
            if ($freshProduct && $freshProduct->expiration_date &&
                \Carbon\Carbon::parse($freshProduct->expiration_date)->startOfDay()->lt(now()->startOfDay())) {
                return redirect()->route('customer.cart.index')
                    ->with('error', "{$freshProduct->product_name} in Cart #{$batchNumber} has expired and cannot be ordered. Please remove it from your cart.");
            }

            if ($freshStock < $item->quantity) {
                if ($freshStock > 0) {
                    $item->update([
                        'quantity' => $freshStock,
                        'subtotal' => $item->product->price * $freshStock,
                    ]);
                    return redirect()->route('customer.cart.index')->with('warning', "Stock changed for {$item->product->product_name} in Cart #{$batchNumber}. Quantity was adjusted to {$freshStock}. Please review before continuing.");
                } else {
                    return redirect()->route('customer.cart.index')->with('error', "{$item->product->product_name} in Cart #{$batchNumber} is now out of stock. Please remove it from your cart to proceed.");
                }
            }
        }

        $subtotal = $items->sum('subtotal');
        $deliveryFee = Order::calculateDeliveryFee((float) $subtotal, 'Delivery');
        $xenditConfigured = app(XenditService::class)->isConfigured();
        
        return view('customer.checkout', compact('selectedBatch', 'batchNumber', 'items', 'subtotal', 'deliveryFee', 'xenditConfigured'));
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

        $batchQuery = Cart::where('customer_id', auth()->id())->has('cartItems');
        if ($request->filled('batch_id')) {
            $batchQuery->where('id', (int) $request->batch_id);
        }

        $selectedBatch = $batchQuery->orderBy('id', 'asc')->first();

        if (!$selectedBatch) {
            return redirect()->route('customer.cart.index')->with('error', 'The selected order batch is empty or no longer exists.');
        }

        $cartItems = $selectedBatch->cartItems()->with('product')->orderBy('id', 'asc')->get();

        // Check if user is restricted
        if (auth()->user()->isRestricted()) {
            $until = auth()->user()->restricted_until->format('M d, Y h:i A');
            return redirect()->route('customer.cart.index')->with('error', "Your account is temporarily restricted from placing orders until {$until}.");
        }

        try {
            DB::beginTransaction();

            // Unique Order Number resolution DB-YYYYMMDD-XXXX
            $today = Carbon::today()->format('Ymd');
            $ordersTodayCount = Order::whereDate('created_at', Carbon::today())->count();
            $orderNumber = 'DB-' . $today . '-' . str_pad($ordersTodayCount + 1, 4, '0', STR_PAD_LEFT);

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
                // Double check stock with fresh product row
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                if (!$product || $product->stock < $item->quantity) {
                    $available = $product ? $product->stock : 0;
                    throw new \Exception("Insufficient stock for product '{$item->product->product_name}'. Available: {$available}, Requested: {$item->quantity}");
                }

                // Block products without valid expiration info or inactive at order placement
                if (empty($product->expiration_date) || $product->status !== 'active') {
                    throw new \Exception("'{$product->product_name}' cannot be ordered because its expiration date information is missing or unverified. Please remove it from your cart.");
                }

                // Block expired products at order placement
                if ($product->expiration_date && \Carbon\Carbon::parse($product->expiration_date)->startOfDay()->lt(now()->startOfDay())) {
                    throw new \Exception("'{$product->product_name}' has expired and cannot be ordered. Please remove it from your cart.");
                }

                // Create Order Item
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'subtotal' => $item->subtotal,
                ]);

                $itemCust = Product::getCartItemCustomization($item->id, $item->product);
                Product::saveOrderItemCustomization($orderItem->id, $itemCust['milk_type'], $itemCust['size']);

                // Deduct stock using FIFO logic!
                InventoryService::deductStock($item->product_id, $item->quantity, "Order checkout #{$orderNumber}");
            }

            // Remove only the checked-out Selection Batch so other batches remain in the cart
            if (session('active_cart_batch_id') == $selectedBatch->id) {
                session()->forget('active_cart_batch_id');
            }
            $selectedBatch->cartItems()->delete();
            $selectedBatch->delete();

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
                        'link' => route('rider.dashboard'),
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
            return redirect()->route('customer.cart.index')->with('error', "Order failed: " . $e->getMessage());
        }
    }
}
