<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Deduct stock using FIFO logic.
     *
     * Iterates over inventory batches from oldest to newest,
     * reducing remaining_stock until the requested quantity is fulfilled.
     *
     * @throws \Exception If insufficient stock across all batches.
     */
    public static function deductStock(int $productId, int $quantity, string $remarks = 'Order Deduction'): void
    {
        DB::transaction(function () use ($productId, $quantity, $remarks) {
            $batches = Inventory::where('product_id', $productId)
                ->where('remaining_stock', '>', 0)
                ->orderBy('created_at', 'asc') // Oldest batches first (FIFO)
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                if ($batch->remaining_stock >= $remaining) {
                    $batch->remaining_stock -= $remaining;
                    $batch->stock_out += $remaining;
                    $batch->save();
                    $remaining = 0;
                } else {
                    $remaining -= $batch->remaining_stock;
                    $batch->stock_out += $batch->remaining_stock;
                    $batch->remaining_stock = 0;
                    $batch->save();
                }
            }

            if ($remaining > 0) {
                throw new \Exception("Insufficient stock for product ID {$productId} under FIFO resolution.");
            }

            // Sync overall product stock
            $product = Product::findOrFail($productId);
            $product->stock = max(0, $product->stock - $quantity);

            // Update nearest expiration date from remaining batches
            $nearestBatch = Inventory::where('product_id', $productId)
                ->where('remaining_stock', '>', 0)
                ->whereNotNull('expiration_date')
                ->orderBy('expiration_date', 'asc')
                ->first();
            $product->expiration_date = $nearestBatch?->expiration_date;

            $product->save();
        });
    }

    /**
     * Add stock as a new FIFO batch.
     */
    public static function addStock(int $productId, int $quantity, ?string $expirationDate = null, string $remarks = 'Stock In'): void
    {
        DB::transaction(function () use ($productId, $quantity, $expirationDate, $remarks) {
            Inventory::create([
                'product_id' => $productId,
                'stock_in' => $quantity,
                'stock_out' => 0,
                'remaining_stock' => $quantity,
                'expiration_date' => $expirationDate,
                'remarks' => $remarks,
            ]);

            $product = Product::findOrFail($productId);
            $product->stock += $quantity;

            if ($expirationDate) {
                // Keep expiration_date pointing to the nearest expiring batch
                $nearestBatch = Inventory::where('product_id', $productId)
                    ->where('remaining_stock', '>', 0)
                    ->whereNotNull('expiration_date')
                    ->orderBy('expiration_date', 'asc')
                    ->first();
                $product->expiration_date = $nearestBatch ? $nearestBatch->expiration_date : $expirationDate;
            }

            $product->save();
        });
    }
}
