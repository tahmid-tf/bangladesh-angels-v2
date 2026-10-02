<div>
    <!-- Header -->
    <div class="bg-white rounded-xl shadow p-4 md:p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#0a5554]/10 text-[#0a5554]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </span>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">Member Activity Notifications</h1>
                </div>
                <p class="text-sm text-gray-600 mt-1">
                    Track member interest, investment signals, and diligence requests across all portfolio and investment deals.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                @if (($stats['unread'] ?? 0) > 0)
                    <button type="button" 
                            wire:click="markAllAsRead" 
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-[#0a5554] text-white text-xs font-semibold hover:bg-[#084646] transition-colors shadow-sm disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Mark all as read</span>
                    </button>
                @endif
                <a href="{{ route('admin.investments') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-gray-300 text-gray-700 bg-white text-xs font-semibold hover:bg-gray-50 transition-colors">
                    <span>&larr; Investments deals list</span>
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards (Live reactive values) -->
        <dl class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                <dt class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Total Activities</dt>
                <dd class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total']) }}</dd>
            </div>
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3">
                <dt class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Unread Alerts</dt>
                <dd class="text-2xl font-bold text-amber-900 mt-1">{{ number_format($stats['unread']) }}</dd>
            </div>
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3">
                <dt class="text-xs font-semibold text-emerald-800 uppercase tracking-wide">Viewed / Read</dt>
                <dd class="text-2xl font-bold text-emerald-900 mt-1">{{ number_format($stats['read']) }}</dd>
            </div>
            <div class="rounded-lg bg-teal-50 border border-teal-200 p-3">
                <dt class="text-xs font-semibold text-teal-800 uppercase tracking-wide">Active Deals</dt>
                <dd class="text-2xl font-bold text-teal-900 mt-1">{{ number_format($stats['deals_count']) }}</dd>
            </div>
        </dl>
    </div>

    <!-- Filters & Search Bar (Livewire Reactivity) -->
    <div class="bg-white rounded-xl shadow p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Status Tabs -->
            <div class="inline-flex rounded-lg border border-gray-200 p-1 bg-gray-50 self-start md:self-auto">
                <button type="button"
                        wire:click="setStatus('all')"
                        class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all {{ $status === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    All ({{ $stats['total'] }})
                </button>
                <button type="button"
                        wire:click="setStatus('unread')"
                        class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all {{ $status === 'unread' ? 'bg-white text-amber-900 shadow-sm font-bold' : 'text-gray-600 hover:text-amber-800' }}">
                    Unread ({{ $stats['unread'] }})
                </button>
                <button type="button"
                        wire:click="setStatus('read')"
                        class="px-3 py-1.5 rounded-md text-xs font-semibold transition-all {{ $status === 'read' ? 'bg-white text-emerald-900 shadow-sm font-bold' : 'text-gray-600 hover:text-emerald-800' }}">
                    Read ({{ $stats['read'] }})
                </button>
            </div>

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center gap-2.5 flex-1 justify-end">
                <!-- Loading indicator -->
                <div wire:loading class="text-xs text-[#0a5554] flex items-center gap-1.5 font-medium pr-1">
                    <svg class="animate-spin h-3.5 w-3.5 text-[#0a5554]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Filtering...</span>
                </div>

                <!-- Filter by Deal -->
                <select wire:model.live="dealId" 
                        class="text-xs border-gray-300 rounded-lg shadow-sm focus:border-[#0a5554] focus:ring-[#0a5554] py-1.5 px-2.5 bg-white text-gray-700 cursor-pointer">
                    <option value="">All Companies / Deals</option>
                    @foreach ($dealsList as $d)
                        <option value="{{ $d->id }}">{{ $d->title }}</option>
                    @endforeach
                </select>

                <!-- Filter by Activity Type -->
                <select wire:model.live="type" 
                        class="text-xs border-gray-300 rounded-lg shadow-sm focus:border-[#0a5554] focus:ring-[#0a5554] py-1.5 px-2.5 bg-white text-gray-700 cursor-pointer">
                    <option value="">All Activity Types</option>
                    <option value="interested">Expressed Interest</option>
                    <option value="invest">Invest Intent</option>
                    <option value="review">Due Diligence / Review</option>
                </select>

                <!-- Search Input -->
                <div class="relative min-w-[200px]">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           wire:keydown.enter="applySearch"
                           placeholder="Search member or company..." 
                           class="w-full text-xs pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg shadow-sm focus:border-[#0a5554] focus:ring-[#0a5554]">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Per Page selector -->
                <select wire:model.live="perPage" 
                        class="text-xs border-gray-300 rounded-lg shadow-sm focus:border-[#0a5554] focus:ring-[#0a5554] py-1.5 px-2 bg-white text-gray-700 cursor-pointer" 
                        title="Items per page">
                    <option value="10">10 / page</option>
                    <option value="20">20 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                </select>

                <button type="button" 
                        wire:click="applySearch" 
                        class="px-3 py-1.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold rounded-lg transition-colors shadow-xs">
                    Filter
                </button>

                @if ($dealId !== '' || $type !== '' || $search !== '' || $status !== 'all' || $perPage !== 20)
                    <button type="button" 
                            wire:click="resetFilters" 
                            class="px-2.5 py-1.5 text-xs text-gray-500 hover:text-gray-800 underline font-medium">
                        Reset
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left notif-table">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">STATUS</th>
                        <th class="px-4 py-3">MEMBER</th>
                        <th class="px-4 py-3">TARGET COMPANY</th>
                        <th class="px-4 py-3">ACTIVITY SIGNAL</th>
                        <th class="px-4 py-3 whitespace-nowrap">RECORDED</th>
                        <th class="px-4 py-3 whitespace-nowrap">ADMIN REVIEW</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="notifications-table-body">
                    @forelse ($notifications as $notification)
                        @php
                            $isViewed = $notification->isViewed();
                            $deal = $notification->deal;
                            $user = $notification->user;
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition-colors {{ !$isViewed ? 'bg-amber-50/20' : '' }}" 
                            wire:key="notification-row-{{ $notification->id }}" 
                            id="notification-row-{{ $notification->id }}">
                            <!-- Status -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <span class="status-indicator-dot w-2 h-2 rounded-full {{ $isViewed ? 'bg-emerald-500' : 'bg-amber-500 ring-2 ring-amber-200 animate-pulse' }}" title="{{ $isViewed ? 'Viewed' : 'Unread' }}"></span>
                                    <span class="status-indicator-label text-xs font-bold {{ $isViewed ? 'text-emerald-700' : 'text-amber-700' }}">
                                        {{ $isViewed ? 'Read' : 'Unread' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Member -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-800 font-bold text-xs flex items-center justify-center shrink-0 border border-emerald-200">
                                        {{ strtoupper(substr($user?->name ?? 'M', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-900 text-xs truncate">{{ $user?->name ?? 'Anonymous / Unknown' }}</p>
                                        <p class="text-[11px] text-gray-500 truncate">{{ $user?->email ?? 'No email' }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Target Company (Clean, highlighted badge with no squished boxes) -->
                            <td class="px-4 py-3">
                                @if ($deal)
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $deal->getLogoUrl() }}" alt="{{ $deal->title }}" class="w-8 h-8 rounded-full object-cover shrink-0 ring-1 ring-gray-200">
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.deals.member-activity', $deal) }}" 
                                               class="notif-company-badge"
                                               title="View {{ $deal->title }} member activity">
                                                <span class="truncate">{{ $deal->title }}</span>
                                                <span class="arrow-icon" aria-hidden="true">&nearr;</span>
                                            </a>
                                            <p class="text-[11px] text-gray-500 mt-1 truncate">
                                                {{ $deal->sector ?: 'General' }} &bull; <span class="capitalize">{{ $deal->type }}</span>
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Deal #{{ $notification->deal_id }} (Archived)</span>
                                @endif
                            </td>

                            <!-- Activity Signal -->
                            <td class="px-4 py-3">
                                <div class="max-w-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ 
                                        $notification->type === 'interested' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : (
                                        $notification->type === 'invest' ? 'bg-teal-100 text-teal-800 border border-teal-200' : 'bg-violet-100 text-violet-800 border border-violet-200'
                                    ) }}">
                                        {{ $notification->type_label }}
                                    </span>
                                    <p class="text-xs text-gray-700 mt-1 leading-snug">
                                        <span class="font-semibold text-gray-900">{{ $user?->name ?? 'Member' }}</span> 
                                        <span class="text-gray-600">{{ $notification->action_message }}</span> 
                                        <strong class="text-gray-800">{{ $deal?->title ?? 'this deal' }}</strong>
                                    </p>
                                </div>
                            </td>

                            <!-- Recorded Time -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="text-xs font-semibold text-gray-800">{{ $notification->created_at?->format('M j, Y') }}</p>
                                <p class="text-[11px] text-gray-500">
                                    {{ $notification->created_at?->format('g:i A') }}
                                    @if ($notification->created_at && !$notification->created_at->isFuture())
                                        <span class="text-gray-400">({{ $notification->created_at->diffForHumans() }})</span>
                                    @endif
                                </p>
                            </td>

                            <!-- Admin Review -->
                            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap review-meta-cell">
                                @if ($isViewed)
                                    <div class="flex items-center gap-1.5 text-emerald-700 font-semibold">
                                        <svg class="w-3.5 h-3.5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>Viewed</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-0.5">
                                        {{ $notification->viewed_at ? $notification->viewed_at->format('M j, Y · g:i A') : 'Recorded' }}
                                    </p>
                                    @if ($notification->viewer)
                                        <p class="text-[11px] text-gray-400">by {{ $notification->viewer->name }}</p>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                        Pending review
                                    </span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <!-- Toggle Read/Unread Livewire Button -->
                                    <button type="button"
                                            wire:click="toggleViewed({{ $notification->id }})"
                                            wire:loading.attr="disabled"
                                            class="notif-toggle-btn group"
                                            data-viewed="{{ $isViewed ? '1' : '0' }}"
                                            title="{{ $isViewed ? 'Click to mark as unread' : 'Click to mark as read' }}">
                                        <span class="tick-icon-wrapper">
                                            <svg class="w-2.5 h-2.5 {{ $isViewed ? 'block' : 'hidden group-hover:block' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                        <span class="btn-text">{{ $isViewed ? 'Viewed' : 'Mark read' }}</span>
                                    </button>

                                    <!-- Link to Deal Member Activity -->
                                    @if ($deal)
                                        <a href="{{ route('admin.deals.member-activity', $deal) }}" 
                                           class="notif-activity-btn"
                                           title="Go to {{ $deal->title }} member activity">
                                            <span>Activity</span>
                                            <span aria-hidden="true">&rarr;</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <div class="max-w-xs mx-auto">
                                    <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="font-semibold text-gray-700">No activity notifications found</p>
                                    <p class="text-xs text-gray-500 mt-1">No notifications match your current filter settings.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($notifications->hasPages())
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
                {{ $notifications->links() }}
            </div>
        @elseif ($notifications->total() > 0)
            <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-500">
                <span>Showing all <strong class="text-gray-800">{{ $notifications->total() }}</strong> notifications (Page 1 of 1 &bull; {{ $perPage }} per page)</span>
                <span class="text-[11px] text-gray-400">Pagination buttons automatically activate when records exceed the page size.</span>
            </div>
        @endif
    </div>
</div>
