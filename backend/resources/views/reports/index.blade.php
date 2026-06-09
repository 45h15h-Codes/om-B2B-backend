@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-file-invoice"></i> Reports Center
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Compile and export detailed logs, product listings, store performance, and sales data summaries.</p>
        </div>
    </div>

    <!-- Reports Filter Toolbar -->
    <div style="padding: 20px 30px; border-bottom: 1px solid var(--border-color); background: #fafbfc;">
        <form id="reportFilterForm" method="GET" action="{{ route('reports.index') }}" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            <!-- Start Date -->
            <div style="width: 160px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Start Date</label>
                <input type="date" id="report_start_date" name="start_date" value="{{ request('start_date') }}" style="width: 100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; box-sizing: border-box;">
            </div>

            <!-- End Date -->
            <div style="width: 160px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">End Date</label>
                <input type="date" id="report_end_date" name="end_date" value="{{ request('end_date') }}" style="width: 100%; padding: 7px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; box-sizing: border-box;">
            </div>

            <!-- Store selector -->
            <div style="width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Shopify Store</label>
                <select id="report_store_id" name="shopify_store_id" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: white; font-family: inherit; outline: none;">
                    <option value="">All Stores</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ request('shopify_store_id') == $st->id ? 'selected' : '' }}>{{ $st->store_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Buttons -->
            <div style="display: flex; gap: 8px;">
                <button type="submit" style="padding: 8px 16px; height: 37px; background: var(--primary-color); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-filter"></i> Apply Filters
                </button>
                <a href="{{ route('reports.index') }}" style="padding: 8px 16px; height: 37px; background: white; color: var(--text-color); border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-sizing: border-box;">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Export Report Downloads Section -->
    <div style="padding: 30px; background: #fafbfc; border-bottom: 1px solid var(--border-color); display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">
        
        <!-- Sales CSV -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h4 style="font-weight: 700; font-size: 14px; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-file-csv" style="color: var(--success-color); font-size: 16px;"></i> Sales Activity Report
                </h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4; margin-bottom: 15px;">Exports a detailed ledger of all Shopify sales orders and direct invoices, including totals and timestamps.</p>
            </div>
            <button onclick="downloadReport('sales')" class="btn btn-primary" style="width: 100%; justify-content: center; height: 36px;">
                <i class="fa-solid fa-download"></i> Export Sales CSV
            </button>
        </div>

        <!-- Inventory CSV -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h4 style="font-weight: 700; font-size: 14px; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-file-csv" style="color: var(--primary-color); font-size: 16px;"></i> Inventory Listings Report
                </h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4; margin-bottom: 15px;">Exports a complete snapshot of all single diamonds and jewelry items currently logged in the inventory registry.</p>
            </div>
            <button onclick="downloadReport('inventory')" class="btn btn-primary" style="width: 100%; justify-content: center; height: 36px; background-color: var(--primary-color); border-color: var(--primary-color);">
                <i class="fa-solid fa-download"></i> Export Inventory CSV
            </button>
        </div>

        <!-- Store Comparison CSV -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h4 style="font-weight: 700; font-size: 14px; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-file-csv" style="color: #805ad5; font-size: 16px;"></i> Store Comparison Report
                </h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4; margin-bottom: 15px;">Exports performance records (revenue, orders count) across all active and configured Shopify storefront endpoints.</p>
            </div>
            <button onclick="downloadReport('stores')" class="btn btn-primary" style="width: 100%; justify-content: center; height: 36px; background-color: #805ad5; border-color: #805ad5;">
                <i class="fa-solid fa-download"></i> Export Stores CSV
            </button>
        </div>

    </div>

    <!-- Metrics Layout Section -->
    <div style="padding: 30px; background: white; display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; flex-wrap: wrap;">
        
        <!-- Connected Storefronts Summary Table -->
        <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; height: fit-content;">
            <div style="background: #fafbfc; border-bottom: 1px solid var(--border-color); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--text-color);">
                <i class="fa-solid fa-store" style="margin-right: 6px; color: var(--primary-color);"></i> Shopify Storefront Performance
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                            <th style="padding: 10px 15px;">Store Name</th>
                            <th style="padding: 10px 15px;">Status</th>
                            <th style="padding: 10px 15px;">Orders</th>
                            <th style="padding: 10px 15px; text-align: right;">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($storeReports as $rep)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 10px 15px; font-weight: 600; color: var(--text-color);">
                                    {{ $rep['store_name'] }}
                                    <div style="font-size: 10.5px; color: var(--text-muted); font-weight: normal;">{{ $rep['domain'] }}</div>
                                </td>
                                <td style="padding: 10px 15px;">
                                    <span style="font-size: 10.5px; font-weight: bold; padding: 2px 6px; border-radius: 12px; background: {{ $rep['is_active'] ? '#f0fff4' : '#f1f5f9' }}; color: {{ $rep['is_active'] ? 'var(--success-color)' : '#475569' }};">
                                        {{ $rep['is_active'] ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="padding: 10px 15px; font-weight: 600;">{{ $rep['orders_count'] }} orders</td>
                                <td style="padding: 10px 15px; text-align: right; font-weight: 700; color: var(--text-color);">
                                    ${{ number_format($rep['revenue'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inventory Status breakdown summary -->
        <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; height: fit-content;">
            <div style="background: #fafbfc; border-bottom: 1px solid var(--border-color); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--text-color);">
                <i class="fa-solid fa-cubes" style="margin-right: 6px; color: var(--primary-color);"></i> Inventory Status Breakdown
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                            <th style="padding: 10px 15px;">Category</th>
                            <th style="padding: 10px 15px;">Available</th>
                            <th style="padding: 10px 15px;">On Hold</th>
                            <th style="padding: 10px 15px;">Sold</th>
                            <th style="padding: 10px 15px; text-align: right;">Total Registry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Diamonds Row -->
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 15px; font-weight: 700; color: var(--text-color);">
                                <i class="fa-solid fa-gem" style="color: var(--primary-color); margin-right: 6px;"></i> Diamonds
                            </td>
                            <td style="padding: 12px 15px; font-weight: 600; color: var(--success-color);">{{ $inventoryStats['diamonds']['available'] }}</td>
                            <td style="padding: 12px 15px; font-weight: 600; color: var(--warning-color);">{{ $inventoryStats['diamonds']['on_hold'] }}</td>
                            <td style="padding: 12px 15px; font-weight: 600; color: var(--error-color);">{{ $inventoryStats['diamonds']['sold'] }}</td>
                            <td style="padding: 12px 15px; text-align: right; font-weight: bold; color: var(--text-color);">{{ $inventoryStats['diamonds']['total'] }}</td>
                        </tr>
                        <!-- Jewelry Row -->
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 15px; font-weight: 700; color: var(--text-color);">
                                <i class="fa-solid fa-ring" style="color: #e53e3e; margin-right: 6px;"></i> Jewelry
                            </td>
                            <td style="padding: 12px 15px; font-weight: 600; color: var(--success-color);">{{ $inventoryStats['jewelry']['available'] }}</td>
                            <td style="padding: 12px 15px; font-weight: 600; color: var(--warning-color);">{{ $inventoryStats['jewelry']['on_hold'] }}</td>
                            <td style="padding: 12px 15px; font-weight: 600; color: var(--error-color);">{{ $inventoryStats['jewelry']['sold'] }}</td>
                            <td style="padding: 12px 15px; text-align: right; font-weight: bold; color: var(--text-color);">{{ $inventoryStats['jewelry']['total'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Shopify Ingested Sales list (10) -->
        <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; grid-column: span 2;">
            <div style="background: #fafbfc; border-bottom: 1px solid var(--border-color); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--text-color);">
                <i class="fa-solid fa-list-check" style="margin-right: 6px; color: var(--primary-color);"></i> Recent Storefront Ingested Sales (Latest 10)
            </div>
            <div style="overflow-x: auto;">
                @if($recentShopifySales->count() > 0)
                    <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); font-weight: bold; color: var(--text-muted);">
                                <th style="padding: 10px 15px;">Order Number</th>
                                <th style="padding: 10px 15px;">Store</th>
                                <th style="padding: 10px 15px;">Customer Name</th>
                                <th style="padding: 10px 15px;">Fulfillment</th>
                                <th style="padding: 10px 15px;">Financial Status</th>
                                <th style="padding: 10px 15px;">Order Date</th>
                                <th style="padding: 10px 15px; text-align: right;">Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentShopifySales as $sal)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 10px 15px; font-weight: 700; color: var(--text-color);">#{{ $sal->order_number }}</td>
                                    <td style="padding: 10px 15px;">{{ $sal->shopifyStore ? $sal->shopifyStore->store_name : 'Default Store' }}</td>
                                    <td style="padding: 10px 15px; font-weight: 600;">{{ $sal->customer_name ?: 'Guest Customer' }}</td>
                                    <td style="padding: 10px 15px; text-transform: capitalize;">{{ $sal->fulfillment_status ?: 'Unfulfilled' }}</td>
                                    <td style="padding: 10px 15px;">
                                        <span style="font-size: 10.5px; font-weight: bold; padding: 2px 6px; border-radius: 12px; background: {{ $sal->financial_status === 'paid' ? '#f0fff4' : '#fffaf0' }}; color: {{ $sal->financial_status === 'paid' ? 'var(--success-color)' : 'var(--warning-color)' }};">
                                            {{ ucfirst($sal->financial_status) }}
                                        </span>
                                    </td>
                                    <td style="padding: 10px 15px; color: var(--text-muted);">{{ $sal->created_at->format('Y-m-d H:i') }}</td>
                                    <td style="padding: 10px 15px; text-align: right; font-weight: 700; color: var(--text-color);">${{ number_format($sal->total_price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="padding: 30px; text-align: center; color: var(--text-muted);">
                        No recent storefront sales ingested.
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection

@section('scripts')
<script>
    function downloadReport(type) {
        const startDate = document.getElementById('report_start_date').value;
        const endDate = document.getElementById('report_end_date').value;
        const storeId = document.getElementById('report_store_id').value;

        // Build URL parameters
        let url = new URL('{{ route("reports.export") }}', window.location.origin);
        url.searchParams.set('type', type);
        if (startDate) url.searchParams.set('start_date', startDate);
        if (endDate) url.searchParams.set('end_date', endDate);
        if (storeId) url.searchParams.set('shopify_store_id', storeId);

        // Redirect browser to trigger file streaming download
        window.location.href = url.toString();
        showToast(`Exporting ${type} report...`, 'success');
    }
</script>
@endsection
