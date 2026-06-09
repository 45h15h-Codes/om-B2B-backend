@extends('layouts.app')

@section('styles')
<style>
    .request-layout {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 24px;
        align-items: start;
    }

    @media (max-width: 992px) {
        .request-layout {
            grid-template-columns: 1fr;
        }
    }

    .form-card {
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 16px;
    }

    .form-group label {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-color);
    }

    .form-control {
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-family: inherit;
        font-size: 13px;
        color: var(--text-color);
        width: 100%;
        background-color: #ffffff;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
    }

    .list-card {
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
    }

    .requests-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    .requests-table th {
        background-color: #f8fafc;
        color: var(--text-muted);
        font-weight: 700;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }

    .requests-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .requests-table tr:hover {
        background-color: #f8fafc;
    }

    .badge-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        display: inline-block;
    }

    .badge-status.Pending {
        background-color: #fef3c7;
        color: #92400e;
    }

    .badge-status.Approved {
        background-color: #dcfce7;
        color: #166534;
    }

    .badge-status.Rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .badge-priority {
        padding: 3px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-priority.High {
        background-color: #fff5f5;
        color: #e53e3e;
        border: 1px solid #fed7d7;
    }

    .badge-priority.Medium {
        background-color: #fffaf0;
        color: #dd6b20;
        border: 1px solid #fbd38d;
    }

    .badge-priority.Low {
        background-color: #f7fafc;
        color: #4a5568;
        border: 1px solid #e2e8f0;
    }
</style>
@endsection

@section('content')
<div style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700;">My Workflow Requests</h1>
    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Submit and check requests for approvals on inventory actions.</p>
</div>

<div class="request-layout">
    <!-- Submit Request Column -->
    <div class="form-card">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-paper-plane" style="color: var(--primary-color);"></i> Submit Request
        </h3>

        <form action="{{ route('inventory.request.store') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="request_type">Request Action</label>
                <select name="request_type" id="request_type" class="form-control" onchange="toggleFormFields(this.value)" required>
                    <option value="">Select Action...</option>
                    <option value="Hold Inventory">Hold Inventory</option>
                    <option value="Release Inventory">Release Inventory</option>
                    <option value="Shopify Sync">Shopify Sync</option>
                    <option value="Price Change">Price Change</option>
                    <option value="Inventory Correction">Inventory Correction</option>
                </select>
            </div>

            <div class="form-group">
                <label for="product_select">Select Inventory Item</label>
                <select name="product_id" id="product_select" class="form-control" required>
                    <option value="">Choose item...</option>
                </select>
            </div>

            <input type="hidden" name="product_type" id="product_type_hidden" value="">

            <!-- Action-specific fields -->
            <div id="hold_fields" style="display: none;">
                <div class="form-group">
                    <label for="reason">Reason for Hold <span style="color: var(--error-color);">*</span></label>
                    <input type="text" name="reason" id="reason" class="form-control" placeholder="E.g., Customer memo hold">
                </div>
            </div>

            <div id="release_fields" style="display: none;">
                <div class="form-group">
                    <label for="remarks">Release Remarks <span style="color: var(--error-color);">*</span></label>
                    <input type="text" name="remarks" id="remarks" class="form-control" placeholder="E.g., Customer cancelled hold">
                </div>
            </div>

            <div id="price_fields" style="display: none;">
                <div class="form-group">
                    <label for="price">New Price ($) <span style="color: var(--error-color);">*</span></label>
                    <input type="number" step="0.01" name="price" id="price" class="form-control" placeholder="E.g., 1250.00">
                </div>
            </div>

            <div class="form-group">
                <label for="priority">Priority</label>
                <select name="priority" id="priority" class="form-control" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                </select>
            </div>

            <div class="form-group">
                <label for="notes">Notes / Remarks</label>
                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Provide additional details..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; height: 40px; justify-content: center; margin-top: 8px;">
                Submit Request
            </button>
        </form>
    </div>

    <!-- Requests List Column -->
    <div class="list-card">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); font-weight: 700; font-size: 15px;">
            Request History
        </div>
        <table class="requests-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Product</th>
                    <th>Details</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Approved By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    @php
                        $product = $req->product;
                        $stockNo = $product ? ($product->stock_no ?? $product->sku) : 'Deleted Product';
                    @endphp
                    <tr>
                        <td style="font-weight: 700;">#{{ $req->id }}</td>
                        <td>{{ $req->created_at->format('Y-m-d') }}</td>
                        <td style="font-weight: 600; color: var(--primary-color);">{{ $req->request_type }}</td>
                        <td>
                            <div style="font-weight: 700;">{{ $stockNo }}</div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: capitalize;">{{ $req->product_type }}</div>
                        </td>
                        <td>
                            @if($req->request_type === 'Hold Inventory')
                                <span style="font-size: 12px; color: var(--text-muted);">Reason: {{ $req->action_payload['reason'] ?? '-' }}</span>
                            @elseif($req->request_type === 'Release Inventory')
                                <span style="font-size: 12px; color: var(--text-muted);">Remarks: {{ $req->action_payload['remarks'] ?? '-' }}</span>
                            @elseif($req->request_type === 'Price Change')
                                <span style="font-size: 12px; font-weight:700;">New Price: ${{ number_format($req->action_payload['price'] ?? 0, 2) }}</span>
                            @else
                                <span style="font-size: 12px; color: var(--text-muted);">{{ Str::limit($req->notes, 30) }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge-priority {{ $req->priority }}">{{ $req->priority }}</span>
                        </td>
                        <td>
                            <span class="badge-status {{ $req->status }}">{{ $req->status }}</span>
                        </td>
                        <td>
                            @if($req->status === 'Approved')
                                <span style="font-weight: 600;">{{ $req->approver ? $req->approver->name : 'Super Admin' }}</span>
                                <div style="font-size: 10px; color: var(--text-muted);">{{ $req->approved_at ? $req->approved_at->format('Y-m-d') : '' }}</div>
                            @elseif($req->status === 'Rejected')
                                <span style="color: var(--error-color); font-weight:600;">Rejected</span>
                            @else
                                <span style="color: var(--text-muted); font-style:italic;">Awaiting review</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">No requests submitted yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const assignedDiamonds = @json($assignedDiamonds);
    const assignedJewelery = @json($assignedJewelery);

    function toggleFormFields(val) {
        document.getElementById('hold_fields').style.display = (val === 'Hold Inventory') ? 'block' : 'none';
        document.getElementById('release_fields').style.display = (val === 'Release Inventory') ? 'block' : 'none';
        document.getElementById('price_fields').style.display = (val === 'Price Change') ? 'block' : 'none';

        // Load items based on action type
        const select = document.getElementById('product_select');
        select.innerHTML = '<option value="">Choose item...</option>';

        // Determine list to render. If Release/Sync/Price, show items that are held or active
        // Let's combine both lists and let user select.
        const combined = [];
        assignedDiamonds.forEach(d => {
            combined.push({
                id: d.id,
                type: 'diamond',
                label: `Diamond: ${d.stock_no} (${d.shape ?: 'Round'}, ${d.size ?: '0.3'}ct, $${d.asking_price}) - ${d.inventory_status}`,
                status: d.inventory_status
            });
        });

        assignedJewelery.forEach(j => {
            combined.push({
                id: j.id,
                type: 'jewelry',
                label: `Jewelry: ${j.sku} (${j.name}) - ${j.inventory_status}`,
                status: j.inventory_status
            });
        });

        combined.forEach(item => {
            // Apply filtering logic based on request action
            let show = true;
            if (val === 'Hold Inventory' && item.status !== 'available') {
                show = false; // Cannot hold if not available
            }
            if (val === 'Release Inventory' && item.status !== 'on_hold') {
                show = false; // Cannot release if not on hold
            }

            if (show) {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.dataset.type = item.type;
                opt.textContent = item.label;
                select.appendChild(opt);
            }
        });
    }

    document.getElementById('product_select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption) {
            document.getElementById('product_type_hidden').value = selectedOption.dataset.type || '';
        }
    });

    // Run trigger on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleFormFields('');
    });
</script>
@endsection
