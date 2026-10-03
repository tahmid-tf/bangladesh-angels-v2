<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MembersTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public $search = '';

    #[Url(as: 'filter', except: 'all')]
    public $filter = 'all';

    public $allCount;

    public $activeCount;

    public $inactiveCount;

    public $pendingCount;

    public $featuredCount;

    /** Search approved members to add to featured (Featured tab only). */
    public $featuredPickerSearch = '';

    /** Selected user IDs for bulk actions (Pending Approval tab) */
    public array $selectedUserIds = [];

    public bool $selectAll = false;

    public function mount()
    {
        $filterParam = request()->query('filter');
        if ($filterParam && in_array($filterParam, ['all', 'active', 'inactive', 'pending', 'featured'], true)) {
            $this->filter = $filterParam;
        }

        $searchParam = request()->query('q') ?? request()->query('search');
        if ($searchParam) {
            $this->search = trim($searchParam);
        }

        $this->updateCounts();
    }

    public function updateCounts()
    {
        $this->allCount = User::where('is_approved', true)->count();
        $this->activeCount = User::where('status', 'active')->where('is_approved', true)->count();
        $this->inactiveCount = User::where('status', 'inactive')->where('is_approved', true)->count();
        $this->pendingCount = User::where('is_approved', false)->count();
        $this->featuredCount = User::where('featured', true)->where('is_approved', true)->count();
    }

    public function updatingSearch()
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatingFilter()
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatingPage()
    {
        $this->clearSelection();
    }

    public function clearSelection(): void
    {
        $this->selectedUserIds = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedUserIds = $this->getCurrentPageIds();
        } else {
            $this->selectedUserIds = [];
        }
    }

    public function updatedSelectedUserIds(): void
    {
        $pageIds = $this->getCurrentPageIds();
        $this->selectAll = ! empty($pageIds) && count(array_intersect($pageIds, array_map('strval', $this->selectedUserIds))) === count($pageIds);
    }

    public function selectAllFiltered(): void
    {
        $query = $this->getBaseQuery();
        $this->selectedUserIds = $query->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->selectAll = true;
    }

    public function selectAllPending(): void
    {
        $this->selectAllFiltered();
    }

    protected function getBaseQuery()
    {
        $query = User::query();

        switch ($this->filter) {
            case 'active':
                $query->where('status', 'active')->where('is_approved', true);
                break;
            case 'inactive':
                $query->where('status', 'inactive')->where('is_approved', true);
                break;
            case 'pending':
                $query->where('is_approved', false);
                break;
            case 'featured':
                $query->where('featured', true)->where('is_approved', true);
                break;
            default:
                $query->where('is_approved', true);
                break;
        }

        if (! empty($this->search)) {
            $term = "%{$this->search}%";
            $query->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('designation', 'like', $term)
                    ->orWhere('company_name', 'like', $term);
            });
        }

        return $query;
    }

    protected function getCurrentPageIds(): array
    {
        $query = $this->getBaseQuery();
        $page = $this->getPage();

        $orderedQuery = ($this->filter === 'pending')
            ? $query->orderBy('created_at', 'desc')
            : $query->orderBy('name', 'asc');

        return $orderedQuery
            ->forPage($page, 10)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    protected function getCurrentPagePendingIds(): array
    {
        return $this->getCurrentPageIds();
    }

    public function searchUsers()
    {
        $this->render();
    }

    public function setFilter($filter)
    {
        $this->filter = in_array($filter, ['all', 'active', 'inactive', 'pending', 'featured'], true) ? $filter : 'all';
        $this->clearSelection();
        $this->resetPage();
    }

    public function approveUser($userId)
    {
        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to perform this action.');
            return;
        }

        $user = User::find($userId);
        if ($user) {
            $user->update([
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\AccountApproved($user));
            } catch (\Throwable $e) {
                \Log::error("Failed sending approval email to user {$user->id}: " . $e->getMessage());
            }

            $this->updateCounts();
            session()->flash('success', "Member '{$user->name}' has been approved.");
        }
    }

    public function deleteUser(int $userId): void
    {
        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to perform this action.');
            return;
        }

        if (auth()->id() === $userId) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            session()->flash('error', 'User not found.');
            return;
        }

        try {
            if ($user->hasMedia('profile_photo')) {
                $user->clearMediaCollection('profile_photo');
            }

            $userName = $user->name;
            $user->delete();

            $this->updateCounts();
            $this->dispatch('member-deleted');
            session()->flash('success', "Member '{$userName}' successfully removed.");
        } catch (\Exception $e) {
            \Log::error('Error deleting user: '.$e->getMessage());
            session()->flash('error', 'An error occurred while trying to delete the member. Please try again.');
        }
    }

    public function updateAccountStatus(int $userId, string $status): void
    {
        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to perform this action.');
            return;
        }

        $validStatuses = ['free', 'core', 'advanced', 'institutional'];
        $status = strtolower(trim($status));

        if (! in_array($status, $validStatuses, true)) {
            session()->flash('error', 'Invalid account tier selected.');
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            session()->flash('error', 'User not found.');
            return;
        }

        if (strtolower((string) $user->account_status) === $status) {
            return;
        }

        $user->update([
            'account_status' => $status,
        ]);

        $this->updateCounts();

        $tierName = ucfirst($status);
        session()->flash('success', "Member '{$user->name}' tier changed to {$tierName}.");
    }

    public function setActiveMode(int $userId, string $mode): void
    {
        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to perform this action.');
            return;
        }

        $mode = strtolower(trim($mode));
        if (! in_array($mode, ['active', 'inactive'], true)) {
            session()->flash('error', 'Invalid mode selected.');
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            session()->flash('error', 'User not found.');
            return;
        }

        $currentMode = strtolower((string) ($user->status ?? 'active'));
        if ($currentMode === $mode) {
            return;
        }

        $user->update([
            'status' => $mode,
        ]);

        $this->updateCounts();
        $modeName = ucfirst($mode);
        session()->flash('success', "Member '{$user->name}' mode changed to {$modeName}.");
    }

    public function verifyEmail(int $userId): void
    {
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        $user = User::find($userId);
        if (! $user || $user->hasVerifiedEmail()) {
            return;
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verified_by' => auth()->id(),
        ]);

        session()->flash('success', "Email verified for {$user->email}.");
    }

    public function approveSelected(): void
    {
        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to perform this action.');
            return;
        }

        if (empty($this->selectedUserIds)) {
            session()->flash('error', 'No members selected.');
            return;
        }

        $userIds = array_map('intval', $this->selectedUserIds);
        $users = User::whereIn('id', $userIds)->where('is_approved', false)->get();
        $count = $users->count();

        if ($count === 0) {
            $this->clearSelection();
            session()->flash('info', 'None of the selected members were pending approval.');
            return;
        }

        $now = now();
        $adminId = auth()->id();

        foreach ($users as $user) {
            $user->update([
                'is_approved' => true,
                'approved_by' => $adminId,
                'approved_at' => $now,
            ]);

            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\AccountApproved($user));
            } catch (\Throwable $e) {
                \Log::error("Failed sending approval email to user {$user->id}: " . $e->getMessage());
            }
        }

        $this->clearSelection();
        $this->updateCounts();
        $this->dispatch('bulk-action-completed');
        session()->flash('success', "Successfully approved {$count} " . ($count === 1 ? 'member' : 'members') . ".");
    }

    public function verifyEmailSelected(): void
    {
        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to perform this action.');
            return;
        }

        if (empty($this->selectedUserIds)) {
            session()->flash('error', 'No members selected.');
            return;
        }

        $userIds = array_map('intval', $this->selectedUserIds);
        $users = User::whereIn('id', $userIds)->whereNull('email_verified_at')->get();
        $count = $users->count();

        if ($count === 0) {
            $this->clearSelection();
            session()->flash('info', 'All selected members already have verified emails.');
            return;
        }

        $now = now();
        $adminId = auth()->id();

        foreach ($users as $user) {
            $user->update([
                'email_verified_at' => $now,
                'email_verified_by' => $adminId,
            ]);
        }

        $this->clearSelection();
        $this->dispatch('bulk-action-completed');
        session()->flash('success', "Successfully verified emails for {$count} " . ($count === 1 ? 'member' : 'members') . ".");
    }

    public function deleteSelected(): void
    {
        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to perform this action.');
            return;
        }

        if (empty($this->selectedUserIds)) {
            session()->flash('error', 'No members selected.');
            return;
        }

        $userIds = array_map('intval', $this->selectedUserIds);
        $userIds = array_values(array_filter($userIds, fn ($id) => $id !== auth()->id()));

        $users = User::whereIn('id', $userIds)->get();
        $count = $users->count();

        if ($count === 0) {
            $this->clearSelection();
            session()->flash('error', 'No valid members to delete.');
            return;
        }

        try {
            foreach ($users as $user) {
                if ($user->hasMedia('profile_photo')) {
                    $user->clearMediaCollection('profile_photo');
                }
                $user->delete();
            }

            $this->clearSelection();
            $this->updateCounts();
            $this->dispatch('bulk-action-completed');
            session()->flash('success', "Successfully deleted {$count} " . ($count === 1 ? 'member' : 'members') . ".");
        } catch (\Throwable $e) {
            \Log::error('Bulk delete error: ' . $e->getMessage());
            session()->flash('error', 'An error occurred during bulk deletion. Please try again.');
        }
    }

    public function addToFeatured(int $userId): void
    {
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        $user = User::where('id', $userId)->where('is_approved', true)->where('featured', false)->first();
        if ($user) {
            $user->update(['featured' => true]);
            $this->updateCounts();
            session()->flash('success', "{$user->name} is now featured on the public investors page.");
        }
    }

    public function removeFromFeatured(int $userId): void
    {
        if (! auth()->user()?->isAdmin()) {
            return;
        }

        $user = User::where('id', $userId)->where('featured', true)->first();
        if ($user) {
            $user->update([
                'featured' => false,
                'featured_testimonial' => null,
                'featured_testimonial_public' => false,
            ]);
            $this->updateCounts();
            session()->flash('success', "{$user->name} has been removed from featured.");
        }
    }

    public function render()
    {
        $query = $this->getBaseQuery()->with('emailVerifiedByAdmin');

        $paginatedUsers = ($this->filter === 'pending')
            ? (clone $query)->orderBy('created_at', 'desc')->paginate(10)
            : (clone $query)->orderBy('name', 'asc')->paginate(10);

        if ($paginatedUsers->isEmpty() && $paginatedUsers->currentPage() > 1) {
            $this->setPage(max(1, $paginatedUsers->lastPage()));
            $paginatedUsers = ($this->filter === 'pending')
                ? (clone $query)->orderBy('created_at', 'desc')->paginate(10)
                : (clone $query)->orderBy('name', 'asc')->paginate(10);
        }

        $users = $paginatedUsers;
        $featurePickerResults = $this->featuredPickerResults();

        return view('livewire.members-table', compact('users', 'featurePickerResults'));
    }

    /**
     * @return Collection<int, User>
     */
    protected function featuredPickerResults(): Collection
    {
        if ($this->filter !== 'featured') {
            return collect();
        }

        $q = trim($this->featuredPickerSearch);
        if ($q === '') {
            return collect();
        }

        $term = '%'.$q.'%';

        return User::query()
            ->where('is_approved', true)
            ->where('featured', false)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('designation', 'like', $term)
                    ->orWhere('company_name', 'like', $term);
            })
            ->orderBy('name')
            ->limit(25)
            ->get();
    }
}
