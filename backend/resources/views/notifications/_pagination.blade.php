@if ($notifications->hasPages())
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px;">
        <div style="font-size: 13px; color: var(--text-muted);">
            Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} notifications
        </div>
        <div style="display: flex; gap: 8px;">
            @if ($notifications->onFirstPage())
                <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed; height: 36px; line-height: 1;">&laquo; Previous</button>
            @else
                <button class="btn btn-secondary" onclick="loadPage('{{ $notifications->previousPageUrl() }}')" style="height: 36px; line-height: 1;">&laquo; Previous</button>
            @endif

            @if ($notifications->hasMorePages())
                <button class="btn btn-secondary" onclick="loadPage('{{ $notifications->nextPageUrl() }}')" style="height: 36px; line-height: 1;">Next &raquo;</button>
            @else
                <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed; height: 36px; line-height: 1;">Next &raquo;</button>
            @endif
        </div>
    </div>
@endif
