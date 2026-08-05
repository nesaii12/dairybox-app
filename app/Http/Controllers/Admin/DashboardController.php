<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Sale;
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
            ->where('expiration_date', '<=', Carbon::now()->addDays(7))
            ->where('expiration_date', '>', Carbon::now())
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

        return view('admin.dashboard', compact(
            'totalSales', 'totalOrders', 'totalCustomers', 'totalProducts',
            'lowStockAlerts', 'nearExpirationAlerts', 'recentOrders',
            'salesLabels', 'salesValues', 'productLabels', 'productValues'
        ));
    }
}
