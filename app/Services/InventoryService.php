<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Calculate sellable available stock (Physical Stock - Reserved Stock).
     */
    public static function getAvailableStock(Product $product): int
    {
        return max(0, (int) $product->stock - (int) ($product->reserved_stock ?? 0));
    }

    /**
     * Standardized anti-overselling error message.
     */
    public static function insufficientStockMessage(Product $product, ?int $available = null): string
    {
        $avail = $available ?? self::getAvailableStock($product);
        return "Sorry! Only {$avail} pieces of {$product->product_name} are currently available.";
    }

    /**
     * Compute total physical stock in active batches whose expiration date is on or after $pickupDate.
     */
    public static function getEligibleBatchStockForDate(int $productId, string $pickupDate): int
    {
        $hasActiveBatches = Inventory::where('product_id', $productId)
            ->where('remaining_stock', '>', 0)
            ->exists();

        if ($hasActiveBatches) {
            return (int) Inventory::where('product_id', $productId)
                ->where('remaining_stock', '>', 0)
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '>=', $pickupDate)
                ->sum('remaining_stock');
        }

        $product = Product::find($productId);
        if (!$product || !$product->expiration_date) {
            return 0;
        }

        return $product->expiration_date->toDateString() >= $pickupDate ? (int) $product->stock : 0;
    }

    /**
     * Deduct stock using FIFO logic with row-level locking and anti-overselling checks.
     *
     * Iterates over inventory batches from oldest to newest,
     * reducing remaining_stock until the requested quantity is fulfilled.
     *
     * @throws \Exception If insufficient available stock.
     */
    public static function deductStock(
        int $productId,
        int $quantity,
        string $remarks = 'Order Deduction',
        bool $enforceAvailableStock = true,
        ?string $minExpiryDate = null
    ): void {
        DB::transaction(function () use ($productId, $quantity, $remarks, $enforceAvailableStock, $minExpiryDate) {
            $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

            $availableStock = $enforceAvailableStock
                ? self::getAvailableStock($product)
                : (int) $product->stock;

            if ($availableStock < $quantity) {
                throw new \Exception(self::insufficientStockMessage($product, $availableStock));
            }

            if ((int) $product->stock < $quantity) {
                throw new \Exception(self::insufficientStockMessage($product, (int) $product->stock));
            }

            $batchQuery = Inventory::where('product_id', $productId)
                ->where('remaining_stock', '>', 0);

            if ($minExpiryDate) {
                // Prioritize non-expired batches valid through the pickup date in FIFO order
                $batchQuery->orderByRaw(
                    'CASE WHEN expiration_date IS NOT NULL AND DATE(expiration_date) >= ? THEN 0 ELSE 1 END ASC',
                    [$minExpiryDate]
                );
            }

            $batches = $batchQuery
                ->orderBy('created_at', 'asc') // Oldest batches first (FIFO)
                ->lockForUpdate()
                ->get();

            $remaining = $quantity;
            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

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

            // Sync overall physical product stock
            $product->stock = max(0, (int) $product->stock - $quantity);

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
            $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();
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
     * Reconcile product physical stock, reserved stock, and expiration dates directly from active records.
     */
    public static function reconcileStock(?int $productId = null): void
    {
        $products = $productId ? Product::where('id', $productId)->get() : Product::all();

        foreach ($products as $product) {
            $batchesRemaining = Inventory::where('product_id', $product->id)->sum('remaining_stock');
            $product->stock = (int) $batchesRemaining;

            $reservedTotal = (int) Reservation::where('product_id', $product->id)
                ->whereIn('status', Reservation::ALLOCATED_STATUSES)
                ->sum('allocated_quantity');
            $product->reserved_stock = max(0, $reservedTotal);

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
