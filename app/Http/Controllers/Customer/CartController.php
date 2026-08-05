<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AppNotification;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CartController extends Controller
{
    public function index()
    {
        $cart = Cart::firstOrCreate(['customer_id' => auth()->id()]);
        $items = $cart->cartItems()->with('product')->get();
        $total = $items->sum('subtotal');

        return view('customer.cart', compact('items', 'total'));
    }

    public function add(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $qty = $request->quantity;

        if ($product->stock < $qty) {
            return redirect()->back()->with('error', "Insufficient stock available for {$product->product_name}. Available: {$product->stock} units.");
        }

        $cart = Cart::firstOrCreate(['customer_id' => auth()->id()]);
        
        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $newQty = $item->quantity + $qty;
            if ($product->stock < $newQty) {
                return redirect()->back()->with('error', "Cannot add more {$product->product_name} to cart. Total cart quantity exceeds available stock.");
            }
            $item->update([
                'quantity' => $newQty,
                'subtotal' => $product->price * $newQty,
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'subtotal' => $product->price * $qty,
            ]);
        }

        return redirect()->route('customer.cart.index')->with('success', "{$product->product_name} has been added to your cart.");
    }

    public function update(Request $request, CartItem $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $qty = $request->quantity;
        $product = $item->product;

        if ($product->stock < $qty) {
            return redirect()->back()->with('error', "Cannot update quantity. Insufficient stock available. Max: {$product->stock} units.");
        }

        $item->update([
            'quantity' => $qty,
            'subtotal' => $product->price * $qty,
        ]);

        return redirect()->route('customer.cart.index')->with('success', 'Cart updated successfully.');
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
            if ($item->product->stock < $item->quantity) {
                return redirect()->route('customer.cart.index')->with('error', "Stock level changed for {$item->product->product_name}. Please adjust quantity.");
            }
        }

        $subtotal = $items->sum('subtotal');
        
        return view('customer.checkout', compact('items', 'subtotal'));
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'order_type' => 'required|in:Delivery,Pickup',
            'delivery_address' => 'required_if:order_type,Delivery|nullable|string|max:255',
            'schedule_date' => 'required|date|after_or_equal:today',
            'schedule_time' => 'required',
        ]);

        $cart = Cart::where('customer_id', auth()->id())->first();
        if (!$cart || $cart->cartItems()->count() === 0) {
            return redirect()->route('customer.catalog')->with('error', 'Your shopping cart is empty.');
        }

        $cartItems = $cart->cartItems()->with('product')->get();

        // Check if user is restricted
        if (auth()->user()->status === 'restricted' && auth()->user()->isRestricted()) {
            return redirect()->route('customer.catalog')->with('error', 'Your account is restricted. You cannot place orders.');
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
                'delivery_address' => ($request->order_type === 'Delivery') ? $request->delivery_address : null,
                'schedule_date' => $request->schedule_date,
                'schedule_time' => $request->schedule_time,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'order_status' => 'Pending',
            ]);

            foreach ($cartItems as $item) {
                // Double check stock check
                if ($item->product->stock < $item->quantity) {
                    throw new \Exception("Insufficient stock for product '{$item->product->product_name}'. available: {$item->product->stock}");
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

            // Create notification
            AppNotification::create([
                'user_id' => auth()->id(),
                'title' => 'Order Received',
                'message' => "Your order {$orderNumber} has been received and is pending admin approval.",
                'is_read' => false,
            ]);

            DB::commit();

            return redirect()->route('customer.orders.index')->with('success', "Order placed successfully! Order Number: {$orderNumber}");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('customer.cart.index')->with('error', "Order failed: " . $e->getMessage());
        }
    }
}
