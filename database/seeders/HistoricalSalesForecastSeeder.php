<?php

namespace Database\Seeders;

use App\Models\Forecast;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\ForecastService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricalSalesForecastSeeder extends Seeder
{
    /**
     * Seed the historical May 2025 – October 2026 store & grant sales dataset
     * and refresh product demand forecasts.
     */
    public function run(): void
    {
        $customerId = User::where('role', 'customer')->value('id') ?? User::value('id') ?? 1;
        $riderId = User::where('role', 'rider')->value('id');

        $products = Product::where('status', 'active')->orderBy('id')->get()->keyBy('id');
        if ($products->isEmpty()) {
            $this->command?->warn('No active products found to attach historical sales.');
            return;
        }

        // Remove any previously seeded DB-HIST-* orders so re-running is idempotent without touching user orders
        $existingHistOrderIds = Order::where('order_number', 'like', 'DB-HIST-%')->pluck('id');
        if ($existingHistOrderIds->isNotEmpty()) {
            Sale::whereIn('order_id', $existingHistOrderIds)->delete();
            OrderItem::whereIn('order_id', $existingHistOrderIds)->delete();
            Order::whereIn('id', $existingHistOrderIds)->delete();
        }

        $monthlyPlan = [
            '2025-05' => [
                'total' => 70563.00,
                'profile' => 'summer',
                'high_days' => [
                    '2025-05-04' => 2394.00,
                    '2025-05-07' => 4980.00,
                    '2025-05-08' => 3505.00,
                    '2025-05-12' => 3570.00,
                    '2025-05-14' => 4778.00,
                    '2025-05-21' => 4350.00,
                    '2025-05-22' => 3350.00,
                    '2025-05-26' => 4282.00,
                    '2025-05-28' => 4506.00,
                    '2025-05-29' => 3570.00,
                    '2025-05-30' => 4570.00,
                ],
                'regular_days' => [
                    '2025-05-02', '2025-05-05', '2025-05-09', '2025-05-11',
                    '2025-05-15', '2025-05-17', '2025-05-19', '2025-05-23',
                    '2025-05-25', '2025-05-31',
                ],
            ],
            '2025-06' => [
                'total' => 68321.00,
                'profile' => 'summer',
                'high_days' => [
                    '2025-06-02' => 4080.00,
                    '2025-06-04' => 3522.00,
                    '2025-06-05' => 3600.00,
                    '2025-06-10' => 4350.00,
                    '2025-06-11' => 4700.00,
                    '2025-06-16' => 3946.00,
                    '2025-06-17' => 5164.00,
                    '2025-06-18' => 5215.00,
                    '2025-06-19' => 2042.00,
                    '2025-06-22' => 4890.00,
                    '2025-06-24' => 2864.00,
                ],
                'regular_days' => [
                    '2025-06-01', '2025-06-06', '2025-06-08', '2025-06-12',
                    '2025-06-14', '2025-06-20', '2025-06-25', '2025-06-27',
                    '2025-06-29', '2025-06-30',
                ],
            ],
            '2025-07' => [
                'total' => 48972.00,
                'profile' => 'standard',
                'high_days' => [
                    '2025-07-07' => 2385.00,
                    '2025-07-08' => 2000.00,
                    '2025-07-13' => 3110.00,
                    '2025-07-14' => 2020.00,
                    '2025-07-15' => 2150.00,
                    '2025-07-24' => 2419.00,
                    '2025-07-25' => 2345.00,
                    '2025-07-29' => 2017.00,
                ],
                'regular_days' => [
                    '2025-07-02', '2025-07-04', '2025-07-06', '2025-07-10',
                    '2025-07-12', '2025-07-17', '2025-07-19', '2025-07-21',
                    '2025-07-23', '2025-07-27', '2025-07-30', '2025-07-31',
                ],
            ],
            '2025-08' => [
                'total' => 52410.00,
                'profile' => 'institutional',
                'high_days' => [],
                'regular_days' => [
                    '2025-08-02', '2025-08-05', '2025-08-07', '2025-08-10',
                    '2025-08-12', '2025-08-15', '2025-08-18', '2025-08-20',
                    '2025-08-22', '2025-08-25', '2025-08-28', '2025-08-30',
                ],
            ],
            '2025-09' => [
                'total' => 56767.00,
                'profile' => 'grant',
                'high_days' => [
                    '2025-09-12' => 3377.00,
                    '2025-09-13' => 3590.00,
                    '2025-09-15' => 4755.00,
                    '2025-09-16' => 7065.00,
                    '2025-09-18' => 4005.00,
                    '2025-09-19' => 7710.00,
                    '2025-09-20' => 3040.00,
                    '2025-09-22' => 3000.00,
                    '2025-09-23' => 3005.00,
                    '2025-09-24' => 3000.00,
                    '2025-09-29' => 4430.00,
                    '2025-09-30' => 3435.00,
                ],
                'regular_days' => [
                    '2025-09-03', '2025-09-06', '2025-09-09', '2025-09-11',
                    '2025-09-26', '2025-09-27',
                ],
            ],
            '2025-10' => [
                'total' => 43032.00,
                'profile' => 'grant',
                'high_days' => [
                    '2025-10-02' => 3590.00,
                    '2025-10-07' => 2005.00,
                    '2025-10-08' => 2180.00,
                    '2025-10-09' => 3585.00,
                    '2025-10-10' => 3895.00,
                    '2025-10-11' => 2140.00,
                    '2025-10-15' => 3000.00,
                    '2025-10-17' => 2095.00,
                    '2025-10-21' => 2110.00,
                    '2025-10-23' => 2170.00,
                    '2025-10-24' => 2175.00,
                    '2025-10-30' => 2135.00,
                ],
                'regular_days' => [
                    '2025-10-04', '2025-10-05', '2025-10-13', '2025-10-19',
                    '2025-10-26', '2025-10-28',
                ],
            ],
            '2025-11' => [
                'total' => 41200.00,
                'profile' => 'offpeak',
                'high_days' => [],
                'regular_days' => [
                    '2025-11-03', '2025-11-06', '2025-11-09', '2025-11-12',
                    '2025-11-15', '2025-11-18', '2025-11-21', '2025-11-24',
                    '2025-11-27', '2025-11-29',
                ],
            ],
            '2025-12' => [
                'total' => 58900.00,
                'profile' => 'holiday',
                'high_days' => [],
                'regular_days' => [
                    '2025-12-02', '2025-12-05', '2025-12-08', '2025-12-11',
                    '2025-12-14', '2025-12-17', '2025-12-20', '2025-12-22',
                    '2025-12-23', '2025-12-27', '2025-12-29', '2025-12-30',
                ],
            ],
            '2026-01' => [
                'total' => 38400.00,
                'profile' => 'offpeak',
                'high_days' => [],
                'regular_days' => [
                    '2026-01-04', '2026-01-07', '2026-01-10', '2026-01-13',
                    '2026-01-16', '2026-01-19', '2026-01-22', '2026-01-25',
                    '2026-01-28', '2026-01-30',
                ],
            ],
            '2026-02' => [
                'total' => 42150.00,
                'profile' => 'offpeak',
                'high_days' => [],
                'regular_days' => [
                    '2026-02-03', '2026-02-06', '2026-02-09', '2026-02-12',
                    '2026-02-14', '2026-02-17', '2026-02-20', '2026-02-23',
                    '2026-02-25', '2026-02-27',
                ],
            ],
            '2026-03' => [
                'total' => 54800.00,
                'profile' => 'presummer',
                'high_days' => [],
                'regular_days' => [
                    '2026-03-03', '2026-03-06', '2026-03-09', '2026-03-12',
                    '2026-03-15', '2026-03-18', '2026-03-21', '2026-03-24',
                    '2026-03-27', '2026-03-30',
                ],
            ],
            '2026-04' => [
                'total' => 67300.00,
                'profile' => 'summer',
                'high_days' => [],
                'regular_days' => [
                    '2026-04-02', '2026-04-05', '2026-04-08', '2026-04-11',
                    '2026-04-14', '2026-04-17', '2026-04-20', '2026-04-23',
                    '2026-04-25', '2026-04-27', '2026-04-29', '2026-04-30',
                ],
            ],
            '2026-05' => [
                'total' => 74850.00,
                'profile' => 'summer',
                'high_days' => [],
                'regular_days' => [
                    '2026-05-03', '2026-05-06', '2026-05-09', '2026-05-12',
                    '2026-05-15', '2026-05-18', '2026-05-21', '2026-05-23',
                    '2026-05-25', '2026-05-27', '2026-05-29', '2026-05-31',
                ],
            ],
            '2026-06' => [
                'total' => 71200.00,
                'profile' => 'summer',
                'high_days' => [],
                'regular_days' => [
                    '2026-06-02', '2026-06-05', '2026-06-08', '2026-06-11',
                    '2026-06-14', '2026-06-17', '2026-06-20', '2026-06-22',
                    '2026-06-24', '2026-06-26', '2026-06-28', '2026-06-30',
                ],
            ],
            '2026-07' => [
                'total' => 51300.00,
                'profile' => 'standard',
                'high_days' => [],
                'regular_days' => [
                    '2026-07-03', '2026-07-06', '2026-07-09', '2026-07-12',
                    '2026-07-15', '2026-07-18', '2026-07-21', '2026-07-24',
                    '2026-07-27', '2026-07-30',
                ],
            ],
            '2026-08' => [
                'total' => 54100.00,
                'profile' => 'institutional',
                'high_days' => [],
                'regular_days' => [
                    '2026-08-03', '2026-08-06', '2026-08-09', '2026-08-12',
                    '2026-08-15', '2026-08-18', '2026-08-21', '2026-08-24',
                    '2026-08-27', '2026-08-30',
                ],
            ],
            '2026-09' => [
                'total' => 59200.00,
                'profile' => 'graham_lead',
                'high_days' => [],
                'regular_days' => [
                    '2026-09-03', '2026-09-06', '2026-09-09', '2026-09-12',
                    '2026-09-15', '2026-09-18', '2026-09-21', '2026-09-24',
                    '2026-09-27', '2026-09-30',
                ],
            ],
            '2026-10' => [
                'total' => 46500.00,
                'profile' => 'graham_lead',
                'high_days' => [],
                'regular_days' => [
                    '2026-10-03', '2026-10-06', '2026-10-09', '2026-10-12',
                    '2026-10-15', '2026-10-18', '2026-10-21', '2026-10-24',
                    '2026-10-27', '2026-10-30',
                ],
            ],
        ];

        $seq = 1;

        DB::transaction(function () use ($monthlyPlan, $products, $customerId, $riderId, &$seq) {
            foreach ($monthlyPlan as $ym => $config) {
                $highDays = $config['high_days'];
                $highSum = array_sum($highDays);
                $remainder = round($config['total'] - $highSum, 2);

                $dailyTargets = $highDays;
                $daysInMonth = Carbon::createFromFormat('Y-m', $ym)->daysInMonth;
                $regDays = [];
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dayCandidate = sprintf('%s-%02d', $ym, $d);
                    if (!isset($highDays[$dayCandidate])) {
                        $regDays[] = $dayCandidate;
                    }
                }
                $regCount = count($regDays);

                if ($regCount > 0 && $remainder > 0) {
                    $baseDaily = floor(($remainder / $regCount) * 100) / 100;
                    $running = 0.0;
                    foreach ($regDays as $idx => $dayStr) {
                        if ($idx === $regCount - 1) {
                            $dailyTargets[$dayStr] = round($remainder - $running, 2);
                        } else {
                            $dailyTargets[$dayStr] = $baseDaily;
                            $running = round($running + $baseDaily, 2);
                        }
                    }
                }

                ksort($dailyTargets);

                foreach ($dailyTargets as $dateStr => $targetAmount) {
                    if ($targetAmount <= 0) {
                        continue;
                    }

                    $orderNum = sprintf('DB-HIST-%s-%04d', str_replace('-', '', $dateStr), $seq++);
                    $ts = Carbon::parse($dateStr . ' 14:30:00');

                    $orderId = DB::table('orders')->insertGetId([
                        'order_number' => $orderNum,
                        'customer_id' => $customerId,
                        'rider_id' => $riderId,
                        'order_type' => 'Pickup',
                        'payment_method' => 'COD',
                        'payment_status' => 'Paid',
                        'paid_at' => $ts,
                        'delivery_address' => 'Storefront Pickup',
                        'schedule_date' => $dateStr,
                        'schedule_time' => '14:30:00',
                        'subtotal' => $targetAmount,
                        'delivery_fee' => 0.00,
                        'total_amount' => $targetAmount,
                        'order_status' => 'Delivered',
                        'cancelled_after_arrival' => false,
                        'created_at' => $ts,
                        'updated_at' => $ts,
                    ]);

                    $allocations = $this->allocateDayItems($products, $targetAmount, $config['profile']);

                    foreach ($allocations as $item) {
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
                        'total_amount' => $targetAmount,
                        'sales_date' => $dateStr,
                        'created_at' => $ts,
                        'updated_at' => $ts,
                    ]);
                }
            }
        });

        // Clean up any duplicate Sale rows on existing orders (e.g. Order #14 had 2 identical Sale rows)
        $duplicateOrderSales = DB::table('sales')
            ->select('order_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('order_id')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicateOrderSales as $dup) {
            DB::table('sales')
                ->where('order_id', $dup->order_id)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        // Generate forecasts for the upcoming month
        $targetMonth = Carbon::now()->addMonth()->format('Y-m');
        ForecastService::generateAllProducts($targetMonth);
    }

    /**
     * Allocate a day's exact target revenue across active products according to the seasonal/monthly profile.
     * Ensures Graham Bar (product #14 or matching 'graham') leads sales in recent months so it leads next month's forecast.
     */
    protected function allocateDayItems($products, float $targetAmount, string $profile): array
    {
        // Weight shares by product ID / name according to season profile
        $weights = [];
        foreach ($products as $id => $prod) {
            $nameLower = strtolower($prod->product_name . ' ' . ($prod->category?->category_name ?? ''));
            $isGraham = str_contains($nameLower, 'graham');
            $isCheese = str_contains($nameLower, 'cheese') || str_contains($nameLower, 'pastillas');
            $isIceCream = str_contains($nameLower, 'ice') || str_contains($nameLower, 'cookie');
            $isChocoOrSweet = str_contains($nameLower, 'choco') || str_contains($nameLower, 'strawberry') || str_contains($nameLower, 'mango');

            if ($profile === 'graham_lead') {
                // Graham Bar has the highest sales share in the recent months (Sept & Oct 2026)
                $weights[$id] = $isGraham ? 0.34 : ($isChocoOrSweet ? 0.09 : 0.05);
            } elseif ($profile === 'holiday') {
                // Holiday surge for milk cheese & sweet flavored milk, strong graham
                $weights[$id] = $isCheese ? 0.18 : ($isGraham ? 0.18 : ($isChocoOrSweet ? 0.10 : 0.05));
            } elseif ($profile === 'summer') {
                // Summer peak: strong graham, ice cream, mango & choco milk
                $weights[$id] = $isGraham ? 0.20 : ($isIceCream ? 0.12 : ($isChocoOrSweet ? 0.10 : 0.06));
            } else {
                // Standard / grant / institutional months
                $weights[$id] = $isGraham ? 0.22 : ($isChocoOrSweet ? 0.10 : 0.07);
            }
        }

        $weightSum = array_sum($weights);
        $items = [];
        $allocatedSubtotal = 0.0;
        $productIds = array_keys($weights);
        $lastId = end($productIds);

        foreach ($weights as $id => $w) {
            $prod = $products[$id];
            $unitPrice = max(1.0, (float) $prod->price);

            if ($id === $lastId) {
                $rem = round($targetAmount - $allocatedSubtotal, 2);
                if ($rem > 0) {
                    $qty = max(1, (int) round($rem / $unitPrice));
                    $items[] = [
                        'product_id' => $id,
                        'quantity' => $qty,
                        'price' => $unitPrice,
                        'subtotal' => $rem,
                    ];
                } elseif ($rem < 0 && !empty($items)) {
                    // Adjust previous item if rounding exceeded targetAmount
                    $lastIdx = count($items) - 1;
                    $items[$lastIdx]['subtotal'] = round($items[$lastIdx]['subtotal'] + $rem, 2);
                }
                break;
            }

            $desiredShare = ($w / $weightSum) * $targetAmount;
            $qty = max(1, (int) floor($desiredShare / $unitPrice));
            $sub = round($qty * $unitPrice, 2);

            if ($allocatedSubtotal + $sub >= $targetAmount) {
                continue;
            }

            $items[] = [
                'product_id' => $id,
                'quantity' => $qty,
                'price' => $unitPrice,
                'subtotal' => $sub,
            ];
            $allocatedSubtotal = round($allocatedSubtotal + $sub, 2);
        }

        return $items;
    }
}
