<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected User $rider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->rider = User::create([
            'name' => 'Rider User',
            'email' => 'rider@example.com',
            'password' => bcrypt('password'),
            'role' => 'rider',
            'status' => 'active',
        ]);
    }

    public function test_guests_cannot_access_rider_management(): void
    {
        $this->get(route('admin.riders.index'))->assertRedirect(route('login'));
        $this->get(route('admin.riders.create'))->assertRedirect(route('login'));
    }

    public function test_non_admins_cannot_access_rider_management(): void
    {
        // Customer
        $this->actingAs($this->customer)->get(route('admin.riders.index'))->assertStatus(403);
        $this->actingAs($this->customer)->get(route('admin.riders.create'))->assertStatus(403);
        
        // Rider
        $this->actingAs($this->rider)->get(route('admin.riders.index'))->assertStatus(403);
    }

    public function test_admin_can_view_riders_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.riders.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Rider User');
    }

    public function test_admin_can_create_rider_account(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.riders.store'), [
            'name' => 'New Delivery Rider',
            'email' => 'newrider@dairybox.com',
            'phone' => '09991112222',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('admin.riders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'New Delivery Rider',
            'email' => 'newrider@dairybox.com',
            'phone' => '09991112222',
            'role' => 'rider',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_rider_account(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.riders.update', $this->rider->id), [
            'name' => 'Updated Rider Name',
            'email' => 'rider@example.com', // keep original email
            'phone' => '09123456789',
            'status' => 'restricted',
        ]);

        $response->assertRedirect(route('admin.riders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->rider->id,
            'name' => 'Updated Rider Name',
            'phone' => '09123456789',
            'status' => 'restricted',
        ]);
    }

    public function test_admin_can_delete_rider_account(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('admin.riders.destroy', $this->rider->id));

        $response->assertRedirect(route('admin.riders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', [
            'id' => $this->rider->id,
        ]);
    }
}
