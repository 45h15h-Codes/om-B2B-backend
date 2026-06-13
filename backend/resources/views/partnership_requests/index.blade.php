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
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
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
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
        font-size: 13.5px;
    }

    .requests-table th {
        background-color: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .requests-table td {
        padding: 16px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
        font-weight: 500;
    }

    .requests-table tr:hover td {
        background-color: #fcfdfe;
    }

    .badge-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
        text-transform: capitalize;
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

    .action-buttons {
        display: flex;
        gap: 6px;
        justify-content: flex-end;
    }
</style>
@endsection

@section('content')
<div style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700;">Partnership Requests</h1>
    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Manage and review B2B partner inquiry applications.</p>
</div>

<!-- Search & Filters -->
<div class="filter-card">
    <form action="{{ route('partnership-requests.index') }}" method="GET">
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
                <label for="search">Search</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Name, email, or business name" value="{{ request('search') }}" autocomplete="off">
            </div>

            <div class="form-group" style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="height: 38px; flex: 1;">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
                <a href="{{ route('partnership-requests.index') }}" class="btn btn-secondary" style="height: 38px; display: flex; align-items: center; justify-content: center;">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Requests List -->
<div class="list-card">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 15px;">
        Inquiry Submissions
    </div>
    <table class="requests-table">
        <thead>
            <tr>
                <th>Applicant Name</th>
                <th>Email Address</th>
                <th>Phone Number</th>
                <th>Business Name</th>
                <th>Business Type</th>
                <th>Status</th>
                <th>Submitted Date</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
                <tr>
                    <td>
                        <strong style="color: var(--primary-color);">{{ $req->full_name }}</strong>
                    </td>
                    <td>{{ $req->email }}</td>
                    <td>{{ $req->phone_number }}</td>
                    <td style="font-weight: 600;">{{ $req->business_name }}</td>
                    <td>{{ $req->business_type }}</td>
                    <td>
                        <span class="badge-status {{ $req->status }}">{{ $req->status }}</span>
                    </td>
                    <td style="color: var(--text-muted);">{{ $req->created_at->format('M d, Y H:i') }}</td>
                    <td>
                        <div class="action-buttons">
                            <a href="{{ route('partnership-requests.show', $req->id) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                <i class="fa-solid fa-circle-info"></i> View Details
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">No partnership requests found.</td>
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
