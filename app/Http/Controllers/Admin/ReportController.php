<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Inventory;
use App\Exports\SalesExport;
use App\Exports\InventoryExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Display the sales reporting and analysis dashboard based on selected date range.
     */
    public function index(Request $request)
    {
        $dates = $this->parseDateRange($request);
        $startDate = $dates['start']->toDateString();
        $endDate = $dates['end']->toDateString();
        $filterType = $dates['filter_type'];
        $dateRangeLabel = $dates['label'];

        // 1. Core KPIs for the selected window
        $salesQuery = Sale::whereBetween('sales_date', [$startDate, $endDate]);
        $totalRevenue = (float) (clone $salesQuery)->sum('total_amount');
        $totalOrders = (int) (clone $salesQuery)->count();
        $avgOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;

        $orderIds = (clone $salesQuery)->pluck('order_id');
        $totalUnitsSold = (int) OrderItem::whereIn('order_id', $orderIds)->sum('quantity');

        // Fulfillment Breakdown (Delivery vs Pickup vs Walk-in)
        $fulfillmentBreakdown = Order::whereIn('id', $orderIds)
            ->selectRaw('order_type, count(*) as count')
            ->groupBy('order_type')
            ->pluck('count', 'order_type')
            ->toArray();
        $deliveryCount = $fulfillmentBreakdown['Delivery'] ?? 0;
        $pickupCount = $fulfillmentBreakdown['Pickup'] ?? 0;
        $walkInCount = $fulfillmentBreakdown['Walk-in'] ?? 0;

        // 2. Sales Trend Timeline Chart
        $diffDays = $dates['start']->diffInDays($dates['end']);

        if ($diffDays <= 45) {
            $periodDates = [];
            $curr = $dates['start']->copy();
            while ($curr->lte($dates['end'])) {
                $periodDates[$curr->format('Y-m-d')] = [
                    'label' => $curr->format('M d'),
                    'revenue' => 0.0,
                ];
                $curr->addDay();
            }

            $dailySales = DB::table('sales')
                ->whereBetween('sales_date', [$startDate, $endDate])
                ->selectRaw('sales_date, SUM(total_amount) as total')
                ->groupBy('sales_date')
                ->get();

            foreach ($dailySales as $row) {
                $key = Carbon::parse($row->sales_date)->format('Y-m-d');
                if (isset($periodDates[$key])) {
                    $periodDates[$key]['revenue'] = (float) $row->total;
                }
            }

            $chartLabels = array_column($periodDates, 'label');
            $chartValues = array_column($periodDates, 'revenue');
        } else {
            $monthlySales = DB::table('sales')
                ->whereBetween('sales_date', [$startDate, $endDate])
                ->selectRaw('DATE_FORMAT(sales_date, "%Y-%m") as month_key, MIN(sales_date) as sample_date, SUM(total_amount) as total')
                ->groupBy('month_key')
                ->orderBy('month_key', 'asc')
                ->get();

            $chartLabels = $monthlySales->map(fn($m) => Carbon::parse($m->sample_date)->format('M Y'))->toArray();
            $chartValues = $monthlySales->map(fn($m) => (float) $m->total)->toArray();
        }

        // 3. Product Performance Breakdown (Best Selling Products)
        $productSales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('sales', 'orders.id', '=', 'sales.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sales_date', [$startDate, $endDate])
            ->select(
                'products.id',
                'products.product_name',
                'products.size',
                'products.milk_type',
                'products.stock',
                DB::raw('SUM(order_items.quantity) as units_sold'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.product_name', 'products.size', 'products.milk_type', 'products.stock')
            ->orderBy('units_sold', 'desc')
            ->get();

        // 4. Milk Type Decision Analysis (Carabao vs Cow Milk)
        $milkTypeStats = DB::table('order_items')
            ->join('sales', 'order_items.order_id', '=', 'sales.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('sales.sales_date', [$startDate, $endDate])
            ->select(
                'products.milk_type',
                DB::raw('SUM(order_items.quantity) as units_sold'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('products.milk_type')
            ->get();

        $carabaoUnits = 0;
        $carabaoRevenue = 0.0;
        $cowUnits = 0;
        $cowRevenue = 0.0;

        foreach ($milkTypeStats as $stat) {
            if (stripos($stat->milk_type, 'carabao') !== false) {
                $carabaoUnits += (int) $stat->units_sold;
                $carabaoRevenue += (float) $stat->total_revenue;
            } elseif (stripos($stat->milk_type, 'cow') !== false) {
                $cowUnits += (int) $stat->units_sold;
                $cowRevenue += (float) $stat->total_revenue;
            }
        }

        $totalMilkRevenue = $carabaoRevenue + $cowRevenue;
        $carabaoShare = $totalMilkRevenue > 0 ? round(($carabaoRevenue / $totalMilkRevenue) * 100, 1) : 0;
        $cowShare = $totalMilkRevenue > 0 ? round(($cowRevenue / $totalMilkRevenue) * 100, 1) : 0;

        // 5. Paginated Sales Transaction Ledger
        $salesLedger = Sale::with(['order.customer', 'order.orderItems.product'])
            ->whereBetween('sales_date', [$startDate, $endDate])
            ->orderBy('sales_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', compact(
            'startDate',
            'endDate',
            'filterType',
            'dateRangeLabel',
            'totalRevenue',
            'totalOrders',
            'avgOrderValue',
            'totalUnitsSold',
            'deliveryCount',
            'pickupCount',
            'walkInCount',
            'chartLabels',
            'chartValues',
            'productSales',
            'carabaoUnits',
            'carabaoRevenue',
            'carabaoShare',
            'cowUnits',
            'cowRevenue',
            'cowShare',
            'salesLedger'
        ));
    }

    public function salesExcel(Request $request)
    {
        $dates = $this->parseDateRange($request);
        return Excel::download(
            new SalesExport($dates['start']->toDateString(), $dates['end']->toDateString()), 
            "sales_report_{$dates['start']->format('Ymd')}_to_{$dates['end']->format('Ymd')}.xlsx"
        );
    }

    public function salesPdf(Request $request)
    {
        $dates = $this->parseDateRange($request);
        $start = $dates['start']->toDateString();
        $end = $dates['end']->toDateString();

        $sales = Sale::with(['order.customer'])
            ->whereBetween('sales_date', [$start, $end])
            ->orderBy('sales_date', 'asc')
            ->get();

        $totalSales = $sales->sum('total_amount');

        $pdf = Pdf::loadView('admin.reports.sales_pdf', compact('sales', 'start', 'end', 'totalSales'));
        return $pdf->download("sales_report_{$start}_to_{$end}.pdf");
    }

    public function inventoryExcel(Request $request)
    {
        $dates = $this->parseDateRange($request);
        return Excel::download(
            new InventoryExport($dates['start']->toDateString(), $dates['end']->toDateString()), 
            "inventory_report_{$dates['start']->format('Ymd')}_to_{$dates['end']->format('Ymd')}.xlsx"
        );
    }

    public function inventoryPdf(Request $request)
    {
        $dates = $this->parseDateRange($request);
        $start = $dates['start']->toDateString();
        $end = $dates['end']->toDateString();

        $logs = Inventory::with('product')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->orderBy('created_at', 'asc')
            ->get();

        $pdf = Pdf::loadView('admin.reports.inventory_pdf', compact('logs', 'start', 'end'));
        return $pdf->download("inventory_report_{$start}_to_{$end}.pdf");
    }

    /**
     * Parse date range inputs supporting presets and custom windows.
     */
    protected function parseDateRange(Request $request)
    {
        $filterType = $request->input('filter_type', 'monthly');

        switch ($filterType) {
            case 'daily':
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                $label = 'Today (' . Carbon::today()->format('M d, Y') . ')';
                break;
            case 'weekly':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                $label = 'This Week (' . $start->format('M d') . ' - ' . $end->format('M d, Y') . ')';
                break;
            case 'annual':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                $label = 'This Year (' . $start->format('Y') . ')';
                break;
            case 'all':
                $start = Carbon::parse('2025-01-01');
                $end = Carbon::now()->endOfDay();
                $label = 'All Time Records';
                break;
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $start = Carbon::parse($request->start_date)->startOfDay();
                    $end = Carbon::parse($request->end_date)->endOfDay();
                    if ($start->gt($end)) {
                        [$start, $end] = [$end, $start];
                    }
                    $label = $start->format('M d, Y') . ' to ' . $end->format('M d, Y');
                } else {
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now()->endOfMonth();
                    $filterType = 'monthly';
                    $label = 'This Month (' . $start->format('F Y') . ')';
                }
                break;
            case 'monthly':
            default:
                $filterType = 'monthly';
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                $label = 'This Month (' . $start->format('F Y') . ')';
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
            'filter_type' => $filterType,
            'label' => $label,
        ];
    }
}
