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
        $cart = Cart::firstOrCreate(['customer_id' => auth()->id()]);
        $items = $cart->cartItems()->with('product')->get();

        $adjustedItems = [];
        foreach ($items as $item) {
            $freshStock = $item->product ? $item->product->fresh()->stock : 0;
            if ($freshStock > 0 && $item->quantity > $freshStock) {
                $item->update([
                    'quantity' => $freshStock,
                    'subtotal' => $item->product->price * $freshStock,
                ]);
                $adjustedItems[] = "{$item->product->product_name} quantity was adjusted to the maximum available stock ({$freshStock}).";
            }
        }

        if (!empty($adjustedItems)) {
            session()->flash('warning', implode(' ', $adjustedItems));
            $items = $cart->cartItems()->with('product')->get();
        }

        $total = $items->sum('subtotal');

        return view('customer.cart', compact('items', 'total'));
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

        $cart = Cart::firstOrCreate(['customer_id' => auth()->id()]);
        
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $currentInCart = $item ? $item->quantity : 0;
        $newTotalQty = $currentInCart + $qty;

        if ($newTotalQty > $product->stock) {
            $availableToAdd = max(0, $product->stock - $currentInCart);
            $msg = $availableToAdd > 0
                ? "Cannot add {$qty} more. You already have {$currentInCart} in your cart, and only {$availableToAdd} more unit" . ($availableToAdd > 1 ? 's are' : ' is') . " available (Stock: {$product->stock})."
                : "Cannot add more. You already have all available {$product->stock} unit" . ($product->stock > 1 ? 's' : '') . " in your cart.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'available_to_add' => $availableToAdd,
                    'current_in_cart' => $currentInCart,
                    'stock' => $product->stock,
                    'available_left' => $availableToAdd,
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        if ($item) {
            $item->update([
                'quantity' => $newTotalQty,
                'subtotal' => $product->price * $newTotalQty,
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'subtotal' => $product->price * $qty,
            ]);
        }

        $totalItemsCount = CartItem::where('cart_id', $cart->id)->sum('quantity');
        $availableLeft = max(0, $product->stock - $newTotalQty);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$product->product_name} has been added to your cart.",
                'cart_count' => $totalItemsCount,
                'product_name' => $product->product_name,
                'product_image' => $product->image ? asset('storage/' . $product->image) : null,
                'product_price' => number_format($product->price, 2),
                'product_id' => $product->id,
                'stock' => $product->stock,
                'available_left' => $availableLeft,
            ]);
        }

        return redirect()->back()->with('success', "{$product->product_name} has been added to your cart.");
    }

    public function update(Request $request, CartItem $item)
    {
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
                    'quantity' => min($item->quantity, $product->stock),
                ], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $item->update([
            'quantity' => $qty,
            'subtotal' => $product->price * $qty,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            $cart = $item->cart;
            $items = $cart->cartItems()->with('product')->get();
            $total = $items->sum('subtotal');
            $cartCount = $items->sum('quantity');

            return response()->json([
                'success' => true,
                'quantity' => $item->quantity,
                'subtotal' => '₱' . number_format($item->subtotal, 2),
                'total' => '₱' . number_format($total, 2),
                'cart_count' => $cartCount,
                'max_stock' => $product->stock,
            ]);
        }

        return redirect()->route('customer.cart.index');
    }

    public function remove(CartItem $item)
    {
        $item->delete();
        return redirect()->route('customer.cart.index')->with('success', 'Item removed from cart.');
    }

    public function checkout()
    {
        $cart = Cart::where('customer_id', auth()->id())->first();
        if (!$cart || $cart->cartItems()->count() === 0) {
            return redirect()->route('customer.catalog')->with('error', 'Your shopping cart is empty.');
        }

        $items = $cart->cartItems()->with('product')->get();
        
        // Double check stock availability before checkout screen
        foreach ($items as $item) {
            $freshProduct = $item->product ? $item->product->fresh() : null;
            $freshStock   = $freshProduct ? $freshProduct->stock : 0;

            // Block expired products
            if ($freshProduct && $freshProduct->expiration_date &&
                \Carbon\Carbon::parse($freshProduct->expiration_date)->startOfDay()->lt(now()->startOfDay())) {
                return redirect()->route('customer.cart.index')
                    ->with('error', "{$freshProduct->product_name} has expired and cannot be ordered. Please remove it from your cart.");
            }

            if ($freshStock < $item->quantity) {
                if ($freshStock > 0) {
                    $item->update([
                        'quantity' => $freshStock,
                        'subtotal' => $item->product->price * $freshStock,
                    ]);
                    return redirect()->route('customer.cart.index')->with('warning', "Stock changed for {$item->product->product_name}. Quantity was adjusted to {$freshStock}. Please review before continuing.");
                } else {
                    return redirect()->route('customer.cart.index')->with('error', "{$item->product->product_name} is now out of stock. Please remove it from your cart to proceed.");
                }
            }
        }

        $subtotal = $items->sum('subtotal');
        $xenditConfigured = app(XenditService::class)->isConfigured();
        
        return view('customer.checkout', compact('items', 'subtotal', 'xenditConfigured'));
    }

    public function placeOrder(Request $request, XenditService $xenditService)
    {
        $request->validate([
            'order_type' => 'required|in:Delivery,Pickup',
            'delivery_address' => 'required_if:order_type,Delivery|nullable|string|max:255',
            'schedule_date' => 'required|date|after_or_equal:today',
            'schedule_time' => 'required',
            'payment_method' => 'required|in:cod,xendit',
        ]);

        $cart = Cart::where('customer_id', auth()->id())->first();
        if (!$cart || $cart->cartItems()->count() === 0) {
            return redirect()->route('customer.catalog')->with('error', 'Your shopping cart is empty.');
        }

        $cartItems = $cart->cartItems()->with('product')->get();

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
            $deliveryFee = ($request->order_type === 'Delivery') ? 50.00 : 0.00;
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

                // Block expired products at order placement
                if ($product->expiration_date && \Carbon\Carbon::parse($product->expiration_date)->startOfDay()->lt(now()->startOfDay())) {
                    throw new \Exception("'{$product->product_name}' has expired and cannot be ordered. Please remove it from your cart.");
                }

                // Create Order Item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'subtotal' => $item->subtotal,
                ]);

                // Deduct stock using FIFO logic!
                InventoryService::deductStock($item->product_id, $item->quantity, "Order checkout #{$orderNumber}");
            }

            // Empty Cart
            $cart->cartItems()->delete();

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
