<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, AppNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        if ($request->ajax() || $request->wantsJson()) {
            $unreadCount = AppNotification::where('user_id', auth()->id())->where('is_read', false)->count();
            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
            ]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request)
    {
        AppNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
