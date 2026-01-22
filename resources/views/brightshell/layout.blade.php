<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - BrightShell ERP</title>
    
    @php
        $faviconPath = public_path('media/brightshell/favicon.png');
        $favicon = file_exists($faviconPath) ? asset('media/brightshell/favicon.png') : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/png" href="{{ $favicon }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:300,400,500,600,700" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bs-bg: #0a0e1a;
            --bs-bg-dark: #050810;
            --bs-bg-card: #0f1420;
            --bs-bg-hover: #141a28;
            --bs-text: #ffffff;
            --bs-text-muted: #8b9dc3;
            --bs-accent: #5bbce4;
            --bs-accent-hover: #7dcbeb;
            --bs-border: rgba(91, 188, 228, 0.15);
            --bs-success: #10b981;
            --bs-warning: #f59e0b;
            --bs-danger: #ef4444;
        }
        
        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--bs-bg) 0%, var(--bs-bg-dark) 100%);
            color: var(--bs-text);
            min-height: 100vh;
            display: flex;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--bs-bg-card);
            border-right: 1px solid var(--bs-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 999;
            display: none;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--bs-border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        
        .sidebar-brand {
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--bs-accent);
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }
        
        .nav-section {
            padding: 0.5rem 1.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--bs-text-muted);
            margin-top: 1rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--bs-text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover {
            background: var(--bs-bg-hover);
            color: var(--bs-text);
        }
        
        .nav-link.active {
            background: rgba(91, 188, 228, 0.1);
            color: var(--bs-accent);
            border-left-color: var(--bs-accent);
        }
        
        .nav-link svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--bs-border);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.85rem;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--bs-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        /* Main content */
        .main {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
        }
        
        .topbar {
            background: var(--bs-bg-card);
            border-bottom: 1px solid var(--bs-border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
            gap: 1rem;
        }
        
        .burger-menu {
            display: none;
            background: none;
            border: none;
            color: var(--bs-text);
            cursor: pointer;
            padding: 0.5rem;
        }
        
        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .content {
            padding: 2rem;
        }
        
        /* Cards */
        .card {
            background: var(--bs-bg-card);
            border: 1px solid var(--bs-border);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s;
        }
        
        .card:hover {
            border-color: rgba(91, 188, 228, 0.3);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--bs-border);
        }
        
        .card-title {
            font-size: 1rem;
            font-weight: 600;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--bs-bg-card);
            border: 1px solid var(--bs-border);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--bs-accent), transparent);
        }
        
        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--bs-text-muted);
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .stat-value.success { color: var(--bs-success); }
        .stat-value.warning { color: var(--bs-warning); }
        .stat-value.danger { color: var(--bs-danger); }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 8px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--bs-accent);
            color: var(--bs-bg);
        }
        
        .btn-primary:hover {
            background: var(--bs-accent-hover);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--bs-text-muted);
            border-color: var(--bs-border);
        }
        
        .btn-secondary:hover {
            background: var(--bs-bg-hover);
            color: var(--bs-text);
        }
        
        .btn-success {
            background: var(--bs-success);
            color: white;
        }
        
        .btn-danger {
            background: var(--bs-danger);
            color: white;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
            border: 1px solid var(--bs-border);
            border-radius: 12px;
            background: var(--bs-bg-card);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--bs-border);
        }
        
        th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--bs-text-muted);
            font-weight: 600;
            background: var(--bs-bg-dark);
        }
        
        tr:hover {
            background: var(--bs-bg-hover);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--bs-text-muted);
            margin-bottom: 0.5rem;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: var(--bs-text);
            background: var(--bs-bg-dark);
            border: 1px solid var(--bs-border);
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--bs-accent);
            box-shadow: 0 0 0 3px rgba(91, 188, 228, 0.15);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        /* Placeholders */
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: var(--bs-text-muted);
            opacity: 0.7;
        }
        
        /* Number : masquer les spinners */
        .form-input[type="number"] {
            -moz-appearance: textfield;
        }
        .form-input[type="number"]::-webkit-outer-spin-button,
        .form-input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        /* Date / time : color-scheme pour pickers natifs */
        .form-input[type="date"],
        .form-input[type="time"],
        .form-input[type="datetime-local"] {
            color-scheme: dark;
        }
        .form-input[type="date"]::-webkit-calendar-picker-indicator,
        .form-input[type="time"]::-webkit-calendar-picker-indicator,
        .form-input[type="datetime-local"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.7;
            cursor: pointer;
        }
        
        /* Select : flèche custom, options sombres */
        .form-select,
        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%238b9dc3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.25rem;
            padding-right: 2.5rem;
        }
        select.form-input option,
        .form-select option {
            background: var(--bs-bg-dark);
            color: var(--bs-text);
        }
        
        /* File input */
        .form-input[type="file"] {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        .form-input[type="file"]::file-selector-button {
            padding: 0.5rem 1rem;
            margin-right: 1rem;
            background: var(--bs-bg-hover);
            border: 1px solid var(--bs-border);
            border-radius: 6px;
            color: var(--bs-text-muted);
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .form-input[type="file"]::file-selector-button:hover {
            background: rgba(91, 188, 228, 0.15);
            color: var(--bs-accent);
            border-color: var(--bs-accent);
        }
        .form-input[type="file"]::-webkit-file-upload-button {
            padding: 0.5rem 1rem;
            margin-right: 1rem;
            background: var(--bs-bg-hover);
            border: 1px solid var(--bs-border);
            border-radius: 6px;
            color: var(--bs-text-muted);
            font-size: 0.875rem;
            cursor: pointer;
        }
        .form-input[type="file"]::-webkit-file-upload-button:hover {
            background: rgba(91, 188, 228, 0.15);
            color: var(--bs-accent);
        }
        
        /* Checkbox / Radio (quand utilisés avec form-input ou en dehors) */
        input[type="checkbox"],
        input[type="radio"] {
            accent-color: var(--bs-accent);
            width: 1.125rem;
            height: 1.125rem;
            cursor: pointer;
        }
        
        /* Désactivé */
        .form-input:disabled,
        .form-select:disabled,
        .form-textarea:disabled,
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Search : reset bouton clear */
        .form-input[type="search"]::-webkit-search-cancel-button {
            filter: invert(1);
            opacity: 0.6;
            cursor: pointer;
        }
        
        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 9999px;
        }
        
        .badge-success { background: rgba(16, 185, 129, 0.15); color: var(--bs-success); }
        .badge-warning { background: rgba(245, 158, 11, 0.15); color: var(--bs-warning); }
        .badge-danger { background: rgba(239, 68, 68, 0.15); color: var(--bs-danger); }
        .badge-info { background: rgba(91, 188, 228, 0.15); color: var(--bs-accent); }
        
        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--bs-success);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--bs-danger);
        }
        
        /* Grid */
        .grid { display: grid; gap: 1.5rem; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }
        
        @media (max-width: 1024px) {
            .grid-3, .grid-4 { grid-template-columns: repeat(2, 1fr); }
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .main { margin-left: 0; }
            .topbar { padding: 0.75rem 1rem; }
            .burger-menu { display: block; }
            .content { padding: 1rem; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .page-title { font-size: 1.1rem; }
            
            /* Table mobile styles */
            .table-container {
                border: none;
                background: transparent;
            }
            
            table, thead, tbody, th, td, tr {
                display: block;
            }
            
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            tr {
                background: var(--bs-bg-card);
                border: 1px solid var(--bs-border);
                border-radius: 12px;
                margin-bottom: 1rem;
                padding: 0.5rem;
            }
            
            td {
                border: none;
                border-bottom: 1px solid rgba(255,255,255,0.05);
                position: relative;
                padding-left: 50%;
                text-align: right;
                min-height: 3rem;
                display: flex;
                align-items: center;
                justify-content: flex-end;
            }
            
            td:last-child {
                border-bottom: none;
            }
            
            td::before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: var(--bs-text-muted);
                font-size: 0.75rem;
                text-transform: uppercase;
            }
        }
        
        /* Utilities */
        .text-muted { color: var(--bs-text-muted); }
        .text-success { color: var(--bs-success); }
        .text-warning { color: var(--bs-warning); }
        .text-danger { color: var(--bs-danger); }
        .text-accent { color: var(--bs-accent); }
        .text-sm { font-size: 0.875rem; }
        .text-xs { font-size: 0.75rem; }
        .font-bold { font-weight: 700; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .mb-4 { margin-bottom: 1rem; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: 0.5rem; }
        .gap-4 { gap: 1rem; }
        
        /* Progress bars */
        .progress {
            height: 8px;
            background: var(--bs-bg-dark);
            border-radius: 9999px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.5s ease;
            background: linear-gradient(90deg, var(--bs-accent), var(--bs-accent-hover));
        }
        
        .progress-bar.warning {
            background: linear-gradient(90deg, var(--bs-warning), #fbbf24);
        }
        
        .progress-bar.danger {
            background: linear-gradient(90deg, var(--bs-danger), #f87171);
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--bs-text-muted);
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.5rem;
            opacity: 0.5;
        }
        
        /* Scrollbars */
        html {
            scrollbar-width: thin;
            scrollbar-color: rgba(91, 188, 228, 0.4) var(--bs-bg-dark);
        }
        body,
        .sidebar-nav,
        .table-container {
            scrollbar-width: thin;
            scrollbar-color: rgba(91, 188, 228, 0.4) var(--bs-bg-dark);
        }
        body::-webkit-scrollbar,
        .sidebar-nav::-webkit-scrollbar {
            width: 8px;
        }
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        body::-webkit-scrollbar-track,
        .sidebar-nav::-webkit-scrollbar-track,
        .table-container::-webkit-scrollbar-track {
            background: var(--bs-bg-dark);
            border-radius: 4px;
        }
        body::-webkit-scrollbar-thumb,
        .sidebar-nav::-webkit-scrollbar-thumb,
        .table-container::-webkit-scrollbar-thumb {
            background: rgba(91, 188, 228, 0.35);
            border-radius: 4px;
        }
        body::-webkit-scrollbar-thumb:hover,
        .sidebar-nav::-webkit-scrollbar-thumb:hover,
        .table-container::-webkit-scrollbar-thumb:hover {
            background: rgba(91, 188, 228, 0.55);
        }
        .table-container::-webkit-scrollbar-corner {
            background: var(--bs-bg-dark);
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            @php
                $logoPath = public_path('media/brightshell/logo.png');
                $hasLogo = file_exists($logoPath);
            @endphp
            @if($hasLogo)
                <img src="{{ asset('media/brightshell/logo.png') }}" alt="Logo" class="sidebar-logo">
            @else
                <svg class="sidebar-logo" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 5L25 15H15L20 5Z" fill="#5bbce4"/>
                    <path d="M20 15L30 25H10L20 15Z" fill="#5bbce4" opacity="0.8"/>
                    <path d="M20 25L35 35H5L20 25Z" fill="#5bbce4" opacity="0.6"/>
                </svg>
            @endif
            <span class="sidebar-brand">BrightShell</span>
        </div>
        
        <nav class="sidebar-nav">
            <a href="{{ route('brightshell.index') }}" class="nav-link {{ request()->routeIs('brightshell.index') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Dashboard
            </a>
            
            <div class="nav-section">CRM</div>
            <a href="{{ route('brightshell.clients') }}" class="nav-link {{ request()->routeIs('brightshell.clients*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Clients
            </a>
            
            <div class="nav-section">Commercial</div>
            <a href="{{ route('brightshell.devis') }}" class="nav-link {{ request()->routeIs('brightshell.devis*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Devis
            </a>
            <a href="{{ route('brightshell.factures') }}" class="nav-link {{ request()->routeIs('brightshell.factures*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Factures
            </a>
            <a href="{{ route('brightshell.legals') }}" class="nav-link {{ request()->routeIs('brightshell.legals*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Legals
            </a>
            
            <div class="nav-section">Gestion</div>
            <a href="{{ route('brightshell.projets') }}" class="nav-link {{ request()->routeIs('brightshell.projets*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Projets
            </a>
            <a href="{{ route('brightshell.taches') }}" class="nav-link {{ request()->routeIs('brightshell.taches*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Tâches
            </a>
            <a href="{{ route('brightshell.notes') }}" class="nav-link {{ request()->routeIs('brightshell.notes*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Notes
            </a>
            <a href="{{ route('brightshell.agenda') }}" class="nav-link {{ request()->routeIs('brightshell.agenda*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Agenda
            </a>
            <a href="{{ route('brightshell.documents') }}" class="nav-link {{ request()->routeIs('brightshell.documents*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                Documents
            </a>
            
            <div class="nav-section">Finances</div>
            <a href="{{ route('brightshell.ressources') }}" class="nav-link {{ request()->routeIs('brightshell.ressources*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ressources
            </a>
            <a href="{{ route('brightshell.comptabilite') }}" class="nav-link {{ request()->routeIs('brightshell.comptabilite*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Comptabilité
            </a>
            <a href="{{ route('brightshell.fournisseurs') }}" class="nav-link {{ request()->routeIs('brightshell.fournisseurs*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Fournisseurs
            </a>
            <a href="{{ route('brightshell.statistiques') }}" class="nav-link {{ request()->routeIs('brightshell.statistiques*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Statistiques
            </a>
            <a href="{{ route('brightshell.exports') }}" class="nav-link {{ request()->routeIs('brightshell.exports*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Exports
            </a>
            
            <div class="nav-section">Communication</div>
            <a href="{{ route('brightshell.mailing') }}" class="nav-link {{ request()->routeIs('brightshell.mailing*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Mailing
            </a>
            
            <div class="nav-section">Système</div>
            <a href="{{ route('brightshell.settings') }}" class="nav-link {{ request()->routeIs('brightshell.settings') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Paramètres
            </a>
            <a href="{{ route('admin.index') }}" class="nav-link" style="opacity: 0.6;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Admin Allotata
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">LE</div>
                <div>
                    <div class="text-sm font-bold">Lucas Espinar</div>
                    <div class="text-xs text-muted">Admin</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <main class="main">
        <header class="topbar">
            <div class="flex items-center gap-2">
                <button class="burger-menu" onclick="toggleSidebar()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </button>
                <h1 class="page-title">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-2">
                @yield('actions')
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Déconnexion</button>
                </form>
            </div>
        </header>
        
        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-error">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ session('error') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @yield('content')
        </div>
    </main>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebar-overlay').classList.toggle('open');
        }
    </script>
    @stack('scripts')
</body>
</html>
