<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RemittanceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Collection $remittances;

    public function __construct(Collection $remittances)
    {
        $this->remittances = $remittances;
    }

    public function collection(): Collection
    {
        return $this->remittances;
    }

    /**
     * @param \App\Models\RiderRemittance $remittance
     */
    public function map($remittance): array
    {
        $variance = (float) $remittance->variance;
        $varianceStatus = $variance == 0 ? 'Balanced' : ($variance < 0 ? 'Shortage' : 'Overage');

        $orderNumbers = $remittance->orders->pluck('order_number')->implode(', ');

        $productsBreakdown = $remittance->orders->map(function ($order) {
            $itemsList = $order->orderItems->map(function ($item) {
                $pName = $item->product?->product_name ?? 'Delivered Product';
                $size = $item->product?->size ? " ({$item->product->size})" : '';
                return "{$item->quantity}x {$pName}{$size} @ ₱" . number_format($item->price, 2);
            })->implode('; ');

            return "Order #{$order->order_number}: " . ($itemsList ?: 'No items');
        })->implode(' | ');

        $totalSalesValue = (float) $remittance->orders->sum('total_amount');

        return [
            $remittance->reference_no,
            $remittance->created_at ? $remittance->created_at->format('Y-m-d h:i A') : 'N/A',
            $remittance->rider?->name ?? 'Unknown Rider',
            $remittance->rider?->phone ?? 'N/A',
            $remittance->orders_count,
            $orderNumbers ?: 'N/A',
            $productsBreakdown ?: 'N/A',
            number_format($totalSalesValue, 2, '.', ''),
            number_format($remittance->expected_amount, 2, '.', ''),
            number_format($remittance->actual_amount, 2, '.', ''),
            number_format($remittance->variance, 2, '.', ''),
            $varianceStatus,
            $remittance->payment_method,
            ucwords(str_replace('_', ' ', $remittance->status)),
            $remittance->admin?->name ?? 'Admin',
            $remittance->notes ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Reference No',
            'Handover Date & Time',
            'Delivery Rider',
            'Rider Phone',
            'Orders Attached Count',
            'Order Numbers / Batches',
            'Delivered Products Breakdown (Name, Size, Qty, Unit Price)',
            'Total Order Sales Value (PHP)',
            'Expected COD (PHP)',
            'Actual Remitted (PHP)',
            'Variance (PHP)',
            'Variance Status',
            'Payment Method',
            'Remittance Status',
            'Recorded By',
            'Remarks / Notes',
        ];
    }
}

