@extends('layouts.app')

@section('styles')
<style>
    .filter-card {
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        align-items: end;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group label {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-color);
    }

    .form-control {
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-family: inherit;
        font-size: 13px;
        color: var(--text-color);
        width: 100%;
        background-color: #ffffff;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
    }

    .list-card {
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .requests-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .requests-table th {
        background-color: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    .requests-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .requests-table tr:hover {
        background-color: #f8fafc;
    }

    .badge-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
    }

    .badge-status.Pending {
        background-color: #fef3c7;
        color: #92400e;
    }

    .badge-status.Approved {
        background-color: #dcfce7;
        color: #166534;
    }

    .badge-status.Rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-priority {
        padding: 3px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-priority.High {
        background-color: #fff5f5;
        color: #e53e3e;
        border: 1px solid #fed7d7;
    }

    .badge-priority.Medium {
        background-color: #fffaf0;
        color: #dd6b20;
        border: 1px solid #fbd38d;
    }

    .badge-priority.Low {
        background-color: #f7fafc;
        color: #4a5568;
        border: 1px solid #e2e8f0;
    }

    .action-buttons {
        display: flex;
        gap: 6px;
    }
</style>
@endsection

@section('content')
<div style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700;">Workflow Requests Management</h1>
    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Review, approve, or reject administrative request submissions.</p>
</div>

<!-- Search & Filters -->
<div class="filter-card">
    <form action="{{ route('all-requests') }}" method="GET">
        <div class="filter-grid">
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                    <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="form-group">
                <label for="priority">Priority</label>
                <select name="priority" id="priority" class="form-control">
                    <option value="">All Priorities</option>
                    <option value="Low" {{ request('priority') === 'Low' ? 'selected' : '' }}>Low</option>
                    <option value="Medium" {{ request('priority') === 'Medium' ? 'selected' : '' }}>Medium</option>
                    <option value="High" {{ request('priority') === 'High' ? 'selected' : '' }}>High</option>
                </select>
            </div>

            <div class="form-group">
                <label for="user_id">Requested By</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">All Admins</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="height: 38px; flex: 1;">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
                <a href="{{ route('all-requests') }}" class="btn btn-secondary" style="height: 38px; display: flex; align-items: center; justify-content: center;">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Requests List -->
<div class="list-card">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 15px;">
        Pending & Past Workflow Requests
    </div>
    <table class="requests-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Requested By</th>
                <th>Action Type</th>
                <th>Product</th>
                <th>Details</th>
                <th>Notes</th>
                <th>Priority</th>
                <th>Status</th>
                <th style="text-align: right;">Manage</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
                @php
                    $product = $req->product;
                    $stockNo = $product ? ($product->stock_no ?? $product->sku) : 'Deleted Product';
                @endphp
                <tr>
                    <td style="font-weight: 700;">#{{ $req->id }}</td>
                    <td>{{ $req->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <strong style="color: var(--primary-color);">{{ $req->user ? $req->user->name : 'N/A' }}</strong>
                        <div style="font-size: 10px; color: var(--text-muted);">Normal Admin</div>
                    </td>
                    <td style="font-weight: 600; color: var(--primary-hover);">{{ $req->request_type }}</td>
                    <td>
                        <div style="font-weight: 700;">{{ $stockNo }}</div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: capitalize;">{{ $req->product_type }}</div>
                    </td>
                    <td>
                        @if($req->request_type === 'Hold Inventory')
                            <span style="font-size: 12px;">Reason: <strong style="color: var(--warning-color);">{{ $req->action_payload['reason'] ?? '-' }}</strong></span>
                        @elseif($req->request_type === 'Release Inventory')
                            <span style="font-size: 12px;">Remarks: <strong>{{ $req->action_payload['remarks'] ?? '-' }}</strong></span>
                        @elseif($req->request_type === 'Price Change')
                            <span style="font-size: 12px; font-weight:700;">New Price: ${{ number_format($req->action_payload['price'] ?? 0, 2) }}</span>
                        @else
                            <span style="font-size: 12px; color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td>
                        <span style="font-size: 12px; color: var(--text-muted);">{{ $req->notes ?: '-' }}</span>
                    </td>
                    <td>
                        <span class="badge-priority {{ $req->priority }}">{{ $req->priority }}</span>
                    </td>
                    <td>
                        <span class="badge-status {{ $req->status }}">{{ $req->status }}</span>
                    </td>
                    <td>
                        <div class="action-buttons" style="justify-content: flex-end;">
                            @if($req->status === 'Pending')
                                <form action="{{ route('inventory.request.approve', $req->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to approve and execute this request?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; height: 30px;">
                                        <i class="fa-solid fa-check"></i> Approve
                                    </button>
                                </form>
                                <form action="{{ route('inventory.request.reject', $req->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to reject this request?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px; height: 30px; background-color: #fee2e2;">
                                        <i class="fa-solid fa-xmark"></i> Reject
                                    </button>
                                </form>
                            @else
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    Reviewed by: <strong>{{ $req->approver ? $req->approver->name : 'System' }}</strong>
                                    <div>{{ $req->approved_at ? $req->approved_at->format('Y-m-d') : '' }}</div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 40px;">No requests found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="pagination-container">
    {{ $requests->links() }}
</div>
@endsection
