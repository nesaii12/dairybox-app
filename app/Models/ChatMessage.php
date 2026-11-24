<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_thread_id',
        'sender_id',
        'sender_role',
        'body',
        'attachment_path',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Format message payload consistently for API responses and broadcasts (Asia/Manila).
     */
    public function toPayload(): array
    {
        $this->loadMissing('sender');
        $manilaTime = $this->created_at
            ? $this->created_at->copy()->timezone('Asia/Manila')
            : now('Asia/Manila');

        return [
            'id' => $this->id,
            'chat_thread_id' => $this->chat_thread_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender?->name ?? ucfirst($this->sender_role),
            'sender_role' => $this->sender_role,
            'body' => $this->body ?? '',
            'attachment_url' => $this->attachment_path ? asset('storage/' . $this->attachment_path) : null,
            'read_at' => $this->read_at ? $this->read_at->copy()->timezone('Asia/Manila')->toIso8601String() : null,
            'created_at_iso' => $manilaTime->toIso8601String(),
            'created_at_formatted' => $manilaTime->format('M d, h:i A'),
            'time_formatted' => $manilaTime->format('h:i A'),
        ];
    }
}
