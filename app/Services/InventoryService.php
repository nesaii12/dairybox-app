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
            $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

            if ($product->stock < $quantity) {
                throw new \Exception("Insufficient stock for product '{$product->product_name}'. Available: {$product->stock}, Requested: {$quantity}");
            }

            $batches = Inventory::where('product_id', $productId)
                ->where('remaining_stock', '>', 0)
                ->orderBy('created_at', 'asc') // Oldest batches first (FIFO)
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                $deductedFromBatch = 0;
                if ($batch->remaining_stock >= $remaining) {
                    $deductedFromBatch = $remaining;
                    $batch->remaining_stock -= $remaining;
                    $batch->stock_out += $remaining;
                    $batch->save();
                    $remaining = 0;
                } else {
                    $deductedFromBatch = $batch->remaining_stock;
                    $remaining -= $batch->remaining_stock;
                    $batch->stock_out += $batch->remaining_stock;
                    $batch->remaining_stock = 0;
                    $batch->save();
                }

                // Record explicit stock-out transaction log for this batch consumption
                if ($deductedFromBatch > 0) {
                    Inventory::create([
                        'product_id' => $productId,
                        'stock_in' => 0,
                        'stock_out' => $deductedFromBatch,
                        'remaining_stock' => 0,
                        'manufacturing_date' => $batch->manufacturing_date,
                        'expiration_date' => $batch->expiration_date,
                        'remarks' => $remarks,
                    ]);
                }
            }

            if ($remaining > 0) {
                // If existing batches didn't fully cover $remaining even though $product->stock >= $quantity
                Inventory::create([
                    'product_id' => $productId,
                    'stock_in' => 0,
                    'stock_out' => $remaining,
                    'remaining_stock' => 0,
                    'manufacturing_date' => $product->manufacturing_date,
                    'expiration_date' => $product->expiration_date,
                    'remarks' => $remarks,
                ]);
                $remaining = 0;
            }

            // Sync overall product stock
            $product->stock = max(0, $product->stock - $quantity);

            // Update nearest expiration date and manufacturing date from remaining batches
            $nearestBatch = Inventory::where('product_id', $productId)
                ->where('remaining_stock', '>', 0)
                ->whereNotNull('expiration_date')
                ->orderBy('expiration_date', 'asc')
                ->first();
            $product->expiration_date = $nearestBatch?->expiration_date;
            if ($nearestBatch?->manufacturing_date) {
                $product->manufacturing_date = $nearestBatch->manufacturing_date;
            }

            $product->save();
        });
    }

    /**
     * Add stock as a new FIFO batch.
     */
    public static function addStock(int $productId, int $quantity, ?string $expirationDate = null, string $remarks = 'Stock In', ?string $manufacturingDate = null): void
    {
        DB::transaction(function () use ($productId, $quantity, $expirationDate, $remarks, $manufacturingDate) {
            $product = Product::findOrFail($productId);
            $resolvedMfgDate = $manufacturingDate ?? $product->manufacturing_date?->toDateString();

            Inventory::create([
                'product_id' => $productId,
                'stock_in' => $quantity,
                'stock_out' => 0,
                'remaining_stock' => $quantity,
                'manufacturing_date' => $resolvedMfgDate,
                'expiration_date' => $expirationDate ?? $product->expiration_date?->toDateString(),
                'remarks' => $remarks,
            ]);

            $product->stock += $quantity;

            if ($manufacturingDate) {
                $product->manufacturing_date = $manufacturingDate;
            }

            if ($expirationDate) {
                // Keep expiration_date pointing to the nearest expiring batch
                $nearestBatch = Inventory::where('product_id', $productId)
                    ->where('remaining_stock', '>', 0)
                    ->whereNotNull('expiration_date')
                    ->orderBy('expiration_date', 'asc')
                    ->first();
                $product->expiration_date = $nearestBatch ? $nearestBatch->expiration_date : $expirationDate;
                if ($nearestBatch?->manufacturing_date) {
                    $product->manufacturing_date = $nearestBatch->manufacturing_date;
                }
            }

            $product->save();
        });
    }

    /**
     * Reconcile product stock and expiration dates directly from active FIFO inventory batches.
     */
    public static function reconcileStock(?int $productId = null): void
    {
        $products = $productId ? Product::where('id', $productId)->get() : Product::all();

        foreach ($products as $product) {
            $batchesRemaining = Inventory::where('product_id', $product->id)->sum('remaining_stock');
            $product->stock = (int) $batchesRemaining;

            $nearestBatch = Inventory::where('product_id', $product->id)
                ->where('remaining_stock', '>', 0)
                ->whereNotNull('expiration_date')
                ->orderBy('expiration_date', 'asc')
                ->first();

            $product->expiration_date = $nearestBatch?->expiration_date;
            if ($nearestBatch?->manufacturing_date) {
                $product->manufacturing_date = $nearestBatch->manufacturing_date;
            }

            $product->save();
        }
    }
}
