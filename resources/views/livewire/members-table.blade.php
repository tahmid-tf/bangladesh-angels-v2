<div x-data="{
    confirmingDelete: false,
    deleteUserId: null,
    deleteUserName: '',
    deleteUserEmail: '',
    deleteUserAvatar: '',
    isDeleting: false,
    openDelete(id, name, email, avatar) {
        this.deleteUserId = id;
        this.deleteUserName = name;
        this.deleteUserEmail = email;
        this.deleteUserAvatar = avatar;
        this.confirmingDelete = true;
    },
    closeDelete() {
        if (this.isDeleting) return;
        this.confirmingDelete = false;
        setTimeout(() => {
            if (!this.confirmingDelete) {
                this.deleteUserId = null;
                this.deleteUserName = '';
                this.deleteUserEmail = '';
                this.deleteUserAvatar = '';
            }
        }, 200);
    },
    async executeDelete() {
        if (!this.deleteUserId || this.isDeleting) return;
        this.isDeleting = true;
        try {
            await $wire.deleteUser(this.deleteUserId);
            this.confirmingDelete = false;
            this.isDeleting = false;
            this.deleteUserId = null;
            this.deleteUserName = '';
            this.deleteUserEmail = '';
            this.deleteUserAvatar = '';
        } catch (e) {
            console.error('Delete member error', e);
            this.isDeleting = false;
        }
    }
}"
@member-deleted.window="confirmingDelete = false; isDeleting = false; deleteUserId = null;">
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Scoped overrides to prevent admin-dashboard.css aggressive table rules from breaking member actions & badges */
        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn,
        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn:has(svg),
        .members-custom-table td :is(a, button).member-action-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 5px !important;
            width: auto !important;
            min-width: 0 !important;
            height: auto !important;
            min-height: 29px !important;
            padding: 5px 12px !important;
            border-radius: 7px !important;
            font-size: 0.75rem !important; /* 12px */
            font-weight: 700 !important;
            line-height: 1 !important;
            place-items: unset !important;
            transform: none !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            cursor: pointer !important;
            transition: all 150ms ease !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        }

        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn:hover,
        .members-custom-table td :is(a, button).member-action-btn:hover {
            transform: translateY(-1px) !important;
            text-decoration: none !important;
        }

        /* SVG Icons inside member action buttons: strictly transparent fill and currentColor stroke */
        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn svg,
        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn:has(svg) svg,
        .members-custom-table td :is(a, button).member-action-btn svg,
        svg.member-svg-icon {
            width: 13px !important;
            height: 13px !important;
            min-width: 13px !important;
            min-height: 13px !important;
            fill: none !important;
            stroke: currentColor !important;
            display: inline-block !important;
            vertical-align: middle !important;
            transform: none !important;
        }

        /* Member Action Row (strictly single-line flex) */
        .member-actions-row {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            flex-wrap: nowrap !important;
            white-space: nowrap !important;
        }

        /* Approve Button (solid brand teal background with crisp white text & white checkmark) */
        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-approve,
        .members-custom-table :is(a, button).member-action-approve {
            background: #0a5554 !important;
            background-color: #0a5554 !important;
            border: 1px solid #084646 !important;
            color: #ffffff !important;
            box-shadow: 0 1px 2px rgba(10, 85, 84, 0.25) !important;
            font-weight: 700 !important;
        }

        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-approve:hover,
        .members-custom-table :is(a, button).member-action-approve:hover {
            background: #073f3e !important;
            background-color: #073f3e !important;
            border-color: #052928 !important;
            color: #ffffff !important;
            transform: translateY(-1px) !important;
        }

        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-approve svg,
        .members-custom-table :is(a, button).member-action-approve svg {
            fill: none !important;
            stroke: #ffffff !important;
        }

        /* Edit Button (clean neutral gray bordered button) */
        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-edit,
        .members-custom-table a.member-action-edit {
            color: #374151 !important;
            background: #ffffff !important;
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        }

        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-edit:hover,
        .members-custom-table a.member-action-edit:hover {
            background: #f3f4f6 !important;
            background-color: #f3f4f6 !important;
            border-color: #9ca3af !important;
            color: #111827 !important;
        }

        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-edit svg,
        .members-custom-table a.member-action-edit svg {
            fill: none !important;
            stroke: #4b5563 !important;
        }

        /* Delete Button (crisp danger red bordered button, never solid green) */
        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-delete,
        .members-custom-table :is(a, button).member-action-delete {
            color: #dc2626 !important;
            background: #ffffff !important;
            background-color: #ffffff !important;
            border: 1px solid #fecaca !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        }

        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-delete:hover,
        .members-custom-table :is(a, button).member-action-delete:hover {
            background: #fef2f2 !important;
            background-color: #fef2f2 !important;
            border-color: #f87171 !important;
            color: #b91c1c !important;
        }

        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-delete svg,
        .members-custom-table :is(a, button).member-action-delete svg {
            fill: none !important;
            stroke: #dc2626 !important;
        }

        /* Disabled state during Livewire actions */
        .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn:disabled,
        .members-custom-table td :is(a, button).member-action-btn:disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
            transform: none !important;
        }

        /* Status Pills (single-line horizontal badge with dot) */
        .member-status-pill {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            padding: 4px 10px !important;
            border-radius: 9999px !important;
            font-size: 0.72rem !important; /* ~11.5px */
            font-weight: 700 !important;
            line-height: 1.25 !important;
            white-space: nowrap !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03) !important;
        }

        .member-status-pill--pending {
            background: #fef3c7 !important;
            color: #92400e !important;
            border: 1px solid #fcd34d !important;
        }

        .member-status-pill--active {
            background: #ecfdf5 !important;
            color: #065f46 !important;
            border: 1px solid #a7f3d0 !important;
        }

        .member-status-pill--free {
            background: #f3f4f6 !important;
            color: #4b5563 !important;
            border: 1px solid #e5e7eb !important;
        }

        .member-status-pill--inactive {
            background: #fef2f2 !important;
            color: #991b1b !important;
            border: 1px solid #fecaca !important;
        }

        /* Member Status Select Dropdown in table */
        .admin-page:not(.admin-page--dashboard) table.members-custom-table td select.member-status-select,
        .members-custom-table td select.member-status-select,
        select.member-status-select {
            display: inline-flex !important;
            align-items: center !important;
            min-height: 27px !important;
            height: 27px !important;
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            padding-left: 20px !important;
            padding-right: 22px !important;
            border-radius: 9999px !important;
            font-size: 0.72rem !important; /* ~11.5px */
            font-weight: 700 !important;
            line-height: 1.2 !important;
            cursor: pointer !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
            background-image: none !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            transition: all 150ms ease !important;
        }

        .member-status-select--free {
            background-color: #f3f4f6 !important;
            color: #374151 !important;
            border: 1px solid #d1d5db !important;
        }
        .member-status-select--free:hover {
            background-color: #e5e7eb !important;
            border-color: #9ca3af !important;
        }

        .member-status-select--core {
            background-color: #ecfdf5 !important;
            color: #065f46 !important;
            border: 1px solid #a7f3d0 !important;
        }
        .member-status-select--core:hover {
            background-color: #d1fae5 !important;
            border-color: #6ee7b7 !important;
        }

        .member-status-select--advanced {
            background-color: #f0fdfa !important;
            color: #0f766e !important;
            border: 1px solid #99f6e4 !important;
        }
        .member-status-select--advanced:hover {
            background-color: #ccfbf1 !important;
            border-color: #5eead4 !important;
        }

        .member-status-select--institutional {
            background-color: #faf5ff !important;
            color: #6b21a8 !important;
            border: 1px solid #e9d5ff !important;
        }
        .member-status-select--institutional:hover {
            background-color: #f3e8ff !important;
            border-color: #d8b4fe !important;
        }
    </style>

    @session('success')
        <div x-data="{ show: true }" x-show="show" class="mx-4 md:mx-6 mt-4 p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ $value }}</span>
            </div>
            <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-800 font-bold ml-3 text-sm px-1.5 py-0.5 rounded hover:bg-emerald-100 transition">✕</button>
        </div>
    @endsession
    @session('error')
        <div x-data="{ show: true }" x-show="show" class="mx-4 md:mx-6 mt-4 p-3.5 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>{{ $value }}</span>
            </div>
            <button type="button" @click="show = false" class="text-red-500 hover:text-red-800 font-bold ml-3 text-sm px-1.5 py-0.5 rounded hover:bg-red-100 transition">✕</button>
        </div>
    @endsession

    <!-- Filters and Search -->
    <div class="p-4 md:p-6 bg-white shadow mt-4">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex flex-wrap gap-2">
                <a wire:click="setFilter('all')" class="px-4 py-2 cursor-pointer transition-colors {{ $filter === 'all' ? 'bg-green-100 text-green-700 font-semibold rounded-lg' : 'text-gray-500 hover:text-green-700' }}">
                    All <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $filter === 'all' ? 'bg-green-200 text-green-800 font-bold' : 'bg-gray-200 text-gray-700' }}">{{ $allCount }}</span>
                </a>
                <a wire:click="setFilter('active')" class="px-4 py-2 cursor-pointer transition-colors {{ $filter === 'active' ? 'bg-green-100 text-green-700 font-semibold rounded-lg' : 'text-gray-500 hover:text-green-700' }}">
                    Active <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $filter === 'active' ? 'bg-green-200 text-green-800 font-bold' : 'bg-gray-200 text-gray-700' }}">{{ $activeCount }}</span>
                </a>
                <a wire:click="setFilter('inactive')" class="px-4 py-2 cursor-pointer transition-colors {{ $filter === 'inactive' ? 'bg-green-100 text-green-700 font-semibold rounded-lg' : 'text-gray-500 hover:text-green-700' }}">
                    Inactive <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $filter === 'inactive' ? 'bg-green-200 text-green-800 font-bold' : 'bg-gray-200 text-gray-700' }}">{{ $inactiveCount }}</span>
                </a>
                <a wire:click="setFilter('pending')" class="px-4 py-2 cursor-pointer transition-colors {{ $filter === 'pending' ? 'bg-green-100 text-green-700 font-semibold rounded-lg' : 'text-gray-500 hover:text-green-700' }}">
                    Pending Approval <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $filter === 'pending' ? 'bg-green-200 text-green-800 font-bold' : 'bg-gray-200 text-gray-700' }}">{{ $pendingCount }}</span>
                </a>
                <a wire:click="setFilter('featured')" class="px-4 py-2 cursor-pointer transition-colors {{ $filter === 'featured' ? 'bg-green-100 text-green-700 font-semibold rounded-lg' : 'text-gray-500 hover:text-green-700' }}">
                    Featured Members <span class="ml-1 px-2 py-0.5 rounded-full text-xs {{ $filter === 'featured' ? 'bg-green-200 text-green-800 font-bold' : 'bg-gray-200 text-gray-700' }}">{{ $featuredCount }}</span>
                </a>
            </div>

            <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto relative items-center">
                <!-- Loading Indicator -->
                <div wire:loading wire:target="searchUsers" class="absolute left-[-40px]">
                    <img src="{{asset('loading.gif')}}" alt="Loading..." class="h-6 w-6">
                </div>
            
                <!-- Search Input -->
                <input type="text" 
                    wire:model.defer="search"
                    wire:keydown.enter="searchUsers"
                    placeholder="Search members..."
                    class="border-gray-300 rounded-lg shadow-sm px-4 py-2 md:w-64 focus:ring-green-500 focus:border-green-500 text-sm">
            
                <!-- Search Button -->
                <button wire:click="searchUsers"
                    class="px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:ring focus:ring-green-300 transition duration-200 text-sm">
                    Search
                </button>
            </div>
            
        </div>

        @if($filter === 'featured')
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-800 mb-1">Add members to featured</h3>
                <p class="text-xs text-gray-600 mb-3">Search approved members who are not yet shown on the public <span class="font-medium">/our-investors</span> page. For each featured member you can add an optional testimonial and choose whether to show it publicly.</p>
                <div class="flex flex-col sm:flex-row gap-2 max-w-2xl">
                    <input type="text"
                        wire:model.live.debounce.400ms="featuredPickerSearch"
                        placeholder="Name, email, designation, or company…"
                        class="flex-1 border-gray-300 rounded-lg shadow-sm px-4 py-2 focus:ring-green-500 focus:border-green-500 text-sm">
                </div>
                @if(strlen(trim($featuredPickerSearch)) > 0)
                    <ul class="mt-4 space-y-2 max-w-2xl">
                        @forelse($featurePickerResults as $pick)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2 px-3 bg-gray-50 rounded-lg border border-gray-100 text-sm">
                                <div>
                                    <span class="font-medium text-gray-800">{{ $pick->name }}</span>
                                    <span class="text-gray-500 text-xs block sm:inline sm:ml-2">{{ $pick->email }}</span>
                                </div>
                                <button type="button"
                                    wire:click="addToFeatured({{ $pick->id }})"
                                    wire:loading.attr="disabled"
                                    class="shrink-0 px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700">
                                    Add to featured
                                </button>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500 py-2">No matching members, or they are already featured.</li>
                        @endforelse
                    </ul>
                @endif
            </div>
        @endif
    </div>

    <!-- Members Table -->
    <div class="p-4 md:p-6 bg-white shadow mt-4 overflow-x-auto">
        <div class="min-w-[320px] md:w-full">
            <table class="w-full border-collapse text-left text-sm members-custom-table">
                <thead>
                    <tr class="bg-gray-100 text-xs font-semibold text-gray-600 uppercase border-b border-gray-200">
                        <th class="pl-4 pr-2 md:px-6 py-3.5 whitespace-nowrap">Member</th>
                        <th class="hidden md:table-cell px-6 py-3.5 whitespace-nowrap">Designation</th>
                        <th class="hidden lg:table-cell px-6 py-3.5 whitespace-nowrap">Organization</th>
                        <th class="hidden md:table-cell px-6 py-3.5 whitespace-nowrap">Phone</th>
                        <th class="hidden lg:table-cell px-6 py-3.5 whitespace-nowrap">Email verified</th>
                        <th class="hidden md:table-cell px-6 py-3.5 whitespace-nowrap">
                            @if($filter === 'pending')
                                Registered
                            @else
                                Last Renewed
                            @endif
                        </th>
                        <th class="pl-2 pr-4 md:px-6 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-4 md:px-6 py-3.5 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50/80 transition-colors" wire:key="member-row-{{ $user->id }}">
                            <!-- Member Profile -->
                            <td class="pl-4 pr-2 md:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->getProfilePhotoUrl() }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover shrink-0 ring-1 ring-gray-200">
                                    <div class="min-w-[130px]">
                                        <p class="font-bold text-gray-900 text-xs inline-flex items-center gap-1.5 flex-wrap">
                                            {{ $user->name }}
                                            @if($user->id === auth()->id())
                                                <span class="text-[10px] font-normal px-1.5 py-0.2 rounded-full bg-gray-100 text-gray-600 border border-gray-200">You</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500 truncate mt-0.5">{{ $user->email }}</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            @if($filter === 'pending')
                                                Location: {{ $user->primary_country ?? '-' }}
                                            @else
                                                Joined: {{ $user->joining_date ?? $user->created_at->format('M d, Y') }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Designation -->
                            <td class="hidden md:table-cell px-6 py-4 text-xs text-gray-700">
                                {{ $user->designation ?? '-' }}
                            </td>

                            <!-- Organization -->
                            <td class="hidden lg:table-cell px-6 py-4 text-xs font-medium text-gray-800">
                                {{ $user->company_name ?? '-' }}
                            </td>

                            <!-- Phone -->
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-xs text-gray-600">
                                {{ $user->phone ?? '-' }}
                            </td>

                            <!-- Email verified -->
                            <td class="hidden lg:table-cell px-6 py-4 text-xs text-gray-600 whitespace-nowrap">
                                @if($user->hasVerifiedEmail())
                                    <div class="inline-flex items-center gap-1 text-emerald-700 font-bold text-xs">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Yes</span>
                                    </div>
                                    <div class="mt-0.5 text-gray-500 leading-tight">
                                        <p class="whitespace-nowrap text-[11px] font-medium text-gray-700">{{ $user->email_verified_at->format('M j, Y · g:i A') }}</p>
                                        @if($user->email_verified_by && $user->emailVerifiedByAdmin)
                                            <p class="text-[11px] text-gray-400 truncate">by {{ $user->emailVerifiedByAdmin->name }}</p>
                                        @else
                                            <p class="text-[11px] text-gray-400">Self-service link</p>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 whitespace-nowrap border border-amber-200">Unverified</span>
                                    <div class="mt-1.5">
                                        <button type="button"
                                            wire:click="verifyEmail({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            class="member-action-btn member-action-approve"
                                            style="min-height: 24px !important; padding: 2px 8px !important; font-size: 11px !important;">
                                            Verify
                                        </button>
                                    </div>
                                @endif
                            </td>

                            <!-- Registered / Last Renewed -->
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                                @if($filter === 'pending')
                                    <div class="flex flex-col">
                                        <span class="text-xs font-semibold text-gray-800">
                                            {{ $user->created_at->format('M j, Y') }}
                                        </span>
                                        <span class="text-[11px] text-gray-400 mt-0.5">
                                            {{ $user->created_at->diffForHumans() }}
                                        </span>
                                        @if($user->approved_at)
                                        <span class="text-[11px] text-emerald-600 mt-1 inline-flex items-center font-medium">
                                            <svg class="w-3 h-3 mr-1 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Approved {{ $user->approved_at->diffForHumans() }}
                                        </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-700">
                                        {{ $user->last_renewed_at ? \Carbon\Carbon::parse($user->last_renewed_at)->format('M j, Y') : '-' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Status Badge / Dropdown -->
                            <td class="pl-2 pr-4 md:px-6 py-4 whitespace-nowrap">
                                @if($filter === 'pending' || !$user->is_approved)
                                    <span class="member-status-pill member-status-pill--pending">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 ring-2 ring-amber-200 animate-pulse shrink-0"></span>
                                        <span>Pending Approval</span>
                                    </span>
                                @else
                                    @php
                                        $currentStatus = strtolower((string) ($user->account_status ?? 'free'));
                                        $dotColor = match($currentStatus) {
                                            'core' => 'bg-emerald-500',
                                            'advanced' => 'bg-teal-500',
                                            'institutional' => 'bg-purple-500',
                                            default => 'bg-gray-400',
                                        };
                                        $statusModifierClass = match($currentStatus) {
                                            'core' => 'member-status-select--core',
                                            'advanced' => 'member-status-select--advanced',
                                            'institutional' => 'member-status-select--institutional',
                                            default => 'member-status-select--free',
                                        };
                                    @endphp
                                    <div x-data="{ updating: false }" class="inline-flex items-center gap-1.5" wire:key="status-cell-{{ $user->id }}-{{ $currentStatus }}">
                                        <div class="relative inline-flex items-center">
                                            <!-- Status dot -->
                                            <span class="pointer-events-none absolute left-2.5 w-1.5 h-1.5 rounded-full {{ $dotColor }} shrink-0 z-10"></span>

                                            <!-- Interactive Tier Select -->
                                            <select 
                                                x-bind:disabled="updating"
                                                @change="updating = true; $wire.updateAccountStatus({{ $user->id }}, $event.target.value).finally(() => { updating = false })"
                                                class="member-status-select {{ $statusModifierClass }}"
                                                title="Change Account Tier">
                                                <option value="free" @selected($currentStatus === 'free') class="bg-white text-gray-800 font-medium">Free</option>
                                                <option value="core" @selected($currentStatus === 'core') class="bg-white text-gray-800 font-medium">Core</option>
                                                <option value="advanced" @selected($currentStatus === 'advanced') class="bg-white text-gray-800 font-medium">Advanced</option>
                                                <option value="institutional" @selected($currentStatus === 'institutional') class="bg-white text-gray-800 font-medium">Institutional</option>
                                            </select>

                                            <!-- Dropdown Chevron Caret -->
                                            <span class="pointer-events-none absolute right-2 text-current opacity-60">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </span>
                                        </div>

                                        <!-- Saving Spinner -->
                                        <span x-show="updating" x-cloak class="shrink-0 text-emerald-600">
                                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                <div class="member-actions-row">
                                    @if($filter === 'pending' && !$user->is_approved)
                                        <button type="button"
                                            wire:click="approveUser({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            class="member-action-btn member-action-approve"
                                            title="Approve member">
                                            <svg class="member-svg-icon w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Approve</span>
                                        </button>
                                    @endif

                                    <a href="{{ route('member.edit', $user->id) }}" 
                                       class="member-action-btn member-action-edit"
                                       title="Edit member details">
                                        <svg class="member-svg-icon w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Edit</span>
                                    </a>

                                    @if($filter === 'featured')
                                        <button type="button"
                                            wire:click="removeFromFeatured({{ $user->id }})"
                                            wire:loading.attr="disabled"
                                            class="member-action-btn member-action-edit text-amber-700"
                                            title="Remove from featured">
                                            <span>Remove</span>
                                        </button>
                                    @endif

                                    @if($user->id != auth()->id())
                                        <button type="button"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                            data-user-email="{{ $user->email }}"
                                            data-user-avatar="{{ $user->getProfilePhotoUrl() }}"
                                            @click="openDelete($el.dataset.userId, $el.dataset.userName, $el.dataset.userEmail, $el.dataset.userAvatar)"
                                            class="member-action-btn member-action-delete"
                                            title="Delete member">
                                            <svg class="member-svg-icon w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($filter === 'featured')
                            <tr class="border-t bg-gray-50/80">
                                <td colspan="8" class="px-4 md:px-6 py-4">
                                    <livewire:featured-testimonial-editor :user-id="$user->id" :wire:key="'featured-testimonial-'.$user->id" />
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-500">
                                <p class="font-semibold text-gray-700">No members found.</p>
                                <p class="text-xs text-gray-400 mt-1">No members match your current filter settings.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-6 px-4 md:px-6">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    <!-- Beautiful Modern Delete Confirmation Modal -->
    <div x-show="confirmingDelete"
         x-cloak
         wire:ignore.self
         class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-[100] flex items-center justify-center"
         style="display: none;"
         role="dialog"
         aria-modal="true"
         aria-labelledby="modal-delete-title"
         @keydown.escape.window="closeDelete()">
        
        <!-- Backdrop with Blur -->
        <div x-show="confirmingDelete"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
             @click="closeDelete()">
        </div>

        <!-- Modal Dialog Card -->
        <div x-show="confirmingDelete"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white rounded-2xl overflow-hidden shadow-2xl border border-gray-100 sm:max-w-md w-full z-10 my-8 mx-auto transform transition-all text-left"
             @click.stop>
            
            <!-- Top Gradient Accent Bar -->
            <div class="h-1.5 w-full bg-gradient-to-r from-red-500 via-rose-500 to-amber-500"></div>

            <!-- Close Button (X) -->
            <button type="button" 
                    @click="closeDelete()" 
                    :disabled="isDeleting"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-1.5 transition-colors focus:outline-none disabled:opacity-40 cursor-pointer"
                    title="Close dialog">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="p-6 sm:p-7">
                <!-- Icon & Header -->
                <div class="flex items-start gap-4">
                    <!-- Danger Icon Badge -->
                    <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-100 text-red-600 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </div>

                    <div class="pt-0.5">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-snug" id="modal-delete-title">
                            Delete Member Account
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Are you sure you want to delete this member?
                        </p>
                    </div>
                </div>

                <!-- Member Preview Card -->
                <div class="mt-4 p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center gap-3">
                    <img :src="deleteUserAvatar" 
                         :alt="deleteUserName" 
                         class="w-10 h-10 rounded-full object-cover ring-2 ring-white shadow-sm shrink-0 bg-gray-200">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs font-bold text-gray-900 truncate" x-text="deleteUserName"></span>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-gray-200 text-gray-600" x-text="'ID #' + deleteUserId"></span>
                        </div>
                        <p class="text-xs text-gray-500 truncate mt-0.5" x-text="deleteUserEmail"></p>
                    </div>
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-end gap-3">
                <button type="button" 
                        @click="closeDelete()" 
                        :disabled="isDeleting"
                        class="px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-xs font-bold hover:bg-gray-100 hover:text-gray-900 transition-colors shadow-sm focus:outline-none disabled:opacity-50 cursor-pointer">
                    Cancel
                </button>
                <button type="button" 
                        @click="executeDelete()" 
                        :disabled="isDeleting"
                        class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 active:bg-red-800 text-white text-xs font-bold transition-all shadow-sm hover:shadow focus:outline-none flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
                    <svg x-show="isDeleting" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isDeleting ? 'Deleting member...' : 'Yes, Delete Member'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
