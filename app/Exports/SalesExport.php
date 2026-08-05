<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Sale::with(['order.customer'])
            ->whereBetween('sales_date', [$this->startDate, $this->endDate])
            ->orderBy('sales_date', 'asc')
            ->get();
    }

    /**
    * @var Sale $sale
    */
    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->order->order_number,
            $sale->order->customer->name,
            $sale->order->order_type,
            '₱' . number_format($sale->total_amount, 2),
            $sale->sales_date,
        ];
    }

    public function headings(): array
    {
        return [
            'Sale ID',
            'Order Reference',
            'Customer Name',
            'Fulfillment Mode',
            'Total Amount',
            'Sales Date',
        ];
    }
}
