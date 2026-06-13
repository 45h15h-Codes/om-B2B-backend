<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OM Gems Admin</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #108bb6;
            --primary-hover: #0b7094;
            --primary-light: #e8f4f8;
            --bg-color: #f4f7f9;
            --card-bg: #ffffff;
            --text-color: #2d3748;
            --text-muted: #718096;
            --border-color: #e2e8f0;
            --success-color: #38a169;
            --error-color: #e53e3e;
            --warning-color: #dd6b20;
            --sidebar-width: 240px;
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
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Styling (Matched to Image) */
        .sidebar {
            width: var(--sidebar-width);
            background-color: #1a83ad;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }

        .sidebar-logo {
            font-weight: 700;
            color: #ffffff;
            font-size: 20px;
            padding: 24px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            padding: 20px 0;
            width: 100%;
        }

        .sidebar-item {
            color: rgba(255, 255, 255, 0.85);
            font-size: 14px;
            font-weight: 600;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border-left: 4px solid transparent;
        }

        .sidebar-item i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .sidebar-item:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar-item.active {
            color: #1a83ad;
            background-color: #ffffff;
            border-left-color: #ffffff;
        }

        .sidebar-item.active i {
            color: #1a83ad;
        }

        /* Main Content wrapper */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header Navigation */
        .header {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 0 40px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 30px;
            height: 100%;
        }

        .header-nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 15px;
            height: 100%;
            display: flex;
            align-items: center;
            position: relative;
            transition: color 0.2s ease;
        }

        .header-nav-link:hover, .header-nav-link.active {
            color: var(--primary-color);
        }

        .header-nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: var(--primary-color);
            border-top-left-radius: 3px;
            border-top-right-radius: 3px;
        }

        /* Right Header Elements */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* Role Badge / Switcher Button */
        .role-switcher-form {
            display: inline-block;
        }

        .role-badge-btn {
            background: none;
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 20px;
            font-family: inherit;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .role-badge-btn.normal_admin {
            border-color: #cbd5e0;
            color: #4a5568;
            background-color: #f7fafc;
        }

        .role-badge-btn.super_admin {
            border-color: #feebc8;
            color: #c05621;
            background-color: #fffaf0;
        }

        .role-badge-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .role-indicator-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .normal_admin .role-indicator-dot {
            background-color: #a0aec0;
        }

        .super_admin .role-indicator-dot {
            background-color: #dd6b20;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* Main Workspace */
        .workspace {
            flex: 1;
            padding: 40px;
            min-width: 0;
        }

        /* Global button styles used across pages */
        .btn {
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
            text-decoration: none;
            box-sizing: border-box;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #ffffff;
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: #f7fafc;
            color: var(--text-color);
            border-color: #cbd5e0;
        }

        .btn-secondary:hover {
            background-color: #f1f5f9;
        }

        .btn-danger {
            background-color: #fff5f5;
            border-color: #fed7d7;
            color: var(--error-color);
        }

        .btn-danger:hover {
            background-color: var(--error-color);
            color: #ffffff;
            border-color: var(--error-color);
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background-color: #f0fff4;
            border: 1px solid #c6f6d5;
            color: var(--success-color);
        }

        .alert-error {
            background-color: #fff5f5;
            border: 1px solid #fed7d7;
            color: var(--error-color);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Profile Dropdown Styling */
        .profile-dropdown-container {
            position: relative;
            display: inline-block;
        }

        .profile-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 45px;
            background-color: var(--card-bg);
            min-width: 190px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            z-index: 1000;
            padding: 6px 0;
            overflow: hidden;
            animation: fadeInDropdown 0.15s ease;
        }

        .profile-dropdown-menu.show {
            display: block;
        }

        .dropdown-item-btn {
            width: 100%;
            padding: 10px 16px;
            background: none;
            border: none;
            text-align: left;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease;
        }

        .dropdown-item-btn:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .dropdown-logout-btn {
            width: 100%;
            padding: 10px 16px;
            background: none;
            border: none;
            text-align: left;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            color: var(--error-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s ease;
        }

        .dropdown-logout-btn:hover {
            background-color: #fff5f5;
        }

        @keyframes fadeInDropdown {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Top-left toast notifications */
        .toast-container {
            position: fixed;
            top: 18px;
            left: 18px;
            z-index: 12000;
            display: flex;
            flex-direction: column;
            gap: 8px;
            pointer-events: none;
        }

        .toast {
            min-width: 220px;
            max-width: 320px;
            padding: 10px 14px;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(15,23,42,0.12);
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            pointer-events: auto;
            opacity: 0;
            transform: translateY(-6px);
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.success { background: #f0fff4; border: 1px solid #c6f6d5; color: var(--success-color); }
        .toast.error { background: #fff5f5; border: 1px solid #fed7d7; color: var(--error-color); }

        /* Global delete confirmation overlay */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 13000;
        }

        .confirm-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 22px 24px;
            border-radius: 12px;
            width: 420px;
            max-width: 92vw;
            box-shadow: 0 12px 30px rgba(15,23,42,0.12);
            text-align: left;
        }

        .confirm-actions {
            display:flex;
            justify-content:flex-end;
            gap:10px;
            margin-top:16px;
        }

        .is-impersonating .sidebar {
            top: 46px !important;
        }
        .is-impersonating .main-wrapper {
            margin-top: 46px;
        }
        .is-impersonating .header {
            top: 46px !important;
        }

        /* Pagination overrides to constrain SVG sizes */
        .pagination-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .pagination-container nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-top: 15px;
            padding: 10px 0;
        }

        .pagination-container svg {
            width: 16px !important;
            height: 16px !important;
            display: inline-block;
            vertical-align: middle;
        }

        .pagination-container .flex {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pagination-container nav .flex.justify-between.flex-1.sm:hidden {
            display: none !important;
        }

        .pagination-container nav .hidden.sm:flex-1 {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 16px;
        }

        .pagination-container span.relative {
            display: inline-flex;
            border-radius: 6px;
            background: #ffffff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .pagination-container a, 
        .pagination-container span[aria-current="page"] span,
        .pagination-container span[aria-disabled="true"] span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            min-width: 36px;
            padding: 0 12px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-color);
            border: 1px solid var(--border-color);
            border-right: none;
            text-decoration: none;
            background: #ffffff;
            transition: background-color 0.15s ease;
        }

        .pagination-container a:first-child,
        .pagination-container span[aria-disabled="true"]:first-child span {
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }

        .pagination-container a:last-child,
        .pagination-container span[aria-disabled="true"]:last-child span {
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
            border-right: 1px solid var(--border-color);
        }

        .pagination-container a:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .pagination-container span[aria-current="page"] span {
            background-color: var(--primary-color);
            color: #ffffff;
            border-color: var(--primary-color);
            cursor: default;
        }

        .pagination-container span[aria-disabled="true"] span {
            color: var(--text-muted);
            cursor: not-allowed;
            background-color: #f8fafc;
        }

        @keyframes bell-wiggle {
            0% { transform: rotate(0); }
            15% { transform: rotate(10deg); }
            30% { transform: rotate(-10deg); }
            45% { transform: rotate(5deg); }
            60% { transform: rotate(-5deg); }
            75% { transform: rotate(2deg); }
            90% { transform: rotate(-2deg); }
            100% { transform: rotate(0); }
        }

        .bell-bounce {
            animation: bell-wiggle 0.8s ease-in-out;
            display: inline-block;
        }
    </style>
        @yield('styles')
    </head>
    <body class="{{ session()->has('super_admin_user_id') ? 'is-impersonating' : '' }}">

        <!-- Toast container (top-left) -->
        <div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>

        <!-- Global Deletion Confirmation Overlay -->
        <div id="confirmOverlay" class="confirm-overlay" aria-hidden="true">
            <div class="confirm-box" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
                <h3 id="confirmTitle" style="margin:0 0 8px 0; font-size:18px;">Confirm</h3>
                <p class="confirm-message" style="margin:0 0 8px 0; color:var(--text-muted); font-weight:600;">Are you sure?</p>
                <div class="confirm-actions">
                    <button type="button" class="btn btn-secondary" id="confirmCancel">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmOk">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        </div>

    @if(session()->has('super_admin_user_id'))
        <div style="background: linear-gradient(90deg, #dd6b20, #e53e3e); color: white; padding: 0 40px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 700; z-index: 1000; position: fixed; top: 0; left: 0; right: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; height: 46px;">
            <div>
                <i class="fa-solid fa-user-secret" style="margin-right: 8px; font-size: 16px;"></i>
                Impersonating Normal Admin Account: <span style="text-decoration: underline; font-weight: 800; background-color: rgba(255,255,255,0.15); padding: 4px 8px; border-radius: 4px; margin-left: 6px;">{{ Auth::user()->name }}</span>
            </div>
            <form action="{{ route('admins.stop-impersonate') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" style="background: #ffffff; color: #dd6b20; border: none; padding: 6px 14px; border-radius: 6px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); font-family: inherit; font-size: 12.5px;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Return to Super Admin Panel
                </button>
            </form>
        </div>
    @endif

    @php
        $activeRole = session('admin_role');
        if (empty($activeRole) && Auth::check()) {
            $activeRole = Auth::user()->role;
            session(['admin_role' => $activeRole]);
        }
        $activeRole = $activeRole ?: 'normal_admin';
        $isSuper = ($activeRole === 'super_admin');
    @endphp

    <!-- Left Sidebar (Wide Text Sidebar matching Image) -->
    <div class="sidebar">
        <div class="sidebar-logo">OM Gems</div>
        <div class="sidebar-menu">
            <!-- Home link -->
            <a href="{{ route('home') }}" class="sidebar-item {{ Route::is('home') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            
            <!-- Diamond link -->
            <a href="{{ route('diamonds.index') }}" class="sidebar-item {{ Route::is('diamonds.*') ? 'active' : '' }}">
                <i class="fa-solid fa-gem"></i>
                <span>Diamond</span>
            </a>
            
            <a href="{{ route('jewelery.index') }}" class="sidebar-item {{ Route::is('jewelery.*') ? 'active' : '' }}">
                <i class="fa-solid fa-ring"></i>
                <span>Jewelery</span>
            </a>


            <!-- Requests Link -->
            @if($isSuper)
                <a href="{{ route('all-requests') }}" class="sidebar-item {{ Route::is('all-requests') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span>All Requests</span>
                </a>
            @else
                <a href="{{ route('my-requests') }}" class="sidebar-item {{ Route::is('my-requests') ? 'active' : '' }}">
                    <i class="fa-solid fa-code-pull-request"></i>
                    <span>My Requests</span>
                </a>
            @endif

            <!-- Shopify link -->
            <a href="{{ route('shopify.dashboard') }}" class="sidebar-item {{ (Route::is('shopify.*') || Route::is('admin.shopify.orders')) ? 'active' : '' }}">
                <i class="fa-brands fa-shopify"></i>
                <span>Shopify</span>
            </a> 
            <a href="{{ route('chat') }}" class="sidebar-item {{ Route::is('chat') ? 'active' : '' }}">
                <i class="fa-solid fa-comments"></i>
                <span>Chat</span>
            </a>
            <div class="sidebar-item">
                <i class="fa-solid fa-circle-info"></i>
                <span>About Us</span>
            </div>
            
            @if(session('admin_role') === 'super_admin')
            <a href="{{ route('admins.index') }}" class="sidebar-item {{ Route::is('admins.*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-plus"></i>
                <span>Add on User</span>
            </a>
            <a href="{{ route('partnership-requests.index') }}" class="sidebar-item {{ Route::is('partnership-requests.*') ? 'active' : '' }}">
                <i class="fa-solid fa-handshake"></i>
                <span>Partnership Requests</span>
            </a>
            @else
            <div class="sidebar-item" style="opacity: 0.5; cursor: not-allowed;" title="Super Admin Only">
                <i class="fa-solid fa-user-plus"></i>
                <span>Add on User</span>
            </div>
            @endif

            @if($isSuper || Auth::user()->hasAnyPermission(['view_revenue', 'view_reports']))
                <!-- Reports & Analytics -->
                <div class="sidebar-section-header" style="padding: 10px 24px 5px 24px; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 0.5px;">Reports & Analytics</div>
                
                @if(Auth::user()->hasPermission('view_revenue'))
                    <a href="{{ route('analytics.revenue') }}" class="sidebar-item {{ Route::is('analytics.revenue') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Revenue Dashboard</span>
                    </a>
                @endif

            @endif
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Header -->
        <header class="header">
            <!-- Nav Options -->
            <div class="header-nav">
                @if(Route::is('diamonds.*'))
                    <a href="{{ route('diamonds.index') }}" class="header-nav-link {{ Request::routeIs('diamonds.index') && !Request::has('create') ? 'active' : '' }}">
                        Search
                    </a>
                    @if(!$isSuper)
                        <a href="{{ route('diamonds.create') }}" class="header-nav-link {{ Request::routeIs('diamonds.create') ? 'active' : '' }}">
                            Upload Diamond
                        </a>
                    @endif
                    @if($isSuper)
                        <a href="{{ route('categories.index') }}" class="header-nav-link {{ Route::is('categories.*') ? 'active' : '' }}">
                            Manage Categories
                        </a>
                    @endif
                @elseif(Route::is('jewelery.*'))
                    <a href="{{ route('jewelery.index') }}" class="header-nav-link {{ Request::routeIs('jewelery.index') && request('tab') !== 'upload' ? 'active' : '' }}">
                        Search
                    </a>
                    @if(!$isSuper)
                        <a href="{{ route('jewelery.index', ['tab' => 'upload']) }}" class="header-nav-link {{ request('tab') === 'upload' ? 'active' : '' }}">
                            Upload Jwelery
                        </a>
                    @endif
                    @if($isSuper)
                        <a href="{{ route('categories.index') }}" class="header-nav-link">
                            Manage Categories
                        </a>
                    @endif
                @elseif(Route::is('categories.*'))
                    <a href="{{ route('categories.index') }}" class="header-nav-link active">
                        Category Editor
                    </a>
                    <a href="{{ url()->previous() }}" class="header-nav-link">
                        Back to Search
                    </a>
                @elseif(Route::is('shopify.*') || Route::is('orders.*') || Route::is('admin.shopify.orders'))
                    <a href="{{ route('shopify.dashboard') }}" class="header-nav-link {{ Route::is('shopify.dashboard') ? 'active' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('shopify.stores') }}" class="header-nav-link {{ Route::is('shopify.stores') ? 'active' : '' }}">
                        Manage Stores
                    </a>
                    @if(Auth::user()->hasPermission('view_shopify_orders'))
                        <a href="{{ route('admin.shopify.orders') }}" class="header-nav-link {{ Route::is('admin.shopify.orders') ? 'active' : '' }}">
                            Shopify Store Orders
                        </a>
                    @endif
                @else
                    <a href="{{ route('home') }}" class="header-nav-link active">
                        Dashboard
                    </a>
                @endif
            </div>

            <!-- Header Actions -->
            <div class="header-actions">
                @auth
                    @if(Auth::user()->hasPermission('view_notifications'))
                    <!-- Notification Bell Dropdown -->
                    <div class="profile-dropdown-container" style="margin-right: 10px;">
                        <div class="user-avatar" onclick="toggleNotificationDropdown(event)" style="cursor: pointer; position: relative;">
                            <i class="fa-regular fa-bell"></i>
                            @if(isset($unreadNotificationsCount) && $unreadNotificationsCount > 0)
                                <span class="badge" style="position: absolute; top: -5px; right: -5px; background-color: var(--error-color); color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 700; display: inline-block; line-height: 1;">
                                    {{ $unreadNotificationsCount }}
                                </span>
                            @endif
                        </div>
                        <div class="profile-dropdown-menu" id="notificationDropdown" style="min-width: 320px; max-height: 400px; overflow-y: auto; padding: 10px;">
                            <div style="font-weight: 700; font-size: 14px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                                <span>Notifications</span>
                                <div style="display: flex; gap: 10px; align-items: center;" id="globalNotificationActions">
                                    @if(isset($recentNotifications) && count($recentNotifications) > 0)
                                        <button id="markAllReadBtn" onclick="markAllNotificationsRead(event)" style="background:none; border:none; color:var(--primary-color); font-size:12px; font-weight:600; cursor:pointer;" title="Mark All Read">
                                            <i class="fa-solid fa-check-double"></i> Mark All Read
                                        </button>
                                        <button id="deleteAllNotificationsBtn" onclick="deleteAllNotifications(event)" style="background:none; border:none; color:var(--error-color); font-size:12px; font-weight:600; cursor:pointer;" title="Delete All">
                                            <i class="fa-solid fa-trash-can"></i> Delete All
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <div id="notificationList" style="margin-top: 8px;">
                                @if(isset($recentNotifications) && count($recentNotifications) > 0)
                                    @foreach($recentNotifications as $notification)
                                        <div class="notification-item {{ $notification->read_at ? 'read' : 'unread' }}" data-id="{{ $notification->id }}" style="position: relative; display: flex; align-items: flex-start; justify-content: space-between; padding: 10px; border-radius: 6px; margin-bottom: 6px; font-size: 12.5px; transition: background-color 0.2s ease; {{ $notification->read_at ? 'opacity: 0.7;' : 'background-color: var(--primary-light); font-weight: 600;' }}">
                                            <a href="{{ route('notifications.read-single', $notification->id) }}" class="notification-link" style="text-decoration: none; color: inherit; flex-grow: 1; margin-right: 8px;">
                                                <div style="font-weight: 700; color: var(--primary-color); margin-bottom: 2px;">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                                <div style="color: var(--text-color);">{{ $notification->data['message'] ?? '' }}</div>
                                                <div style="font-size: 10px; color: var(--text-muted); margin-top: 4px;">{{ $notification->created_at->diffForHumans() }}</div>
                                            </a>
                                            <div class="notification-actions" style="display: flex; gap: 6px; align-items: center; margin-top: 2px;">
                                                @if(!$notification->read_at)
                                                    <button class="action-btn mark-read-btn" onclick="executeNotificationAction(event, '{{ $notification->id }}', 'read')" style="background: none; border: none; color: var(--success-color); cursor: pointer; padding: 2px; font-size: 13px;" title="Mark Read">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                @else
                                                    <button class="action-btn mark-unread-btn" onclick="executeNotificationAction(event, '{{ $notification->id }}', 'unread')" style="background: none; border: none; color: var(--primary-color); cursor: pointer; padding: 2px; font-size: 13px;" title="Mark Unread">
                                                        <i class="fa-solid fa-rotate-left"></i>
                                                    </button>
                                                @endif
                                                <button class="action-btn delete-btn" onclick="executeNotificationAction(event, '{{ $notification->id }}', 'delete')" style="background: none; border: none; color: var(--error-color); cursor: pointer; padding: 2px; font-size: 13px;" title="Delete">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="no-notifications-placeholder" style="text-align: center; color: var(--text-muted); padding: 20px 0; font-size: 13px;">No notifications.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <span class="role-badge-btn {{ session('admin_role') === 'super_admin' ? 'super_admin' : 'normal_admin' }}" style="cursor: default;">
                        <span class="role-indicator-dot"></span>
                        {{ session('admin_role') === 'super_admin' ? 'Super Admin' : 'Normal Admin' }}
                    </span>

                    <div class="profile-dropdown-container">
                        <div class="user-avatar" onclick="toggleProfileDropdown(event)" style="cursor: pointer;">
                            <i class="fa-regular fa-user"></i>
                        </div>
                        <div class="profile-dropdown-menu" id="profileDropdown">
                            <form action="{{ route('logout') }}" method="POST" style="width: 100%; margin: 0;">
                                @csrf
                                <button type="submit" class="dropdown-logout-btn">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="user-avatar">
                        <i class="fa-regular fa-user"></i>
                    </div>
                @endauth
            </div>
        </header>

        <!-- Workspace -->
        <main class="workspace">
            @yield('content')
        </main>
    </div>

    <script>
        function toggleProfileDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
            const notifyDropdown = document.getElementById('notificationDropdown');
            if (notifyDropdown) {
                notifyDropdown.classList.remove('show');
            }
        }

        function toggleNotificationDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileDropdown) {
                profileDropdown.classList.remove('show');
            }
        }

        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
            const notifyDropdown = document.getElementById('notificationDropdown');
            if (notifyDropdown && notifyDropdown.classList.contains('show')) {
                notifyDropdown.classList.remove('show');
            }
        });

        // AJAX Notification handlers
        let lastNotificationTime = '{{ isset($recentNotifications) && $recentNotifications->isNotEmpty() ? $recentNotifications->max("created_at")->toDateTimeString() : now()->toDateTimeString() }}';

        function updateNotificationBadge(unreadCount) {
            const badgeContainer = document.querySelector('.user-avatar .badge');
            const badgeWrapper = document.querySelector('.user-avatar');
            
            if (unreadCount > 0) {
                if (badgeContainer) {
                    badgeContainer.textContent = unreadCount;
                    badgeContainer.style.display = 'inline-block';
                } else if (badgeWrapper) {
                    const span = document.createElement('span');
                    span.className = 'badge';
                    span.style.cssText = 'position: absolute; top: -5px; right: -5px; background-color: var(--error-color); color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 700; display: inline-block; line-height: 1;';
                    span.textContent = unreadCount;
                    badgeWrapper.appendChild(span);
                }
            } else {
                if (badgeContainer) {
                    badgeContainer.remove();
                }
            }
        }

        function rebuildNotificationList(notifications, unreadCount) {
            updateNotificationBadge(unreadCount);
            
            const container = document.getElementById('notificationList');
            if (!container) return;
            
            container.innerHTML = '';
            
            const globalActions = document.getElementById('globalNotificationActions');
            if (notifications.length > 0) {
                if (globalActions) {
                    globalActions.innerHTML = `
                        <button id="markAllReadBtn" onclick="markAllNotificationsRead(event)" style="background:none; border:none; color:var(--primary-color); font-size:12px; font-weight:600; cursor:pointer;" title="Mark All Read">
                            <i class="fa-solid fa-check-double"></i> Mark All Read
                        </button>
                        <button id="deleteAllNotificationsBtn" onclick="deleteAllNotifications(event)" style="background:none; border:none; color:var(--error-color); font-size:12px; font-weight:600; cursor:pointer;" title="Delete All">
                            <i class="fa-solid fa-trash-can"></i> Delete All
                        </button>
                    `;
                }
                
                notifications.forEach(n => {
                    const div = document.createElement('div');
                    div.className = 'notification-item ' + (n.read_at ? 'read' : 'unread');
                    div.setAttribute('data-id', n.id);
                    div.style.cssText = 'position: relative; display: flex; align-items: flex-start; justify-content: space-between; padding: 10px; border-radius: 6px; margin-bottom: 6px; font-size: 12.5px; transition: background-color 0.2s ease; ' + (n.read_at ? 'opacity: 0.7;' : 'background-color: var(--primary-light); font-weight: 600;');
                    
                    const actionButton = n.read_at 
                        ? `<button class="action-btn mark-unread-btn" onclick="executeNotificationAction(event, '${n.id}', 'unread')" style="background: none; border: none; color: var(--primary-color); cursor: pointer; padding: 2px; font-size: 13px;" title="Mark Unread">
                               <i class="fa-solid fa-rotate-left"></i>
                           </button>`
                        : `<button class="action-btn mark-read-btn" onclick="executeNotificationAction(event, '${n.id}', 'read')" style="background: none; border: none; color: var(--success-color); cursor: pointer; padding: 2px; font-size: 13px;" title="Mark Read">
                               <i class="fa-solid fa-check"></i>
                           </button>`;
                    
                    div.innerHTML = `
                        <a href="${n.action_url}" class="notification-link" style="text-decoration: none; color: inherit; flex-grow: 1; margin-right: 8px;">
                            <div style="font-weight: 700; color: var(--primary-color); margin-bottom: 2px;">${n.title}</div>
                            <div style="color: var(--text-color);">${n.message}</div>
                            <div style="font-size: 10px; color: var(--text-muted); margin-top: 4px;">${n.created_at_human}</div>
                        </a>
                        <div class="notification-actions" style="display: flex; gap: 6px; align-items: center; margin-top: 2px;">
                            ${actionButton}
                            <button class="action-btn delete-btn" onclick="executeNotificationAction(event, '${n.id}', 'delete')" style="background: none; border: none; color: var(--error-color); cursor: pointer; padding: 2px; font-size: 13px;" title="Delete">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(div);
                });
            } else {
                container.innerHTML = `<div class="no-notifications-placeholder" style="text-align: center; color: var(--text-muted); padding: 20px 0; font-size: 13px;">No notifications.</div>`;
                if (globalActions) {
                    globalActions.innerHTML = '';
                }
            }
        }

        function executeNotificationAction(event, id, action) {
            event.preventDefault();
            event.stopPropagation();
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const url = `/notifications/${action}/${id}`;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    rebuildNotificationList(data.notifications, data.unread_count);
                    showToast(`Notification ${action === 'read' ? 'marked read' : (action === 'unread' ? 'marked unread' : 'deleted')}`, 'success');
                }
            })
            .catch(err => console.error(`Error executing notification ${action}:`, err));
        }

        function markAllNotificationsRead(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch('/notifications/api/read-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    rebuildNotificationList(data.notifications, 0);
                    showToast('All notifications marked as read', 'success');
                }
            })
            .catch(err => console.error('Error marking all read:', err));
        }

        function deleteAllNotifications(event) {
            event.preventDefault();
            event.stopPropagation();
            
            if (!confirm('Are you sure you want to delete all notifications?')) {
                return;
            }
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch('/notifications/delete-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    rebuildNotificationList(data.notifications, data.unread_count);
                    showToast('All notifications deleted', 'success');
                }
            })
            .catch(err => console.error('Error deleting all notifications:', err));
        }

        function pollNotifications() {
            fetch(`/notifications/api/latest?since=${encodeURIComponent(lastNotificationTime)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.unread_count !== undefined) {
                        updateNotificationBadge(data.unread_count);
                    }
                    if (data.notifications && data.notifications.length > 0) {
                        const bellIcon = document.querySelector('.user-avatar i.fa-bell');
                        if (bellIcon) {
                            bellIcon.classList.add('bell-bounce');
                            setTimeout(() => {
                                bellIcon.classList.remove('bell-bounce');
                            }, 800);
                        }

                        const container = document.getElementById('notificationList');
                        if (container) {
                            const placeholder = container.querySelector('.no-notifications-placeholder');
                            if (placeholder) {
                                placeholder.remove();
                            }

                            const globalActions = document.getElementById('globalNotificationActions');
                            if (globalActions && !globalActions.innerHTML.trim()) {
                                globalActions.innerHTML = `
                                    <button id="markAllReadBtn" onclick="markAllNotificationsRead(event)" style="background:none; border:none; color:var(--primary-color); font-size:12px; font-weight:600; cursor:pointer;" title="Mark All Read">
                                        <i class="fa-solid fa-check-double"></i> Mark All Read
                                    </button>
                                    <button id="deleteAllNotificationsBtn" onclick="deleteAllNotifications(event)" style="background:none; border:none; color:var(--error-color); font-size:12px; font-weight:600; cursor:pointer;" title="Delete All">
                                        <i class="fa-solid fa-trash-can"></i> Delete All
                                    </button>
                                `;
                            }

                            data.notifications.forEach(n => {
                                if (container.querySelector(`.notification-item[data-id="${n.id}"]`)) {
                                    return;
                                }
                                const div = document.createElement('div');
                                div.className = 'notification-item unread';
                                div.setAttribute('data-id', n.id);
                                div.style.cssText = 'position: relative; display: flex; align-items: flex-start; justify-content: space-between; padding: 10px; border-radius: 6px; margin-bottom: 6px; font-size: 12.5px; transition: background-color 0.2s ease; background-color: var(--primary-light); font-weight: 600;';
                                div.innerHTML = `
                                    <a href="${n.action_url}" class="notification-link" style="text-decoration: none; color: inherit; flex-grow: 1; margin-right: 8px;">
                                        <div style="font-weight: 700; color: var(--primary-color); margin-bottom: 2px;">${n.title}</div>
                                        <div style="color: var(--text-color);">${n.message}</div>
                                        <div style="font-size: 10px; color: var(--text-muted); margin-top: 4px;">${n.created_at_human}</div>
                                    </a>
                                    <div class="notification-actions" style="display: flex; gap: 6px; align-items: center; margin-top: 2px;">
                                        <button class="action-btn mark-read-btn" onclick="executeNotificationAction(event, '${n.id}', 'read')" style="background: none; border: none; color: var(--success-color); cursor: pointer; padding: 2px; font-size: 13px;" title="Mark Read">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button class="action-btn delete-btn" onclick="executeNotificationAction(event, '${n.id}', 'delete')" style="background: none; border: none; color: var(--error-color); cursor: pointer; padding: 2px; font-size: 13px;" title="Delete">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                `;
                                container.insertBefore(div, container.firstChild);
                            });
                        }
                        lastNotificationTime = data.last_timestamp;
                    }
                })
                .catch(err => console.error('Error polling notifications:', err));
        }

        setInterval(pollNotifications, 12000);
        
        // Toast helper
        function showToast(message, type = 'success', timeout = 4000) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const t = document.createElement('div');
            t.className = 'toast ' + (type === 'error' ? 'error' : 'success');
            t.innerHTML = '<i class="fa-solid ' + (type === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check') + '"></i><span style="font-weight:700;">' + message + '</span>';
            container.appendChild(t);
            // show
            requestAnimationFrame(() => t.classList.add('show'));
            // remove after timeout
            setTimeout(() => {
                t.classList.remove('show');
                setTimeout(() => t.remove(), 300);
            }, timeout);
        }

        // If server set session flash, show toast on load
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast("{{ str_replace('"', '\\"', session('success')) }}", 'success');
            @endif

            @if(session('error'))
                showToast("{{ str_replace('"', '\\"', session('error')) }}", 'error');
            @endif

            @if($errors->any())
                @foreach($errors->all() as $error)
                    showToast("{{ str_replace('"', '\\"', $error) }}", 'error');
                @endforeach
            @endif

            // Global delete confirmation flow for forms with .confirm-delete-form
            (function() {
                const overlay = document.getElementById('confirmOverlay');
                const confirmMsg = overlay ? overlay.querySelector('.confirm-message') : null;
                const btnOk = document.getElementById('confirmOk');
                const btnCancel = document.getElementById('confirmCancel');
                let pendingForm = null;

                function showConfirm(form) {
                    pendingForm = form;
                    const name = form.dataset.username || '';
                    if (confirmMsg) confirmMsg.textContent = name ? `Are you sure you want to remove "${name}"?` : 'Are you sure you want to remove this item?';
                    if (overlay) overlay.style.display = 'flex';
                }

                function hideConfirm() {
                    if (overlay) overlay.style.display = 'none';
                    pendingForm = null;
                }

                document.querySelectorAll('.confirm-delete-form').forEach(function(form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        showConfirm(form);
                    });
                });

                if (btnCancel) btnCancel.addEventListener('click', hideConfirm);
                if (overlay) overlay.addEventListener('click', function(e) { if (e.target === overlay) hideConfirm(); });
                if (btnOk) btnOk.addEventListener('click', function() {
                    if (pendingForm) pendingForm.submit();
                });
            })();
        });
    </script>
    @yield('scripts')
</body>
</html>