<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_fifo_stock_deduction_across_multiple_batches(): void
    {
        // 1. Setup category and product
        $category = Category::create(['category_name' => 'Test Category']);
        
        $product = Product::create([
            'category_id' => $category->id,
            'product_name' => 'Test Milk',
            'description' => 'Test Description',
            'milk_type' => 'Cow',
            'size' => 'Medium',
            'price' => 50.00,
            'stock' => 0,
            'expiration_date' => null,
            'status' => 'active',
        ]);

        // 2. Add first batch (FIFO: oldest intake)
        InventoryService::addStock($product->id, 10, '2026-07-01', 'First batch intake');
        
        // 3. Add second batch (FIFO: newer intake)
        InventoryService::addStock($product->id, 20, '2026-07-15', 'Second batch intake');

        $product->refresh();
        $this->assertEquals(30, $product->stock);
        $this->assertEquals('2026-07-01', $product->expiration_date->toDateString());

        // 4. Deduct 15 units (should exhaust batch 1 [10 units] and deduct 5 from batch 2)
        InventoryService::deductStock($product->id, 15, 'Customer checkout order');

        // 5. Refresh models and assert remaining batch levels
        $product->refresh();
        $this->assertEquals(15, $product->stock);
        
        // Nearest active expiration date should now point to batch 2
        $this->assertEquals('2026-07-15', $product->expiration_date->toDateString());

        $batch1 = Inventory::where('product_id', $product->id)
            ->where('remarks', 'First batch intake')
            ->first();
        $batch2 = Inventory::where('product_id', $product->id)
            ->where('remarks', 'Second batch intake')
            ->first();

        // Batch 1 exhausted
        $this->assertEquals(0, $batch1->remaining_stock);
        $this->assertEquals(10, $batch1->stock_out);

        // Batch 2 partially deducted
        $this->assertEquals(15, $batch2->remaining_stock);
        $this->assertEquals(5, $batch2->stock_out);
    }

    public function test_insufficient_stock_throws_exception(): void
    {
        $category = Category::create(['category_name' => 'Test Category']);
        
        $product = Product::create([
            'category_id' => $category->id,
            'product_name' => 'Test Milk',
            'description' => 'Test Description',
            'milk_type' => 'Cow',
            'size' => 'Medium',
            'price' => 50.00,
            'stock' => 0,
            'expiration_date' => null,
            'status' => 'active',
        ]);

        InventoryService::addStock($product->id, 5, '2026-07-01', 'First batch intake');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient stock for product ID {$product->id} under FIFO resolution.");

        InventoryService::deductStock($product->id, 10, 'checkout');
    }

    public function test_stock_in_rejects_expiration_date_before_manufacturing_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $category = Category::create(['category_name' => 'Dairy']);
        $product = Product::create([
            'category_id' => $category->id,
            'product_name' => 'Mango Graham Bar',
            'description' => 'Fresh batch',
            'milk_type' => 'Cow',
            'size' => 'Medium',
            'price' => 50.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.inventory.adjust'), [
            'product_id' => $product->id,
            'adjustment_type' => 'in',
            'quantity' => 25,
            'manufacturing_date' => now()->toDateString(),
            'expiration_date' => now()->subDays(20)->toDateString(),
            'remarks' => 'Invalid batch dates',
        ]);

        $response->assertSessionHasErrors([
            'expiration_date' => 'Expiration date must be on or after the manufacturing date.',
        ]);

        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_stock_in_accepts_expiration_date_on_or_after_manufacturing_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $category = Category::create(['category_name' => 'Dairy']);
        $product = Product::create([
            'category_id' => $category->id,
            'product_name' => 'Mango Graham Bar',
            'description' => 'Fresh batch',
            'milk_type' => 'Cow',
            'size' => 'Medium',
            'price' => 50.00,
            'stock' => 10,
            'status' => 'active',
        ]);

        $today = now()->toDateString();

        $response = $this->actingAs($admin)->post(route('admin.inventory.adjust'), [
            'product_id' => $product->id,
            'adjustment_type' => 'in',
            'quantity' => 15,
            'manufacturing_date' => $today,
            'expiration_date' => $today,
            'remarks' => 'Same-day expiry batch',
        ]);

        $response->assertRedirect(route('admin.inventory.index'));
        $response->assertSessionHasNoErrors();
        $this->assertEquals(25, $product->fresh()->stock);
    }
}
