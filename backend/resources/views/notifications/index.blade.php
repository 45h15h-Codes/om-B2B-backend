@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header with actions -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-bell"></i> Notification Center
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Manage and monitor all inventory, system, and webhook status notifications.</p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <button class="btn btn-secondary" onclick="markAllRead(event)" style="height: 38px; line-height: 1;">
                <i class="fa-solid fa-check-double"></i> Mark All Read
            </button>
            <button class="btn btn-danger" onclick="deleteAllNotifs(event)" style="height: 38px; line-height: 1;">
                <i class="fa-solid fa-trash-can"></i> Delete All
            </button>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div style="padding: 16px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background: #fafbfc;">
        <!-- Filters tab -->
        <div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; max-width: 100%;">
            @foreach(['all' => 'All', 'unread' => 'Unread', 'read' => 'Read', 'orders' => 'Orders', 'diamond_sales' => 'Diamond Sales', 'jewelry_sales' => 'Jewelry Sales', 'sync' => 'Sync Alerts', 'system' => 'System Alerts'] as $key => $label)
                <button class="filter-tab-btn {{ $filter === $key ? 'active' : '' }}" onclick="changeFilter('{{ $key }}')" data-filter="{{ $key }}" style="padding: 8px 16px; border: 1px solid var(--border-color); border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; background: white; color: var(--text-color); transition: all 0.2s ease; white-space: nowrap;">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <!-- Search Input -->
        <div style="position: relative; width: 280px; max-width: 100%;">
            <input type="text" id="notifSearch" placeholder="Search notification details..." oninput="triggerSearch()" style="width: 100%; padding: 10px 16px 10px 38px; border: 1px solid var(--border-color); border-radius: 20px; font-size: 13px; font-family: inherit; font-weight: 500; outline: none; transition: border-color 0.2s; box-sizing: border-box;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 12px; color: var(--text-muted); font-size: 13px;"></i>
        </div>
    </div>

    <!-- Bulk Action Bar (Hidden by default) -->
    <div id="bulkActionBar" style="padding: 12px 30px; background-color: #fffaf0; border-bottom: 1px solid #feebc8; display: none; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" style="width: 16px; height: 16px; cursor: pointer;">
            <span id="selectedCountText" style="font-size: 13px; font-weight: 700; color: #c05621;">0 items selected</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-secondary" onclick="executeBulkAction('mark-read')" style="background: white; border-color: #cbd5e0; color: var(--success-color); padding: 6px 12px; font-size: 12.5px; height: 32px; line-height: 1; box-sizing: border-box;">
                <i class="fa-solid fa-check"></i> Mark Read
            </button>
            <button class="btn btn-danger" onclick="executeBulkAction('delete')" style="background: white; border-color: #fed7d7; color: var(--error-color); padding: 6px 12px; font-size: 12.5px; height: 32px; line-height: 1; box-sizing: border-box;">
                <i class="fa-solid fa-trash-can"></i> Delete Selected
            </button>
        </div>
    </div>

    <!-- Notifications List Container -->
    <div id="notificationsContainer">
        @include('notifications._list_items')
    </div>

    <!-- Pagination Container -->
    <div id="paginationContainer" style="border-top: 1px solid var(--border-color); background: #fafbfc;">
        @include('notifications._pagination')
    </div>
</div>

<style>
    .filter-tab-btn.active {
        background-color: var(--primary-color) !important;
        color: white !important;
        border-color: var(--primary-color) !important;
    }
    .filter-tab-btn:hover:not(.active) {
        background-color: var(--primary-light) !important;
        color: var(--primary-color) !important;
        border-color: #b0d4e3 !important;
    }
    .notification-row:hover {
        background-color: #f7fafc !important;
    }
</style>
@endsection

@section('scripts')
<script>
    let currentFilter = '{{ $filter }}';
    let currentSearch = '';
    let currentPageUrl = '{{ route("notifications.index") }}';

    function changeFilter(filter) {
        currentFilter = filter;
        document.querySelectorAll('.filter-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === filter);
        });
        loadPage('{{ route("notifications.index") }}');
    }

    let searchTimeout = null;
    function triggerSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = document.getElementById('notifSearch').value;
            loadPage('{{ route("notifications.index") }}');
        }, 300);
    }

    function loadPage(url = null) {
        if (url) {
            currentPageUrl = url;
        }
        
        let fetchUrl = new URL(currentPageUrl, window.location.origin);
        fetchUrl.searchParams.set('filter', currentFilter);
        if (currentSearch) {
            fetchUrl.searchParams.set('search', currentSearch);
        }

        fetch(fetchUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('notificationsContainer').innerHTML = data.html;
                document.getElementById('paginationContainer').innerHTML = data.pagination;
                updateNotificationBadge(data.unread_count);
                
                // Reset select all checkbox and bulk bar
                document.getElementById('selectAllCheckbox').checked = false;
                toggleBulkActions();
            }
        })
        .catch(err => console.error('Error loading notifications page:', err));
    }

    function toggleSelectAll(masterCheckbox) {
        document.querySelectorAll('.notif-checkbox').forEach(cb => {
            cb.checked = masterCheckbox.checked;
        });
        toggleBulkActions();
    }

    function toggleBulkActions() {
        const checkedBoxes = document.querySelectorAll('.notif-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionBar');
        const countText = document.getElementById('selectedCountText');
        const allBoxes = document.querySelectorAll('.notif-checkbox');

        if (checkedBoxes.length > 0) {
            bulkBar.style.display = 'flex';
            countText.textContent = `${checkedBoxes.length} item(s) selected`;
            document.getElementById('selectAllCheckbox').checked = (checkedBoxes.length === allBoxes.length);
        } else {
            bulkBar.style.display = 'none';
            document.getElementById('selectAllCheckbox').checked = false;
        }
    }

    function executeBulkAction(action) {
        const checkedBoxes = document.querySelectorAll('.notif-checkbox:checked');
        if (checkedBoxes.length === 0) return;

        if (action === 'delete' && !confirm('Are you sure you want to delete the selected notifications?')) {
            return;
        }

        const ids = Array.from(checkedBoxes).map(cb => cb.value);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = action === 'delete' ? '/notifications/delete-multiple' : '/notifications/mark-read-multiple';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(`Selected notifications ${action === 'delete' ? 'deleted' : 'marked read'}.`, 'success');
                loadPage(); // reload current state
            }
        })
        .catch(err => console.error(`Error executing bulk ${action}:`, err));
    }

    function markSingleAction(event, id, action) {
        event.preventDefault();
        event.stopPropagation();
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch(`/notifications/${action}/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast(`Notification marked as ${action === 'read' ? 'read' : 'unread'}.`, 'success');
                loadPage(); // refresh view
            }
        })
        .catch(err => console.error(`Error marking notification ${action}:`, err));
    }

    function deleteSingleAction(event, id) {
        event.preventDefault();
        event.stopPropagation();
        
        if (!confirm('Are you sure you want to delete this notification?')) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch(`/notifications/delete/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Notification deleted.', 'success');
                loadPage(); // refresh view
            }
        })
        .catch(err => console.error('Error deleting notification:', err));
    }

    function markAllRead(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch('/notifications/api/read-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('All notifications marked as read.', 'success');
                loadPage();
            }
        })
        .catch(err => console.error('Error marking all read:', err));
    }

    function deleteAllNotifs(event) {
        event.preventDefault();
        event.stopPropagation();
        
        if (!confirm('Are you sure you want to delete all notifications?')) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch('/notifications/delete-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('All notifications deleted.', 'success');
                loadPage();
            }
        })
        .catch(err => console.error('Error deleting all notifications:', err));
    }
</script>
@endsection
