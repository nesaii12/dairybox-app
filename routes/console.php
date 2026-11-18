<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly threshold check for low stock & batch expiration
Schedule::command('stock:check-thresholds')->dailyAt('00:00');

// Auto-refresh demand forecasts for all active products every day at 01:00 AM
// to incorporate any newly completed (Delivered) sales transactions.
Schedule::command('forecast:generate-all')->dailyAt('01:00');
