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

    .inventory-table-container {
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        margin-bottom: 24px;
    }

    .inventory-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .inventory-table th {
        background-color: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    .inventory-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
        color: var(--text-color);
    }

    .inventory-table tr:hover {
        background-color: #f8fafc;
    }

    .product-img {
        width: 44px;
        height: 44px;
        border-radius: 6px;
        object-fit: cover;
        background-color: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        border: 1px solid var(--border-color);
    }

    .badge-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
    }

    .badge-status.available {
        background-color: #dcfce7;
        color: #166534;
    }

    .badge-status.on_hold {
        background-color: #fef3c7;
        color: #92400e;
    }

    .badge-status.sold {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-status.reserved {
        background-color: #e0f2fe;
        color: #0369a1;
    }

    .badge-sync {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .badge-sync.synced {
        background-color: #dcfce7;
        color: #166534;
    }

    .badge-sync.failed {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-sync.unmapped {
        background-color: #f1f5f9;
        color: #475569;
    }

    .action-buttons {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .action-btn {
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 700;
        border-radius: 4px;
        border: 1px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none;
        color: inherit;
    }

    .action-btn-hold {
        background-color: #fef3c7;
        border-color: #fde68a;
        color: #92400e;
    }

    .action-btn-hold:hover {
        background-color: #fde68a;
    }

    .action-btn-release {
        background-color: #dcfce7;
        border-color: #bbf7d0;
        color: #166534;
    }

    .action-btn-release:hover {
        background-color: #bbf7d0;
    }

    .action-btn-sync {
        background-color: #e0f2fe;
        border-color: #bae6fd;
        color: #0369a1;
    }

    .action-btn-sync:hover {
        background-color: #bae6fd;
    }

    .bulk-card {
        background-color: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .bulk-actions-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    /* Modal Styling */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    }

    .modal-box {
        background: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        width: 450px;
        max-width: 90%;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }

    .modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-color);
        font-weight: 700;
        font-size: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-body {
        padding: 20px;
    }

    .modal-footer {
        padding: 16px 20px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background-color: #f8fafc;
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
    $isAdmin = (session('admin_role', auth()->user()->role) === 'super_admin');
    $productType = request('product_type', 'diamond');
@endphp

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 700;">Unified Inventory Management</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Monitor and coordinate inventory holds, assignments, and Shopify synchronizations.</p>
    </div>
</div>

@if($productType === 'jewelry')
    <div class="jewelery-tabs">
        <a href="{{ route('jewelery.index') }}" class="jewelery-tab-link" style="text-decoration: none;">Search</a>
        <a href="{{ route('inventory.index', ['product_type' => 'jewelry']) }}" class="jewelery-tab-link active" style="text-decoration: none;">Inventory</a>
        @if($isAdmin)
            <a href="{{ route('inventory-history.index', ['product_type' => 'jewelry']) }}" class="jewelery-tab-link" style="text-decoration: none;">Inventory History</a>
        @endif
    </div>
@else
    <div class="search-tabs-container">
        <a href="{{ route('diamonds.index') }}" class="search-tab-nav" style="text-decoration: none;">
            Search Single Diamonds
        </a>
        <a href="{{ route('inventory.index', ['product_type' => 'diamond']) }}" class="search-tab-nav active" style="text-decoration: none;">
            Inventory
        </a>
        @if($isAdmin)
            <a href="{{ route('inventory-history.index', ['product_type' => 'diamond']) }}" class="search-tab-nav" style="text-decoration: none;">
                Inventory History
            </a>
        @endif
    </div>
@endif

<!-- Search & Filters -->
<div class="filter-card">
    <form action="{{ route('inventory.index') }}" method="GET">
        <div class="filter-grid">
            <div class="form-group">
                <label for="stock_no">Stock No / SKU</label>
                <input type="text" name="stock_no" id="stock_no" class="form-control" value="{{ request('stock_no') }}" placeholder="Search Stock No...">
            </div>

            <div class="form-group">
                <label for="product_type">Product Type</label>
                <select name="product_type" id="product_type" class="form-control">
                    <option value="">All Types</option>
                    <option value="diamond" {{ request('product_type') === 'diamond' ? 'selected' : '' }}>Diamond</option>
                    <option value="jewelry" {{ request('product_type') === 'jewelry' ? 'selected' : '' }}>Jewelry</option>
                </select>
            </div>

            <div class="form-group">
                <label for="inventory_status">Inventory Status</label>
                <select name="inventory_status" id="inventory_status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="available" {{ request('inventory_status') === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="on_hold" {{ request('inventory_status') === 'on_hold' ? 'selected' : '' }}>On Hold</option>
                    <option value="sold" {{ request('inventory_status') === 'sold' ? 'selected' : '' }}>Sold</option>
                    <option value="reserved" {{ request('inventory_status') === 'reserved' ? 'selected' : '' }}>Reserved</option>
                </select>
            </div>

            <div class="form-group">
                <label for="shopify_sync_status">Shopify Sync Status</label>
                <select name="shopify_sync_status" id="shopify_sync_status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="synced" {{ request('shopify_sync_status') === 'synced' ? 'selected' : '' }}>Synced</option>
                    <option value="failed" {{ request('shopify_sync_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="unmapped" {{ request('shopify_sync_status') === 'unmapped' ? 'selected' : '' }}>Unmapped</option>
                </select>
            </div>

            @if(session('admin_role', auth()->user()->role) === 'super_admin')
                <div class="form-group">
                    <label for="assigned_admin_id">Assigned Admin</label>
                    <select name="assigned_admin_id" id="assigned_admin_id" class="form-control">
                        <option value="">All Admins</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" {{ request('assigned_admin_id') == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group" style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="height: 38px; flex: 1;">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
                <a href="{{ route('inventory.index') }}" class="btn btn-secondary" style="height: 38px; display: flex; align-items: center; justify-content: center;">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Bulk Actions Section (Super Admin only for assignments, others for holds) -->
<div class="bulk-card">
    <div style="font-weight: 700; font-size: 14px; color: var(--text-color);">
        <i class="fa-solid fa-list-check" style="margin-right: 6px; color: var(--primary-color);"></i>
        Selected <span id="selectedCount">0</span> items
    </div>
    <div class="bulk-actions-wrapper">
        <button type="button" class="btn btn-secondary" onclick="triggerBulkAction('sync')">
            <i class="fa-solid fa-rotate"></i> Bulk Shopify Sync
        </button>
        <button type="button" class="btn btn-secondary action-btn-release" onclick="triggerBulkAction('release')">
            <i class="fa-solid fa-unlock"></i> Bulk Release Hold
        </button>
        <button type="button" class="btn btn-secondary action-btn-hold" onclick="openBulkHoldModal()">
            <i class="fa-solid fa-lock"></i> Bulk Hold
        </button>

        @if(session('admin_role', auth()->user()->role) === 'super_admin')
            <div style="height: 24px; width: 1px; background-color: var(--border-color); margin: 0 8px;"></div>
            <form id="bulkAssignForm" action="{{ route('inventory.bulk-assign') }}" method="POST" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                @csrf
                <input type="hidden" name="product_type" id="bulkAssignType" value="diamond">
                <input type="hidden" name="product_ids[]" class="bulk-ids-placeholder">
                <select name="assigned_admin_id" class="form-control" style="width: 160px; height: 34px; padding: 4px 8px; font-size: 12.5px;" required>
                    <option value="">Assign Admin...</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-primary" style="height: 34px; font-size: 12.5px; padding: 0 12px;" onclick="submitBulkAssign()">
                    Assign
                </button>
            </form>
        @endif
    </div>
</div>

<!-- Inventory Table -->
<div class="inventory-table-container">
    <table class="inventory-table">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">
                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                </th>
                <th>Stock No / SKU</th>
                <th>Image</th>
                <th>Type</th>
                <th>Shape</th>
                <th>Carat</th>
                <th>Color</th>
                <th>Clarity</th>
                <th>Price</th>
                <th>Inventory Status</th>
                <th>Hold Status</th>
                <th>Assigned Admin</th>
                <th>Shopify Sync</th>
                <th>Mappings</th>
                <th>Created Date</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php
                    $isDiamond = $item->product_type === 'diamond';
                    $viewRoute = $isDiamond ? route('diamonds.show', $item->id) : route('jewelery.show', $item->id);
                    $editRoute = $isDiamond ? route('diamonds.edit', $item->id) : route('jewelery.edit', $item->id);
                    
                    // Shopify mapping status logic
                    $mappingCount = $item->store_mapping_count;
                    $failedCount = $item->failed_mapping_count;
                    
                    if ($mappingCount == 0) {
                        $syncLabel = 'Unmapped';
                        $syncClass = 'unmapped';
                    } elseif ($failedCount > 0) {
                        $syncLabel = 'Failed';
                        $syncClass = 'failed';
                    } else {
                        $syncLabel = 'Synced';
                        $syncClass = 'synced';
                    }

                    $canEdit = true;
                    if ($isDiamond) {
                        $activeRole = session('admin_role', auth()->user()->role);
                        if ($activeRole === 'super_admin') {
                            if ($item->uploader_role === 'normal_admin') {
                                $canEdit = false;
                            }
                        } else {
                            if ((int)$item->user_id !== (int)auth()->id()) {
                                $canEdit = false;
                            }
                        }
                    }
                @endphp
                <tr>
                    <td style="text-align: center;">
                        <input type="checkbox" class="item-checkbox" data-type="{{ $item->product_type }}" data-id="{{ $item->id }}" onchange="updateSelectedCount()">
                    </td>
                    <td style="font-weight: 700;">{{ $item->stock_no }}</td>
                    <td>
                        @if($isDiamond)
                            <div class="product-img"><i class="fa-solid fa-gem"></i></div>
                        @else
                            <div class="product-img"><i class="fa-solid fa-ring"></i></div>
                        @endif
                    </td>
                    <td style="text-transform: capitalize;">{{ $item->product_type }}</td>
                    <td>{{ $item->shape ?: '-' }}</td>
                    <td>{{ $item->carat ? number_format($item->carat, 2) : '-' }}</td>
                    <td>{{ $item->color ?: '-' }}</td>
                    <td>{{ $item->clarity ?: '-' }}</td>
                    <td style="font-weight: 700;">${{ number_format($item->price, 2) }}</td>
                    <td>
                        <span class="badge-status {{ $item->inventory_status }}">
                            {{ str_replace('_', ' ', ucfirst($item->inventory_status)) }}
                        </span>
                    </td>
                    <td>
                        @if($item->inventory_status === 'on_hold')
                            <div style="font-size: 11px;">
                                <strong style="color: var(--warning-color);">Hold</strong>
                                @if($item->hold_at)
                                    <div style="color: var(--text-muted);">{{ \Carbon\Carbon::parse($item->hold_at)->format('M d, Y') }}</div>
                                @endif
                            </div>
                        @else
                            <span style="color: var(--text-muted);">None</span>
                        @endif
                    </td>
                    <td style="font-weight: 500;">
                        {{ $item->assigned_admin_name ?: 'Unassigned' }}
                    </td>
                    <td>
                        <span class="badge-sync {{ $syncClass }}">
                            @if($syncClass === 'synced')
                                <i class="fa-solid fa-check"></i>
                            @elseif($syncClass === 'failed')
                                <i class="fa-solid fa-exclamation-triangle"></i>
                            @else
                                <i class="fa-solid fa-link-slash"></i>
                            @endif
                            {{ $syncLabel }}
                        </span>
                    </td>
                    <td style="font-weight: 600; text-align: center;">
                        {{ $mappingCount }} stores
                    </td>
                    <td>{{ \Carbon\Carbon::parse($item->created_at)->format('Y-m-d') }}</td>
                    <td>
                        <div class="action-buttons" style="justify-content: flex-end;">
                            <a href="{{ $viewRoute }}" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11.5px; height: 26px;" title="View Details">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            @if($canEdit)
                                <a href="{{ $editRoute }}" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11.5px; height: 26px;" title="Edit Product">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            @endif

                            @can('hold', [\App\Models\InventoryRequest::class, $item])
                                @if($item->inventory_status === 'available')
                                    <button type="button" class="action-btn action-btn-hold" onclick="openHoldModal('{{ $item->product_type }}', {{ $item->id }})" title="Hold Item">
                                        <i class="fa-solid fa-lock"></i> Hold
                                    </button>
                                @endif
                            @endcan

                            @can('release', [\App\Models\InventoryRequest::class, $item])
                                @if($item->inventory_status === 'on_hold')
                                    <form action="{{ route('inventory.release', [$item->product_type, $item->id]) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to release this hold?')">
                                        @csrf
                                        <button type="submit" class="action-btn action-btn-release" title="Release Hold">
                                            <i class="fa-solid fa-unlock"></i> Release
                                        </button>
                                    </form>
                                @endif
                            @endcan

                            @can('sync', [\App\Models\InventoryRequest::class, $item])
                                @if($mappingCount > 0)
                                    <form action="{{ route('inventory.sync', [$item->product_type, $item->id]) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="action-btn action-btn-sync" title="Shopify Sync">
                                            <i class="fa-solid fa-rotate"></i> Sync
                                        </button>
                                    </form>
                                @else
                                    <!-- Missing Shopify Mapping Safeguard Badge & Publishing Trigger -->
                                    @if($isDiamond)
                                        <form action="{{ route('shopify.publish-diamond', $item->id) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="action-btn" style="background-color: #fee2e2; border-color: #fca5a5; color: #991b1b;" title="Missing store mapping! Click to sync.">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Link Shopify
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('shopify.publish-jewelry', $item->id) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="action-btn" style="background-color: #fee2e2; border-color: #fca5a5; color: #991b1b;" title="Missing store mapping! Click to sync.">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Link Shopify
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" style="text-align: center; color: var(--text-muted); padding: 40px;">No inventory items found matching your filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="pagination-container">
    {{ $items->links() }}
</div>

<!-- Hold Modal -->
<div class="modal-overlay" id="holdModal">
    <div class="modal-box">
        <div class="modal-header">
            <span>Apply Hold</span>
            <i class="fa-solid fa-xmark" style="cursor: pointer;" onclick="closeHoldModal()"></i>
        </div>
        <form id="holdForm" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="hold_reason_input">Reason for Hold <span style="color: var(--error-color);">*</span></label>
                    <textarea name="reason" id="hold_reason_input" class="form-control" rows="3" required placeholder="Enter reason for holding this item..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeHoldModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Apply Hold</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Hold Modal -->
<div class="modal-overlay" id="bulkHoldModal">
    <div class="modal-box">
        <div class="modal-header">
            <span>Apply Bulk Hold</span>
            <i class="fa-solid fa-xmark" style="cursor: pointer;" onclick="closeBulkHoldModal()"></i>
        </div>
        <form id="bulkHoldForm" method="POST" action="">
            @csrf
            <input type="hidden" name="product_type" id="bulkHoldType" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label for="bulk_hold_reason_input">Reason for Hold <span style="color: var(--error-color);">*</span></label>
                    <textarea name="reason" id="bulk_hold_reason_input" class="form-control" rows="3" required placeholder="Enter reason for holding selected items..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeBulkHoldModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Apply Holds</button>
            </div>
        </form>
    </div>
</div>

</div>

<!-- Bulk Progress Modal Overlay -->
<div class="modal-overlay" id="bulkProgressModal" style="display: none;">
    <div class="modal-box" style="width: 500px;">
        <div class="modal-header">
            <span id="bulkProgressTitle">Processing Bulk Action</span>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <div style="margin-bottom: 15px; font-weight: bold; color: var(--text-color);" id="bulkProgressText">
                Queueing job...
            </div>
            
            <!-- Progress Bar container -->
            <div style="height: 16px; width: 100%; background: #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 20px; border: 1px solid var(--border-color);">
                <div id="bulkProgressBar" style="width: 0%; height: 100%; background: var(--primary-color); border-radius: 8px; transition: width 0.3s ease;"></div>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); font-weight: 600; margin-bottom: 15px;">
                <span id="bulkProgressPercent">0% Completed</span>
                <span id="bulkProgressCounts">0 / 0 Items</span>
            </div>

            <!-- Error logs collapsible -->
            <div id="bulkProgressErrorsContainer" style="display: none; border-top: 1px solid var(--border-color); padding-top: 15px;">
                <h5 style="font-size: 12px; font-weight: 700; color: var(--error-color); margin-bottom: 8px; text-transform: uppercase;">
                    Validation / Sync Failures (<span id="bulkProgressErrorCount">0</span>)
                </h5>
                <div id="bulkProgressErrorsList" style="max-height: 150px; overflow-y: auto; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px; padding: 10px; font-size: 11px; font-family: monospace; color: #c53030; line-height: 1.4; text-align: left;">
                </div>
            </div>
        </div>
        <div class="modal-footer" id="bulkProgressFooter" style="display: none;">
            <button type="button" class="btn btn-primary" onclick="closeProgressModalAndReload()">Done</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let progressInterval = null;

    function showProgressModal(jobId, actionName) {
        document.getElementById('bulkProgressTitle').textContent = `Bulk ${actionName} Progress`;
        document.getElementById('bulkProgressText').textContent = 'Queued in background. Waiting for worker...';
        document.getElementById('bulkProgressBar').style.width = '0%';
        document.getElementById('bulkProgressPercent').textContent = '0% Completed';
        document.getElementById('bulkProgressCounts').textContent = '0 / 0 Items';
        document.getElementById('bulkProgressErrorsContainer').style.display = 'none';
        document.getElementById('bulkProgressErrorsList').innerHTML = '';
        document.getElementById('bulkProgressFooter').style.display = 'none';
        document.getElementById('bulkProgressModal').style.display = 'flex';

        pollJobStatus(jobId);
    }

    function pollJobStatus(jobId) {
        if (progressInterval) clearInterval(progressInterval);

        progressInterval = setInterval(() => {
            fetch(`/bulk-operations/status/${jobId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const job = data.job;
                    const progress = job.progress;
                    
                    // Update text based on status
                    if (job.status === 'pending') {
                        document.getElementById('bulkProgressText').textContent = 'Queued in background. Waiting for worker...';
                    } else if (job.status === 'processing') {
                        document.getElementById('bulkProgressText').textContent = 'Processing items...';
                    } else if (job.status === 'success') {
                        document.getElementById('bulkProgressText').innerHTML = `<span style="color: var(--success-color);"><i class="fa-solid fa-circle-check"></i> Bulk action completed successfully.</span>`;
                        clearInterval(progressInterval);
                        document.getElementById('bulkProgressFooter').style.display = 'flex';
                    } else if (job.status === 'failed') {
                        document.getElementById('bulkProgressText').innerHTML = `<span style="color: var(--error-color);"><i class="fa-solid fa-circle-xmark"></i> Bulk action failed.</span>`;
                        clearInterval(progressInterval);
                        document.getElementById('bulkProgressFooter').style.display = 'flex';
                    }

                    // Update Progress bar & percentages
                    const percent = progress.percent || 0;
                    document.getElementById('bulkProgressBar').style.width = `${percent}%`;
                    document.getElementById('bulkProgressPercent').textContent = `${percent}% Completed`;
                    document.getElementById('bulkProgressCounts').textContent = `${progress.processed || 0} / ${progress.total || 0} Items`;

                    // Update Errors if any
                    if (progress.errors && progress.errors.length > 0) {
                        document.getElementById('bulkProgressErrorsContainer').style.display = 'block';
                        document.getElementById('bulkProgressErrorCount').textContent = progress.errors.length;
                        document.getElementById('bulkProgressErrorsList').innerHTML = progress.errors.map(err => `<div>• ${escapeHtml(err)}</div>`).join('');
                    }
                }
            })
            .catch(err => {
                console.error('Error polling bulk job status:', err);
            });
        }, 1000);
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function closeProgressModalAndReload() {
        document.getElementById('bulkProgressModal').style.display = 'none';
        window.location.reload();
    }

    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = master.checked;
        });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const selected = document.querySelectorAll('.item-checkbox:checked');
        document.getElementById('selectedCount').textContent = selected.length;
    }

    function getSelectedItems() {
        const selected = document.querySelectorAll('.item-checkbox:checked');
        const items = [];
        selected.forEach(cb => {
            items.push({
                type: cb.dataset.type,
                id: cb.dataset.id
            });
        });
        return items;
    }

    function openHoldModal(type, id) {
        const form = document.getElementById('holdForm');
        form.action = `/inventory/hold/${type}/${id}`;
        document.getElementById('holdModal').style.display = 'flex';
    }

    function closeHoldModal() {
        document.getElementById('holdModal').style.display = 'none';
    }

    function openBulkHoldModal() {
        const selected = getSelectedItems();
        if (selected.length === 0) {
            alert('Please select at least one item.');
            return;
        }

        // Validate homogeneous types for API bulk operation simplicity
        const firstType = selected[0].type;
        const mismatch = selected.some(item => item.type !== firstType);
        if (mismatch) {
            alert('Bulk operations must be applied to items of the same type (either all Diamonds or all Jewelry).');
            return;
        }

        document.getElementById('bulkHoldType').value = firstType;
        document.getElementById('bulkHoldModal').style.display = 'flex';
    }

    function closeBulkHoldModal() {
        document.getElementById('bulkHoldModal').style.display = 'none';
    }

    document.getElementById('bulkHoldForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const reason = document.getElementById('bulk_hold_reason_input').value;
        const selected = getSelectedItems();
        const type = document.getElementById('bulkHoldType').value;

        if (!reason || selected.length === 0) return;

        closeBulkHoldModal();

        const productIds = selected.map(item => item.id);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch("{{ route('inventory.bulk-hold') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                product_type: type,
                product_ids: productIds,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showProgressModal(data.job_id, 'Hold');
            } else {
                alert(data.message || 'Error queueing bulk hold.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error queueing bulk hold.');
        });
    });

    function triggerBulkAction(action) {
        const selected = getSelectedItems();
        if (selected.length === 0) {
            alert('Please select at least one item.');
            return;
        }

        const firstType = selected[0].type;
        const mismatch = selected.some(item => item.type !== firstType);
        if (mismatch) {
            alert('Bulk operations must be applied to items of the same type (either all Diamonds or all Jewelry).');
            return;
        }

        let confirmMsg = `Are you sure you want to perform bulk ${action} on ${selected.length} items?`;
        if (!confirm(confirmMsg)) return;

        const productIds = selected.map(item => item.id);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = action === 'release' ? "{{ route('inventory.bulk-release') }}" : "{{ route('inventory.bulk-sync') }}";

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                product_type: firstType,
                product_ids: productIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showProgressModal(data.job_id, action.charAt(0).toUpperCase() + action.slice(1));
            } else {
                alert(data.message || `Error queueing bulk ${action}.`);
            }
        })
        .catch(err => {
            console.error(err);
            alert(`Error queueing bulk ${action}.`);
        });
    }

    function submitBulkAssign() {
        const selected = getSelectedItems();
        if (selected.length === 0) {
            alert('Please select at least one item.');
            return;
        }

        const firstType = selected[0].type;
        const mismatch = selected.some(item => item.type !== firstType);
        if (mismatch) {
            alert('Bulk operations must be applied to items of the same type (either all Diamonds or all Jewelry).');
            return;
        }

        const assignedAdminSelect = document.querySelector('select[name="assigned_admin_id"]');
        const assignedAdminId = assignedAdminSelect.value;
        if (!assignedAdminId) {
            alert('Please select an admin user.');
            return;
        }

        const productIds = selected.map(item => item.id);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch("{{ route('inventory.bulk-assign') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                product_type: firstType,
                product_ids: productIds,
                assigned_admin_id: assignedAdminId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showProgressModal(data.job_id, 'Assign');
            } else {
                alert(data.message || 'Error queueing bulk assignment.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error queueing bulk assignment.');
        });
    }
</script>
@endsection
