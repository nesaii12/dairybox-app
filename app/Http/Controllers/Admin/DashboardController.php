<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Models\Product;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Sale;
use App\Services\ForecastService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. KPI Counts
        $totalSales = Sale::sum('total_amount');
        $totalOrders = Order::count();
        $totalCustomers = User::where('role', 'customer')->count();
        $totalProducts = Product::count();
        
        $lowStockAlerts = Product::where('status', 'active')
            ->where('stock', '<', 10)
            ->count();

        $nearExpirationAlerts = Product::where('status', 'active')
            ->whereNotNull('expiration_date')
            ->where('stock', '>', 0)
            ->where('expiration_date', '<=', Carbon::now()->addDays(7))
            ->count();

        // 2. Recent Orders
        $recentOrders = Order::with('customer')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 3. Chart 1: Monthly Sales Graph (last 6 months)
        $rawSales = DB::table('sales')
            ->select('sales_date', 'total_amount')
            ->orderBy('sales_date', 'asc')
            ->get();

        $salesData = $rawSales->groupBy(function($item) {
            return Carbon::parse($item->sales_date)->format('Y-m');
        })->map(function($group, $key) {
            return [
                'sort_key' => $key,
                'month_label' => Carbon::parse($group->first()->sales_date)->format('M Y'),
                'total_sales' => (float) $group->sum('total_amount')
            ];
        })->sortBy('sort_key')->take(6);

        $salesLabels = $salesData->pluck('month_label')->toArray();
        $salesValues = $salesData->pluck('total_sales')->toArray();

        // 4. Chart 2: Best Selling Products (Top 5)
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.product_name',
                'products.size',
                DB::raw('SUM(order_items.quantity) as total_qty')
            )
            ->where('orders.order_status', 'Delivered')
            ->groupBy('products.product_name', 'products.size')
            ->orderBy('total_qty', 'desc')
            ->take(5)
            ->get();

        $productLabels = $topProducts->map(function($p) {
            return "{$p->product_name} ({$p->size})";
        })->toArray();
        $productValues = $topProducts->pluck('total_qty')->toArray();

        // 5. Forecast Summary for Dashboard Widget (Rolling Year-over-Year Baseline)
        $rollingWindow = ForecastService::getRollingWindow();
        $targetMonth = $rollingWindow['target_year'] . '-01';
        $targetMonthLabel = Carbon::createFromFormat('Y-m', $targetMonth)->format('F Y');

        // Automatically ensure forecasts exist for the target forecast month
        $existingForecastsCount = Forecast::where('forecast_month', $targetMonth)->count();
        if ($existingForecastsCount === 0) {
            ForecastService::generateAllProducts($targetMonth);
        }

        // Load all active products with their latest forecast for target month
        $forecastSummary = Product::where('status', 'active')
            ->with(['category', 'forecasts' => function ($q) use ($targetMonth) {
                $q->where('forecast_month', $targetMonth);
            }])
            ->get()
            ->map(function ($product) use ($targetMonth) {
                $forecast = $product->forecasts->first();
                $predictedSales = $forecast ? (float) $forecast->predicted_sales : null;
                $expectedRevenue = $predictedSales !== null ? round($predictedSales * (float) $product->price, 2) : null;
                $recommendedRestock = $predictedSales !== null
                    ? ForecastService::recommendedRestock($predictedSales, $product->stock)
                    : null;
                $stockoutRisk = $predictedSales !== null
                    ? ForecastService::stockoutRisk($predictedSales, $product->stock)
                    : 'unknown';

                return [
                    'id'                => $product->id,
                    'name'              => $product->product_name . ' (' . $product->size . ')',
                    'category'          => $product->category?->category_name ?? '—',
                    'current_stock'     => $product->stock,
                    'price'             => (float) $product->price,
                    'predicted_sales'   => $predictedSales,
                    'expected_revenue'  => $expectedRevenue,
                    'recommended_restock' => $recommendedRestock,
                    'stockout_risk'     => $stockoutRisk,
                    'forecast_updated'  => $forecast?->updated_at,
                ];
            });

        // Count products with forecasts generated
        $forecastedCount = $forecastSummary->whereNotNull('predicted_sales')->count();

        // Products at critical/low stockout risk (sorted by severity)
        $stockoutRiskProducts = $forecastSummary
            ->whereIn('stockout_risk', ['critical', 'low'])
            ->sortByDesc(fn($p) => $p['stockout_risk'] === 'critical' ? 2 : 1)
            ->take(5)
            ->values();

        // Top 5 predicted demand products (highest predicted_sales)
        $topDemandProducts = $forecastSummary
            ->whereNotNull('predicted_sales')
            ->sortByDesc('predicted_sales')
            ->take(5)
            ->values();

        // Total expected revenue from forecasts
        $totalForecastRevenue = $forecastSummary->sum('expected_revenue');

        // Active catalog products & registered customers for Walk-in Sales form
        $activeProducts = Product::where('status', 'active')
            ->with('category')
            ->orderBy('product_name')
            ->get()
            ->map(function ($product) {
                $sizesWithPrices = [];
                foreach ($product->available_sizes as $size) {
                    $sizesWithPrices[] = [
                        'size' => $size,
                        'price' => (float) $product->getPriceForSize($size),
                        'formatted_price' => '₱' . number_format($product->getPriceForSize($size), 2),
                    ];
                }

                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'category_name' => $product->category?->category_name ?? '',
                    'milk_type' => $product->milk_type ?? 'Cow',
                    'size' => $product->size ?? 'Medium',
                    'base_price' => (float) $product->price,
                    'stock' => InventoryService::getAvailableStock($product),
                    'physical_stock' => (int) $product->stock,
                    'reserved_stock' => (int) ($product->reserved_stock ?? 0),
                    'sizes' => $sizesWithPrices,
                    'sizes_summary' => implode(' | ', array_map(fn($sp) => "{$sp['size']}: {$sp['formatted_price']}", $sizesWithPrices)),
                ];
            });

        $customers = User::where('role', 'customer')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Upcoming / Active Reservations overview for Dashboard
        try {
            $upcomingReservations = Reservation::with('product')
                ->whereIn('status', [
                    Reservation::STATUS_PENDING,
                    Reservation::STATUS_CONFIRMED,
                    Reservation::STATUS_READY,
                ])
                ->orderBy('pickup_date', 'asc')
                ->take(5)
                ->get();
            $totalReservedUnits = (int) Reservation::whereIn('status', Reservation::ALLOCATED_STATUSES)
                ->sum('allocated_quantity');
        } catch (\Throwable $e) {
            $upcomingReservations = collect();
            $totalReservedUnits = 0;
        }

        return view('admin.dashboard', compact(
            'totalSales', 'totalOrders', 'totalCustomers', 'totalProducts',
            'lowStockAlerts', 'nearExpirationAlerts', 'recentOrders',
            'salesLabels', 'salesValues', 'productLabels', 'productValues',
            'targetMonth', 'targetMonthLabel', 'rollingWindow',
            'forecastedCount', 'forecastSummary',
            'stockoutRiskProducts', 'topDemandProducts', 'totalForecastRevenue',
            'activeProducts', 'customers',
            'upcomingReservations', 'totalReservedUnits'
        ));
    }
}

