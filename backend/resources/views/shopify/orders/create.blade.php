@extends('layouts.app')

@section('styles')
<style>
    .create-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .header-section {
        margin-bottom: 24px;
    }

    .header-section h2 {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 4px;
    }

    .header-section p {
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

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-color);
        background-color: #ffffff;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px var(--primary-light);
    }

    /* Items Picker Grid */
    .tabs {
        display: flex;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 16px;
    }

    .tab-btn {
        background: none;
        border: none;
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 700;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
    }

    .tab-btn.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    .picker-list {
        max-height: 280px;
        overflow-y: auto;
        border: 1px solid var(--border-color);
        border-radius: 8px;
    }

    .picker-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s ease;
    }

    .picker-item:last-child {
        border-bottom: none;
    }

    .picker-item:hover {
        background-color: #f8fafc;
    }

    .picker-info h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 2px;
    }

    .picker-info p {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 500;
    }

    .btn-add-item {
        background: none;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-add-item:hover {
        background-color: var(--primary-color);
        color: #ffffff;
    }

    /* Selected Items Table */
    .selected-items-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .selected-items-table th {
        padding: 10px 14px;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        border-bottom: 1px solid var(--border-color);
    }

    .selected-items-table td {
        padding: 12px 14px;
        font-size: 14px;
        border-bottom: 1px solid var(--border-color);
        font-weight: 500;
        vertical-align: middle;
    }

    .summary-card {
        background-color: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 20px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 14px;
        font-weight: 500;
    }

    .summary-row.total {
        border-top: 1px solid var(--border-color);
        padding-top: 12px;
        margin-top: 12px;
        font-size: 18px;
        font-weight: 700;
        color: var(--primary-color);
    }

    .btn-remove {
        color: var(--error-color);
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-remove:hover {
        color: #b91c1c;
    }

    .search-input {
        margin-bottom: 12px;
    }
</style>
@endsection

@section('content')
<div class="create-container">
    <div class="header-section">
        <h2>Create Order</h2>
        <p>Create a local order with item snapshots to prepare for Shopify synchronization.</p>
    </div>

    <form action="{{ route('orders.store') }}" method="POST" id="orderForm">
        @csrf
        
        <div class="grid">
            <!-- Left Side: Items & Order Details -->
            <div>
                <!-- Items Picker -->
                <div class="card">
                    <div class="card-header">
                        <h3>1. Add Diamonds & Jewelry</h3>
                    </div>
                    <div class="card-body">
                        <div class="tabs">
                            <button type="button" class="tab-btn active" onclick="switchTab('diamonds')">Diamonds</button>
                            <button type="button" class="tab-btn" onclick="switchTab('jewelry')">Jewelery</button>
                        </div>

                        <!-- Diamonds Picker -->
                        <div id="diamondsTab">
                            <input type="text" class="form-control search-input" placeholder="Search diamonds by Stock No, Shape, Clarity..." onkeyup="filterItems('diamonds', this.value)">
                            <div class="picker-list">
                                @forelse($diamonds as $diamond)
                                    <div class="picker-item diamond-search-item" data-search="{{ strtolower($diamond->stock_no . ' ' . $diamond->shape . ' ' . $diamond->color . ' ' . $diamond->clarity) }}">
                                        <div class="picker-info">
                                            <h4>{{ $diamond->stock_no }} - {{ $diamond->shape }} {{ $diamond->size }}ct</h4>
                                            <p>Color: {{ $diamond->color }} | Clarity: {{ $diamond->clarity }} | Price: ${{ number_format($diamond->asking_price ?: $diamond->cash_price, 2) }}</p>
                                        </div>
                                        <button type="button" class="btn-add-item" onclick="addItem('diamond', {{ $diamond->id }}, '{{ $diamond->stock_no }} - {{ $diamond->shape }} ({{ $diamond->size }}ct)', {{ $diamond->asking_price ?: $diamond->cash_price }})">
                                            Add to Order
                                        </button>
                                    </div>
                                @empty
                                    <div style="padding: 20px; text-align: center; color: var(--text-muted);">No approved diamonds available.</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Jewelry Picker -->
                        <div id="jewelryTab" style="display: none;">
                            <input type="text" class="form-control search-input" placeholder="Search jewelry by SKU, Name..." onkeyup="filterItems('jewelry', this.value)">
                            <div class="picker-list">
                                @forelse($jewelry as $item)
                                    <div class="picker-item jewelry-search-item" data-search="{{ strtolower($item->sku . ' ' . $item->name) }}">
                                        <div class="picker-info">
                                            <h4>{{ $item->sku }} - {{ $item->name }}</h4>
                                            <p>Type: {{ $item->type }} | Price: ${{ number_format($item->price, 2) }}</p>
                                        </div>
                                        <button type="button" class="btn-add-item" onclick="addItem('jewelry', {{ $item->id }}, '{{ $item->sku }} - {{ $item->name }}', {{ $item->price }})">
                                            Add to Order
                                        </button>
                                    </div>
                                @empty
                                    <div style="padding: 20px; text-align: center; color: var(--text-muted);">No approved jewelry items available.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Items List -->
                <div class="card">
                    <div class="card-header">
                        <h3>2. Selected Items Snapshot</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="selected-items-table">
                            <thead>
                                <tr>
                                    <th>Item Details</th>
                                    <th style="width: 100px;">Qty</th>
                                    <th style="width: 150px;">Price Override ($)</th>
                                    <th style="width: 150px;">Total</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="selectedItemsContainer">
                                <tr id="noItemsPlaceholder">
                                    <td colspan="5" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                        No items selected yet. Use the picker above to add items.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Store Connection & Totals Summary -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>3. Order Settings</h3>
                    </div>
                    <div class="card-body">
                        <!-- Shopify Store -->
                        <div class="form-group">
                            <label for="shopify_store_id">Shopify Store Context</label>
                            <select name="shopify_store_id" id="shopify_store_id" class="form-control" required>
                                <option value="" disabled selected>Select active store...</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">
                                        {{ $store->store_name ?: 'Store' }} ({{ $store->shop_domain }})
                                    </option>
                                @endforeach
                            </select>
                            <small style="display: block; margin-top: 4px; color: var(--text-muted); font-size: 11px;">
                                Sync runs in Normal Admin credentials context.
                            </small>
                        </div>

                        <!-- Customer Name -->
                        <div class="form-group">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="John Doe">
                        </div>

                        <!-- Customer Email -->
                        <div class="form-group">
                            <label for="email">Customer Email</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="customer@example.com">
                        </div>

                        <!-- Customer Phone -->
                        <div class="form-group">
                            <label for="customer_phone">Customer Phone</label>
                            <input type="text" name="customer_phone" id="customer_phone" class="form-control" placeholder="+1 234 567 890">
                        </div>

                        <!-- Discount -->
                        <div class="form-group">
                            <label for="discount_input">Order Discount ($)</label>
                            <input type="number" name="discount" id="discount_input" class="form-control" value="0.00" step="0.01" min="0" oninput="calculateTotals()">
                        </div>

                        <!-- Order Totals Summary -->
                        <div class="summary-card">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span id="summarySubtotal">$0.00</span>
                            </div>
                            <div class="summary-row">
                                <span>Discount:</span>
                                <span id="summaryDiscount" style="color: var(--error-color); font-weight: 600;">-$0.00</span>
                            </div>
                            <div class="summary-row total">
                                <span>Estimated Total:</span>
                                <span id="summaryTotal">$0.00</span>
                            </div>
                        </div>

                        <div style="margin-top: 24px;">
                            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 42px;">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Save Order
                            </button>
                            <a href="{{ route('orders.index') }}" class="btn btn-secondary" style="width: 100%; justify-content: center; margin-top: 10px; height: 42px;">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    let itemCount = 0;

    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        if (tab === 'diamonds') {
            document.getElementById('diamondsTab').style.display = 'block';
            document.getElementById('jewelryTab').style.display = 'none';
            event.target.classList.add('active');
        } else {
            document.getElementById('diamondsTab').style.display = 'none';
            document.getElementById('jewelryTab').style.display = 'block';
            event.target.classList.add('active');
        }
    }

    function filterItems(type, query) {
        const items = document.querySelectorAll('.' + type + '-search-item');
        query = query.toLowerCase().trim();
        items.forEach(item => {
            const searchText = item.getAttribute('data-search');
            if (searchText.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function addItem(type, id, name, price) {
        // Remove empty state placeholder
        const placeholder = document.getElementById('noItemsPlaceholder');
        if (placeholder) {
            placeholder.style.display = 'none';
        }

        // Prevent duplicate items
        const existingInput = document.querySelector(`input[name^="items"][value="${id}"]`);
        // Let's check both ID and Type to be absolutely sure
        let isDuplicate = false;
        document.querySelectorAll('#selectedItemsContainer tr').forEach(row => {
            const typeInput = row.querySelector('input[name*="[product_type]"]');
            const idInput = row.querySelector('input[name*="[product_id]"]');
            if (typeInput && idInput && typeInput.value === type && parseInt(idInput.value) === id) {
                isDuplicate = true;
            }
        });

        if (isDuplicate) {
            showToast('This item is already added to the order!', 'error');
            return;
        }

        const container = document.getElementById('selectedItemsContainer');
        const rowId = `itemRow_${itemCount}`;

        const rowHtml = `
            <tr id="${rowId}">
                <td>
                    <div style="font-weight: 700;">${name}</div>
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Type: ${type}</div>
                    <input type="hidden" name="items[${itemCount}][product_type]" value="${type}">
                    <input type="hidden" name="items[${itemCount}][product_id]" value="${id}">
                </td>
                <td>
                    <input type="number" name="items[${itemCount}][quantity]" class="form-control item-qty" value="1" min="1" oninput="calculateTotals()" style="padding: 6px 10px;">
                </td>
                <td>
                    <input type="number" name="items[${itemCount}][price_snapshot]" class="form-control item-price" value="${price.toFixed(2)}" step="0.01" min="0" oninput="calculateTotals()" style="padding: 6px 10px;">
                </td>
                <td class="item-row-total" style="font-weight: 700; color: var(--primary-color);">
                    $${price.toFixed(2)}
                </td>
                <td>
                    <button type="button" class="btn-remove" onclick="removeItem('${rowId}')">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            </tr>
        `;

        container.insertAdjacentHTML('beforeend', rowHtml);
        itemCount++;
        calculateTotals();
        showToast('Item added to order.');
    }

    function removeItem(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
        }

        const container = document.getElementById('selectedItemsContainer');
        const rows = container.querySelectorAll('tr:not(#noItemsPlaceholder)');
        if (rows.length === 0) {
            const placeholder = document.getElementById('noItemsPlaceholder');
            if (placeholder) {
                placeholder.style.display = 'table-row';
            }
        }

        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0.00;

        const rows = document.querySelectorAll('#selectedItemsContainer tr:not(#noItemsPlaceholder)');
        rows.forEach(row => {
            const qty = parseInt(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0.00;
            const rowTotal = qty * price;
            
            row.querySelector('.item-row-total').textContent = `$${rowTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            subtotal += rowTotal;
        });

        const discountInput = parseFloat(document.getElementById('discount_input').value) || 0.00;
        const total = Math.max(0.00, subtotal - discountInput);

        document.getElementById('summarySubtotal').textContent = `$${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('summaryDiscount').textContent = `-$${discountInput.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('summaryTotal').textContent = `$${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }

    document.getElementById('orderForm').addEventListener('submit', function(e) {
        const rows = document.querySelectorAll('#selectedItemsContainer tr:not(#noItemsPlaceholder)');
        if (rows.length === 0) {
            e.preventDefault();
            showToast('Please add at least one item to the order.', 'error');
        }
    });
</script>
@endsection
