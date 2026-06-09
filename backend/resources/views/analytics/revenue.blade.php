@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-chart-line"></i> Revenue & Sales Analytics
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Monitor storefront revenue distributions, direct invoicing performance, store sales rankings, and sold product volume.</p>
        </div>
        <div>
            <button onclick="window.location.reload()" style="padding: 8px 16px; height: 37px; background: white; color: var(--text-color); border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- Analytics Dashboard Overview Cards Grid -->
    <div style="padding: 30px; background: #fafbfc; border-bottom: 1px solid var(--border-color); display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
        
        <!-- Total Revenue Card -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Grand Total Sales</span>
                <span style="color: var(--primary-color); font-size: 18px;"><i class="fa-solid fa-wallet"></i></span>
            </div>
            <h2 style="font-size: 24px; font-weight: 700; color: var(--text-color);">${{ number_format($grandTotal, 2) }}</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px; display: flex; justify-content: space-between;">
                <span>Orders: <strong>{{ $totalCount }}</strong></span>
                <span>AOV: <strong>${{ $totalCount > 0 ? number_format($grandTotal / $totalCount, 2) : '0.00' }}</strong></span>
            </div>
        </div>

        <!-- Today Revenue Card -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Today's Revenue</span>
                <span style="color: var(--success-color); font-size: 18px;"><i class="fa-solid fa-calendar-day"></i></span>
            </div>
            <h2 style="font-size: 24px; font-weight: 700; color: var(--text-color);">${{ number_format($totalToday, 2) }}</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px; display: flex; justify-content: space-between;">
                <span>Shopify: <strong>${{ number_format($shopifyToday, 2) }}</strong></span>
                <span>Invoices: <strong>${{ number_format($invoiceToday, 2) }}</strong></span>
            </div>
        </div>

        <!-- Monthly Revenue Card -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">This Month's Revenue</span>
                <span style="color: var(--warning-color); font-size: 18px;"><i class="fa-solid fa-calendar-days"></i></span>
            </div>
            <h2 style="font-size: 24px; font-weight: 700; color: var(--text-color);">${{ number_format($totalMonth, 2) }}</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px; display: flex; justify-content: space-between;">
                <span>Shopify: <strong>${{ number_format($shopifyMonth, 2) }}</strong></span>
                <span>Invoices: <strong>${{ number_format($invoiceMonth, 2) }}</strong></span>
            </div>
        </div>

        <!-- Yearly Revenue Card -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">This Year's Revenue</span>
                <span style="color: #805ad5; font-size: 18px;"><i class="fa-solid fa-chart-line"></i></span>
            </div>
            <h2 style="font-size: 24px; font-weight: 700; color: var(--text-color);">${{ number_format($totalYear, 2) }}</h2>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px; display: flex; justify-content: space-between;">
                <span>Shopify: <strong>${{ number_format($shopifyYear, 2) }}</strong></span>
                <span>Invoices: <strong>${{ number_format($invoiceYear, 2) }}</strong></span>
            </div>
        </div>

    </div>

    <!-- Revenue Streams Comparison Details -->
    <div style="padding: 30px; border-bottom: 1px solid var(--border-color); background: white; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        
        <!-- Shopify Sales Stats -->
        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; border-left: 4px solid #96bf48;">
            <h3 style="font-size: 15px; font-weight: 700; color: var(--text-color); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-brands fa-shopify" style="color: #96bf48; font-size: 18px;"></i> Shopify Storefront Streams
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Total Ingested Revenue</span>
                    <h4 style="font-size: 18px; font-weight: 700; color: var(--text-color); margin-top: 4px;">${{ number_format($shopifyTotal, 2) }}</h4>
                </div>
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Sync Order Count</span>
                    <h4 style="font-size: 18px; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $shopifyCount }} orders</h4>
                </div>
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Shopify AOV</span>
                    <h4 style="font-size: 18px; font-weight: 700; color: var(--text-color); margin-top: 4px;">
                        ${{ $shopifyCount > 0 ? number_format($shopifyTotal / $shopifyCount, 2) : '0.00' }}
                    </h4>
                </div>
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Current Month Share</span>
                    <h4 style="font-size: 18px; font-weight: 700; color: var(--text-color); margin-top: 4px;">
                        {{ $totalMonth > 0 ? round(($shopifyMonth / $totalMonth) * 100, 1) : 0 }}%
                    </h4>
                </div>
            </div>
        </div>

        <!-- Direct Invoicing Sales Stats -->
        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; border-left: 4px solid var(--primary-color);">
            <h3 style="font-size: 15px; font-weight: 700; color: var(--text-color); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary-color); font-size: 18px;"></i> Direct Invoices (Approved)
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Direct Invoice Revenue</span>
                    <h4 style="font-size: 18px; font-weight: 700; color: var(--text-color); margin-top: 4px;">${{ number_format($invoiceTotal, 2) }}</h4>
                </div>
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Approved Order Count</span>
                    <h4 style="font-size: 18px; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $invoiceCount }} orders</h4>
                </div>
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Invoice AOV</span>
                    <h4 style="font-size: 18px; font-weight: 700; color: var(--text-color); margin-top: 4px;">
                        ${{ $invoiceCount > 0 ? number_format($invoiceTotal / $invoiceCount, 2) : '0.00' }}
                    </h4>
                </div>
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Current Month Share</span>
                    <h4 style="font-size: 18px; font-weight: 700; color: var(--text-color); margin-top: 4px;">
                        {{ $totalMonth > 0 ? round(($invoiceMonth / $totalMonth) * 100, 1) : 0 }}%
                    </h4>
                </div>
            </div>
        </div>

    </div>

    <!-- Charts Layout Section -->
    <div style="padding: 30px; background: white; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; flex-wrap: wrap;">
        
        <!-- Revenue Trend Chart -->
        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px;">
            <h4 style="font-size: 13.5px; font-weight: 700; color: var(--text-color); margin-bottom: 20px;">
                <i class="fa-solid fa-chart-line" style="color: var(--primary-color); margin-right: 6px;"></i> Revenue Trend (Last 6 Months)
            </h4>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- Sales by Store Chart -->
        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px;">
            <h4 style="font-size: 13.5px; font-weight: 700; color: var(--text-color); margin-bottom: 20px;">
                <i class="fa-solid fa-store" style="color: #96bf48; margin-right: 6px;"></i> Storefront Ingested Sales Comparison
            </h4>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="storeSalesChart"></canvas>
            </div>
        </div>

        <!-- Sold Product Type Breakdown -->
        <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; grid-column: span 2;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; align-items: center;">
                <div>
                    <h4 style="font-size: 13.5px; font-weight: 700; color: var(--text-color); margin-bottom: 10px;">
                        <i class="fa-solid fa-gem" style="color: var(--primary-color); margin-right: 6px;"></i> Product Type Sales Volume Breakdown
                    </h4>
                    <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 20px;">Visualizing inventory unit sales volumes of Diamonds versus Jewelry products across all synchronized Shopify orders.</p>
                    
                    <div style="display: flex; gap: 30px; margin-top: 10px;">
                        <div style="border-left: 3px solid var(--primary-color); padding-left: 12px;">
                            <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Diamonds Sold</span>
                            <h4 style="font-size: 20px; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $soldDiamonds }} units</h4>
                        </div>
                        <div style="border-left: 3px solid #e53e3e; padding-left: 12px;">
                            <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">Jewelry Sold</span>
                            <h4 style="font-size: 20px; font-weight: 700; color: var(--text-color); margin-top: 4px;">{{ $soldJewelry }} units</h4>
                        </div>
                    </div>
                </div>
                
                <div style="position: relative; height: 200px; width: 100%; display: flex; justify-content: center;">
                    <canvas id="productSalesBreakdownChart" style="max-height: 200px; max-width: 200px;"></canvas>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@section('scripts')
<!-- Load Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Monthly Revenue Trend Chart (Line Chart)
    const monthlyLabels = {!! json_encode($monthlyLabels) !!};
    const monthlyShopifyData = {!! json_encode($monthlyShopifyData) !!};
    const monthlyInvoiceData = {!! json_encode($monthlyInvoiceData) !!};

    const ctxTrend = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: 'Shopify Storefronts',
                    data: monthlyShopifyData,
                    borderColor: '#96bf48',
                    backgroundColor: 'rgba(150, 191, 72, 0.05)',
                    tension: 0.3,
                    fill: true,
                    borderWidth: 2
                },
                {
                    label: 'Direct Invoices',
                    data: monthlyInvoiceData,
                    borderColor: '#108bb6',
                    backgroundColor: 'rgba(16, 139, 182, 0.05)',
                    tension: 0.3,
                    fill: true,
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // 2. Sales by Store Chart (Bar Chart)
    const storeNames = {!! json_encode($storeNames) !!};
    const storeSales = {!! json_encode($storeSales) !!};

    const ctxStore = document.getElementById('storeSalesChart').getContext('2d');
    new Chart(ctxStore, {
        type: 'bar',
        data: {
            labels: storeNames.length > 0 ? storeNames : ['No Stores Connected'],
            datasets: [{
                label: 'Ingested Shopify Revenue',
                data: storeSales.length > 0 ? storeSales : [0],
                backgroundColor: 'rgba(16, 139, 182, 0.75)',
                borderColor: '#108bb6',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // 3. Product Sales Breakdown Chart (Pie/Doughnut Chart)
    const soldDiamonds = {{ $soldDiamonds }};
    const soldJewelry = {{ $soldJewelry }};

    const ctxProduct = document.getElementById('productSalesBreakdownChart').getContext('2d');
    new Chart(ctxProduct, {
        type: 'doughnut',
        data: {
            labels: ['Diamonds', 'Jewelry'],
            datasets: [{
                data: [soldDiamonds, soldJewelry],
                backgroundColor: ['#108bb6', '#e53e3e'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15
                    }
                }
            },
            cutout: '65%'
        }
    });
</script>
@endsection
