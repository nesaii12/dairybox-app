<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckoutCodPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

            Artisan::call('migrate', ['--path' => 'database/migrations/' . $basename, '--force' => true]);
        }
    }

    private function setupCustomerWithCart(): array
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
            'phone' => '09123456789',
        ]);

        $category = Category::create([
            'category_name' => 'Dairy Milk',
            'status' => 'active',
        ]);

        $product = Product::create([
            'product_name' => 'Fresh Carabao Milk',
            'category_id' => $category->id,
            'price' => 120.00,
            'milk_type' => 'Carabao',
            'size' => 'Medium',
            'available_sizes' => ['Small', 'Medium', 'Large'],
            'available_milk_types' => ['Cow', 'Carabao'],
            'shelf_life_days' => 14,
            'expiration_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ]);

        InventoryService::addStock($product->id, 50, now()->addDays(10)->toDateString(), 'Initial Test Stock', now()->subDay()->toDateString());

        $cart = Cart::create(['customer_id' => $customer->id]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 240.00,
        ]);

        return compact('customer', 'product', 'cart', 'cartItem');
    }

    public function test_checkout_page_renders_cod_and_gcash_qr_live_chat_and_no_xendit(): void
    {
        $data = $this->setupCustomerWithCart();

        $response = $this->actingAs($data['customer'])
            ->get(route('customer.checkout.index'));

        $response->assertStatus(200);

        // Assert Cash on Delivery is present and default selected
        $response->assertSee('Cash on Delivery');
        $response->assertSee('Pay upon Delivery');
        $response->assertSee('Prepare exact cash payment upon receiving your order from the dispatch rider.');
        $response->assertSee('value="cod"', false);

        // Assert GCash QR Live Chat info card is present
        $response->assertSee('Online Payment via GCash QR');
        $response->assertSee('Want to pay via GCash QR? Place your order as Cash on Delivery first, or contact our Admin in Live Chat to request the GCash QR code and verify your payment.');
        $response->assertSee('Contact Admin via Live Chat');

        // Assert Right-Hand Order Summary Note & Action Button
        $response->assertSee('Payment Method: Cash on Delivery — Pay upon delivery or coordinate GCash QR with Admin via Live Chat.');
        $response->assertSee('Place Order (COD) — ₱');

        // Assert Xendit card and branding are removed
        $response->assertDontSee('Powered by Xendit');
        $response->assertDontSee('Xendit Secure');
        $response->assertDontSee('Pay instantly using GCash, Maya, Debit/Credit Card, QR Ph, or GrabPay.');
    }

    public function test_checkout_submission_enforces_cod_and_creates_order_without_xendit(): void
    {
        $data = $this->setupCustomerWithCart();

        $nowManila = Carbon::now('Asia/Manila');
        $scheduleDate = $nowManila->copy()->addDay()->format('Y-m-d');

        $response = $this->actingAs($data['customer'])
            ->post(route('customer.checkout.placeOrder'), [
                'order_type' => 'Delivery',
                'delivery_address' => '123 Main St, Barangay Centro, City',
                'schedule_date' => $scheduleDate,
                'schedule_time' => '08:00:00',
                'payment_method' => 'cod',
                'item_ids' => [$data['cartItem']->id],
            ]);

        $response->assertRedirect(route('customer.orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $data['customer']->id,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_type' => 'Delivery',
        ]);
    }

    public function test_checkout_submission_rejects_xendit_payment_method(): void
    {
        $data = $this->setupCustomerWithCart();

        $nowManila = Carbon::now('Asia/Manila');
        $scheduleDate = $nowManila->copy()->addDay()->format('Y-m-d');

        $response = $this->actingAs($data['customer'])
            ->post(route('customer.checkout.placeOrder'), [
                'order_type' => 'Delivery',
                'delivery_address' => '123 Main St, Barangay Centro, City',
                'schedule_date' => $scheduleDate,
                'schedule_time' => '08:00:00',
                'payment_method' => 'xendit',
                'item_ids' => [$data['cartItem']->id],
            ]);

        $response->assertSessionHasErrors(['payment_method']);
        $this->assertDatabaseMissing('orders', [
            'customer_id' => $data['customer']->id,
            'payment_method' => 'xendit',
        ]);
    }
}
