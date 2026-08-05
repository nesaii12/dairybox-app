<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryExport implements FromCollection, WithHeadings, WithMapping
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
        return Inventory::with('product')
            ->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
    * @var Inventory $inventory
    */
    public function map($inventory): array
    {
        $type = $inventory->stock_in > 0 ? 'Stock In' : 'Stock Out';
        $qty = $inventory->stock_in > 0 ? $inventory->stock_in : $inventory->stock_out;
        
        return [
            $inventory->id,
            $inventory->product->product_name . ' (' . $inventory->product->size . ')',
            $type,
            $qty,
            $inventory->remaining_stock,
            $inventory->expiration_date ?? 'N/A',
            $inventory->remarks,
            $inventory->created_at->format('Y-m-d h:i A'),
        ];
    }

    public function headings(): array
    {
        return [
            'Log ID',
            'Product Name',
            'Transaction Type',
            'Quantity',
            'Remaining Stock',
            'Batch Expiration Date',
            'Remarks / Reason',
            'Logged Timestamp',
        ];
    }
}
