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
     * last-month sales momentum combined with linear regression and seasonal adjustment.
     */
    public static function predictProductDemand(int $productId, string $targetMonthYear): float
    {
        $dateExpr = "COALESCE(sales.sales_date, DATE(orders.created_at))";

        // Fetch monthly sales history for the product prior to the target month
        $salesHistory = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
            ->select(
                DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m') as month"),
                DB::raw('SUM(order_items.quantity) as total_qty')
            )
            ->where('order_items.product_id', $productId)
            ->where('orders.order_status', 'Delivered')
            ->whereRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') < ?", [$targetMonthYear])
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        $n = $salesHistory->count();
        if ($n === 0) {
            return 0.0;
        }

        // Handle partial ongoing current month so early-month data doesn't drag down momentum or trend
        $currentYm = Carbon::now()->format('Y-m');
        $latestRec = $salesHistory->last();
        $prevRec = $n >= 2 ? $salesHistory->slice(-2, 1)->first() : null;

        $effectiveHistory = $salesHistory->values();
        if (
            $n >= 2
            && $latestRec->month === $currentYm
            && Carbon::now()->day < 25
            && (float) $latestRec->total_qty < (float) $prevRec->total_qty
        ) {
            // Use completed months for regression trend if current month is still early/partial,
            // while keeping the higher of current vs last completed month for recent momentum
            $regressionHistory = $salesHistory->slice(0, $n - 1)->values();
            $lastMonthQty = max((float) $latestRec->total_qty, (float) $prevRec->total_qty);
            $priorMonthQty = $n >= 3
                ? (float) $salesHistory->slice(-3, 1)->first()->total_qty
                : (float) $prevRec->total_qty;
            $referenceMonthNum = (int) date('m', strtotime($prevRec->month . '-01'));
        } else {
            $regressionHistory = $effectiveHistory;
            $lastMonthQty = (float) $latestRec->total_qty;
            $priorMonthQty = $prevRec ? (float) $prevRec->total_qty : $lastMonthQty;
            $referenceMonthNum = (int) date('m', strtotime($latestRec->month . '-01'));
        }

        // 1. Recent Momentum Forecast (anchored on last month's sales so top sellers last month lead next month)
        $recentMomentum = ($lastMonthQty * 0.75) + ($priorMonthQty * 0.25);

        $regCount = $regressionHistory->count();
        if ($regCount < 2) {
            return round(max(0.0, $recentMomentum), 2);
        }

        // 2. Linear Regression Trend: y = slope * x + intercept
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($regressionHistory as $index => $history) {
            $x = $index + 1;
            $y = (float) $history->total_qty;
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $denominator = $regCount * $sumX2 - $sumX * $sumX;
        $slope = $denominator != 0
            ? ($regCount * $sumXY - $sumX * $sumY) / $denominator
            : 0;
        $intercept = ($sumY - $slope * $sumX) / $regCount;

        $predictedIndex = $regCount + 1;
        $trendForecast = max(0.0, $slope * $predictedIndex + $intercept);

        // 3. Seasonal Adjustment for target calendar month
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

        $overallAverage = $sumY / max(1, $regCount);
        $seasonalIndex = 1.0;

        if ($seasonalData->isNotEmpty() && $overallAverage > 0) {
            $seasonalAverage = (float) $seasonalData->avg();
            $seasonalIndex = $seasonalAverage / $overallAverage;
        } else {
            // Fall back to storewide seasonal index for that calendar month if product-specific month is not yet recorded
            $storeMonthAvg = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
                ->where('orders.order_status', 'Delivered')
                ->whereRaw("MONTH({$dateExpr}) = ?", [$targetMonthNum])
                ->select(DB::raw('SUM(order_items.quantity) as qty'))
                ->groupBy(DB::raw("YEAR({$dateExpr})"))
                ->pluck('qty')
                ->avg();

            $storeOverallAvg = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
                ->where('orders.order_status', 'Delivered')
                ->select(DB::raw('SUM(order_items.quantity) as qty'))
                ->groupBy(DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m')"))
                ->pluck('qty')
                ->avg();

            if ($storeMonthAvg && $storeOverallAvg > 0) {
                $seasonalIndex = (float) $storeMonthAvg / (float) $storeOverallAvg;
            }
        }

        $clampedSeasonal = max(0.75, min(1.35, $seasonalIndex));
        $seasonalTrendForecast = $trendForecast * $clampedSeasonal;
        $seasonalMomentumForecast = $recentMomentum * max(0.85, min(1.25, $seasonalIndex));

        // Blend 65% recent last-month momentum with 35% seasonal trend so last month's top-selling product leads next month
        $blended = (0.65 * $seasonalMomentumForecast) + (0.35 * $seasonalTrendForecast);

        return round(max(0.0, $blended), 2);
    }

    /**
     * Retrieve last month's sales performance per product to explain next-month demand predictions.
     */
    public static function getLastMonthProductStats(?string $targetMonthYear = null): array
    {
        $targetMonthYear = $targetMonthYear ?? Carbon::now()->addMonth()->format('Y-m');
        $dateExpr = "COALESCE(sales.sales_date, DATE(orders.created_at))";

        // Find the latest recorded month prior to targetMonthYear
        $recordedMonths = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
            ->where('orders.order_status', 'Delivered')
            ->whereRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') < ?", [$targetMonthYear])
            ->select(
                DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m') as ym"),
                DB::raw("SUM(order_items.quantity) as total_qty")
            )
            ->groupBy('ym')
            ->orderBy('ym', 'desc')
            ->limit(2)
            ->get();

        if ($recordedMonths->isEmpty()) {
            return [
                'reference_ym' => null,
                'reference_label' => null,
                'products' => [],
                'top_product' => null,
            ];
        }

        // If the most recent month is the current ongoing month and is still partial compared to the last full month,
        // pick the month with the more complete dataset or combine reference awareness
        $latest = $recordedMonths->first();
        $previous = $recordedMonths->count() > 1 ? $recordedMonths->get(1) : null;
        $currentYm = Carbon::now()->format('Y-m');

        $referenceYm = (
            $previous
            && $latest->ym === $currentYm
            && Carbon::now()->day < 25
            && (int) $latest->total_qty < (int) $previous->total_qty
        ) ? $previous->ym : $latest->ym;

        $rows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.order_status', 'Delivered')
            ->whereRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') = ?", [$referenceYm])
            ->select(
                'products.id as product_id',
                'products.product_name',
                'products.size',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.product_name', 'products.size')
            ->orderByDesc('total_qty')
            ->get();

        $productMap = [];
        foreach ($rows as $idx => $row) {
            $productMap[(int) $row->product_id] = [
                'product_id' => (int) $row->product_id,
                'product_name' => $row->product_name,
                'size' => $row->size,
                'qty' => (int) $row->total_qty,
                'revenue' => (float) $row->total_revenue,
                'rank' => $idx + 1,
            ];
        }

        $topRow = $rows->first();

        return [
            'reference_ym' => $referenceYm,
            'reference_label' => Carbon::createFromFormat('Y-m', $referenceYm)->format('F Y'),
            'products' => $productMap,
            'top_product' => $topRow ? [
                'product_id' => (int) $topRow->product_id,
                'product_name' => $topRow->product_name,
                'size' => $topRow->size,
                'qty' => (int) $topRow->total_qty,
                'revenue' => (float) $topRow->total_revenue,
            ] : null,
        ];
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
        $nowYr = (int) Carbon::now()->year;
        $nowMo = (int) Carbon::now()->month;
        $nowYm = Carbon::now()->format('Y-m');
        $isPartialCurrentMonth = Carbon::now()->day < 25 && $monthlyRecords->count() > 1;

        $completedMonthlyRecords = $isPartialCurrentMonth
            ? $monthlyRecords->filter(fn($r) => $r->ym !== $nowYm)
            : $monthlyRecords;

        $overallMonthlyAvgQty = $completedMonthlyRecords->count() > 0
            ? (float) $completedMonthlyRecords->avg('total_qty')
            : (float) ($monthlyRecords->avg('total_qty') ?? 0.0);

        $seasonalMonths = [];
        for ($m = 1; $m <= 12; $m++) {
            $rowsForMonth = $yearlyMonthlyRecords->where('mo', $m);
            if ($isPartialCurrentMonth && $m === $nowMo && $rowsForMonth->count() > 1) {
                $completedRowsForMonth = $rowsForMonth->filter(fn($r) => (int) $r->yr !== $nowYr);
                if ($completedRowsForMonth->isNotEmpty()) {
                    $rowsForMonth = $completedRowsForMonth;
                }
            }
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
        $currentYm = Carbon::now()->format('Y-m');
        $trendRecords = (
            $n >= 3
            && $latestRecord
            && $prevRecord
            && $latestRecord->ym === $currentYm
            && Carbon::now()->day < 25
            && (float) $latestRecord->total_qty < (float) $prevRecord->total_qty
        ) ? $monthlyRecords->slice(0, $n - 1)->values() : $monthlyRecords->values();

        $trendCount = $trendRecords->count();
        $trendDirection = 'insufficient';
        $trendSlope = 0.0;
        if ($trendCount >= 2) {
            $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
            foreach ($trendRecords as $idx => $rec) {
                $x = $idx + 1;
                $y = (float) $rec->total_qty;
                $sumX += $x;
                $sumY += $y;
                $sumXY += $x * $y;
                $sumX2 += $x * $x;
            }
            $denom = ($trendCount * $sumX2) - ($sumX * $sumX);
            $trendSlope = $denom != 0 ? round((($trendCount * $sumXY) - ($sumX * $sumY)) / $denom, 2) : 0.0;
            if ($trendSlope > 0.5) {
                $trendDirection = 'upward';
            } elseif ($trendSlope < -0.5) {
                $trendDirection = 'downward';
            } else {
                $trendDirection = 'stable';
            }
        }

        // Top High Sales Days recorded in history
        $highSalesDays = (clone $baseQuery)
            ->select(
                DB::raw("{$dateExpr} as sales_day"),
                DB::raw("SUM(order_items.quantity) as total_qty"),
                DB::raw("SUM(order_items.subtotal) as total_revenue")
            )
            ->groupBy('sales_day')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(fn($d) => [
                'date' => $d->sales_day,
                'label' => Carbon::parse($d->sales_day)->format('M j, Y'),
                'total_qty' => (int) $d->total_qty,
                'total_revenue' => (float) $d->total_revenue,
            ])
            ->toArray();

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
            'high_sales_days' => $highSalesDays,
            'mom_growth' => $momGrowth,
            'trend_direction' => $trendDirection,
            'trend_slope' => $trendSlope,
            'timeline_labels' => $timelineLabels,
            'timeline_quantities' => $timelineQuantities,
            'timeline_revenues' => $timelineRevenues,
        ];
    }
}
