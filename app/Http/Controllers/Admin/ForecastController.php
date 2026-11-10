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

        $selectedProductId = $request->filled('product_filter') ? (int) $request->input('product_filter') : null;
        $selectedProduct = $selectedProductId ? Product::find($selectedProductId) : null;

        $allProducts = Product::where('status', 'active')->orderBy('product_name')->get();

        $products = Product::with(['category', 'forecasts' => function($q) {
            $q->orderBy('forecast_month', 'desc');
        }])->latest()->paginate($perPage)->withQueryString();

        // Next calendar month
        $targetMonth = Carbon::now()->addMonth()->format('Y-m');
        $targetMonthLabel = Carbon::now()->addMonth()->format('F Y');

        // Historical Sales Trend & Seasonal Demand Analytics
        $analytics = ForecastService::getHistoricalAnalytics($selectedProduct?->id);

        // Overall inventory planning summary across all active products for targetMonth
        $activeForecastProducts = Product::where('status', 'active')
            ->with(['forecasts' => function ($q) use ($targetMonth) {
                $q->where('forecast_month', $targetMonth);
            }])
            ->get();

        $totalPredictedUnits = 0;
        $totalRestockUnits = 0;
        $productsRequiringStock = 0;

        foreach ($activeForecastProducts as $prod) {
            $fc = $prod->forecasts->first();
            if ($fc) {
                $pred = (float) $fc->predicted_sales;
                $restock = ForecastService::recommendedRestock($pred, $prod->stock);
                $totalPredictedUnits += (int) round($pred);
                $totalRestockUnits += $restock;
                if ($restock > 0) {
                    $productsRequiringStock++;
                }
            }
        }

        return view('admin.forecast.index', compact(
            'products',
            'allProducts',
            'selectedProduct',
            'targetMonth',
            'targetMonthLabel',
            'analytics',
            'totalPredictedUnits',
            'totalRestockUnits',
            'productsRequiringStock'
        ));
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

    /**
     * Bulk-generate forecasts for all active products at once.
     */
    public function generateAll(Request $request)
    {
        $targetMonth = Carbon::now()->addMonth()->format('Y-m');
        $targetMonthLabel = Carbon::now()->addMonth()->format('F Y');

        try {
            $count = ForecastService::generateAllProducts($targetMonth);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'FORECAST_BULK_GENERATED',
                'details' => "Bulk-generated demand forecasts for {$count} active product(s) (Month: {$targetMonth})",
                'ip_address' => $request->ip(),
            ]);

            return redirect()->route('admin.forecast.index')->with('success', "Demand forecasts refreshed for {$count} product(s) in {$targetMonthLabel}.");

        } catch (\Exception $e) {
            return redirect()->route('admin.forecast.index')->with('error', "Bulk forecast generation failed: " . $e->getMessage());
        }
    }
}
