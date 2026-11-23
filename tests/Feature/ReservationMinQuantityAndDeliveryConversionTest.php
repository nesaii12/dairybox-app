<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\RiderRemittance;
use App\Models\Sale;
use App\Models\User;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationMinQuantityAndDeliveryConversionTest extends TestCase
{
    protected User $admin;
    protected User $customer;
    protected User $rider;
    protected Product $freshMilk;

    protected function setUp(): void
    {
        parent::setUp();

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

                if (!Schema::hasTable('remittance_orders')) {
                    Schema::create('remittance_orders', function (Blueprint $table) {
                        $table->id();
                        $table->foreignId('remittance_id')->constrained('rider_remittances')->cascadeOnDelete();
                        $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                        $table->decimal('amount', 10, 2)->default(0.00);
                        $table->timestamps();
                    });
                }

                if (!Schema::hasTable('cash_ledger_entries')) {
                    Schema::create('cash_ledger_entries', function (Blueprint $table) {
                        $table->id();
                        $table->date('ledger_date');
                        $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                        $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
                        $table->string('rider_name');
                        $table->foreignId('remittance_id')->nullable()->constrained('rider_remittances')->nullOnDelete();
                        $table->string('entry_type')->default('RIDER_REMITTANCE');
                        $table->decimal('amount_expected', 10, 2)->default(0.00);
                        $table->decimal('amount_remitted', 10, 2)->default(0.00);
                        $table->decimal('variance', 10, 2)->default(0.00);
                        $table->string('payment_method')->default('Cash');
                        $table->text('notes')->nullable();
                        $table->timestamp('recorded_at');
                        $table->timestamps();
                    });
                }

                if (!Schema::hasColumn('orders', 'remittance_id')) {
                    Schema::table('orders', function (Blueprint $table) {
                        $table->unsignedBigInteger('remittance_id')->nullable()->after('rider_id');
                    });
                }
                continue;
            }

            Artisan::call('migrate', ['--path' => 'database/migrations/' . $basename, '--force' => true]);
        }

        // Relax SQLite enum check constraints on orders table to match MySQL VARCHAR behavior
        if (config('database.default') === 'sqlite') {
            Schema::dropIfExists('orders');
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('remittance_id')->nullable();
                $table->string('order_type', 50)->default('Pickup');
                $table->string('payment_method', 50)->default('cod');
                $table->string('payment_status', 50)->default('pending');
                $table->string('payment_channel')->nullable();
                $table->string('xendit_invoice_id')->nullable();
                $table->string('xendit_invoice_url')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('delivery_address')->nullable();
                $table->date('schedule_date');
                $table->time('schedule_time')->nullable();
                $table->decimal('subtotal', 10, 2);
                $table->decimal('delivery_fee', 10, 2)->default(0.00);
                $table->decimal('total_amount', 10, 2);
                $table->decimal('down_payment_paid', 10, 2)->default(0.00);
                $table->decimal('pending_balance', 10, 2)->nullable();
                $table->string('order_status', 50)->default('Pending');
                $table->boolean('cancelled_after_arrival')->default(false);
                $table->string('cancellation_reason')->nullable();
                $table->timestamps();
            });
        }

        $this->admin = User::create([
            'name' => 'Admin DairyBox',
            'email' => 'admin@dairybox.test',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->customer = User::create([
            'name' => 'Maria Customer',
            'email' => 'maria@dairybox.test',
            'phone' => '09171234567',
            'password' => bcrypt('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->rider = User::create([
            'name' => 'Carlo Rider',
            'email' => 'carlo@dairybox.test',
            'phone' => '09185556666',
            'password' => bcrypt('Password123!'),
            'role' => 'rider',
            'status' => 'active',
        ]);

        $category = Category::create(['category_name' => 'Fresh Milk']);

        $this->freshMilk = Product::create([
            'category_id' => $category->id,
            'product_name' => 'Fresh Milk',
            'description' => 'Farm-fresh pasteurized milk',
            'milk_type' => 'Cow',
            'size' => 'Medium',
            'price' => 50.00,
            'stock' => 0,
            'reserved_stock' => 0,
            'manufacturing_date' => '2026-11-20',
            'expiration_date' => '2026-12-20',
            'status' => 'active',
        ]);

        InventoryService::addStock($this->freshMilk->id, 100, '2026-12-20', 'Fresh batch intake', '2026-11-20');
        $this->freshMilk->refresh();
    }

    public function test_minimum_reservation_quantity_15_pieces_is_enforced_on_create_and_update(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 09:00:00', 'Asia/Manila'));

        try {
            // Create form includes min="15" and exact minimum error message text
            $this->actingAs($this->admin)
                ->get(route('admin.reservations.create'))
                ->assertStatus(200)
                ->assertSee('min="15"', false)
                ->assertSee('Minimum reservation is 15 pieces of products.');

            // Creating with 14 pieces is rejected with exact validation error
            $tooSmallCreate = $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
                'customer_name' => 'Juan Dela Cruz',
                'contact_number' => '09171112222',
                'product_id' => $this->freshMilk->id,
                'size' => 'Medium',
                'quantity' => 14,
                'unit_price' => 50.00,
                'reservation_date' => '2026-11-21',
                'pickup_date' => '2026-11-25',
                'down_payment' => 200.00,
                'payment_status' => 'Partially Paid',
                'status' => 'Confirmed',
            ]);
            $tooSmallCreate->assertSessionHasErrors([
                'quantity' => 'Minimum reservation is 15 pieces of products.',
            ]);
            $this->assertEquals(0, Reservation::count());

            // Creating with 15 pieces succeeds
            $validCreate = $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
                'customer_name' => 'Juan Dela Cruz',
                'contact_number' => '09171112222',
                'product_id' => $this->freshMilk->id,
                'size' => 'Medium',
                'quantity' => 15,
                'unit_price' => 50.00,
                'reservation_date' => '2026-11-21',
                'pickup_date' => '2026-11-25',
                'down_payment' => 250.00,
                'payment_status' => 'Partially Paid',
                'status' => 'Confirmed',
            ]);
            $validCreate->assertSessionHas('success');
            $reservation = Reservation::latest('id')->firstOrFail();
            $this->assertEquals(15, $reservation->quantity);

            // Edit form renders min="15" and minimum message
            $this->actingAs($this->admin)
                ->get(route('admin.reservations.edit', $reservation->id))
                ->assertStatus(200)
                ->assertSee('min="15"', false)
                ->assertSee('Minimum reservation is 15 pieces of products.');

            // Updating to 10 pieces is rejected with exact validation error
            $tooSmallUpdate = $this->actingAs($this->admin)->put(route('admin.reservations.update', $reservation->id), [
                'customer_name' => 'Juan Dela Cruz',
                'contact_number' => '09171112222',
                'product_id' => $this->freshMilk->id,
                'size' => 'Medium',
                'quantity' => 10,
                'unit_price' => 50.00,
                'reservation_date' => '2026-11-21',
                'pickup_date' => '2026-11-25',
                'down_payment' => 250.00,
                'payment_status' => 'Partially Paid',
                'status' => 'Confirmed',
            ]);
            $tooSmallUpdate->assertSessionHasErrors([
                'quantity' => 'Minimum reservation is 15 pieces of products.',
            ]);
            $this->assertEquals(15, $reservation->fresh()->quantity);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Full End-to-End Conversion Test (Subtotal < ₱3,000 -> ₱25.00 Delivery Fee + Pending COD Balance):
     * - 20 pcs @ ₱50 = ₱1,000 subtotal, ₱500 down payment paid
     * - Blocked before pickup date (Nov 21 vs pickup Nov 25)
     * - Converted on pickup date (Nov 25): Delivery Fee ₱25, Total ₱1,025, Pending COD Balance = ₱525
     * - Stock stays in Reserved Stock while Out for Delivery (100 physical / 20 reserved / 80 available)
     * - Rider Dashboard displays ₱500.00 Paid, Delivery Fee ₱25.00, Collect Cash (COD) ₱525.00
     * - Rider Confirm Delivered deducts Physical Stock (80), releases Reserved Stock (0), marks Reservation Completed,
     *   logs full Sale ₱1,025.00, and adds ONLY ₱525.00 COD into Rider Cash Remittance!
     */
    public function test_convert_unclaimed_pickup_under_3000_to_rider_delivery_with_pending_balance_and_remittance_sync(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 09:00:00', 'Asia/Manila'));

        try {
            $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
                'customer_name' => 'Elena Cruz',
                'contact_number' => '09179990000',
                'product_id' => $this->freshMilk->id,
                'size' => 'Medium',
                'quantity' => 20,
                'unit_price' => 50.00,
                'reservation_date' => '2026-11-21',
                'pickup_date' => '2026-11-25',
                'down_payment' => 500.00,
                'payment_status' => 'Partially Paid',
                'status' => 'Confirmed',
            ])->assertSessionHas('success');

            $reservation = Reservation::latest('id')->firstOrFail();

            // 1. Before scheduled pickup date (Nov 21 < Nov 25) -> conversion MUST be blocked
            $this->actingAs($this->admin)->post(route('admin.reservations.convertToDelivery', $reservation->id), [
                'delivery_address' => 'Purok 3, Brgy. Luna, Surigao City',
                'rider_id' => $this->rider->id,
            ])->assertSessionHas('error');

            $this->assertEquals('Confirmed', $reservation->fresh()->status);

            // 2. On scheduled pickup day (Nov 25, Asia/Manila)
            Carbon::setTestNow(Carbon::parse('2026-11-25 14:30:00', 'Asia/Manila'));

            // Validation requires delivery_address and rider_id
            $this->actingAs($this->admin)->post(route('admin.reservations.convertToDelivery', $reservation->id), [
                'delivery_address' => '',
                'rider_id' => '',
            ])->assertSessionHasErrors(['delivery_address', 'rider_id']);

            // Convert to Rider Delivery
            $this->actingAs($this->admin)->post(route('admin.reservations.convertToDelivery', $reservation->id), [
                'delivery_address' => 'Purok 3, Brgy. Luna, Surigao City',
                'rider_id' => $this->rider->id,
            ])->assertRedirect(route('admin.reservations.show', $reservation->id))
              ->assertSessionHas('success');

            $reservation->refresh();
            $this->freshMilk->refresh();
            $order = Order::findOrFail($reservation->order_id);
            $delivery = Delivery::where('order_id', $order->id)->firstOrFail();

            // Tiered Delivery Fee & COD Balance assertions
            $this->assertEquals(Reservation::STATUS_OUT_FOR_DELIVERY, $reservation->status);
            $this->assertEquals('Delivery', $reservation->fulfillment_type);
            $this->assertEquals(25.00, (float) $reservation->delivery_fee);
            $this->assertEquals(1025.00, (float) $reservation->total_amount);
            $this->assertEquals(525.00, (float) $reservation->remaining_balance);
            $this->assertEquals(1000.00, (float) $order->subtotal);
            $this->assertEquals(25.00, (float) $order->delivery_fee);
            $this->assertEquals(1025.00, (float) $order->total_amount);
            $this->assertEquals(500.00, (float) $order->down_payment_paid);
            $this->assertEquals(525.00, (float) $order->pending_balance);
            $this->assertEquals(525.00, $order->collectibleCodAmount());
            $this->assertEquals('cod', $order->payment_method);
            $this->assertEquals('Out for Delivery', $order->order_status);
            $this->assertEquals('Out for Delivery', $delivery->delivery_status);

            // Stock MUST remain locked in Reserved Stock while Out for Delivery (NO physical deduction yet, NO sale yet)
            $this->assertEquals(20, $reservation->allocated_quantity);
            $this->assertEquals(100, $this->freshMilk->stock);
            $this->assertEquals(20, $this->freshMilk->reserved_stock);
            $this->assertEquals(80, $this->freshMilk->available_stock);
            $this->assertEquals(0, Sale::count());

            // 3. Rider Dashboard (Active Queue) clearly displays Down Payment Paid, Delivery Fee, and Pending Balance COD
            $this->actingAs($this->rider)
                ->get(route('rider.dashboard'))
                ->assertStatus(200)
                ->assertSee($order->order_number)
                ->assertSee('Elena Cruz')
                ->assertSee('09179990000')
                ->assertSee('₱500.00 Paid')
                ->assertSee('₱25.00')
                ->assertSee('Pending Balance')
                ->assertSee('Collect Cash (COD): ₱525.00');

            // 4. Rider taps Confirm Delivered (with Asia/Manila timestamp)
            Carbon::setTestNow(Carbon::parse('2026-11-25 15:15:00', 'Asia/Manila'));

            $this->actingAs($this->rider)
                ->post(route('rider.deliveries.updateStatus', $delivery->id), [
                    'status' => 'Delivered',
                ])
                ->assertRedirect(route('rider.dashboard'))
                ->assertSessionHas('success');

            $reservation->refresh();
            $this->freshMilk->refresh();
            $order->refresh();
            $delivery->refresh();

            // Accurate Asia/Manila timestamps
            $this->assertNotNull($delivery->arrived_at);
            $this->assertNotNull($delivery->delivered_at);
            $this->assertEquals('2026-11-25 15:15:00', $delivery->delivered_at->timezone('Asia/Manila')->format('Y-m-d H:i:s'));

            // Physical Stock deducted, Reserved Stock released, Reservation Completed
            $this->assertEquals(Reservation::STATUS_COMPLETED, $reservation->status);
            $this->assertEquals(Reservation::PAYMENT_PAID, $reservation->payment_status);
            $this->assertEquals(0.00, (float) $reservation->remaining_balance);
            $this->assertEquals(0, $reservation->allocated_quantity);
            $this->assertEquals(80, $this->freshMilk->stock);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(80, $this->freshMilk->available_stock);

            // Single fulfilled Sale logged for FULL Order Total (₱1,025.00)
            $this->assertEquals(1, Sale::count());
            $this->assertEquals(1025.00, (float) Sale::first()->total_amount);
            $this->assertEquals('Delivered - Pending Remittance', $order->order_status);

            // 5. Rider Cash Remittance MUST sync ONLY the collected Pending Balance COD (₱525.00, NOT ₱1,025.00)
            $remitOrdersRes = $this->actingAs($this->admin)
                ->getJson(route('admin.remittances.riderOrders', $this->rider->id))
                ->assertStatus(200);

            $remitOrdersRes->assertJsonPath('total_pending', 525);
            $remitOrdersRes->assertJsonPath('orders_count', 1);
            $remitOrdersRes->assertJsonPath('orders.0.total_amount', 525);
            $remitOrdersRes->assertJsonPath('orders.0.order_total_sales', 1025);
            $remitOrdersRes->assertJsonPath('orders.0.down_payment_paid', 500);

            // Admin confirms Rider Remittance for ₱525.00 -> balanced (₱0.00 variance)
            $this->actingAs($this->admin)->post(route('admin.remittances.store'), [
                'rider_id' => $this->rider->id,
                'order_ids' => [$order->id],
                'actual_amount' => 525.00,
                'payment_method' => 'Cash',
                'action_type' => 'confirm',
                'notes' => 'Converted reservation COD balance handover',
            ])->assertRedirect(route('admin.remittances.index'))
              ->assertSessionHas('success');

            $remittance = RiderRemittance::latest('id')->firstOrFail();
            $this->assertEquals(525.00, (float) $remittance->expected_amount);
            $this->assertEquals(525.00, (float) $remittance->actual_amount);
            $this->assertEquals(0.00, (float) $remittance->variance);
            $this->assertEquals('Paid & Remitted', $order->fresh()->order_status);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Subtotal >= ₱3,000.00 (Free Delivery = ₱0.00) + Fully Paid Down Payment:
     * - 60 pcs @ ₱50 = ₱3,000.00 subtotal, ₱3,000.00 down payment paid
     * - Delivery Fee = ₱0.00 (Free), Pending Balance = ₱0.00
     * - Rider Dashboard displays "Fully Paid / ₱0.00 to Collect" & "Free"
     * - On Confirm Delivered: Order = Delivered (not pending remittance), Rider Cash Remittance = ₱0.00!
     */
    public function test_convert_pickup_3000_or_more_gets_free_delivery_and_fully_paid_shows_zero_to_collect(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 10:00:00', 'Asia/Manila'));

        try {
            $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
                'customer_name' => 'Bulk Fully Paid Client',
                'contact_number' => '09173334444',
                'product_id' => $this->freshMilk->id,
                'size' => 'Medium',
                'quantity' => 60,
                'unit_price' => 50.00,
                'reservation_date' => '2026-11-25',
                'pickup_date' => '2026-11-25',
                'down_payment' => 3000.00,
                'payment_status' => 'Fully Paid',
                'status' => 'Ready for Pickup',
            ])->assertSessionHas('success');

            $reservation = Reservation::latest('id')->firstOrFail();

            $this->actingAs($this->admin)->post(route('admin.reservations.convertToDelivery', $reservation->id), [
                'delivery_address' => 'Brgy. Washington, Surigao City',
                'rider_id' => $this->rider->id,
            ])->assertSessionHas('success');

            $reservation->refresh();
            $order = Order::findOrFail($reservation->order_id);
            $delivery = Delivery::where('order_id', $order->id)->firstOrFail();

            $this->assertEquals(0.00, (float) $reservation->delivery_fee);
            $this->assertEquals(3000.00, (float) $reservation->total_amount);
            $this->assertEquals(0.00, (float) $reservation->remaining_balance);
            $this->assertEquals(0.00, $order->collectibleCodAmount());
            $this->assertFalse($order->hasPendingBalance());
            $this->assertTrue($order->isPaid());

            // Rider Dashboard explicitly shows Fully Paid / ₱0.00 to Collect
            $this->actingAs($this->rider)
                ->get(route('rider.dashboard'))
                ->assertStatus(200)
                ->assertSee('₱3,000.00 Paid')
                ->assertSee('Free')
                ->assertSee('Fully Paid / ₱0.00 to Collect');

            // Confirm Delivered
            $this->actingAs($this->rider)
                ->post(route('rider.deliveries.updateStatus', $delivery->id), [
                    'status' => 'Delivered',
                ])
                ->assertSessionHas('success');

            $this->freshMilk->refresh();
            $this->assertEquals(40, $this->freshMilk->stock);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(Reservation::STATUS_COMPLETED, $reservation->fresh()->status);
            $this->assertEquals('Delivered', $order->fresh()->order_status);
            $this->assertEquals(3000.00, (float) Sale::first()->total_amount);

            // Rider Cash Remittance has ₱0.00 pending cash for this fully paid delivery
            $this->actingAs($this->admin)
                ->getJson(route('admin.remittances.riderOrders', $this->rider->id))
                ->assertStatus(200)
                ->assertJsonPath('total_pending', 0)
                ->assertJsonPath('orders_count', 0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_failed_converted_reservation_delivery_releases_reserved_stock_without_inflating_physical_stock(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 10:00:00', 'Asia/Manila'));

        try {
            $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
                'customer_name' => 'Unreachable Client',
                'contact_number' => '09174445555',
                'product_id' => $this->freshMilk->id,
                'size' => 'Medium',
                'quantity' => 20,
                'unit_price' => 50.00,
                'reservation_date' => '2026-11-25',
                'pickup_date' => '2026-11-25',
                'down_payment' => 500.00,
                'payment_status' => 'Partially Paid',
                'status' => 'Confirmed',
            ])->assertSessionHas('success');

            $reservation = Reservation::latest('id')->firstOrFail();

            $this->actingAs($this->admin)->post(route('admin.reservations.convertToDelivery', $reservation->id), [
                'delivery_address' => 'Brgy. Taft, Surigao City',
                'rider_id' => $this->rider->id,
            ])->assertSessionHas('success');

            $reservation->refresh();
            $delivery = Delivery::where('order_id', $reservation->order_id)->firstOrFail();

            // Rider reports Failed Delivery
            $this->actingAs($this->rider)
                ->post(route('rider.deliveries.updateStatus', $delivery->id), [
                    'status' => 'Failed Delivery',
                    'cancellation_reason' => 'Customer unreachable after multiple calls',
                ])
                ->assertSessionHas('success');

            $this->freshMilk->refresh();
            $reservation->refresh();

            // Physical stock MUST stay 100 (never inflated to 120!) and Reserved Stock released to 0
            $this->assertEquals(100, $this->freshMilk->stock);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(100, $this->freshMilk->available_stock);
            $this->assertEquals(Reservation::STATUS_CANCELLED, $reservation->status);
            $this->assertEquals(0, $reservation->allocated_quantity);
            $this->assertEquals(0, Sale::count());
        } finally {
            Carbon::setTestNow();
        }
    }
}
