<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $afterCommit = true;

    public int $userId;
    public array $notification;
    public int $unreadCount;

    /**
     * Create a new event instance.
     */
    public function __construct(AppNotification $notification)
    {
        $this->userId = (int) $notification->user_id;

        $this->notification = [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'target_url' => $notification->target_url,
            'link' => $notification->link,
            'is_read' => (bool) $notification->is_read,
            'created_at' => $notification->created_at ? $notification->created_at->diffForHumans() : 'Just now',
        ];

        $this->unreadCount = AppNotification::where('user_id', $this->userId)->unread()->count();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}
