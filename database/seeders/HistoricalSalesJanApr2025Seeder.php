<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Services\ForecastService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricalSalesJanApr2025Seeder extends Seeder
{
    /**
     * Populate realistic completed historical sales records for January–April 2025
     * to complete the full 2025 Year-over-Year comparison curve and stabilize
     * seasonal baselines and linear-regression trend calculations.
     *
     * Strictly additive and idempotent: skips any month in Jan–Apr 2025 that already
     * has completed sales records and never modifies May–Dec 2025 or 2026 records.
     */
    public function run(): void
    {
        $products = Product::with('category')->orderBy('id')->get();
        if ($products->isEmpty()) {
            $this->command?->warn('No catalog products found. Skipping Jan–Apr 2025 historical sales seeding.');
            return;
        }

        $customers = User::where('role', 'customer')
            ->where('status', 'active')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        if ($customers->isEmpty()) {
            $fallbackId = User::value('id') ?? 1;
            $customers = collect([$fallbackId]);
        }

        $riders = User::where('role', 'rider')
            ->where('status', 'active')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        // Monthly unit targets for Jan–Apr 2025 (~950 to 1,380 units/month),
        // scaling smoothly into May 2025's 1,982-unit peak while staying below 2026 YoY.
        $monthlyTargets = [
            '2025-01' => ['target_units' => 960,  'profile' => 'offpeak'],
            '2025-02' => ['target_units' => 1085, 'profile' => 'offpeak'],
            '2025-03' => ['target_units' => 1245, 'profile' => 'presummer'],
            '2025-04' => ['target_units' => 1380, 'profile' => 'presummer_peak'],
        ];

        $paymentPatterns = [
            ['order_type' => 'Pickup',   'payment_method' => 'cash',   'payment_channel' => null,          'address' => 'Storefront Pickup'],
            ['order_type' => 'Walk-in',  'payment_method' => 'cash',   'payment_channel' => null,          'address' => 'Walk-in Counter'],
            ['order_type' => 'Walk-in',  'payment_method' => 'gcash',  'payment_channel' => 'GCASH',       'address' => 'Walk-in Counter'],
            ['order_type' => 'Delivery', 'payment_method' => 'xendit', 'payment_channel' => 'GCASH',       'address' => 'Local Town Center Delivery'],
            ['order_type' => 'Delivery', 'payment_method' => 'cod',    'payment_channel' => null,          'address' => 'Local Barangay Delivery'],
            ['order_type' => 'Pickup',   'payment_method' => 'gcash',  'payment_channel' => 'GCASH',       'address' => 'Storefront Pickup'],
            ['order_type' => 'Delivery', 'payment_method' => 'xendit', 'payment_channel' => 'CREDIT_CARD', 'address' => 'Local Town Center Delivery'],
        ];

        $dateExpr = "COALESCE(sales.sales_date, DATE(orders.created_at))";
        $seq = 1;
        $insertedMonths = 0;

        DB::transaction(function () use ($monthlyTargets, $products, $customers, $riders, $paymentPatterns, $dateExpr, &$seq, &$insertedMonths) {
            foreach ($monthlyTargets as $ym => $config) {
                $existingCount = DB::table('orders')
                    ->leftJoin('sales', 'orders.id', '=', 'sales.order_id')
                    ->whereRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') = ?", [$ym])
                    ->whereIn('orders.order_status', ['Delivered', 'Delivered - Pending Remittance', 'Paid & Remitted'])
                    ->count();

                if ($existingCount > 0) {
                    $this->command?->info("Skipping {$ym}: {$existingCount} completed order(s) already exist.");
                    continue;
                }

                $monthStart = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
                $daysInMonth = $monthStart->daysInMonth;
                $dailyUnitTargets = $this->distributeMonthlyUnitsAcrossDays($monthStart, $daysInMonth, $config['target_units']);

                $monthTotalUnits = 0;
                $monthTotalRevenue = 0.0;

                foreach ($dailyUnitTargets as $dayIndex => $dayUnits) {
                    $dateObj = $monthStart->copy()->addDays($dayIndex);
                    $dateStr = $dateObj->format('Y-m-d');

                    // Deterministic afternoon order time between 10:00 and 16:45
                    $hour = 10 + (($dayIndex * 3 + $seq) % 7);
                    $minute = (($dayIndex * 11 + $seq * 7) % 60);
                    $timeStr = sprintf('%02d:%02d:00', $hour, $minute);
                    $ts = Carbon::parse("{$dateStr} {$timeStr}");

                    $pattern = $paymentPatterns[($seq - 1) % count($paymentPatterns)];
                    $customerId = $customers[($seq - 1) % $customers->count()];
                    $riderId = ($pattern['order_type'] === 'Delivery' && $riders->isNotEmpty())
                        ? $riders[($seq - 1) % $riders->count()]
                        : null;

                    $items = $this->allocateDayUnitsAcrossProducts($products, $dayUnits, $config['profile'], $dayIndex);
                    $orderSubtotal = round(array_sum(array_column($items, 'subtotal')), 2);
                    $orderUnits = array_sum(array_column($items, 'quantity'));

                    $orderNum = sprintf('DB-HIST-%s-%04d', $dateObj->format('Ymd'), $seq++);

                    $orderId = DB::table('orders')->insertGetId([
                        'order_number' => $orderNum,
                        'customer_id' => $customerId,
                        'rider_id' => $riderId,
                        'order_type' => $pattern['order_type'],
                        'payment_method' => $pattern['payment_method'],
                        'payment_status' => 'Paid',
                        'payment_channel' => $pattern['payment_channel'],
                        'paid_at' => $ts,
                        'delivery_address' => $pattern['address'],
                        'schedule_date' => $dateStr,
                        'schedule_time' => $timeStr,
                        'subtotal' => $orderSubtotal,
                        'delivery_fee' => 0.00,
                        'total_amount' => $orderSubtotal,
                        'down_payment_paid' => 0.00,
                        'pending_balance' => null,
                        'order_status' => 'Delivered',
                        'cancelled_after_arrival' => false,
                        'created_at' => $ts,
                        'updated_at' => $ts,
                    ]);

                    foreach ($items as $item) {
                        DB::table('order_items')->insert([
                            'order_id' => $orderId,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                            'subtotal' => $item['subtotal'],
                            'created_at' => $ts,
                            'updated_at' => $ts,
                        ]);
                    }

                    DB::table('sales')->insert([
                        'order_id' => $orderId,
                        'total_amount' => $orderSubtotal,
                        'sales_date' => $dateStr,
                        'created_at' => $ts,
                        'updated_at' => $ts,
                    ]);

                    $monthTotalUnits += $orderUnits;
                    $monthTotalRevenue = round($monthTotalRevenue + $orderSubtotal, 2);
                }

                $insertedMonths++;
                $this->command?->info(sprintf(
                    'Seeded %s: %d orders, %d units, PHP %s',
                    $ym,
                    $daysInMonth,
                    $monthTotalUnits,
                    number_format($monthTotalRevenue, 2)
                ));
            }
        });

        if ($insertedMonths > 0) {
            // Refresh forecasts for the active target window and any already-generated forecast months
            $window = ForecastService::getRollingWindow();
            $defaultTarget = $window['target_year'] . '-01';
            $existingMonths = DB::table('forecasts')
                ->distinct()
                ->pluck('forecast_month')
                ->push($defaultTarget)
                ->unique()
                ->sort()
                ->values();

            foreach ($existingMonths as $fm) {
                ForecastService::generateAllProducts($fm);
            }

            $this->command?->info('Refreshed product demand forecasts for: ' . $existingMonths->implode(', '));
        }
    }

    /**
     * Distribute a monthly unit target across all days of the month with realistic
     * weekend uplift and gentle deterministic day-to-day variation, summing exactly to targetUnits.
     */
    protected function distributeMonthlyUnitsAcrossDays(Carbon $monthStart, int $daysInMonth, int $targetUnits): array
    {
        $weights = [];
        for ($d = 0; $d < $daysInMonth; $d++) {
            $dayOfWeek = $monthStart->copy()->addDays($d)->dayOfWeek;
            $isWeekend = ($dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY);
            $isPayday = in_array($d + 1, [14, 15, 16, 28, 29, 30, 31], true);

            $w = 1.0;
            if ($isWeekend) {
                $w += 0.22;
            }
            if ($isPayday) {
                $w += 0.14;
            }
            // Subtle deterministic wave (-0.06 to +0.06)
            $w += (((($d * 7) % 5) - 2) * 0.03);
            $weights[$d] = max(0.75, $w);
        }

        $weightSum = array_sum($weights);
        $allocations = [];
        $allocated = 0;

        foreach ($weights as $d => $w) {
            $units = max(12, (int) floor(($w / $weightSum) * $targetUnits));
            $allocations[$d] = $units;
            $allocated += $units;
        }

        // Distribute remaining units across highest-weight days so the monthly total is exact
        $remainder = $targetUnits - $allocated;
        $sortedDays = array_keys($weights);
        usort($sortedDays, fn ($a, $b) => $weights[$b] <=> $weights[$a]);

        $idx = 0;
        while ($remainder > 0) {
            $dayKey = $sortedDays[$idx % count($sortedDays)];
            $allocations[$dayKey]++;
            $remainder--;
            $idx++;
        }

        while ($remainder < 0) {
            $dayKey = $sortedDays[count($sortedDays) - 1 - ($idx % count($sortedDays))];
            if ($allocations[$dayKey] > 12) {
                $allocations[$dayKey]--;
                $remainder++;
            }
            $idx++;
        }

        return $allocations;
    }

    /**
     * Allocate a day's exact unit count across catalog products (including Fresh Milk,
     * Pasteurized Mango Milk, Milk Cheese, Pastillas Milk, Pandan Milk, Graham Bars, etc.)
     * so unit shares remain consistent with the store's historical product mix.
     */
    protected function allocateDayUnitsAcrossProducts($products, int $dayUnits, string $profile, int $dayIndex): array
    {
        $weights = [];
        foreach ($products as $prod) {
            $nameLower = strtolower($prod->product_name . ' ' . ($prod->category?->category_name ?? ''));
            $isGraham = str_contains($nameLower, 'graham');
            $isIceCream = str_contains($nameLower, 'ice');
            $isMangoOrChocoOrStraw = str_contains($nameLower, 'mango')
                || str_contains($nameLower, 'choco')
                || str_contains($nameLower, 'strawberry');
            $isFreshOrYogurt = str_contains($nameLower, 'fresh milk') || str_contains($nameLower, 'yogurt');
            $isSpecialtyMilk = str_contains($nameLower, 'cheese')
                || str_contains($nameLower, 'pastillas')
                || str_contains($nameLower, 'pandan')
                || str_contains($nameLower, 'ube');

            if ($profile === 'presummer_peak') {
                // April transition into summer peak: graham bars, ice cream, mango/choco milk rise
                $w = $isGraham ? 0.22
                    : ($isIceCream ? 0.11
                    : ($isMangoOrChocoOrStraw ? 0.08
                    : ($isFreshOrYogurt ? 0.065
                    : ($isSpecialtyMilk ? 0.045 : 0.05))));
            } elseif ($profile === 'presummer') {
                // March ramp-up: balanced flavored milks, fresh milk, yogurt, graham bars
                $w = $isGraham ? 0.21
                    : ($isIceCream ? 0.095
                    : ($isMangoOrChocoOrStraw ? 0.075
                    : ($isFreshOrYogurt ? 0.065
                    : ($isSpecialtyMilk ? 0.048 : 0.05))));
            } else {
                // Jan–Feb off-peak: steady daily dairy staples + graham bars
                $w = $isGraham ? 0.20
                    : ($isIceCream ? 0.085
                    : ($isMangoOrChocoOrStraw ? 0.07
                    : ($isFreshOrYogurt ? 0.07
                    : ($isSpecialtyMilk ? 0.05 : 0.05))));
            }

            $weights[$prod->id] = $w;
        }

        $weightSum = array_sum($weights);
        $productMap = $products->keyBy('id');

        $quantities = [];
        $allocated = 0;

        foreach ($weights as $id => $w) {
            $qty = max(1, (int) floor(($w / $weightSum) * $dayUnits));
            $quantities[$id] = $qty;
            $allocated += $qty;
        }

        $remainder = $dayUnits - $allocated;
        $orderedIds = array_keys($weights);
        usort($orderedIds, fn ($a, $b) => $weights[$b] <=> $weights[$a]);

        $step = 0;
        while ($remainder > 0) {
            // Rotate slightly by dayIndex among top products for realistic variety
            $pick = $orderedIds[($step + $dayIndex) % min(6, count($orderedIds))];
            $quantities[$pick]++;
            $remainder--;
            $step++;
        }

        while ($remainder < 0) {
            $pick = $orderedIds[count($orderedIds) - 1 - ($step % count($orderedIds))];
            if ($quantities[$pick] > 1) {
                $quantities[$pick]--;
                $remainder++;
            }
            $step++;
        }

        $items = [];
        foreach ($quantities as $id => $qty) {
            $prod = $productMap[$id];
            $price = round(max(1.0, (float) $prod->price), 2);
            $items[] = [
                'product_id' => $id,
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => round($qty * $price, 2),
            ];
        }

        return $items;
    }
}
