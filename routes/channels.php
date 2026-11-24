<?php

use App\Models\ChatThread;
use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.orders', function ($user) {
    return $user->role === 'admin';
});

Broadcast::channel('admin.deliveries', function ($user) {
    return $user->role === 'admin';
});

Broadcast::channel('customer.orders.{customerId}', function ($user, $customerId) {
    return (int) $user->id === (int) $customerId;
});

Broadcast::channel('orders.{orderId}', function ($user, $orderId) {
    $order = Order::find($orderId);
    if (!$order) {
        return false;
    }
    return $user->role === 'admin'
        || (int) $user->id === (int) $order->customer_id
        || ($order->rider_id && (int) $user->id === (int) $order->rider_id);
});

Broadcast::channel('rider.deliveries.{riderId}', function ($user, $riderId) {
    return (int) $user->id === (int) $riderId && $user->role === 'rider';
});

Broadcast::channel('transaction.chat.{threadId}', function ($user, $threadId) {
    $thread = ChatThread::with('chatable')->find($threadId);
    return $thread ? $thread->canAccess($user) : false;
});
