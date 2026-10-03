<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class MembersTable extends Component
{
    use WithPagination;

    public $search = '';

    public $filter = 'all';

    public $allCount;

    public $activeCount;

    public $inactiveCount;

    public $pendingCount;

    public $featuredCount;

    /** Search approved members to add to featured (Featured tab only). */
    public $featuredPickerSearch = '';

    protected $queryString = ['search', 'filter'];

    public function mount()
    {
        $this->updateCounts();
    }

    public function updateCounts()
    {
        $this->allCount = User::where('is_approved', true)->count();
        $this->activeCount = User::where('account_status', '!=', 'free')->where('is_approved', true)->count();
        $this->inactiveCount = User::where('account_status', 'free')->where('is_approved', true)->count();
        $this->pendingCount = User::where('is_approved', false)->count();
        $this->featuredCount = User::where('featured', true)->where('is_approved', true)->count();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilter()
    {
        $this->resetPage();
    }

    public function searchUsers()
    {
        $this->render();
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
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
            session()->flash('success', "Member '{$userName}' successfully removed.");
        } catch (\Exception $e) {
            \Log::error('Error deleting user: '.$e->getMessage());
            session()->flash('error', 'An error occurred while trying to delete the member. Please try again.');
        }
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
        $query = User::query();

        switch ($this->filter) {
            case 'active':
                $query->where('account_status', '!=', 'free')->where('is_approved', true);
                break;
            case 'inactive':
                $query->where('account_status', 'free')->where('is_approved', true);
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

        $query->with('emailVerifiedByAdmin');

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
