<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Forecast;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\User;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationManagementTest extends TestCase
{
    protected User $admin;
    protected User $customer;
    protected Product $freshMilk;

    protected function setUp(): void
    {
        parent::setUp();

        // Run all SQLite-compatible migrations on the isolated in-memory test DB
        // (avoids modifying the pre-existing MySQL-only ALTER statement in 2026_11_11_000001).
        $migrationFiles = glob(database_path('migrations/*.php'));
        sort($migrationFiles);

        foreach ($migrationFiles as $file) {
            $basename = basename($file);
            if ($basename === '2026_11_11_000001_create_rider_remittances_and_cash_ledger_tables.php') {
                if (!Schema::hasColumn('orders', 'remittance_id')) {
                    Schema::table('orders', function (Blueprint $table) {
                        $table->unsignedBigInteger('remittance_id')->nullable()->after('rider_id');
                    });
                }
                continue;
            }

            $relativePath = 'database/migrations/' . $basename;
            Artisan::call('migrate', ['--path' => $relativePath, '--force' => true]);
        }

        // Relax SQLite enum check constraints on orders.order_type so 'Walk-in' and 'Reservation' insert cleanly just like MySQL VARCHAR(50)
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
            'password' => bcrypt('Password123!'),
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
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
            'expiration_date' => '2026-12-10',
            'status' => 'active',
        ]);

        // Seed 35 pcs physical stock expiring Dec 10, 2026 (>= Nov 27, 2026)
        InventoryService::addStock($this->freshMilk->id, 35, '2026-12-10', 'Fresh batch intake', '2026-11-20');
        $this->freshMilk->refresh();
    }

    /**
     * Core Verification Scenario:
     * 35 pcs Fresh Milk physical stock
     * -> reserve & confirm 30 pcs for Nov 27 with down payment (35 physical / 30 reserved / 5 available)
     * -> online order for 10 pcs is rejected with exact anti-overselling message
     * -> walk-in sale for 10 pcs is also rejected
     * -> mark Ready for Pickup (no duplicate allocation: still 35 / 30 / 5)
     * -> mark Completed on Nov 27 (5 physical / 0 reserved / 5 available, single fulfilled sale logged)
     */
    public function test_full_reservation_lifecycle_and_anti_overselling_verification(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 09:00:00', 'Asia/Manila'));

        try {
            // Initial state: 35 physical / 0 reserved / 35 available
            $this->assertEquals(35, $this->freshMilk->stock);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(35, $this->freshMilk->available_stock);

            // 1. Create Pending reservation for 30 pcs for Nov 27 with ₱500 down payment (Total = ₱1,500)
            $createResponse = $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
                'customer_name' => 'Juan Dela Cruz',
                'contact_number' => '09171234567',
                'product_id' => $this->freshMilk->id,
                'size' => 'Medium',
                'quantity' => 30,
                'unit_price' => 50.00,
                'reservation_date' => '2026-11-21',
                'pickup_date' => '2026-11-27',
                'down_payment' => 500.00,
                'payment_status' => 'Partially Paid',
                'status' => 'Pending',
                'notes' => 'Community event bulk order',
            ]);

            $reservation = Reservation::latest('id')->firstOrFail();
            $createResponse->assertRedirect(route('admin.reservations.show', $reservation->id));

            // Pending MUST NOT allocate stock or create a completed sale
            $this->freshMilk->refresh();
            $this->assertEquals('Pending', $reservation->status);
            $this->assertEquals('Partially Paid', $reservation->payment_status);
            $this->assertEquals(1500.00, (float) $reservation->total_amount);
            $this->assertEquals(500.00, (float) $reservation->down_payment);
            $this->assertEquals(1000.00, (float) $reservation->remaining_balance);
            $this->assertEquals(0, $reservation->allocated_quantity);
            $this->assertEquals(35, $this->freshMilk->stock);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(35, $this->freshMilk->available_stock);
            $this->assertEquals(0, Sale::count());

            // 2. Confirm reservation -> 35 physical / 30 reserved / 5 available
            $confirmResponse = $this->actingAs($this->admin)
                ->post(route('admin.reservations.confirm', $reservation->id));
            $confirmResponse->assertSessionHas('success');

            $reservation->refresh();
            $this->freshMilk->refresh();
            $this->assertEquals('Confirmed', $reservation->status);
            $this->assertEquals(30, $reservation->allocated_quantity);
            $this->assertEquals(35, $this->freshMilk->stock);
            $this->assertEquals(30, $this->freshMilk->reserved_stock);
            $this->assertEquals(5, $this->freshMilk->available_stock);
            // Down payment must NOT be counted as completed sale beforehand
            $this->assertEquals(0, Sale::count());

            // 3. Re-confirming or moving to Ready for Pickup MUST NOT duplicate allocation
            $this->actingAs($this->admin)->post(route('admin.reservations.ready', $reservation->id))
                ->assertSessionHas('success');

            $reservation->refresh();
            $this->freshMilk->refresh();
            $this->assertEquals('Ready for Pickup', $reservation->status);
            $this->assertEquals(30, $reservation->allocated_quantity);
            $this->assertEquals(35, $this->freshMilk->stock);
            $this->assertEquals(30, $this->freshMilk->reserved_stock);
            $this->assertEquals(5, $this->freshMilk->available_stock);

            // 4. Online customer order for 10 pcs is REJECTED with exact message
            $cart = Cart::create(['customer_id' => $this->customer->id]);
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $this->freshMilk->id,
                'quantity' => 10,
                'subtotal' => 500.00,
            ]);

            $checkoutResponse = $this->actingAs($this->customer)->post(route('customer.checkout.placeOrder'), [
                'item_ids' => [$cartItem->id],
                'order_type' => 'Pickup',
                'schedule_date' => '2026-11-21',
                'schedule_time' => '13:00:00',
                'payment_method' => 'cod',
            ]);

            $checkoutResponse->assertRedirect(route('customer.cart.index'));
            $checkoutResponse->assertSessionHas('error', 'Sorry! Only 5 pieces of Fresh Milk are currently available.');

            // Also verify Walk-in POS for 10 pcs is REJECTED with exact message
            $walkInResponse = $this->actingAs($this->admin)->post(route('admin.walkInSales.store'), [
                'customer_type' => 'walkin',
                'payment_method' => 'cash',
                'items' => [
                    [
                        'product_id' => $this->freshMilk->id,
                        'size' => 'Medium',
                        'quantity' => 10,
                        'price' => 50.00,
                    ],
                ],
            ]);
            $walkInResponse->assertSessionHas('error', 'Sorry! Only 5 pieces of Fresh Milk are currently available.');

            // Stock remains untouched (35 / 30 / 5) and no Order/Sale created
            $this->freshMilk->refresh();
            $this->assertEquals(35, $this->freshMilk->stock);
            $this->assertEquals(30, $this->freshMilk->reserved_stock);
            $this->assertEquals(5, $this->freshMilk->available_stock);
            $this->assertEquals(0, Order::count());
            $this->assertEquals(0, Sale::count());

            // 5. Mark Completed on Nov 27 -> 5 physical / 0 reserved / 5 available + single fulfilled Sale
            Carbon::setTestNow(Carbon::parse('2026-11-27 10:00:00', 'Asia/Manila'));

            $completeResponse = $this->actingAs($this->admin)
                ->post(route('admin.reservations.complete', $reservation->id));
            $completeResponse->assertSessionHas('success');

            $reservation->refresh();
            $this->freshMilk->refresh();

            $this->assertEquals('Completed', $reservation->status);
            $this->assertEquals('Fully Paid', $reservation->payment_status);
            $this->assertEquals(0.00, (float) $reservation->remaining_balance);
            $this->assertEquals(0, $reservation->allocated_quantity);
            $this->assertNotNull($reservation->order_id);

            // Final inventory check: 5 physical / 0 reserved / 5 available
            $this->assertEquals(5, $this->freshMilk->stock);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(5, $this->freshMilk->available_stock);

            // Single fulfilled Sale logged for exact total_amount (₱1,500.00) on Nov 27
            $this->assertEquals(1, Sale::count());
            $sale = Sale::firstOrFail();
            $this->assertEquals(1500.00, (float) $sale->total_amount);
            $this->assertEquals('2026-11-27', $sale->sales_date->toDateString());
            $this->assertEquals('Reservation', $sale->order->order_type);
            $this->assertEquals('Delivered', $sale->order->order_status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reservation_confirmation_validates_batch_expiry_against_pickup_date(): void
    {
        // Product batch expires 2026-12-10; try confirming pickup on 2026-12-20
        $response = $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
            'customer_name' => 'Ana Santos',
            'contact_number' => '09189998888',
            'product_id' => $this->freshMilk->id,
            'size' => 'Medium',
            'quantity' => 10,
            'unit_price' => 50.00,
            'reservation_date' => '2026-11-21',
            'pickup_date' => '2026-12-20',
            'down_payment' => 100.00,
            'status' => 'Confirmed',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Reservation::count());
        $this->assertEquals(0, $this->freshMilk->fresh()->reserved_stock);
    }

    public function test_cancelling_confirmed_reservation_releases_reserved_stock_back_to_available(): void
    {
        $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
            'customer_name' => 'Pedro Reyes',
            'contact_number' => '09170001111',
            'product_id' => $this->freshMilk->id,
            'size' => 'Medium',
            'quantity' => 20,
            'unit_price' => 50.00,
            'reservation_date' => '2026-11-21',
            'pickup_date' => '2026-11-27',
            'down_payment' => 200.00,
            'status' => 'Confirmed',
        ]);

        $reservation = Reservation::latest('id')->firstOrFail();
        $this->freshMilk->refresh();
        $this->assertEquals(35, $this->freshMilk->stock);
        $this->assertEquals(20, $this->freshMilk->reserved_stock);
        $this->assertEquals(15, $this->freshMilk->available_stock);

        $this->actingAs($this->admin)->post(route('admin.reservations.cancel', $reservation->id), [
            'cancellation_reason' => 'Customer rescheduled',
        ])->assertSessionHas('success');

        $reservation->refresh();
        $this->freshMilk->refresh();

        $this->assertEquals('Cancelled', $reservation->status);
        $this->assertEquals(0, $reservation->allocated_quantity);
        $this->assertEquals(35, $this->freshMilk->stock);
        $this->assertEquals(0, $this->freshMilk->reserved_stock);
        $this->assertEquals(35, $this->freshMilk->available_stock);
        $this->assertEquals(0, Sale::count());
    }

    public function test_editing_confirmed_reservation_updates_allocation_without_duplication(): void
    {
        $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
            'customer_name' => 'Clara Lim',
            'contact_number' => '09172223333',
            'product_id' => $this->freshMilk->id,
            'size' => 'Medium',
            'quantity' => 15,
            'unit_price' => 50.00,
            'reservation_date' => '2026-11-21',
            'pickup_date' => '2026-11-27',
            'down_payment' => 250.00,
            'status' => 'Confirmed',
        ]);

        $reservation = Reservation::latest('id')->firstOrFail();
        $this->assertEquals(15, $this->freshMilk->fresh()->reserved_stock);

        // Edit quantity from 15 -> 25 and down payment from 250 -> 500
        $this->actingAs($this->admin)->put(route('admin.reservations.update', $reservation->id), [
            'customer_name' => 'Clara Lim',
            'contact_number' => '09172223333',
            'product_id' => $this->freshMilk->id,
            'size' => 'Medium',
            'quantity' => 25,
            'unit_price' => 50.00,
            'reservation_date' => '2026-11-21',
            'pickup_date' => '2026-11-27',
            'down_payment' => 500.00,
            'payment_status' => 'Partially Paid',
            'status' => 'Confirmed',
            'notes' => 'Increased order quantity',
        ])->assertRedirect(route('admin.reservations.show', $reservation->id));

        $reservation->refresh();
        $this->freshMilk->refresh();

        $this->assertEquals(25, $reservation->quantity);
        $this->assertEquals(25, $reservation->allocated_quantity);
        $this->assertEquals(1250.00, (float) $reservation->total_amount);
        $this->assertEquals(750.00, (float) $reservation->remaining_balance);
        $this->assertEquals(35, $this->freshMilk->stock);
        $this->assertEquals(25, $this->freshMilk->reserved_stock);
        $this->assertEquals(10, $this->freshMilk->available_stock);
    }

    public function test_admin_reservation_views_filters_and_role_protection(): void
    {
        $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
            'customer_name' => 'Searchable Client',
            'contact_number' => '09998887777',
            'product_id' => $this->freshMilk->id,
            'size' => 'Medium',
            'quantity' => 5,
            'unit_price' => 50.00,
            'reservation_date' => '2026-11-21',
            'pickup_date' => '2026-11-27',
            'down_payment' => 100.00,
            'status' => 'Confirmed',
            'notes' => 'Birthday party',
        ]);

        $reservation = Reservation::latest('id')->firstOrFail();

        // Index renders with search, status, and pickup_date filters
        $this->actingAs($this->admin)
            ->get(route('admin.reservations.index', [
                'search' => 'Searchable',
                'status' => 'Confirmed',
                'pickup_date' => '2026-11-27',
            ]))
            ->assertStatus(200)
            ->assertSee($reservation->reservation_code)
            ->assertSee('Searchable Client')
            ->assertSee('Fresh Milk');

        // Create, Show, and Edit pages render cleanly
        $this->actingAs($this->admin)->get(route('admin.reservations.create'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('admin.reservations.show', $reservation->id))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('admin.reservations.edit', $reservation->id))->assertStatus(200);

        // Pre-seed forecast row for rolling target month so SQLite doesn't invoke MySQL YEAR() in ForecastService
        Forecast::create([
            'product_id' => $this->freshMilk->id,
            'forecast_month' => '2027-01',
            'predicted_sales' => 50,
        ]);

        // Existing admin pages continue working cleanly with Reservation Management integrated
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertStatus(200)->assertSee('Reservation Management');
        $this->actingAs($this->admin)->get(route('admin.inventory.index'))->assertStatus(200)->assertSee('Stock Breakdown');
        $this->actingAs($this->admin)->get(route('admin.walkInSales.create'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertStatus(200);

        // Non-admin customer is blocked (redirected on web, 403 on JSON)
        $this->actingAs($this->customer)->get(route('admin.reservations.index'))->assertRedirect(route('customer.catalog'));
        $this->actingAs($this->customer)->getJson(route('admin.reservations.index'))->assertStatus(403);
    }
}
