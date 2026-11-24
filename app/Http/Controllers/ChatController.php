<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * List accessible order & reservation threads for the floating chat bubble.
     */
    public function threads(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $user->role;

        $ordersQuery = Order::with(['customer', 'rider', 'chatThread.messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->latest('updated_at')
            ->limit(15);

        $reservationsQuery = Reservation::with(['customer', 'rider', 'order.rider', 'chatThread.messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->latest('updated_at')
            ->limit(15);

        if ($user->isCustomer()) {
            $ordersQuery->where('customer_id', $user->id);
            $reservationsQuery->where('customer_id', $user->id);
        } elseif ($user->isRider()) {
            $ordersQuery->where('rider_id', $user->id);
            $reservationsQuery->where(function ($q) use ($user) {
                $q->where('rider_id', $user->id)
                    ->orWhereHas('order', fn ($oq) => $oq->where('rider_id', $user->id));
            });
        }

        $items = collect();

        foreach ($ordersQuery->get() as $order) {
            $thread = $order->chatThread;
            $lastMsg = $thread?->messages->first();
            $unread = $thread ? $thread->unreadCountForRole($role) : 0;

            $items->push([
                'type' => 'order',
                'id' => $order->id,
                'thread_id' => $thread?->id,
                'reference' => 'Order #' . $order->order_number,
                'status_label' => $order->order_status,
                'subtitle' => $user->isCustomer()
                    ? ($order->rider ? 'Rider: ' . $order->rider->name : 'Admin Support')
                    : ($order->customer?->name ?? 'Customer'),
                'last_message' => $lastMsg ? ($lastMsg->body ?: 'Sent an attachment') : 'No messages yet',
                'last_time' => $lastMsg?->created_at?->copy()->timezone('Asia/Manila')->format('h:i A')
                    ?? $order->updated_at?->copy()->timezone('Asia/Manila')->format('M d'),
                'sort_ts' => ($lastMsg?->created_at ?? $order->updated_at)?->timestamp ?? 0,
                'unread_count' => $unread,
            ]);
        }

        foreach ($reservationsQuery->get() as $reservation) {
            $thread = $reservation->chatThread;
            $lastMsg = $thread?->messages->first();
            $unread = $thread ? $thread->unreadCountForRole($role) : 0;

            $items->push([
                'type' => 'reservation',
                'id' => $reservation->id,
                'thread_id' => $thread?->id,
                'reference' => $reservation->reservation_code,
                'status_label' => $reservation->status,
                'subtitle' => $user->isCustomer()
                    ? 'Reservation Inquiry'
                    : ($reservation->customer_name ?? $reservation->customer?->name ?? 'Customer'),
                'last_message' => $lastMsg ? ($lastMsg->body ?: 'Sent an attachment') : 'No messages yet',
                'last_time' => $lastMsg?->created_at?->copy()->timezone('Asia/Manila')->format('h:i A')
                    ?? $reservation->updated_at?->copy()->timezone('Asia/Manila')->format('M d'),
                'sort_ts' => ($lastMsg?->created_at ?? $reservation->updated_at)?->timestamp ?? 0,
                'unread_count' => $unread,
            ]);
        }

        $sorted = $items->sortByDesc('sort_ts')->take(20)->values();
        $totalUnread = (int) $sorted->sum('unread_count');

        return response()->json([
            'threads' => $sorted,
            'total_unread' => $totalUnread,
        ]);
    }

    /**
     * Fetch or auto-initialize a transaction chat thread and mark incoming messages as read.
     */
    public function showThread(Request $request, string $type, int $id): JsonResponse
    {
        $user = $request->user();
        $normalizedType = strtolower($type);

        $chatable = match ($normalizedType) {
            'order' => Order::with(['customer', 'rider', 'reservation'])->find($id),
            'reservation' => Reservation::with(['customer', 'rider', 'order.rider'])->find($id),
            default => null,
        };

        if (!$chatable) {
            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        if (!ChatThread::canUserAccessChatable($user, $chatable)) {
            return response()->json(['message' => 'Unauthorized to access this chat thread.'], 403);
        }

        $thread = ChatThread::firstOrCreate(
            [
                'chatable_type' => $chatable::class,
                'chatable_id' => $chatable->id,
            ],
            [
                'status' => ChatThread::STATUS_OPEN,
            ]
        );

        // Mark unread messages from other roles as read
        $thread->messages()
            ->whereNull('read_at')
            ->where('sender_role', '!=', $user->role)
            ->update(['read_at' => now('Asia/Manila')]);

        $messages = $thread->messages()
            ->with('sender')
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $msg) => $msg->toPayload())
            ->values();

        return response()->json([
            'thread' => $this->formatThreadMeta($thread, $chatable, $normalizedType),
            'messages' => $messages,
            'unread_count' => 0,
        ]);
    }

    /**
     * Store a new message in the transaction chat thread and broadcast it.
     */
    public function storeMessage(Request $request, ChatThread $thread): JsonResponse
    {
        $user = $request->user();
        $thread->loadMissing('chatable');

        if (!$thread->canAccess($user)) {
            return response()->json(['message' => 'Unauthorized to post in this chat thread.'], 403);
        }

        if ($thread->status === ChatThread::STATUS_CLOSED) {
            return response()->json(['message' => 'This chat thread is closed.'], 422);
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'image', 'max:10240'],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat-attachments', 'public');
        }

        if ($body === '' && $attachmentPath === null) {
            return response()->json([
                'message' => 'Please enter a message or attach a photo.',
            ], 422);
        }

        $message = ChatMessage::create([
            'chat_thread_id' => $thread->id,
            'sender_id' => $user->id,
            'sender_role' => $user->role,
            'body' => $body !== '' ? $body : null,
            'attachment_path' => $attachmentPath,
            'created_at' => now('Asia/Manila'),
            'updated_at' => now('Asia/Manila'),
        ]);

        $message->load('sender');

        try {
            broadcast(new ChatMessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('ChatMessageSent broadcast failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => $message->toPayload(),
        ], 201);
    }

    private function formatThreadMeta(ChatThread $thread, Order|Reservation $chatable, string $type): array
    {
        if ($chatable instanceof Order) {
            $reference = 'Order #' . $chatable->order_number;
            $statusLabel = $chatable->order_status;
            $customerName = $chatable->reservation?->customer_name ?? $chatable->customer?->name ?? 'Customer';
            $riderName = $chatable->rider?->name;
            $fulfillment = $chatable->order_type;
        } else {
            $reference = $chatable->reservation_code;
            $statusLabel = $chatable->status;
            $customerName = $chatable->customer_name ?? $chatable->customer?->name ?? 'Customer';
            $riderName = $chatable->rider?->name ?? $chatable->order?->rider?->name;
            $fulfillment = $chatable->fulfillment_type ?? 'Pickup';
        }

        return [
            'id' => $thread->id,
            'status' => $thread->status,
            'type' => $type,
            'chatable_id' => $chatable->id,
            'reference' => $reference,
            'status_label' => $statusLabel,
            'fulfillment' => $fulfillment,
            'customer_name' => $customerName,
            'rider_name' => $riderName,
            'has_rider' => !empty($riderName),
        ];
    }
}
