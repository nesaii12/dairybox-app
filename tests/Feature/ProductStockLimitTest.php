<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ForecastService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductStockLimitTest extends TestCase
{
    protected User $admin;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/0001_01_01_000001_create_cache_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_06_12_000001_create_categories_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_06_12_000002_create_products_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_06_12_000003_create_inventories_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_06_12_000011_create_notifications_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_06_12_000013_create_audit_logs_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_24_000001_add_manufacturing_date_to_products_and_inventories_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_24_000003_add_link_to_notifications_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_24_000004_add_flavor_to_products_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2026_11_21_000001_add_reserved_stock_to_products_and_create_reservations_table.php']);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->category = Category::create(['category_name' => 'Milk']);
    }

    public function test_creating_product_rejects_initial_stock_above_100(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'product_name' => 'Fresh Carabao Milk',
            'category_id' => $this->category->id,
            'milk_types' => ['Carabao'],
            'sizes' => ['Medium'],
            'price' => 50.00,
            'initial_stock' => 101,
            'manufacturing_date' => now()->toDateString(),
            'expiration_date' => now()->addDays(14)->toDateString(),
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors([
            'initial_stock' => 'Initial stock cannot exceed 100 units per product to avoid spoilage.',
        ]);

        $this->assertDatabaseMissing('products', ['product_name' => 'Fresh Carabao Milk']);
    }

    public function test_creating_product_allows_initial_stock_up_to_100(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'product_name' => 'Fresh Carabao Milk',
            'category_id' => $this->category->id,
            'milk_types' => ['Carabao'],
            'sizes' => ['Medium'],
            'price' => 50.00,
            'initial_stock' => 100,
            'manufacturing_date' => now()->toDateString(),
            'expiration_date' => now()->addDays(14)->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'product_name' => 'Fresh Carabao Milk',
            'stock' => 100,
        ]);
    }

    public function test_inventory_stock_in_blocks_exceeding_100_and_allows_up_to_100(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'product_name' => 'Pasteurized Choco Milk',
            'milk_type' => 'Carabao',
            'size' => 'Medium',
            'price' => 50.00,
            'stock' => 0,
            'expiration_date' => now()->addDays(10)->toDateString(),
            'status' => 'active',
        ]);

        InventoryService::addStock($product->id, 80, now()->addDays(10)->toDateString(), 'Initial batch', now()->toDateString(), true);

        // Attempt to add 25 units (80 + 25 = 105 > 100) -> blocked
        $blocked = $this->actingAs($this->admin)->post(route('admin.inventory.adjust'), [
            'product_id' => $product->id,
            'adjustment_type' => 'in',
            'quantity' => 25,
            'manufacturing_date' => now()->toDateString(),
            'expiration_date' => now()->addDays(15)->toDateString(),
            'remarks' => 'Over-limit restock attempt',
        ]);

        $blocked->assertSessionHasErrors('quantity');
        $this->assertSame(80, $product->fresh()->stock);

        // Add exact remaining capacity 20 units (80 + 20 = 100) -> allowed
        $allowed = $this->actingAs($this->admin)->post(route('admin.inventory.adjust'), [
            'product_id' => $product->id,
            'adjustment_type' => 'in',
            'quantity' => 20,
            'manufacturing_date' => now()->toDateString(),
            'expiration_date' => now()->addDays(15)->toDateString(),
            'remarks' => 'Top-up to 100 cap',
        ]);

        $allowed->assertRedirect(route('admin.inventory.index'));
        $allowed->assertSessionHasNoErrors();
        $this->assertSame(100, $product->fresh()->stock);

        // Adding 1 more unit when already at 100 -> blocked
        $atCap = $this->actingAs($this->admin)->post(route('admin.inventory.adjust'), [
            'product_id' => $product->id,
            'adjustment_type' => 'in',
            'quantity' => 1,
            'manufacturing_date' => now()->toDateString(),
            'expiration_date' => now()->addDays(15)->toDateString(),
            'remarks' => 'Beyond limit attempt',
        ]);

        $atCap->assertSessionHasErrors('quantity');
        $this->assertSame(100, $product->fresh()->stock);
    }

    public function test_stock_out_still_works_when_product_is_over_100_units(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'product_name' => 'Manggo Graham Bar',
            'milk_type' => 'Carabao',
            'size' => 'Medium',
            'price' => 35.00,
            'stock' => 0,
            'expiration_date' => now()->addDays(20)->toDateString(),
            'status' => 'active',
        ]);

        // Simulate legacy stock above 100 (without stock-limit enforcement)
        InventoryService::addStock($product->id, 150, now()->addDays(20)->toDateString(), 'Legacy stock');

        // Stock Out of 50 units should succeed to bring product down to 100
        $res = $this->actingAs($this->admin)->post(route('admin.inventory.adjust'), [
            'product_id' => $product->id,
            'adjustment_type' => 'out',
            'quantity' => 50,
            'remarks' => 'Reduce excess stock',
        ]);

        $res->assertRedirect(route('admin.inventory.index'));
        $res->assertSessionHasNoErrors();
        $this->assertSame(100, $product->fresh()->stock);
    }

    public function test_forecast_recommended_restock_respects_100_unit_spoilage_limit(): void
    {
        // High forecast (170 * 1.2 = 204), current stock 57 -> capped at 100 -> +43 units needed
        $this->assertSame(43, ForecastService::recommendedRestock(170.0, 57));

        // Current stock already >= 100 -> 0 units needed
        $this->assertSame(0, ForecastService::recommendedRestock(200.0, 100));
        $this->assertSame(0, ForecastService::recommendedRestock(200.0, 306));

        // Forecast + 20% under 100 (60 * 1.2 = 72), stock 3 -> +69 units needed
        $this->assertSame(69, ForecastService::recommendedRestock(60.0, 3));
    }
}
