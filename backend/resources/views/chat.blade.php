@extends('layouts.app')

@section('styles')
<style>
    /* Chat Main Layout */
    .chat-container {
        display: flex;
        background-color: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        height: calc(100vh - 160px);
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }

    /* Left Sidebar */
    .chat-sidebar {
        width: 350px;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        background-color: #ffffff;
    }

    .chat-tabs {
        display: flex;
        padding: 20px;
        gap: 12px;
        border-bottom: 1px solid var(--border-color);
    }

    .chat-tab {
        flex: 1;
        padding: 10px 0;
        text-align: center;
        font-weight: 700;
        font-size: 13px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid var(--border-color);
        background-color: #ffffff;
        color: var(--text-muted);
    }

    .chat-tab.active {
        background-color: var(--primary-color);
        color: #ffffff;
        border-color: var(--primary-color);
        box-shadow: 0 2px 4px rgba(16, 139, 182, 0.1);
    }

    .chat-search-wrapper {
        padding: 15px 20px 10px 20px;
        position: relative;
    }

    .chat-search-input {
        width: 100%;
        padding: 10px 40px 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 13px;
        color: var(--text-color);
        background-color: #f7fafc;
        transition: all 0.2s ease;
    }

    .chat-search-input:focus {
        outline: none;
        border-color: var(--primary-color);
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(16, 139, 182, 0.1);
    }

    .chat-search-icon {
        position: absolute;
        right: 32px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 14px;
    }

    .chat-actions-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        padding: 0 20px 15px 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .chat-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background-color: #ffffff;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 13px;
    }

    .chat-action-btn:hover {
        background-color: var(--primary-light);
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    /* Left Sidebar List Empty State */
    .chat-list-empty {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        text-align: center;
        background-color: #fcfdfe;
    }

    .chat-empty-icon-wrapper {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background-color: #f7fafc;
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
        color: var(--text-muted);
    }

    .chat-empty-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 8px;
    }

    .chat-empty-desc {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.5;
    }

    /* Right Main Panel */
    .chat-main {
        flex: 1;
        background-color: #f7fafc;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
        text-align: center;
    }

    .chat-main-welcome {
        max-width: 460px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .chat-welcome-art {
        width: 100px;
        height: 100px;
        border-radius: 24px;
        background-color: var(--primary-light);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        box-shadow: 0 8px 24px rgba(16, 139, 182, 0.08);
    }

    .chat-welcome-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 12px;
    }

    .chat-welcome-desc {
        font-size: 14px;
        color: var(--text-muted);
        line-height: 1.6;
    }
</style>
@endsection

@section('content')
<div class="chat-container">
    <!-- Left Chat Sidebar -->
    <div class="chat-sidebar">
        <!-- Tabs: Sell vs Buy -->
        <div class="chat-tabs">
            <div class="chat-tab active" id="tab-sell" onclick="switchChatTab('sell')">Sell</div>
            <div class="chat-tab" id="tab-buy" onclick="switchChatTab('buy')">Buy</div>
        </div>

        <!-- Search Bar -->
        <div class="chat-search-wrapper">
            <input type="text" class="chat-search-input" placeholder="Search chats..." id="chat-search">
            <i class="fa-solid fa-magnifying-glass chat-search-icon"></i>
        </div>

        <!-- Sidebar Filter Actions Row -->
        <div class="chat-actions-row">
            <div class="chat-action-btn" title="Filter Chats">
                <i class="fa-solid fa-filter"></i>
            </div>
            <div class="chat-action-btn" title="Sort Chats">
                <i class="fa-solid fa-arrow-down-short-wide"></i>
            </div>
            <div class="chat-action-btn" title="Grid Layout">
                <i class="fa-solid fa-grip"></i>
            </div>
        </div>

        <!-- Chat List - Empty State -->
        <div class="chat-list-empty">
            <div class="chat-empty-icon-wrapper">
                <i class="fa-regular fa-comments" style="font-size: 24px;"></i>
            </div>
            <h4 class="chat-empty-title">No Active Chats</h4>
            <p class="chat-empty-desc">
                Your buyer and seller conversation lists are currently empty because there are no registered accounts on the site yet.
            </p>
        </div>
    </div>

    <!-- Right Workspace Area - Welcome State -->
    <div class="chat-main">
        <div class="chat-main-welcome">
            <div class="chat-welcome-art">
                <i class="fa-solid fa-message" style="font-size: 44px;"></i>
            </div>
            <h3 class="chat-welcome-title">OM Gems Live Chat</h3>
            <p class="chat-welcome-desc">
                Welcome to your secure communications workspace. Once buyers complete purchases or make inquiries, real-time message feeds will load in this dashboard for instant negotiation.
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function switchChatTab(tabType) {
        const sellTab = document.getElementById('tab-sell');
        const buyTab = document.getElementById('tab-buy');

        if (tabType === 'sell') {
            sellTab.classList.add('active');
            buyTab.classList.remove('active');
        } else {
            buyTab.classList.add('active');
            sellTab.classList.remove('active');
        }
    }
</script>
@endsection
