<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChatThread extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'chatable_type',
        'chatable_id',
        'status',
    ];

    public function chatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_thread_id');
    }

    /**
     * Determine whether the given user is authorized to view/post in this thread.
     */
    public function canAccess(User $user): bool
    {
        return static::canUserAccessChatable($user, $this->chatable);
    }

    /**
     * Determine whether the given user can access a chatable Order or Reservation.
     */
    public static function canUserAccessChatable(User $user, ?Model $chatable): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$chatable) {
            return false;
        }

        if ($chatable instanceof Order) {
            if ($user->isCustomer()) {
                return (int) $chatable->customer_id === (int) $user->id;
            }

            if ($user->isRider()) {
                return $chatable->rider_id !== null && (int) $chatable->rider_id === (int) $user->id;
            }

            return false;
        }

        if ($chatable instanceof Reservation) {
            if ($user->isCustomer()) {
                return $chatable->customer_id !== null && (int) $chatable->customer_id === (int) $user->id;
            }

            if ($user->isRider()) {
                if ($chatable->rider_id !== null && (int) $chatable->rider_id === (int) $user->id) {
                    return true;
                }

                $chatable->loadMissing('order');
                return $chatable->order !== null
                    && $chatable->order->rider_id !== null
                    && (int) $chatable->order->rider_id === (int) $user->id;
            }

            return false;
        }

        return false;
    }

    /**
     * Count unread messages from other roles for the given viewer role.
     */
    public function unreadCountForRole(string $viewerRole): int
    {
        return $this->messages()
            ->whereNull('read_at')
            ->where('sender_role', '!=', $viewerRole)
            ->count();
    }
}
