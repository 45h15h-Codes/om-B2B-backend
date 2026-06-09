@extends('layouts.app')

@section('styles')
<style>
    /* Welcome Header */
    .welcome-header {
        font-size: 24px;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 30px;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-box {
        border-radius: 6px;
        padding: 24px 20px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .stat-box.diamonds { background: linear-gradient(135deg, #0b759d 0%, #1594be 100%); }
    .stat-box.jewelry { background: linear-gradient(135deg, #0b609d 0%, #1579be 100%); }
    .stat-box.buy-trades { background: linear-gradient(135deg, #e52968 0%, #f05286 100%); }
    .stat-box.sell-trades { background: linear-gradient(135deg, #f29c5e 0%, #f7b785 100%); }
    .stat-box.messages { background: linear-gradient(135deg, #1abc9c 0%, #2ecc71 100%); }

    .stat-number {
        font-size: 32px;
        font-weight: 700;
        line-height: 1.2;
    }

    .stat-text {
        font-size: 12px;
        font-weight: 500;
        margin-top: 4px;
        text-transform: capitalize;
        opacity: 0.9;
    }

    .stat-icon-bg {
        position: absolute;
        right: -10px;
        bottom: -15px;
        font-size: 60px;
        opacity: 0.15;
        transform: rotate(-10deg);
    }

    /* Categories Box */
    .categories-box {
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 30px;
        max-width: 600px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }

    .categories-title {
        font-size: 16px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 24px;
    }

    .categories-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .category-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .category-img-box {
        width: 100px;
        height: 100px;
        border-radius: 8px;
        overflow: hidden;
        background-color: #000000;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .category-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.9;
        transition: transform 0.3s ease;
    }

    .category-card:hover .category-img {
        transform: scale(1.05);
    }

    .category-name {
        font-size: 13px;
        font-weight: 700;
        color: #2d3748;
    }

    .category-count {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 2px;
    }
</style>
@endsection

@section('content')

<h2 class="welcome-header">Welcome, OM Gems</h2>

<!-- Dashboard Stats Grid -->
<div class="stats-grid">
    <div class="stat-box diamonds">
        <span class="stat-number">{{ $stats['diamonds_count'] }}</span>
        <span class="stat-text">Diamonds Listed</span>
        <i class="fa-solid fa-gem stat-icon-bg"></i>
    </div>
    
    <div class="stat-box jewelry">
        <span class="stat-number">{{ $stats['jewelry_count'] }}</span>
        <span class="stat-text">Jewelry Listed</span>
        <i class="fa-solid fa-ring stat-icon-bg"></i>
    </div>
    
    <div class="stat-box buy-trades">
        <span class="stat-number">{{ $stats['active_buy_trades'] }}</span>
        <span class="stat-text">Active Buy Trades</span>
        <i class="fa-solid fa-handshake stat-icon-bg"></i>
    </div>
    
    <div class="stat-box sell-trades">
        <span class="stat-number">{{ $stats['active_sell_trades'] }}</span>
        <span class="stat-text">Active Sell Trades</span>
        <i class="fa-solid fa-tag stat-icon-bg"></i>
    </div>
    
    <div class="stat-box messages">
        <span class="stat-number">{{ $stats['unread_messages'] }}</span>
        <span class="stat-text">Unread Messages</span>
        <i class="fa-solid fa-envelope stat-icon-bg"></i>
    </div>
</div>

<!-- Jewelry Categories Listing (Exactly matches Image Layout) -->
<div style="display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 40px;">
    <div class="categories-box" style="flex: 1; min-width: 300px; margin-bottom: 0;">
        <h3 class="categories-title">Jewelry Categories</h3>
        
        <div class="categories-grid">
            @foreach($categories as $key => $cat)
                <div class="category-card" style="{{ in_array($key, ['necklaces', 'watches']) ? 'margin-top: 10px;' : '' }}">
                    <div class="category-img-box">
                        <img src="{{ $cat['image'] }}" class="category-img" alt="{{ $cat['name'] }}">
                    </div>
                    <span class="category-name">{{ $cat['name'] }}</span>
                    <span class="category-count">{{ $cat['count'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Charts widgets -->
    <div style="flex: 1.5; min-width: 400px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="background-color: #ffffff; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 15px; color: #2d3748;">Inventory Status</h3>
            <div style="height: 180px; display: flex; justify-content: center; align-items: center;">
                <canvas id="inventoryStatusChart"></canvas>
            </div>
        </div>
        <div style="background-color: #ffffff; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
            <h3 style="font-size: 14px; font-weight: 700; margin-bottom: 15px; color: #2d3748;">Volume by Type</h3>
            <div style="height: 180px; display: flex; justify-content: center; align-items: center;">
                <canvas id="inventoryTypeChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Doughnut Chart for Status
        const ctxStatus = document.getElementById('inventoryStatusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'On Hold', 'Sold'],
                datasets: [{
                    data: [
                        {{ $stats['diamonds_count'] + $stats['jewelry_count'] }},
                        2, 
                        1
                    ],
                    backgroundColor: ['#108bb6', '#f59e0b', '#ef4444'],
                    borderWidth: 1
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
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });

        // Bar Chart for Type
        const ctxType = document.getElementById('inventoryTypeChart').getContext('2d');
        new Chart(ctxType, {
            type: 'bar',
            data: {
                labels: ['Diamonds', 'Jewelry'],
                datasets: [{
                    label: 'Items Count',
                    data: [{{ $stats['diamonds_count'] }}, {{ $stats['jewelry_count'] }}],
                    backgroundColor: ['#0b759d', '#0b609d'],
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
                            precision: 0,
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>
@endsection
