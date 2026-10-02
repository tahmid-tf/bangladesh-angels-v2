<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Investment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a paginated, filterable table of member activity notifications.
     */
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.notifications.index');
    }

    /**
     * Toggle viewed status of an investment (notification).
     */
    public function toggleViewed(Request $request, Investment $investment): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin($request);

        $action = $request->input('action'); // 'view', 'unview', or null for toggle

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

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'viewed' => $isViewed,
                'viewed_at' => $investment->viewed_at?->toISOString(),
                'viewed_at_formatted' => $investment->viewed_at?->format('M j, Y g:i A'),
                'viewed_at_human' => $investment->viewed_at?->diffForHumans(),
                'viewer_name' => $request->user()->name,
                'unread_count' => $globalUnreadCount,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Mark all unread investments as viewed.
     */
    public function markAllViewed(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin($request);

        $updated = Investment::whereNull('viewed_at')->update([
            'viewed_at' => now(),
            'viewed_by' => $request->user()->id,
            'updated_at' => now(),
        ]);

        $message = $updated > 0
            ? "Marked {$updated} " . Str::plural('notification', $updated) . ' as read.'
            : 'All notifications are already marked as read.';

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'updated_count' => $updated,
                'unread_count' => 0,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Get live unread notifications count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'unread_count' => Investment::whereNull('viewed_at')->count(),
        ]);
    }

    /**
     * Ensure current user is an admin.
     */
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
