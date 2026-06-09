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
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
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

    .history-table-container {
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .history-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .history-table th {
        background-color: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    .history-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
        color: var(--text-color);
    }

    .history-table tr:hover {
        background-color: #f8fafc;
    }

    .badge-action {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: capitalize;
        display: inline-block;
    }

    .badge-action.hold {
        background-color: #fef3c7;
        color: #92400e;
    }

    .badge-action.release {
        background-color: #dcfce7;
        color: #166534;
    }

    .badge-action.sync {
        background-color: #e0f2fe;
        color: #0369a1;
    }

    .badge-action.price_change {
        background-color: #f3e8ff;
        color: #6b21a8;
    }

    .badge-action.assign_admin {
        background-color: #e2e8f0;
        color: #334155;
    }

        background-color: #ffedd5;
        color: #9a3412;
    }

    /* Tabs Navigation */
    .search-tabs-container {
        display: flex;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 20px;
        gap: 8px;
    }

    .search-tab-nav {
        padding: 12px 24px;
        font-weight: 700;
        font-size: 15px;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
        margin-bottom: -2px;
        text-decoration: none;
    }

    .search-tab-nav.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    .jewelery-tabs {
        display: flex;
        border-bottom: 2px solid var(--border-color);
        gap: 30px;
        margin-bottom: 20px;
    }

    .jewelery-tab-link {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-muted);
        text-decoration: none;
        padding-bottom: 12px;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .jewelery-tab-link.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
</style>
@endsection

@section('content')
@php
    $productType = request('product_type', 'diamond');
@endphp

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 700;">Inventory History Audit Log</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Track modifications, hold updates, price changes, and store sync activities.</p>
    </div>
</div>

@if($productType === 'jewelry')
    <div class="jewelery-tabs">
        <a href="{{ route('jewelery.index') }}" class="jewelery-tab-link" style="text-decoration: none;">Search</a>
        <a href="{{ route('inventory.index', ['product_type' => 'jewelry']) }}" class="jewelery-tab-link" style="text-decoration: none;">Inventory</a>
        <a href="{{ route('inventory-history.index', ['product_type' => 'jewelry']) }}" class="jewelery-tab-link active" style="text-decoration: none;">Inventory History</a>
    </div>
@else
    <div class="search-tabs-container">
        <a href="{{ route('diamonds.index') }}" class="search-tab-nav" style="text-decoration: none;">
            Search Single Diamonds
        </a>
        <a href="{{ route('inventory.index', ['product_type' => 'diamond']) }}" class="search-tab-nav" style="text-decoration: none;">
            Inventory
        </a>
        <a href="{{ route('inventory-history.index', ['product_type' => 'diamond']) }}" class="search-tab-nav active" style="text-decoration: none;">
            Inventory History
        </a>
    </div>
@endif

<!-- Search & Filters -->
<div class="filter-card">
    <form action="{{ route('inventory-history.index') }}" method="GET">
        <div class="filter-grid">
            <div class="form-group">
                <label for="product_type">Product Type</label>
                <select name="product_type" id="product_type" class="form-control">
                    <option value="">All Types</option>
                    <option value="diamond" {{ request('product_type') === 'diamond' ? 'selected' : '' }}>Diamond</option>
                    <option value="jewelry" {{ request('product_type') === 'jewelry' ? 'selected' : '' }}>Jewelry</option>
                </select>
            </div>

            <div class="form-group">
                <label for="action">Action Type</label>
                <select name="action" id="action" class="form-control">
                    <option value="">All Actions</option>
                    <option value="hold" {{ request('action') === 'hold' ? 'selected' : '' }}>Hold</option>
                    <option value="release" {{ request('action') === 'release' ? 'selected' : '' }}>Release</option>
                    <option value="sync" {{ request('action') === 'sync' ? 'selected' : '' }}>Shopify Sync</option>
                    <option value="price_change" {{ request('action') === 'price_change' ? 'selected' : '' }}>Price Change</option>
                    <option value="assign_admin" {{ request('action') === 'assign_admin' ? 'selected' : '' }}>Assign Admin</option>
                    <option value="correction" {{ request('action') === 'correction' ? 'selected' : '' }}>Correction</option>
                </select>
            </div>

            <div class="form-group">
                <label for="user_id">Triggered By</label>
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ ucfirst(str_replace('_', ' ', $user->role)) }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>

            <div class="form-group" style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="height: 38px; flex: 1;">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
                <a href="{{ route('inventory-history.index') }}" class="btn btn-secondary" style="height: 38px; display: flex; align-items: center; justify-content: center;">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- History Table -->
<div class="history-table-container">
    <table class="history-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Action Date</th>
                <th>Product Type</th>
                <th>Stock No / SKU</th>
                <th>Action Type</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Triggered By</th>
                <th>Remarks / Reason</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($histories as $history)
                @php
                    $product = $history->product;
                    $stockNo = $product ? ($product->stock_no ?? $product->sku ?? 'ID: '.$product->id) : 'Deleted Product (ID: '.$history->product_id.')';
                @endphp
                <tr>
                    <td>#{{ $history->id }}</td>
                    <td>{{ $history->created_at->format('Y-m-d H:i:s') }}</td>
                    <td style="text-transform: capitalize; font-weight: 600;">{{ $history->product_type }}</td>
                    <td style="font-weight: 700;">{{ $stockNo }}</td>
                    <td>
                        <span class="badge-action {{ $history->action }}">
                            {{ str_replace('_', ' ', $history->action) }}
                        </span>
                    </td>
                    <td style="color: var(--text-muted);">{{ $history->old_value ?? '-' }}</td>
                    <td style="font-weight: 600;">{{ $history->new_value ?? '-' }}</td>
                    <td>
                        @if($history->user)
                            <strong style="color: var(--primary-color);">{{ $history->user->name }}</strong>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ ucfirst(str_replace('_', ' ', $history->user->role)) }}</div>
                        @else
                            <span style="color: var(--text-muted);">System / Sync</span>
                        @endif
                    </td>
                    <td>{{ $history->remarks ?: '-' }}</td>
                    <td style="font-family: monospace; font-size: 12px; color: var(--text-muted);">{{ $history->ip_address ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 40px;">No inventory logs found matching your filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="pagination-container">
    {{ $histories->links() }}
</div>
@endsection
