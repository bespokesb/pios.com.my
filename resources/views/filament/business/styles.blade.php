<!-- PWA Meta Tags -->
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="theme-color" content="#134C49">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="PIOS Business">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

<link rel="stylesheet" href="{{ asset('assets/css/bo-style-v3.css') }}?v={{ time() }}">
<style>
    /* Force remove all underlines under text and enable clean clickable card links */
    a, a:hover, a:focus, a:visited, a:active {
        text-decoration: none !important;
    }

    /* Mobile Responsive Header & Fullscreen Menu for Business Panel */
    @media (max-width: 991px) {
        .fi-topbar {
            display: none !important;
        }

        .mobile-dash-header,
        .mobile-customer-header,
        .mobile-prod-header,
        .mobile-tx-header,
        .mobile-fees-header,
        .mobile-reports-header,
        .mobile-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            height: 60px !important;
            padding: 0 16px !important;
            background-color: #134C49 !important;
            color: #FFFFFF !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 9999 !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .mobile-dash-left, .mobile-header-left {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
        }

        .mobile-dash-title, .mobile-header-title {
            font-size: 1.15rem !important;
            font-weight: 700 !important;
            color: #FFFFFF !important;
            font-family: 'Poppins', sans-serif !important;
            margin: 0 !important;
        }

        .back-btn, .mobile-back-btn {
            color: #FFFFFF !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
            background: transparent !important;
            border: none !important;
            cursor: pointer !important;
        }

        .mobile-menu-toggle-btn, .mobile-hamburger-btn {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: transparent !important;
            border: none !important;
            color: #FFFFFF !important;
            cursor: pointer !important;
            padding: 8px !important;
            border-radius: 6px !important;
        }

        .fullscreen-menu {
            position: fixed !important;
            inset: 0 !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background-color: #134C49 !important;
            background: #134C49 !important;
            z-index: 999999 !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            padding: 20px 24px 32px !important;
            box-sizing: border-box !important;
            overflow-y: auto !important;
            display: none !important;
            opacity: 0 !important;
            pointer-events: none !important;
            transform: translateY(-100%) !important;
            transition: transform 0.28s ease, opacity 0.28s ease !important;
        }

        .fullscreen-menu.active,
        .fullscreen-menu.open {
            display: flex !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            transform: none !important;
            visibility: visible !important;
        }

        .fs-menu-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding-bottom: 16px !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
            width: 100% !important;
        }

        .fs-header-logo {
            height: 36px !important;
            width: auto !important;
            display: block !important;
        }

        .fs-menu-close-btn {
            background: transparent !important;
            border: none !important;
            color: #FFFFFF !important;
            cursor: pointer !important;
            padding: 6px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .fs-nav-list {
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
            margin: 20px 0 !important;
            flex: 1 !important;
        }

        .fs-nav-item {
            display: flex !important;
            align-items: center !important;
            gap: 14px !important;
            padding: 12px 14px !important;
            border-radius: 10px !important;
            color: rgba(255, 255, 255, 0.85) !important;
            text-decoration: none !important;
            font-weight: 500 !important;
            font-size: 1rem !important;
            transition: all 0.2s ease !important;
        }

        .fs-nav-item:hover,
        .fs-nav-item.active {
            background-color: rgba(255, 255, 255, 0.12) !important;
            color: #FFFFFF !important;
        }

        .fs-nav-item.active {
            background-color: rgba(20, 184, 166, 0.25) !important;
            color: #5EEAD4 !important;
            font-weight: 600 !important;
        }

        .fs-menu-footer {
            display: flex !important;
            flex-direction: column !important;
            gap: 16px !important;
            padding-top: 20px !important;
            border-top: 1px solid rgba(255, 255, 255, 0.12) !important;
            width: 100% !important;
        }

        .fs-user-profile,
        .fs-user-card {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            width: 100% !important;
            gap: 12px !important;
        }

        .fs-user-left {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            flex: 1 !important;
            min-width: 0 !important;
        }

        .fs-user-avatar,
        .fs-avatar-img {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            border-radius: 50% !important;
            object-fit: cover !important;
            border: 2px solid rgba(255, 255, 255, 0.25) !important;
            display: block !important;
        }

        .fs-user-info {
            flex: 1 !important;
            min-width: 0 !important;
        }

        .fs-user-name {
            font-size: 1rem !important;
            font-weight: 600 !important;
            color: #FFFFFF !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: block !important;
            margin: 0 !important;
        }

        .fs-logout-form {
            display: inline-flex !important;
            align-items: center !important;
            margin: 0 !important;
            padding: 0 !important;
            flex-shrink: 0 !important;
        }

        .fs-logout-btn {
            background: transparent !important;
            border: none !important;
            color: rgba(255, 255, 255, 0.8) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 8px !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        .fs-logout-btn:hover {
            color: #EF4444 !important;
            background-color: rgba(239, 68, 68, 0.15) !important;
        }
    }

    @media (min-width: 992px) {
        .mobile-dash-header,
        .mobile-customer-header,
        .mobile-prod-header,
        .mobile-tx-header,
        .mobile-fees-header,
        .mobile-reports-header,
        .mobile-header,
        .fullscreen-menu {
            display: none !important;
        }

        /* Desktop Sidebar Theme for Business Panel */
        .fi-sidebar {
            background-color: #134C49 !important;
            background: #134C49 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .fi-sidebar-header {
            background-color: #0E3B38 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .fi-sidebar-nav {
            padding: 12px 10px !important;
        }

        .fi-sidebar-item-btn {
            color: rgba(255, 255, 255, 0.85) !important;
            border-radius: 8px !important;
            transition: all 0.2s ease !important;
        }

        .fi-sidebar-item-btn:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #FFFFFF !important;
        }

        .fi-sidebar-item-active .fi-sidebar-item-btn {
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.3), rgba(13, 148, 136, 0.2)) !important;
            color: #5EEAD4 !important;
            border-left: 3px solid #14B8A6 !important;
            font-weight: 600 !important;
        }

        .fi-sidebar-group-label {
            color: rgba(255, 255, 255, 0.5) !important;
            text-transform: uppercase !important;
            font-size: 0.72rem !important;
            letter-spacing: 0.05em !important;
        }
    }
</style>
<script>
    window.openPiosMenu = function (e) {
        if (e) {
            if (typeof e.preventDefault === 'function') e.preventDefault();
            if (typeof e.stopPropagation === 'function') e.stopPropagation();
        }
        const menu = document.getElementById('fullscreenMenu');
        const toggleBtn = document.getElementById('mobileMenuToggleBtn');
        if (menu) {
            menu.classList.add('active', 'open');
            menu.style.setProperty('display', 'flex', 'important');
            menu.style.setProperty('opacity', '1', 'important');
            menu.style.setProperty('pointer-events', 'auto', 'important');
            menu.style.setProperty('transform', 'none', 'important');
            menu.style.setProperty('visibility', 'visible', 'important');
            menu.style.setProperty('z-index', '9999999', 'important');
            menu.setAttribute('aria-hidden', 'false');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closePiosMenu = function (e) {
        if (e) {
            if (typeof e.preventDefault === 'function') e.preventDefault();
            if (typeof e.stopPropagation === 'function') e.stopPropagation();
        }
        const menu = document.getElementById('fullscreenMenu');
        const toggleBtn = document.getElementById('mobileMenuToggleBtn');
        if (menu) {
            menu.classList.remove('active', 'open');
            menu.style.setProperty('display', 'none', 'important');
            menu.style.setProperty('opacity', '0', 'important');
            menu.style.setProperty('pointer-events', 'none', 'important');
            menu.style.setProperty('transform', 'translateY(-100%)', 'important');
            menu.setAttribute('aria-hidden', 'true');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
    };

    window.togglePiosMenu = function (e) {
        if (e) {
            if (typeof e.preventDefault === 'function') e.preventDefault();
            if (typeof e.stopPropagation === 'function') e.stopPropagation();
        }
        const menu = document.getElementById('fullscreenMenu');
        if (!menu) return;
        const isOpen = menu.classList.contains('active') ||
                       menu.classList.contains('open') ||
                       menu.style.display === 'flex' ||
                       menu.getAttribute('aria-hidden') === 'false';
        if (isOpen) {
            window.closePiosMenu(e);
        } else {
            window.openPiosMenu(e);
        }
    };
</script>
