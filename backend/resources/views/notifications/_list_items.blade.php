@forelse($notifications as $n)
    <div class="notification-row {{ $n->read_at ? 'read' : 'unread' }}" data-id="{{ $n->id }}" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border-color); transition: all 0.2s ease; {{ $n->read_at ? 'opacity: 0.75;' : 'background-color: var(--primary-light); font-weight: 600;' }}">
        <div style="display: flex; align-items: center; gap: 14px; flex-grow: 1;">
            <input type="checkbox" class="notif-checkbox" value="{{ $n->id }}" onchange="toggleBulkActions()" style="width: 16px; height: 16px; cursor: pointer;">
            <div style="flex-grow: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <span style="font-weight: 700; color: var(--primary-color); font-size: 14px;">{{ $n->data['title'] ?? 'Notification' }}</span>
                    <span style="font-size: 11px; color: var(--text-muted); font-weight: 500;">{{ $n->created_at->diffForHumans() }}</span>
                </div>
                <div style="font-size: 13px; color: var(--text-color); line-height: 1.4;">{{ $n->data['message'] ?? '' }}</div>
                @if(isset($n->data['action_url']))
                    <a href="{{ route('notifications.read-single', $n->id) }}" style="font-size: 12px; color: var(--primary-color); font-weight: 700; display: inline-flex; align-items: center; gap: 4px; margin-top: 6px; text-decoration: none;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> View Details
                    </a>
                @endif
            </div>
        </div>
        <div class="action-menu" style="display: flex; gap: 8px; align-items: center; margin-left: 16px;">
            @if(!$n->read_at)
                <button class="btn btn-secondary" onclick="markSingleAction(event, '{{ $n->id }}', 'read')" style="padding: 6px 12px; font-size: 12px; height: 32px; line-height: 1;" title="Mark as Read">
                    <i class="fa-solid fa-check"></i> Mark Read
                </button>
            @else
                <button class="btn btn-secondary" onclick="markSingleAction(event, '{{ $n->id }}', 'unread')" style="padding: 6px 12px; font-size: 12px; height: 32px; line-height: 1;" title="Mark as Unread">
                    <i class="fa-solid fa-rotate-left"></i> Mark Unread
                </button>
            @endif
            <button class="btn btn-danger" onclick="deleteSingleAction(event, '{{ $n->id }}')" style="padding: 6px 12px; font-size: 12px; height: 32px; line-height: 1;" title="Delete">
                <i class="fa-solid fa-trash-can"></i> Delete
            </button>
        </div>
    </div>
@empty
    <div style="text-align: center; color: var(--text-muted); padding: 40px 20px; font-size: 14px;">
        <i class="fa-regular fa-bell-slash" style="font-size: 32px; color: #cbd5e0; margin-bottom: 12px; display: block;"></i>
        No notifications found.
    </div>
@endforelse
