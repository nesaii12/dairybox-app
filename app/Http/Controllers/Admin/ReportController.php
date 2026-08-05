<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Inventory;
use App\Exports\SalesExport;
use App\Exports\InventoryExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
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

    protected function parseDateRange(Request $request)
    {
        $request->validate([
            'filter_type' => 'required|in:custom,daily,weekly,monthly,annual',
            'start_date' => 'required_if:filter_type,custom|nullable|date',
            'end_date' => 'required_if:filter_type,custom|nullable|date',
        ]);

        $start = Carbon::today();
        $end = Carbon::today();

        switch ($request->filter_type) {
            case 'daily':
                $start = Carbon::today();
                $end = Carbon::today();
                break;
            case 'weekly':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;
            case 'monthly':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            case 'annual':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $start = Carbon::parse($request->start_date);
                $end = Carbon::parse($request->end_date);
                break;
        }

        return ['start' => $start, 'end' => $end];
    }
}
