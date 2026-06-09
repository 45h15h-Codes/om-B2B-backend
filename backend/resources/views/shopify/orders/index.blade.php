@extends('layouts.app')

@section('styles')
<style>
    /* Styling for Shopify Orders page */
    .shopify-orders-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .shopify-orders-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        padding: 20px 24px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
    }

    .shopify-orders-title h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .sync-status-indicator {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: #e6fffa;
        color: #0d9488;
        border: 1px solid #b2f5ea;
    }

    .status-pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #0d9488;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(13, 148, 136, 0.7);
        animation: pulse-green 2s infinite;
    }

    @keyframes pulse-green {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(13, 148, 136, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 6px rgba(13, 148, 136, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(13, 148, 136, 0);
        }
    }

    /* Filters Section */
    .orders-filter-panel {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .orders-filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
    }

    .search-input-wrapper {
        position: relative;
        flex: 1;
        min-width: 280px;
    }

    .search-input-wrapper i.search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 14px;
    }

    .search-input-wrapper .search-field {
        width: 100%;
        padding: 10px 16px 10px 40px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .search-input-wrapper .search-field:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(16, 139, 182, 0.1);
    }

    .select-dropdown {
        padding: 10px 16px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
        outline: none;
        background: #ffffff;
        min-width: 180px;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    .select-dropdown:focus {
        border-color: var(--primary-color);
    }

    .btn-action {
        height: 42px;
        padding: 0 20px;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* Table & List styling */
    .orders-list-wrapper {
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
        padding: 14px 18px;
        border-bottom: 2px solid var(--border-color);
    }

    .list-table td {
        font-size: 14px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
        vertical-align: middle;
    }

    .list-table tr:last-child td {
        border-bottom: none;
    }

    .list-table tbody tr {
        transition: background-color 0.15s ease;
    }

    .list-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Custom Status Badges */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-success {
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .badge-warning {
        background-color: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .badge-danger {
        background-color: #fdf2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .badge-neutral {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    /* Modal Layout */
    .payload-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        z-index: 15000;
        align-items: center;
        justify-content: center;
        padding: 20px;
        backdrop-filter: blur(4px);
    }

    .payload-modal.show {
        display: flex;
        animation: modalFadeIn 0.2s ease-out;
    }

    .payload-modal-content {
        background: #ffffff;
        border-radius: 16px;
        width: 800px;
        max-width: 100%;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .payload-modal-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    }

    .payload-modal-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .payload-modal-close {
        background: none;
        border: none;
        font-size: 20px;
        color: var(--text-muted);
        cursor: pointer;
        transition: color 0.15s;
    }

    .payload-modal-close:hover {
        color: var(--error-color);
    }

    .payload-modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
        background-color: #0f172a;
    }

    .json-code-block {
        font-family: 'Courier New', Courier, monospace;
        font-size: 13.5px;
        color: #38bdf8;
        line-height: 1.5;
        white-space: pre-wrap;
        margin: 0;
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    @media (max-width: 768px) {
        .details-grid-layout {
            grid-template-columns: 1fr !important;
        }
    }

    /* Style pagination container and elements to prevent massive SVGs */
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
</style>
@endsection

@section('content')
<div class="shopify-orders-container">
    <!-- Header Area -->
    <div class="shopify-orders-header">
        <div class="shopify-orders-title">
            <h2>
                <i class="fa-brands fa-shopify" style="color: #96bf48; font-size: 26px;"></i>
                {{ isset($isLocalOrders) && $isLocalOrders ? 'Local Orders & Drafts' : 'Shopify Webhook Orders Sync' }}
            </h2>
            <p style="font-size:13.5px; color: var(--text-muted); margin-top:4px;">
                {{ isset($isLocalOrders) && $isLocalOrders ? 'Create and manage draft orders locally before syncing to Shopify.' : 'Securely synced from Shopify in real-time. Only accessible by Super Admin.' }}
            </p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            @if(isset($isLocalOrders) && $isLocalOrders)
                <a href="{{ route('orders.create') }}" class="btn btn-primary" style="height: 42px; padding: 0 20px;">
                    <i class="fa-solid fa-plus"></i> Create Local Order
                </a>
            @else
                <span class="sync-status-indicator" id="syncStatusBadge">
                    <span class="status-pulse-dot"></span>
                    <span>Live Sync Connected</span>
                </span>
            @endif
        </div>
    </div>

    <!-- Filters Section -->
    <div class="orders-filter-panel">
        <form action="{{ isset($isLocalOrders) && $isLocalOrders ? route('orders.index') : route('admin.shopify.orders') }}" method="GET" class="orders-filter-form" id="filterForm">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="q" id="searchField" class="search-field" 
                       placeholder="Search by Order #, email, or customer name..." 
                       value="{{ request('q') }}">
            </div>

            <select name="store_id" id="storeFilter" class="select-dropdown" onchange="this.form.submit()">
                <option value="">Filter Store (All)</option>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>{{ $st->store_name }}</option>
                @endforeach
            </select>

            @if(isset($isLocalOrders) && $isLocalOrders)
                <select name="status" id="statusFilter" class="select-dropdown" onchange="this.form.submit()">
                    <option value="">Status (All)</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="syncing" {{ request('status') === 'syncing' ? 'selected' : '' }}>Syncing</option>
                    <option value="synced" {{ request('status') === 'synced' ? 'selected' : '' }}>Synced</option>
                    <option value="invoice_sent" {{ request('status') === 'invoice_sent' ? 'selected' : '' }}>Invoice Sent</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            @else
                <select name="financial_status" id="financialStatusFilter" class="select-dropdown" onchange="this.form.submit()">
                    <option value="">Financial Status (All)</option>
                    <option value="paid" {{ request('financial_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ request('financial_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="authorized" {{ request('financial_status') === 'authorized' ? 'selected' : '' }}>Authorized</option>
                    <option value="partially_paid" {{ request('financial_status') === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                    <option value="refunded" {{ request('financial_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                    <option value="voided" {{ request('financial_status') === 'voided' ? 'selected' : '' }}>Voided</option>
                </select>
            @endif

            <button type="submit" class="btn btn-primary btn-action">
                <i class="fa-solid fa-filter"></i> Filter
            </button>

            @if(request()->filled('q') || request()->filled('financial_status') || request()->filled('status') || request()->filled('store_id'))
                <a href="{{ isset($isLocalOrders) && $isLocalOrders ? route('orders.index') : route('admin.shopify.orders') }}" class="btn btn-secondary btn-action" style="display:inline-flex; align-items:center;">
                    Clear Filters
                </a>
            @endif
        </form>

        @if(session('admin_role') === 'super_admin')
        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 5px 0;">

        <!-- Manual Recovery Sync Trigger -->
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-info" style="color: var(--primary-color);"></i>
                <span style="font-size: 13.5px; font-weight: 600; color: var(--text-color);">Manual Recovery Sync:</span>
                <span style="font-size: 12px; color: var(--text-muted);">Compares local database against Shopify API for missing items.</span>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="button" id="runRecoveryBtn" class="btn btn-primary" style="height: 38px; padding: 0 16px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-arrows-rotate"></i> Run Recovery (All Stores)
                </button>
            </div>
        </div>
        @endif
    </div>

    <!-- Table Container -->
    <div class="orders-list-wrapper" id="ordersTableContainer">
        @include('shopify.orders.partials.table', ['orders' => $orders])
    </div>
</div>

<!-- Raw Payload Modal -->
<div class="payload-modal" id="payloadModal" aria-hidden="true">
    <div class="payload-modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="payload-modal-header">
            <h3 class="payload-modal-title" id="modalTitle">
                <i class="fa-solid fa-code" style="color: var(--primary-color);"></i>
                Order Payload JSON: #<span id="modalOrderNumber">N/A</span>
            </h3>
            <button type="button" class="payload-modal-close" id="modalCloseBtn">&times;</button>
        </div>
        <div class="payload-modal-body">
            <pre><code class="json-code-block" id="modalCodeBlock"></code></pre>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="payload-modal" id="orderDetailsModal" aria-hidden="true">
    <div class="payload-modal-content" style="width: 950px; max-width: 95%;" role="dialog" aria-modal="true" aria-labelledby="detailsModalTitle">
        <div class="payload-modal-header" style="background: #ffffff; border-bottom: 1px solid var(--border-color);">
            <h3 class="payload-modal-title" id="detailsModalTitle" style="color: var(--text-color); font-weight: 700; font-size: 18px;">
                <i class="fa-solid fa-file-invoice" style="color: var(--primary-color);"></i>
                Order Details: #<span id="detailsOrderNumber">N/A</span>
            </h3>
            <button type="button" class="payload-modal-close" id="detailsModalCloseBtn">&times;</button>
        </div>
        <div class="payload-modal-body" style="background-color: #ffffff; color: var(--text-color); padding: 24px; overflow-y: auto;">
            <!-- Modal Grid -->
            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; margin-bottom: 24px;" class="details-grid-layout">
                
                <!-- Left Section: Store & Customer Details -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- Store Info -->
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; background: #f8fafc;">
                        <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-brands fa-shopify"></i> Shopify Store Info
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13.5px;">
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Store Name</span>
                                <span id="detStoreName" style="font-weight: 600;">N/A</span>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Domain</span>
                                <span id="detStoreDomain" style="font-weight: 600;">N/A</span>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Sync Time</span>
                                <span id="detSyncTime" style="font-weight: 600;">N/A</span>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Processed At</span>
                                <span id="detProcessedAt" style="font-weight: 600;">N/A</span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 18px;">
                        <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-user"></i> Customer Information
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13.5px; margin-bottom: 16px;">
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Customer Name</span>
                                <span id="detCustName" style="font-weight: 600; color: var(--text-color);">N/A</span>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Email ID</span>
                                <span id="detCustEmail" style="font-weight: 600; color: var(--text-color);">N/A</span>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Phone Number</span>
                                <span id="detCustPhone" style="font-weight: 600; color: var(--text-color);">N/A</span>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 11.5px; font-weight: 600; text-transform: uppercase;">Customer State</span>
                                <span id="detCustState" style="font-weight: 600; color: var(--text-color);">N/A</span>
                            </div>
                        </div>
                        
                        <!-- PII Warning Banner -->
                        <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px; display: flex; gap: 10px; align-items: flex-start;">
                            <i class="fa-solid fa-triangle-exclamation" style="color: #d97706; font-size: 16px; margin-top: 2px;"></i>
                            <div style="font-size: 12px; color: #b45309; line-height: 1.5;">
                                <strong style="font-weight: 700;">Shopify Protected Customer Data Policy:</strong>
                                Customer PII (name, email, phone, full address) may be hidden/redacted if the developer app is not configured with Protected Customer Data access in the Shopify Partner Dashboard.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Section: Address details & Financial info -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- Addresses -->
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 18px;">
                        <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-location-dot"></i> Regional & Address Details
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 13px;">
                            <div>
                                <strong style="display: block; margin-bottom: 6px; color: var(--text-muted); font-size: 11.5px; text-transform: uppercase;">Shipping Region</strong>
                                <div id="detShippingAddr" style="line-height: 1.4; font-weight: 600;">N/A</div>
                            </div>
                            <div>
                                <strong style="display: block; margin-bottom: 6px; color: var(--text-muted); font-size: 11.5px; text-transform: uppercase;">Billing Region</strong>
                                <div id="detBillingAddr" style="line-height: 1.4; font-weight: 600;">N/A</div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment & Totals -->
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; background: #fafbfc;">
                        <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 12px; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-credit-card"></i> Order Status & Totals
                        </h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
                            <div>
                                <span style="display: block; font-size: 10.5px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Payment</span>
                                <span id="detFinStatus" class="status-pill">N/A</span>
                            </div>
                            <div>
                                <span style="display: block; font-size: 10.5px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Fulfillment</span>
                                <span id="detFulStatus" class="status-pill">N/A</span>
                            </div>
                            <div>
                                <span style="display: block; font-size: 10.5px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Gateway</span>
                                <span id="detGateway" style="font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; height: 26px;">N/A</span>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div style="display: flex; flex-direction: column; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; font-size: 13.5px;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Subtotal</span>
                                <span id="detSubtotal" style="font-weight: 600;">$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Discount</span>
                                <span id="detDiscount" style="font-weight: 600; color: var(--error-color);">-$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Shipping</span>
                                <span id="detShipping" style="font-weight: 600;">$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Tax</span>
                                <span id="detTax" style="font-weight: 600;">$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 8px; font-size: 15px;">
                                <span style="font-weight: 700; color: var(--text-color);">Total Price</span>
                                <span id="detTotal" style="font-weight: 800; color: var(--primary-color); font-size: 16px;">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div style="border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden;">
                <div style="padding: 14px 18px; background: #f8fafc; border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 14px; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-list" style="color: var(--primary-color);"></i> Line Items (<span id="detLineItemCount">0</span>)
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;" id="detLineItemsTable">
                        <thead>
                            <tr style="background: #fdfdfd; border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 12px 18px; font-weight: 700; color: var(--text-muted); font-size: 11.5px; text-transform: uppercase;">Product Details</th>
                                <th style="padding: 12px 18px; font-weight: 700; color: var(--text-muted); font-size: 11.5px; text-transform: uppercase;">SKU</th>
                                <th style="padding: 12px 18px; font-weight: 700; color: var(--text-muted); font-size: 11.5px; text-transform: uppercase; text-align: right;">Price</th>
                                <th style="padding: 12px 18px; font-weight: 700; color: var(--text-muted); font-size: 11.5px; text-transform: uppercase; text-align: center;">Qty</th>
                                <th style="padding: 12px 18px; font-weight: 700; color: var(--text-muted); font-size: 11.5px; text-transform: uppercase; text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody id="detLineItemsBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('payloadModal');
        const modalOrderNum = document.getElementById('modalOrderNumber');
        const modalCodeBlock = document.getElementById('modalCodeBlock');
        const modalCloseBtn = document.getElementById('modalCloseBtn');

        const detailsModal = document.getElementById('orderDetailsModal');
        const detailsModalCloseBtn = document.getElementById('detailsModalCloseBtn');
        
        const searchField = document.getElementById('searchField');
        const storeFilter = document.getElementById('storeFilter');
        const financialStatusFilter = document.getElementById('financialStatusFilter');
        const tableContainer = document.getElementById('ordersTableContainer');

        const runRecoveryBtn = document.getElementById('runRecoveryBtn');

        let isModalOpen = false;

        // Helper function to format Address
        function formatAddress(addr) {
            if (!addr) return '<span style="color: var(--text-muted); font-style: italic;">No address provided</span>';
            
            const parts = [];
            if (addr.name || addr.first_name || addr.last_name) {
                const name = addr.name || ((addr.first_name || '') + ' ' + (addr.last_name || '')).trim();
                if (name) parts.push(`<span style="font-weight: 700; display: block; margin-bottom: 2px;">${name}</span>`);
            }
            
            const streetParts = [];
            if (addr.address1) streetParts.push(addr.address1);
            if (addr.address2) streetParts.push(addr.address2);
            if (streetParts.length > 0) {
                parts.push(streetParts.join(', '));
            } else {
                parts.push('<span style="color: #d97706; font-size: 11.5px; font-weight: 600;">[Street Address Redacted]</span>');
            }
            
            const cityZip = [];
            if (addr.city) cityZip.push(addr.city);
            if (addr.zip) cityZip.push(addr.zip);
            if (cityZip.length > 0) {
                parts.push(cityZip.join(' '));
            } else {
                parts.push('<span style="color: #d97706; font-size: 11.5px; font-weight: 600;">[City/Zip Redacted]</span>');
            }
            
            const region = [];
            if (addr.province || addr.province_code) {
                region.push(addr.province || addr.province_code);
            }
            if (addr.country || addr.country_code) {
                region.push(addr.country || addr.country_code);
            }
            if (region.length > 0) {
                parts.push(region.join(', '));
            }
            
            if (addr.phone) {
                parts.push(`Phone: ${addr.phone}`);
            }
            
            return parts.join('<br>');
        }

        // Helper function to format money
        function formatMoney(amount, currencyCode) {
            return `${currencyCode} ${parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        // Helper function to resolve Financial status badge
        function getFinancialBadgeClass(status) {
            status = (status || '').toLowerCase();
            if (status === 'paid') return 'badge-success';
            if (['pending', 'authorized'].includes(status)) return 'badge-warning';
            if (['refunded', 'voided'].includes(status)) return 'badge-danger';
            return 'badge-neutral';
        }

        // Helper function to resolve Fulfillment status badge
        function getFulfillmentBadgeClass(status) {
            status = (status || 'unfulfilled').toLowerCase();
            if (status === 'fulfilled') return 'badge-success';
            if (['partial', 'restocked'].includes(status)) return 'badge-warning';
            return 'badge-neutral';
        }

        // Open modal on click "View Raw Payload"
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.view-json-btn');
            if (btn) {
                const orderNum = btn.dataset.orderNumber || 'N/A';
                let jsonStr = btn.dataset.json || '{}';
                
                try {
                    const parsed = JSON.parse(jsonStr);
                    jsonStr = JSON.stringify(parsed, null, 4);
                } catch (err) {
                    // Fallback to raw
                }

                modalOrderNum.textContent = orderNum;
                modalCodeBlock.textContent = jsonStr;
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                isModalOpen = true;
            }
        });

        // Open detailed modal on click "View Details"
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.view-details-btn');
            if (btn) {
                const orderNum = btn.dataset.orderNumber || 'N/A';
                const storeName = btn.dataset.storeName || 'N/A';
                const storeDomain = btn.dataset.storeDomain || 'N/A';
                const syncTime = btn.dataset.createdAt || 'N/A';
                
                let jsonStr = btn.dataset.json || '{}';
                let parsed = {};
                try {
                    parsed = JSON.parse(jsonStr);
                } catch (err) {
                    console.error('Failed to parse order JSON', err);
                }

                // Populate store details
                document.getElementById('detailsOrderNumber').textContent = orderNum;
                document.getElementById('detStoreName').textContent = storeName;
                document.getElementById('detStoreDomain').textContent = storeDomain;
                document.getElementById('detSyncTime').textContent = syncTime;
                
                const processedAt = parsed.processed_at ? new Date(parsed.processed_at).toLocaleString() : 'N/A';
                document.getElementById('detProcessedAt').textContent = processedAt;

                // Customer Info
                const cust = parsed.customer || {};
                const firstName = cust.first_name || '';
                const lastName = cust.last_name || '';
                const fullName = (firstName + ' ' + lastName).trim() || 'Guest Customer (Redacted)';
                
                document.getElementById('detCustName').textContent = fullName;
                document.getElementById('detCustEmail').textContent = cust.email || 'No email address (Redacted)';
                document.getElementById('detCustPhone').textContent = cust.phone || 'No phone number (Redacted)';
                document.getElementById('detCustState').textContent = cust.state ? cust.state.toUpperCase() : 'N/A';

                // Formatting Address Info
                document.getElementById('detShippingAddr').innerHTML = formatAddress(parsed.shipping_address);
                document.getElementById('detBillingAddr').innerHTML = formatAddress(parsed.billing_address);

                // Status & Gateways
                const finStatus = (parsed.financial_status || 'unknown').toUpperCase();
                const fulStatus = (parsed.fulfillment_status || 'unfulfilled').toUpperCase();
                
                const detFin = document.getElementById('detFinStatus');
                detFin.textContent = finStatus;
                detFin.className = 'status-pill ' + getFinancialBadgeClass(parsed.financial_status);

                const detFul = document.getElementById('detFulStatus');
                detFul.textContent = fulStatus;
                detFul.className = 'status-pill ' + getFulfillmentBadgeClass(parsed.fulfillment_status);

                const gateways = parsed.payment_gateway_names || [];
                document.getElementById('detGateway').textContent = gateways.length > 0 ? gateways.join(', ') : 'N/A';

                // Totals
                const currency = parsed.currency || 'USD';
                const subtotal = parseFloat(parsed.subtotal_price || 0);
                const discounts = parseFloat(parsed.total_discounts || 0);
                const tax = parseFloat(parsed.total_tax || 0);
                
                let shipping = 0;
                if (parsed.shipping_lines && parsed.shipping_lines.length > 0) {
                    parsed.shipping_lines.forEach(line => {
                        shipping += parseFloat(line.price || 0);
                    });
                } else if (parsed.total_shipping_price_set && parsed.total_shipping_price_set.shop_money) {
                    shipping = parseFloat(parsed.total_shipping_price_set.shop_money.amount || 0);
                }
                
                const total = parseFloat(parsed.total_price || 0);

                document.getElementById('detSubtotal').textContent = formatMoney(subtotal, currency);
                document.getElementById('detDiscount').textContent = '-' + formatMoney(discounts, currency);
                document.getElementById('detShipping').textContent = formatMoney(shipping, currency);
                document.getElementById('detTax').textContent = formatMoney(tax, currency);
                document.getElementById('detTotal').textContent = formatMoney(total, currency);

                // Line Items
                const tbody = document.getElementById('detLineItemsBody');
                tbody.innerHTML = '';
                const lineItems = parsed.line_items || [];
                document.getElementById('detLineItemCount').textContent = lineItems.length;

                if (lineItems.length > 0) {
                    lineItems.forEach(item => {
                        const title = item.title || item.name || 'Unknown Product';
                        const variantTitle = item.variant_title ? `<br><small style="color: var(--text-muted); font-weight: 500;">Variant: ${item.variant_title}</small>` : '';
                        const sku = item.sku || '<span style="color: var(--text-muted); font-style: italic;">N/A</span>';
                        const price = parseFloat(item.price || 0);
                        const qty = parseInt(item.quantity || 0);
                        const itemTotal = price * qty;
                        
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid var(--border-color)';
                        tr.innerHTML = `
                            <td style="padding: 12px 18px; vertical-align: middle;">
                                <div style="font-weight: 600; color: var(--text-color);">${title}</div>
                                ${variantTitle}
                            </td>
                            <td style="padding: 12px 18px; vertical-align: middle; font-family: monospace;">${sku}</td>
                            <td style="padding: 12px 18px; vertical-align: middle; text-align: right; font-weight: 600;">${formatMoney(price, currency)}</td>
                            <td style="padding: 12px 18px; vertical-align: middle; text-align: center; font-weight: 600;">${qty}</td>
                            <td style="padding: 12px 18px; vertical-align: middle; text-align: right; font-weight: 700; color: var(--primary-color);">${formatMoney(itemTotal, currency)}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted); font-style: italic;">
                                No items in this order
                            </td>
                        </tr>
                    `;
                }

                detailsModal.classList.add('show');
                detailsModal.setAttribute('aria-hidden', 'false');
                isModalOpen = true;
            }
        });

        // Close modal helper
        function closeModal() {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            if (detailsModal) {
                detailsModal.classList.remove('show');
                detailsModal.setAttribute('aria-hidden', 'true');
            }
            isModalOpen = false;
        }

        if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
        if (detailsModalCloseBtn) detailsModalCloseBtn.addEventListener('click', closeModal);
        window.addEventListener('click', function(e) {
            if (e.target === modal || e.target === detailsModal) {
                closeModal();
            }
        });

        // Run recovery sync
        if (runRecoveryBtn) {
            runRecoveryBtn.addEventListener('click', function() {
                // Show loading state
                runRecoveryBtn.disabled = true;
                const originalHtml = runRecoveryBtn.innerHTML;
                runRecoveryBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Syncing All Stores...';

                fetch('{{ route("admin.shopify.orders.sync-recovery") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ store_id: null })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast(data.message, 'success', 5000);
                        triggerRefresh();
                    } else {
                        showToast(data.message || 'Recovery sync failed.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error running recovery sync:', error);
                    showToast('Failed to connect to recovery service.', 'error');
                })
                .finally(() => {
                    runRecoveryBtn.disabled = false;
                    runRecoveryBtn.innerHTML = originalHtml;
                });
            });
        }

        // Real-Time Polling Mechanism
        let pollingInterval = null;

        function triggerRefresh() {
            const queryParams = new URLSearchParams(window.location.search);
            const q = searchField.value.trim();
            const store = storeFilter.value;
            const status = financialStatusFilter.value;
            
            if (q) queryParams.set('q', q);
            else queryParams.delete('q');

            if (store) queryParams.set('store_id', store);
            else queryParams.delete('store_id');

            if (status) queryParams.set('financial_status', status);
            else queryParams.delete('financial_status');

            const url = window.location.pathname + '?' + queryParams.toString();

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.html) {
                    const oldContent = tableContainer.innerHTML;
                    if (oldContent.trim() !== data.html.trim()) {
                        tableContainer.innerHTML = data.html;
                        
                        const badge = document.getElementById('syncStatusBadge');
                        if (badge) {
                            badge.style.backgroundColor = '#d1fae5';
                            setTimeout(() => {
                                badge.style.backgroundColor = '#e6fffa';
                            }, 1500);
                        }
                    }
                }
            })
            .catch(error => console.error('Error reloading table:', error));
        }

        function startPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
            
            pollingInterval = setInterval(function() {
                // If user is actively typing in search or if modal is open, temporarily pause updates
                if (document.activeElement === searchField || isModalOpen || runRecoveryBtn.disabled) {
                    return;
                }
                triggerRefresh();
            }, 5000); // Poll every 5 seconds
        }

        startPolling();
    });
</script>
@endsection
