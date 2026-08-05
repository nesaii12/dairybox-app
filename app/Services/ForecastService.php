<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ForecastService
{
    /**
     * Predict demand for a product in a target month using
     * linear regression with seasonal adjustment.
     */
    public static function predictProductDemand(int $productId, string $targetMonthYear): float
    {
        // Fetch monthly sales history for the product
        $salesHistory = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m') as month"),
                DB::raw('SUM(order_items.quantity) as total_qty')
            )
            ->where('order_items.product_id', $productId)
            ->where('orders.order_status', 'Delivered')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $n = $salesHistory->count();
        if ($n < 3) {
            // Not enough data — fall back to simple average
            return $salesHistory->avg('total_qty') ?? 0.0;
        }

        // Linear Regression: y = slope * x + intercept
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($salesHistory as $index => $history) {
            $x = $index + 1;
            $y = (float) $history->total_qty;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $denominator = $n * $sumX2 - $sumX * $sumX;
        $slope = $denominator != 0
            ? ($n * $sumXY - $sumX * $sumY) / $denominator
            : 0;
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Predict for next month index
        $predictedIndex = $n + 1;
        $trendForecast = $slope * $predictedIndex + $intercept;

        // Apply seasonal adjustment if we have data for the same calendar month
        $targetMonthNum = date('m', strtotime($targetMonthYear . '-01'));
        $seasonalData = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.order_status', 'Delivered')
            ->whereMonth('orders.created_at', $targetMonthNum)
            ->select(DB::raw('SUM(order_items.quantity) as qty'))
            ->groupBy(DB::raw("DATE_FORMAT(orders.created_at, '%Y')"))
            ->pluck('qty');

        if ($seasonalData->isNotEmpty()) {
            $seasonalAverage = $seasonalData->avg();
            $overallAverage = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $productId)
                ->where('orders.order_status', 'Delivered')
                ->sum('order_items.quantity') / max(1, $n);

            $seasonalIndex = $overallAverage > 0 ? ($seasonalAverage / $overallAverage) : 1.0;
            return max(0.0, $trendForecast * $seasonalIndex);
        }

        return max(0.0, $trendForecast);
    }
}
