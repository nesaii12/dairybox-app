<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.4;
        }
        .header {
            margin-bottom: 25px;
            border-bottom: 2px solid #333333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0;
            color: #0c4a6e;
        }
        .header p {
            margin: 3px 0 0 0;
            color: #666666;
            font-size: 10px;
        }
        .meta-info {
            width: 100%;
            margin-bottom: 15px;
        }
        .meta-info td {
            vertical-align: top;
        }
        .meta-info .label {
            font-weight: bold;
            color: #555555;
            width: 80px;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .report-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px;
            font-weight: bold;
            text-align: left;
            color: #1e293b;
        }
        .report-table td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            vertical-align: middle;
        }
        .report-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            text-align: center;
            font-size: 9px;
            color: #999999;
            border-top: 1px solid #dddddd;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>DairyBox Inventory Transaction Logs</h1>
        <p>Generated automatically on {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <table class="meta-info">
        <tr>
            <td class="label">Date Range:</td>
            <td>{{ \Carbon\Carbon::parse($start)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($end)->format('M d, Y') }}</td>
            <td class="label" style="text-align: right;">Issuer:</td>
            <td style="text-align: right;">System Administrator</td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 40px;">Log ID</th>
                <th>Product Details</th>
                <th style="width: 70px;">Type</th>
                <th style="width: 50px; text-align: right;">Qty</th>
                <th style="width: 70px; text-align: right;">Rem Stock</th>
                <th>Expiration</th>
                <th>Remarks / Reasons</th>
                <th style="width: 95px;">Timestamp</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            @php
                $type = $log->stock_in > 0 ? 'Stock In' : 'Stock Out';
                $qty = $log->stock_in > 0 ? $log->stock_in : $log->stock_out;
            @endphp
            <tr>
                <td>#{{ $log->id }}</td>
                <td><strong>{{ $log->product->product_name }}</strong> ({{ $log->product->size }})</td>
                <td style="color: {{ $log->stock_in > 0 ? '#10b981' : '#f43f5e' }}; font-weight: bold;">{{ $type }}</td>
                <td style="text-align: right; font-weight: bold;">{{ $qty }}</td>
                <td style="text-align: right;">{{ $log->remaining_stock }}</td>
                <td>{{ $log->expiration_date ? \Carbon\Carbon::parse($log->expiration_date)->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ $log->remarks }}</td>
                <td style="font-size: 10px; color: #666666;">{{ $log->created_at->format('Y-m-d h:i A') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; color: #999999; padding: 20px;">No inventory logs recorded for this date range.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Confidential Document &bull; DairyBox Sales and Inventory Management System
    </div>

</body>
</html>
