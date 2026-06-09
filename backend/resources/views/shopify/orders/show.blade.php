@extends('layouts.app')

@section('styles')
<style>
    .show-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .header-info h2 {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 4px;
    }

    .header-info p {
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 500;
    }

    .grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 28px;
    }

    @media (max-width: 991px) {
        .grid {
            grid-template-columns: 1fr;
        }
    }

    .card {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        margin-bottom: 28px;
        overflow: hidden;
    }

    .card-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h3 {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-color);
    }

    .card-body {
        padding: 24px;
    }

    /* Badges */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: capitalize;
    }

    .badge-pending { background-color: #f3f4f6; color: #4b5563; }
    .badge-approved { background-color: #eff6ff; color: #1d4ed8; }
    .badge-pending_sync { background-color: #fef3c7; color: #d97706; }
    .badge-syncing { background-color: #f5f3ff; color: #7c3aed; }
    .badge-synced { background-color: #ecfdf5; color: #047857; }
    .badge-invoice_sent { background-color: #e0f2fe; color: #0369a1; }
    .badge-paid { background-color: #dcfce7; color: #15803d; }
    .badge-completed { background-color: #f0fdf4; color: #15803d; }
    .badge-failed { background-color: #fef2f2; color: #b91c1c; }
    .badge-cancelled { background-color: #fee2e2; color: #991b1b; }

    .badge-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }

    .badge-pending .badge-dot { background-color: #9ca3af; }
    .badge-approved .badge-dot { background-color: #2563eb; }
    .badge-pending_sync .badge-dot { background-color: #fbbf24; }
    .badge-syncing .badge-dot { background-color: #8b5cf6; }
    .badge-synced .badge-dot { background-color: #10b981; }
    .badge-invoice_sent .badge-dot { background-color: #0ea5e9; }
    .badge-paid .badge-dot { background-color: #16a34a; }
    .badge-completed .badge-dot { background-color: #22c55e; }
    .badge-failed .badge-dot { background-color: #ef4444; }
    .badge-cancelled .badge-dot { background-color: #dc2626; }

    /* Timeline Styling */
    .timeline {
        position: relative;
        padding-left: 24px;
        margin-top: 10px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 8px;
        bottom: 8px;
        left: 6px;
        width: 2px;
        background-color: var(--border-color);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 24px;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-badge {
        position: absolute;
        left: -24px;
        top: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background-color: #ffffff;
        border: 3px solid var(--primary-color);
        box-sizing: border-box;
    }

    .timeline-badge.success { border-color: var(--success-color); }
    .timeline-badge.failed { border-color: var(--error-color); }
    .timeline-badge.info { border-color: #6366f1; }

    .timeline-content {
        background-color: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 12px 16px;
    }

    .timeline-time {
        font-size: 11px;
        color: var(--text-muted);
        font-weight: 600;
        margin-bottom: 4px;
    }

    .timeline-action {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 2px;
        text-transform: capitalize;
    }

    .timeline-message {
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 500;
    }

    /* Info List */
    .info-list {
        list-style: none;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-color);
        font-size: 14px;
        font-weight: 500;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        color: var(--text-muted);
        font-weight: 600;
    }

    .info-value {
        color: var(--text-color);
        font-weight: 700;
        text-align: right;
    }

    /* Json Viewer */
    .json-code {
        background-color: #0f172a;
        color: #e2e8f0;
        padding: 16px;
        border-radius: 8px;
        font-family: monospace;
        font-size: 12px;
        max-height: 350px;
        overflow-y: auto;
        white-space: pre-wrap;
    }
</style>
@endsection

@section('content')
<div class="show-container">
    <div class="header-section">
        <div class="header-info">
            <h2>Order Details</h2>
            <p style="font-family: monospace; font-size: 13px;">UUID: {{ $order->uuid }}</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back to List
            </a>

            @if($isSuper && $order->status === 'pending')
                <form action="{{ route('orders.approve', $order->id) }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-check-double"></i> Approve & Sync
                    </button>
                </form>
            @endif

            @if($order->status === 'failed')
                <form action="{{ route('orders.retry', $order->id) }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="background-color: var(--primary-light); color: var(--primary-color); border-color: var(--primary-color);">
                        <i class="fa-solid fa-arrows-rotate"></i> Retry Sync
                    </button>
                </form>
            @endif

            @if($order->shopify_draft_id && in_array($order->status, ['synced', 'failed']) && !$order->invoice_sent_at)
                @if($isSuper)
                    <form action="{{ route('orders.send-invoice', $order->id) }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="background-color: #108bb6; border-color: #108bb6;">
                            <i class="fa-solid fa-paper-plane"></i> Send Invoice
                        </button>
                    </form>
                @endif
            @endif

            @if($order->shopify_draft_id && in_array($order->status, ['synced', 'invoice_sent']))
                @if($isSuper)
                    <form action="{{ route('orders.complete', $order->id) }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="background-color: var(--success-color); border-color: var(--success-color);">
                            <i class="fa-solid fa-circle-check"></i> Complete Order
                        </button>
                    </form>
                @endif
            @endif

            @if($order->invoice_url)
                <a href="{{ $order->invoice_url }}" target="_blank" class="btn btn-secondary" style="color: var(--primary-color); border-color: var(--primary-color);">
                    <i class="fa-solid fa-file-invoice-dollar"></i> View Invoice Page <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px; margin-left: 4px;"></i>
                </a>
            @endif

        </div>
    </div>

    @if($order->error_message)
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <strong style="display: block; margin-bottom: 2px;">Sync Error</strong>
                <span style="font-weight: 500;">{{ $order->error_message }}</span>
            </div>
        </div>
    @endif

    <div class="grid">
        <!-- Left Column: Items & JSON payloads -->
        <div>
            <!-- Order Items -->
            <div class="card">
                <div class="card-header">
                    <h3>Items Snapshot</h3>
                </div>
                <div class="table-responsive">
                    <table class="table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th>Qty</th>
                                <th>Price Snapshot</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        @if($item['product_type'] === 'diamond')
                                            <div style="font-weight: 700;">Diamond (Stock: {{ $item['stock_no'] }})</div>
                                            <div style="font-size: 12px; color: var(--text-muted);">
                                                Shape: {{ $item['shape'] }} | Carat: {{ $item['carat'] }}ct | Color: {{ $item['color'] }} | Clarity: {{ $item['clarity'] }}
                                            </div>
                                        @else
                                            <div style="font-weight: 700;">Jewelry (SKU: {{ $item['sku'] }})</div>
                                            <div style="font-size: 12px; color: var(--text-muted);">
                                                Name: {{ $item['name'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $item['quantity'] }}</td>
                                    <td>${{ number_format($item['price_snapshot'], 2) }}</td>
                                    <td style="text-align: right; font-weight: 700; color: var(--primary-color);">
                                        ${{ number_format($item['price_snapshot'] * $item['quantity'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Shopify Payload & Response (Optional display for debugging) -->
            @if($order->shopify_response)
                <div class="card">
                    <div class="card-header">
                        <h3>Shopify API Details</h3>
                    </div>
                    <div class="card-body">
                        <h4 style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Shopify Draft Order Response</h4>
                        <pre class="json-code"><code>{{ json_encode($order->shopify_response, JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Status & Timeline -->
        <div>
            <!-- Status Card -->
            <div class="card">
                <div class="card-header">
                    <h3>Summary</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <ul class="info-list">
                        <li class="info-row" style="padding: 16px 20px;">
                            <span class="info-label">Status</span>
                            <span class="info-value">
                                <span class="badge badge-{{ $order->status }}">
                                    <span class="badge-dot"></span>
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </span>
                        </li>
                        <li class="info-row" style="padding: 16px 20px;">
                            <span class="info-label">Shopify Store</span>
                            <span class="info-value">
                                <div>{{ $order->shopifyStore->store_name ?: 'Store' }}</div>
                                <div style="font-size: 11px; font-weight: 600; color: var(--text-muted);">{{ $order->shopifyStore->shop_domain }}</div>
                            </span>
                        </li>
                        <li class="info-row" style="padding: 16px 20px;">
                            <span class="info-label">Customer Name</span>
                            <span class="info-value">{{ $order->customer_name ?: '-' }}</span>
                        </li>
                        <li class="info-row" style="padding: 16px 20px;">
                            <span class="info-label">Customer Email</span>
                            <span class="info-value">{{ $order->email ?: '-' }}</span>
                        </li>
                        <li class="info-row" style="padding: 16px 20px;">
                            <span class="info-label">Customer Phone</span>
                            <span class="info-value">{{ $order->customer_phone ?: '-' }}</span>
                        </li>
                        @if($order->shopify_draft_id)
                            <li class="info-row" style="padding: 16px 20px;">
                                <span class="info-label">Shopify Draft ID</span>
                                <span class="info-value">{{ $order->shopify_draft_id }}</span>
                            </li>
                        @endif
                        @if($order->shopify_order_id)
                            <li class="info-row" style="padding: 16px 20px;">
                                <span class="info-label">Shopify Order ID</span>
                                <span class="info-value">{{ $order->shopify_order_id }}</span>
                            </li>
                        @endif
                        @if($order->shopify_order_number)
                            <li class="info-row" style="padding: 16px 20px;">
                                <span class="info-label">Shopify Order #</span>
                                <span class="info-value">{{ $order->shopify_order_number }}</span>
                            </li>
                        @endif
                        @if($order->shopify_order_admin_url)
                            <li class="info-row" style="padding: 16px 20px;">
                                <span class="info-label">Shopify Admin URL</span>
                                <span class="info-value">
                                    <a href="{{ $order->shopify_order_admin_url }}" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                        Open Shopify <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 10px; margin-left: 2px;"></i>
                                    </a>
                                </span>
                            </li>
                        @endif
                        @if($order->invoice_sent_at)
                            <li class="info-row" style="padding: 16px 20px;">
                                <span class="info-label">Invoice Sent At</span>
                                <span class="info-value">{{ $order->invoice_sent_at->format('M d, Y H:i') }}</span>
                            </li>
                        @endif
                        <li class="info-row" style="padding: 16px 20px;">
                            <span class="info-label">Subtotal</span>
                            <span class="info-value">${{ number_format($order->subtotal, 2) }}</span>
                        </li>
                        <li class="info-row" style="padding: 16px 20px;">
                            <span class="info-label">Discount Applied</span>
                            <span class="info-value" style="color: var(--error-color);">-&nbsp;${{ number_format($order->discount, 2) }}</span>
                        </li>
                        <li class="info-row" style="padding: 16px 20px; border-bottom: none; background-color: #f8fafc;">
                            <span class="info-label" style="color: var(--text-color); font-size: 16px;">Total</span>
                            <span class="info-value" style="color: var(--primary-color); font-size: 18px;">${{ number_format($order->total, 2) }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Audit Logs Timeline -->
            <div class="card">
                <div class="card-header">
                    <h3>Unified Sync & Inventory Timeline</h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @forelse($timeline as $item)
                            @php
                                $badgeClass = 'info';
                                $titleLower = strtolower($item['title']);
                                if (str_contains($titleLower, 'success') || str_contains($titleLower, 'completed') || str_contains($titleLower, 'approved') || str_contains($titleLower, 'paid')) {
                                    $badgeClass = 'success';
                                } elseif (str_contains($titleLower, 'failed') || str_contains($titleLower, 'error') || str_contains($titleLower, 'cancelled')) {
                                    $badgeClass = 'failed';
                                }
                            @endphp
                            <div class="timeline-item">
                                <div class="timeline-badge {{ $badgeClass }}"></div>
                                <div class="timeline-content">
                                    <div class="timeline-time">{{ $item['time'] ? $item['time']->format('M d, Y H:i:s') : '-' }}</div>
                                    <div class="timeline-action">{{ $item['title'] }}</div>
                                    <div class="timeline-message">{{ $item['description'] }}</div>
                                </div>
                            </div>
                        @empty
                            <p style="color: var(--text-muted); font-style: italic; text-align: center; padding: 20px 0;">No logs or inventory actions associated with this order.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
