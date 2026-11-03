<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $afterCommit = true;

    public array $order;
    public string $action;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, string $action = 'updated')
    {
        $order->loadMissing(['customer', 'rider', 'delivery']);

        $this->action = $action;
        $this->order = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_id' => $order->customer_id,
            'customer_name' => $order->customer ? $order->customer->name : 'N/A',
            'order_type' => $order->order_type,
            'order_status' => $order->order_status,
            'total_amount' => (float) $order->total_amount,
            'total_formatted' => '₱' . number_format($order->total_amount, 2),
            'schedule_date' => $order->schedule_date ? (is_string($order->schedule_date) ? $order->schedule_date : $order->schedule_date->format('Y-m-d')) : null,
            'schedule_time' => $order->schedule_time,
            'delivery_address' => $order->delivery_address,
            'created_at' => $order->created_at ? $order->created_at->format('M d, Y h:i A') : '',
            'created_diff' => $order->created_at ? $order->created_at->diffForHumans() : '',
            'rider_id' => $order->rider_id,
            'rider_name' => $order->rider ? $order->rider->name : null,
            'delivery_status' => $order->delivery ? $order->delivery->delivery_status : null,
            'cancellation_reason' => $order->cancellation_reason,
            'cancelled_after_arrival' => (bool) $order->cancelled_after_arrival,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.orders'),
            new PrivateChannel('orders.' . $this->order['id']),
            new PrivateChannel('customer.orders.' . $this->order['customer_id']),
        ];

        if (!empty($this->order['rider_id'])) {
            $channels[] = new PrivateChannel('rider.deliveries.' . $this->order['rider_id']);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.updated';
    }
}
