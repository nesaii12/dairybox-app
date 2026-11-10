<?php

namespace App\Services;

use App\Models\Forecast;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ForecastService
{
    /**
     * Predict demand for a product in a target month using
     * linear regression with seasonal adjustment.
     */
    public static function predictProductDemand(int $productId, string $targetMonthYear): float
    {
        $dateExpr = "COALESCE(sales.sales_date, DATE(orders.created_at))";

        // Fetch monthly sales history for the product
        $salesHistory = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
            ->select(
                DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m') as month"),
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
        $targetMonthNum = (int) date('m', strtotime($targetMonthYear . '-01'));
        $seasonalData = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
            ->where('order_items.product_id', $productId)
            ->where('orders.order_status', 'Delivered')
            ->whereRaw("MONTH({$dateExpr}) = ?", [$targetMonthNum])
            ->select(DB::raw('SUM(order_items.quantity) as qty'))
            ->groupBy(DB::raw("YEAR({$dateExpr})"))
            ->pluck('qty');

        if ($seasonalData->isNotEmpty()) {
            $seasonalAverage = $seasonalData->avg();
            $overallAverage = $sumY / max(1, $n);

            $seasonalIndex = $overallAverage > 0 ? ($seasonalAverage / $overallAverage) : 1.0;
            return max(0.0, $trendForecast * $seasonalIndex);
        }

        return max(0.0, $trendForecast);
    }

    /**
     * Generate (or refresh) forecasts for all active products for the target month.
     * Returns the number of products updated.
     */
    public static function generateAllProducts(string $targetMonth): int
    {
        $products = Product::where('status', 'active')->get();
        $count = 0;

        foreach ($products as $product) {
            $predictedQty = self::predictProductDemand($product->id, $targetMonth);

            Forecast::updateOrCreate(
                ['product_id' => $product->id, 'forecast_month' => $targetMonth],
                ['predicted_sales' => $predictedQty]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Return the recommended restock quantity for a product given its forecast.
     * Safety buffer: forecast + 20% headroom, minus current stock. Min 0.
     */
    public static function recommendedRestock(float $predictedSales, int $currentStock): int
    {
        $buffer = (int) ceil($predictedSales * 1.20);
        return max(0, $buffer - $currentStock);
    }

    /**
     * Determine if a product is at risk of stocking out based on its forecast vs current stock.
     * Risk levels: 'critical' (stock < 50% of forecast), 'low' (stock < forecast), 'safe'.
     */
    public static function stockoutRisk(float $predictedSales, int $currentStock): string
    {
        if ($predictedSales <= 0) {
            return 'safe';
        }

        if ($currentStock < ($predictedSales * 0.5)) {
            return 'critical';
        }

        if ($currentStock < $predictedSales) {
            return 'low';
        }

        return 'safe';
    }

    /**
     * Analyze historical sales records to identify monthly/yearly sales trends,
     * seasonal demand patterns, and peak performance periods.
     * Supports filtering by a specific product ID or aggregating across all products.
     */
    public static function getHistoricalAnalytics(?int $productId = null): array
    {
        $baseQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
            ->where('orders.order_status', 'Delivered');

        if ($productId) {
            $baseQuery->where('order_items.product_id', $productId);
        }

        // Use sales.sales_date when present, falling back to orders.created_at
        $dateExpr = "COALESCE(sales.sales_date, DATE(orders.created_at))";

        // 1. Monthly Historical Trend (Chronological YYYY-MM)
        $monthlyRecords = (clone $baseQuery)
            ->select(
                DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m') as ym"),
                DB::raw("SUM(order_items.quantity) as total_qty"),
                DB::raw("SUM(order_items.subtotal) as total_revenue"),
                DB::raw("COUNT(DISTINCT orders.id) as total_orders")
            )
            ->groupBy('ym')
            ->orderBy('ym', 'asc')
            ->get();

        // 2. Yearly Breakdown for Year-over-Year Monthly Comparison (Jan..Dec per Year)
        $yearlyMonthlyRecords = (clone $baseQuery)
            ->select(
                DB::raw("YEAR({$dateExpr}) as yr"),
                DB::raw("MONTH({$dateExpr}) as mo"),
                DB::raw("SUM(order_items.quantity) as total_qty"),
                DB::raw("SUM(order_items.subtotal) as total_revenue")
            )
            ->groupBy('yr', 'mo')
            ->orderBy('yr', 'asc')
            ->orderBy('mo', 'asc')
            ->get();

        $years = $yearlyMonthlyRecords->pluck('yr')->unique()->sort()->values()->toArray();
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $fullMonthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $yoySeries = [];
        foreach ($years as $yr) {
            $monthlyQty = array_fill(0, 12, 0);
            $monthlyRev = array_fill(0, 12, 0.0);
            foreach ($yearlyMonthlyRecords->where('yr', $yr) as $row) {
                $idx = ((int) $row->mo) - 1;
                if ($idx >= 0 && $idx < 12) {
                    $monthlyQty[$idx] = (int) $row->total_qty;
                    $monthlyRev[$idx] = (float) $row->total_revenue;
                }
            }
            $yoySeries[$yr] = [
                'year' => (int) $yr,
                'quantities' => $monthlyQty,
                'revenues' => $monthlyRev,
                'total_qty' => array_sum($monthlyQty),
                'total_revenue' => array_sum($monthlyRev),
            ];
        }

        // 3. Seasonal Demand Index per Calendar Month (1..12)
        // Compares each calendar month's average demand across recorded years against overall monthly average
        $overallMonthlyAvgQty = $monthlyRecords->count() > 0
            ? (float) $monthlyRecords->avg('total_qty')
            : 0.0;

        $seasonalMonths = [];
        for ($m = 1; $m <= 12; $m++) {
            $rowsForMonth = $yearlyMonthlyRecords->where('mo', $m);
            $avgQty = $rowsForMonth->isNotEmpty() ? (float) $rowsForMonth->avg('total_qty') : 0.0;
            $avgRev = $rowsForMonth->isNotEmpty() ? (float) $rowsForMonth->avg('total_revenue') : 0.0;
            $totalQty = (int) $rowsForMonth->sum('total_qty');
            $yearsRecorded = $rowsForMonth->count();

            $seasonalIndex = ($overallMonthlyAvgQty > 0 && $avgQty > 0)
                ? round($avgQty / $overallMonthlyAvgQty, 2)
                : 0.0;

            $seasonalMonths[$m] = [
                'month_num' => $m,
                'short_name' => $monthNames[$m - 1],
                'full_name' => $fullMonthNames[$m],
                'avg_qty' => round($avgQty, 1),
                'avg_revenue' => round($avgRev, 2),
                'total_qty' => $totalQty,
                'years_recorded' => $yearsRecorded,
                'seasonal_index' => $seasonalIndex,
            ];
        }

        // Identify consistently high sales performance period (Peak Seasonal Month)
        $recordedSeasonal = collect($seasonalMonths)->where('total_qty', '>', 0);
        $peakSeasonalMonth = $recordedSeasonal->sortByDesc('avg_qty')->first();
        $lowSeasonalMonth = $recordedSeasonal->sortBy('avg_qty')->first();

        // Peak single historical month (YYYY-MM)
        $peakHistoricalRecord = $monthlyRecords->sortByDesc('total_qty')->first();
        $peakHistoricalPeriod = $peakHistoricalRecord ? [
            'ym' => $peakHistoricalRecord->ym,
            'label' => Carbon::createFromFormat('Y-m', $peakHistoricalRecord->ym)->format('F Y'),
            'total_qty' => (int) $peakHistoricalRecord->total_qty,
            'total_revenue' => (float) $peakHistoricalRecord->total_revenue,
            'total_orders' => (int) $peakHistoricalRecord->total_orders,
        ] : null;

        // Month-over-Month & Year-over-Year growth comparison for latest complete/active month
        $latestRecord = $monthlyRecords->last();
        $prevRecord = $monthlyRecords->count() >= 2 ? $monthlyRecords->slice(-2, 1)->first() : null;
        $momGrowth = null;
        if ($latestRecord && $prevRecord && (float) $prevRecord->total_qty > 0) {
            $momGrowth = round((((float) $latestRecord->total_qty - (float) $prevRecord->total_qty) / (float) $prevRecord->total_qty) * 100, 1);
        }

        // Trend slope direction across chronological months
        $n = $monthlyRecords->count();
        $trendDirection = 'insufficient';
        $trendSlope = 0.0;
        if ($n >= 2) {
            $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
            foreach ($monthlyRecords->values() as $idx => $rec) {
                $x = $idx + 1;
                $y = (float) $rec->total_qty;
                $sumX += $x;
                $sumY += $y;
                $sumXY += $x * $y;
                $sumX2 += $x * $x;
            }
            $denom = ($n * $sumX2) - ($sumX * $sumX);
            $trendSlope = $denom != 0 ? round((($n * $sumXY) - ($sumX * $sumY)) / $denom, 2) : 0.0;
            if ($trendSlope > 0.5) {
                $trendDirection = 'upward';
            } elseif ($trendSlope < -0.5) {
                $trendDirection = 'downward';
            } else {
                $trendDirection = 'stable';
            }
        }

        // Timeline labels & values for chronological trend chart
        $timelineLabels = $monthlyRecords->map(fn($r) => Carbon::createFromFormat('Y-m', $r->ym)->format('M Y'))->values()->toArray();
        $timelineQuantities = $monthlyRecords->map(fn($r) => (int) $r->total_qty)->values()->toArray();
        $timelineRevenues = $monthlyRecords->map(fn($r) => round((float) $r->total_revenue, 2))->values()->toArray();

        return [
            'has_data' => $monthlyRecords->isNotEmpty(),
            'months_analyzed' => $n,
            'years_analyzed' => count($years),
            'years' => $years,
            'month_labels' => $monthNames,
            'yoy_series' => array_values($yoySeries),
            'seasonal_months' => array_values($seasonalMonths),
            'overall_monthly_avg_qty' => round($overallMonthlyAvgQty, 1),
            'peak_seasonal_month' => $peakSeasonalMonth,
            'low_seasonal_month' => $lowSeasonalMonth,
            'peak_historical_period' => $peakHistoricalPeriod,
            'mom_growth' => $momGrowth,
            'trend_direction' => $trendDirection,
            'trend_slope' => $trendSlope,
            'timeline_labels' => $timelineLabels,
            'timeline_quantities' => $timelineQuantities,
            'timeline_revenues' => $timelineRevenues,
        ];
    }
}
