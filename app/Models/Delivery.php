<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'order_id',
        'rider_id',
        'delivery_status',
        'arrived_at',
        'cancelled_after_arrival',
        'cancellation_reason',
        'admin_verified_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'arrived_at' => 'datetime',
            'delivered_at' => 'datetime',
            'admin_verified_at' => 'datetime',
            'cancelled_after_arrival' => 'boolean',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (Delivery $delivery) {
            try {
                broadcast(new \App\Events\DeliveryUpdated($delivery, 'created'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Delivery broadcast failed: ' . $e->getMessage());
            }
        });

        static::updated(function (Delivery $delivery) {
            try {
                broadcast(new \App\Events\DeliveryUpdated($delivery, 'updated'));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Delivery broadcast failed: ' . $e->getMessage());
            }
        });
    }

    /**
     * Check if the rider has arrived at the customer location.
     */
    public function hasArrived(): bool
    {
        return !is_null($this->arrived_at);
    }

    /**
     * Accessor for arrived_at ensuring Philippine Time (Asia/Manila, UTC+8).
     */
    public function getArrivedAtAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return \Carbon\Carbon::parse($value, 'Asia/Manila')->timezone('Asia/Manila');
    }

    /**
     * Mutator for arrived_at ensuring Philippine Time (Asia/Manila, UTC+8).
     */
    public function setArrivedAtAttribute($value)
    {
        if (!$value) {
            $this->attributes['arrived_at'] = null;
            return;
        }

        $this->attributes['arrived_at'] = $value instanceof \Carbon\Carbon
            ? $value->copy()->timezone('Asia/Manila')->format('Y-m-d H:i:s')
            : \Carbon\Carbon::parse($value, 'Asia/Manila')->timezone('Asia/Manila')->format('Y-m-d H:i:s');
    }

    /**
     * Accessor for delivered_at ensuring Philippine Time (Asia/Manila, UTC+8).
     */
    public function getDeliveredAtAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return \Carbon\Carbon::parse($value, 'Asia/Manila')->timezone('Asia/Manila');
    }

    /**
     * Mutator for delivered_at ensuring Philippine Time (Asia/Manila, UTC+8).
     */
    public function setDeliveredAtAttribute($value)
    {
        if (!$value) {
            $this->attributes['delivered_at'] = null;
            return;
        }

        $this->attributes['delivered_at'] = $value instanceof \Carbon\Carbon
            ? $value->copy()->timezone('Asia/Manila')->format('Y-m-d H:i:s')
            : \Carbon\Carbon::parse($value, 'Asia/Manila')->timezone('Asia/Manila')->format('Y-m-d H:i:s');
    }

    /**
     * Check if the order was cancelled after the rider arrived.
     */
    public function wasCancelledAfterArrival(): bool
    {
        return (bool) $this->cancelled_after_arrival;
    }

    /**
     * Get the order for this delivery.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the rider for this delivery.
     */
    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }
}
