<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'ledger_date',
        'admin_id',
        'rider_id',
        'rider_name',
        'remittance_id',
        'entry_type',
        'amount_expected',
        'amount_remitted',
        'variance',
        'payment_method',
        'notes',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'ledger_date' => 'date',
            'recorded_at' => 'datetime',
            'amount_expected' => 'decimal:2',
            'amount_remitted' => 'decimal:2',
            'variance' => 'decimal:2',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(RiderRemittance::class, 'remittance_id');
    }
}

