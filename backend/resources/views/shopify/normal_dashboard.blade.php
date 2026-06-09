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

    /* Actions panel */
    .actions-panel {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }

    .panel-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 16px;
        color: var(--text-color);
    }

    .actions-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    /* Tables */
    .tables-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
    }

    @media (min-width: 1024px) {
        .tables-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .table-container {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        padding: 24px;
        overflow: hidden;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .shopify-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .shopify-table th {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        padding: 12px 16px;
        border-bottom: 2px solid var(--border-color);
    }

    .shopify-table td {
        font-size: 13px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
        vertical-align: middle;
    }

    .shopify-table tr:last-child td {
        border-bottom: none;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .status-badge.synced { background: #ecfdf5; color: #047857; }
    .status-badge.pending { background: #fffbeb; color: #b45309; }
    .status-badge.processing { background: #eff6ff; color: #1d4ed8; }
    .status-badge.failed { background: #fdf2f2; color: #b91c1c; }

    .btn-icon-only {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color);
        background: #ffffff;
        color: var(--text-color);
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-icon-only:hover {
        background: var(--primary-light);
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .error-tooltip-wrapper {
        position: relative;
        display: inline-block;
    }

    .error-tooltip-content {
        display: none;
        position: absolute;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        background: #1e293b;
        color: #ffffff;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 11px;
        white-space: normal;
        width: 200px;
        z-index: 10;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
    }

    .error-tooltip-wrapper:hover .error-tooltip-content {
        display: block;
    }
</style>
@endsection

@section('content')
<div class="shopify-container">
    <!-- Header with Connection Status -->
    <div class="shopify-header">
        <div class="shopify-store-info">
            <h2>
                <i class="fa-brands fa-shopify" style="color: #96bf48; font-size: 26px;"></i>
                My Shopify Storefront
            </h2>
            <p style="font-size:13.5px; color: var(--text-muted); margin-top:4px;">Connected Store: <strong>{{ $storeName }}</strong></p>
        </div>
        <div>
            @if($connectionStatus)
                <span class="connection-status active">
                    <span class="role-indicator-dot" style="background-color: #16a34a;"></span>
                    Connected
                </span>
            @else
                <span class="connection-status inactive">
                    <span class="role-indicator-dot" style="background-color: var(--error-color);"></span>
                    Disconnected
                </span>
            @endif
        </div>
    </div>

    <!-- Counters Cards -->
    <div class="stats-grid">
        <div class="stat-card diamonds">
            <div class="stat-icon"><i class="fa-solid fa-gem"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $totalDiamonds }}</span>
                <span class="stat-lbl">My Diamonds</span>
            </div>
        </div>

        <div class="stat-card jewelry">
            <div class="stat-icon"><i class="fa-solid fa-ring"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $totalJewelry }}</span>
                <span class="stat-lbl">My Jewelry</span>
            </div>
        </div>

        <div class="stat-card synced">
            <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $syncedCount }}</span>
                <span class="stat-lbl">Synced</span>
            </div>
        </div>

        <div class="stat-card pending">
            <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $pendingCount }}</span>
                <span class="stat-lbl">Pending</span>
            </div>
        </div>

        <div class="stat-card failed">
            <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-info">
                <span class="stat-val">{{ $failedCount }}</span>
                <span class="stat-lbl">Failed</span>
            </div>
        </div>
    </div>

    <!-- Inventory & Integration Overview Grid -->
    <h3 style="font-size: 15px; font-weight: 700; color: var(--text-color); margin-top: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
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

    <!-- Orders Statistics Grid -->
    <div class="actions-panel">
        <h3 class="panel-title">My Orders & Sales Statistics</h3>
        <div class="stats-grid">
            <div class="stat-card" style="border-left: 4px solid var(--primary-color); cursor: pointer;" onclick="window.location='{{ route('admin.shopify.orders') }}'">
                <div class="stat-icon" style="background: var(--primary-light); color: var(--primary-color);"><i class="fa-solid fa-cart-shopping"></i></div>
                <div class="stat-info">
                    <span class="stat-val">{{ $ordersStats['total'] ?? 0 }}</span>
                    <span class="stat-lbl">Total Orders</span>
                </div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--warning-color); cursor: pointer;" onclick="window.location='{{ route('admin.shopify.orders', ['fulfillment_status' => 'unfulfilled']) }}'">
                <div class="stat-icon" style="background: #fffbeb; color: var(--warning-color);"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <span class="stat-val">{{ $ordersStats['pending'] ?? 0 }}</span>
                    <span class="stat-lbl">Pending Orders</span>
                </div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--success-color); cursor: pointer;" onclick="window.location='{{ route('admin.shopify.orders', ['fulfillment_status' => 'fulfilled']) }}'">
                <div class="stat-icon" style="background: #e6fffa; color: var(--success-color);"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-info">
                    <span class="stat-val">{{ $ordersStats['completed'] ?? 0 }}</span>
                    <span class="stat-lbl">Fulfillment Completed</span>
                </div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #8b5cf6; cursor: pointer;" onclick="window.location='{{ route('analytics.revenue') }}'">
                <div class="stat-icon" style="background: #f5f3ff; color: #8b5cf6;"><i class="fa-solid fa-sack-dollar"></i></div>
                <div class="stat-info">
                    <span class="stat-val">${{ number_format($revenueStats['today'] ?? 0, 2) }}</span>
                    <span class="stat-lbl">Revenue Today</span>
                </div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #ec4899; cursor: pointer;" onclick="window.location='{{ route('analytics.revenue') }}'">
                <div class="stat-icon" style="background: #fdf2f8; color: #ec4899;"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                <div class="stat-info">
                    <span class="stat-val">${{ number_format($revenueStats['this_month'] ?? 0, 2) }}</span>
                    <span class="stat-lbl">Revenue This Month</span>
                </div>
            </div>
        </div>
        <div class="stats-grid" style="margin-top: 20px;">
            <div class="stat-card" style="border-left: 4px solid #10b981; cursor: pointer;" onclick="window.location='{{ route('admin.shopify.orders', ['start_date' => \Carbon\Carbon::today()->toDateString()]) }}'">
                <div class="stat-icon" style="background: #ecfdf5; color: #10b981;"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="stat-info">
                    <span class="stat-val">{{ $ordersStats['today'] ?? 0 }}</span>
                    <span class="stat-lbl">Orders Today</span>
                </div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #3b82f6; cursor: pointer;" onclick="window.location='{{ route('admin.shopify.orders', ['start_date' => \Carbon\Carbon::now()->startOfMonth()->toDateString()]) }}'">
                <div class="stat-icon" style="background: #eff6ff; color: #3b82f6;"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="stat-info">
                    <span class="stat-val">{{ $ordersStats['this_month'] ?? 0 }}</span>
                    <span class="stat-lbl">Orders This Month</span>
                </div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #f59e0b; cursor: pointer;" onclick="window.location='{{ route('reports.index') }}'">
                <div class="stat-icon" style="background: #fffbeb; color: #f59e0b;"><i class="fa-solid fa-gem"></i></div>
                <div class="stat-info">
                    <span class="stat-val">{{ $soldDiamonds ?? 0 }}</span>
                    <span class="stat-lbl">Diamonds Sold</span>
                </div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #d946ef; cursor: pointer;" onclick="window.location='{{ route('reports.index') }}'">
                <div class="stat-icon" style="background: #fdf4ff; color: #d946ef;"><i class="fa-solid fa-ring"></i></div>
                <div class="stat-info">
                    <span class="stat-val">{{ $soldJewelry ?? 0 }}</span>
                    <span class="stat-lbl">Jewelry Sold</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Shopify Connection Form -->
    <div class="actions-panel">
        <h3 class="panel-title">Shopify Stores Configuration</h3>
        <p style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 20px;">
            Manage and switch between your connected Shopify stores. Each store is isolated and operates independently.
        </p>
        <a href="{{ route('shopify.stores') }}" class="btn btn-primary" style="background-color: var(--primary-color); border-color: var(--primary-color);">
            <i class="fa-solid fa-store" style="margin-right: 6px;"></i>
            Manage Shopify Stores
        </a>
    </div>

    <!-- Action Center -->
    <div class="actions-panel">
        <h3 class="panel-title">My Synchronization Actions</h3>
        <div class="actions-grid">
            <form action="{{ route('shopify.sync-all') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="diamonds">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    Sync My Diamonds
                </button>
            </form>

            <form action="{{ route('shopify.sync-all') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="jewelry">
                <button type="submit" class="btn btn-primary" style="background-color: #db2777; border-color: #db2777;">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    Sync My Jewelry
                </button>
            </form>

            <form action="{{ route('shopify.sync-all') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="all">
                <button type="submit" class="btn btn-secondary">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    Sync All My Products
                </button>
            </form>

            @if($failedCount > 0)
                <form action="{{ route('shopify.retry-failed') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Retry My Failed Syncs
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Sync Logs/Tables -->
    <div class="tables-grid">
        <!-- Recent Diamond Syncs -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="panel-title" style="margin:0;">Recent Diamond Syncs</h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="shopify-table">
                    <thead>
                        <tr>
                            <th>Diamond</th>
                            <th>Shopify ID</th>
                            <th>Status</th>
                            <th>Synced At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDiamonds as $sync)
                            @php $product = $sync->product; @endphp
                            <tr>
                                <td>
                                    @if($product)
                                        <div style="font-weight: 700; color: var(--text-color);">
                                            {{ $product->shape }} {{ floatval($product->size) }}ct
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-muted);">
                                            #{{ $product->stock_no ?? $product->id }} | {{ $product->color }} | {{ $product->clarity }}
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">Deleted Diamond (#{{ $sync->product_id }})</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sync->shopify_product_id)
                                        @if($sync->shopify_product_url)
                                            <a href="{{ $sync->shopify_product_url }}" target="_blank" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                                {{ $sync->shopify_product_id }}
                                                <i class="fa-solid fa-up-right-from-square" style="font-size: 10px; margin-left: 2px;"></i>
                                            </a>
                                        @else
                                            {{ $sync->shopify_product_id }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($sync->sync_status === 'failed')
                                        <div class="error-tooltip-wrapper">
                                            <span class="status-badge failed">
                                                <i class="fa-solid fa-circle-xmark"></i> Failed
                                            </span>
                                            <div class="error-tooltip-content">
                                                <strong>Error:</strong> {{ $sync->sync_message ?? 'Unknown API Error' }}
                                                <br><span style="font-size: 9px; opacity: 0.8;">Attempts: {{ $sync->sync_attempts }}</span>
                                            </div>
                                        </div>
                                    @elseif($sync->sync_status === 'synced')
                                        <span class="status-badge synced">
                                            <i class="fa-solid fa-circle-check"></i> Synced
                                        </span>
                                    @elseif($sync->sync_status === 'processing')
                                        <span class="status-badge pending">
                                            <i class="fa-solid fa-spinner fa-spin"></i> Syncing
                                        </span>
                                    @else
                                        <span class="status-badge pending">
                                            <i class="fa-solid fa-clock"></i> Pending
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    {{ $sync->synced_at ? $sync->synced_at->diffForHumans() : '-' }}
                                </td>
                                <td>
                                    @if($sync->sync_status === 'failed')
                                        <form action="{{ route('shopify.retry', $sync->id) }}" method="POST" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn-icon-only" title="Retry Sync">
                                                <i class="fa-solid fa-arrow-rotate-right"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); font-style: italic; padding: 30px;">
                                    No diamond sync records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Jewelry Syncs -->
        <div class="table-container">
            <div class="table-header">
                <h3 class="panel-title" style="margin:0;">Recent Jewelry Syncs</h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="shopify-table">
                    <thead>
                        <tr>
                            <th>Jewelry</th>
                            <th>Shopify ID</th>
                            <th>Status</th>
                            <th>Synced At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentJewelry as $sync)
                            @php $product = $sync->product; @endphp
                            <tr>
                                <td>
                                    @if($product)
                                        <div style="font-weight: 700; color: var(--text-color);">
                                            {{ $product->name }}
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-muted);">
                                            #{{ $product->sku ?? $product->id }} | {{ $product->type }}
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted); font-style: italic;">Deleted Jewelry (#{{ $sync->product_id }})</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sync->shopify_product_id)
                                        @if($sync->shopify_product_url)
                                            <a href="{{ $sync->shopify_product_url }}" target="_blank" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                                {{ $sync->shopify_product_id }}
                                                <i class="fa-solid fa-up-right-from-square" style="font-size: 10px; margin-left: 2px;"></i>
                                            </a>
                                        @else
                                            {{ $sync->shopify_product_id }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($sync->sync_status === 'failed')
                                        <div class="error-tooltip-wrapper">
                                            <span class="status-badge failed">
                                                <i class="fa-solid fa-circle-xmark"></i> Failed
                                            </span>
                                            <div class="error-tooltip-content">
                                                <strong>Error:</strong> {{ $sync->sync_message ?? 'Unknown API Error' }}
                                                <br><span style="font-size: 9px; opacity: 0.8;">Attempts: {{ $sync->sync_attempts }}</span>
                                            </div>
                                        </div>
                                    @elseif($sync->sync_status === 'synced')
                                        <span class="status-badge synced">
                                            <i class="fa-solid fa-circle-check"></i> Synced
                                        </span>
                                    @elseif($sync->sync_status === 'processing')
                                        <span class="status-badge pending">
                                            <i class="fa-solid fa-spinner fa-spin"></i> Syncing
                                        </span>
                                    @else
                                        <span class="status-badge pending">
                                            <i class="fa-solid fa-clock"></i> Pending
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    {{ $sync->synced_at ? $sync->synced_at->diffForHumans() : '-' }}
                                </td>
                                <td>
                                    @if($sync->sync_status === 'failed')
                                        <form action="{{ route('shopify.retry', $sync->id) }}" method="POST" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn-icon-only" title="Retry Sync">
                                                <i class="fa-solid fa-arrow-rotate-right"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); font-style: italic; padding: 30px;">
                                    No jewelry sync records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
