<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Investment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminNotificationApiController extends BaseApiController
{
    /**
     * Paginated list of member activity notifications.
     */
    public function notifications(Request $request): JsonResponse
    {
        $status = $request->query('status'); // 'unread', 'read', or all

        $notifications = Investment::with(['deal:id,title,slug', 'user:id,name,email'])
            ->when($status === 'unread', fn ($q) => $q->whereNull('viewed_at'))
            ->when($status === 'read', fn ($q) => $q->whereNotNull('viewed_at'))
            ->latest()
            ->paginate((int) $request->query('per_page', 25));

        return $this->success($notifications);
    }

    /**
     * Unread count of notifications.
     */
    public function unreadCount(): JsonResponse
    {
        $count = Investment::whereNull('viewed_at')->count();

        return $this->success(['unread_count' => $count]);
    }

    /**
     * Toggle viewed status for a notification.
     */
    public function toggleViewed(Request $request, Investment $investment): JsonResponse
    {
        $action = $request->input('action'); // 'view', 'unview', or null

        if ($action === 'view' || ($action === null && ! $investment->isViewed())) {
            $investment->markAsViewed($request->user()->id);
            $isViewed = true;
            $message = 'Notification marked as read.';
        } else {
            $investment->markAsUnviewed();
            $isViewed = false;
            $message = 'Notification marked as unread.';
        }

        $globalUnreadCount = Investment::whereNull('viewed_at')->count();

        return $this->success([
            'viewed' => $isViewed,
            'viewed_at' => $investment->viewed_at?->toISOString(),
            'unread_count' => $globalUnreadCount,
        ], $message);
    }

    /**
     * Mark all notifications as viewed.
     */
    public function markAllViewed(Request $request): JsonResponse
    {
        $updated = Investment::whereNull('viewed_at')->update([
            'viewed_at' => now(),
            'viewed_by' => $request->user()->id,
            'updated_at' => now(),
        ]);

        $message = $updated > 0
            ? "Marked {$updated} ".Str::plural('notification', $updated).' as read.'
            : 'All notifications are already marked as read.';

        return $this->success([
            'updated_count' => $updated,
            'unread_count' => 0,
        ], $message);
    }
}
