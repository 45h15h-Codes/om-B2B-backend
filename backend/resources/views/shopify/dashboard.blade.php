@extends('layouts.app')

@section('styles')
<style>
    .shopify-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .shopify-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        padding: 20px 24px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
    }

    .shopify-title h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .connection-status {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .connection-status.active {
        background-color: #e6fffa;
        color: #0b69a3;
        border: 1px solid #b2f5ea;
    }

    .connection-status.inactive {
        background-color: #fff5f5;
        color: var(--error-color);
        border: 1px solid #fed7d7;
    }

    /* Cards Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
    }

    .stat-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 12px -3px rgba(0, 0, 0, 0.04);
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .stat-card.diamonds .stat-icon { background: #e8f4f8; color: #108bb6; }
    .stat-card.jewelry .stat-icon { background: #fdf2f8; color: #db2777; }
    .stat-card.synced .stat-icon { background: #f0fdf4; color: #16a34a; }
    .stat-card.pending .stat-icon { background: #fffbeb; color: #d97706; }
    .stat-card.failed .stat-icon { background: #fdf2f2; color: #dc2626; }

    .stat-info {
        display: flex;
        flex-direction: column;
    }

    .stat-val {
        font-size: 22px;
        font-weight: 700;
        color: var(--text-color);
    }

    .stat-lbl {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-muted);
    }

    /* Actions bar */
    .actions-bar {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--border-color);
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
    }

    /* Navigation Tabs */
    .tabs-nav {
        display: flex;
        border-bottom: 2px solid var(--border-color);
        gap: 8px;
        margin-bottom: 20px;
    }

    .tab-nav-btn {
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 700;
        color: var(--text-muted);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
        margin-bottom: -2px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .tab-nav-btn:hover {
        color: var(--primary-color);
    }

    .tab-nav-btn.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    /* Filter Panel */
    .filter-panel {
        background: #f8fafc;
        border-radius: 8px;
        padding: 16px;
        border: 1px solid var(--border-color);
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        margin-bottom: 20px;
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        width: 100%;
    }

    .filter-input-group {
        display: flex;
        align-items: center;
        position: relative;
        flex: 1;
        min-width: 200px;
    }

    .filter-input {
        width: 100%;
        padding: 8px 12px 8px 36px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 13.5px;
        font-family: inherit;
        outline: none;
    }

    .filter-input:focus {
        border-color: var(--primary-color);
    }

    .filter-icon {
        position: absolute;
        left: 12px;
        color: var(--text-muted);
        font-size: 14px;
    }

    .filter-select {
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 13.5px;
        font-family: inherit;
        outline: none;
        background: #ffffff;
        min-width: 140px;
        cursor: pointer;
    }

    /* Dashboard Lists */
    .list-wrapper {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.01);
    }

    .list-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .list-table th {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        padding: 12px 16px;
        border-bottom: 2px solid var(--border-color);
    }

    .list-table td {
        font-size: 13.5px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
        vertical-align: middle;
    }

    .list-table tr:last-child td {
        border-bottom: none;
    }

    /* Badges */
    .sync-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-transform: uppercase;
    }

    .sync-badge.synced { background: #e6fffa; color: #047857; }
    .sync-badge.pending { background: #fffbeb; color: #b45309; }
    .sync-badge.failed { background: #fff5f5; color: #b91c1c; }
    .sync-badge.not-synced { background: #f1f5f9; color: #64748b; }

    /* Pagination design overrides */
    .pagination-container {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        width: 100%;
    }

    .pagination-container nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        margin-top: 15px;
        padding: 10px 0;
    }

    .pagination-container svg {
        width: 16px !important;
        height: 16px !important;
        display: inline-block;
        vertical-align: middle;
    }

    .pagination-container .flex {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pagination-container nav .flex.justify-between.flex-1.sm:hidden {
        display: none !important;
    }

    .pagination-container nav .hidden.sm:flex-1 {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 16px;
    }

    .pagination-container span.relative {
        display: inline-flex;
        border-radius: 6px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .pagination-container a, 
    .pagination-container span[aria-current="page"] span,
    .pagination-container span[aria-disabled="true"] span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 36px;
        min-width: 36px;
        padding: 0 12px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-color);
        border: 1px solid var(--border-color);
        border-right: none;
        text-decoration: none;
        background: #ffffff;
        transition: background-color 0.15s ease;
    }

    .pagination-container a:first-child,
    .pagination-container span[aria-disabled="true"]:first-child span {
        border-top-left-radius: 6px;
        border-bottom-left-radius: 6px;
    }

    .pagination-container a:last-child,
    .pagination-container span[aria-disabled="true"]:last-child span {
        border-top-right-radius: 6px;
        border-bottom-right-radius: 6px;
        border-right: 1px solid var(--border-color);
    }

    .pagination-container a:hover {
        background-color: var(--primary-light);
        color: var(--primary-color);
    }

    .pagination-container span[aria-current="page"] span {
        background-color: var(--primary-color);
        color: #ffffff;
        border-color: var(--primary-color);
        cursor: default;
    }

    .pagination-container span[aria-disabled="true"] span {
        color: var(--text-muted);
        cursor: not-allowed;
        background-color: #f8fafc;
    }

    /* Tooltips */
    .error-info-trigger {
        cursor: pointer;
        position: relative;
        display: inline-block;
    }

    .error-info-tooltip {
        display: none;
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        background: #1e293b;
        color: #ffffff;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 11.5px;
        width: 240px;
        z-index: 100;
        white-space: normal;
        line-height: 1.4;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.25);
    }

    .error-info-trigger:hover .error-info-tooltip {
        display: block;
    }
</style>
@endsection

@section('content')
<div class="shopify-container">
    <!-- Header banner -->
    <div class="shopify-header">
        <div class="shopify-store-info">
            <h2>
                <i class="fa-brands fa-shopify" style="color: #96bf48; font-size: 26px;"></i>
                Shopify Integration Dashboard (Super Admin)
            </h2>
            <p style="font-size:13.5px; color: var(--text-muted); margin-top:4px;">Manage all synced diamonds, jewelry, and customer orders across connected stores.</p>
        </div>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="stats-grid" style="margin-bottom: 10px;">
        <div class="stat-card" style="border-left: 4px solid var(--primary-color);">
            <div class="stat-icon" style="background: var(--primary-light); color: var(--primary-color);"><i class="fa-solid fa-cart-shopping"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $orderStats['total'] ?? 0 }}</span>
                <span class="stat-lbl">Total Orders</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--warning-color);">
            <div class="stat-icon" style="background: #fffbeb; color: var(--warning-color);"><i class="fa-solid fa-spinner fa-spin"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $orderStats['pending'] ?? 0 }}</span>
                <span class="stat-lbl">Pending Sync</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--primary-color);">
            <div class="stat-icon" style="background: #eff6ff; color: #1d4ed8;"><i class="fa-solid fa-check"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $orderStats['synced'] ?? 0 }}</span>
                <span class="stat-lbl">Synced</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--success-color);">
            <div class="stat-icon" style="background: #e6fffa; color: var(--success-color);"><i class="fa-solid fa-credit-card"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $orderStats['paid'] ?? 0 }}</span>
                <span class="stat-lbl">Paid</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--error-color);">
            <div class="stat-icon" style="background: #fff5f5; color: var(--error-color);"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $orderStats['failed'] ?? 0 }}</span>
                <span class="stat-lbl">Failed</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #64748b;">
            <div class="stat-icon" style="background: #f1f5f9; color: #64748b;"><i class="fa-solid fa-ban"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $orderStats['cancelled'] ?? 0 }}</span>
                <span class="stat-lbl">Cancelled</span>
            </div>
        </div>
    </div>

    <!-- Inventory & Integration Overview Grid -->
    <h3 style="font-size: 15px; font-weight: 700; color: var(--text-color); margin-top: 5px; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
        <i class="fa-solid fa-chart-simple" style="color: var(--primary-color);"></i> Inventory & Store Stats
    </h3>
    <div class="stats-grid" style="margin-bottom: 25px;">
        <div class="stat-card" style="border-left: 4px solid var(--warning-color);">
            <div class="stat-icon" style="background: #fffbeb; color: var(--warning-color);"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $inventoryOnHold ?? 0 }}</span>
                <span class="stat-lbl">Inventory On Hold</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--success-color);">
            <div class="stat-icon" style="background: #ecfdf5; color: #047857;"><i class="fa-solid fa-tags"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $inventorySoldToday ?? 0 }}</span>
                <span class="stat-lbl">Inventory Sold Today</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--primary-color);">
            <div class="stat-icon" style="background: var(--primary-light); color: var(--primary-color);"><i class="fa-solid fa-box-open"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $inventoryAvailable ?? 0 }}</span>
                <span class="stat-lbl">Inventory Available</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
            <div class="stat-icon" style="background: #f5f3ff; color: #8b5cf6;"><i class="fa-solid fa-store"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $activeStoresCount ?? 0 }}</span>
                <span class="stat-lbl">Active Shopify Stores</span>
            </div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #ef4444;">
            <div class="stat-icon" style="background: #fef2f2; color: #ef4444;"><i class="fa-solid fa-bell"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $unreadNotificationsCount ?? 0 }}</span>
                <span class="stat-lbl">Unread Notifications</span>
            </div>
        </div>
    </div>

    <!-- Store Performance Rankings Widget -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.01); margin-bottom: 25px;">
        <h3 style="font-size: 16px; font-weight: 700; color: var(--text-color); margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-trophy" style="color: #d97706;"></i> Shopify Store Performance Rankings
        </h3>
        <div style="overflow-x: auto;">
            <table class="list-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="padding: 10px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rank</th>
                        <th style="padding: 10px; text-align: left; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Store Name</th>
                        <th style="padding: 10px; text-align: right; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Revenue</th>
                        <th style="padding: 10px; text-align: center; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Paid Orders</th>
                        <th style="padding: 10px; text-align: center; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Diamonds Sold</th>
                        <th style="padding: 10px; text-align: center; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jewelry Sold</th>
                        <th style="padding: 10px; text-align: right; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Conversion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rankings as $index => $ranking)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 10px; font-weight: 700; color: var(--text-color);">
                                @if($index == 0)
                                    <span style="background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 12px; font-size: 12px;"><i class="fa-solid fa-crown"></i> 1</span>
                                @elseif($index == 1)
                                    <span style="background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 12px; font-size: 12px;">2</span>
                                @elseif($index == 2)
                                    <span style="background: #ffedd5; color: #ea580c; padding: 4px 8px; border-radius: 12px; font-size: 12px;">3</span>
                                @else
                                    <span style="padding-left: 8px;">{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td style="padding: 12px 10px; font-weight: 600; color: var(--text-color);">{{ $ranking['store_name'] }}</td>
                            <td style="padding: 12px 10px; text-align: right; font-weight: 700; color: var(--success-color);">${{ number_format($ranking['revenue'], 2) }}</td>
                            <td style="padding: 12px 10px; text-align: center; font-weight: 600;">{{ $ranking['orders_count'] }}</td>
                            <td style="padding: 12px 10px; text-align: center; color: var(--primary-color); font-weight: 600;">{{ $ranking['diamonds_sold'] }}</td>
                            <td style="padding: 12px 10px; text-align: center; color: #db2777; font-weight: 600;">{{ $ranking['jewelry_sold'] }}</td>
                            <td style="padding: 12px 10px; text-align: right; font-weight: 700; color: var(--primary-color);">
                                {{ number_format($ranking['conversion_rate'], 2) }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 20px 0; font-style: italic;">No store performance data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabs Layout Navigation -->
    <div>
        <div class="tabs-nav">
            <a href="{{ route('shopify.dashboard', ['tab' => 'diamonds']) }}" class="tab-nav-btn {{ $activeTab === 'diamonds' ? 'active' : '' }}">
                <i class="fa-solid fa-gem"></i> Diamonds Catalog
            </a>
            <a href="{{ route('shopify.dashboard', ['tab' => 'jewelry']) }}" class="tab-nav-btn {{ $activeTab === 'jewelry' ? 'active' : '' }}">
                <i class="fa-solid fa-ring"></i> Jewelry Catalog
            </a>
            <a href="{{ route('shopify.dashboard', ['tab' => 'synced']) }}" class="tab-nav-btn {{ $activeTab === 'synced' ? 'active' : '' }}">
                <i class="fa-brands fa-shopify"></i> Synced Records (Unified)
            </a>
            <a href="{{ route('shopify.dashboard', ['tab' => 'reservations']) }}" class="tab-nav-btn {{ $activeTab === 'reservations' ? 'active' : '' }}">
                <i class="fa-solid fa-history"></i> Reservation History
            </a>
            <a href="{{ route('shopify.dashboard', ['tab' => 'audits']) }}" class="tab-nav-btn {{ $activeTab === 'audits' ? 'active' : '' }}">
                <i class="fa-solid fa-clipboard-list"></i> Sync Audit Logs
            </a>
        </div>

        <!-- Filter bar -->
        <div class="filter-panel">
            <form action="{{ route('shopify.dashboard') }}" method="GET" class="filter-form">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                
                <div class="filter-input-group">
                    <i class="fa-solid fa-magnifying-glass filter-icon"></i>
                    <input type="text" name="q" class="filter-input" placeholder="Search by SKU, Name, or Cert..." value="{{ request('q') }}">
                </div>

                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">Filter Status (All)</option>
                    @if($activeTab === 'synced')
                        <option value="synced" {{ request('status') === 'synced' ? 'selected' : '' }}>Synced</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    @else
                        <option value="not_synced" {{ request('status') === 'not_synced' ? 'selected' : '' }}>Not Synced</option>
                        <option value="synced" {{ request('status') === 'synced' ? 'selected' : '' }}>Synced</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    @endif
                </select>

                @if(in_array($activeTab, ['diamonds', 'jewelry']))
                <select name="inventory_status" class="filter-select" onchange="this.form.submit()">
                    <option value="">Inventory Status (All)</option>
                    <option value="available" {{ request('inventory_status') === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="on_hold" {{ request('inventory_status') === 'on_hold' ? 'selected' : '' }}>Hold</option>
                    <option value="sold" {{ request('inventory_status') === 'sold' ? 'selected' : '' }}>Sold</option>
                </select>
                @endif

                <button type="submit" class="btn btn-primary" style="height: 35px; padding: 0 16px;">Filter</button>
                @if(request()->filled('q') || request()->filled('status') || request()->filled('inventory_status'))
                    <a href="{{ route('shopify.dashboard', ['tab' => $activeTab]) }}" class="btn btn-secondary" style="height: 35px; padding: 0 16px; align-items: center; display: inline-flex;">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Tab 1 Contents: Diamonds -->
        @if($activeTab === 'diamonds')
            <div class="list-wrapper">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>Stock No / Cert</th>
                            <th>Description Detail</th>
                            <th>Asking Price</th>
                            <th>Admin / Store</th>
                            <th>Inventory Status</th>
                            <th>Shopify Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($diamonds as $diamond)
                            @php $sync = $diamond->shopifyProduct; @endphp
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color);">#{{ $diamond->stock_no ?: 'N/A' }}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">Cert: {{ $diamond->report_no ?: '-' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">{{ $diamond->shape ?: 'Round' }} {{ floatval($diamond->size) }}ct</div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);">
                                        Color: {{ $diamond->color ?: '-' }} | Clarity: {{ $diamond->clarity ?: '-' }} | Cut: {{ $diamond->cut ?: '-' }}
                                    </div>
                                </td>
                                <td>
                                    <strong>${{ number_format($diamond->asking_price ?: 0.00, 2) }}</strong>
                                </td>
                                <td>
                                    @if($diamond->user)
                                        <div style="font-weight: 600; color: var(--text-color);">{{ $diamond->user->name }}</div>
                                        <div style="font-size: 11px; color: var(--text-muted);">{{ $diamond->user->activeShopifyStore ? $diamond->user->activeShopifyStore->store_name : 'No Active Store' }}</div>
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if(($diamond->inventory_status ?? 'available') === 'available')
                                        <span class="sync-badge synced"><i class="fa-solid fa-circle-check"></i> Available</span>
                                    @elseif($diamond->inventory_status === 'on_hold')
                                        <span class="sync-badge pending"><i class="fa-solid fa-clock"></i> Hold</span>
                                    @elseif($diamond->inventory_status === 'sold')
                                        <span class="sync-badge failed"><i class="fa-solid fa-circle-xmark"></i> Sold</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$sync)
                                        <span class="sync-badge not-synced"><i class="fa-solid fa-cloud"></i> Not Synced</span>
                                    @elseif($sync->sync_status === 'synced')
                                        <span class="sync-badge synced"><i class="fa-solid fa-circle-check"></i> Synced</span>
                                    @elseif($sync->sync_status === 'processing')
                                        <span class="sync-badge pending"><i class="fa-solid fa-spinner fa-spin"></i> Syncing...</span>
                                    @elseif($sync->sync_status === 'failed')
                                        <div class="error-info-trigger">
                                            <span class="sync-badge failed"><i class="fa-solid fa-circle-xmark"></i> Failed</span>
                                            <div class="error-info-tooltip">
                                                <strong>Sync Error:</strong> {{ $sync->sync_message ?? 'Unknown Shopify API Failure.' }}
                                                <br><span style="font-size: 9.5px; opacity:0.8;">Attempts: {{ $sync->sync_attempts }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="sync-badge pending"><i class="fa-solid fa-clock"></i> Pending</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    @if(!$sync)
                                        @if(($diamond->inventory_status ?? 'available') === 'available')
                                            <form action="{{ route('shopify.publish-diamond', $diamond->id) }}" method="POST" style="display:inline-block; margin:0;">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary" style="font-size:12px; padding: 6px 12px; border-color: #b0d4e3; background: #e8f4f8; color: var(--primary-color);">
                                                    Publish to Shopify
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted" style="font-size:11.5px; font-style:italic;">Blocked ({{ ucfirst($diamond->inventory_status) }})</span>
                                        @endif
                                    @elseif($sync->sync_status === 'synced')
                                        @if($sync->shopify_url)
                                            <a href="{{ $sync->shopify_url }}" target="_blank" class="btn btn-primary" style="font-size:12px; padding: 6px 12px; text-decoration: none; background-color: #96bf48; border-color: #96bf48;">
                                                <i class="fa-brands fa-shopify"></i> Visit Shopify
                                            </a>
                                        @endif
                                    @elseif($sync->sync_status === 'failed')
                                        @if(($diamond->inventory_status ?? 'available') === 'available')
                                            <form action="{{ route('shopify.retry', $sync->id) }}" method="POST" style="display:inline-block; margin:0;">
                                                @csrf
                                                <button type="submit" class="btn btn-danger" style="font-size:12px; padding: 6px 12px; background-color: var(--error-color); color: white; border: none;">
                                                    Retry Sync
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted" style="font-size:11.5px; font-style:italic;">Blocked ({{ ucfirst($diamond->inventory_status) }})</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px 0; font-style: italic;">
                                    No diamonds found matching current queries.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="pagination-container">
                    {{ $diamonds->links() }}
                </div>
            </div>
        @endif

        <!-- Tab 2 Contents: Jewelry -->
        @if($activeTab === 'jewelry')
            <div class="list-wrapper">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Category Type</th>
                            <th>Price</th>
                            <th>Admin / Store</th>
                            <th>Inventory Status</th>
                            <th>Shopify Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jewelry as $item)
                            @php $sync = $item->shopifyProduct; @endphp
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color);">#{{ $item->sku ?: 'N/A' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">{{ $item->name }}</div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);">Creator: {{ $item->created_by }} | Stock: {{ $item->in_stock ?? 1 }}</div>
                                </td>
                                <td>
                                    <span class="card-type-badge">{{ $item->type ?: 'Jewelry' }}</span>
                                </td>
                                <td>
                                    <strong>${{ number_format($item->price ?: 0.00, 2) }}</strong>
                                </td>
                                <td>
                                    @if($item->user)
                                        <div style="font-weight: 600; color: var(--text-color);">{{ $item->user->name }}</div>
                                        <div style="font-size: 11px; color: var(--text-muted);">{{ $item->user->activeShopifyStore ? $item->user->activeShopifyStore->store_name : 'No Active Store' }}</div>
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if(($item->inventory_status ?? 'available') === 'available')
                                        <span class="sync-badge synced"><i class="fa-solid fa-circle-check"></i> Available</span>
                                    @elseif($item->inventory_status === 'on_hold')
                                        <span class="sync-badge pending"><i class="fa-solid fa-clock"></i> Hold</span>
                                    @elseif($item->inventory_status === 'sold')
                                        <span class="sync-badge failed"><i class="fa-solid fa-circle-xmark"></i> Sold</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$sync)
                                        <span class="sync-badge not-synced"><i class="fa-solid fa-cloud"></i> Not Synced</span>
                                    @elseif($sync->sync_status === 'synced')
                                        <span class="sync-badge synced"><i class="fa-solid fa-circle-check"></i> Synced</span>
                                    @elseif($sync->sync_status === 'processing')
                                        <span class="sync-badge pending"><i class="fa-solid fa-spinner fa-spin"></i> Syncing...</span>
                                    @elseif($sync->sync_status === 'failed')
                                        <div class="error-info-trigger">
                                            <span class="sync-badge failed"><i class="fa-solid fa-circle-xmark"></i> Failed</span>
                                            <div class="error-info-tooltip">
                                                <strong>Sync Error:</strong> {{ $sync->sync_message ?? 'Unknown Shopify API Failure.' }}
                                                <br><span style="font-size: 9.5px; opacity:0.8;">Attempts: {{ $sync->sync_attempts }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="sync-badge pending"><i class="fa-solid fa-clock"></i> Pending</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    @if(!$sync)
                                        @if(($item->inventory_status ?? 'available') === 'available')
                                            <form action="{{ route('shopify.publish-jewelry', $item->id) }}" method="POST" style="display:inline-block; margin:0;">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary" style="font-size:12px; padding: 6px 12px; border-color: #fbcfe8; background: #fdf2f8; color: #db2777;">
                                                    Publish to Shopify
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted" style="font-size:11.5px; font-style:italic;">Blocked ({{ ucfirst($item->inventory_status) }})</span>
                                        @endif
                                    @elseif($sync->sync_status === 'synced')
                                        @if($sync->shopify_url)
                                            <a href="{{ $sync->shopify_url }}" target="_blank" class="btn btn-primary" style="font-size:12px; padding: 6px 12px; text-decoration: none; background-color: #96bf48; border-color: #96bf48;">
                                                <i class="fa-brands fa-shopify"></i> Visit Shopify
                                            </a>
                                        @endif
                                    @elseif($sync->sync_status === 'failed')
                                        @if(($item->inventory_status ?? 'available') === 'available')
                                            <form action="{{ route('shopify.retry', $sync->id) }}" method="POST" style="display:inline-block; margin:0;">
                                                @csrf
                                                <button type="submit" class="btn btn-danger" style="font-size:12px; padding: 6px 12px; background-color: var(--error-color); color: white; border: none;">
                                                    Retry Sync
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted" style="font-size:11.5px; font-style:italic;">Blocked ({{ ucfirst($item->inventory_status) }})</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px 0; font-style: italic;">
                                    No jewelry items found matching current queries.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="pagination-container">
                    {{ $jewelry->links() }}
                </div>
            </div>
        @endif

        <!-- Tab 3 Contents: Synced Products (Unified View) -->
        @if($activeTab === 'synced')
            <div class="list-wrapper">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Catalog Type</th>
                            <th>Shopify Product ID</th>
                            <th>Admin / Store</th>
                            <th>Sync Status</th>
                            <th>Synced At</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shopifyProducts as $sync)
                            @php $product = $sync->product; @endphp
                            <tr>
                                <td>
                                    @if($product)
                                        @if($sync->product_type === 'diamond')
                                            <div style="font-weight: 700; color: var(--text-color);">
                                                {{ $product->shape }} {{ floatval($product->size) }}ct
                                            </div>
                                            <div style="font-size: 11px; color: var(--text-muted);">
                                                SKU: {{ $product->stock_no }} | Cert: {{ $product->report_no ?: '-' }}
                                            </div>
                                        @else
                                            <div style="font-weight: 700; color: var(--text-color);">
                                                {{ $product->name }}
                                            </div>
                                            <div style="font-size: 11px; color: var(--text-muted);">
                                                SKU: {{ $product->sku }} | Type: {{ $product->type ?: 'Jewelry' }}
                                            </div>
                                        @endif
                                    @else
                                        <div style="color: var(--text-muted); font-style: italic;">Deleted Product Record</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="sync-badge" style="background:#e8f4f8; color:#108bb6;">
                                        {{ ucfirst($sync->product_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($sync->shopify_product_id)
                                        @if($sync->shopify_url)
                                            <a href="{{ $sync->shopify_url }}" target="_blank" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                                {{ $sync->shopify_product_id }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px; margin-left: 2px;"></i>
                                            </a>
                                        @else
                                            {{ $sync->shopify_product_id }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($sync->product && $sync->product->user)
                                        <div style="font-weight: 600; color: var(--text-color);">{{ $sync->product->user->name }}</div>
                                        <div style="font-size: 11px; color: var(--text-muted);">{{ $sync->shopifyStore ? $sync->shopifyStore->store_name : 'No Store Connected' }}</div>
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sync->sync_status === 'failed')
                                        <div class="error-info-trigger">
                                            <span class="sync-badge failed"><i class="fa-solid fa-triangle-exclamation"></i> Failed</span>
                                            <div class="error-info-tooltip">
                                                <strong>Sync Error:</strong> {{ $sync->sync_message ?? 'Unknown Shopify API Failure.' }}
                                                <br><span style="font-size: 9.5px; opacity:0.8;">Attempts: {{ $sync->sync_attempts }}</span>
                                            </div>
                                        </div>
                                    @elseif($sync->sync_status === 'synced')
                                        <span class="sync-badge synced"><i class="fa-solid fa-check-double"></i> Synced</span>
                                    @elseif($sync->sync_status === 'processing')
                                        <span class="sync-badge pending"><i class="fa-solid fa-spinner fa-spin"></i> Syncing...</span>
                                    @else
                                        <span class="sync-badge pending"><i class="fa-solid fa-clock"></i> Pending</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $sync->synced_at ? $sync->synced_at->diffForHumans() : '-' }}
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 8px; justify-content: flex-end; align-items: center;">
                                        @if($sync->shopify_url)
                                            <a href="{{ $sync->shopify_url }}" target="_blank" class="btn btn-secondary" style="font-size:12px; padding: 6px 12px;" title="Visit Shopify Storefront">
                                                Visit Shopify
                                            </a>
                                        @endif
                                        @if($sync->sync_status === 'failed')
                                            <form action="{{ route('shopify.retry', $sync->id) }}" method="POST" style="margin:0; display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary" style="font-size:12px; padding: 6px 12px;" title="Retry Background Sync">
                                                    Retry
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('shopify.delete-sync', $sync->id) }}" method="POST" class="confirm-delete-form" data-username="Sync Record ID #{{ $sync->id }}" style="margin:0; display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="font-size:12px; padding: 6px 12px; background: #fff5f5; color: var(--error-color); border-color:#fed7d7;" title="Delete local sync association record">
                                                Delete Link
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px 0; font-style: italic;">
                                    No synchronized product records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="pagination-container">
                    {{ $shopifyProducts->links() }}
                </div>
            </div>
        @endif

        <!-- Tab 4 Contents: Reservation History -->
        @if($activeTab === 'reservations')
            <div class="list-wrapper">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Shopify Store</th>
                            <th>Local Order</th>
                            <th>Shopify Order ID</th>
                            <th>Reservation Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservations as $reservation)
                            @php $product = $reservation->product; @endphp
                            <tr>
                                <td>
                                    @if($product)
                                        <div style="font-weight: 700; color: var(--text-color);">
                                            {{ $reservation->product_type === 'diamond' ? ($product->shape . ' ' . floatval($product->size) . 'ct') : $product->name }}
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-muted);">
                                            SKU: {{ $reservation->product_type === 'diamond' ? $product->stock_no : $product->sku }} | Type: {{ ucfirst($reservation->product_type) }}
                                        </div>
                                    @else
                                        <div style="color: var(--text-muted); font-style: italic;">Deleted Product Record</div>
                                    @endif
                                </td>
                                <td>
                                    @if($reservation->shopifyStore)
                                        <div style="font-weight: 600; color: var(--text-color);">{{ $reservation->shopifyStore->store_name }}</div>
                                        <div style="font-size: 11px; color: var(--text-muted);">{{ $reservation->shopifyStore->shop_domain }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($reservation->order)
                                        <a href="{{ route('orders.show', $reservation->order_id) }}" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                            Order #{{ $reservation->order->shopify_order_number ?: $reservation->order->id }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($reservation->shopify_order_id)
                                        {{ $reservation->shopify_order_id }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($reservation->status === 'hold')
                                        <span class="sync-badge pending"><i class="fa-solid fa-clock"></i> Hold</span>
                                    @elseif($reservation->status === 'released')
                                        <span class="sync-badge not-synced"><i class="fa-solid fa-ban"></i> Released</span>
                                    @elseif($reservation->status === 'completed')
                                        <span class="sync-badge synced"><i class="fa-solid fa-check-double"></i> Completed</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $reservation->created_at ? $reservation->created_at->diffForHumans() : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px 0; font-style: italic;">
                                    No reservation history logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="pagination-container">
                    {{ $reservations->links() }}
                </div>
        @endif
 
        <!-- Tab 5 Contents: Sync Audit Logs -->
        @if($activeTab === 'audits')
            <div class="list-wrapper">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>Product Info</th>
                            <th>Sold Store</th>
                            <th>Auto-Drafted Stores</th>
                            <th>Order No</th>
                            <th>Sold Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $audit)
                            @php
                                $product = $audit->diamond ?? $audit->jewelry;
                                $isDiamond = $audit->diamond_id ? true : false;
                            @endphp
                            <tr>
                                <td>
                                    @if($product)
                                        <div style="font-weight: 700; color: var(--text-color);">
                                            @if($isDiamond)
                                                Diamond #{{ $product->stock_no }}
                                                <div style="font-size: 11px; color: var(--text-muted);">{{ $product->shape }} {{ floatval($product->size) }}ct</div>
                                            @else
                                                Jewelry #{{ $product->sku }}
                                                <div style="font-size: 11px; color: var(--text-muted);">{{ $product->name }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <div style="color: var(--text-muted); font-style: italic;">Deleted Product (#{{ $audit->stock_no }})</div>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: var(--text-color);">{{ $audit->shopifyStore ? $audit->shopifyStore->store_name : 'Shopify Store' }}</span>
                                </td>
                                <td>
                                    @if(!empty($audit->auto_drafted_stores))
                                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                            @foreach($audit->auto_drafted_stores as $storeName)
                                                <span style="background: #fff5f5; color: var(--error-color); border: 1px solid #fed7d7; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700;">
                                                    {{ $storeName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">None</span>
                                    @endif
                                </td>
                                <td>
                                    @if($audit->diamond && $audit->diamond->sold_order_number)
                                        <span style="font-weight: 700; color: var(--primary-color);">#{{ $audit->diamond->sold_order_number }}</span>
                                    @elseif($audit->jewelry && $audit->jewelry->sold_order_number)
                                        <span style="font-weight: 700; color: var(--primary-color);">#{{ $audit->jewelry->sold_order_number }}</span>
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $audit->created_at ? $audit->created_at->format('Y-m-d H:i:s') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px 0; font-style: italic;">
                                    No sync audit logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
 
                <div class="pagination-container">
                    {{ $audits->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
