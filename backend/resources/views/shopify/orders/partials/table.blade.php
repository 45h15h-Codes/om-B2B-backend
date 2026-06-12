@php
    $isLocal = isset($isLocalOrders) && $isLocalOrders;
@endphp
<table class="list-table">
    <thead>
        <tr>
            @if($isLocal)
                <th>Customer Order Details</th>
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
                <th>Invoice Status</th>
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
                        <div style="display: inline-flex; gap: 6px; justify-content: flex-end; align-items: center;">
                            <div class="action-btn-group">
                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-primary btn-sm btn-details" style="font-size: 12px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                                    <i class="fa-solid fa-eye"></i> View Details
                                </a>
                                <button type="button" class="btn btn-primary btn-sm dropdown-trigger" title="More Actions">
                                    <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                                </button>
                                <div class="action-dropdown-menu">
                                    @if(session('admin_role') === 'super_admin' && $order->status === 'pending')
                                        <form action="{{ route('orders.approve', $order->id) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="action-dropdown-item">
                                                <i class="fa-solid fa-check-double"></i> Approve & Sync
                                            </button>
                                        </form>
                                    @endif

                                    @if($order->status === 'failed')
                                        <form action="{{ route('orders.retry', $order->id) }}" method="POST" style="margin: 0;">
                                            @csrf
                                            <button type="submit" class="action-dropdown-item">
                                                <i class="fa-solid fa-arrows-rotate"></i> Retry Sync
                                            </button>
                                        </form>
                                    @endif

                                    @if($order->shopify_draft_id)
                                        @php
                                            $invoiceSent = !empty($order->invoice_sent_at);
                                            $invoiceUrl = $order->invoice_url;
                                        @endphp

                                        @if(!$invoiceSent)
                                            @if(session('admin_role') === 'super_admin')
                                                <form action="{{ route('orders.send-invoice', $order->id) }}" method="POST" style="margin: 0;">
                                                    @csrf
                                                    <button type="submit" class="action-dropdown-item">
                                                        <i class="fa-solid fa-paper-plane"></i> Send Invoice
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button" class="action-dropdown-item" style="opacity: 0.5; cursor: not-allowed;" title="Super Admin Only">
                                                    <i class="fa-solid fa-paper-plane"></i> Send Invoice (Super Admin)
                                                </button>
                                            @endif
                                        @else
                                            @if(session('admin_role') === 'super_admin')
                                                <form action="{{ route('orders.send-invoice', $order->id) }}?resend=1" method="POST" style="margin: 0;">
                                                    @csrf
                                                    <button type="submit" class="action-dropdown-item">
                                                        <i class="fa-solid fa-arrows-spin"></i> Resend Invoice
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button" class="action-dropdown-item" style="opacity: 0.5; cursor: not-allowed;" title="Super Admin Only">
                                                    <i class="fa-solid fa-arrows-spin"></i> Resend Invoice (Super Admin)
                                                </button>
                                            @endif
                                        @endif

                                        @if(in_array($order->status, ['synced', 'invoice_sent']))
                                            @if(session('admin_role') === 'super_admin')
                                                <form action="{{ route('orders.complete', $order->id) }}" method="POST" style="margin: 0;">
                                                    @csrf
                                                    <button type="submit" class="action-dropdown-item">
                                                        <i class="fa-solid fa-circle-check"></i> Complete Order
                                                    </button>
                                                </form>
                                            @endif
                                        @endif

                                        <a href="{{ route('orders.invoice', $order->id) }}" target="_blank" class="action-dropdown-item" style="text-decoration: none;">
                                            <i class="fa-solid fa-file-invoice-dollar"></i> View Invoice
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
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
                        @php
                            $invoiceStatus = 'Draft';
                            $invoiceBadgeClass = 'badge-neutral';
                            if ($order->localOrder) {
                                $statusVal = strtolower($order->localOrder->status);
                                if (in_array($statusVal, ['pending', 'approved', 'syncing', 'inventory_unavailable'])) {
                                    $invoiceStatus = 'Draft';
                                    $invoiceBadgeClass = 'badge-neutral';
                                } elseif ($statusVal === 'synced') {
                                    if ($order->localOrder->invoice_sent_at) {
                                        $invoiceStatus = 'Sent';
                                        $invoiceBadgeClass = 'badge-warning';
                                    } else {
                                        $invoiceStatus = 'Ready to Send';
                                        $invoiceBadgeClass = 'badge-warning';
                                    }
                                } elseif ($statusVal === 'invoice_sent') {
                                    $invoiceStatus = 'Sent';
                                    $invoiceBadgeClass = 'badge-warning';
                                } elseif (in_array($statusVal, ['paid', 'completed'])) {
                                    $invoiceStatus = 'Paid';
                                    $invoiceBadgeClass = 'badge-success';
                                } elseif ($statusVal === 'failed') {
                                    $invoiceStatus = 'Sync Failed';
                                    $invoiceBadgeClass = 'badge-danger';
                                } elseif ($statusVal === 'cancelled') {
                                    $invoiceStatus = 'Cancelled';
                                    $invoiceBadgeClass = 'badge-danger';
                                }
                            } else {
                                $invoiceStatus = (strtolower($order->financial_status) === 'paid') ? 'Paid' : 'Pending';
                                $invoiceBadgeClass = (strtolower($order->financial_status) === 'paid') ? 'badge-success' : 'badge-warning';
                            }
                        @endphp
                        <span class="status-pill {{ $invoiceBadgeClass }}">
                            <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                            {{ $invoiceStatus }}
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
                            <div class="action-btn-group">
                                <button type="button" class="btn btn-primary btn-sm view-details-btn btn-details" 
                                        data-order-id="{{ $order->id }}"
                                        data-json="{{ json_encode($orderPayload) }}"
                                        data-order-number="{{ $order->order_number }}"
                                        data-store-name="{{ $order->shopifyStore ? $order->shopifyStore->store_name : 'N/A' }}"
                                        data-store-domain="{{ $order->shopifyStore ? $order->shopifyStore->shop_domain : 'N/A' }}"
                                        data-created-at="{{ $order->created_at ? $order->created_at->format('M d, Y H:i A') : 'N/A' }}"
                                        data-invoice-status="{{ $invoiceStatus }}"
                                        data-invoice-badge-class="{{ $invoiceBadgeClass }}"
                                        data-invoice-sent-at="{{ $order->localOrder && $order->localOrder->invoice_sent_at ? $order->localOrder->invoice_sent_at->format('M d, Y H:i A') : 'N/A' }}"
                                        data-invoice-url="{{ $order->localOrder ? $order->localOrder->invoice_url : '' }}"
                                        data-draft-order-id="{{ $order->localOrder ? '#' . $order->localOrder->id : 'N/A' }}"
                                        data-shopify-draft-id="{{ $order->localOrder ? $order->localOrder->shopify_draft_id : '' }}">
                                    <i class="fa-solid fa-eye"></i> View Details
                                </button>
                                <button type="button" class="btn btn-primary btn-sm dropdown-trigger" title="More Actions">
                                    <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                                </button>
                                <div class="action-dropdown-menu">
                                    <button type="button" class="action-dropdown-item view-json-btn" 
                                            data-json="{{ json_encode($orderPayload) }}"
                                            data-order-number="{{ $order->order_number }}">
                                        <i class="fa-solid fa-code"></i> View Raw JSON
                                    </button>
                                    
                                    @if($order->localOrder)
                                        @php
                                            $lo = $order->localOrder;
                                            $hasDraftId = !empty($lo->shopify_draft_id);
                                            $invoiceSent = !empty($lo->invoice_sent_at);
                                            $invoiceUrl = $lo->invoice_url;
                                        @endphp
                                        
                                        @if($hasDraftId)
                                            <div class="action-dropdown-divider"></div>
                                            @if(!$invoiceSent)
                                                @if(session('admin_role') === 'super_admin')
                                                    <form action="{{ route('orders.send-invoice', $lo->id) }}" method="POST" style="margin: 0;">
                                                        @csrf
                                                        <button type="submit" class="action-dropdown-item">
                                                            <i class="fa-solid fa-paper-plane"></i> Send Invoice
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="action-dropdown-item" style="opacity: 0.5; cursor: not-allowed;" title="Super Admin Only">
                                                        <i class="fa-solid fa-paper-plane"></i> Send Invoice (Super Admin)
                                                    </button>
                                                @endif
                                            @else
                                                @if(session('admin_role') === 'super_admin')
                                                    <form action="{{ route('orders.send-invoice', $lo->id) }}?resend=1" method="POST" style="margin: 0;">
                                                        @csrf
                                                        <button type="submit" class="action-dropdown-item">
                                                            <i class="fa-solid fa-arrows-spin"></i> Resend Invoice
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="action-dropdown-item" style="opacity: 0.5; cursor: not-allowed;" title="Super Admin Only">
                                                        <i class="fa-solid fa-arrows-spin"></i> Resend Invoice (Super Admin)
                                                    </button>
                                                @endif
                                            @endif
                                        @endif
                                        
                                        <a href="{{ route('shopify.orders.invoice', $order->id) }}" target="_blank" class="action-dropdown-item">
                                            <i class="fa-solid fa-file-invoice-dollar"></i> View Invoice
                                        </a>
                                    @endif
                                </div>
                            </div>
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
