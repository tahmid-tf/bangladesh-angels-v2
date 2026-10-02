<?php

namespace App\Livewire;

use App\Models\Deal;
use App\Models\Investment;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdminNotificationsTable extends Component
{
    use WithPagination;

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all'; // 'all', 'unread', 'read'

    #[Url(as: 'deal_id', except: '')]
    public string $dealId = '';

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'per_page', except: 20)]
    public int $perPage = 20;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDealId(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $this->status = in_array($status, ['all', 'unread', 'read'], true) ? $status : 'all';
        $this->resetPage();
    }

    public function applySearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->status = 'all';
        $this->dealId = '';
        $this->type = '';
        $this->search = '';
        $this->perPage = 20;
        $this->resetPage();
    }

    public function toggleViewed(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $investment = Investment::findOrFail($id);

        if ($investment->isViewed()) {
            $investment->markAsUnviewed();
            $msg = 'Notification marked as unread.';
        } else {
            $investment->markAsViewed(auth()->id());
            $msg = 'Notification marked as read.';
        }

        $unreadCount = Investment::whereNull('viewed_at')->count();

        $this->js("
            if (typeof updateGlobalBadges === 'function') updateGlobalBadges({$unreadCount});
            if (typeof showNotificationToast === 'function') showNotificationToast('" . addslashes($msg) . "');
        ");

        $this->dispatch('notification-status-updated', unreadCount: $unreadCount, message: $msg);
    }

    public function markAllAsRead(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $updated = Investment::whereNull('viewed_at')->update([
            'viewed_at' => now(),
            'viewed_by' => auth()->id(),
            'updated_at' => now(),
        ]);

        $msg = $updated > 0
            ? "Marked {$updated} notifications as read."
            : 'All notifications are already marked as read.';

        $this->js("
            if (typeof updateGlobalBadges === 'function') updateGlobalBadges(0);
            if (typeof showNotificationToast === 'function') showNotificationToast('" . addslashes($msg) . "');
        ");

        $this->dispatch('notification-status-updated', unreadCount: 0, message: $msg);
    }

    public function render(): View
    {
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

        $query = Investment::query()
            ->with(['user', 'deal', 'viewer'])
            ->latest('created_at');

        if ($this->status === 'unread') {
            $query->whereNull('viewed_at');
        } elseif ($this->status === 'read') {
            $query->whereNotNull('viewed_at');
        }

        if (filled($this->type)) {
            $query->where('type', $this->type);
        }

        if (filled($this->dealId)) {
            $query->where('deal_id', $this->dealId);
        }

        if (filled($this->search)) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', function ($uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                       ->orWhere('email', 'like', "%{$term}%");
                })->orWhereHas('deal', function ($dq) use ($term) {
                    $dq->where('title', 'like', "%{$term}%");
                });
            });
        }

        $notifications = $query->paginate($this->perPage);

        return view('livewire.admin-notifications-table', [
            'notifications' => $notifications,
            'stats' => $stats,
            'dealsList' => $dealsList,
        ]);
    }
}
