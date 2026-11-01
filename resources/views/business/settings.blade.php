<x-business-layout title="PIOS - Settings & Profil" headerTitle="Settings" bodyClass="fees-body" mainClass="fees-main" mobileHeaderClass="mobile-fees-header">

    <style>
        /* Inherit Theme Fonts Strictly */
        .fees-main,
        .fees-main * {
            font-family: var(--font-sans, 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
        }

        h1, h2, h3, h4,
        .fees-heading,
        .settings-hero-title,
        .provider-name,
        .config-section-title,
        .countdown-metric-title {
            font-family: var(--font-heading, 'Poppins', var(--font-sans, sans-serif)) !important;
        }

        /* Full Width Main Layout for Settings */
        .fees-main {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        .settings-topbar {
            margin-bottom: 24px !important;
            width: 100%;
        }

        /* BGE Subscription Countdown Card (Full Width) */
        .bge-countdown-card {
            background: linear-gradient(135deg, #134C49 0%, #174A78 100%);
            border-radius: 16px;
            padding: 26px 30px;
            color: #FFFFFF;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(19, 76, 73, 0.28);
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .countdown-header-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .countdown-pill-badge {
            font-size: 0.74rem;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .countdown-pill-gold {
            background: #E7C46A;
            color: #0F172A;
        }

        .countdown-pill-green {
            background: #10B981;
            color: #FFFFFF;
        }

        .countdown-pill-amber {
            background: #F59E0B;
            color: #FFFFFF;
        }

        .countdown-pill-red {
            background: #EF4444;
            color: #FFFFFF;
        }

        .bge-countdown-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.16);
            padding-top: 18px;
        }

        @media (max-width: 900px) {
            .bge-countdown-grid {
                grid-template-columns: 1fr;
            }
        }

        .countdown-metric-box {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 14px;
            padding: 16px 22px;
            display: inline-flex;
            align-items: center;
            gap: 16px;
            backdrop-filter: blur(8px);
            margin-right: 18px;
        }

        .countdown-digits {
            font-size: 2.3rem;
            font-weight: 900;
            line-height: 1;
            color: #FACC15;
            font-family: var(--font-heading, 'Poppins', sans-serif);
            letter-spacing: -0.02em;
        }

        .countdown-unit-text {
            font-size: 0.85rem;
            color: #E2E8F0;
            line-height: 1.35;
        }

        .btn-renew-whatsapp {
            background: #E7C46A;
            color: #0F172A;
            font-weight: 800;
            font-size: 0.94rem;
            padding: 13px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(231, 196, 106, 0.35);
            white-space: nowrap;
        }

        .btn-renew-whatsapp:hover {
            background: #F1D487;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(231, 196, 106, 0.45);
        }

        /* Profile & Password Grid (Full Width 2 Columns) */
        .settings-double-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 24px;
            width: 100%;
            box-sizing: border-box;
        }

        @media (max-width: 980px) {
            .settings-double-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        /* Avatar Upload Area */
        .avatar-upload-row {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 12px 0 20px;
            border-bottom: 1px solid #F1F5F9;
            margin-bottom: 20px;
        }

        .avatar-preview-wrap {
            position: relative;
            width: 84px;
            height: 84px;
            flex-shrink: 0;
        }

        .avatar-preview-img {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #0D9488;
            background: #F8FAFC;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);
            display: block;
        }

        .avatar-upload-btn-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            background: #F0FDFA;
            border: 1.5px solid #99F6E4;
            color: #0F766E;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .avatar-upload-btn-label:hover {
            background: #CCFBF1;
            border-color: #0D9488;
        }

        /* PIOS Hero Header Banner - Full Width */
        .settings-hero-banner {
            background: linear-gradient(135deg, #134C49 0%, #174A78 100%);
            border-radius: 16px;
            padding: 26px 30px;
            color: #FFFFFF;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(19, 76, 73, 0.25);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            width: 100%;
            box-sizing: border-box;
        }

        .settings-hero-badge-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }

        .settings-gold-badge {
            font-size: 0.72rem;
            font-weight: 800;
            background: #E7C46A;
            color: #0F172A;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .settings-status-pill {
            font-size: 0.76rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.18);
            color: #FFFFFF;
            padding: 3px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .settings-status-pill .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10B981;
        }

        .settings-hero-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #FFFFFF;
        }

        .settings-hero-subtitle {
            margin: 4px 0 0;
            font-size: 0.90rem;
            color: #E2E8F0;
            line-height: 1.55;
        }

        /* Provider Selection Cards - Full Width Grid */
        .provider-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
            width: 100%;
            box-sizing: border-box;
        }

        @media (max-width: 900px) {
            .provider-cards-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }
        }

        .provider-card {
            background: #FFFFFF;
            border: 2px solid #E2E8F0;
            border-radius: 16px;
            padding: 22px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            width: 100%;
            box-sizing: border-box;
        }

        .provider-card:hover {
            border-color: #0D9488;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.08);
        }

        .provider-card.selected {
            border-color: #0D9488;
            background: #F0FDFA;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.15);
        }

        .provider-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .provider-icon-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .provider-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .provider-name {
            font-size: 1.08rem;
            font-weight: 700;
            color: #0F172A;
            margin: 0;
            line-height: 1.25;
        }

        .provider-badge {
            font-size: 0.70rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .provider-radio-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #CBD5E1;
            background: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .provider-card.selected .provider-radio-indicator {
            border-color: #0D9488;
            background: #0D9488;
        }

        .provider-card.selected .provider-radio-indicator::after {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #FFFFFF;
        }

        .provider-desc {
            font-size: 0.85rem;
            color: #64748B;
            line-height: 1.5;
            margin: 0;
        }

        /* Config Card Container - Full Width */
        .config-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 26px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            width: 100%;
            box-sizing: border-box;
        }

        .config-section-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0F172A;
            margin: 0 0 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            border-bottom: 1px solid #F1F5F9;
        }

        .form-group {
            margin-bottom: 18px;
            width: 100%;
        }

        .form-label {
            display: block;
            font-size: 0.86rem;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 11px 16px;
            font-size: 0.90rem;
            border: 1.5px solid #CBD5E1;
            border-radius: 10px;
            color: #0F172A;
            background: #FFFFFF;
            box-sizing: border-box;
            transition: all 0.15s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: #0D9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
        }

        .form-hint {
            font-size: 0.78rem;
            color: #64748B;
            margin-top: 5px;
            margin-bottom: 0;
        }

        .tutorial-box {
            background: #F8FAFC;
            border: 1px dashed #CBD5E1;
            border-radius: 10px;
            padding: 16px 20px;
            margin: 18px 0;
            font-size: 0.84rem;
            color: #334155;
            line-height: 1.6;
            width: 100%;
            box-sizing: border-box;
        }

        .tutorial-box ol {
            margin: 6px 0 0;
            padding-left: 20px;
        }

        .tutorial-box li {
            margin-bottom: 4px;
        }

        /* Buttons */
        .btn-test {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            border-radius: 9px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: 1.5px solid #CBD5E1;
            background: #FFFFFF;
            color: #334155;
        }

        .btn-test:hover {
            background: #F8FAFC;
            border-color: #94A3B8;
        }

        .btn-save-whatsapp {
            background-color: #0D9488 !important;
            color: #FFFFFF !important;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.94rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
        }

        .btn-save-whatsapp:hover {
            background-color: #0F766E !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35);
        }

        .test-feedback-box {
            display: none;
            margin-top: 14px;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.86rem;
            line-height: 1.45;
            width: 100%;
            box-sizing: border-box;
        }

        /* Dark Mode Theme Support */
        body.dark-mode-active .provider-card,
        body.dark-mode-active .config-card {
            background: #1E293B !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
        }

        body.dark-mode-active .provider-card:hover {
            border-color: #0D9488 !important;
        }

        body.dark-mode-active .provider-card.selected {
            background: rgba(13, 148, 136, 0.12) !important;
            border-color: #0D9488 !important;
        }

        body.dark-mode-active .provider-name,
        body.dark-mode-active .config-section-title {
            color: #F8FAFC !important;
        }

        body.dark-mode-active .provider-desc,
        body.dark-mode-active .form-hint,
        body.dark-mode-active .tutorial-box {
            color: #94A3B8 !important;
        }

        body.dark-mode-active .form-label {
            color: #E2E8F0 !important;
        }

        body.dark-mode-active .form-input {
            background: #0F172A !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
        }

        body.dark-mode-active .form-input:focus {
            border-color: #0D9488 !important;
        }

        body.dark-mode-active .tutorial-box {
            background: #0F172A !important;
            border-color: #334155 !important;
        }

        body.dark-mode-active .btn-test {
            background: #0F172A !important;
            border-color: #334155 !important;
            color: #E2E8F0 !important;
        }

        body.dark-mode-active .btn-test:hover {
            background: #1E293B !important;
            border-color: #475569 !important;
        }

        body.dark-mode-active .avatar-upload-row {
            border-bottom-color: #334155 !important;
        }

        body.dark-mode-active .avatar-upload-btn-label {
            background: rgba(13, 148, 136, 0.15) !important;
            border-color: #0D9488 !important;
            color: #5EEAD4 !important;
        }
    </style>

    <!-- Standard PIOS Desktop Topbar (Full Width) -->
    <div class="fees-topbar settings-topbar">
        <div class="topbar-left">
            <h1 class="fees-heading">Settings</h1>
            <p class="fees-subheading">Status langganan, profil akaun, kata laluan dan integrasi WhatsApp Blaster perniagaan anda</p>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="topbarDate">{{ now()->format('D, d M Y') }}</span>
            <div class="system-status-badge">
                <span class="status-dot"></span>
                <span>Business Owner</span>
            </div>
            <button class="theme-toggle-btn" id="desktopThemeToggleBtn" onclick="window.togglePiosTheme(event)" aria-label="Toggle Dark / Light Theme" title="Toggle Dark / Light Theme">
                <svg class="theme-icon-moon moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <svg class="theme-icon-sun sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            </button>
            <button class="notif-bell-btn" aria-label="Notifications" title="Notifications">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notif-badge-dot"></span>
            </button>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('success'))
        <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #065F46; font-size: 0.90rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 6px rgba(16, 185, 129, 0.08); width: 100%; box-sizing: border-box;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span style="font-weight: 600;">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" style="background: transparent; border: none; font-size: 1.2rem; cursor: pointer; color: #065F46;">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #991B1B; font-size: 0.90rem; width: 100%; box-sizing: border-box;">
            <div style="font-weight: 700; margin-bottom: 6px;">Sila semak ralat berikut:</div>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 1. BGE Subscription Day Countdown Card (Full Width) -->
    <div class="bge-countdown-card">
        <div class="countdown-header-row">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                    <span class="countdown-pill-badge countdown-pill-gold">Default Mandatory</span>
                    @if ($bgeSubscription && $bgeSubscription->isTrial())
                        <span class="countdown-pill-badge countdown-pill-amber">
                            ⭐ 48-Hour Free Trial
                        </span>
                    @elseif ($bgeSubscription && $bgeSubscription->isActive())
                        <span class="countdown-pill-badge countdown-pill-green">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #FFF; display: inline-block;"></span>
                            Active Subscription
                        </span>
                    @elseif ($bgeSubscription && $bgeSubscription->status === 'pending_approval')
                        <span class="countdown-pill-badge countdown-pill-amber">
                            Pending Payment Confirmation
                        </span>
                    @else
                        <span class="countdown-pill-badge countdown-pill-red">
                            Expired (Restricted Access)
                        </span>
                    @endif
                </div>
                <h2 style="margin: 0; font-size: 1.45rem; font-weight: 800; letter-spacing: -0.01em; color: #FFFFFF;">
                    BizGrowth Engine (BGE) &mdash; Subscription Status &amp; Countdown
                </h2>
                <p style="margin: 4px 0 0; font-size: 0.88rem; color: #E2E8F0; line-height: 1.5;">
                    The core operational engine of your PIOS business. 30-day renewal confirmed directly by your sponsoring Partner.
                </p>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 1.6rem; font-weight: 900; color: #FFFFFF;">
                    RM 30.00 <span style="font-size: 0.82rem; font-weight: 500; color: #E2E8F0;">/ 30 Days</span>
                </div>
                <div style="font-size: 0.78rem; color: #CBD5E1;">
                    Sponsoring Partner: <strong style="color: #FDE68A;">{{ $partner?->applicant_name ?? 'PIOS Partner' }}</strong>
                </div>
            </div>
        </div>

        <div class="bge-countdown-grid">
            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 16px;">
                <!-- Countdown Visual Box -->
                <div class="countdown-metric-box">
                    @if ($bgeSubscription && $bgeSubscription->isTrial())
                        <div class="countdown-digits">{{ $bgeSubscription->hoursRemaining() }}</div>
                        <div class="countdown-unit-text">
                            <strong style="color: #FFFFFF; font-size: 1rem; display: block;">HOURS LEFT</strong>
                            Active trial period
                        </div>
                    @elseif ($bgeSubscription && $bgeSubscription->isActive())
                        <div class="countdown-digits">{{ $bgeSubscription->daysRemaining() }}</div>
                        <div class="countdown-unit-text">
                            <strong style="color: #FFFFFF; font-size: 1rem; display: block;">DAYS REMAINING</strong>
                            Active paid subscription
                        </div>
                    @elseif ($bgeSubscription && $bgeSubscription->status === 'pending_approval')
                        <div class="countdown-digits" style="font-size: 1.4rem; color: #FCD34D;">Pending</div>
                        <div class="countdown-unit-text">
                            <strong style="color: #FFFFFF; font-size: 0.95rem; display: block;">CONFIRMATION</strong>
                            Partner reviewing
                        </div>
                    @else
                        <div class="countdown-digits" style="color: #F87171;">0</div>
                        <div class="countdown-unit-text">
                            <strong style="color: #FFFFFF; font-size: 1rem; display: block;">DAYS REMAINING</strong>
                            Expired
                        </div>
                    @endif
                </div>

                <!-- Expiry details -->
                <div style="font-size: 0.86rem; color: #F1F5F9; line-height: 1.6;">
                    @if ($bgeSubscription && $bgeSubscription->expires_at)
                        <div>Expiry Date: <strong>{{ $bgeSubscription->expires_at->format('d M Y, h:i A') }}</strong></div>
                    @else
                        <div>Expiry Date: <strong>-</strong></div>
                    @endif
                    <div>BO Account No: <strong style="color: #E7C46A;">{{ $bo?->business_owner_no ?? '-' }}</strong></div>
                    <div>Partner WhatsApp: <strong>{{ $partner?->hp_no ?? 'Not set' }}</strong></div>
                </div>
            </div>

            <!-- WhatsApp Renewal Action -->
            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 8px;">
                <button type="button" class="btn-renew-whatsapp" onclick="triggerPartnerWhatsAppRenew()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.201.3-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.787-1.677-2.088-.175-.3-.019-.463.132-.613.136-.135.301-.351.452-.527.15-.175.201-.3.301-.501.101-.2.05-.376-.025-.526-.075-.15-.677-1.632-.928-2.235-.245-.587-.493-.508-.677-.517-.175-.01-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.3-1.054 1.03-1.054 2.511 0 1.482 1.079 2.911 1.23 3.112.15.2 2.124 3.243 5.145 4.548.719.311 1.28.497 1.718.636.722.23 1.379.197 1.9-.12.57-.348 1.782-.728 2.033-1.431.25-.703.25-1.306.175-1.431-.075-.125-.276-.2-.577-.35zM12 2a10 10 0 0 0-8.59 15.11L2 22l4.98-1.31A10 10 0 1 0 12 2z"></path>
                    </svg>
                    Renew Subscription (via WhatsApp)
                </button>
                <span style="font-size: 0.74rem; color: #CBD5E1; max-width: 320px; line-height: 1.35;">
                    Opens your Partner's WhatsApp with pre-filled payment details. Your Partner confirms payment to add +30 days (or bundle) instantly.
                </span>
            </div>
        </div>
    </div>

    <!-- 2. Account Management Grid: Edit Profile & Change Password (Full Width 2 Columns) -->
    <div class="settings-double-grid">
        <!-- Edit Profile & Avatar Card -->
        <div class="config-card" style="margin-bottom: 0;">
            <h3 class="config-section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Edit Profile &amp; Picture
            </h3>

            <form action="{{ route('business.settings.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- Avatar Upload Section -->
                <div class="avatar-upload-row">
                    <div class="avatar-preview-wrap">
                        <img src="{{ auth()->user()?->avatarUrl() }}" alt="Avatar" id="avatarPreview" class="avatar-preview-img">
                    </div>
                    <div>
                        <input type="file" name="avatar" id="avatarInput" accept="image/png,image/jpeg,image/webp" style="display: none;" onchange="previewAvatar(this)">
                        <label for="avatarInput" class="avatar-upload-btn-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                <circle cx="12" cy="13" r="4"></circle>
                            </svg>
                            Change Profile Picture
                        </label>
                        <p class="form-hint" style="margin-top: 6px;">Format JPG, PNG or WebP (Max 4MB). Displayed across the sidebar and profile.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="applicantNameInput">Applicant / Full Name <span style="color: #DC2626;">*</span></label>
                    <input type="text" name="applicant_name" id="applicantNameInput" class="form-input" value="{{ old('applicant_name', $bo?->applicant_name ?? $user->name) }}" required placeholder="e.g. Ahmad Razali">
                </div>

                <div class="form-group">
                    <label class="form-label" for="companyNameInput">Company / Business Name</label>
                    <input type="text" name="company_name" id="companyNameInput" class="form-input" value="{{ old('company_name', $bo?->company_name) }}" placeholder="e.g. Razali Enterprise">
                </div>

                <div class="form-group">
                    <label class="form-label" for="hpNoInput">WhatsApp Phone Number</label>
                    <input type="text" name="hp_no" id="hpNoInput" class="form-input" value="{{ old('hp_no', $bo?->hp_no) }}" placeholder="e.g. 0123456789">
                    <p class="form-hint">Used for customer notifications and WhatsApp integration.</p>
                </div>

                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 0.82rem; color: #475569; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div>BO Account No: <strong style="color: #0F172A;">{{ $bo?->business_owner_no ?? '-' }}</strong></div>
                    <div>Email: <strong style="color: #0F172A;">{{ $user->email }}</strong></div>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-save-whatsapp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Save Profile &amp; Picture
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password Card -->
        <div class="config-card" style="margin-bottom: 0;">
            <h3 class="config-section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Change Password
            </h3>

            <form action="{{ route('business.settings.password.update') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="currentPasswordInput">Current Password <span style="color: #DC2626;">*</span></label>
                    <input type="password" name="current_password" id="currentPasswordInput" class="form-input" required placeholder="Enter your current password">
                </div>

                <div class="form-group">
                    <label class="form-label" for="newPasswordInput">New Password <span style="color: #DC2626;">*</span></label>
                    <input type="password" name="password" id="newPasswordInput" class="form-input" required placeholder="At least 8 characters">
                    <p class="form-hint">Use a combination of letters, numbers, and symbols for maximum security.</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="passwordConfirmationInput">Confirm New Password <span style="color: #DC2626;">*</span></label>
                    <input type="password" name="password_confirmation" id="passwordConfirmationInput" class="form-input" required placeholder="Re-type new password">
                </div>

                <div class="tutorial-box" style="margin-top: 10px; margin-bottom: 22px;">
                    <strong>Important:</strong> After updating your password, ensure you remember your new credentials for future logins.
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-save-whatsapp">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. PIOS Themed Hero Banner - WhatsApp Blaster (Full Width) -->
    <div class="settings-hero-banner">
        <div>
            <div class="settings-hero-badge-row">
                <span class="settings-gold-badge">Pemasaran &amp; Komunikasi</span>
                <span class="settings-status-pill">
                    <span class="dot" style="background: {{ ($bo?->whatsapp_status ?? '') === 'connected' ? '#10B981' : '#F59E0B' }};"></span>
                    Status: <strong>{{ ucfirst($bo?->whatsapp_status ?? 'Connected') }}</strong>
                </span>
                @if ($bo?->whatsapp_last_tested_at)
                    <span style="font-size: 0.74rem; color: #CBD5E1;">Diuji: {{ $bo->whatsapp_last_tested_at->diffForHumans() }}</span>
                @endif
            </div>
            <h2 class="settings-hero-title">Tetapan WhatsApp Blaster</h2>
            <p class="settings-hero-subtitle">
                Pilih kaedah penghantaran mesej promosi produk ke pelanggan. Anda boleh menggunakan mod Manual (wa.me percuma) atau sambungkan API Fonnte / UltraMsg untuk blast automatik terus dari katalog.
            </p>
        </div>
        <div style="text-align: right; min-width: 180px;">
            <div style="font-size: 0.78rem; color: #CBD5E1; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">Provider Aktif</div>
            <div style="font-size: 1.35rem; font-weight: 800; color: #E7C46A;">
                {{ $bo?->whatsAppProviderLabel() ?? 'Manual (wa.me)' }}
            </div>
        </div>
    </div>

    <form action="{{ route('business.settings.whatsapp.update') }}" method="POST" id="waSettingsForm" style="width: 100%; box-sizing: border-box;">
        @csrf

        <!-- Provider Selection Grid (Full Width 3 Columns) -->
        <div class="provider-cards-grid">
            <!-- 1. Manual wa.me -->
            <div class="provider-card {{ ($bo?->whatsapp_provider ?? 'manual') === 'manual' ? 'selected' : '' }}" onclick="selectProvider('manual', this)">
                <div>
                    <div class="provider-top">
                        <div class="provider-icon-title">
                            <div class="provider-icon-box" style="background: #DCFCE7; color: #16A34A;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.201.3-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.787-1.677-2.088-.175-.3-.019-.463.132-.613.136-.135.301-.351.452-.527.15-.175.201-.3.301-.501.101-.2.05-.376-.025-.526-.075-.15-.677-1.632-.928-2.235-.245-.587-.493-.508-.677-.517-.175-.01-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.3-1.054 1.03-1.054 2.511 0 1.482 1.079 2.911 1.23 3.112.15.2 2.124 3.243 5.145 4.548.719.311 1.28.497 1.718.636.722.23 1.379.197 1.9-.12.57-.348 1.782-.728 2.033-1.431.25-.703.25-1.306.175-1.431-.075-.125-.276-.2-.577-.35zM12 2a10 10 0 0 0-8.59 15.11L2 22l4.98-1.31A10 10 0 1 0 12 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="provider-name">Manual (wa.me)</h3>
                                <span class="provider-badge" style="background: #E2E8F0; color: #475569;">Percuma / Default</span>
                            </div>
                        </div>
                        <div class="provider-radio-indicator"></div>
                    </div>
                    <p class="provider-desc">
                        Menjana pautan WhatsApp peribadi. Butang <strong>Blast</strong> pada produk akan membuka aplikasi WhatsApp anda dengan teks promosi terus siap diisi.
                    </p>
                </div>
            </div>

            <!-- 2. Fonnte (API) -->
            <div class="provider-card {{ ($bo?->whatsapp_provider ?? '') === 'fonnte' ? 'selected' : '' }}" onclick="selectProvider('fonnte', this)">
                <div>
                    <div class="provider-top">
                        <div class="provider-icon-title">
                            <div class="provider-icon-box" style="background: #CCFBF1; color: #0D9488;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                </svg>
                            </div>
                            <div>
                                <h3 class="provider-name">Fonnte (API)</h3>
                                <span class="provider-badge" style="background: #FEF3C7; color: #92400E;">⭐ Paling Disyorkan</span>
                            </div>
                        </div>
                        <div class="provider-radio-indicator"></div>
                    </div>
                    <p class="provider-desc">
                        Gateway WhatsApp mesra pasaran Malaysia. Imbas QR dalam 2 minit di fonnte.com untuk blast teks &amp; gambar produk secara automatik.
                    </p>
                </div>
            </div>

            <!-- 3. UltraMsg (API) -->
            <div class="provider-card {{ ($bo?->whatsapp_provider ?? '') === 'ultramsg' ? 'selected' : '' }}" onclick="selectProvider('ultramsg', this)">
                <div>
                    <div class="provider-top">
                        <div class="provider-icon-title">
                            <div class="provider-icon-box" style="background: #E0E7FF; color: #4338CA;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                                </svg>
                            </div>
                            <div>
                                <h3 class="provider-name">UltraMsg (API)</h3>
                                <span class="provider-badge" style="background: #E0E7FF; color: #3730A3;">Gateway Global</span>
                            </div>
                        </div>
                        <div class="provider-radio-indicator"></div>
                    </div>
                    <p class="provider-desc">
                        Gateway WhatsApp antarabangsa stabil dengan sokongan Instance ID khusus dan laporan penghantaran berjadual.
                    </p>
                </div>
            </div>
        </div>

        <!-- Hidden input for selected provider -->
        <input type="hidden" name="whatsapp_provider" id="selectedProviderInput" value="{{ $bo?->whatsapp_provider ?? 'manual' }}">

        <!-- Fonnte Configuration Section (Full Width) -->
        <div id="fonnteConfigSection" class="config-card" style="{{ ($bo?->whatsapp_provider ?? '') === 'fonnte' ? 'display: block;' : 'display: none;' }}">
            <h3 class="config-section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                Konfigurasi Fonnte (API Token)
            </h3>

            <div class="form-group">
                <label class="form-label" for="fonnteApiToken">Fonnte API Token <span style="color: #DC2626;">*</span></label>
                <input type="text" name="whatsapp_api_token" id="fonnteApiToken" class="form-input" placeholder="Contoh: a1b2c3d4e5f6g7h8..." value="{{ $bo?->whatsapp_provider === 'fonnte' ? $bo?->whatsapp_api_token : '' }}">
                <p class="form-hint">Dapatkan token ini daripada dashboard akaun Fonnte anda (Menu: Device / Manage Device).</p>
            </div>

            <div class="tutorial-box">
                <strong>Panduan Ringkas Pengaktifan Fonnte:</strong>
                <ol>
                    <li>Layari laman web rasmi <a href="https://fonnte.com" target="_blank" rel="noopener noreferrer" style="color: #0D9488; font-weight: 700; text-decoration: underline;">fonnte.com</a> dan daftar akaun.</li>
                    <li>Tekan <strong>Add Device</strong> dan imbas kod QR menggunakan WhatsApp pada telefon pintar anda.</li>
                    <li>Salin <strong>API Token</strong> peranti tersebut dan tampalkan ke ruangan di atas.</li>
                </ol>
            </div>

            <div style="margin-top: 18px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn-test" onclick="testConnection('fonnte')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Uji Sambungan (Ping API)
                </button>
                <button type="button" class="btn-test" onclick="openTestMessageModal('fonnte')" style="color: #0D9488; border-color: #99F6E4; background: #F0FDFA;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Hantar Mesej Ujian Ke Nombor Saya
                </button>
            </div>

            <div id="fonnteFeedback" class="test-feedback-box"></div>
        </div>

        <!-- UltraMsg Configuration Section (Full Width) -->
        <div id="ultramsgConfigSection" class="config-card" style="{{ ($bo?->whatsapp_provider ?? '') === 'ultramsg' ? 'display: block;' : 'display: none;' }}">
            <h3 class="config-section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4338CA" stroke-width="2.2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                Konfigurasi UltraMsg (Instance ID &amp; Token)
            </h3>

            <div class="form-group">
                <label class="form-label" for="ultramsgInstanceId">UltraMsg Instance ID <span style="color: #DC2626;">*</span></label>
                <input type="text" name="whatsapp_instance_id" id="ultramsgInstanceId" class="form-input" placeholder="Contoh: instance99887" value="{{ $bo?->whatsapp_provider === 'ultramsg' ? $bo?->whatsapp_instance_id : '' }}">
                <p class="form-hint">Instance ID peranti anda di UltraMsg.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="ultramsgApiToken">UltraMsg Token <span style="color: #DC2626;">*</span></label>
                <input type="text" name="whatsapp_api_token" id="ultramsgApiToken" class="form-input" placeholder="Contoh: 1a2b3c4d5e6f..." value="{{ $bo?->whatsapp_provider === 'ultramsg' ? $bo?->whatsapp_api_token : '' }}">
                <p class="form-hint">Token kebenaran instance di UltraMsg.</p>
            </div>

            <div class="tutorial-box">
                <strong>Panduan Ringkas Pengaktifan UltraMsg:</strong>
                <ol>
                    <li>Buka akaun di <a href="https://ultramsg.com" target="_blank" rel="noopener noreferrer" style="color: #4338CA; font-weight: 700; text-decoration: underline;">ultramsg.com</a>.</li>
                    <li>Imbas kod QR pada instance WhatsApp anda.</li>
                    <li>Salin <strong>Instance ID</strong> dan <strong>Token</strong> ke borang di atas.</li>
                </ol>
            </div>

            <div style="margin-top: 18px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn-test" onclick="testConnection('ultramsg')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Uji Sambungan (Ping API)
                </button>
                <button type="button" class="btn-test" onclick="openTestMessageModal('ultramsg')" style="color: #4338CA; border-color: #C7D2FE; background: #EEF2FF;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Hantar Mesej Ujian Ke Nombor Saya
                </button>
            </div>

            <div id="ultramsgFeedback" class="test-feedback-box"></div>
        </div>

        <!-- Manual wa.me Notice Section (Full Width) -->
        <div id="manualConfigSection" class="config-card" style="{{ ($bo?->whatsapp_provider ?? 'manual') === 'manual' ? 'display: block;' : 'display: none;' }}">
            <h3 class="config-section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" color="#16A34A"><path d="M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.201.3-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.787-1.677-2.088-.175-.3-.019-.463.132-.613.136-.135.301-.351.452-.527.15-.175.201-.3.301-.501.101-.2.05-.376-.025-.526-.075-.15-.677-1.632-.928-2.235-.245-.587-.493-.508-.677-.517-.175-.01-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.3-1.054 1.03-1.054 2.511 0 1.482 1.079 2.911 1.23 3.112.15.2 2.124 3.243 5.145 4.548.719.311 1.28.497 1.718.636.722.23 1.379.197 1.9-.12.57-.348 1.782-.728 2.033-1.431.25-.703.25-1.306.175-1.431-.075-.125-.276-.2-.577-.35zM12 2a10 10 0 0 0-8.59 15.11L2 22l4.98-1.31A10 10 0 1 0 12 2z"></path></svg>
                Mod Manual (wa.me)
            </h3>
            <p style="font-size: 0.92rem; color: #475569; margin: 0; line-height: 1.6;">
                Dalam mod ini, setiap butang <strong>Blast</strong> atau ikon WhatsApp pada katalog produk dan pangkalan data pelanggan akan membuka aplikasi WhatsApp anda secara terus dengan format mesej promosi siap dijana. Mod ini adalah <strong>100% percuma</strong> tanpa sebarang kos bulanan mahupun keperluan mendaftar gateway API pihak ketiga.
            </p>
        </div>

        <!-- Submit Button Bar (Full Width) -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 10px; margin-bottom: 24px; width: 100%; box-sizing: border-box;">
            <button type="submit" class="btn-save-whatsapp">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Simpan Tetapan WhatsApp
            </button>
        </div>
    </form>

    <!-- Live Test Message Modal -->
    <div id="testMsgModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.65); z-index: 99999; align-items: center; justify-content: center; padding: 16px;">
        <div style="background: #FFFFFF; border-radius: 16px; max-width: 440px; width: 100%; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 8px;">
                    <span style="color: #25D366;">💬</span> Ujian Hantar WhatsApp
                </h3>
                <button type="button" onclick="closeTestMessageModal()" style="background: transparent; border: none; font-size: 1.4rem; cursor: pointer; color: #64748B; padding: 0;">&times;</button>
            </div>
            <p style="font-size: 0.84rem; color: #64748B; margin: 0 0 16px; line-height: 1.5;">
                Mesej percubaan akan dihantar serta-merta ke nombor telefon di bawah bagi mengesahkan akaun dan API berfungsi dengan tepat.
            </p>
            <div class="form-group">
                <label class="form-label" for="testPhoneNumberInput">Nombor WhatsApp Penerima</label>
                <input type="text" id="testPhoneNumberInput" class="form-input" placeholder="Contoh: 0123456789 atau 60123456789" value="{{ $bo?->hp_no ?? '' }}">
            </div>
            <div id="modalTestFeedback" class="test-feedback-box"></div>
            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px;">
                <button type="button" onclick="closeTestMessageModal()" style="background: #F1F5F9; border: 1px solid #CBD5E1; color: #475569; padding: 9px 18px; border-radius: 9px; font-weight: 600; cursor: pointer;">Batal</button>
                <button type="button" id="confirmSendTestBtn" onclick="submitLiveTestMessage()" class="btn-save-whatsapp" style="padding: 9px 20px; font-size: 0.88rem;">
                    Hantar Sekarang
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentTestingProvider = 'fonnte';

        // Avatar live preview
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('avatarPreview');
                    if (img) {
                        img.src = e.target.result;
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // WhatsApp Partner renewal trigger
        function triggerPartnerWhatsAppRenew() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch("{{ route('business.subscriptions.renew-whatsapp') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.whatsapp_url) {
                    window.open(data.whatsapp_url, '_blank');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                } else {
                    window.open("{{ $partnerWhatsAppRenewUrl ?? '#' }}", '_blank');
                }
            })
            .catch(function() {
                window.open("{{ $partnerWhatsAppRenewUrl ?? '#' }}", '_blank');
            });
        }

        function selectProvider(provider, element) {
            document.getElementById('selectedProviderInput').value = provider;

            // Highlight selected card
            document.querySelectorAll('.provider-card').forEach(card => card.classList.remove('selected'));
            if (element) {
                element.classList.add('selected');
            }

            // Toggle config sections
            const fonnteSection = document.getElementById('fonnteConfigSection');
            const ultraSection = document.getElementById('ultramsgConfigSection');
            const manualSection = document.getElementById('manualConfigSection');

            if (fonnteSection) fonnteSection.style.display = (provider === 'fonnte') ? 'block' : 'none';
            if (ultraSection) ultraSection.style.display = (provider === 'ultramsg') ? 'block' : 'none';
            if (manualSection) manualSection.style.display = (provider === 'manual') ? 'block' : 'none';

            // Ensure correct input name fields don't collide
            const fonnteToken = document.getElementById('fonnteApiToken');
            const ultraToken = document.getElementById('ultramsgApiToken');

            if (provider === 'fonnte') {
                if (fonnteToken) fonnteToken.disabled = false;
                if (ultraToken) ultraToken.disabled = true;
            } else if (provider === 'ultramsg') {
                if (fonnteToken) fonnteToken.disabled = true;
                if (ultraToken) ultraToken.disabled = false;
            } else {
                if (fonnteToken) fonnteToken.disabled = true;
                if (ultraToken) ultraToken.disabled = true;
            }
        }

        // Initialize state on load
        document.addEventListener('DOMContentLoaded', () => {
            const initialProvider = document.getElementById('selectedProviderInput').value || 'manual';
            const fonnteToken = document.getElementById('fonnteApiToken');
            const ultraToken = document.getElementById('ultramsgApiToken');

            if (initialProvider === 'fonnte') {
                if (fonnteToken) fonnteToken.disabled = false;
                if (ultraToken) ultraToken.disabled = true;
            } else if (initialProvider === 'ultramsg') {
                if (fonnteToken) fonnteToken.disabled = true;
                if (ultraToken) ultraToken.disabled = false;
            } else {
                if (fonnteToken) fonnteToken.disabled = true;
                if (ultraToken) ultraToken.disabled = true;
            }
        });

        // Test Connection Ping
        function testConnection(provider) {
            currentTestingProvider = provider;
            const feedbackEl = document.getElementById(provider + 'Feedback');
            if (!feedbackEl) return;

            let token = '';
            let instanceId = '';

            if (provider === 'fonnte') {
                token = document.getElementById('fonnteApiToken')?.value || '';
                if (!token.trim()) {
                    showFeedback(feedbackEl, false, 'Sila masukkan Fonnte API Token terlebih dahulu.');
                    return;
                }
            } else if (provider === 'ultramsg') {
                instanceId = document.getElementById('ultramsgInstanceId')?.value || '';
                token = document.getElementById('ultramsgApiToken')?.value || '';
                if (!instanceId.trim() || !token.trim()) {
                    showFeedback(feedbackEl, false, 'Sila masukkan UltraMsg Instance ID dan Token terlebih dahulu.');
                    return;
                }
            }

            feedbackEl.style.display = 'block';
            feedbackEl.style.background = '#EFF6FF';
            feedbackEl.style.border = '1px solid #BFDBFE';
            feedbackEl.style.color = '#1D4ED8';
            feedbackEl.innerHTML = '⏳ Menghubungi pelayan ' + provider.toUpperCase() + '... Sila tunggu sebentar.';

            fetch('{{ route("business.settings.whatsapp.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    whatsapp_provider: provider,
                    whatsapp_api_token: token,
                    whatsapp_instance_id: instanceId,
                    send_test_message: false
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (status >= 200 && status < 300 && body.success) {
                    showFeedback(feedbackEl, true, '✅ ' + (body.message || 'Sambungan berjaya!') + (body.device ? ' (' + body.device + ')' : ''));
                } else {
                    showFeedback(feedbackEl, false, '❌ ' + (body.message || 'Gagal menyambung ke pelayan provider.'));
                }
            })
            .catch(() => {
                showFeedback(feedbackEl, false, '❌ Ralat rangkaian semasa menghubungi pelayan.');
            });
        }

        // Live Test Message Modal Handling
        function openTestMessageModal(provider) {
            currentTestingProvider = provider;
            const modal = document.getElementById('testMsgModal');
            const feedback = document.getElementById('modalTestFeedback');
            if (feedback) feedback.style.display = 'none';
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeTestMessageModal() {
            const modal = document.getElementById('testMsgModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        function submitLiveTestMessage() {
            const provider = currentTestingProvider;
            const phone = document.getElementById('testPhoneNumberInput')?.value || '';
            const feedbackEl = document.getElementById('modalTestFeedback');
            const sendBtn = document.getElementById('confirmSendTestBtn');

            if (!phone.trim()) {
                showFeedback(feedbackEl, false, 'Sila masukkan nombor WhatsApp penerima.');
                return;
            }

            let token = '';
            let instanceId = '';
            if (provider === 'fonnte') {
                token = document.getElementById('fonnteApiToken')?.value || '';
            } else if (provider === 'ultramsg') {
                instanceId = document.getElementById('ultramsgInstanceId')?.value || '';
                token = document.getElementById('ultramsgApiToken')?.value || '';
            }

            if (sendBtn) {
                sendBtn.disabled = true;
                sendBtn.textContent = 'Menghantar...';
            }

            feedbackEl.style.display = 'block';
            feedbackEl.style.background = '#EFF6FF';
            feedbackEl.style.border = '1px solid #BFDBFE';
            feedbackEl.style.color = '#1D4ED8';
            feedbackEl.innerHTML = '⏳ Menghantar mesej ujian WhatsApp ke ' + phone + '...';

            fetch('{{ route("business.settings.whatsapp.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    whatsapp_provider: provider,
                    whatsapp_api_token: token,
                    whatsapp_instance_id: instanceId,
                    test_phone: phone,
                    send_test_message: true
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (sendBtn) {
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Hantar Sekarang';
                }
                if (status >= 200 && status < 300 && body.success) {
                    showFeedback(feedbackEl, true, '✅ Mesej ujian WhatsApp telah berjaya dihantar ke telefon anda!');
                } else {
                    showFeedback(feedbackEl, false, '❌ ' + (body.message || 'Mesej ujian gagal dihantar.'));
                }
            })
            .catch(() => {
                if (sendBtn) {
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Hantar Sekarang';
                }
                showFeedback(feedbackEl, false, '❌ Ralat rangkaian semasa menghantar mesej.');
            });
        }

        function showFeedback(el, isSuccess, msg) {
            if (!el) return;
            el.style.display = 'block';
            el.style.background = isSuccess ? '#ECFDF5' : '#FEF2F2';
            el.style.border = '1px solid ' + (isSuccess ? '#A7F3D0' : '#FECACA');
            el.style.color = isSuccess ? '#065F46' : '#991B1B';
            el.innerHTML = msg;
        }
    </script>
    @endpush

</x-business-layout>
