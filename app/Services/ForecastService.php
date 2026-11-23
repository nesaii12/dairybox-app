<?php

namespace App\Services;

use App\Models\Forecast;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ForecastService
{
    /**
     * Determine the active rolling year-over-year forecast window and data archival status.
     * 
     * Rules:
     * - Currently in 2026:
     *   Primary basis: 2026 Full-Year Sales to predict 2027 Demand.
     *   2025 data remains active for multi-year comparison and will auto-archive when 2027 arrives.
     * - Once 2027 arrives:
     *   2025 data is automatically archived (excluded from active baseline queries).
     *   Primary basis switches to 2026 Full-Year Sales to predict 2028 Demand.
     * - Rolling onwards (Year Y >= 2028):
     *   Data older than Y-1 is archived, Y-1 full year is basis, predicting Y+1 demand.
     */
    public static function getRollingWindow(?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now();
        $currentYear = (int) $now->year;

        if ($currentYear <= 2026) {
            $basisYear = 2026;
            $targetYear = 2027;
            $archivedYears = [];
            $pendingArchiveYear = 2025;
            $archiveStatus = '2025 Data Active (Auto-archives upon 2027 rollover)';
            $isArchived2025 = false;
        } elseif ($currentYear === 2027) {
            $basisYear = 2026;
            $targetYear = 2028;
            $archivedYears = [2025];
            $pendingArchiveYear = null;
            $archiveStatus = '2025 Data Automatically Archived (Basis: 2026 Full-Year Sales → Target: 2028 Demand)';
            $isArchived2025 = true;
        } else {
            $basisYear = $currentYear - 1;
            $targetYear = $currentYear + 1;
            $archivedYears = range(2025, $currentYear - 2);
            $pendingArchiveYear = null;
            $archiveStatus = "Historical data prior to {$basisYear} archived";
            $isArchived2025 = true;
        }

        return [
            'current_year' => $currentYear,
            'basis_year' => $basisYear,
            'target_year' => $targetYear,
            'archived_years' => $archivedYears,
            'pending_archive_year' => $pendingArchiveYear,
            'archive_status' => $archiveStatus,
            'is_archived_2025' => $isArchived2025,
            'basis_label' => "{$basisYear} Full-Year Sales",
            'target_label' => "{$targetYear} Demand",
            'description' => ($currentYear <= 2026)
                ? "Currently using {$basisYear} Full-Year Sales as the basis to predict {$targetYear} Demand. Once 2027 arrives, 2025 data automatically archives, switching primary forecast basis to 2026 Full-Year Sales to predict 2028 Demand."
                : "2025 historical data is automatically archived. Primary forecast basis is {$basisYear} Full-Year Sales predicting {$targetYear} Demand.",
        ];
    }

    /**
     * Predict demand for a product in a target month using the rolling year-over-year restart model.
     * Uses the basis year (2026 Full-Year Sales) as the foundational baseline to project target demand (2027 Demand).
     * Excludes any archived years (e.g. 2025 data is excluded once 2027 arrives).
     */
    public static function predictProductDemand(int $productId, string $targetMonthYear): float
    {
        $window = self::getRollingWindow();
        $basisYear = $window['basis_year'];
        $targetYear = $window['target_year'];
        $archivedYears = $window['archived_years'];

        $targetParts = explode('-', $targetMonthYear);
        $targetCalMonth = isset($targetParts[1]) ? (int) $targetParts[1] : 1;
        $dateExpr = "COALESCE(sales.sales_date, DATE(orders.created_at))";

        // Query product sales, strictly excluding archived data
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
            ->where('order_items.product_id', $productId)
            ->whereIn('orders.order_status', ['Delivered', 'Delivered - Pending Remittance', 'Paid & Remitted']);

        if (!empty($archivedYears)) {
            $query->whereRaw("YEAR({$dateExpr}) NOT IN (" . implode(',', array_map('intval', $archivedYears)) . ")");
        }

        // Limit to historical data prior to the target projection month
        $query->whereRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') < ?", [$targetMonthYear]);

        $salesHistory = $query->select(
            DB::raw("YEAR({$dateExpr}) as yr"),
            DB::raw("MONTH({$dateExpr}) as mo"),
            DB::raw("DATE_FORMAT({$dateExpr}, '%Y-%m') as ym"),
            DB::raw("SUM(order_items.quantity) as total_qty"),
            DB::raw("SUM(order_items.subtotal) as total_revenue")
        )
        ->groupBy('yr', 'mo', 'ym')
        ->orderBy('ym', 'asc')
        ->get();

        if ($salesHistory->isEmpty()) {
            return 0.0;
        }

        // 1. Primary Baseline: Basis Year (2026 Full-Year Sales)
        $basisRecords = $salesHistory->where('yr', $basisYear);
        $basisMonthsCount = $basisRecords->count();
        $basisTotalQty = (float) $basisRecords->sum('total_qty');
        $basisMonthlyAvg = $basisMonthsCount > 0 ? ($basisTotalQty / max(1, $basisMonthsCount)) : 0.0;

        // Sales in matching calendar month of the basis year (e.g. May 2026 for May 2027)
        $basisMonthRecord = $basisRecords->firstWhere('mo', $targetCalMonth);
        $basisMonthQty = $basisMonthRecord ? (float) $basisMonthRecord->total_qty : null;

        // 2. Momentum within the Basis Year (anchored on recent months of 2026)
        $latestBasisRec = $basisRecords->sortByDesc('ym')->first();
        $prevBasisRec = $basisRecords->sortByDesc('ym')->skip(1)->first();
        $recentMomentum = $latestBasisRec ? (float) $latestBasisRec->total_qty : $basisMonthlyAvg;
        if ($latestBasisRec && $prevBasisRec) {
            $recentMomentum = ((float) $latestBasisRec->total_qty * 0.70) + ((float) $prevBasisRec->total_qty * 0.30);
        }

        // 3. Seasonal Demand Index for the target calendar month
        // Compare historical sales for this calendar month across active (unarchived) years
        $targetMonthAvg = $salesHistory->where('mo', $targetCalMonth)->avg('total_qty');
        $overallAvg = $salesHistory->avg('total_qty');
        $seasonalIndex = ($overallAvg > 0 && $targetMonthAvg > 0)
            ? ((float) $targetMonthAvg / (float) $overallAvg)
            : 1.0;
        $clampedSeasonal = max(0.80, min(1.30, $seasonalIndex));

        // 4. Year-over-Year Growth Trend (Basis Year vs Prior Unarchived Year, e.g. 2026 vs 2025)
        $priorYear = $basisYear - 1;
        $priorYearSales = $salesHistory->where('yr', $priorYear);
        $yoyGrowthFactor = 1.0;

        if ($priorYearSales->isNotEmpty() && !in_array($priorYear, $archivedYears)) {
            $priorTotal = (float) $priorYearSales->sum('total_qty');
            if ($priorTotal > 0 && $basisTotalQty > 0) {
                $priorMonths = max(1, $priorYearSales->count());
                $basisMonths = max(1, $basisMonthsCount);
                $priorRate = $priorTotal / $priorMonths;
                $basisRate = $basisTotalQty / $basisMonths;
                $rawGrowth = ($basisRate - $priorRate) / $priorRate;
                $yoyGrowthFactor = 1.0 + max(-0.10, min(0.20, $rawGrowth));
            }
        }

        // 5. Synthesize Rolling Demand Forecast
        if ($basisMonthQty !== null && $basisMonthQty > 0) {
            $baseDemand = ($basisMonthQty * 0.65) + ($recentMomentum * 0.35);
        } else {
            $baseDemand = ($basisMonthlyAvg * 0.50) + ($recentMomentum * 0.50);
        }

        $predictedSales = $baseDemand * $yoyGrowthFactor * $clampedSeasonal;

        return round(max(0.0, $predictedSales), 2);
    }

    /**
     * Retrieve last month's sales performance per product to explain next-month demand predictions.
     */
    public static function getLastMonthProductStats(?string $targetMonthYear = null): array
    {
        $window = self::getRollingWindow();
        $targetMonthYear = $targetMonthYear ?? ($window['target_year'] . '-01');
        $dateExpr = "COALESCE(sales.sales_date, DATE(orders.created_at))";

        // Find the latest recorded month prior to targetMonthYear, excluding archived years
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
            ->whereIn('orders.order_status', ['Delivered', 'Delivered - Pending Remittance', 'Paid & Remitted'])
            ->whereRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') < ?", [$targetMonthYear]);

        if (!empty($window['archived_years'])) {
            $query->whereRaw("YEAR({$dateExpr}) NOT IN (" . implode(',', array_map('intval', $window['archived_years'])) . ")");
        }

        $recordedMonths = $query->select(
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
            ->whereIn('orders.order_status', ['Delivered', 'Delivered - Pending Remittance', 'Paid & Remitted'])
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
     * Safety buffer: forecast + 20% headroom, capped at Product::MAX_STOCK_LIMIT (100 units)
     * to prevent dairy spoilage, minus current stock. Min 0.
     */
    public static function recommendedRestock(float $predictedSales, int $currentStock): int
    {
        $buffer = min(Product::MAX_STOCK_LIMIT, (int) ceil($predictedSales * 1.20));
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
        $window = self::getRollingWindow();
        $dateExpr = "COALESCE(sales.sales_date, DATE(orders.created_at))";

        $baseQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
            ->whereIn('orders.order_status', ['Delivered', 'Delivered - Pending Remittance', 'Paid & Remitted']);

        if (!empty($window['archived_years'])) {
            $baseQuery->whereRaw("YEAR({$dateExpr}) NOT IN (" . implode(',', array_map('intval', $window['archived_years'])) . ")");
        }

        if ($productId) {
            $baseQuery->where('order_items.product_id', $productId);
        }

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
            'rolling_window' => $window,
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
