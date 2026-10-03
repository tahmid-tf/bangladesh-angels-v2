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