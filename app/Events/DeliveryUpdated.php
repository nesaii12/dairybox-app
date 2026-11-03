<?php

namespace App\Events;

use App\Models\Delivery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $afterCommit = true;

    public array $delivery;
    public string $action;

    /**
     * Create a new event instance.
     */
    public function __construct(Delivery $delivery, string $action = 'updated')
    {
        $delivery->loadMissing(['order.customer', 'rider']);

        $this->action = $action;
        $order = $delivery->order;

        $this->delivery = [
            'id' => $delivery->id,
            'order_id' => $delivery->order_id,
            'order_number' => $order ? $order->order_number : null,
            'customer_name' => ($order && $order->customer) ? $order->customer->name : 'N/A',
            'delivery_address' => $order ? $order->delivery_address : 'N/A',
            'rider_id' => $delivery->rider_id,
            'rider_name' => $delivery->rider ? $delivery->rider->name : null,
            'rider_phone' => $delivery->rider ? $delivery->rider->phone : null,
            'delivery_status' => $delivery->delivery_status,
            'arrived_at' => $delivery->arrived_at ? $delivery->arrived_at->format('M d, Y h:i A') : null,
            'delivered_at' => $delivery->delivered_at ? $delivery->delivered_at->format('M d, Y h:i A') : null,
            'cancellation_reason' => $delivery->cancellation_reason,
            'cancelled_after_arrival' => (bool) $delivery->cancelled_after_arrival,
            'customer_id' => $order ? $order->customer_id : null,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.deliveries'),
            new PrivateChannel('orders.' . $this->delivery['order_id']),
        ];

        if (!empty($this->delivery['rider_id'])) {
            $channels[] = new PrivateChannel('rider.deliveries.' . $this->delivery['rider_id']);
        }

        if (!empty($this->delivery['customer_id'])) {
            $channels[] = new PrivateChannel('customer.orders.' . $this->delivery['customer_id']);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'delivery.updated';
    }
}
