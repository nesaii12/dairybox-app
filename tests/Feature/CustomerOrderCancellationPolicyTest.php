<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerOrderCancellationPolicyTest extends TestCase
{
    protected User $admin;
    protected User $customer;
    protected User $rider;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Run SQLite-compatible setup
        $migrationFiles = glob(database_path('migrations/*.php'));
        sort($migrationFiles);

        foreach ($migrationFiles as $file) {
            $basename = basename($file);
            if ($basename === '2026_11_11_000001_create_rider_remittances_and_cash_ledger_tables.php') {
                if (!Schema::hasTable('rider_remittances')) {
                    Schema::create('rider_remittances', function (Blueprint $table) {
                        $table->id();
                        $table->string('reference_no')->unique();
                        $table->foreignId('rider_id')->constrained('users')->cascadeOnDelete();
                        $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                        $table->integer('orders_count')->default(0);
                        $table->decimal('expected_amount', 10, 2)->default(0.00);
                        $table->decimal('actual_amount', 10, 2)->default(0.00);
                        $table->decimal('variance', 10, 2)->default(0.00);
                        $table->string('status')->default('pending_verification');
                        $table->string('payment_method')->default('Cash');
                        $table->text('notes')->nullable();
                        $table->timestamp('verified_at')->nullable();
                        $table->timestamps();
                    });
                }

                if (!Schema::hasTable('cash_ledger_entries')) {
                    Schema::create('cash_ledger_entries', function (Blueprint $table) {
                        $table->id();
                        $table->string('transaction_code')->unique();
                        $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                        $table->foreignId('remittance_id')->nullable()->constrained('rider_remittances')->nullOnDelete();
                        $table->string('entry_type');
                        $table->decimal('amount', 10, 2);
                        $table->decimal('running_balance', 10, 2)->default(0.00);
                        $table->text('description')->nullable();
                        $table->timestamp('entry_date');
                        $table->timestamps();
                    });
                }
                continue;
            }

            $migration = require $file;
            if (is_object($migration) && method_exists($migration, 'up')) {
                try {
                    $migration->up();
                } catch (\Throwable $e) {
                    // Ignore already existing tables/columns
                }
            }
        }

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_test@dairybox.test'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'status' => 'active',
                'phone' => '09170000001',
            ]
        );

        $this->customer = User::firstOrCreate(
            ['email' => 'customer_test@dairybox.test'],
            [
                'name' => 'Diane Customer',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'status' => 'active',
                'phone' => '09503879109',
                'email_verified_at' => now(),
            ]
        );

        $this->rider = User::firstOrCreate(
            ['email' => 'rider_test@dairybox.test'],
            [
                'name' => 'Fast Rider',
                'password' => bcrypt('password'),
                'role' => 'rider',
                'status' => 'active',
                'phone' => '09170000002',
            ]
        );

        $category = Category::firstOrCreate(['category_name' => 'Dairy'], ['description' => 'Dairy products']);

        $this->product = Product::firstOrCreate(
            ['product_name' => 'Fresh Yogurt Test'],
            [
                'category_id' => $category->id,
                'milk_type' => 'Cow',
                'size' => 'Small',
                'price' => 25.00,
                'stock' => 100,
                'expiration_date' => now()->addDays(14)->toDateString(),
            ]
        );
    }

    public function test_customer_cancelling_pending_order_does_not_apply_restriction(): void
    {
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => $this->customer->id,
            'order_type' => 'Pickup',
            'payment_method' => 'cod',
            'payment_status' => 'Unpaid',
            'schedule_date' => now()->toDateString(),
            'subtotal' => 25.00,
            'delivery_fee' => 0.00,
            'total_amount' => 25.00,
            'order_status' => 'Pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 25.00,
            'subtotal' => 25.00,
        ]);

        $response = $this->actingAs($this->customer)
            ->from(route('customer.orders.show', $order->id))
            ->post(route('customer.orders.cancel', $order->id), [
                'cancellation_reason' => 'Changed my mind',
            ]);

        $response->assertRedirect(route('customer.orders.show', $order->id));

        $order->refresh();
        $this->customer->refresh();

        // 1. Order status should be recognized and displayed as Cancelled by Customer
        $this->assertTrue($order->isCancelledByCustomer());
        $this->assertEquals('Cancelled by Customer', $order->statusBadgeLabel());

        // 2. Customer must NOT be restricted
        $this->assertFalse($this->customer->isRestricted());
        $this->assertEquals('active', $this->customer->status);
        $this->assertNull($this->customer->restricted_until);
    }

    public function test_admin_ui_shows_cancelled_by_customer_without_restrict_button_for_undispatched_order(): void
    {
        $order = Order::create([
            'order_number' => 'DB-20261124-0002',
            'customer_id' => $this->customer->id,
            'order_type' => 'Pickup',
            'payment_method' => 'cod',
            'payment_status' => 'Unpaid',
            'schedule_date' => now()->toDateString(),
            'subtotal' => 25.00,
            'delivery_fee' => 0.00,
            'total_amount' => 25.00,
            'order_status' => 'Cancelled',
            'cancellation_reason' => 'Changed my mind',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price' => 25.00,
            'subtotal' => 25.00,
        ]);

        $this->assertFalse($order->isDispatched());
        $this->assertTrue($order->isCancelledByCustomer());
        $this->assertEquals('Cancelled by Customer', $order->statusBadgeLabel());

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order->id));

        $response->assertOk();
        $response->assertSee('Order Cancelled by Customer');
        $response->assertSee('Cancelled by Customer');
        $response->assertSee('No restriction applied: Order was not dispatched');
        $response->assertDontSee('Restrict Customer (3 Days)');
    }

    public function test_admin_cannot_restrict_customer_for_an_undispatched_order(): void
    {
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => $this->customer->id,
            'order_type' => 'Delivery',
            'payment_method' => 'cod',
            'payment_status' => 'Unpaid',
            'schedule_date' => now()->toDateString(),
            'subtotal' => 50.00,
            'delivery_fee' => 50.00,
            'total_amount' => 100.00,
            'order_status' => 'Cancelled',
            'cancellation_reason' => 'Changed my mind',
        ]);

        $this->assertFalse($order->isDispatched());

        // Attempting to restrict customer referencing undispatched order
        $response = $this->actingAs($this->admin)
            ->post(route('admin.customers.restrict', $this->customer->id), [
                'reason' => "Cancelled Order #{$order->order_number}: Customer cancellation",
                'order_id' => $order->id,
            ]);

        $this->customer->refresh();

        // Must reject the restriction
        $this->assertFalse($this->customer->isRestricted());
        $this->assertEquals('active', $this->customer->status);
        $response->assertSessionHas('error');
    }

    public function test_admin_can_restrict_customer_only_after_order_was_dispatched(): void
    {
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => $this->customer->id,
            'rider_id' => $this->rider->id,
            'order_type' => 'Delivery',
            'payment_method' => 'cod',
            'payment_status' => 'Unpaid',
            'schedule_date' => now()->toDateString(),
            'subtotal' => 50.00,
            'delivery_fee' => 50.00,
            'total_amount' => 100.00,
            'order_status' => 'Out for Delivery',
        ]);

        Delivery::create([
            'order_id' => $order->id,
            'rider_id' => $this->rider->id,
            'delivery_status' => 'Out for Delivery',
        ]);

        $this->assertTrue($order->isDispatched());

        // Cancel order post-dispatch
        $order->update([
            'order_status' => 'Cancelled',
            'cancellation_reason' => 'Customer refused handover after rider dispatched',
        ]);

        // Restriction should be permitted because order was dispatched
        $response = $this->actingAs($this->admin)
            ->post(route('admin.customers.restrict', $this->customer->id), [
                'reason' => "Cancelled Order #{$order->order_number}: Refused handover after dispatch",
                'order_id' => $order->id,
            ]);

        $this->customer->refresh();

        $this->assertTrue($this->customer->isRestricted());
        $this->assertEquals('restricted', $this->customer->status);
        $this->assertNotNull($this->customer->restricted_until);
        $response->assertSessionHas('success');
    }
}
