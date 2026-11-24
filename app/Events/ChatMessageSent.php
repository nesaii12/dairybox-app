<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $afterCommit = true;

    public array $message;

    public function __construct(ChatMessage $chatMessage)
    {
        $this->message = $chatMessage->toPayload();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('transaction.chat.' . $this->message['chat_thread_id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
