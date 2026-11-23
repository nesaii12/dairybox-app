<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CashLedgerEntry;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationCancellationPolicyTest extends TestCase
{
    protected User $admin;
    protected User $customer;
    protected Product $freshMilk;

    protected function setUp(): void
    {
        parent::setUp();

        // Run all SQLite-compatible migrations on the isolated in-memory test DB
        $migrationFiles = glob(database_path('migrations/*.php'));
        sort($migrationFiles);

        foreach ($migrationFiles as $file) {
            $basename = basename($file);
            if ($basename === '2026_11_11_000001_create_rider_remittances_and_cash_ledger_tables.php') {
                // Create rider_remittances and cash_ledger_entries in SQLite (skipping MySQL-only ALTER)
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
            'expiration_date' => '2026-12-20',
            'status' => 'active',
        ]);

        InventoryService::addStock($this->freshMilk->id, 100, '2026-12-20', 'Fresh batch intake', '2026-11-20');
        $this->freshMilk->refresh();
    }

    /**
     * Helper: create a Confirmed reservation with ₱500.00 down payment (20 pcs @ ₱50 = ₱1,000 total).
     */
    protected function createConfirmedReservationWithDownPayment(float $downPayment = 500.00, int $qty = 20): Reservation
    {
        $response = $this->actingAs($this->admin)->post(route('admin.reservations.store'), [
            'customer_name' => 'Juan Dela Cruz',
            'contact_number' => '09171234567',
            'product_id' => $this->freshMilk->id,
            'size' => 'Medium',
            'quantity' => $qty,
            'unit_price' => 50.00,
            'reservation_date' => '2026-11-21',
            'pickup_date' => '2026-11-28',
            'down_payment' => $downPayment,
            'payment_status' => 'Partially Paid',
            'status' => 'Confirmed',
            'notes' => 'Advance order',
        ]);

        $response->assertSessionHas('success');

        return Reservation::latest('id')->firstOrFail();
    }

    public function test_admin_settings_persists_cancellation_policy_in_database_and_renders_live_preview(): void
    {
        // Initial DB-seeded default from migration is Enabled with 20% fee / 80% refund
        $initialPolicy = SettingService::getReservationCancellationPolicy();
        $this->assertTrue($initialPolicy['enabled']);
        $this->assertEquals(20.0, $initialPolicy['fee_percentage']);
        $this->assertEquals(80.0, $initialPolicy['refund_percentage']);
        $this->assertTrue($initialPolicy['persisted']);

        // Admin Settings page displays Reservation Cancellation Policy section with live DB summary
        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertStatus(200)
            ->assertSee('Reservation Cancellation Policy')
            ->assertSee('Cancellation Fee: 20% | Refund Percentage: 80%')
            ->assertSee('Save Changes');

        // Validation rejects out-of-range percentages (< 0 or > 100)
        $this->actingAs($this->admin)
            ->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '1',
                'cancellation_fee_percentage' => 120,
                'cancellation_policy_description' => 'Invalid fee',
            ])
            ->assertSessionHasErrors('cancellation_fee_percentage');

        // Update policy to 15% and verify persistence in DB settings table + AuditLog
        $this->actingAs($this->admin)
            ->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '1',
                'cancellation_fee_percentage' => 15,
                'cancellation_policy_description' => 'Updated 15% cancellation policy for advance dairy reservations.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => SettingService::KEY_CANCELLATION_FEE_ENABLED,
            'value' => '1',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => SettingService::KEY_CANCELLATION_FEE_PERCENTAGE,
            'value' => '15',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => SettingService::KEY_CANCELLATION_POLICY_DESCRIPTION,
            'value' => 'Updated 15% cancellation policy for advance dairy reservations.',
        ]);

        $updated = SettingService::getReservationCancellationPolicy();
        $this->assertTrue($updated['enabled']);
        $this->assertEquals(15.0, $updated['fee_percentage']);
        $this->assertEquals(85.0, $updated['refund_percentage']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'RESERVATION_POLICY_UPDATED',
            'user_id' => $this->admin->id,
        ]);

        // Non-admin customer is blocked from updating policy
        $this->actingAs($this->customer)
            ->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '0',
                'cancellation_fee_percentage' => 0,
            ])
            ->assertRedirect(route('customer.catalog'));
    }

    /**
     * Verification Test A:
     * Down payment ₱500, Fee 20% (Customer cancels) -> Fee ₱100, Refund ₱400, Refund Status = Pending.
     */
    public function test_scenario_a_fee_20_percent_customer_cancels(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 10:00:00', 'Asia/Manila'));

        try {
            $this->actingAs($this->admin)->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '1',
                'cancellation_fee_percentage' => 20,
                'cancellation_policy_description' => '20% cancellation fee applies.',
            ])->assertSessionHas('success');

            $reservation = $this->createConfirmedReservationWithDownPayment(500.00, 20);
            $this->freshMilk->refresh();
            $this->assertEquals(20, $this->freshMilk->reserved_stock);
            $this->assertEquals(80, $this->freshMilk->available_stock);

            $this->actingAs($this->admin)->post(route('admin.reservations.cancel', $reservation->id), [
                'cancellation_initiated_by' => 'customer',
                'cancellation_reason' => 'Customer changed event date',
            ])->assertSessionHas('success');

            $reservation->refresh();
            $this->freshMilk->refresh();

            $this->assertEquals(Reservation::STATUS_CANCELLED, $reservation->status);
            $this->assertEquals(Reservation::CANCELLED_BY_CUSTOMER, $reservation->cancellation_initiated_by);
            $this->assertEquals(20.00, (float) $reservation->applied_fee_percentage);
            $this->assertEquals(100.00, (float) $reservation->cancellation_fee_amount);
            $this->assertEquals(400.00, (float) $reservation->refund_amount);
            $this->assertEquals(Reservation::REFUND_PENDING, $reservation->refund_status);
            $this->assertNull($reservation->refund_confirmed_at);

            // Reserved Stock released back to Available Stock
            $this->assertEquals(0, $reservation->allocated_quantity);
            $this->assertEquals(100, $this->freshMilk->stock);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(100, $this->freshMilk->available_stock);

            // Forfeited fee MUST NEVER be counted as product unit sale in Orders / OrderItems / Sales / Forecasting
            $this->assertEquals(0, Order::count());
            $this->assertEquals(0, OrderItem::count());
            $this->assertEquals(0, Sale::count());

            // Cash Ledger: forfeited cancellation fee logged, refund NOT yet logged until explicit confirmation
            $this->assertEquals(1, CashLedgerEntry::where('entry_type', 'RESERVATION_CANCELLATION_FEE')
                ->where('reservation_id', $reservation->id)
                ->where('amount_remitted', 100.00)
                ->count());
            $this->assertEquals(0, CashLedgerEntry::where('entry_type', 'RESERVATION_REFUND')->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Verification Test B:
     * Down payment ₱500, Fee 10% (Customer cancels) -> Fee ₱50, Refund ₱450, Refund Status = Pending.
     */
    public function test_scenario_b_fee_10_percent_customer_cancels(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 10:00:00', 'Asia/Manila'));

        try {
            $this->actingAs($this->admin)->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '1',
                'cancellation_fee_percentage' => 10,
                'cancellation_policy_description' => '10% cancellation fee applies.',
            ])->assertSessionHas('success');

            $reservation = $this->createConfirmedReservationWithDownPayment(500.00, 15);

            $this->actingAs($this->admin)->post(route('admin.reservations.cancel', $reservation->id), [
                'cancellation_initiated_by' => 'customer',
                'cancellation_reason' => 'Customer cancelled advance order',
            ])->assertSessionHas('success');

            $reservation->refresh();
            $this->freshMilk->refresh();

            $this->assertEquals(Reservation::STATUS_CANCELLED, $reservation->status);
            $this->assertEquals(Reservation::CANCELLED_BY_CUSTOMER, $reservation->cancellation_initiated_by);
            $this->assertEquals(10.00, (float) $reservation->applied_fee_percentage);
            $this->assertEquals(50.00, (float) $reservation->cancellation_fee_amount);
            $this->assertEquals(450.00, (float) $reservation->refund_amount);
            $this->assertEquals(Reservation::REFUND_PENDING, $reservation->refund_status);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(100, $this->freshMilk->available_stock);
            $this->assertEquals(0, Sale::count());
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Verification Test C:
     * Down payment ₱500, Fee Disabled (Customer cancels) -> Fee ₱0, Refund ₱500, Refund Status = Pending.
     */
    public function test_scenario_c_fee_disabled_customer_cancels_gets_full_refund(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 10:00:00', 'Asia/Manila'));

        try {
            $this->actingAs($this->admin)->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '0',
                'cancellation_fee_percentage' => 20, // Even when 20% is stored, disabled toggle forces ₱0 fee
                'cancellation_policy_description' => 'Cancellation fee temporarily waived.',
            ])->assertSessionHas('success');

            $reservation = $this->createConfirmedReservationWithDownPayment(500.00, 15);

            $this->actingAs($this->admin)->post(route('admin.reservations.cancel', $reservation->id), [
                'cancellation_initiated_by' => 'customer',
                'cancellation_reason' => 'Customer requested cancellation during waived period',
            ])->assertSessionHas('success');

            $reservation->refresh();
            $this->freshMilk->refresh();

            $this->assertEquals(Reservation::STATUS_CANCELLED, $reservation->status);
            $this->assertEquals(Reservation::CANCELLED_BY_CUSTOMER, $reservation->cancellation_initiated_by);
            $this->assertEquals(0.00, (float) $reservation->applied_fee_percentage);
            $this->assertEquals(0.00, (float) $reservation->cancellation_fee_amount);
            $this->assertEquals(500.00, (float) $reservation->refund_amount);
            $this->assertEquals(Reservation::REFUND_PENDING, $reservation->refund_status);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(100, $this->freshMilk->available_stock);
            $this->assertEquals(0, CashLedgerEntry::where('entry_type', 'RESERVATION_CANCELLATION_FEE')->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Verification Test D:
     * Down payment ₱500, Business cancels (any fee %, e.g. 35% enabled) -> Fee ₱0, Refund ₱500, Refund Status = Pending.
     */
    public function test_scenario_d_business_cancels_overrides_any_fee_percentage_with_full_refund(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 10:00:00', 'Asia/Manila'));

        try {
            $this->actingAs($this->admin)->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '1',
                'cancellation_fee_percentage' => 35,
                'cancellation_policy_description' => '35% customer cancellation fee.',
            ])->assertSessionHas('success');

            $reservation = $this->createConfirmedReservationWithDownPayment(500.00, 15);

            $this->actingAs($this->admin)->post(route('admin.reservations.cancel', $reservation->id), [
                'cancellation_initiated_by' => 'business',
                'cancellation_reason' => 'Unable to fulfill due to production equipment maintenance',
            ])->assertSessionHas('success');

            $reservation->refresh();
            $this->freshMilk->refresh();

            $this->assertEquals(Reservation::STATUS_CANCELLED, $reservation->status);
            $this->assertEquals(Reservation::CANCELLED_BY_BUSINESS, $reservation->cancellation_initiated_by);
            $this->assertEquals(0.00, (float) $reservation->applied_fee_percentage);
            $this->assertEquals(0.00, (float) $reservation->cancellation_fee_amount);
            $this->assertEquals(500.00, (float) $reservation->refund_amount);
            $this->assertEquals(Reservation::REFUND_PENDING, $reservation->refund_status);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(100, $this->freshMilk->available_stock);
            $this->assertEquals(0, CashLedgerEntry::where('entry_type', 'RESERVATION_CANCELLATION_FEE')->count());
            $this->assertEquals(0, Sale::count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_historical_snapshot_is_immutable_after_future_policy_edits_and_refund_requires_explicit_confirmation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 10:00:00', 'Asia/Manila'));

        try {
            // 1. Cancel reservation under 20% policy -> Fee ₱100, Refund ₱400 (Pending)
            $this->actingAs($this->admin)->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '1',
                'cancellation_fee_percentage' => 20,
                'cancellation_policy_description' => 'Original 20% policy snapshot.',
            ])->assertSessionHas('success');

            $reservation = $this->createConfirmedReservationWithDownPayment(500.00, 15);

            $this->actingAs($this->admin)->post(route('admin.reservations.cancel', $reservation->id), [
                'cancellation_initiated_by' => 'customer',
                'cancellation_reason' => 'Customer cancelled',
            ])->assertSessionHas('success');

            // 2. Admin changes policy to 5%, then disables it entirely
            $this->actingAs($this->admin)->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '1',
                'cancellation_fee_percentage' => 5,
                'cancellation_policy_description' => 'New 5% policy.',
            ])->assertSessionHas('success');

            $this->actingAs($this->admin)->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '0',
                'cancellation_fee_percentage' => 50,
                'cancellation_policy_description' => 'Disabled policy.',
            ])->assertSessionHas('success');

            // 3. Past cancelled reservation MUST remain unchanged
            $reservation->refresh();
            $this->assertEquals(20.00, (float) $reservation->applied_fee_percentage);
            $this->assertEquals(100.00, (float) $reservation->cancellation_fee_amount);
            $this->assertEquals(400.00, (float) $reservation->refund_amount);
            $this->assertEquals(Reservation::REFUND_PENDING, $reservation->refund_status);
            $this->assertEquals('Original 20% policy snapshot.', $reservation->applied_policy_description);

            // Show page renders pending refund handover form
            $this->actingAs($this->admin)
                ->get(route('admin.reservations.show', $reservation->id))
                ->assertStatus(200)
                ->assertSee('Cancellation &amp; Refund Audit', false)
                ->assertSee('Refund: Pending')
                ->assertSee('Confirm Refund Handover');

            // 4. Admin explicitly confirms physical/digital refund handover
            $this->actingAs($this->admin)->post(route('admin.reservations.confirmRefund', $reservation->id), [
                'refund_method' => 'GCash',
                'refund_reference' => 'GCASH-REF-99281',
            ])->assertSessionHas('success');

            $reservation->refresh();
            $this->assertEquals(Reservation::REFUND_COMPLETED, $reservation->refund_status);
            $this->assertNotNull($reservation->refund_confirmed_at);
            $this->assertEquals($this->admin->id, $reservation->refund_confirmed_by);
            $this->assertEquals('GCash', $reservation->refund_method);
            $this->assertEquals('GCASH-REF-99281', $reservation->refund_reference);

            // Cash ledger now contains the exact -₱400.00 refund outflow entry
            $refundLedger = CashLedgerEntry::where('entry_type', 'RESERVATION_REFUND')
                ->where('reservation_id', $reservation->id)
                ->first();
            $this->assertNotNull($refundLedger);
            $this->assertEquals(-400.00, (float) $refundLedger->amount_remitted);
            $this->assertEquals('GCash', $refundLedger->payment_method);

            // Audit log captured refund confirmation
            $this->assertDatabaseHas('audit_logs', [
                'action' => 'RESERVATION_REFUND_CONFIRMED',
                'user_id' => $this->admin->id,
            ]);

            // Duplicate confirmation attempt is rejected without writing duplicate ledger entry
            $this->actingAs($this->admin)->post(route('admin.reservations.confirmRefund', $reservation->id), [
                'refund_method' => 'Cash',
            ])->assertSessionHas('error');

            $this->assertEquals(1, CashLedgerEntry::where('entry_type', 'RESERVATION_REFUND')
                ->where('reservation_id', $reservation->id)
                ->count());

            // Rider Remittance page still renders cleanly with reservation ledger entries present
            $this->actingAs($this->admin)
                ->get(route('admin.remittances.index'))
                ->assertStatus(200)
                ->assertSee($reservation->reservation_code)
                ->assertSee('Reservation Refund');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_cancelling_via_edit_form_also_applies_backend_policy_and_snapshot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 10:00:00', 'Asia/Manila'));

        try {
            $this->actingAs($this->admin)->put(route('admin.settings.updateReservationPolicy'), [
                'cancellation_fee_enabled' => '1',
                'cancellation_fee_percentage' => 20,
                'cancellation_policy_description' => 'Standard 20% fee.',
            ])->assertSessionHas('success');

            $reservation = $this->createConfirmedReservationWithDownPayment(500.00, 15);

            $this->actingAs($this->admin)->put(route('admin.reservations.update', $reservation->id), [
                'customer_name' => $reservation->customer_name,
                'contact_number' => $reservation->contact_number,
                'product_id' => $reservation->product_id,
                'size' => $reservation->size,
                'quantity' => $reservation->quantity,
                'unit_price' => 50.00,
                'reservation_date' => '2026-11-21',
                'pickup_date' => '2026-11-28',
                'down_payment' => 500.00,
                'payment_status' => 'Partially Paid',
                'status' => 'Cancelled',
                'cancellation_initiated_by' => 'business',
                'cancellation_reason' => 'Store unable to fulfill batch',
            ])->assertRedirect(route('admin.reservations.show', $reservation->id));

            $reservation->refresh();
            $this->freshMilk->refresh();

            $this->assertEquals(Reservation::STATUS_CANCELLED, $reservation->status);
            $this->assertEquals(Reservation::CANCELLED_BY_BUSINESS, $reservation->cancellation_initiated_by);
            $this->assertEquals(0.00, (float) $reservation->applied_fee_percentage);
            $this->assertEquals(0.00, (float) $reservation->cancellation_fee_amount);
            $this->assertEquals(500.00, (float) $reservation->refund_amount);
            $this->assertEquals(Reservation::REFUND_PENDING, $reservation->refund_status);
            $this->assertEquals(0, $this->freshMilk->reserved_stock);
            $this->assertEquals(100, $this->freshMilk->available_stock);
        } finally {
            Carbon::setTestNow();
        }
    }
}
