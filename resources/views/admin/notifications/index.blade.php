@extends('layouts.admin')
@section('page_title', 'Member Activity Notifications | Dashboard')

@section('page_content')
<section class="container mx-auto p-4 md:p-6 max-w-7xl">
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
                <button type="button" 
                        id="table-mark-all-read-btn"
                        onclick="markAllNotificationsAsReadTable()" 
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-[#0a5554] text-white text-xs font-semibold hover:bg-[#084646] transition-colors shadow-sm {{ ($stats['unread'] ?? 0) > 0 ? '' : 'hidden' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Mark all as read</span>
                </button>
                <a href="{{ route('admin.investments') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-gray-300 text-gray-700 bg-white text-xs font-semibold hover:bg-gray-50 transition-colors">
                    <span>← Investments deals list</span>
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <dl class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                <dt class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Total Activities</dt>
                <dd class="text-2xl font-bold text-gray-900 mt-1" id="stat-total">{{ number_format($stats['total']) }}</dd>
            </div>
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3">
                <dt class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Unread Alerts</dt>
                <dd class="text-2xl font-bold text-amber-900 mt-1" id="stat-unread">{{ number_format($stats['unread']) }}</dd>
            </div>
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3">
                <dt class="text-xs font-semibold text-emerald-800 uppercase tracking-wide">Viewed / Read</dt>
                <dd class="text-2xl font-bold text-emerald-900 mt-1" id="stat-read">{{ number_format($stats['read']) }}</dd>
            </div>
            <div class="rounded-lg bg-teal-50 border border-teal-200 p-3">
                <dt class="text-xs font-semibold text-teal-800 uppercase tracking-wide">Active Deals</dt>
                <dd class="text-2xl font-bold text-teal-900 mt-1">{{ number_format($stats['deals_count']) }}</dd>
            </div>
        </dl>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-xl shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Status Tabs -->
            <div class="inline-flex rounded-lg border border-gray-200 p-1 bg-gray-50 self-start md:self-auto">
                <a href="{{ route('admin.notifications.index', array_merge(request()->except(['status', 'page']), ['status' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors {{ ($filters['status'] ?? 'all') === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                    All ({{ $stats['total'] }})
                </a>
                <a href="{{ route('admin.notifications.index', array_merge(request()->except(['status', 'page']), ['status' => 'unread'])) }}"
                   class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors {{ ($filters['status'] ?? '') === 'unread' ? 'bg-white text-amber-900 shadow-sm' : 'text-gray-600 hover:text-amber-800' }}">
                    Unread ({{ $stats['unread'] }})
                </a>
                <a href="{{ route('admin.notifications.index', array_merge(request()->except(['status', 'page']), ['status' => 'read'])) }}"
                   class="px-3 py-1.5 rounded-md text-xs font-semibold transition-colors {{ ($filters['status'] ?? '') === 'read' ? 'bg-white text-emerald-900 shadow-sm' : 'text-gray-600 hover:text-emerald-800' }}">
                    Read ({{ $stats['read'] }})
                </a>
            </div>

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center gap-2.5 flex-1 justify-end">
                <input type="hidden" name="status" value="{{ $filters['status'] ?? 'all' }}">

                <!-- Filter by Deal -->
                <select name="deal_id" onchange="this.form.submit()" class="text-xs border-gray-300 rounded-lg shadow-sm focus:border-[#0a5554] focus:ring-[#0a5554] py-1.5 px-2.5 bg-white text-gray-700">
                    <option value="">All Companies / Deals</option>
                    @foreach ($dealsList as $d)
                        <option value="{{ $d->id }}" {{ (string)($filters['deal_id'] ?? '') === (string)$d->id ? 'selected' : '' }}>
                            {{ $d->title }}
                        </option>
                    @endforeach
                </select>

                <!-- Filter by Activity Type -->
                <select name="type" onchange="this.form.submit()" class="text-xs border-gray-300 rounded-lg shadow-sm focus:border-[#0a5554] focus:ring-[#0a5554] py-1.5 px-2.5 bg-white text-gray-700">
                    <option value="">All Activity Types</option>
                    <option value="interested" {{ ($filters['type'] ?? '') === 'interested' ? 'selected' : '' }}>Expressed Interest</option>
                    <option value="invest" {{ ($filters['type'] ?? '') === 'invest' ? 'selected' : '' }}>Invest Intent</option>
                    <option value="review" {{ ($filters['type'] ?? '') === 'review' ? 'selected' : '' }}>Due Diligence / Review</option>
                </select>

                <!-- Search Input -->
                <div class="relative min-w-[200px]">
                    <input type="text" 
                           name="q" 
                           value="{{ $filters['q'] ?? '' }}" 
                           placeholder="Search member or company..." 
                           class="w-full text-xs pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg shadow-sm focus:border-[#0a5554] focus:ring-[#0a5554]">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <button type="submit" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold rounded-lg transition-colors">
                    Filter
                </button>

                @if (request()->hasAny(['type', 'deal_id', 'q']))
                    <a href="{{ route('admin.notifications.index', ['status' => $filters['status'] ?? 'all']) }}" class="px-2.5 py-1.5 text-xs text-gray-500 hover:text-gray-800 underline">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Notifications Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">Status</th>
                        <th class="px-4 py-3">Member</th>
                        <th class="px-4 py-3">Target Company</th>
                        <th class="px-4 py-3">Activity Signal</th>
                        <th class="px-4 py-3 whitespace-nowrap">Recorded</th>
                        <th class="px-4 py-3">Admin Review</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="notifications-table-body">
                    @forelse ($notifications as $notification)
                        @php
                            $isViewed = $notification->isViewed();
                            $deal = $notification->deal;
                            $user = $notification->user;
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition-colors {{ !$isViewed ? 'bg-amber-50/20' : '' }}" id="notification-row-{{ $notification->id }}">
                            <!-- Status -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="status-indicator-dot w-2.5 h-2.5 rounded-full {{ $isViewed ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse' }}" title="{{ $isViewed ? 'Viewed' : 'Unread' }}"></span>
                                    <span class="status-indicator-label text-xs font-semibold {{ $isViewed ? 'text-emerald-700' : 'text-amber-700' }}">
                                        {{ $isViewed ? 'Read' : 'Unread' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Member -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#0a5554]/10 text-[#0a5554] flex items-center justify-center font-bold text-xs ring-1 ring-[#0a5554]/20 shrink-0">
                                        {{ strtoupper(substr($user?->name ?? 'M', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-900 truncate">{{ $user?->name ?? 'Anonymous / Unknown' }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $user?->email ?? 'No email' }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Company / Deal (Prominently Highlighted!) -->
                            <td class="px-4 py-3">
                                @if ($deal)
                                    <div class="flex items-center gap-2.5">
                                        <img src="{{ $deal->getLogoUrl() }}" alt="" class="w-7 h-7 rounded-full object-cover shrink-0 ring-1 ring-gray-200">
                                        <div class="min-w-0">
                                            <!-- Highlighted Company Badge -->
                                            <a href="{{ route('admin.deals.member-activity', $deal) }}" 
                                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold text-sm text-[#0a5554] bg-[#0a5554]/10 hover:bg-[#0a5554]/20 border border-[#0a5554]/20 transition-all shadow-xs"
                                               title="View {{ $deal->title }} member activity">
                                                <span>{{ $deal->title }}</span>
                                                <svg class="w-3.5 h-3.5 shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                            <p class="text-[11px] text-gray-500 mt-0.5 truncate">{{ $deal->sector ?: 'General' }} · {{ ucfirst($deal->type) }}</p>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Deal #{{ $notification->deal_id }} (Archived)</span>
                                @endif
                            </td>

                            <!-- Activity Signal -->
                            <td class="px-4 py-3">
                                <div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ 
                                        $notification->type === 'interested' ? 'bg-emerald-100 text-emerald-800' : (
                                        $notification->type === 'invest' ? 'bg-teal-100 text-teal-800' : 'bg-violet-100 text-violet-800'
                                    ) }}">
                                        {{ $notification->type_label }}
                                    </span>
                                    <p class="text-xs text-gray-600 mt-1">
                                        {{ $user?->name ?? 'Member' }} {{ $notification->action_message }} 
                                        <strong class="text-gray-800">{{ $deal?->title ?? 'this deal' }}</strong>
                                    </p>
                                </div>
                            </td>

                            <!-- Recorded Time -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="text-xs font-medium text-gray-800">{{ $notification->created_at?->format('M j, Y') }}</p>
                                <p class="text-[11px] text-gray-400">{{ $notification->created_at?->format('g:i A') }} ({{ $notification->created_at?->diffForHumans() }})</p>
                            </td>

                            <!-- Review Info -->
                            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap review-meta-cell">
                                @if ($isViewed)
                                    <div class="flex items-center gap-1.5 text-emerald-700 font-medium">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Viewed {{ $notification->viewed_at?->diffForHumans() }}</span>
                                    </div>
                                    @if ($notification->viewer)
                                        <p class="text-[11px] text-gray-400 mt-0.5">by {{ $notification->viewer->name }}</p>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                        Pending review
                                    </span>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-2">
                                    <!-- Toggle Read/Unread Button -->
                                    <button type="button"
                                            onclick="toggleNotificationRowViewed({{ $notification->id }})"
                                            id="btn-toggle-{{ $notification->id }}"
                                            class="table-toggle-btn inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all shadow-xs {{ $isViewed ? 'bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-100' : 'bg-white text-gray-700 border border-gray-300 hover:border-emerald-400 hover:text-emerald-700 hover:bg-emerald-50' }}"
                                            data-viewed="{{ $isViewed ? '1' : '0' }}"
                                            title="{{ $isViewed ? 'Click to mark as unread' : 'Click to mark as read' }}">
                                        <span class="tick-icon-wrapper w-4 h-4 rounded-full flex items-center justify-center transition-colors {{ $isViewed ? 'bg-emerald-600 text-white' : 'border border-gray-400' }}">
                                            <svg class="w-3 h-3 {{ $isViewed ? 'block' : 'hidden' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                        <span class="btn-text">{{ $isViewed ? 'Viewed' : 'Mark read' }}</span>
                                    </button>

                                    <!-- Link to Deal Member Activity -->
                                    @if ($deal)
                                        <a href="{{ route('admin.deals.member-activity', $deal) }}" 
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold text-[#0a5554] bg-[#0a5554]/10 hover:bg-[#0a5554]/20 transition-colors"
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
        @endif
    </div>
</section>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-5 right-5 z-50 transform transition-all duration-300 translate-y-24 opacity-0 pointer-events-none">
    <div class="flex items-center gap-2 px-4 py-3 rounded-lg shadow-xl text-white text-xs font-semibold" id="toast-inner">
        <span id="toast-message"></span>
    </div>
</div>

@push('scripts')
<script>
    function showNotificationToast(msg, isSuccess = true) {
        const toast = document.getElementById('toast');
        const inner = document.getElementById('toast-inner');
        const message = document.getElementById('toast-message');

        message.textContent = msg;
        inner.className = isSuccess 
            ? 'flex items-center gap-2 px-4 py-3 rounded-lg shadow-xl text-white text-xs font-semibold bg-[#0a5554]'
            : 'flex items-center gap-2 px-4 py-3 rounded-lg shadow-xl text-white text-xs font-semibold bg-red-600';

        toast.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
        setTimeout(() => {
            toast.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
        }, 2500);
    }

    async function toggleNotificationRowViewed(id) {
        const row = document.getElementById(`notification-row-${id}`);
        const btn = document.getElementById(`btn-toggle-${id}`);
        if (!row || !btn) return;

        const currentViewed = btn.getAttribute('data-viewed') === '1';
        const newAction = currentViewed ? 'unview' : 'view';

        try {
            btn.disabled = true;
            const res = await fetch(`{{ url('admin/notifications') }}/${id}/toggle-viewed`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: newAction })
            });

            if (res.ok) {
                const data = await res.json();
                const isNowViewed = data.viewed;

                // Update Button
                btn.setAttribute('data-viewed', isNowViewed ? '1' : '0');
                btn.title = isNowViewed ? 'Click to mark as unread' : 'Click to mark as read';
                btn.className = `table-toggle-btn inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all shadow-xs ${
                    isNowViewed 
                        ? 'bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-100' 
                        : 'bg-white text-gray-700 border border-gray-300 hover:border-emerald-400 hover:text-emerald-700 hover:bg-emerald-50'
                }`;

                const tickWrapper = btn.querySelector('.tick-icon-wrapper');
                if (tickWrapper) {
                    tickWrapper.className = `tick-icon-wrapper w-4 h-4 rounded-full flex items-center justify-center transition-colors ${
                        isNowViewed ? 'bg-emerald-600 text-white' : 'border border-gray-400'
                    }`;
                    const tickSvg = tickWrapper.querySelector('svg');
                    if (tickSvg) {
                        tickSvg.className = `w-3 h-3 ${isNowViewed ? 'block' : 'hidden'}`;
                    }
                }

                const btnText = btn.querySelector('.btn-text');
                if (btnText) btnText.textContent = isNowViewed ? 'Viewed' : 'Mark read';

                // Update Row styling
                if (isNowViewed) {
                    row.classList.remove('bg-amber-50/20');
                } else {
                    row.classList.add('bg-amber-50/20');
                }

                // Update Status Indicator
                const dot = row.querySelector('.status-indicator-dot');
                if (dot) {
                    dot.className = `status-indicator-dot w-2.5 h-2.5 rounded-full ${isNowViewed ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'}`;
                }
                const label = row.querySelector('.status-indicator-label');
                if (label) {
                    label.textContent = isNowViewed ? 'Read' : 'Unread';
                    label.className = `status-indicator-label text-xs font-semibold ${isNowViewed ? 'text-emerald-700' : 'text-amber-700'}`;
                }

                // Update Review Cell
                const reviewCell = row.querySelector('.review-meta-cell');
                if (reviewCell) {
                    if (isNowViewed) {
                        reviewCell.innerHTML = `
                            <div class="flex items-center gap-1.5 text-emerald-700 font-medium">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Viewed just now</span>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-0.5">by ${data.viewer_name || 'Admin'}</p>
                        `;
                    } else {
                        reviewCell.innerHTML = `
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                Pending review
                            </span>
                        `;
                    }
                }

                // Update global header badge & stat cards
                updateGlobalBadges(data.unread_count);

                showNotificationToast(data.message || (isNowViewed ? 'Marked as viewed.' : 'Marked as unread.'));
            }
        } catch (e) {
            console.error(e);
            showNotificationToast('Failed to update status', false);
        } finally {
            btn.disabled = false;
        }
    }

    async function markAllNotificationsAsReadTable() {
        try {
            const res = await fetch("{{ route('admin.notifications.mark-all-viewed') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (res.ok) {
                const data = await res.json();
                showNotificationToast(data.message || 'All notifications marked as read.');
                
                // Hide table mark all button
                const btn = document.getElementById('table-mark-all-read-btn');
                if (btn) btn.classList.add('hidden');

                // Update global badges
                updateGlobalBadges(0);

                // Reload or update rows
                setTimeout(() => {
                    window.location.reload();
                }, 600);
            }
        } catch (err) {
            console.error(err);
            showNotificationToast('Failed to mark all as read', false);
        }
    }

    function updateGlobalBadges(unreadCount) {
        const topBadge = document.getElementById('admin-unread-badge');
        if (topBadge) {
            if (unreadCount > 0) {
                topBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                topBadge.classList.remove('hidden');
            } else {
                topBadge.classList.add('hidden');
            }
        }

        const sidebarBadge = document.querySelector('.sidebar-unread-badge');
        if (sidebarBadge) {
            if (unreadCount > 0) {
                sidebarBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                sidebarBadge.classList.remove('hidden');
            } else {
                sidebarBadge.classList.add('hidden');
            }
        }

        const pill = document.getElementById('dropdown-unread-pill');
        if (pill) {
            if (unreadCount > 0) {
                pill.textContent = `${unreadCount} Unread`;
                pill.className = 'text-[11px] font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800';
            } else {
                pill.textContent = 'All read';
                pill.className = 'text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800';
            }
        }

        const statUnread = document.getElementById('stat-unread');
        if (statUnread) statUnread.textContent = unreadCount;
    }
</script>
@endpush
@endsection
