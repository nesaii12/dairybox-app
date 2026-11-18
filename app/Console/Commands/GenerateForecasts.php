<?php

namespace App\Console\Commands;

use App\Services\ForecastService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateForecasts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forecast:generate-all
                            {--month= : Target month in YYYY-MM format (defaults to next calendar month)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate or refresh demand forecasts for all active products based on historical sales data.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetMonth = $this->option('month')
            ?? Carbon::now()->addMonth()->format('Y-m');

        $targetLabel = Carbon::createFromFormat('Y-m', $targetMonth)->format('F Y');

        $this->info("Generating demand forecasts for {$targetLabel}...");

        try {
            $count = ForecastService::generateAllProducts($targetMonth);
            $this->info("Successfully generated forecasts for {$count} active product(s).");
        } catch (\Throwable $e) {
            $this->error("Forecast generation failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

