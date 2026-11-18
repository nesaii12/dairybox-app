<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WalkInSaleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin DairyBox',
            'email' => 'admin@dairybox.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->customer = User::create([
            'name' => 'Regular Customer',
            'email' => 'customer@dairybox.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'status' => 'active',
        ]);

        $category = Category::create(['category_name' => 'Pasteurized Milk']);

        $this->product = Product::create([
            'category_id' => $category->id,
            'product_name' => 'Choco Fresh Milk',
            'description' => 'Rich chocolate dairy beverage',
            'milk_type' => 'Cow',
            'flavor' => 'Chocolate',
            'size' => 'Medium',
            'price' => 50.00,
            'stock' => 0,
            'expiration_date' => null,
            'status' => 'active',
        ]);

        // Add 25 units via FIFO batch
        InventoryService::addStock($this->product->id, 25, '2026-12-15', 'Initial production batch');
        $this->product->refresh();
    }

    public function test_dashboard_renders_walk_in_sale_button_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('New Walk-in Sale');
        $response->assertSee('openWalkInModal()');
        $response->assertSee('walkInModal');
    }

    public function test_walk_in_sale_create_page_renders_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.walkInSales.create'));

        $response->assertStatus(200);
        $response->assertSee('Record Walk-in Counter Sale');
        $response->assertSee('Choco Fresh Milk');
    }

    public function test_admin_can_record_walk_in_sale_deducting_stock_and_updating_sales(): void
    {
        $this->assertEquals(25, $this->product->fresh()->stock);

        $payload = [
            'customer_type' => 'walkin',
            'customer_name' => 'John Walk-in',
            'payment_method' => 'cash',
            'notes' => 'Paid with ₱500 bill',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'size' => 'Large',
                    'quantity' => 5,
                    'price' => 48.00, // custom price set by admin
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.walkInSales.store'), $payload);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');

        // 1. Assert Product Stock was immediately updated (25 - 5 = 20)
        $this->assertEquals(20, $this->product->fresh()->stock);

        // 2. Assert Order was created with order_type = 'Walk-in'
        $order = Order::where('order_type', 'Walk-in')->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals('Walk-in', $order->order_type);
        $this->assertEquals('Delivered', $order->order_status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('cash', $order->payment_method);
        $this->assertEquals(240.00, (float) $order->total_amount); // 5 * 48.00

        // 3. Assert OrderItem exists and size customization is recorded
        $orderItem = OrderItem::where('order_id', $order->id)->first();
        $this->assertNotNull($orderItem);
        $this->assertEquals(5, $orderItem->quantity);
        $this->assertEquals(48.00, (float) $orderItem->price);
        $this->assertEquals(240.00, (float) $orderItem->subtotal);

        $customization = Product::getOrderItemCustomization($orderItem->id, $this->product);
        $this->assertEquals('Large', $customization['size']);

        // 4. Assert Sale record was created for daily revenue summaries
        $sale = Sale::where('order_id', $order->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals(240.00, (float) $sale->total_amount);
        $this->assertEquals(now()->toDateString(), $sale->sales_date->toDateString());
    }

    public function test_walk_in_sale_insufficient_stock_fails(): void
    {
        $payload = [
            'customer_type' => 'walkin',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 50, // exceeds available 25
                    'price' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.walkInSales.store'), $payload);

        $response->assertSessionHas('error');
        // Stock should remain untouched
        $this->assertEquals(25, $this->product->fresh()->stock);
        $this->assertEquals(0, Order::where('order_type', 'Walk-in')->count());
    }

    public function test_customer_cannot_record_walk_in_sale(): void
    {
        $payload = [
            'customer_type' => 'walkin',
            'payment_method' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->customer)->post(route('admin.walkInSales.store'), $payload);
        $response->assertStatus(403);
    }
}
