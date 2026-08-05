<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->otherCustomer = User::create([
            'name' => 'Juan Luna',
            'email' => 'juan@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'status' => 'active',
        ]);
    }

    public function test_old_notifications_index_route_is_disabled(): void
    {
        $response = $this->actingAs($this->customer)->get('/customer/notifications');
        
        $response->assertStatus(404);
    }

    public function test_customer_can_mark_notification_as_read(): void
    {
        $notification = AppNotification::create([
            'user_id' => $this->customer->id,
            'title' => 'Test Notification',
            'message' => 'This is a test notification.',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)
            ->from('/customer/products')
            ->post(route('customer.notifications.read', $notification));

        $response->assertRedirect('/customer/products');
        $response->assertSessionHas('success', 'Notification marked as read.');
        
        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_customer_cannot_mark_other_users_notification_as_read(): void
    {
        $notification = AppNotification::create([
            'user_id' => $this->otherCustomer->id,
            'title' => 'Other Notification',
            'message' => 'Should not be readable by Maria.',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)
            ->post(route('customer.notifications.read', $notification));

        $response->assertStatus(403);
        $this->assertFalse($notification->fresh()->is_read);
    }

    public function test_customer_can_mark_all_notifications_as_read(): void
    {
        AppNotification::create([
            'user_id' => $this->customer->id,
            'title' => 'Notif 1',
            'message' => 'Message 1',
            'is_read' => false,
        ]);

        AppNotification::create([
            'user_id' => $this->customer->id,
            'title' => 'Notif 2',
            'message' => 'Message 2',
            'is_read' => false,
        ]);

        // This belongs to someone else and should NOT be changed
        $otherNotif = AppNotification::create([
            'user_id' => $this->otherCustomer->id,
            'title' => 'Notif 3',
            'message' => 'Message 3',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)
            ->from('/customer/products')
            ->post(route('customer.notifications.readAll'));

        $response->assertRedirect('/customer/products');
        $response->assertSessionHas('success', 'All notifications marked as read.');

        $this->assertEquals(0, AppNotification::where('user_id', $this->customer->id)->where('is_read', false)->count());
        $this->assertFalse($otherNotif->fresh()->is_read);
    }
}
