<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
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
        .totals-block {
            float: right;
            width: 200px;
            margin-top: 10px;
            border-top: 2px solid #333333;
            padding-top: 8px;
        }
        .totals-block table {
            width: 100%;
        }
        .totals-block td {
            padding: 3px 0;
        }
        .totals-block .total-val {
            font-size: 14px;
            font-weight: bold;
            text-align: right;
            color: #0c4a6e;
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
        <h1>DairyBox Revenue & Sales Report</h1>
        <p>Generated automatically on {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <table class="meta-info">
        <tr>
            <td class="label">Filter Range:</td>
            <td>{{ \Carbon\Carbon::parse($start)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($end)->format('M d, Y') }}</td>
            <td class="label" style="text-align: right;">Issuer:</td>
            <td style="text-align: right;">System Administrator</td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 50px;">Sale ID</th>
                <th>Order Number</th>
                <th>Customer Name</th>
                <th>Fulfillment</th>
                <th>Sales Date</th>
                <th style="text-align: right; width: 100px;">Total Revenue</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
            <tr>
                <td>#{{ $sale->id }}</td>
                <td><strong>{{ $sale->order->order_number }}</strong></td>
                <td>{{ $sale->order->customer->name }}</td>
                <td>{{ $sale->order->order_type }}</td>
                <td>{{ \Carbon\Carbon::parse($sale->sales_date)->format('M d, Y') }}</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($sale->total_amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #999999; padding: 20px;">No sales logged for this date range.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-block">
        <table>
            <tr>
                <td style="font-weight: bold;">Grand Total Sales:</td>
                <td class="total-val">{{ number_format($totalSales, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Confidential Document &bull; DairyBox Sales and Inventory Management System
    </div>

</body>
</html>
