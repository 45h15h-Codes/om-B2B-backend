@extends('layouts.app')

@section('content')
<div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
    
    <!-- Header -->
    <div style="padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; background-color: var(--primary-light);">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-clock-rotate-left"></i> Inventory Timeline System
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Chronological tracking of state changes and Shopify audit logs for diamonds and jewelry products.</p>
        </div>
    </div>

    <!-- Filters Toolbar -->
    <div style="padding: 20px 30px; border-bottom: 1px solid var(--border-color); background: #fafbfc;">
        <form method="GET" action="{{ route('inventory.timeline') }}" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            
            <!-- Search field -->
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Search</label>
                <div style="position: relative;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Stock No, SKU, remarks..." style="width: 100%; padding: 8px 12px 8px 34px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; font-family: inherit; outline: none; box-sizing: border-box;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: var(--text-muted); font-size: 13px;"></i>
                </div>
            </div>

            <!-- Product Type filter -->
            <div style="width: 160px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Product Type</label>
                <select name="product_type" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: white; font-family: inherit; outline: none;">
                    <option value="">All Products</option>
                    <option value="diamond" {{ request('product_type') == 'diamond' ? 'selected' : '' }}>Diamonds</option>
                    <option value="jewelry" {{ request('product_type') == 'jewelry' ? 'selected' : '' }}>Jewelry</option>
                </select>
            </div>

            <!-- Action type filter -->
            <div style="width: 180px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Action</label>
                <select name="action" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; background: white; font-family: inherit; outline: none;">
                    <option value="">All Actions</option>
                    <option value="create" {{ request('action') == 'create' ? 'selected' : '' }}>Create</option>
                    <option value="update" {{ request('action') == 'update' ? 'selected' : '' }}>Update</option>
                    <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
                    <option value="hold" {{ request('action') == 'hold' ? 'selected' : '' }}>Hold (Reservation)</option>
                    <option value="release" {{ request('action') == 'release' ? 'selected' : '' }}>Release Hold</option>
                    <option value="sold" {{ request('action') == 'sold' ? 'selected' : '' }}>Sold</option>
                    <option value="publish" {{ request('action') == 'publish' ? 'selected' : '' }}>Shopify Publish</option>
                    <option value="unpublish" {{ request('action') == 'unpublish' ? 'selected' : '' }}>Shopify Unpublish</option>
                    <option value="sync" {{ request('action') == 'sync' ? 'selected' : '' }}>Shopify Sync</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 8px;">
                <button type="submit" style="padding: 8px 16px; height: 37px; background: var(--primary-color); color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s;">
                    <i class="fa-solid fa-filter"></i> Apply
                </button>
                <a href="{{ route('inventory.timeline') }}" style="padding: 8px 16px; height: 37px; background: white; color: var(--text-color); border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-sizing: border-box; transition: background 0.2s;">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Timeline Body -->
    <div style="padding: 30px; background: white; position: relative;">
        @if($timeline->count() > 0)
            <!-- Timeline Container with Left Border -->
            <div style="position: relative; padding-left: 35px; border-left: 2px solid var(--border-color); margin-left: 15px;">
                
                @foreach($timeline as $event)
                    @php
                        // Determine Event styling parameters
                        $badgeColor = 'var(--primary-color)';
                        $bgLight = 'var(--primary-light)';
                        $icon = 'fa-circle-dot';
                        $actionName = ucfirst($event->action);

                        switch(strtolower($event->action)) {
                            case 'create':
                                $badgeColor = '#3182ce';
                                $bgLight = '#ebf8ff';
                                $icon = 'fa-plus';
                                break;
                            case 'update':
                                $badgeColor = '#4a5568';
                                $bgLight = '#edf2f7';
                                $icon = 'fa-pen-to-square';
                                break;
                            case 'delete':
                                $badgeColor = 'var(--error-color)';
                                $bgLight = '#fff5f5';
                                $icon = 'fa-trash-can';
                                break;
                            case 'hold':
                                $badgeColor = 'var(--warning-color)';
                                $bgLight = '#fffaf0';
                                $icon = 'fa-hand';
                                break;
                            case 'release':
                                $badgeColor = '#805ad5';
                                $bgLight = '#faf5ff';
                                $icon = 'fa-hand-holding';
                                break;
                            case 'sold':
                                $badgeColor = 'var(--success-color)';
                                $bgLight = '#f0fff4';
                                $icon = 'fa-circle-check';
                                break;
                            case 'publish':
                                $badgeColor = '#319795';
                                $bgLight = '#e6fffa';
                                $icon = 'fa-cloud-arrow-up';
                                break;
                            case 'unpublish':
                                $badgeColor = '#dd6b20';
                                $bgLight = '#fffaf0';
                                $icon = 'fa-cloud-arrow-down';
                                break;
                            case 'sync':
                                $badgeColor = '#3182ce';
                                $bgLight = '#ebf8ff';
                                $icon = 'fa-arrows-rotate';
                                break;
                        }
                    @phpend
                    
                    <div class="timeline-event" style="position: relative; margin-bottom: 30px;">
                        
                        <!-- Timeline Point Anchor Dot -->
                        <div style="position: absolute; left: -47px; top: 4px; width: 22px; height: 22px; border-radius: 50%; background: {{ $bgLight }}; border: 2px solid {{ $badgeColor }}; display: flex; align-items: center; justify-content: center; z-index: 2; color: {{ $badgeColor }}; font-size: 10px; font-weight: bold;">
                            <i class="fa-solid {{ $icon }}"></i>
                        </div>

                        <!-- Timeline Panel -->
                        <div style="background: #fafbfc; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px 20px; transition: transform 0.2s, box-shadow 0.2s;" onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.05)'" onmouseleave="this.style.transform='none'; this.style.boxShadow='none'">
                            
                            <!-- Header Info -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="background: {{ $bgLight }}; color: {{ $badgeColor }}; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 12px; border: 1px solid {{ $badgeColor }}22; text-transform: uppercase;">
                                        {{ $actionName }}
                                    </span>
                                    <span style="font-weight: 600; font-size: 14px; color: var(--text-color);">
                                        @if($event->product)
                                            @if($event->product_type === 'App\Models\Diamond')
                                                Diamond - <a href="{{ route('diamonds.show', $event->product_id) }}" style="color: var(--primary-color); text-decoration: none; font-weight: 700;">Stock #{{ $event->product->stock_no }}</a>
                                            @else
                                                Jewelry - <a href="{{ route('jewelery.show', $event->product_id) }}" style="color: var(--primary-color); text-decoration: none; font-weight: 700;">SKU {{ $event->product->sku }}</a>
                                            @endif
                                        @else
                                            Unknown Product (ID: {{ $event->product_id }})
                                        @endif
                                    </span>
                                </div>
                                <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;" title="{{ $event->created_at }}">
                                    <i class="fa-regular fa-clock" style="margin-right: 4px;"></i>{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}
                                </span>
                            </div>

                            <!-- Detail Content -->
                            <div style="font-size: 13px; color: var(--text-color); line-height: 1.5; margin-bottom: 12px;">
                                @if($event->log_type === 'history')
                                    <span style="color: var(--text-muted);">Remarks:</span> <strong>{{ $event->description ?: 'No remarks' }}</strong>
                                @else
                                    <span style="color: var(--text-muted);">Shopify Status:</span> 
                                    @if($event->description)
                                        <strong style="color: var(--error-color);"><i class="fa-solid fa-circle-exclamation"></i> Sync Failed: {{ $event->description }}</strong>
                                    @else
                                        <strong style="color: var(--success-color);"><i class="fa-solid fa-circle-check"></i> Sync Successful</strong>
                                    @endif
                                @endif
                            </div>

                            <!-- Metadata Block (Old vs New Values, User/Store info) -->
                            <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: 12px; background: white; border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 14px; border-left: 3px solid {{ $badgeColor }};">
                                
                                @if($event->old_value !== null || $event->new_value !== null)
                                    <div style="flex: 1 1 200px; display: flex; align-items: center; gap: 6px;">
                                        <span style="color: var(--text-muted);">Change:</span>
                                        <span style="font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: var(--error-color);">{{ $event->old_value ?? 'None' }}</span>
                                        <i class="fa-solid fa-arrow-right" style="color: var(--text-muted); font-size: 10px;"></i>
                                        <span style="font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: var(--success-color);">{{ $event->new_value ?? 'None' }}</span>
                                    </div>
                                @endif

                                @if($event->user)
                                    <div>
                                        <span style="color: var(--text-muted);"><i class="fa-solid fa-user" style="margin-right: 4px;"></i></span>
                                        <strong>{{ $event->user->name }}</strong> 
                                        <span style="color: var(--text-muted); font-size: 11px;">({{ ucfirst(str_replace('_', ' ', $event->user->role)) }})</span>
                                    </div>
                                @elseif($event->log_type === 'history')
                                    <div>
                                        <span style="color: var(--text-muted);"><i class="fa-solid fa-gears" style="margin-right: 4px;"></i></span>
                                        <strong>System Agent</strong>
                                    </div>
                                @endif

                                @if($event->store)
                                    <div>
                                        <span style="color: var(--text-muted);"><i class="fa-solid fa-store" style="margin-right: 4px;"></i> Store:</span>
                                        <strong>{{ $event->store->store_name }}</strong>
                                    </div>
                                @endif

                                @if($event->extra)
                                    <div>
                                        <span style="color: var(--text-muted);"><i class="fa-solid fa-hashtag" style="margin-right: 4px;"></i> Detail:</span>
                                        <span style="font-family: monospace; font-size: 11px;">{{ $event->extra }}</span>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach

            </div>

            <!-- Pagination Links -->
            <div style="margin-top: 30px; display: flex; justify-content: center;">
                {{ $timeline->links() }}
            </div>

        @else
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-timeline" style="font-size: 40px; color: var(--border-color); margin-bottom: 12px;"></i>
                <p style="font-size: 15px; font-weight: 500;">No timeline records found matching filters.</p>
            </div>
        @endif
    </div>

</div>
@endsection
