@php
    $isLocal = isset($isLocalOrders) && $isLocalOrders;
@endphp
<table class="list-table">
    <thead>
        <tr>
            @if($isLocal)
                <th>Order/Draft Details</th>
                <th>Shopify Store</th>
                <th>Customer Info</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Created By</th>
                <th>Created At</th>
                <th style="text-align: right;">Actions</th>
            @else
                <th>Order Number</th>
                <th>Shopify Store</th>
                <th>Customer Info</th>
                <th>Total Price</th>
                <th>Financial Status</th>
                <th>Fulfillment Status</th>
                <th>Created At</th>
                <th style="text-align: right;">Actions</th>
            @endif
        </tr>
    </thead>
    <tbody id="ordersTableBody">
        @forelse($orders as $order)
            <tr class="order-row" data-order-id="{{ $order->id }}">
                @if($isLocal)
                    <td>
                        <div style="font-weight: 700; color: var(--primary-color);">
                            #{{ $order->id }}
                        </div>
                        @if($order->shopify_order_number)
                            <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;">
                                Shopify #: {{ $order->shopify_order_number }}
                            </div>
                        @endif
                        @if($order->shopify_draft_id)
                            <div style="font-size: 11px; color: var(--text-muted);">
                                Draft ID: {{ $order->shopify_draft_id }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-color);">
                            {{ $order->shopifyStore ? $order->shopifyStore->store_name : 'N/A' }}
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                            {{ $order->shopifyStore ? $order->shopifyStore->shop_domain : 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-color);">
                            {{ $order->customer_name ?: 'Guest Customer' }}
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            {{ $order->email ?: 'No email address' }}
                        </div>
                    </td>
                    <td>
                        <strong style="color: var(--text-color); font-size: 14px;">
                            USD {{ number_format($order->total, 2) }}
                        </strong>
                    </td>
                    <td>
                        @php
                            $status = strtolower($order->status);
                            $badgeClass = 'badge-neutral';
                            if (in_array($status, ['paid', 'completed', 'synced'])) $badgeClass = 'badge-success';
                            elseif (in_array($status, ['pending', 'pending_sync', 'syncing', 'approved', 'invoice_sent'])) $badgeClass = 'badge-warning';
                            elseif (in_array($status, ['failed', 'cancelled'])) $badgeClass = 'badge-danger';
                        @endphp
                        <span class="status-pill {{ $badgeClass }}">
                            <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-color);">
                            {{ $order->creator ? $order->creator->name : 'System' }}
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 500;">
                            {{ $order->created_at ? $order->created_at->format('M d, Y') : '-' }}
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                            {{ $order->created_at ? $order->created_at->format('H:i A') : '-' }}
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-secondary btn-sm" style="font-size: 12px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-eye"></i> View Details
                        </a>
                    </td>
                @else
                    @php
                        $orderPayload = $order->order_json;
                        if (is_string($orderPayload)) {
                            $orderPayload = json_decode($orderPayload, true);
                            if (is_string($orderPayload)) {
                                $orderPayload = json_decode($orderPayload, true);
                            }
                        }
                    @endphp
                    <td>
                        <div style="font-weight: 700; color: var(--primary-color);">
                            #{{ $order->order_number ?: 'N/A' }}
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                            Shopify ID: {{ $order->shopify_order_id }}
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-color);">
                            {{ $order->shopifyStore ? $order->shopifyStore->store_name : 'N/A' }}
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                            {{ $order->shopifyStore ? $order->shopifyStore->shop_domain : 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-color);">
                            {{ $order->customer_name ?: 'Guest Customer' }}
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            {{ $order->customer_email ?: 'No email address' }}
                        </div>
                    </td>
                    <td>
                        <strong style="color: var(--text-color); font-size: 14px;">
                            {{ $order->currency }} {{ number_format($order->total_price, 2) }}
                        </strong>
                    </td>
                    <td>
                        @php
                            $financial = strtolower($order->financial_status);
                            $finBadgeClass = 'badge-neutral';
                            if ($financial === 'paid') $finBadgeClass = 'badge-success';
                            elseif (in_array($financial, ['pending', 'authorized'])) $finBadgeClass = 'badge-warning';
                            elseif (in_array($financial, ['refunded', 'voided'])) $finBadgeClass = 'badge-danger';
                        @endphp
                        <span class="status-pill {{ $finBadgeClass }}">
                            <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                            {{ ucfirst($order->financial_status ?: 'unknown') }}
                        </span>
                    </td>
                    <td>
                        @php
                            $fulfillment = strtolower($order->fulfillment_status);
                            $fulBadgeClass = 'badge-neutral';
                            if ($fulfillment === 'fulfilled') $fulBadgeClass = 'badge-success';
                            elseif ($fulfillment === 'unfulfilled') $fulBadgeClass = 'badge-neutral';
                            elseif (in_array($fulfillment, ['partial', 'restocked'])) $fulBadgeClass = 'badge-warning';
                        @endphp
                        <span class="status-pill {{ $fulBadgeClass }}">
                            <i class="fa-solid fa-box" style="font-size: 10px;"></i>
                            {{ ucfirst($order->fulfillment_status ?: 'Unfulfilled') }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 500;">
                            {{ $order->created_at ? $order->created_at->format('M d, Y') : '-' }}
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                            {{ $order->created_at ? $order->created_at->format('H:i A') : '-' }}
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: inline-flex; gap: 6px; justify-content: flex-end; align-items: center;">
                            <button type="button" class="btn btn-primary btn-sm view-details-btn" 
                                    data-json="{{ json_encode($orderPayload) }}"
                                    data-order-number="{{ $order->order_number }}"
                                    data-store-name="{{ $order->shopifyStore ? $order->shopifyStore->store_name : 'N/A' }}"
                                    data-store-domain="{{ $order->shopifyStore ? $order->shopifyStore->shop_domain : 'N/A' }}"
                                    data-created-at="{{ $order->created_at ? $order->created_at->format('M d, Y H:i A') : 'N/A' }}">
                                <i class="fa-solid fa-eye"></i> View Details
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm view-json-btn" 
                                    data-json="{{ json_encode($orderPayload) }}"
                                    data-order-number="{{ $order->order_number }}"
                                    title="View Raw JSON Payload">
                                <i class="fa-solid fa-code"></i>
                            </button>
                        </div>
                    </td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px 0; font-style: italic;">
                    No orders found matching search query or filters.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-container" id="paginationLinks">
    {{ $orders->links() }}
</div>
