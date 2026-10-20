<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'PIOS - Business Owner' }}</title>
    <meta name="description" content="PIOS BizGrowth Engine Business Owner Panel">

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

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Global Business Theme Controller (Early Load in Head) -->
    <script>
        (function () {
            // Apply initial theme immediately to prevent flashing
            try {
                var saved = localStorage.getItem('pios_theme');
                if (saved === 'dark') {
                    document.documentElement.classList.add('dark');
                    if (document.body) {
                        document.body.classList.add('dark-mode-active');
                    } else {
                        document.addEventListener('DOMContentLoaded', function () {
                            if (document.body) document.body.classList.add('dark-mode-active');
                        });
                    }
                } else if (saved === 'light') {
                    document.documentElement.classList.remove('dark');
                    if (document.body) {
                        document.body.classList.remove('dark-mode-active');
                    }
                }
            } catch (e) {}
        })();

        // Core Theme Functions (Available immediately to inline onclick handlers)
        window.applyPiosTheme = function (isDark) {
            var dark = Boolean(isDark);

            // 1. Update Body class
            if (document.body) {
                if (dark) {
                    document.body.classList.add('dark-mode-active');
                } else {
                    document.body.classList.remove('dark-mode-active');
                }
            }

            // 2. Update HTML class
            if (document.documentElement) {
                if (dark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }

            // 3. Save to localStorage
            try {
                localStorage.setItem('pios_theme', dark ? 'dark' : 'light');
            } catch (e) {}

            // 4. Sync mobile toggle switch if present
            var fsDark = document.getElementById('fsDarkModeToggle');
            if (fsDark) {
                fsDark.checked = dark;
            }

            // 5. Sync title/aria-label for desktop theme buttons
            var btns = document.querySelectorAll('#desktopThemeToggleBtn, .theme-toggle-btn');
            btns.forEach(function (btn) {
                btn.setAttribute('title', dark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
                btn.setAttribute('aria-label', dark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
            });
        };

        window.togglePiosTheme = function (e) {
            if (e) {
                if (typeof e.preventDefault === 'function') e.preventDefault();
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
            }

            var isDark = false;
            if (document.body && document.body.classList.contains('dark-mode-active')) {
                isDark = true;
            } else if (document.documentElement && document.documentElement.classList.contains('dark')) {
                isDark = true;
            }
            window.applyPiosTheme(!isDark);
        };

        // Attach listeners when DOM is ready
        function setupPiosThemeListeners() {
            try {
                var saved = localStorage.getItem('pios_theme');
                if (saved === 'dark') {
                    window.applyPiosTheme(true);
                } else if (saved === 'light') {
                    window.applyPiosTheme(false);
                }
            } catch (e) {}

            // Direct binding to all desktop buttons
            var btns = document.querySelectorAll('#desktopThemeToggleBtn, .theme-toggle-btn');
            btns.forEach(function (btn) {
                btn.onclick = function (e) {
                    window.togglePiosTheme(e);
                };
            });

            // Mobile switch
            var fsDark = document.getElementById('fsDarkModeToggle');
            if (fsDark) {
                fsDark.onchange = function (e) {
                    window.applyPiosTheme(e.target.checked);
                };
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupPiosThemeListeners);
        } else {
            setupPiosThemeListeners();
        }

        // Delegation fallback using safe parentNode traversal (no reliance on Element.closest for SVG)
        document.addEventListener('click', function (e) {
            var el = e.target;
            while (el && el !== document && el !== document.body) {
                if (el.id === 'desktopThemeToggleBtn' || (el.classList && el.classList.contains('theme-toggle-btn'))) {
                    window.togglePiosTheme(e);
                    return;
                }
                el = el.parentElement || el.parentNode;
            }
        });

        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'fsDarkModeToggle') {
                window.applyPiosTheme(e.target.checked);
            }
        });
    </script>

    <!-- Business Owner Unified Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/bo-style-v3.css') }}?v={{ time() }}">
    <style>
        :root {
            --dash-desk-bg: #F1F5F9;
            --dash-sidebar-bg: #134C49;
            --dash-mob-header: #134C49;
            --dash-mob-menu-bg: #134C49;
            --color-teal: #0D9488;
            --color-green: #059669;
        }

        /* Action buttons standard teal theme */
        .btn-add-customer,
        .btn-new-product-desktop {
            background: #0D9488 !important;
            background-color: #0D9488 !important;
            background-image: none !important;
            color: #FFFFFF !important;
        }
        .btn-add-customer:hover,
        .btn-new-product-desktop:hover {
            background: #0F766E !important;
            background-color: #0F766E !important;
            background-image: none !important;
        }

        /* Ensure theme toggle button is always interactive, on top, and SVG children ignore pointer events */
        .theme-toggle-btn {
            cursor: pointer !important;
            pointer-events: auto !important;
            position: relative !important;
            z-index: 99 !important;
            user-select: none !important;
        }
        .theme-toggle-btn * {
            pointer-events: none !important;
        }

        /* Force remove all underlines under text and enable clean clickable card links */
        a, a:hover, a:focus, a:visited, a:active {
            text-decoration: none !important;
        }

        .kpi-card, a.kpi-card {
            text-decoration: none !important;
            color: inherit !important;
            display: block;
            cursor: pointer;
        }

        .kpi-card *, a.kpi-card * {
            text-decoration: none !important;
        }

        .sidebar-item, .fs-nav-item, .view-all-link, .activity-row, .pipeline-item {
            text-decoration: none !important;
        }

        /* Responsive Navigation System */
        @media (min-width: 992px) {
            .mobile-dash-header,
            .mobile-customer-header,
            .mobile-prod-header,
            .mobile-tx-header,
            .mobile-transaction-header,
            .mobile-fees-header,
            .mobile-reports-header,
            .mobile-header,
            .fullscreen-menu {
                display: none !important;
            }
            .desktop-sidebar {
                display: flex !important;
            }
            .desktop-only {
                display: block !important;
            }
        }

        @media (max-width: 991px) {
            .desktop-sidebar,
            .desktop-only {
                display: none !important;
            }
            .mobile-dash-header,
            .mobile-customer-header,
            .mobile-prod-header,
            .mobile-tx-header,
            .mobile-transaction-header,
            .mobile-fees-header,
            .mobile-reports-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                height: 60px !important;
                padding: 0 16px !important;
                background-color: #134C49 !important;
                color: #FFFFFF !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 100 !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .mobile-menu-toggle-btn,
            .mobile-hamburger-btn {
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
            .dash-layout,
            .cust-layout,
            .prod-layout,
            .trans-layout,
            .tx-layout,
            .fees-layout,
            .reports-layout {
                display: block !important;
                width: 100% !important;
            }
            .dash-main,
            .cust-main,
            .prod-main,
            .trans-main,
            .tx-main,
            .fees-main,
            .reports-main {
                width: 100% !important;
                padding: 16px !important;
                box-sizing: border-box !important;
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
    @stack('styles')
</head>

<body class="{{ $bodyClass ?? 'dashboard-body' }}">

    <!-- Mobile Top Header (Shown only on Mobile < 992px) -->
    <x-business.mobile-header :title="$headerTitle ?? 'Dashboard'" :customHeaderClass="$mobileHeaderClass ?? 'mobile-dash-header'" />

    <!-- Fullscreen Mobile Navigation Menu -->
    <x-business.fullscreen-menu />

    <div class="dash-layout">
        <!-- Desktop Sidebar -->
        <x-business.sidebar />

        <!-- Main Content Area -->
        <main class="{{ $mainClass ?? 'dash-main' }}">
            {{ $slot }}
        </main>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/dashboard.js') }}?v={{ file_exists(public_path('assets/js/dashboard.js')) ? filemtime(public_path('assets/js/dashboard.js')) : time() }}"></script>
    <script>
        // Mobile menu clean handler
        (function () {
            const toggleBtn = document.getElementById('mobileMenuToggleBtn');
            if (toggleBtn) {
                toggleBtn.onclick = function (e) {
                    window.togglePiosMenu(e);
                };
            }
            const closeBtn = document.getElementById('fsMenuCloseBtn');
            if (closeBtn) {
                closeBtn.onclick = function (e) {
                    window.closePiosMenu(e);
                };
            }
        })();
    </script>
    @stack('scripts')

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js')
                    .then(function (reg) { console.log('PIOS PWA registered:', reg.scope); })
                    .catch(function (err) { console.log('PIOS PWA registration failed:', err); });
            });
        }
    </script>
</body>

</html>
