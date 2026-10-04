@extends('layouts.admin')
@section('page_title', $deal->title.' — Member activity | Dashboard')

@section('page_content')
<section class="container mx-auto p-4 md:p-6 max-w-6xl">
    <div class="bg-white rounded-xl shadow p-4 md:p-5 mb-5">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div class="flex gap-4 min-w-0">
                <img src="{{ $deal->getLogoUrl() }}" alt="" class="w-14 h-14 rounded-full object-cover shrink-0 ring-2 ring-gray-100">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Admin only</p>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 mt-1">{{ $deal->title }}</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ $deal->sector ?: 'Sector not set' }} · Deal type: <span class="font-medium text-gray-800">{{ $deal->type }}</span> · Status: <span class="font-medium text-gray-800">{{ $deal->status }}</span></p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('deal.view', $deal) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-[#0a5554] text-white text-sm font-semibold hover:bg-[#084646]">
                    Public page
                </a>
                <a href="{{ route('edit.deal', $deal->id) }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 text-gray-800 text-sm font-semibold hover:bg-gray-200">
                    Edit deal
                </a>
                <a href="{{ route('admin.investments') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-200 text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    ← Investments index
                </a>
            </div>
        </div>

        <dl class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-3">
                <dt class="text-xs font-semibold text-emerald-800 uppercase tracking-wide">Interested</dt>
                <dd class="text-2xl font-bold text-emerald-900 mt-1">{{ $interested->count() }}</dd>
            </div>
            <div class="rounded-lg bg-teal-50 border border-teal-100 px-3 py-3">
                <dt class="text-xs font-semibold text-teal-800 uppercase tracking-wide">Invest</dt>
                <dd class="text-2xl font-bold text-teal-900 mt-1">{{ $investRows->count() }}</dd>
            </div>
            <div class="rounded-lg bg-violet-50 border border-violet-100 px-3 py-3">
                <dt class="text-xs font-semibold text-violet-800 uppercase tracking-wide">Review / WhatsApp</dt>
                <dd class="text-2xl font-bold text-violet-900 mt-1">{{ $reviewRows->count() }}</dd>
            </div>
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-3 py-3">
                <dt class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Committed</dt>
                <dd class="text-2xl font-bold text-amber-900 mt-1">{{ $deal->commits->count() }}</dd>
            </div>
        </dl>
    </div>

    @if (session('success'))
        <div class="mb-5 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
        </div>
    @endif

    <div class="space-y-8">
        <div class="bg-white rounded-xl shadow overflow-hidden">
            @php
                $unviewedInterested = $interested->whereNull('viewed_at');
                $unviewedCount = $unviewedInterested->count();
            @endphp
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Interested <span class="text-gray-500 font-normal normal-case">(express interest)</span></h2>
                    <span id="interested-unread-badge" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $unviewedCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $unviewedCount > 0 ? $unviewedCount . ' unread' : 'All viewed' }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" id="mark-all-interested-btn" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md text-[#0a5554] bg-[#0a5554]/10 hover:bg-[#0a5554]/20 transition-colors {{ $unviewedCount > 0 ? '' : 'hidden' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Mark all as viewed
                    </button>
                    <span class="text-xs font-semibold text-gray-500">{{ $interested->count() }} {{ \Illuminate\Support\Str::plural('member', $interested->count()) }}</span>
                </div>
            </div>
            @if ($interested->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-gray-500">No interested members yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2">Member</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2 whitespace-nowrap">Recorded</th>
                                <th class="px-4 py-2 text-right whitespace-nowrap">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($interested as $row)
                                @php
                                    $isViewed = $row->isViewed();
                                @endphp
                                <tr class="hover:bg-gray-50/80 transition-colors {{ !$isViewed ? 'bg-amber-50/20' : '' }}" id="row-investment-{{ $row->id }}">
                                    <td class="px-4 py-2 font-medium text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <span class="unread-dot w-2 h-2 rounded-full bg-amber-500 shrink-0 {{ $isViewed ? 'hidden' : '' }}" title="Unread"></span>
                                            <span>{{ $row->user?->name ?? '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600 break-all">{{ $row->user?->email ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $row->created_at?->format('M j, Y g:i A') }}</td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <form action="{{ route('admin.deals.member-activity.toggle-viewed', [$deal, $row]) }}" method="POST" class="inline-block toggle-viewed-form" data-investment-id="{{ $row->id }}">
                                                @csrf
                                                <button type="submit"
                                                    class="toggle-viewed-btn group inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-[#0a5554]/30 {{ $isViewed ? 'bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-100' : 'bg-white text-gray-700 border border-gray-300 hover:border-emerald-400 hover:text-emerald-700 hover:bg-emerald-50' }}"
                                                    data-viewed="{{ $isViewed ? '1' : '0' }}"
                                                    title="{{ $isViewed ? 'Viewed by admin (' . ($row->viewed_at?->format('M j, Y g:i A') ?? 'Viewed') . ($row->viewer ? ' by ' . $row->viewer->name : '') . '). Click to mark as unread.' : 'Click to mark as viewed by admin' }}">
                                                    <span class="tick-icon-wrapper w-4 h-4 rounded-full flex items-center justify-center transition-colors {{ $isViewed ? 'bg-emerald-600 text-white' : 'border border-gray-400 group-hover:border-emerald-600 group-hover:bg-emerald-600 group-hover:text-white' }}">
                                                        <svg class="w-3 h-3 {{ $isViewed ? 'block' : 'hidden group-hover:block' }}" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </span>
                                                    <span class="btn-label">{{ $isViewed ? 'Viewed' : 'Mark as read' }}</span>
                                                </button>
                                            </form>
                                            <div class="viewed-time-info text-[11px] text-gray-400 {{ $isViewed ? '' : 'hidden' }}">
                                                <span class="viewed-time-text">{{ $row->viewed_at?->format('M j, g:i A') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Invest <span class="text-gray-500 font-normal normal-case">(investment record)</span></h2>
                <span class="text-xs font-semibold text-gray-500">{{ $investRows->count() }} {{ \Illuminate\Support\Str::plural('member', $investRows->count()) }}</span>
            </div>
            @if ($investRows->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-gray-500">No invest-type investment rows for this deal.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2">Member</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2 whitespace-nowrap">Recorded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($investRows as $row)
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $row->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600 break-all">{{ $row->user?->email ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $row->created_at?->format('M j, Y g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Review / WhatsApp signal</h2>
                <span class="text-xs font-semibold text-gray-500">{{ $reviewRows->count() }} {{ \Illuminate\Support\Str::plural('member', $reviewRows->count()) }}</span>
            </div>
            @if ($reviewRows->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-gray-500">No review signals for this deal.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2">Member</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2 whitespace-nowrap">Recorded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($reviewRows as $row)
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $row->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600 break-all">{{ $row->user?->email ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $row->created_at?->format('M j, Y g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Committed <span class="text-gray-500 font-normal normal-case">(amount & deadline)</span></h2>
                <span class="text-xs font-semibold text-gray-500">{{ $deal->commits->count() }} {{ \Illuminate\Support\Str::plural('commitment', $deal->commits->count()) }}</span>
            </div>
            @if ($deal->commits->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-gray-500">No recorded commitments for this deal.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2">Member</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2 whitespace-nowrap">Amount</th>
                                <th class="px-4 py-2 whitespace-nowrap">Deadline</th>
                                <th class="px-4 py-2 whitespace-nowrap">Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($deal->commits as $commit)
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $commit->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600 break-all">{{ $commit->user?->email ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-800 whitespace-nowrap">${{ number_format((float) $commit->amount, 2) }}</td>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $commit->deadline ? \Carbon\Carbon::parse($commit->deadline)->format('M j, Y') : '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $commit->created_at?->format('M j, Y g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Commit Submissions</h2>
                <span class="text-xs font-semibold text-gray-500">{{ $deal->commitSubmissions->count() }} {{ \Illuminate\Support\Str::plural('submission', $deal->commitSubmissions->count()) }}</span>
            </div>
            @if ($deal->commitSubmissions->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-gray-500">No commit form submissions for this deal yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2 whitespace-nowrap">WhatsApp</th>
                                <th class="px-4 py-2">Company</th>
                                <th class="px-4 py-2 whitespace-nowrap">Proposed amount</th>
                                <th class="px-4 py-2 whitespace-nowrap">Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($deal->commitSubmissions as $submission)
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-4 py-2 font-medium text-gray-900">{{ $submission->name }}</td>
                                    <td class="px-4 py-2 text-gray-600 break-all">
                                        <a href="mailto:{{ $submission->email }}" class="hover:underline">{{ $submission->email }}</a>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $submission->whatsapp_number }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $submission->company_name }}</td>
                                    <td class="px-4 py-2 text-gray-800 whitespace-nowrap">{{ $submission->currency }} {{ number_format((float) $submission->amount, 2) }}</td>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $submission->updated_at?->format('M j, Y g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</section>

<!-- Floating Toast Notification -->
<div id="activity-toast" class="fixed bottom-5 right-5 z-50 transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none">
    <div class="bg-gray-900 text-white text-xs md:text-sm font-medium px-4 py-2.5 rounded-lg shadow-lg flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        <span id="activity-toast-text">Updated</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function showToast(message) {
        const toast = document.getElementById('activity-toast');
        const toastText = document.getElementById('activity-toast-text');
        if (!toast || !toastText) return;
        toastText.textContent = message;
        toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
        clearTimeout(window.__toastTimeout);
        window.__toastTimeout = setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
        }, 2500);
    }

    function updateBadge(unviewedCount) {
        const badge = document.getElementById('interested-unread-badge');
        const markAllBtn = document.getElementById('mark-all-interested-btn');
        if (badge) {
            if (unviewedCount > 0) {
                badge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800';
                badge.textContent = `${unviewedCount} unread`;
                if (markAllBtn) markAllBtn.classList.remove('hidden');
            } else {
                badge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800';
                badge.textContent = 'All viewed';
                if (markAllBtn) markAllBtn.classList.add('hidden');
            }
        }
    }

    // Toggle viewed for individual investment
    document.querySelectorAll('.toggle-viewed-form').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = form.querySelector('.toggle-viewed-btn');
            const row = form.closest('tr');
            const unreadDot = row?.querySelector('.unread-dot');
            const tickIconWrapper = btn.querySelector('.tick-icon-wrapper');
            const tickSvg = tickIconWrapper.querySelector('svg');
            const label = btn.querySelector('.btn-label');
            const timeInfo = row?.querySelector('.viewed-time-info');
            const timeText = row?.querySelector('.viewed-time-text');

            btn.disabled = true;
            btn.classList.add('opacity-75');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || form.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });

                if (!response.ok) throw new Error('Request failed');
                const data = await response.json();

                if (data.success) {
                    if (data.viewed) {
                        btn.dataset.viewed = '1';
                        btn.className = 'toggle-viewed-btn group inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-[#0a5554]/30 bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-100';
                        btn.title = `Viewed by admin (${data.viewed_at_formatted || 'Viewed'}${data.viewer_name ? ' by ' + data.viewer_name : ''}). Click to mark as unread.`;
                        tickIconWrapper.className = 'tick-icon-wrapper w-4 h-4 rounded-full flex items-center justify-center transition-colors bg-emerald-600 text-white';
                        tickSvg.classList.remove('hidden', 'group-hover:block');
                        tickSvg.classList.add('block');
                        label.textContent = 'Viewed';
                        if (unreadDot) unreadDot.classList.add('hidden');
                        if (row) row.classList.remove('bg-amber-50/20');
                        if (timeInfo && timeText) {
                            timeText.textContent = data.viewed_at_formatted ? (data.viewed_at_formatted.split(',')[1]?.trim() || data.viewed_at_formatted) : 'Just now';
                            timeInfo.classList.remove('hidden');
                        }
                    } else {
                        btn.dataset.viewed = '0';
                        btn.className = 'toggle-viewed-btn group inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-[#0a5554]/30 bg-white text-gray-700 border border-gray-300 hover:border-emerald-400 hover:text-emerald-700 hover:bg-emerald-50';
                        btn.title = 'Click to mark as viewed by admin';
                        tickIconWrapper.className = 'tick-icon-wrapper w-4 h-4 rounded-full flex items-center justify-center transition-colors border border-gray-400 group-hover:border-emerald-600 group-hover:bg-emerald-600 group-hover:text-white';
                        tickSvg.classList.remove('block');
                        tickSvg.classList.add('hidden', 'group-hover:block');
                        label.textContent = 'Mark as read';
                        if (unreadDot) unreadDot.classList.remove('hidden');
                        if (row) row.classList.add('bg-amber-50/20');
                        if (timeInfo) timeInfo.classList.add('hidden');
                    }

                    if (typeof data.unviewed_count !== 'undefined') {
                        updateBadge(data.unviewed_count);
                    }
                    showToast(data.message || (data.viewed ? 'Marked as viewed' : 'Marked as unread'));
                }
            } catch (err) {
                // If ajax failed, fallback to normal form submission
                form.submit();
            } finally {
                btn.disabled = false;
                btn.classList.remove('opacity-75');
            }
        });
    });

    // Mark all as viewed
    const markAllBtn = document.getElementById('mark-all-interested-btn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', async function () {
            markAllBtn.disabled = true;
            markAllBtn.classList.add('opacity-75');

            try {
                const response = await fetch("{{ route('admin.deals.member-activity.mark-all-viewed', $deal) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });

                if (!response.ok) throw new Error('Request failed');
                const data = await response.json();

                if (data.success) {
                    document.querySelectorAll('.toggle-viewed-btn').forEach(btn => {
                        const row = btn.closest('tr');
                        const unreadDot = row?.querySelector('.unread-dot');
                        const tickIconWrapper = btn.querySelector('.tick-icon-wrapper');
                        const tickSvg = tickIconWrapper.querySelector('svg');
                        const label = btn.querySelector('.btn-label');
                        const timeInfo = row?.querySelector('.viewed-time-info');
                        const timeText = row?.querySelector('.viewed-time-text');

                        btn.dataset.viewed = '1';
                        btn.className = 'toggle-viewed-btn group inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-[#0a5554]/30 bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-100';
                        btn.title = 'Viewed by admin. Click to mark as unread.';
                        tickIconWrapper.className = 'tick-icon-wrapper w-4 h-4 rounded-full flex items-center justify-center transition-colors bg-emerald-600 text-white';
                        tickSvg.classList.remove('hidden', 'group-hover:block');
                        tickSvg.classList.add('block');
                        label.textContent = 'Viewed';
                        if (unreadDot) unreadDot.classList.add('hidden');
                        if (row) row.classList.remove('bg-amber-50/20');
                        if (timeInfo && timeText) {
                            timeText.textContent = 'Just now';
                            timeInfo.classList.remove('hidden');
                        }
                    });

                    updateBadge(0);
                    showToast(data.message || 'All marked as viewed');
                }
            } catch (err) {
                console.error(err);
            } finally {
                markAllBtn.disabled = false;
                markAllBtn.classList.remove('opacity-75');
            }
        });
    }
});
</script>
@endpush
