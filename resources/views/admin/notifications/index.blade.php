@extends('layouts.admin')
@section('page_title', 'Member Activity Notifications | Dashboard')

@push('head_styles')
<style>
    /* Scoped styles to prevent admin-dashboard.css aggressive table overrides */
    .notif-table td a:has(svg) {
        display: inline-flex !important;
        width: auto !important;
        height: auto !important;
        background: transparent !important;
        border: none !important;
        transform: none !important;
        border-radius: 0 !important;
    }
    .notif-table td a:has(svg) svg {
        width: auto !important;
        height: auto !important;
        fill: currentColor !important;
    }
    .notif-table :is(a, button)[class*="bg-[#0a5554]"] {
        min-height: auto !important;
        border-radius: inherit !important;
        background: transparent !important;
        box-shadow: none !important;
        color: inherit !important;
    }

    /* Target Company Badge */
    .notif-company-badge {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        padding: 4px 10px !important;
        border-radius: 8px !important;
        font-size: 0.8125rem !important;
        font-weight: 700 !important;
        color: #064e3b !important;
        background: #ecfdf5 !important;
        border: 1px solid #a7f3d0 !important;
        text-decoration: none !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        transition: all 150ms ease !important;
        max-width: 100% !important;
    }
    .notif-company-badge:hover {
        background: #d1fae5 !important;
        border-color: #6ee7b7 !important;
        color: #064e3b !important;
        transform: translateY(-1px) !important;
        text-decoration: none !important;
    }
    .notif-company-badge .arrow-icon {
        font-size: 11px !important;
        color: #059669 !important;
        transition: transform 150ms ease !important;
    }
    .notif-company-badge:hover .arrow-icon {
        transform: translate(1px, -1px) !important;
    }

    /* Activity Signal Link */
    .notif-activity-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 5px !important;
        padding: 4px 12px !important;
        border-radius: 9999px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #065f46 !important;
        background: #f0fdf4 !important;
        border: 1px solid #bbf7d0 !important;
        text-decoration: none !important;
        min-height: 28px !important;
        box-shadow: none !important;
        transition: all 160ms ease !important;
    }
    .notif-activity-btn:hover {
        background: #0a5554 !important;
        border-color: #0a5554 !important;
        color: #ffffff !important;
        transform: translateY(-1px) !important;
        text-decoration: none !important;
    }

    /* Filter Action Button */
    .notif-filter-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        padding: 8px 20px !important;
        border-radius: 8px !important;
        font-size: 0.8125rem !important;
        font-weight: 700 !important;
        min-height: 38px !important;
        line-height: 1 !important;
        color: #ffffff !important;
        background: #0a5554 !important;
        border: 1px solid #084646 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08) !important;
        transition: all 150ms ease !important;
        cursor: pointer !important;
        white-space: nowrap !important;
        text-decoration: none !important;
    }
    .notif-filter-btn:hover {
        background: #073f3e !important;
        border-color: #053030 !important;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.16) !important;
        transform: translateY(-1px) !important;
        color: #ffffff !important;
        text-decoration: none !important;
    }
    .notif-filter-btn:active {
        transform: translateY(0) !important;
    }

    /* Read/Unread Toggle Button */
    .notif-toggle-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        padding: 4px 11px !important;
        border-radius: 9999px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        min-height: 28px !important;
        box-shadow: none !important;
        transition: all 150ms ease !important;
        cursor: pointer !important;
        outline: none !important;
    }
    .notif-toggle-btn[data-viewed="1"] {
        background: #ecfdf5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
    }
    .notif-toggle-btn[data-viewed="1"]:hover {
        background: #d1fae5 !important;
        border-color: #6ee7b7 !important;
    }
    .notif-toggle-btn[data-viewed="0"] {
        background: #ffffff !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
    }
    .notif-toggle-btn[data-viewed="0"]:hover {
        border-color: #10b981 !important;
        color: #047857 !important;
        background: #f0fdf4 !important;
    }
    .notif-toggle-btn .tick-icon-wrapper {
        width: 14px !important;
        height: 14px !important;
        border-radius: 9999px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 150ms ease !important;
        flex-shrink: 0 !important;
    }
    .notif-toggle-btn[data-viewed="1"] .tick-icon-wrapper {
        background: #059669 !important;
        color: #ffffff !important;
        border: none !important;
    }
    .notif-toggle-btn[data-viewed="0"] .tick-icon-wrapper {
        background: transparent !important;
        border: 1.5px solid #9ca3af !important;
        color: transparent !important;
    }
    .notif-toggle-btn[data-viewed="0"]:hover .tick-icon-wrapper {
        border-color: #059669 !important;
        background: #059669 !important;
        color: #ffffff !important;
    }
</style>
@endpush

@section('page_content')
<section class="container mx-auto p-4 md:p-6 max-w-7xl">
    <livewire:admin-notifications-table />
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
        if (!toast || !inner || !message) return;

        message.textContent = msg;
        inner.className = isSuccess 
            ? 'flex items-center gap-2 px-4 py-3 rounded-lg shadow-xl text-white text-xs font-semibold bg-[#0a5554]'
            : 'flex items-center gap-2 px-4 py-3 rounded-lg shadow-xl text-white text-xs font-semibold bg-red-600';

        toast.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
        setTimeout(() => {
            toast.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
        }, 2500);
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
    }

    window.addEventListener('notification-status-updated', event => {
        const count = event.detail?.unreadCount ?? event.detail?.[0]?.unreadCount;
        const msg = event.detail?.message ?? event.detail?.[0]?.message;
        if (typeof count !== 'undefined') updateGlobalBadges(count);
        if (msg) showNotificationToast(msg);
    });
</script>
@endpush
@endsection
