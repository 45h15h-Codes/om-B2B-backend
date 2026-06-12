@php
    $orderNumber = '';
    $invoiceNumber = '';
    $invoiceDate = '';
    $paymentStatus = '';
    $customerName = '';
    $customerEmail = '';
    $customerPhone = '';
    $billingAddress = '';
    $shippingAddress = '';

    // Company Info
    $companyName = 'OM Gems';
    $companyEmail = 'info@omgems.com';
    $companyPhone = '+1 (212) 555-0199';
    $companyAddress = '580 Fifth Avenue, Suite 1210, New York, NY 10036';
    $companyWebsite = 'www.omgems.com';

    // Pricing
    $subtotal = 0.00;
    $discount = 0.00;
    $shipping = 0.00;
    $tax = 0.00;
    $grandTotal = 0.00;

    $items = [];

    // Helper to format address from Shopify object/array
    $formatAddress = function($addressObj) {
        if (!$addressObj) return '';
        if (is_string($addressObj)) {
            // Check if it's JSON or a string
            $decoded = json_decode($addressObj, true);
            if (is_array($decoded)) {
                $addressObj = $decoded;
            } else {
                return nl2br(e($addressObj));
            }
        }
        $parts = [];
        if (!empty($addressObj['name'])) {
            $parts[] = $addressObj['name'];
        } elseif (!empty($addressObj['first_name']) || !empty($addressObj['last_name'])) {
            $parts[] = trim(($addressObj['first_name'] ?? '') . ' ' . ($addressObj['last_name'] ?? ''));
        }
        if (!empty($addressObj['company'])) {
            $parts[] = $addressObj['company'];
        }
        if (!empty($addressObj['address1'])) {
            $parts[] = $addressObj['address1'];
        }
        if (!empty($addressObj['address2'])) {
            $parts[] = $addressObj['address2'];
        }
        $cityStateZip = trim(
            ($addressObj['city'] ?? '') . ', ' . 
            ($addressObj['province_code'] ?? $addressObj['province'] ?? '') . ' ' . 
            ($addressObj['zip'] ?? '')
        );
        if ($cityStateZip !== ',') {
            $parts[] = $cityStateZip;
        }
        if (!empty($addressObj['country'])) {
            $parts[] = $addressObj['country'];
        }
        if (!empty($addressObj['phone'])) {
            $parts[] = 'Phone: ' . $addressObj['phone'];
        }
        return implode('<br>', array_map('e', $parts));
    };

    if ($isWebhook) {
        // ShopifyOrder model
        $orderNumber = $order->order_number ? '#' . $order->order_number : 'N/A';
        $invoiceNumber = 'INV-' . ($order->order_number ?: $order->id);
        $invoiceDate = $order->created_at ? $order->created_at->format('M d, Y') : date('M d, Y');
        
        $statusLower = strtolower($order->financial_status);
        if ($statusLower === 'paid') {
            $paymentStatus = 'Paid';
        } elseif ($statusLower === 'voided' || $statusLower === 'refunded') {
            $paymentStatus = ucfirst($statusLower);
        } else {
            $paymentStatus = 'Unpaid';
        }
        
        $customerName = $order->customer_name ?: 'Valued Customer';
        $customerEmail = $order->customer_email ?: 'N/A';
        
        $payload = $order->order_json;
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
            if (is_string($payload)) {
                $payload = json_decode($payload, true);
            }
        }

        if (is_array($payload)) {
            $customerPhone = $order->customer_phone;
            $billingAddress = $formatAddress($payload['billing_address'] ?? null);
            $shippingAddress = $formatAddress($payload['shipping_address'] ?? null);
            
            // If empty, fallback to default address
            if (!$billingAddress && isset($payload['customer']['default_address'])) {
                $billingAddress = $formatAddress($payload['customer']['default_address']);
            }
            if (!$shippingAddress && isset($payload['customer']['default_address'])) {
                $shippingAddress = $formatAddress($payload['customer']['default_address']);
            }

            // Get shipping, tax
            $shipping = isset($payload['total_shipping_price_set']['shop_money']['amount']) 
                ? (float) $payload['total_shipping_price_set']['shop_money']['amount'] 
                : (isset($payload['total_shipping_line_items_price']) ? (float)$payload['total_shipping_line_items_price'] : 0.00);
            
            $tax = isset($payload['total_tax']) ? (float)$payload['total_tax'] : 0.00;
        }

        $subtotal = (float)$order->subtotal;
        $discount = (float)$order->discount;
        $grandTotal = (float)$order->total;
        $items = $order->items;
    } else {
        // Local Order model
        $orderNumber = $order->shopify_order_number ? '#' . $order->shopify_order_number : ($order->shopify_draft_id ? 'Draft: ' . $order->shopify_draft_id : 'Local: #' . $order->id);
        $invoiceNumber = $order->shopify_order_number ? 'INV-' . $order->shopify_order_number : ($order->shopify_draft_id ? 'INV-DRAFT-' . $order->id : 'INV-LOCAL-' . $order->id);
        $invoiceDate = $order->created_at ? $order->created_at->format('M d, Y') : date('M d, Y');
        
        // Map status to readable payment status
        $statusLower = strtolower($order->status);
        if (in_array($statusLower, ['paid', 'completed'])) {
            $paymentStatus = 'Paid';
        } elseif ($statusLower === 'cancelled') {
            $paymentStatus = 'Cancelled';
        } else {
            $paymentStatus = 'Unpaid';
        }

        $customerName = $order->customer_name ?: 'Valued Customer';
        $customerEmail = $order->email ?: 'N/A';
        $customerPhone = $order->customer_phone ?: 'N/A';

        // Check shopify_payload or response for addresses
        $payload = $order->shopify_payload ?: $order->shopify_response;
        if (is_array($payload)) {
            $billingAddress = $formatAddress($payload['billing_address'] ?? null);
            $shippingAddress = $formatAddress($payload['shipping_address'] ?? null);
            
            if (!$billingAddress && isset($payload['customer']['default_address'])) {
                $billingAddress = $formatAddress($payload['customer']['default_address']);
            }
            if (!$shippingAddress && isset($payload['customer']['default_address'])) {
                $shippingAddress = $formatAddress($payload['customer']['default_address']);
            }

            $tax = isset($payload['total_tax']) ? (float)$payload['total_tax'] : 0.00;
            $shipping = isset($payload['total_shipping_line_items_price']) ? (float)$payload['total_shipping_line_items_price'] : 0.00;
        }

        $subtotal = (float)$order->subtotal;
        $discount = (float)$order->discount;
        $grandTotal = (float)$order->total;

        // Line items
        foreach ($order->items as $item) {
            $sku = $item['sku'] ?? $item['stock_no'] ?? 'N/A';
            $name = '';
            if ($item['product_type'] === 'diamond') {
                $name = 'Diamond (' . ($item['shape'] ?? '') . ' ' . ($item['carat'] ?? '') . 'ct ' . ($item['color'] ?? '') . ' ' . ($item['clarity'] ?? '') . ')';
            } else {
                $name = $item['name'] ?? 'Jewelry Product';
            }
            $items[] = [
                'sku' => $sku,
                'name' => $name,
                'quantity' => $item['quantity'] ?? 1,
                'price_snapshot' => $item['price_snapshot'] ?? 0.00,
            ];
        }
    }

    // Default address formatting if completely empty
    if (!$billingAddress) {
        $billingAddress = e($customerName);
        if ($customerPhone && $customerPhone !== 'N/A') {
            $billingAddress .= '<br>Phone: ' . e($customerPhone);
        }
    }
    if (!$shippingAddress) {
        $shippingAddress = $billingAddress;
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoiceNumber }} - OM Gems</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #108bb6;
            --primary-dark: #0b7094;
            --text-color: #1a202c;
            --text-muted: #4a5568;
            --border-color: #e2e8f0;
            --bg-color: #f7fafc;
            --invoice-width: 850px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Action Bar (Sticky Top) */
        .action-bar {
            background-color: #1e293b;
            padding: 12px 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .action-bar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .action-bar-title {
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
        }

        .action-bar-right {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .btn-back {
            color: #94a3b8;
            border-color: #334155;
            background-color: transparent;
        }

        .btn-back:hover {
            color: #ffffff;
            background-color: #334155;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-success {
            background-color: #10b981;
            color: #ffffff;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-secondary {
            background-color: #4b5563;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background-color: #374151;
        }

        /* Invoice Container */
        .invoice-wrapper {
            max-width: var(--invoice-width);
            margin: 40px auto;
            padding: 48px;
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Logo Brand */
        .brand-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary-color);
            letter-spacing: -1px;
            text-transform: uppercase;
        }

        .logo-subtitle {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: -4px;
        }

        .invoice-title-block {
            text-align: right;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-paid {
            background-color: #ecfdf5;
            color: #065f46;
        }

        .status-unpaid {
            background-color: #fff7ed;
            color: #9a3412;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background-color: #fef2f2;
            color: #991b1b;
        }

        /* Invoice Meta */
        .invoice-meta {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 24px;
            margin-bottom: 32px;
        }

        .meta-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .meta-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }

        /* Billing Shipping Grid */
        .address-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .address-block h4 {
            font-size: 12px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 6px;
        }

        .address-text {
            font-size: 13px;
            color: #334155;
            font-weight: 500;
            line-height: 1.6;
        }

        /* Items Table */
        .items-table-container {
            margin-bottom: 32px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            text-align: left;
            padding: 10px 16px;
            background-color: #f8fafc;
            border-bottom: 2px solid var(--border-color);
        }

        .items-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
            vertical-align: middle;
        }

        .item-name {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .item-sku {
            font-family: monospace;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }

        .items-table td.numeric {
            text-align: right;
        }

        .items-table th.numeric {
            text-align: right;
        }

        /* Summary Section */
        .summary-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }

        .summary-box {
            width: 300px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 500;
            color: #475569;
        }

        .summary-row.discount {
            color: #ef4444;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid var(--border-color);
            padding-top: 12px;
            margin-top: 4px;
        }

        /* Company / Footer Info */
        .company-info-block {
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }

        .company-contact {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        .company-contact strong {
            color: #334155;
            font-size: 13px;
        }

        .company-address-text {
            max-width: 250px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.6;
            text-align: right;
            font-weight: 500;
        }

        .invoice-footer-note {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Alerts */
        .alert-bar {
            margin: 20px auto 0 auto;
            max-width: var(--invoice-width);
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error {
            background-color: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }

        /* Print Override */
        @media print {
            body {
                background-color: #ffffff;
            }

            .action-bar, .alert-bar {
                display: none !important;
            }

            .invoice-wrapper {
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
                max-width: 100%;
                width: 100%;
            }

            /* Prevent page break inside billing/shipping address and summary */
            .address-grid, .summary-container, .company-info-block {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar -->
    <div class="action-bar">
        <div class="action-bar-left">
            @php
                $backUrl = $isWebhook ? route('admin.shopify.orders') : route('orders.index');
                if (request()->has('from_show') && request()->input('from_show')) {
                    $backUrl = $isWebhook ? route('admin.shopify.orders.show', $order->id) : route('orders.show', $order->id);
                }
            @endphp
            <a href="{{ $backUrl }}" class="btn btn-back">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
            <span class="action-bar-title">Invoice: {{ $invoiceNumber }}</span>
        </div>
        <div class="action-bar-right">
            <!-- Send Email Action -->
            @php
                $localOrderId = null;
                $hasDraftId = false;
                $invoiceSent = false;
                if ($isWebhook) {
                    if (isset($localOrder) && $localOrder) {
                        $localOrderId = $localOrder->id;
                        $hasDraftId = !empty($localOrder->shopify_draft_id);
                        $invoiceSent = !empty($localOrder->invoice_sent_at);
                    }
                } else {
                    $localOrderId = $order->id;
                    $hasDraftId = !empty($order->shopify_draft_id);
                    $invoiceSent = !empty($order->invoice_sent_at);
                }
                
                $isSuper = (session('admin_role', 'normal_admin') === 'super_admin');
            @endphp

            @if($localOrderId && $hasDraftId && $isSuper)
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('send-invoice-form').submit();">
                    <i class="fa-solid fa-paper-plane"></i> {{ $invoiceSent ? 'Resend Invoice Email' : 'Send Invoice Email' }}
                </button>
                <form id="send-invoice-form" action="{{ route('orders.send-invoice', $localOrderId) }}{{ $invoiceSent ? '?resend=1' : '' }}" method="POST" style="display: none;">
                    @csrf
                </form>
            @endif

            <button type="button" class="btn btn-primary" onclick="window.print();">
                <i class="fa-solid fa-print"></i> Print Invoice
            </button>

            <button type="button" class="btn btn-success" onclick="window.print();">
                <i class="fa-solid fa-file-pdf"></i> Download PDF
            </button>
        </div>
    </div>

    <!-- Toast/Alert Notifications if any -->
    @if(session('success'))
        <div class="alert-bar">
            <i class="fa-solid fa-circle-check" style="font-size: 16px;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert-bar alert-error">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 16px;"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Invoice Sheet -->
    <div class="invoice-wrapper">
        <!-- Header Brand -->
        <div class="brand-container">
            <div>
                <div class="logo-text">OM GEMS</div>
                <div class="logo-subtitle">Fine Diamonds & Jewelry</div>
            </div>
            <div class="invoice-title-block">
                <div class="invoice-title">INVOICE</div>
                <div style="margin-top: 8px;">
                    <span class="status-badge status-{{ strtolower($paymentStatus) }}">
                        {{ $paymentStatus }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Invoice Meta Details -->
        <div class="invoice-meta">
            <div class="meta-group">
                <span class="meta-label">Invoice Number</span>
                <span class="meta-value">{{ $invoiceNumber }}</span>
            </div>
            <div class="meta-group">
                <span class="meta-label">Order Number</span>
                <span class="meta-value">{{ $orderNumber }}</span>
            </div>
            <div class="meta-group">
                <span class="meta-label">Invoice Date</span>
                <span class="meta-value">{{ $invoiceDate }}</span>
            </div>
            <div class="meta-group" style="text-align: right;">
                <span class="meta-label">Payment Status</span>
                <span class="meta-value" style="color: var(--primary-color);">{{ $paymentStatus }}</span>
            </div>
        </div>

        <!-- Address Grid -->
        <div class="address-grid">
            <div class="address-block">
                <h4>Bill To</h4>
                <div class="address-text">
                    {!! $billingAddress !!}
                    @if($customerEmail && $customerEmail !== 'N/A')
                        <br>Email: {{ $customerEmail }}
                    @endif
                </div>
            </div>
            <div class="address-block">
                <h4>Ship To</h4>
                <div class="address-text">
                    {!! $shippingAddress !!}
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="items-table-container">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Product / Description</th>
                        <th style="width: 15%;">SKU / Stock #</th>
                        <th style="width: 10%; text-align: center;">Qty</th>
                        <th style="width: 12%; text-align: right;">Unit Price</th>
                        <th style="width: 13%; text-align: right;">Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $qty = $item['quantity'] ?? 1;
                            $price = $item['price_snapshot'] ?? 0.00;
                            $rowTotal = $price * $qty;
                        @endphp
                        <tr>
                            <td>
                                <div class="item-name">{{ $item['name'] }}</div>
                            </td>
                            <td>
                                <span class="item-sku">{{ $item['sku'] ?? $item['stock_no'] ?? 'N/A' }}</span>
                            </td>
                            <td style="text-align: center;">{{ $qty }}</td>
                            <td class="numeric">${{ number_format($price, 2) }}</td>
                            <td class="numeric" style="font-weight: 700;">${{ number_format($rowTotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); font-style: italic;">No line items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Summary & Totals -->
        <div class="summary-container">
            <div class="summary-box">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>${{ number_format($subtotal, 2) }}</span>
                </div>
                @if($discount > 0)
                    <div class="summary-row discount">
                        <span>Discount</span>
                        <span>-${{ number_format($discount, 2) }}</span>
                    </div>
                @endif
                @if($shipping > 0)
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>${{ number_format($shipping, 2) }}</span>
                    </div>
                @endif
                @if($tax > 0)
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>${{ number_format($tax, 2) }}</span>
                    </div>
                @endif
                <div class="summary-row total">
                    <span>Grand Total</span>
                    <span>${{ number_format($grandTotal, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Company Contact Details & Footer -->
        <div class="company-info-block">
            <div class="company-contact">
                <strong>{{ $companyName }}</strong>
                <span>Email: {{ $companyEmail }}</span>
                <span>Phone: {{ $companyPhone }}</span>
                <span>Website: <a href="https://{{ $companyWebsite }}" target="_blank" style="color: var(--text-muted); text-decoration: none;">{{ $companyWebsite }}</a></span>
            </div>
            <div class="company-address-text">
                {{ $companyAddress }}
            </div>
        </div>

        <div class="invoice-footer-note">
            Thank you for your business with OM Gems! If you have any questions, please contact us.
        </div>
    </div>

</body>
</html>
