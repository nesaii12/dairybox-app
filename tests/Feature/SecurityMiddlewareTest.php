<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class SecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect(route('login'));

        $response2 = $this->get('/customer/products');
        $response2->assertStatus(200);

        $responseCart = $this->get('/customer/cart');
        $responseCart->assertRedirect(route('login'));

        $response3 = $this->get('/rider/dashboard');
        $response3->assertRedirect(route('login'));
    }

    public function test_customer_role_cannot_access_admin_routes(): void
    {
        $customer = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($customer)->get('/admin/dashboard');
        
        $response->assertStatus(403);
    }

    public function test_rider_role_cannot_access_admin_routes(): void
    {
        $rider = User::create([
            'name' => 'Juan Rider',
            'email' => 'rider@example.com',
            'password' => bcrypt('password'),
            'role' => 'rider',
            'status' => 'active',
        ]);

        $response = $this->actingAs($rider)->get('/admin/dashboard');
        
        $response->assertStatus(403);
    }

    public function test_admin_role_can_access_admin_routes(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        
        $response->assertStatus(200);
    }

    public function test_restricted_customer_cannot_access_checkout(): void
    {
        $customer = User::create([
            'name' => 'Restricted Customer',
            'email' => 'restricted@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'status' => 'restricted',
            'restricted_until' => Carbon::now()->addDays(3),
        ]);

        // Hit checkout and assert redirection back with errors / session flash message
        $response = $this->actingAs($customer)
            ->from('/customer/cart')
            ->get('/customer/checkout');

        $response->assertRedirect('/customer/cart');
        $response->assertSessionHas('error');
    }
}
