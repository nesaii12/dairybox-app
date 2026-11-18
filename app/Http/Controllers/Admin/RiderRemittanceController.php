<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiderRemittance;
use App\Models\CashLedgerEntry;
use App\Models\Order;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\AppNotification;
use App\Exports\RemittanceExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RiderRemittanceController extends Controller
{
    /**
     * Display the Rider Cash Remittance workspace, input form, history, and ledger.
     */
    public function index(Request $request)
    {
        // 1. Fetch active riders with their pending unremitted order counts and cash on hand
        $activeRiders = User::where('role', 'rider')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $activeRiders->each(function ($rider) {
            $pendingOrders = Order::where('rider_id', $rider->id)
                ->where('order_type', 'Delivery')
                ->whereRaw("LOWER(payment_method) = 'cod'")
                ->whereNull('remittance_id')
                ->whereIn('order_status', ['Delivered', 'Delivered - Pending Remittance'])
                ->get();

            $rider->pending_orders_count = $pendingOrders->count();
            $rider->pending_cod_total = (float) $pendingOrders->sum('total_amount');
        });

        // 2. High-Level Metrics
        $today = Carbon::today()->toDateString();

        $totalRemittedToday = (float) CashLedgerEntry::whereDate('ledger_date', $today)->sum('amount_remitted');
        $pendingVerificationsCount = RiderRemittance::where('status', 'pending_verification')->count();
        $todayNetVariance = (float) CashLedgerEntry::whereDate('ledger_date', $today)->sum('variance');

        $totalUnremittedCash = (float) Order::where('order_type', 'Delivery')
            ->whereRaw("LOWER(payment_method) = 'cod'")
            ->whereNull('remittance_id')
            ->whereIn('order_status', ['Delivered', 'Delivered - Pending Remittance'])
            ->sum('total_amount');

        // 3. Remittance History with timeframe and multi-criteria filtering
        [$historyQuery, $dates] = $this->buildRemittanceQuery($request);
        $remittances = $historyQuery->paginate(10)->withQueryString();
        $dateRangeLabel = $dates['label'];
        $filterType = $dates['filter_type'];

        // 4. Daily Cash Ledger with filtering
        $ledgerQuery = CashLedgerEntry::with(['admin', 'rider', 'remittance'])
            ->latest('recorded_at');

        if ($request->filled('ledger_date')) {
            $ledgerQuery->whereDate('ledger_date', $request->ledger_date);
        }

        if ($request->filled('ledger_rider_id')) {
            $ledgerQuery->where('rider_id', $request->ledger_rider_id);
        }

        $ledgerEntries = $ledgerQuery->paginate(15, ['*'], 'ledger_page')->withQueryString();

        $selectedRiderId = $request->query('rider_id');

        return view('admin.remittances.index', compact(
            'activeRiders',
            'remittances',
            'ledgerEntries',
            'totalRemittedToday',
            'pendingVerificationsCount',
            'todayNetVariance',
            'totalUnremittedCash',
            'selectedRiderId',
            'dateRangeLabel',
            'filterType'
        ));
    }

    /**
     * Parse date range inputs supporting presets and custom windows.
     */
    public function parseDateRange(Request $request): array
    {
        $filterType = $request->input('filter_type', 'monthly');

        switch ($filterType) {
            case 'daily':
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                $label = 'Daily Logs (' . Carbon::today()->format('M d, Y') . ')';
                break;
            case 'weekly':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                $label = 'Weekly Logs (' . $start->format('M d') . ' - ' . $end->format('M d, Y') . ')';
                break;
            case 'annual':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                $label = 'Annual Logs (' . $start->format('Y') . ')';
                break;
            case 'all':
                $start = Carbon::parse('2025-01-01')->startOfDay();
                $end = Carbon::now()->endOfDay();
                $label = 'All Logs';
                break;
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $start = Carbon::parse($request->input('start_date'))->startOfDay();
                    $end = Carbon::parse($request->input('end_date'))->endOfDay();
                    $label = $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
                } else {
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now()->endOfMonth();
                    $label = 'Monthly Logs (' . Carbon::now()->format('F Y') . ')';
                }
                break;
            case 'monthly':
            default:
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                $label = 'Monthly Logs (' . Carbon::now()->format('F Y') . ')';
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => $label,
            'filter_type' => $filterType,
        ];
    }

    /**
     * Build unified remittance query matching active filters.
     */
    protected function buildRemittanceQuery(Request $request): array
    {
        $dates = $this->parseDateRange($request);
        $start = $dates['start'];
        $end = $dates['end'];

        $historyQuery = RiderRemittance::with([
            'rider',
            'admin',
            'orders.customer',
            'orders.orderItems.product'
        ])->latest('id');

        if ($request->filled('date')) {
            $historyQuery->whereDate('created_at', $request->date);
        } elseif ($dates['filter_type'] !== 'all') {
            $historyQuery->whereBetween('created_at', [$start, $end]);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $historyQuery->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('rider', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('admin', function ($aq) use ($search) {
                      $aq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('rider_id')) {
            $historyQuery->where('rider_id', $request->rider_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $historyQuery->where('status', $request->status);
        }

        return [$historyQuery, $dates];
    }

    /**
     * Download filtered remittance transaction records as Excel spreadsheet.
     */
    public function exportExcel(Request $request)
    {
        [$query, $dates] = $this->buildRemittanceQuery($request);
        $remittances = $query->get();

        $startStr = $dates['start']->format('Ymd');
        $endStr = $dates['end']->format('Ymd');
        $filename = "remittance_report_{$startStr}_to_{$endStr}.xlsx";

        return Excel::download(new RemittanceExport($remittances), $filename);
    }

    /**
     * Download filtered remittance transaction records as PDF summary.
     */
    public function exportPdf(Request $request)
    {
        [$query, $dates] = $this->buildRemittanceQuery($request);
        $remittances = $query->get();

        $start = $dates['start'];
        $end = $dates['end'];
        $dateRangeLabel = $dates['label'];

        $totalExpected = (float) $remittances->sum('expected_amount');
        $totalActual = (float) $remittances->sum('actual_amount');
        $totalVariance = (float) $remittances->sum('variance');
        $totalOrders = (int) $remittances->sum('orders_count');

        $activeRiderName = null;
        if ($request->filled('rider_id')) {
            $activeRiderName = User::find($request->rider_id)?->name;
        }

        $activeStatus = $request->input('status', 'all');

        $pdf = Pdf::loadView('admin.remittances.remittance_pdf', compact(
            'remittances',
            'start',
            'end',
            'dateRangeLabel',
            'totalExpected',
            'totalActual',
            'totalVariance',
            'totalOrders',
            'activeRiderName',
            'activeStatus'
        ))->setPaper('a4', 'landscape');

        $startStr = $dates['start']->format('Ymd');
        $endStr = $dates['end']->format('Ymd');
        $filename = "remittance_report_{$startStr}_to_{$endStr}.pdf";

        return $pdf->download($filename);
    }

    /**
     * JSON endpoint to fetch completed delivery orders awaiting remittance for a specific rider.
     */
    public function getRiderOrders(User $rider)
    {
        $orders = Order::with('customer')
            ->where('rider_id', $rider->id)
            ->where('order_type', 'Delivery')
            ->whereRaw("LOWER(payment_method) = 'cod'")
            ->whereNull('remittance_id')
            ->whereIn('order_status', ['Delivered', 'Delivered - Pending Remittance'])
            ->orderBy('schedule_date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer?->name ?? 'Guest Customer',
                    'customer_phone' => $order->customer?->phone ?? 'N/A',
                    'delivery_address' => $order->delivery_address ?? 'Store Area',
                    'schedule_date' => $order->schedule_date ? $order->schedule_date->format('M d, Y') : 'N/A',
                    'schedule_time' => $order->schedule_time ?? 'Standard Run',
                    'total_amount' => (float) $order->total_amount,
                    'order_status' => $order->order_status,
                    'batch_id' => 'BATCH-' . ($order->schedule_date ? $order->schedule_date->format('Ymd') : date('Ymd')),
                ];
            });

        return response()->json([
            'success' => true,
            'rider' => [
                'id' => $rider->id,
                'name' => $rider->name,
                'email' => $rider->email,
                'phone' => $rider->phone,
            ],
            'orders' => $orders,
            'total_pending' => (float) $orders->sum('total_amount'),
            'orders_count' => $orders->count(),
        ]);
    }

    /**
     * Store and process a new rider cash remittance.
     */
    public function store(Request $request)
    {
        $request->validate([
            'rider_id' => 'required|exists:users,id',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|exists:orders,id',
            'actual_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:Cash,GCash Transfer,Bank Transfer,Other',
            'notes' => 'nullable|string|max:1000',
            'action_type' => 'required|in:confirm,pending',
        ]);

        $rider = User::findOrFail($request->rider_id);

        // Fetch selected orders and verify integrity
        $orders = Order::whereIn('id', $request->order_ids)
            ->where('rider_id', $rider->id)
            ->whereNull('remittance_id')
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No valid unremitted orders found for this rider run.');
        }

        $expectedAmount = (float) $orders->sum('total_amount');
        $actualAmount = (float) $request->actual_amount;
        $variance = round($actualAmount - $expectedAmount, 2);
        $isConfirm = $request->action_type === 'confirm';
        $status = $isConfirm ? 'confirmed' : 'pending_verification';

        try {
            DB::beginTransaction();

            $referenceNo = RiderRemittance::generateReferenceNo();

            // 1. Create RiderRemittance record
            $remittance = RiderRemittance::create([
                'reference_no' => $referenceNo,
                'rider_id' => $rider->id,
                'admin_id' => auth()->id(),
                'orders_count' => $orders->count(),
                'expected_amount' => $expectedAmount,
                'actual_amount' => $actualAmount,
                'variance' => $variance,
                'status' => $status,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'verified_at' => $isConfirm ? now() : null,
            ]);

            // 2. Attach orders and update order statuses
            foreach ($orders as $order) {
                // Attach to pivot
                $remittance->orders()->attach($order->id, [
                    'amount' => $order->total_amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($isConfirm) {
                    $order->update([
                        'remittance_id' => $remittance->id,
                        'order_status' => 'Paid & Remitted',
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);
                } else {
                    $order->update([
                        'remittance_id' => $remittance->id,
                        'order_status' => 'Delivered - Pending Remittance',
                    ]);
                }
            }

            // 3. Ledger & Audit Integration (if confirmed)
            if ($isConfirm) {
                // Save entry into daily cash ledger
                CashLedgerEntry::create([
                    'ledger_date' => now()->toDateString(),
                    'admin_id' => auth()->id(),
                    'rider_id' => $rider->id,
                    'rider_name' => $rider->name,
                    'remittance_id' => $remittance->id,
                    'entry_type' => 'RIDER_REMITTANCE',
                    'amount_expected' => $expectedAmount,
                    'amount_remitted' => $actualAmount,
                    'variance' => $variance,
                    'payment_method' => $request->payment_method,
                    'notes' => $request->notes,
                    'recorded_at' => now(),
                ]);

                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'RIDER_REMITTANCE_CONFIRMED',
                    'details' => "Confirmed remittance {$referenceNo} for rider {$rider->name}. Orders: {$orders->count()}, Remitted: ₱" . number_format($actualAmount, 2) . ", Expected: ₱" . number_format($expectedAmount, 2) . ", Variance: ₱" . number_format($variance, 2),
                    'ip_address' => $request->ip(),
                ]);

                // Notify Rider
                AppNotification::create([
                    'user_id' => $rider->id,
                    'title' => 'Remittance Confirmed',
                    'message' => "Your cash remittance of ₱" . number_format($actualAmount, 2) . " (Ref: {$referenceNo}) for {$orders->count()} orders has been confirmed.",
                    'link' => route('rider.history'),
                    'is_read' => false,
                ]);
            } else {
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'RIDER_REMITTANCE_PENDING',
                    'details' => "Saved remittance {$referenceNo} as Pending Verification for rider {$rider->name}. Remitted: ₱" . number_format($actualAmount, 2) . ", Expected: ₱" . number_format($expectedAmount, 2),
                    'ip_address' => $request->ip(),
                ]);
            }

            DB::commit();

            $msg = $isConfirm
                ? "Remittance {$referenceNo} confirmed and logged to Daily Cash Ledger. All {$orders->count()} orders marked as Paid & Remitted."
                : "Remittance {$referenceNo} recorded as Pending Verification.";

            return redirect()->route('admin.remittances.index')
                ->with('success', $msg);

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to record remittance: ' . $e->getMessage());
        }
    }

    /**
     * Show details of a remittance record (JSON endpoint for modal).
     */
    public function show(RiderRemittance $remittance)
    {
        $remittance->load(['rider', 'admin', 'orders.customer', 'orders.orderItems.product', 'ledgerEntry']);

        return response()->json([
            'success' => true,
            'remittance' => [
                'id' => $remittance->id,
                'reference_no' => $remittance->reference_no,
                'rider_name' => $remittance->rider?->name ?? 'Unknown Rider',
                'rider_phone' => $remittance->rider?->phone ?? 'N/A',
                'admin_name' => $remittance->admin?->name ?? 'Admin',
                'status' => $remittance->status,
                'orders_count' => $remittance->orders_count,
                'total_order_sales_value' => (float) $remittance->orders->sum('total_amount'),
                'expected_amount' => (float) $remittance->expected_amount,
                'actual_amount' => (float) $remittance->actual_amount,
                'variance' => (float) $remittance->variance,
                'payment_method' => $remittance->payment_method,
                'notes' => $remittance->notes,
                'created_at' => $remittance->created_at->format('M d, Y h:i A'),
                'verified_at' => $remittance->verified_at ? $remittance->verified_at->format('M d, Y h:i A') : null,
                'orders' => $remittance->orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer?->name ?? 'Guest Customer',
                        'delivery_address' => $order->delivery_address ?? 'N/A',
                        'total_sales_value' => (float) $order->total_amount,
                        'subtotal' => (float) $order->subtotal,
                        'delivery_fee' => (float) $order->delivery_fee,
                        'amount' => (float) ($order->pivot->amount ?? $order->total_amount),
                        'order_status' => $order->order_status,
                        'items' => $order->orderItems->map(function ($item) {
                            return [
                                'product_name' => $item->product?->product_name ?? 'Product',
                                'size' => $item->product?->size ?? 'Standard',
                                'milk_type' => $item->product?->milk_type ?? '',
                                'quantity' => (int) $item->quantity,
                                'unit_price' => (float) $item->price,
                                'subtotal' => (float) $item->subtotal,
                            ];
                        }),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Verify and confirm a pending remittance.
     */
    public function verify(Request $request, RiderRemittance $remittance)
    {
        if ($remittance->status === 'confirmed') {
            return redirect()->back()->with('error', 'This remittance is already confirmed.');
        }

        try {
            DB::beginTransaction();

            $remittance->update([
                'status' => 'confirmed',
                'verified_at' => now(),
                'admin_id' => auth()->id(),
            ]);

            // Update attached orders to Paid & Remitted
            foreach ($remittance->orders as $order) {
                $order->update([
                    'order_status' => 'Paid & Remitted',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            // Save to daily cash ledger if not already recorded
            CashLedgerEntry::updateOrCreate(
                ['remittance_id' => $remittance->id],
                [
                    'ledger_date' => now()->toDateString(),
                    'admin_id' => auth()->id(),
                    'rider_id' => $remittance->rider_id,
                    'rider_name' => $remittance->rider?->name ?? 'Rider',
                    'entry_type' => 'RIDER_REMITTANCE',
                    'amount_expected' => $remittance->expected_amount,
                    'amount_remitted' => $remittance->actual_amount,
                    'variance' => $remittance->variance,
                    'payment_method' => $remittance->payment_method,
                    'notes' => $remittance->notes,
                    'recorded_at' => now(),
                ]
            );

            // Audit Log
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'RIDER_REMITTANCE_VERIFIED',
                'details' => "Verified and confirmed pending remittance {$remittance->reference_no} for rider {$remittance->rider?->name}. Amount: ₱" . number_format($remittance->actual_amount, 2),
                'ip_address' => $request->ip(),
            ]);

            // Notify Rider
            if ($remittance->rider_id) {
                AppNotification::create([
                    'user_id' => $remittance->rider_id,
                    'title' => 'Remittance Verified',
                    'message' => "Your pending remittance {$remittance->reference_no} has been verified and confirmed by admin.",
                    'link' => route('rider.history'),
                    'is_read' => false,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', "Remittance {$remittance->reference_no} has been confirmed and logged to the daily cash ledger.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Reject or void a pending remittance, releasing its orders back to unremitted pool.
     */
    public function reject(Request $request, RiderRemittance $remittance)
    {
        if ($remittance->status === 'confirmed') {
            return redirect()->back()->with('error', 'Cannot reject an already confirmed remittance.');
        }

        try {
            DB::beginTransaction();

            $remittance->update([
                'status' => 'rejected',
                'notes' => trim(($remittance->notes ?? '') . "\n[Rejected by Admin " . auth()->user()->name . " on " . now()->format('Y-m-d H:i') . "] Reason: " . $request->input('reason', 'Administrative Void')),
            ]);

            // Release orders back
            foreach ($remittance->orders as $order) {
                $order->update([
                    'remittance_id' => null,
                    'order_status' => 'Delivered - Pending Remittance',
                ]);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'RIDER_REMITTANCE_REJECTED',
                'details' => "Rejected pending remittance {$remittance->reference_no} for rider {$remittance->rider?->name}. Orders released back to pending remittance pool.",
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', "Remittance {$remittance->reference_no} marked as rejected. Orders have been released back to pending remittance.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Action failed: ' . $e->getMessage());
        }
    }
}

