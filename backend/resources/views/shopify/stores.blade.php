@extends('layouts.app')

@section('styles')
<style>
    .shopify-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .shopify-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        padding: 20px 24px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
    }

    .shopify-title h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .store-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
    }

    @media (min-width: 1024px) {
        .store-grid {
            grid-template-columns: 2fr 1fr;
        }
    }

    .card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        padding: 24px;
    }

    .card-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 16px;
        color: var(--text-color);
        display: flex;
        align-items: center;
        gap: 8px;
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
        padding: 12px 16px;
        border-bottom: 2px solid var(--border-color);
    }

    .list-table td {
        font-size: 13.5px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
        vertical-align: middle;
    }

    .list-table tr:last-child td {
        border-bottom: none;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-transform: uppercase;
    }

    .badge.active {
        background: #e6fffa;
        color: #0b69a3;
        border: 1px solid #b2f5ea;
    }

    .badge.inactive {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #cbd5e0;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        font-weight: 700;
        font-size: 12px;
        color: var(--text-color);
        text-transform: uppercase;
        margin-bottom: 8px;
        display: block;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 14px;
        color: var(--text-color);
        background-color: #fafafa;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        background-color: #ffffff;
        outline: none;
    }
</style>
@endsection

@section('content')
<div class="shopify-container">
    <div class="shopify-header">
        <div class="shopify-title">
            <h2>
                <i class="fa-solid fa-store" style="color: var(--primary-color);"></i>
                Shopify Stores Management
            </h2>
        </div>
    </div>

    <div class="store-grid">
        <!-- Stores List -->
       <div class="card">
    <h3 class="card-title">Connected Shopify Stores</h3>

    <div style="overflow-x: auto;">
        <table class="list-table">
            <thead>
                <tr>
                    <th>Store Name</th>
                    <th>Shopify Domain</th>

                    @if(session('admin_role') === 'super_admin')
                        <th>Owner</th>
                    @endif

                    <th>Status</th>

                    @if(session('admin_role') !== 'super_admin')
                        <th style="text-align: right;">Actions</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @forelse($stores as $store)
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: var(--text-color);">
                                {{ $store->store_name ?: 'Unnamed Store' }}
                            </div>
                        </td>

                        <td>
                            <code>{{ $store->shop_domain }}</code>
                        </td>

                        @if(session('admin_role') === 'super_admin')
                            <td>
                                @if($store->user)
                                    <strong>{{ $store->user->name }}</strong>
                                @else
                                    <span style="color: var(--text-muted); font-style: italic;">
                                        System / Global
                                    </span>
                                @endif
                            </td>
                        @endif

                        <td>
                            @if($activeStoreId === $store->id)
                                <span class="badge active">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Active
                                </span>
                            @else
                                <span class="badge inactive">
                                    <i class="fa-solid fa-circle"></i>
                                    Inactive
                                </span>
                            @endif
                        </td>

                        @if(session('admin_role') !== 'super_admin')
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; justify-content: flex-end; align-items: center;">

                                    @if($activeStoreId !== $store->id)
                                        <form action="{{ route('shopify.set-active-store', $store->id) }}"
                                              method="POST"
                                              style="margin:0;">
                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-secondary"
                                                    style="font-size:12px; padding:6px 12px;">
                                                Make Active
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('shopify.delete-store', $store->id) }}"
                                          method="POST"
                                          class="confirm-delete-form"
                                          data-username="{{ $store->store_name ?: $store->shop_domain }}"
                                          style="margin:0;">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-danger"
                                                style="font-size:12px; padding:6px 12px; background:#fff5f5; color:var(--error-color); border-color:#fed7d7;">
                                            Disconnect
                                        </button>
                                    </form>

                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ session('admin_role') === 'super_admin' ? 4 : 5 }}"
                            style="text-align:center; color:var(--text-muted); padding:40px 0; font-style:italic;">
                            No Shopify stores connected yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

        <!-- Connect Store Form -->
       @if(session('admin_role') !== 'super_admin')
<div class="card">
    <h3 class="card-title">Connect New Store</h3>

    <form action="{{ route('shopify.connect-store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="store_name">Store Name (Optional)</label>
            <input type="text"
                   id="store_name"
                   name="store_name"
                   class="form-control"
                   placeholder="e.g. My Retail Store"
                   value="{{ old('store_name') }}">
        </div>

        <div class="form-group">
            <label for="shop_domain">Shop Domain</label>
            <input type="text"
                   id="shop_domain"
                   name="shop_domain"
                   class="form-control"
                   placeholder="e.g. store.myshopify.com"
                   value="{{ old('shop_domain') }}"
                   required>

            <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">
                Enter your primary myshopify domain.
            </small>
        </div>

        <div class="form-group">
            <label for="access_token">Admin API Access Token</label>
            <input type="password"
                   id="access_token"
                   name="access_token"
                   class="form-control"
                   placeholder="shpat_xxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                   required>

            <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">
                The Custom App Admin API token generated in your store settings.
            </small>
        </div>

        <button type="submit"
                class="btn btn-primary"
                style="width: 100%; justify-content: center; margin-top: 10px;">
            <i class="fa-solid fa-plug"></i>
            Verify & Connect
        </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
