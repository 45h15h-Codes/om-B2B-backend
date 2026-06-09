@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-list-check"></i> System Activity Logs
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Search and filter security audit logs, admin sessions, and core record updates.</p>
        </div>
    </div>

    <!-- Filters Toolbar -->
    <div style="padding: 20px 30px; border-bottom: 1px solid var(--border-color); background: #fafbfc;">
        <form method="GET" action="{{ route('system.activity-logs.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 15px; align-items: flex-end;">
            
            <!-- User filter -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Admin User</label>
                <select name="user_id" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: white; font-family: inherit; outline: none;">
                    <option value="">All Users</option>
                    @foreach($users as $usr)
                        <option value="{{ $usr->id }}" {{ request('user_id') == $usr->id ? 'selected' : '' }}>{{ $usr->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Action filter -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Action / Event</label>
                <select name="action" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: white; font-family: inherit; outline: none;">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $act)) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" style="width: 100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; box-sizing: border-box;">
            </div>

            <!-- End Date -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" style="width: 100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; box-sizing: border-box;">
            </div>

            <!-- Search field -->
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Search Payload</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search IP, payload keys..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; box-sizing: border-box;">
            </div>

            <!-- Action buttons -->
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="submit" style="padding: 8px 16px; height: 37px; background: var(--primary-color); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                    <i class="fa-solid fa-filter"></i> Apply
                </button>
                <a href="{{ route('system.activity-logs.index') }}" style="padding: 8px 16px; height: 37px; background: white; color: var(--text-color); border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-sizing: border-box; transition: background 0.2s;">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div style="background: white;">
        @if($logs->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                    <thead>
                        <tr style="background: #fafbfc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                            <th style="padding: 16px 20px; width: 60px;">ID</th>
                            <th style="padding: 16px 20px; width: 180px;">User</th>
                            <th style="padding: 16px 20px; width: 160px;">Action</th>
                            <th style="padding: 16px 20px; width: 180px;">Target Entity</th>
                            <th style="padding: 16px 20px; width: 130px;">IP Address</th>
                            <th style="padding: 16px 20px;">Payload Specs</th>
                            <th style="padding: 16px 20px; width: 160px; text-align: right;">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            @php
                                $entity = '-';
                                if ($log->model_type && $log->model_id) {
                                    $parts = explode('\\', $log->model_type);
                                    $entity = end($parts) . ' (ID: ' . $log->model_id . ')';
                                }
                                
                                $actionLabel = ucfirst(str_replace('_', ' ', $log->action));
                                $actionBg = '#edf2f7';
                                $actionColor = '#4a5568';
                                
                                if (str_contains($log->action, 'login')) {
                                    $actionBg = '#e6fffa';
                                    $actionColor = '#319795';
                                } elseif (str_contains($log->action, 'logout')) {
                                    $actionBg = '#edf2f7';
                                    $actionColor = '#718096';
                                } elseif (str_contains($log->action, 'delete') || str_contains($log->action, 'reject')) {
                                    $actionBg = '#fff5f5';
                                    $actionColor = 'var(--error-color)';
                                } elseif (str_contains($log->action, 'approve') || str_contains($log->action, 'success')) {
                                    $actionBg = '#f0fff4';
                                    $actionColor = 'var(--success-color)';
                                }
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.15s;" onmouseenter="this.style.backgroundColor='#f8fafc'" onmouseleave="this.style.backgroundColor='transparent'">
                                <td style="padding: 16px 20px; font-weight: bold; color: var(--text-muted);">#{{ $log->id }}</td>
                                <td style="padding: 16px 20px;">
                                    @if($log->user)
                                        <div style="font-weight: 700; color: var(--text-color);">{{ $log->user->name }}</div>
                                        <div style="font-size: 11px; color: var(--text-muted);">{{ ucfirst(str_replace('_', ' ', $log->user->role)) }}</div>
                                    @else
                                        <div style="font-weight: 700; color: var(--text-muted);">System Agent</div>
                                    @endif
                                </td>
                                <td style="padding: 16px 20px;">
                                    <span style="font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 12px; background: {{ $actionBg }}; color: {{ $actionColor }};">
                                        {{ $actionLabel }}
                                    </span>
                                </td>
                                <td style="padding: 16px 20px; font-weight: 600; color: var(--text-color);">{{ $entity }}</td>
                                <td style="padding: 16px 20px; font-family: monospace; font-size: 12px;">{{ $log->ip_address ?: 'N/A' }}</td>
                                <td style="padding: 16px 20px;">
                                    @if($log->payload)
                                        <a href="#" onclick="togglePayload(event, {{ $log->id }})" style="font-size: 11.5px; font-weight: 700; color: var(--primary-color); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-square-poll-horizontal"></i> View Metadata Payload
                                        </a>
                                        
                                        <!-- Collapsible Payload details -->
                                        <div id="payload-{{ $log->id }}" style="display: none; margin-top: 10px; text-align: left;">
                                            <pre style="white-space: pre-wrap; font-family: 'Courier New', Courier, monospace; font-size: 11px; background: #f1f5f9; color: #334155; padding: 10px; border-radius: 6px; overflow-x: auto; max-height: 200px; border: 1px solid var(--border-color); line-height: 1.4;">{{ json_encode($log->payload, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 11.5px;">No payload</span>
                                    @endif
                                </td>
                                <td style="padding: 16px 20px; text-align: right; color: var(--text-muted); font-size: 12px;">
                                    <div>{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                                    <div style="font-size: 11px; margin-top: 2px;">{{ $log->created_at->diffForHumans() }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div style="padding: 20px 30px; border-top: 1px solid var(--border-color); display: flex; justify-content: center; background: #fafbfc;">
                {{ $logs->links() }}
            </div>
        @else
            <div style="padding: 50px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-list-check" style="font-size: 40px; color: var(--border-color); margin-bottom: 12px;"></i>
                <p style="font-size: 14px; font-weight: 500;">No activity log records found matching your filters.</p>
            </div>
        @endif
    </div>

</div>
@endsection

@section('scripts')
<script>
    function togglePayload(event, id) {
        event.preventDefault();
        const payloadDiv = document.getElementById(`payload-${id}`);
        if (payloadDiv.style.display === 'none') {
            payloadDiv.style.display = 'block';
        } else {
            payloadDiv.style.display = 'none';
        }
    }
</script>
@endsection
