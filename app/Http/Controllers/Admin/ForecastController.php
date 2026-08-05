<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Forecast;
use App\Models\AuditLog;
use App\Services\ForecastService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ForecastController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', session('per_page_forecast', 10));
        session(['per_page_forecast' => $perPage]);

        $products = Product::with(['category', 'forecasts' => function($q) {
            $q->orderBy('forecast_month', 'desc');
        }])->latest()->paginate($perPage)->withQueryString();

        // Next calendar month
        $targetMonth = Carbon::now()->addMonth()->format('Y-m');
        $targetMonthLabel = Carbon::now()->addMonth()->format('F Y');

        return view('admin.forecast.index', compact('products', 'targetMonth', 'targetMonthLabel'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $productId = $request->product_id;
        $product = Product::findOrFail($productId);
        $targetMonth = Carbon::now()->addMonth()->format('Y-m');
        $targetMonthLabel = Carbon::now()->addMonth()->format('F Y');

        try {
            // Predict demand
            $predictedQty = ForecastService::predictProductDemand($productId, $targetMonth);

            // Save in database
            Forecast::updateOrCreate(
                [
                    'product_id' => $productId,
                    'forecast_month' => $targetMonth,
                ],
                [
                    'predicted_sales' => $predictedQty,
                ]
            );

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'FORECAST_GENERATED',
                'details' => "Generated demand forecast for {$product->product_name} (Month: {$targetMonth}, Predicted Sales: {$predictedQty} units)",
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('admin.forecast.index')->with('success', "Forecasting generated successfully for {$product->product_name} in {$targetMonthLabel}. Predicted Demand: " . round($predictedQty, 1) . " units.");

        } catch (\Exception $e) {
            return redirect()->route('admin.forecast.index')->with('error', "Forecasting calculation failed: " . $e->getMessage());
        }
    }
}
