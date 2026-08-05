<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ── Admin User ──
        User::create([
            'name' => 'Admin DairyBox',
            'email' => 'admin@dairybox.com',
            'password' => Hash::make('password'),
            'phone' => '09171234567',
            'role' => 'admin',
            'status' => 'active',
        ]);

        // ── Rider User ──
        $rider = User::create([
            'name' => 'Juan Rider',
            'email' => 'rider@dairybox.com',
            'password' => Hash::make('password'),
            'phone' => '09181234567',
            'role' => 'rider',
            'status' => 'active',
        ]);

        // ── Customer Users ──
        $customers = [];
        $customerNames = [
            ['name' => 'Maria Santos', 'email' => 'maria@example.com'],
            ['name' => 'Jose Cruz', 'email' => 'jose@example.com'],
            ['name' => 'Ana Garcia', 'email' => 'ana@example.com'],
            ['name' => 'Pedro Reyes', 'email' => 'pedro@example.com'],
            ['name' => 'Rosa Mendoza', 'email' => 'rosa@example.com'],
        ];

        foreach ($customerNames as $c) {
            $customers[] = User::create([
                'name' => $c['name'],
                'email' => $c['email'],
                'password' => Hash::make('password'),
                'phone' => '0917' . rand(1000000, 9999999),
                'role' => 'customer',
                'status' => 'active',
            ]);
        }

        // ── Categories ──
        $flavoredMilk = Category::create(['category_name' => 'Flavored Milk']);
        $iceCream = Category::create(['category_name' => 'Ice Cream']);

        // ── Products ──
        $products = [];

        // Flavored Milk products
        $milkProducts = [
            ['product_name' => 'Chocolate Carabao Milk', 'milk_type' => 'Carabao', 'size' => 'Small', 'price' => 45.00, 'image' => 'products/chocolate_milk.png'],
            ['product_name' => 'Chocolate Carabao Milk', 'milk_type' => 'Carabao', 'size' => 'Medium', 'price' => 65.00, 'image' => 'products/chocolate_milk.png'],
            ['product_name' => 'Strawberry Cow Milk', 'milk_type' => 'Cow', 'size' => 'Small', 'price' => 50.00, 'image' => 'products/strawberry_milk.png'],
            ['product_name' => 'Strawberry Cow Milk', 'milk_type' => 'Cow', 'size' => 'Large', 'price' => 85.00, 'image' => 'products/strawberry_milk.png'],
            ['product_name' => 'Vanilla Carabao Milk', 'milk_type' => 'Carabao', 'size' => 'Medium', 'price' => 60.00, 'image' => 'products/chocolate_milk.png'],
            ['product_name' => 'Ube Cow Milk', 'milk_type' => 'Cow', 'size' => 'Large', 'price' => 90.00, 'image' => 'products/strawberry_milk.png'],
        ];

        foreach ($milkProducts as $mp) {
            $products[] = Product::create(array_merge($mp, [
                'category_id' => $flavoredMilk->id,
                'description' => 'Fresh ' . strtolower($mp['product_name']) . ' made from pure ' . strtolower($mp['milk_type']) . ' milk.',
                'stock' => 0,
                'expiration_date' => now()->addDays(rand(5, 30)),
                'status' => 'active',
            ]));
        }

        // Ice Cream products
        $iceCreamProducts = [
            ['product_name' => 'Carabao Milk Ice Cream - Mango', 'milk_type' => 'Carabao', 'size' => 'Small', 'price' => 75.00, 'image' => 'products/mango_ice_cream.png'],
            ['product_name' => 'Carabao Milk Ice Cream - Cheese', 'milk_type' => 'Carabao', 'size' => 'Medium', 'price' => 95.00, 'image' => 'products/mango_ice_cream.png'],
            ['product_name' => 'Cow Milk Ice Cream - Cookies & Cream', 'milk_type' => 'Cow', 'size' => 'Large', 'price' => 120.00, 'image' => 'products/mango_ice_cream.png'],
            ['product_name' => 'Cow Milk Ice Cream - Avocado', 'milk_type' => 'Cow', 'size' => 'Medium', 'price' => 100.00, 'image' => 'products/avocado_ice_cream.png'],
        ];

        foreach ($iceCreamProducts as $ip) {
            $products[] = Product::create(array_merge($ip, [
                'category_id' => $iceCream->id,
                'description' => 'Premium ' . strtolower($ip['product_name']) . ' crafted from fresh ' . strtolower($ip['milk_type']) . ' milk.',
                'stock' => 0,
                'expiration_date' => now()->addDays(rand(10, 60)),
                'status' => 'active',
            ]));
        }

        // ── Inventory Batches (FIFO) ──
        foreach ($products as $product) {
            $stockQty = rand(20, 80);
            Inventory::create([
                'product_id' => $product->id,
                'stock_in' => $stockQty,
                'stock_out' => 0,
                'remaining_stock' => $stockQty,
                'expiration_date' => $product->expiration_date,
                'remarks' => 'Initial stock load',
            ]);

            // Update product stock
            $product->update(['stock' => $stockQty]);
        }

        // ── Historical Orders & Sales (12 months for forecasting) ──
        $orderStatuses = ['Delivered'];

        for ($monthsAgo = 12; $monthsAgo >= 1; $monthsAgo--) {
            $ordersThisMonth = rand(3, 8);

            for ($o = 0; $o < $ordersThisMonth; $o++) {
                $customer = $customers[array_rand($customers)];
                $orderDate = now()->subMonths($monthsAgo)->addDays(rand(0, 27));

                $order = Order::create([
                    'order_number' => 'DB-' . $orderDate->format('Ymd') . '-' . str_pad($o + 1, 4, '0', STR_PAD_LEFT),
                    'customer_id' => $customer->id,
                    'rider_id' => $rider->id,
                    'order_type' => rand(0, 1) ? 'Delivery' : 'Pickup',
                    'delivery_address' => rand(0, 1) ? 'Brgy. ' . rand(1, 50) . ', Sample City' : null,
                    'schedule_date' => $orderDate,
                    'schedule_time' => sprintf('%02d:00:00', rand(8, 17)),
                    'subtotal' => 0,
                    'delivery_fee' => rand(0, 1) ? 50.00 : 0.00,
                    'total_amount' => 0,
                    'order_status' => 'Delivered',
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);

                // Add 1-3 items per order
                $itemCount = rand(1, 3);
                $subtotal = 0;

                for ($i = 0; $i < $itemCount; $i++) {
                    $product = $products[array_rand($products)];
                    $qty = rand(1, 5);
                    $itemSubtotal = $product->price * $qty;
                    $subtotal += $itemSubtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'price' => $product->price,
                        'subtotal' => $itemSubtotal,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);
                }

                $order->update([
                    'subtotal' => $subtotal,
                    'total_amount' => $subtotal + $order->delivery_fee,
                ]);

                // Create sale record
                Sale::create([
                    'order_id' => $order->id,
                    'total_amount' => $subtotal + $order->delivery_fee,
                    'sales_date' => $orderDate->toDateString(),
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);

                // Create delivery log if it's a delivery order
                if ($order->order_type === 'Delivery') {
                    \App\Models\Delivery::create([
                        'order_id' => $order->id,
                        'rider_id' => $rider->id,
                        'delivery_status' => 'Delivered',
                        'delivered_at' => $orderDate,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ]);
                }
            }
        }

        // ── Audit Log Entry ──
        AuditLog::create([
            'user_id' => 1,
            'action' => 'SYSTEM_INIT',
            'details' => 'Database seeded with initial test data.',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
