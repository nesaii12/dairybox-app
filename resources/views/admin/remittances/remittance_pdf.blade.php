<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rider Cash Remittance Report</title>
    <style>
        @page {
            margin: 20px 25px 25px 25px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header {
            margin-bottom: 16px;
            border-bottom: 2px solid #0284c7;
            padding-bottom: 8px;
        }
        .header table {
            width: 100%;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            color: #0369a1;
            font-weight: bold;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 2px 0 0 0;
            color: #64748b;
            font-size: 9px;
        }
        .meta-card {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 14px;
            padding: 8px 12px;
        }
        .meta-card table {
            width: 100%;
        }
        .meta-card td {
            font-size: 9.5px;
            padding: 2px 0;
        }
        .meta-card .label {
            font-weight: bold;
            color: #475569;
            width: 100px;
        }
        .summary-grid {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 6px;
        }
        .summary-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .summary-box.sky {
            background-color: #f0f9ff;
            border-color: #bae6fd;
        }
        .summary-box.rose {
            background-color: #fff1f2;
            border-color: #fecdd3;
        }
        .summary-box.amber {
            background-color: #fffbeb;
            border-color: #fde68a;
        }
        .summary-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.5px;
        }
        .summary-val {
            font-size: 13px;
            font-weight: bold;
            margin-top: 2px;
            color: #0f172a;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .report-table th {
            background-color: #0f172a;
            color: #ffffff;
            border: 1px solid #0f172a;
            padding: 6px 7px;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .report-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 7px;
            font-size: 9px;
            vertical-align: middle;
        }
        .report-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-confirmed {
            background-color: #dcfce7;
            color: #15803d;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #b45309;
        }
        .status-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .variance-balanced {
            color: #16a34a;
            font-weight: bold;
        }
        .variance-shortage {
            color: #dc2626;
            font-weight: bold;
        }
        .variance-overage {
            color: #0284c7;
            font-weight: bold;
        }
        .totals-row td {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #64748b;
        }
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
        .signatures {
            width: 100%;
            margin-top: 20px;
        }
        .signature-box {
            width: 30%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            border-bottom: 1px solid #64748b;
            margin-top: 35px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>DairyBox Backoffice &bull; Rider Cash Remittance Report</h1>
                    <p>Cash on Delivery (COD) Fleet Reconciliations, Physical Handovers &amp; Variance Audit Log</p>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <span style="font-size: 9px; color: #64748b;">Report Generated: {{ now()->format('M d, Y • h:i A') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Meta Information -->
    <div class="meta-card">
        <table>
            <tr>
                <td class="label">Timeframe Window:</td>
                <td><strong>{{ $dateRangeLabel }}</strong></td>
                <td class="label" style="text-align: right;">Rider Scope:</td>
                <td style="text-align: right;"><strong>{{ $activeRiderName ?? 'All Active Fleet Riders' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Date Coverage:</td>
                <td>{{ \Carbon\Carbon::parse($start)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($end)->format('M d, Y') }}</td>
                <td class="label" style="text-align: right;">Remittance Status:</td>
                <td style="text-align: right;"><strong>{{ ucwords(str_replace('_', ' ', $activeStatus)) }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Summary KPI Cards -->
    <table class="summary-grid">
        <tr>
            <td class="summary-box sky" style="width: 20%;">
                <div class="summary-title">Total Remittances</div>
                <div class="summary-val">{{ $remittances->count() }} records</div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="summary-title">Orders Handled</div>
                <div class="summary-val">{{ $totalOrders }} orders</div>
            </td>
            <td class="summary-box sky" style="width: 20%;">
                <div class="summary-title">Expected Cash (COD)</div>
                <div class="summary-val">PHP {{ number_format($totalExpected, 2) }}</div>
            </td>
            <td class="summary-box" style="width: 20%;">
                <div class="summary-title">Actual Cash Remitted</div>
                <div class="summary-val">PHP {{ number_format($totalActual, 2) }}</div>
            </td>
            <td class="summary-box {{ $totalVariance < 0 ? 'rose' : ($totalVariance > 0 ? 'sky' : '') }}" style="width: 20%;">
                <div class="summary-title">Net Variance</div>
                <div class="summary-val" style="color: {{ $totalVariance < 0 ? '#dc2626' : ($totalVariance > 0 ? '#0284c7' : '#16a34a') }};">
                    {{ $totalVariance > 0 ? '+' : '' }}PHP {{ number_format($totalVariance, 2) }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Main Data Table -->
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 90px;">Reference No</th>
                <th style="width: 100px;">Date &amp; Time</th>
                <th>Delivery Rider</th>
                <th style="text-align: center; width: 45px;">Orders</th>
                <th style="text-align: right; width: 80px;">Expected</th>
                <th style="text-align: right; width: 80px;">Remitted</th>
                <th style="text-align: right; width: 75px;">Variance</th>
                <th style="width: 65px;">Method</th>
                <th style="text-align: center; width: 75px;">Status</th>
                <th style="width: 75px;">Recorded By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($remittances as $remittance)
            <tr>
                <td style="font-weight: bold; font-family: monospace;">{{ $remittance->reference_no }}</td>
                <td>{{ $remittance->created_at ? $remittance->created_at->format('M d, Y h:i A') : 'N/A' }}</td>
                <td>
                    <strong>{{ $remittance->rider?->name ?? 'Unknown' }}</strong>
                    @if($remittance->rider?->phone)
                        <br><span style="color: #64748b; font-size: 8px;">{{ $remittance->rider->phone }}</span>
                    @endif
                </td>
                <td style="text-align: center;">{{ $remittance->orders_count }}</td>
                <td style="text-align: right; font-weight: bold; color: #475569;">
                    PHP {{ number_format($remittance->expected_amount, 2) }}
                </td>
                <td style="text-align: right; font-weight: bold; color: #0f172a;">
                    PHP {{ number_format($remittance->actual_amount, 2) }}
                </td>
                <td style="text-align: right;">
                    @if($remittance->variance == 0)
                        <span class="variance-balanced">Balanced</span>
                    @elseif($remittance->variance < 0)
                        <span class="variance-shortage">-PHP {{ number_format(abs($remittance->variance), 2) }}</span>
                    @else
                        <span class="variance-overage">+PHP {{ number_format($remittance->variance, 2) }}</span>
                    @endif
                </td>
                <td>{{ $remittance->payment_method }}</td>
                <td style="text-align: center;">
                    @if($remittance->status === 'confirmed')
                        <span class="status-badge status-confirmed">Confirmed</span>
                    @elseif($remittance->status === 'pending_verification')
                        <span class="status-badge status-pending">Pending</span>
                    @else
                        <span class="status-badge status-rejected">{{ ucfirst($remittance->status) }}</span>
                    @endif
                </td>
                <td>{{ $remittance->admin?->name ?? 'Admin' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center; color: #94a3b8; padding: 20px;">
                    No remittance handover records found matching the specified timeframe and filter criteria.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($remittances->isNotEmpty())
        <tfoot>
            <tr class="totals-row">
                <td colspan="3" style="text-align: right;">Total Summary ({{ $remittances->count() }} Remittances):</td>
                <td style="text-align: center;">{{ $totalOrders }}</td>
                <td style="text-align: right;">PHP {{ number_format($totalExpected, 2) }}</td>
                <td style="text-align: right;">PHP {{ number_format($totalActual, 2) }}</td>
                <td style="text-align: right; color: {{ $totalVariance < 0 ? '#dc2626' : ($totalVariance > 0 ? '#0284c7' : '#16a34a') }};">
                    {{ $totalVariance > 0 ? '+' : '' }}PHP {{ number_format($totalVariance, 2) }}
                </td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- Audit Signatures -->
    <table class="signatures">
        <tr>
            <td class="signature-box">
                <div class="signature-line"></div>
                <strong style="font-size: 8.5px;">{{ auth()->user()->name ?? 'Cashier / Admin' }}</strong>
                <div style="font-size: 7.5px; color: #64748b;">Report Prepared By</div>
            </td>
            <td style="width: 5%;"></td>
            <td class="signature-box">
                <div class="signature-line"></div>
                <strong style="font-size: 8.5px;">Fleet Dispatch Manager</strong>
                <div style="font-size: 7.5px; color: #64748b;">Reconciliation Verified By</div>
            </td>
            <td style="width: 5%;"></td>
            <td class="signature-box">
                <div class="signature-line"></div>
                <strong style="font-size: 8.5px;">Internal Audit &amp; Finance</strong>
                <div style="font-size: 7.5px; color: #64748b;">Ledger Sign-off</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Confidential Document &bull; DairyBox Backoffice Operations &bull; Rider Cash Remittance System
    </div>

</body>
</html>

