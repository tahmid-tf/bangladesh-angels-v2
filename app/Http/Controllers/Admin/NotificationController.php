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

        $status = $request->query('status', 'all'); // 'all', 'unread', 'read'
        $type = $request->query('type');
        $dealId = $request->query('deal_id');
        $search = $request->query('q');
        $perPage = (int) $request->query('per_page', 20);
        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = Investment::query()
            ->with(['user', 'deal', 'viewer'])
            ->latest('created_at');

        if ($status === 'unread') {
            $query->whereNull('viewed_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('viewed_at');
        }

        if (filled($type)) {
            $query->where('type', $type);
        }

        if (filled($dealId)) {
            $query->where('deal_id', $dealId);
        }

        if (filled($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', function ($uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                       ->orWhere('email', 'like', "%{$term}%");
                })->orWhereHas('deal', function ($dq) use ($term) {
                    $dq->where('title', 'like', "%{$term}%");
                });
            });
        }

        $notifications = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Investment::count(),
            'unread' => Investment::whereNull('viewed_at')->count(),
            'read' => Investment::whereNotNull('viewed_at')->count(),
            'deals_count' => Investment::distinct('deal_id')->count('deal_id'),
        ];

        $dealsList = Deal::query()
            ->whereIn('id', Investment::select('deal_id')->distinct())
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'stats' => $stats,
            'dealsList' => $dealsList,
            'filters' => [
                'status' => $status,
                'type' => $type,
                'deal_id' => $dealId,
                'q' => $search,
                'per_page' => $perPage,
            ],
        ]);
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
