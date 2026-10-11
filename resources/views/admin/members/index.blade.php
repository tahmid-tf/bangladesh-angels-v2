@extends('layouts.admin')
@section('page_title','Members | Dashboard')
@section('page_content')

<!-- Header Section -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center p-4 bg-white shadow gap-3">
    <h1 class="text-lg md:text-xl font-bold mb-2 md:mb-0">Members</h1>
    @php
        $accountScope = request('account_scope', 'all');
        $approvalScope = request('approval_scope', 'all');
        $exportQuery = ['account_scope' => $accountScope, 'approval_scope' => $approvalScope];
        $isSuperadmin = auth()->user()?->role === 'superadmin';
    @endphp
    <div class="flex flex-wrap gap-2">
        @if ($isSuperadmin)
            <a href="{{ route('admin.members.export', ['format' => 'pdf'] + $exportQuery) }}" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200">PDF</a>
            <a href="{{ route('admin.members.export', ['format' => 'sql'] + $exportQuery) }}" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200">MySQL Backup</a>
            <a href="{{ route('admin.members.export', ['format' => 'csv'] + $exportQuery) }}" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200">CSV</a>
            <a href="{{ route('admin.members.export', ['format' => 'excel'] + $exportQuery) }}" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200">Excel</a>
            <a href="{{ route('admin.members.export', ['format' => 'json'] + $exportQuery) }}" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200">JSON</a>
        @endif
        <a href="{{route('member.add')}}" class="bg-green-600 text-white px-4 py-2 rounded-lg shadow hover:bg-green-700 whitespace-nowrap">
            + Add New Member
        </a>
    </div>
</div>

@if ($isSuperadmin)
    <div class="mt-4 mx-4 md:mx-0 bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('admin.members') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label for="account_scope" class="block text-xs font-semibold text-gray-600 mb-1">Account Scope</label>
                <select id="account_scope" name="account_scope" class="px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm">
                    <option value="all" @selected($accountScope === 'all')>All</option>
                    <option value="active" @selected($accountScope === 'active')>Active only</option>
                    <option value="inactive" @selected($accountScope === 'inactive')>Inactive (free) only</option>
                </select>
            </div>
            <div>
                <label for="approval_scope" class="block text-xs font-semibold text-gray-600 mb-1">Approval Scope</label>
                <select id="approval_scope" name="approval_scope" class="px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm">
                    <option value="all" @selected($approvalScope === 'all')>All</option>
                    <option value="approved" @selected($approvalScope === 'approved')>Approved only</option>
                    <option value="pending" @selected($approvalScope === 'pending')>Pending only</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-[#0a5554] text-white text-sm font-semibold hover:bg-[#084646]">Apply export filters</button>
            <a href="{{ route('admin.members') }}" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-semibold hover:bg-gray-200">Reset</a>
        </form>
    </div>
@endif

@if (session('success'))
<div class="mt-4 mx-4 md:mx-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative flex items-center justify-between">
    <div>
        <strong class="font-bold">Success!</strong>
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    <button onclick="this.parentElement.remove()" class="ml-4">
        <svg class="fill-current h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <path d="M14.59 5.41L10 10l-4.59-4.59L4 7l6 6 6-6z"/>
        </svg>
    </button>
</div>
@endif

@if (session('error'))
<div class="mt-4 mx-4 md:mx-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative flex items-center justify-between">
    <div>
        <strong class="font-bold">Error:</strong>
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
    <button onclick="this.parentElement.remove()" class="ml-4">
        <svg class="fill-current h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <path d="M14.59 5.41L10 10l-4.59-4.59L4 7l6 6 6-6z"/>
        </svg>
    </button>
</div>
@endif

<livewire:members-table />

@push('head_styles')
<style>
    [x-cloak] {
        display: none !important;
    }

    /* Scoped overrides for members custom table action buttons & badges */
    .admin-page:not(.admin-page--dashboard) table.members-custom-table,
    table.members-custom-table {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    .admin-page:not(.admin-page--dashboard) table.members-custom-table th,
    .members-custom-table th {
        padding: 0.75rem 0.65rem !important;
        background: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
        color: #64748b !important;
        font-size: 0.67rem !important;
        font-weight: 750 !important;
        letter-spacing: 0.04em !important;
        text-transform: uppercase !important;
        white-space: nowrap !important;
    }

    .admin-page:not(.admin-page--dashboard) table.members-custom-table td,
    .members-custom-table td {
        padding: 0.75rem 0.65rem !important;
        border-top: 1px solid #f1f5f9 !important;
        color: #334155 !important;
        vertical-align: middle !important;
    }

    /* Checkbox column width and alignment */
    .admin-page:not(.admin-page--dashboard) table.members-custom-table th.col-checkbox,
    .admin-page:not(.admin-page--dashboard) table.members-custom-table td.col-checkbox,
    .members-custom-table th.col-checkbox,
    .members-custom-table td.col-checkbox {
        width: 44px !important;
        min-width: 44px !important;
        max-width: 44px !important;
        padding-left: 1.15rem !important;
        padding-right: 0.5rem !important;
        text-align: center !important;
    }

    /* First column (Member) when no checkbox present */
    .admin-page:not(.admin-page--dashboard) table.members-custom-table th.col-member-first,
    .admin-page:not(.admin-page--dashboard) table.members-custom-table td.col-member-first,
    .members-custom-table th.col-member-first,
    .members-custom-table td.col-member-first {
        padding-left: 1.25rem !important;
    }

    /* Actions column: align right with generous right padding to prevent bleeding or overflowing edge */
    .admin-page:not(.admin-page--dashboard) table.members-custom-table th.col-actions,
    .admin-page:not(.admin-page--dashboard) table.members-custom-table td.col-actions,
    .members-custom-table th.col-actions,
    .members-custom-table td.col-actions {
        text-align: right !important;
        padding-right: 1.5rem !important;
        padding-left: 0.75rem !important;
        white-space: nowrap !important;
        width: 1% !important;
    }

    .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn,
    .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn:has(svg),
    .members-custom-table td :is(a, button).member-action-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 4px !important;
        width: auto !important;
        min-width: 0 !important;
        height: auto !important;
        min-height: 27px !important;
        padding: 4px 9px !important;
        border-radius: 6px !important;
        font-size: 0.72rem !important; /* ~11.5px */
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

    /* Standard SVG action icons (outline icons: pencil, approve checkmark, trash bin) */
    .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn svg.member-svg-icon,
    .members-custom-table td :is(a, button).member-action-btn svg.member-svg-icon,
    svg.member-svg-icon {
        width: 12px !important;
        height: 12px !important;
        min-width: 12px !important;
        min-height: 12px !important;
        fill: none !important;
        stroke: currentColor !important;
        display: inline-block;
        vertical-align: middle !important;
    }

    /* Dedicated Spinner SVGs inside action buttons */
    .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn svg.member-spinner-icon,
    .members-custom-table td :is(a, button).member-action-btn svg.member-spinner-icon,
    svg.member-spinner-icon {
        width: 12px !important;
        height: 12px !important;
        min-width: 12px !important;
        min-height: 12px !important;
        vertical-align: middle !important;
        animation: spin 1s linear infinite !important;
    }

    .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn svg.member-spinner-icon circle,
    .members-custom-table td :is(a, button).member-action-btn svg.member-spinner-icon circle,
    svg.member-spinner-icon circle {
        stroke: currentColor !important;
        stroke-width: 4 !important;
        fill: none !important;
    }

    .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn svg.member-spinner-icon path,
    .members-custom-table td :is(a, button).member-action-btn svg.member-spinner-icon path,
    svg.member-spinner-icon path {
        fill: currentColor !important;
        stroke: none !important;
    }

    /* Livewire loading display rules for action buttons */
    .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn [wire\:loading],
    .members-custom-table td :is(a, button).member-action-btn [wire\:loading] {
        display: none;
    }

    .admin-page:not(.admin-page--dashboard) table.members-custom-table td :is(a, button).member-action-btn [wire\:loading\.remove],
    .members-custom-table td :is(a, button).member-action-btn [wire\:loading\.remove] {
        display: inline-block;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Member Action Row (strictly single-line flex aligned to right) */
    .member-actions-row {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 5px !important;
        flex-wrap: nowrap !important;
        white-space: nowrap !important;
    }

    /* Approve Button */
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

    /* Edit Button */
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

    /* Delete Button */
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

    /* Member Mode Select Dropdown (Active / Inactive) */
    .member-mode-select--active {
        background-color: #ecfdf5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
    }
    .member-mode-select--active:hover {
        background-color: #d1fae5 !important;
        border-color: #6ee7b7 !important;
    }

    .member-mode-select--inactive {
        background-color: #f3f4f6 !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
    }
    .member-mode-select--inactive:hover {
        background-color: #e5e7eb !important;
        border-color: #9ca3af !important;
    }
</style>
@endpush

@endsection