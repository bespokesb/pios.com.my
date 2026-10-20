<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PIOS - Log Masuk | Owner, Partner, Business Owner & Pelanggan</title>
    <meta name="description" content="Log masuk portal PIOS untuk Owner, Partner, Business Owner dan Pelanggan.">
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#134C49">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIOS">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Main Responsive Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/style-v2.css') }}?v={{ time() }}">
    <style>
        :root {
            --color-desktop-bg: #134C49 !important;
            --color-desktop-card: #0E3B38 !important;
            --color-desktop-primary: #14B8A6 !important;
            --color-desktop-primary-hover: #0D9488 !important;
            --color-mobile-bg: #134C49 !important;
            --color-mobile-drawer: #0E3B38 !important;
            --color-mobile-primary: #14B8A6 !important;
            --color-mobile-primary-hover: #0D9488 !important;
        }
        body {
            background-color: #134C49 !important;
            background: linear-gradient(135deg, #134C49 0%, #0E3B38 50%, #165B57 100%) !important;
            color: #FFFFFF !important;
        }
        .login-wrapper {
            background: transparent !important;
        }
        .submit-btn {
            background-color: #14B8A6 !important;
        }
        .submit-btn:hover {
            background-color: #0D9488 !important;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #134C49 0%, #0E3B38 50%, #165B57 100%) !important; background-color: #134C49 !important;">

    <!-- Mobile Top Navigation Header (Shown on Mobile screens) -->
    <header class="mobile-header" id="mobileHeader">
        <div class="mobile-header-inner">
            <a href="{{ url('/') }}" class="mobile-logo-link" aria-label="PIOS Homepage" style="display: flex; align-items: center; text-decoration: none;">
                <div style="position: relative; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 9999px; border: 2px solid #E7C46A; background: #16436E; padding: 2px; margin-right: 10px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                    <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" style="width: 100%; height: 100%; border-radius: 9999px; object-fit: contain;">
                </div>
                <div style="display: flex; flex-direction: column;">
                    <span style="font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.2rem; line-height: 1; color: #FFFFFF; letter-spacing: 0.5px;">pios</span>
                    <span style="font-family: 'Inter', sans-serif; font-size: 8px; font-weight: 700; letter-spacing: 1.2px; color: #E7C46A; text-transform: uppercase; margin-top: 2px;">BizGrowth Engine</span>
                </div>
            </a>
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Navigation Menu" aria-expanded="false" aria-controls="mobileDrawer">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>
        </div>
    </header>

    <!-- Mobile Fullscreen Menu -->
    <div class="drawer-overlay" id="drawerOverlay" aria-hidden="true"></div>
    <aside class="mobile-drawer" id="mobileDrawer" aria-hidden="true">
        <div class="drawer-header">
            <div class="drawer-logo-container">
                <a href="{{ url('/') }}" style="display: flex; align-items: center; text-decoration: none;">
                    <div style="position: relative; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 9999px; border: 2px solid #E7C46A; background: #16436E; padding: 2px; margin-right: 10px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                        <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" style="width: 100%; height: 100%; border-radius: 9999px; object-fit: contain;">
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.25rem; line-height: 1; color: #FFFFFF; letter-spacing: 0.5px;">pios</span>
                        <span style="font-family: 'Inter', sans-serif; font-size: 8px; font-weight: 700; letter-spacing: 1.2px; color: #E7C46A; text-transform: uppercase; margin-top: 2px;">BizGrowth Engine</span>
                    </div>
                </a>
            </div>
            <button class="drawer-close-btn" id="drawerCloseBtn" aria-label="Close Navigation Menu">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Guest Mobile Drawer: Only Dark Mode toggle at the bottom -->
        <div class="drawer-footer" style="margin-top: auto;">
            <div class="dark-mode-row">
                <div class="dark-mode-label">
                    <svg class="fs-icon-moon moon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <svg class="fs-icon-sun sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <span>Dark mode</span>
                </div>
                <label class="switch">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
    </aside>

    <!-- Main Container (Desktop 2-column & Mobile 1-column layout) -->
    <main class="login-wrapper">
        <div class="login-container">
            
            <!-- Left Side: Hero Card (Visible on Desktop) -->
            <section class="login-hero-card" aria-label="PIOS Business Overview">
                <img src="{{ asset('assets/images/hero_card.png') }}" alt="RUN Your PIOS Business" class="hero-image">
            </section>

            <!-- Right Side: Login Form (Adaptive for Desktop & Mobile) -->
            <section class="login-form-card" aria-label="PIOS Login Form">
                
                <!-- Center Mascot Logo -->
                <div class="brand-logo-wrapper">
                    <img src="{{ asset('assets/images/desktop_center_logo.png') }}" alt="PIOS BizGrowth Engine" class="center-logo desktop-only">
                    <img src="{{ asset('assets/images/center_logo.png') }}" alt="PIOS BizGrowth Engine" class="center-logo mobile-only">
                </div>

                @if ($errors->any())
                    <div style="background-color: #FEF2F2; border: 1px solid #FCA5A5; color: #B91C1C; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 0.88rem;">
                        <div style="font-weight: 600; margin-bottom: 4px;">Log Masuk Tidak Berjaya:</div>
                        <ul style="margin: 0; padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Interactive Login Form -->
                <form class="login-form" id="loginForm" method="POST" action="{{ route('login.submit') }}" autocomplete="on" novalidate>
                    @csrf
                    
                    <!-- PIOS ID Input Group -->
                    <div class="form-group">
                        <label for="piosId" class="form-label">
                            PIOS ID<span class="required-mark">*</span>
                        </label>
                        <div class="input-container">
                            <input 
                                type="text" 
                                id="piosId" 
                                name="piosId" 
                                class="form-input" 
                                placeholder="Enter your ID / Phone / Email" 
                                value="{{ old('piosId') }}"
                                required 
                                autocomplete="username"
                                aria-required="true"
                            >
                        </div>
                        @error('piosId')
                            <div class="field-error" style="display: block; color: #EF4444; font-size: 0.85rem; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password Input Group -->
                    <div class="form-group">
                        <div class="password-label-row">
                            <label for="password" class="form-label">
                                Password<span class="required-mark">*</span>
                            </label>
                        </div>
                        <div class="input-container password-container">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="••••••••••••" 
                                required 
                                autocomplete="current-password"
                                aria-required="true"
                            >
                            <button 
                                type="button" 
                                class="password-toggle-btn" 
                                id="passwordToggleBtn" 
                                aria-label="Toggle password visibility"
                                tabindex="0"
                            >
                                <!-- Eye Icon (Show) -->
                                <svg class="eye-icon eye-show" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <!-- Eye Off Icon (Hide) -->
                                <svg class="eye-icon eye-hide" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <div class="field-error" style="display: block; color: #EF4444; font-size: 0.85rem; margin-top: 4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remember Me Checkbox Group -->
                    <div class="checkbox-group">
                        <label class="custom-checkbox-container">
                            <input type="checkbox" id="rememberMe" name="rememberMe" value="1" {{ old('rememberMe') ? 'checked' : '' }}>
                            <span class="custom-checkmark">
                                <svg class="check-icon" width="12" height="10" viewBox="0 0 12 10" fill="none">
                                    <path d="M1 5L4.5 8.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="checkbox-text">Remember me</span>
                        </label>
                    </div>

                    <!-- Login Submit Button -->
                    <button type="submit" class="submit-btn" id="loginSubmitBtn">
                        <span class="btn-text">Log Masuk</span>
                        <div class="btn-spinner" id="btnSpinner" aria-hidden="true"></div>
                    </button>

                    <!-- Link to Portals -->
                    <div style="text-align: center; margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                        <div style="font-size: 0.82rem; color: #94A3B8; margin-bottom: 8px;">Pilih Portal Khusus:</div>
                        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; font-size: 0.85rem;">
                            <a href="{{ route('filament.admin.auth.login') }}" style="color: #94A3B8; text-decoration: none;">
                                Owner
                            </a>
                            <span style="color: #64748B;">•</span>
                            <a href="{{ route('filament.partner.auth.login') }}" style="color: #94A3B8; text-decoration: none;">
                                Partner
                            </a>
                            <span style="color: #64748B;">•</span>
                            <a href="{{ route('filament.business.auth.login') }}" style="color: #14B8A6; font-weight: 600; text-decoration: none;">
                                Business Owner
                            </a>
                            <span style="color: #64748B;">•</span>
                            <a href="{{ route('marketplace.index') }}" style="color: #94A3B8; text-decoration: none;">
                                Pelanggan (Marketplace)
                            </a>
                        </div>
                    </div>

                </form>
            </section>

        </div>
    </main>

    <!-- Main Javascript -->
    <script src="{{ asset('assets/js/script.js') }}?v={{ file_exists(public_path('assets/js/script.js')) ? filemtime(public_path('assets/js/script.js')) : time() }}"></script>

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
