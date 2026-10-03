<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MembersTable extends Component
{
    use WithFileUploads;
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

    /** Livewire Edit Modal State */
    public bool $showEditModal = false;

    public ?int $editingUserId = null;

    public $editProfilePhoto = null;

    public array $editForm = [
        'full_name' => '',
        'email' => '',
        'phone' => '',
        'gender' => 'male',
        'primary_country' => 'Bangladesh',
        'preference_sector' => '',
        'organization' => '',
        'designation' => '',
        'role' => 'investor',
        'account_level' => '',
        'account_status' => 'free',
        'status' => 'active',
        'public_profile' => 'false',
        'primary_contact' => '',
        'secondary_contact' => '',
        'total_invested' => '',
        'revenue_generated' => '',
        'was_referred' => 0,
        'referred_by' => '',
        'password' => '',
        'password_confirmation' => '',
        'notes' => '',
    ];

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

        $editParam = request()->query('edit');
        if ($editParam && is_numeric($editParam)) {
            $this->openEditModal((int) $editParam);
        }
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

    public function updatedFilter()
    {
        if ($this->showEditModal) {
            $this->closeEditModal(false);
        }
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
        if ($this->showEditModal) {
            $this->closeEditModal(false);
        }
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
        $this->dispatch('member-mode-updated');
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

    protected ?User $cachedEditingUser = null;

    protected static ?array $cachedCountries = null;

    public function getEditingUserProperty(): ?User
    {
        if (! $this->editingUserId) {
            return null;
        }

        if ($this->cachedEditingUser && $this->cachedEditingUser->id === $this->editingUserId) {
            return $this->cachedEditingUser;
        }

        return $this->cachedEditingUser = User::with(['emailVerifiedByAdmin', 'media'])->find($this->editingUserId);
    }

    public function getCountries(): array
    {
        return self::$cachedCountries ??= [
            'Afghanistan', 'Åland Islands', 'Albania', 'Algeria', 'American Samoa', 'Andorra', 'Angola', 'Anguilla',
            'Antarctica', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Aruba', 'Australia', 'Austria', 'Azerbaijan',
            'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bermuda',
            'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Bouvet Island', 'Brazil',
            'British Indian Ocean Territory', 'Brunei Darussalam', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cambodia',
            'Cameroon', 'Canada', 'Cape Verde', 'Cayman Islands', 'Central African Republic', 'Chad', 'Chile', 'China',
            'Christmas Island', 'Cocos (Keeling) Islands', 'Colombia', 'Comoros', 'Congo',
            'Congo, The Democratic Republic of The', 'Cook Islands', 'Costa Rica', "Cote D'ivoire", 'Croatia', 'Cuba',
            'Cyprus', 'Czech Republic', 'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt',
            'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Ethiopia', 'Falkland Islands (Malvinas)',
            'Faroe Islands', 'Fiji', 'Finland', 'France', 'French Guiana', 'French Polynesia',
            'French Southern Territories', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Gibraltar', 'Greece',
            'Greenland', 'Grenada', 'Guadeloupe', 'Guam', 'Guatemala', 'Guernsey', 'Guinea', 'Guinea-bissau', 'Guyana',
            'Haiti', 'Heard Island and Mcdonald Islands', 'Holy See (Vatican City State)', 'Honduras', 'Hong Kong',
            'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran, Islamic Republic of', 'Iraq', 'Ireland', 'Isle of Man',
            'Israel', 'Italy', 'Jamaica', 'Japan', 'Jersey', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati',
            "Korea, Democratic People's Republic of", 'Korea, Republic of', 'Kuwait', 'Kyrgyzstan',
            "Lao People's Democratic Republic", 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libyan Arab Jamahiriya',
            'Liechtenstein', 'Lithuania', 'Luxembourg', 'Macao', 'Macedonia, The Former Yugoslav Republic of',
            'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Martinique',
            'Mauritania', 'Mauritius', 'Mayotte', 'Mexico', 'Micronesia, Federated States of',
            'Moldova, Republic of', 'Monaco', 'Mongolia', 'Montenegro', 'Montserrat', 'Morocco', 'Mozambique',
            'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands', 'Netherlands Antilles', 'New Caledonia',
            'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'Niue', 'Norfolk Island', 'Northern Mariana Islands',
            'Norway', 'Oman', 'Pakistan', 'Palau', 'Palestinian Territory, Occupied', 'Panama', 'Papua New Guinea',
            'Paraguay', 'Peru', 'Philippines', 'Pitcairn', 'Poland', 'Portugal', 'Puerto Rico', 'Qatar', 'Reunion',
            'Romania', 'Russian Federation', 'Rwanda', 'Saint Helena', 'Saint Kitts and Nevis', 'Saint Lucia',
            'Saint Pierre and Miquelon', 'Saint Vincent and The Grenadines', 'Samoa', 'San Marino',
            'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone', 'Singapore',
            'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa',
            'South Georgia and The South Sandwich Islands', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname',
            'Svalbard and Jan Mayen', 'Swaziland', 'Sweden', 'Switzerland', 'Syrian Arab Republic', 'Taiwan',
            'Tajikistan', 'Tanzania, United Republic of', 'Thailand', 'Timor-leste', 'Togo', 'Tokelau', 'Tonga',
            'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Turkmenistan', 'Turks and Caicos Islands', 'Tuvalu',
            'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States',
            'United States Minor Outlying Islands', 'Uruguay', 'Uzbekistan', 'Vanuatu', 'Venezuela', 'Viet Nam',
            'Virgin Islands, British', 'Virgin Islands, U.S.', 'Wallis and Futuna', 'Western Sahara', 'Yemen',
            'Zambia', 'Zimbabwe',
        ];
    }

    public function openEditModal(int $userId): void
    {
        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to edit members.');
            return;
        }

        $user = User::with(['emailVerifiedByAdmin', 'media'])->find($userId);
        if (! $user) {
            session()->flash('error', 'Member not found.');
            return;
        }

        $this->cachedEditingUser = $user;
        $this->resetValidation();
        $this->editProfilePhoto = null;
        $this->editingUserId = $user->id;

        $this->editForm = [
            'full_name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'gender' => (string) ($user->gender ?? 'male'),
            'primary_country' => (string) ($user->primary_country ?? 'Bangladesh'),
            'preference_sector' => (string) ($user->preference_sector ?? ''),
            'organization' => (string) ($user->company_name ?? ''),
            'designation' => (string) ($user->designation ?? ''),
            'role' => (string) ($user->role ?? 'investor'),
            'account_level' => (string) ($user->level ?? ''),
            'account_status' => strtolower((string) ($user->account_status ?? 'free')),
            'status' => strtolower((string) ($user->status ?? 'active')),
            'public_profile' => ((string) $user->public_profile === 'true' || $user->public_profile === true || $user->public_profile === 1) ? 'true' : 'false',
            'primary_contact' => (string) ($user->primary_contact ?? ''),
            'secondary_contact' => (string) ($user->secondary_contact ?? ''),
            'total_invested' => $user->total_invested !== null ? (string) $user->total_invested : '',
            'revenue_generated' => $user->revenue_generated !== null ? (string) $user->revenue_generated : '',
            'was_referred' => (int) ($user->was_referred ? 1 : 0),
            'referred_by' => (string) ($user->referred_by ?? ''),
            'password' => '',
            'password_confirmation' => '',
            'notes' => (string) ($user->notes ?? ''),
        ];

        $this->showEditModal = true;
    }

    public function closeEditModal(bool $skipRender = true): void
    {
        $this->showEditModal = false;
        $this->editingUserId = null;
        $this->cachedEditingUser = null;
        $this->editProfilePhoto = null;
        $this->resetValidation();

        $this->dispatch('member-edit-closed');

        if ($skipRender) {
            $this->skipRender();
        }
    }

    public function verifyEmailForEditModal(): void
    {
        if (! auth()->user()?->isAdmin() || ! $this->editingUserId) {
            return;
        }

        $user = User::find($this->editingUserId);
        if (! $user || $user->hasVerifiedEmail()) {
            return;
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verified_by' => auth()->id(),
        ]);

        $this->cachedEditingUser = null;

        session()->flash('success', "Email verified successfully for {$user->name}.");
    }

    public function triggerDeleteFromModal(): void
    {
        if (! auth()->user()?->isAdmin() || ! $this->editingUserId) {
            return;
        }

        if (auth()->id() === $this->editingUserId) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user = User::find($this->editingUserId);
        if (! $user) {
            return;
        }

        $userId = $user->id;
        $userName = $user->name;
        $userEmail = $user->email;
        $userAvatar = $user->getProfilePhotoUrl();

        $this->closeEditModal(false);
        $this->dispatch('open-delete-prompt', id: $userId, name: $userName, email: $userEmail, avatar: $userAvatar);
    }

    public function saveMemberEdit(): void
    {
        if (! auth()->user()?->isAdmin()) {
            session()->flash('error', 'You do not have permission to edit members.');
            return;
        }

        if (! $this->editingUserId) {
            return;
        }

        $rules = [
            'editForm.full_name' => 'required|string|max:255',
            'editForm.email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->editingUserId),
            ],
            'editForm.phone' => 'required|string|max:20',
            'editForm.gender' => 'required|in:male,female,other',
            'editForm.primary_country' => 'required|string|max:100',
            'editForm.preference_sector' => 'nullable|string|max:255',
            'editForm.organization' => 'nullable|string|max:255',
            'editForm.designation' => 'nullable|string|max:255',
            'editForm.account_status' => 'required|in:free,core,advanced,institutional',
            'editForm.status' => 'required|in:active,inactive',
            'editForm.account_level' => 'nullable|in:emerald,ruby,diamond,',
            'editForm.public_profile' => 'nullable|in:true,false',
            'editForm.primary_contact' => 'nullable|string|max:255',
            'editForm.secondary_contact' => 'nullable|string|max:255',
            'editForm.total_invested' => 'nullable|numeric|min:0',
            'editForm.revenue_generated' => 'nullable|numeric|min:0',
            'editForm.was_referred' => 'required|in:0,1',
            'editForm.referred_by' => 'nullable|string|max:255|required_if:editForm.was_referred,1',
            'editProfilePhoto' => 'nullable|image|max:3072',
            'editForm.password' => 'nullable|string|min:8|same:editForm.password_confirmation',
            'editForm.password_confirmation' => 'nullable|string',
            'editForm.notes' => 'nullable|string|max:5000',
        ];

        if (auth()->user()?->role === 'superadmin') {
            $rules['editForm.role'] = 'nullable|in:investor,admin,superadmin';
        }

        $messages = [
            'editForm.full_name.required' => 'The full name field is required.',
            'editForm.email.required' => 'The email address is required.',
            'editForm.email.unique' => 'This email address is already in use.',
            'editForm.phone.required' => 'The phone number is required.',
            'editForm.gender.required' => 'Please select a gender.',
            'editForm.primary_country.required' => 'Please select a country.',
            'editForm.account_status.required' => 'Please select an account tier.',
            'editForm.status.required' => 'Please select an account mode.',
            'editForm.referred_by.required_if' => 'Please enter who referred this member.',
            'editForm.password.min' => 'The password must be at least 8 characters.',
            'editForm.password.same' => 'The password confirmation does not match.',
            'editProfilePhoto.image' => 'The profile photo must be an image.',
            'editProfilePhoto.max' => 'The profile photo must not exceed 3MB.',
        ];

        $this->validate($rules, $messages);

        $user = User::find($this->editingUserId);
        if (! $user) {
            session()->flash('error', 'Member not found.');
            $this->closeEditModal();
            return;
        }

        $updateData = [
            'name' => $this->editForm['full_name'],
            'email' => $this->editForm['email'],
            'phone' => $this->editForm['phone'],
            'gender' => $this->editForm['gender'],
            'account_status' => strtolower($this->editForm['account_status']),
            'status' => strtolower($this->editForm['status']),
            'public_profile' => $this->editForm['public_profile'] === 'true' ? 'true' : 'false',
            'level' => ! empty($this->editForm['account_level']) ? $this->editForm['account_level'] : null,
            'company_name' => $this->editForm['organization'] ?: null,
            'designation' => $this->editForm['designation'] ?: null,
            'primary_contact' => $this->editForm['primary_contact'] ?: null,
            'secondary_contact' => $this->editForm['secondary_contact'] ?: null,
            'primary_country' => $this->editForm['primary_country'],
            'preference_sector' => $this->editForm['preference_sector'] ?: null,
            'was_referred' => (int) $this->editForm['was_referred'] === 1,
            'referred_by' => ((int) $this->editForm['was_referred'] === 1) ? $this->editForm['referred_by'] : null,
            'total_invested' => ($this->editForm['total_invested'] !== '' && $this->editForm['total_invested'] !== null) ? (float) $this->editForm['total_invested'] : null,
            'revenue_generated' => ($this->editForm['revenue_generated'] !== '' && $this->editForm['revenue_generated'] !== null) ? (float) $this->editForm['revenue_generated'] : null,
            'notes' => $this->editForm['notes'] ?: null,
        ];

        if (auth()->user()?->role === 'superadmin' && ! empty($this->editForm['role'])) {
            $updateData['role'] = $this->editForm['role'];
        }

        $user->update($updateData);

        // Profile photo upload
        if ($this->editProfilePhoto) {
            $user->clearMediaCollection('profile_photo');
            $user->addMedia($this->editProfilePhoto)->toMediaCollection('profile_photo');
            $this->editProfilePhoto = null;
        }

        // Password change
        if (! empty($this->editForm['password'])) {
            $user->update([
                'password' => Hash::make($this->editForm['password']),
            ]);
        }

        $userName = $user->name;
        $this->closeEditModal(false);
        $this->updateCounts();

        $this->dispatch('member-edit-saved');
        session()->flash('success', "Member '{$userName}' details updated successfully.");
    }
}
