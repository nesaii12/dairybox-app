<?php

namespace Tests\Feature;

use App\Events\ChatMessageSent;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionChatTest extends TestCase
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

    private function createOrderWithParticipants(): array
    {
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $otherCustomer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $rider = User::factory()->create(['role' => 'rider', 'status' => 'active']);
        $otherRider = User::factory()->create(['role' => 'rider', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $order = Order::create([
            'order_number' => 'DB-20261123-0001',
            'customer_id' => $customer->id,
            'rider_id' => $rider->id,
            'order_type' => 'Delivery',
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
            'delivery_address' => 'Brgy. San Jose, Purok 2',
            'schedule_date' => now()->toDateString(),
            'subtotal' => 250.00,
            'delivery_fee' => 25.00,
            'total_amount' => 275.00,
            'order_status' => 'Out for Delivery',
        ]);

        return compact('customer', 'otherCustomer', 'rider', 'otherRider', 'admin', 'order');
    }

    public function test_authorized_parties_can_initialize_and_post_in_order_chat(): void
    {
        Event::fake([ChatMessageSent::class]);

        extract($this->createOrderWithParticipants());

        // Customer auto-initializes thread
        $res = $this->actingAs($customer)->getJson("/chat/thread/order/{$order->id}");
        $res->assertOk()
            ->assertJsonPath('thread.reference', 'Order #DB-20261123-0001')
            ->assertJsonPath('thread.has_rider', true);

        $threadId = $res->json('thread.id');

        // Customer sends message
        $postCustomer = $this->actingAs($customer)->postJson("/chat/thread/{$threadId}/messages", [
            'body' => 'Green gate beside the sari-sari store.',
        ]);
        $postCustomer->assertCreated()
            ->assertJsonPath('message.sender_role', 'customer')
            ->assertJsonPath('message.body', 'Green gate beside the sari-sari store.');

        Event::assertDispatched(ChatMessageSent::class);

        // Rider opens thread -> customer's message is marked read
        $riderRes = $this->actingAs($rider)->getJson("/chat/thread/order/{$order->id}");
        $riderRes->assertOk()->assertJsonCount(1, 'messages');
        $this->assertNotNull(ChatMessage::first()->read_at);

        // Rider replies
        $this->actingAs($rider)->postJson("/chat/thread/{$threadId}/messages", [
            'body' => 'I am outside your delivery address',
        ])->assertCreated()->assertJsonPath('message.sender_role', 'rider');

        // Admin replies
        $this->actingAs($admin)->postJson("/chat/thread/{$threadId}/messages", [
            'body' => 'Please prepare exact COD balance',
        ])->assertCreated()->assertJsonPath('message.sender_role', 'admin');

        // Threads list endpoint returns the thread with unread count for customer
        $this->actingAs($customer)->getJson('/chat/threads')
            ->assertOk()
            ->assertJsonPath('total_unread', 2);

        $this->assertDatabaseCount('chat_messages', 3);
    }

    public function test_unauthorized_customer_and_unassigned_rider_are_forbidden(): void
    {
        extract($this->createOrderWithParticipants());

        $thread = ChatThread::create([
            'chatable_type' => Order::class,
            'chatable_id' => $order->id,
            'status' => 'open',
        ]);

        $this->actingAs($otherCustomer)->getJson("/chat/thread/order/{$order->id}")->assertForbidden();
        $this->actingAs($otherCustomer)->postJson("/chat/thread/{$thread->id}/messages", ['body' => 'Hi'])->assertForbidden();

        $this->actingAs($otherRider)->getJson("/chat/thread/order/{$order->id}")->assertForbidden();
        $this->actingAs($otherRider)->postJson("/chat/thread/{$thread->id}/messages", ['body' => 'Hi'])->assertForbidden();
    }

    public function test_reservation_chat_supports_attachments_and_validation(): void
    {
        Storage::fake('public');
        Event::fake([ChatMessageSent::class]);

        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $category = Category::create(['category_name' => 'Milk']);
        $product = Product::create([
            'category_id' => $category->id,
            'product_name' => 'Fresh Carabao Milk',
            'milk_type' => 'Carabao',
            'size' => 'Large',
            'price' => 150.00,
            'stock' => 50,
            'reserved_stock' => 15,
            'status' => 'active',
        ]);

        $reservation = Reservation::create([
            'reservation_code' => 'RES-20261123-0001',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'contact_number' => '09171234567',
            'product_id' => $product->id,
            'size' => 'Large',
            'quantity' => 15,
            'allocated_quantity' => 15,
            'unit_price' => 150.00,
            'total_amount' => 2250.00,
            'delivery_fee' => 0,
            'down_payment' => 500.00,
            'remaining_balance' => 1750.00,
            'payment_status' => Reservation::PAYMENT_PARTIAL,
            'status' => Reservation::STATUS_CONFIRMED,
            'fulfillment_type' => Reservation::FULFILLMENT_PICKUP,
            'reservation_date' => now()->toDateString(),
            'pickup_date' => now()->addDay()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $res = $this->actingAs($admin)->getJson("/chat/thread/reservation/{$reservation->id}");
        $res->assertOk()->assertJsonPath('thread.reference', 'RES-20261123-0001');
        $threadId = $res->json('thread.id');

        // Empty payload rejected
        $this->actingAs($admin)->postJson("/chat/thread/{$threadId}/messages", [])->assertStatus(422);

        // Attachment upload with text accepted
        $file = UploadedFile::fake()->image('landmark.jpg');
        $uploadRes = $this->actingAs($customer)->postJson("/chat/thread/{$threadId}/messages", [
            'body' => 'Gate photo attached',
            'attachment' => $file,
        ]);
        $uploadRes->assertCreated();
        $this->assertNotNull($uploadRes->json('message.attachment_url'));

        // Picture-only message (no text body) accepted
        $photoOnly = UploadedFile::fake()->image('delivery-proof.png');
        $photoOnlyRes = $this->actingAs($admin)->postJson("/chat/thread/{$threadId}/messages", [
            'attachment' => $photoOnly,
        ]);
        $photoOnlyRes->assertCreated()
            ->assertJsonPath('message.body', '');
        $this->assertNotNull($photoOnlyRes->json('message.attachment_url'));
    }
}
