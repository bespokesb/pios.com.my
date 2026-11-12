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
            <p class="fees-subheading">Profil akaun, kata laluan dan integrasi WhatsApp Blaster perniagaan anda</p>
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

    <!-- Account Management Grid: Edit Profile & Change Password (Full Width 2 Columns) -->
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
                Pilih kaedah penghantaran mesej promosi produk ke pelanggan. Anda boleh menggunakan mod Manual (wa.me percuma), sambungan rasmi Wati.io (WhatsApp Business API) atau gateway tempatan Wasapla.org untuk blast automatik terus dari katalog.
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

            <!-- 2. Wasapla.org (API) -->
            <div class="provider-card {{ ($bo?->whatsapp_provider ?? '') === 'wasapla' ? 'selected' : '' }}" onclick="selectProvider('wasapla', this)">
                <div>
                    <div class="provider-top">
                        <div class="provider-icon-title">
                            <div class="provider-icon-box" style="background: #CCFBF1; color: #0D9488;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                </svg>
                            </div>
                            <div>
                                <h3 class="provider-name">Wasapla.org (API)</h3>
                                <span class="provider-badge" style="background: #FEF3C7; color: #92400E;">🇲🇾 Gateway Tempatan (Unofficial)</span>
                            </div>
                        </div>
                        <div class="provider-radio-indicator"></div>
                    </div>
                    <p class="provider-desc">
                        Gateway WhatsApp tempatan Malaysia. Menyokong Matching URL dan Mandatori 4-Layer Guard (<code>X-App-Domain</code>) untuk blast teks &amp; promosi produk.
                    </p>
                </div>
            </div>

            <!-- 3. Wati.io (Official API) -->
            <div class="provider-card {{ ($bo?->whatsapp_provider ?? '') === 'wati' ? 'selected' : '' }}" onclick="selectProvider('wati', this)">
                <div>
                    <div class="provider-top">
                        <div class="provider-icon-title">
                            <div class="provider-icon-box" style="background: #E0E7FF; color: #4338CA;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="provider-name">Wati.io (Official API)</h3>
                                <span class="provider-badge" style="background: #E0E7FF; color: #3730A3;">⭐ Meta Verified (Official)</span>
                            </div>
                        </div>
                        <div class="provider-radio-indicator"></div>
                    </div>
                    <p class="provider-desc">
                        Platform WhatsApp Business Solution Provider (BSP) rasmi Meta. Penghantaran rasmi berkapasiti tinggi dengan sambungan langsung WhatsApp Business API.
                    </p>
                </div>
            </div>
        </div>

        <!-- Hidden input for selected provider -->
        <input type="hidden" name="whatsapp_provider" id="selectedProviderInput" value="{{ $bo?->whatsapp_provider ?? 'manual' }}">

        <!-- Wasapla Configuration Section (Full Width) -->
        <div id="wasaplaConfigSection" class="config-card" style="{{ ($bo?->whatsapp_provider ?? '') === 'wasapla' ? 'display: block;' : 'display: none;' }}">
            <h3 class="config-section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                Konfigurasi Wasapla.org (API Key &amp; Instance)
            </h3>

            <!-- 1. Matching Base URL -->
            <div class="form-group">
                <label class="form-label" for="wasaplaInstanceId">Matching Base URL <span style="color: #DC2626;">*</span></label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" name="whatsapp_instance_id" id="wasaplaInstanceId" class="form-input" style="padding-right: 42px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.88rem;" placeholder="https://api.wasapla.org/v1/instances/demo-wasapla-org" value="{{ $bo?->whatsapp_provider === 'wasapla' ? $bo?->whatsapp_instance_id : '' }}">
                    <button type="button" onclick="copyInputVal('wasaplaInstanceId', this)" title="Salin" style="position: absolute; right: 8px; background: transparent; border: 1px solid #CBD5E1; border-radius: 6px; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #64748B;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                </div>
                <p class="form-hint">Matching Base URL unik peranti WhatsApp Wasapla anda.</p>
            </div>

            <!-- 2. API Key Pelanggan (Bearer Token) -->
            <div class="form-group">
                <label class="form-label" for="wasaplaApiToken">API Key Pelanggan (Bearer Token) <span style="color: #DC2626;">*</span></label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" name="whatsapp_api_token" id="wasaplaApiToken" class="form-input" style="padding-right: 42px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.88rem;" placeholder="wsp_live_c1d70d0d53395cb5ae5ea8be533ef2855b6a593abbcd602d" value="{{ $bo?->whatsapp_provider === 'wasapla' ? $bo?->whatsapp_api_token : '' }}">
                    <button type="button" onclick="copyInputVal('wasaplaApiToken', this)" title="Salin" style="position: absolute; right: 8px; background: transparent; border: 1px solid #CBD5E1; border-radius: 6px; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #64748B;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                </div>
                <p class="form-hint">Kunci API akaun Wasapla anda (disertakan sebagai Bearer Token).</p>
            </div>

            <!-- 3. Paired Domain (X-App-Domain) -->
            <div class="form-group">
                <label class="form-label" for="wasaplaDomain">Paired Domain (X-App-Domain) <span style="color: #DC2626;">*</span></label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" name="whatsapp_domain" id="wasaplaDomain" class="form-input" style="padding-right: 42px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.88rem;" placeholder="demo.wasapla.org" value="{{ $bo?->whatsapp_domain ?: (request()?->getHost() ?: 'pios.com.my') }}">
                    <button type="button" onclick="copyInputVal('wasaplaDomain', this)" title="Salin" style="position: absolute; right: 8px; background: transparent; border: 1px solid #CBD5E1; border-radius: 6px; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #64748B;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                </div>
                <p class="form-hint">Domain berdaftar yang dipautkan dalam 4-Layer Guard Wasapla.</p>
            </div>

            <!-- 4. Webhook Secret (X-Bot-Token) -->
            <div class="form-group">
                <label class="form-label" for="wasaplaBotToken">Webhook Secret (X-Bot-Token)</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" name="whatsapp_bot_token" id="wasaplaBotToken" class="form-input" style="padding-right: 42px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.88rem;" placeholder="whsec_ac83474b324d64429b648e4ce0174c3de0613c0f" value="{{ $bo?->whatsapp_provider === 'wasapla' ? $bo?->whatsapp_bot_token : '' }}">
                    <button type="button" onclick="copyInputVal('wasaplaBotToken', this)" title="Salin" style="position: absolute; right: 8px; background: transparent; border: 1px solid #CBD5E1; border-radius: 6px; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #64748B;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                </div>
                <p class="form-hint">Kunci rahsia webhook / bot token Wasapla (disertakan sebagai header X-Bot-Token).</p>
            </div>

            <!-- Keselamatan 4 Lapisan Box (Mengikut Sistem Wasapla) -->
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px 20px; margin-top: 18px;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <path d="M9 12l2 2 4-4"></path>
                    </svg>
                    <div>
                        <div style="font-weight: 700; color: #0F172A; font-size: 0.95rem;">Keselamatan 4 Lapisan:</div>
                        <p style="margin: 4px 0 0 0; color: #475569; font-size: 0.88rem; line-height: 1.55;">
                            Setiap panggilan API ke Matching URL disahkan melalui API Key aktif, padanan nama instance, status langganan aktif, dan kesepadanan domain yang dipautkan.
                        </p>
                    </div>
                </div>
            </div>

            <div style="margin-top: 18px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn-test" onclick="testConnection('wasapla')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Uji Sambungan (Ping API)
                </button>
                <button type="button" class="btn-test" onclick="openQrModal('wasapla')" style="color: #0F766E; border-color: #0D9488; background: #CCFBF1;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Imbas Kod QR WhatsApp
                </button>
                <button type="button" class="btn-test" onclick="openTestMessageModal('wasapla')" style="color: #0D9488; border-color: #99F6E4; background: #F0FDFA;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Hantar Mesej Ujian Ke Nombor Saya
                </button>
            </div>

            <div id="wasaplaFeedback" class="test-feedback-box"></div>
        </div>

        <!-- Wati.io Configuration Section (Full Width) -->
        <div id="watiConfigSection" class="config-card" style="{{ ($bo?->whatsapp_provider ?? '') === 'wati' ? 'display: block;' : 'display: none;' }}">
            <h3 class="config-section-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#4338CA" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Konfigurasi Wati.io (Official WhatsApp API)
            </h3>

            <div class="form-group">
                <label class="form-label" for="watiEndpoint">Wati Endpoint URL <span style="color: #DC2626;">*</span></label>
                <input type="text" name="whatsapp_endpoint" id="watiEndpoint" class="form-input" placeholder="Contoh: https://live-mt-server.wati.io/302812" value="{{ $bo?->whatsapp_provider === 'wati' ? ($bo?->whatsapp_endpoint ?: $bo?->whatsapp_instance_id) : '' }}">
                <p class="form-hint">Endpoint URL atau Tenant Server URL daripada dashboard Wati.io anda.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="watiApiToken">Wati Access Token <span style="color: #DC2626;">*</span></label>
                <input type="text" name="whatsapp_api_token" id="watiApiToken" class="form-input" placeholder="Contoh: Bearer token atau API Token anda di Wati" value="{{ $bo?->whatsapp_provider === 'wati' ? $bo?->whatsapp_api_token : '' }}">
                <p class="form-hint">Dapatkan Access Token ini di dashboard Wati.io (Menu: Connector > API).</p>
            </div>

            <div class="tutorial-box">
                <strong>Panduan Ringkas Pengaktifan Wati.io:</strong>
                <ol>
                    <li>Buka akaun di <a href="https://wati.io" target="_blank" rel="noopener noreferrer" style="color: #4338CA; font-weight: 700; text-decoration: underline;">wati.io</a>.</li>
                    <li>Pergi ke menu <strong>Connector &gt; API</strong> untuk menyalin <strong>API Endpoint</strong> dan <strong>Access Token</strong>.</li>
                    <li>Tampalkan maklumat tersebut ke ruangan di atas dan klik Uji Sambungan.</li>
                </ol>
            </div>

            <div style="margin-top: 18px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn-test" onclick="testConnection('wati')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Uji Sambungan (Ping API)
                </button>
                <button type="button" class="btn-test" onclick="openTestMessageModal('wati')" style="color: #4338CA; border-color: #C7D2FE; background: #EEF2FF;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Hantar Mesej Ujian Ke Nombor Saya
                </button>
            </div>

            <div id="watiFeedback" class="test-feedback-box"></div>
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

    <!-- WhatsApp QR Code Modal for Wasapla Pairing -->
    <div id="qrCodeModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.7); z-index: 99999; align-items: center; justify-content: center; padding: 16px;">
        <div style="background: #FFFFFF; border-radius: 18px; max-width: 460px; width: 100%; padding: 24px 28px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); text-align: center; position: relative;">
            <button type="button" onclick="closeQrModal()" style="position: absolute; top: 16px; right: 16px; background: transparent; border: none; font-size: 1.5rem; cursor: pointer; color: #64748B; padding: 4px; line-height: 1;">&times;</button>

            <div style="width: 52px; height: 52px; border-radius: 50%; background: #CCFBF1; color: #0D9488; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>

            <h3 style="margin: 0 0 6px; font-size: 1.2rem; font-weight: 800; color: #0F172A;">
                Pautkan Peranti WhatsApp
            </h3>
            <p style="font-size: 0.85rem; color: #64748B; margin: 0 0 16px; line-height: 1.45;">
                Imbas kod QR di bawah menggunakan aplikasi WhatsApp pada telefon anda untuk mengaktifkan penghantaran mesej automatik.
            </p>

            <div id="qrLoadingBox" style="padding: 40px 20px; font-size: 0.88rem; color: #0D9488; background: #F0FDFA; border-radius: 12px; border: 1px dashed #99F6E4; margin-bottom: 16px;">
                ⏳ Sedang memuatkan Kod QR Wasapla...
            </div>

            <div id="qrDisplayBox" style="display: none; margin-bottom: 16px;">
                <div style="background: #FFFFFF; padding: 12px; border-radius: 14px; border: 2px solid #E2E8F0; display: inline-block; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                    <img id="qrCodeImg" src="" alt="WhatsApp QR Code" style="width: 220px; height: 220px; display: block; border-radius: 8px;">
                </div>
                <div style="margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="dot" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #F59E0B;"></span>
                    <span id="qrStatusLabel" style="font-size: 0.82rem; font-weight: 600; color: #64748B;">Menunggu imbasan telefon...</span>
                </div>
            </div>

            <div id="qrConnectedBox" style="display: none; padding: 24px 16px; background: #ECFDF5; border-radius: 12px; border: 1.5px solid #A7F3D0; margin-bottom: 16px;">
                <div style="font-size: 2.2rem; margin-bottom: 6px;">🎉</div>
                <div style="font-weight: 800; font-size: 1.05rem; color: #065F46;">WhatsApp Berjaya Disambungkan!</div>
                <p style="font-size: 0.84rem; color: #047857; margin: 6px 0 0; line-height: 1.4;">
                    Peranti anda kini aktif dan sedia untuk menghantar mesej ujian atau promosi produk.
                </p>
            </div>

            <div id="qrErrorBox" style="display: none; padding: 14px 16px; background: #FEF2F2; border-radius: 12px; border: 1px solid #FECACA; color: #991B1B; font-size: 0.84rem; margin-bottom: 16px; text-align: left;">
            </div>

            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; text-align: left; font-size: 0.8rem; color: #334155; line-height: 1.5; margin-bottom: 18px;">
                <strong style="color: #0F172A; display: block; margin-bottom: 4px;">Cara Mengimbas:</strong>
                <ol style="margin: 0; padding-left: 18px;">
                    <li>Buka aplikasi WhatsApp di telefon anda.</li>
                    <li>Pergi ke <strong>Tetapan (Settings)</strong> &gt; <strong>Peranti Dipautkan (Linked Devices)</strong>.</li>
                    <li>Tekan <strong>Pautkan Peranti (Link a Device)</strong> dan halakan kamera ke kod QR ini.</li>
                </ol>
            </div>

            <div style="display: flex; justify-content: center; gap: 10px;">
                <button type="button" id="refreshQrBtn" onclick="loadWhatsAppQr()" class="btn-test" style="font-size: 0.84rem; padding: 8px 16px;">
                    🔄 Muat Semula QR
                </button>
                <button type="button" onclick="closeQrModal()" class="btn-save-whatsapp" style="font-size: 0.84rem; padding: 8px 20px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentTestingProvider = 'wasapla';

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


        function selectProvider(provider, element) {
            document.getElementById('selectedProviderInput').value = provider;

            // Highlight selected card
            document.querySelectorAll('.provider-card').forEach(card => card.classList.remove('selected'));
            if (element) {
                element.classList.add('selected');
            }

            // Toggle config sections
            const wasaplaSection = document.getElementById('wasaplaConfigSection');
            const watiSection = document.getElementById('watiConfigSection');
            const manualSection = document.getElementById('manualConfigSection');

            if (wasaplaSection) wasaplaSection.style.display = (provider === 'wasapla') ? 'block' : 'none';
            if (watiSection) watiSection.style.display = (provider === 'wati') ? 'block' : 'none';
            if (manualSection) manualSection.style.display = (provider === 'manual') ? 'block' : 'none';

            // Ensure correct input name fields don't collide
            const wasaplaToken = document.getElementById('wasaplaApiToken');
            const wasaplaBot = document.getElementById('wasaplaBotToken');
            const wasaplaInstance = document.getElementById('wasaplaInstanceId');
            const wasaplaDomain = document.getElementById('wasaplaDomain');
            const watiToken = document.getElementById('watiApiToken');
            const watiEndpoint = document.getElementById('watiEndpoint');

            if (provider === 'wasapla') {
                if (wasaplaToken) wasaplaToken.disabled = false;
                if (wasaplaBot) wasaplaBot.disabled = false;
                if (wasaplaInstance) wasaplaInstance.disabled = false;
                if (wasaplaDomain) wasaplaDomain.disabled = false;
                if (watiToken) watiToken.disabled = true;
                if (watiEndpoint) watiEndpoint.disabled = true;
            } else if (provider === 'wati') {
                if (wasaplaToken) wasaplaToken.disabled = true;
                if (wasaplaBot) wasaplaBot.disabled = true;
                if (wasaplaInstance) wasaplaInstance.disabled = true;
                if (wasaplaDomain) wasaplaDomain.disabled = true;
                if (watiToken) watiToken.disabled = false;
                if (watiEndpoint) watiEndpoint.disabled = false;
            } else {
                if (wasaplaToken) wasaplaToken.disabled = true;
                if (wasaplaBot) wasaplaBot.disabled = true;
                if (wasaplaInstance) wasaplaInstance.disabled = true;
                if (wasaplaDomain) wasaplaDomain.disabled = true;
                if (watiToken) watiToken.disabled = true;
                if (watiEndpoint) watiEndpoint.disabled = true;
            }
        }

        function copyInputVal(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input || !input.value) return;
            navigator.clipboard.writeText(input.value).then(() => {
                const origHtml = btn.innerHTML;
                btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                setTimeout(() => { btn.innerHTML = origHtml; }, 1500);
            }).catch(() => {});
        }

        // Initialize state on load
        document.addEventListener('DOMContentLoaded', () => {
            const initialProvider = document.getElementById('selectedProviderInput')?.value || 'manual';
            selectProvider(initialProvider, document.querySelector(`.provider-card[onclick*="'${initialProvider}'"]`));
        });

        // Test Connection Ping
        function testConnection(provider) {
            currentTestingProvider = provider;
            const feedbackEl = document.getElementById(provider + 'Feedback');
            if (!feedbackEl) return;

            let token = '';
            let botToken = '';
            let instanceId = '';
            let endpoint = '';
            let domain = '';

            if (provider === 'wasapla') {
                token = document.getElementById('wasaplaApiToken')?.value || '';
                botToken = document.getElementById('wasaplaBotToken')?.value || '';
                instanceId = document.getElementById('wasaplaInstanceId')?.value || '';
                domain = document.getElementById('wasaplaDomain')?.value || '';
                if ((!token.trim() && !botToken.trim()) || !instanceId.trim()) {
                    showFeedback(feedbackEl, false, 'Sila masukkan Matching Base URL dan API Key Pelanggan terlebih dahulu.');
                    return;
                }
            } else if (provider === 'wati') {
                endpoint = document.getElementById('watiEndpoint')?.value || '';
                token = document.getElementById('watiApiToken')?.value || '';
                if (!token.trim()) {
                    showFeedback(feedbackEl, false, 'Sila masukkan Wati.io Access Token terlebih dahulu.');
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
                    whatsapp_bot_token: botToken,
                    whatsapp_instance_id: instanceId,
                    whatsapp_endpoint: endpoint,
                    whatsapp_domain: domain,
                    send_test_message: false
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (status >= 200 && status < 300 && body.success) {
                    if (body.is_device_connected === false) {
                        showFeedback(
                            feedbackEl,
                            'warning',
                            '⚠️ ' + (body.message || 'Kredensial API sah tetapi peranti WhatsApp belum diimbas.') +
                            '<div style="margin-top: 10px;"><button type="button" onclick="openQrModal(\'' + provider + '\')" class="btn-test" style="background: #0D9488; color: #FFFFFF; border: none; padding: 7px 16px; font-size: 0.84rem; border-radius: 8px; cursor: pointer; font-weight: 700;">📱 Buka Kod QR Sekarang</button></div>'
                        );
                    } else {
                        showFeedback(feedbackEl, true, '✅ ' + (body.message || 'Sambungan berjaya!') + (body.device ? ' (' + body.device + ')' : ''));
                    }
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
            let botToken = '';
            let instanceId = '';
            let endpoint = '';
            let domain = '';

            if (provider === 'wasapla') {
                token = document.getElementById('wasaplaApiToken')?.value || '';
                botToken = document.getElementById('wasaplaBotToken')?.value || '';
                instanceId = document.getElementById('wasaplaInstanceId')?.value || '';
                domain = document.getElementById('wasaplaDomain')?.value || '';
            } else if (provider === 'wati') {
                endpoint = document.getElementById('watiEndpoint')?.value || '';
                token = document.getElementById('watiApiToken')?.value || '';
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
                    whatsapp_bot_token: botToken,
                    whatsapp_instance_id: instanceId,
                    whatsapp_endpoint: endpoint,
                    whatsapp_domain: domain,
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

        let qrPollTimer = null;

        function openQrModal(provider) {
            currentTestingProvider = provider || 'wasapla';
            const modal = document.getElementById('qrCodeModal');
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
            loadWhatsAppQr();
        }

        function closeQrModal() {
            if (qrPollTimer) {
                clearInterval(qrPollTimer);
                qrPollTimer = null;
            }
            const modal = document.getElementById('qrCodeModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        function loadWhatsAppQr() {
            const provider = currentTestingProvider || 'wasapla';
            let token = '';
            let botToken = '';
            let instanceId = '';
            let domain = '';

            if (provider === 'wasapla') {
                token = document.getElementById('wasaplaApiToken')?.value || '';
                botToken = document.getElementById('wasaplaBotToken')?.value || '';
                instanceId = document.getElementById('wasaplaInstanceId')?.value || '';
                domain = document.getElementById('wasaplaDomain')?.value || '';
            }

            const loadingBox = document.getElementById('qrLoadingBox');
            const displayBox = document.getElementById('qrDisplayBox');
            const connectedBox = document.getElementById('qrConnectedBox');
            const errorBox = document.getElementById('qrErrorBox');
            const qrImg = document.getElementById('qrCodeImg');
            const statusLabel = document.getElementById('qrStatusLabel');

            if ((!token.trim() && !botToken.trim()) || !instanceId.trim()) {
                if (loadingBox) loadingBox.style.display = 'none';
                if (displayBox) displayBox.style.display = 'none';
                if (connectedBox) connectedBox.style.display = 'none';
                if (errorBox) {
                    errorBox.style.display = 'block';
                    errorBox.innerHTML = '<strong>Sila isi Matching Base URL dan API Key Pelanggan</strong> terlebih dahulu sebelum memuatkan Kod QR.';
                }
                return;
            }

            if (loadingBox) loadingBox.style.display = 'block';
            if (displayBox) displayBox.style.display = 'none';
            if (connectedBox) connectedBox.style.display = 'none';
            if (errorBox) errorBox.style.display = 'none';

            fetch('{{ route("business.settings.whatsapp.qr") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    whatsapp_provider: provider,
                    whatsapp_api_token: token,
                    whatsapp_bot_token: botToken,
                    whatsapp_instance_id: instanceId,
                    whatsapp_domain: domain
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (loadingBox) loadingBox.style.display = 'none';

                if (status >= 200 && status < 300 && body.success) {
                    if (body.status === 'connected' || body.is_connected) {
                        if (displayBox) displayBox.style.display = 'none';
                        if (connectedBox) connectedBox.style.display = 'block';
                        if (qrPollTimer) {
                            clearInterval(qrPollTimer);
                            qrPollTimer = null;
                        }
                    } else if (body.qr) {
                        if (connectedBox) connectedBox.style.display = 'none';
                        if (displayBox) displayBox.style.display = 'block';
                        if (qrImg) qrImg.src = body.qr;
                        if (statusLabel) statusLabel.textContent = 'Menunggu imbasan telefon (Status: ' + (body.status || 'qr') + ')...';

                        if (!qrPollTimer) {
                            qrPollTimer = setInterval(() => {
                                pollQrStatus();
                            }, 6000);
                        }
                    } else {
                        if (errorBox) {
                            errorBox.style.display = 'block';
                            errorBox.innerHTML = '❌ ' + (body.message || 'Kod QR tidak tersedia buat masa ini.');
                        }
                    }
                } else {
                    if (displayBox) displayBox.style.display = 'none';
                    if (connectedBox) connectedBox.style.display = 'none';
                    if (errorBox) {
                        errorBox.style.display = 'block';
                        errorBox.innerHTML = '❌ ' + (body.message || 'Gagal menjana Kod QR daripada Wasapla.');
                    }
                    if (qrPollTimer) {
                        clearInterval(qrPollTimer);
                        qrPollTimer = null;
                    }
                }
            })
            .catch(() => {
                if (loadingBox) loadingBox.style.display = 'none';
                if (errorBox) {
                    errorBox.style.display = 'block';
                    errorBox.innerHTML = '❌ Ralat rangkaian semasa memuatkan Kod QR.';
                }
            });
        }

        function pollQrStatus() {
            const provider = currentTestingProvider || 'wasapla';
            const token = document.getElementById('wasaplaApiToken')?.value || '';
            const botToken = document.getElementById('wasaplaBotToken')?.value || '';
            const instanceId = document.getElementById('wasaplaInstanceId')?.value || '';
            const domain = document.getElementById('wasaplaDomain')?.value || '';

            fetch('{{ route("business.settings.whatsapp.qr") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    whatsapp_provider: provider,
                    whatsapp_api_token: token,
                    whatsapp_bot_token: botToken,
                    whatsapp_instance_id: instanceId,
                    whatsapp_domain: domain
                })
            })
            .then(res => res.json())
            .then(body => {
                if (body && body.success) {
                    if (body.status === 'connected' || body.is_connected) {
                        const displayBox = document.getElementById('qrDisplayBox');
                        const connectedBox = document.getElementById('qrConnectedBox');
                        if (displayBox) displayBox.style.display = 'none';
                        if (connectedBox) connectedBox.style.display = 'block';
                        if (qrPollTimer) {
                            clearInterval(qrPollTimer);
                            qrPollTimer = null;
                        }
                    } else if (body.qr) {
                        const qrImg = document.getElementById('qrCodeImg');
                        if (qrImg && body.qr !== qrImg.src) {
                            qrImg.src = body.qr;
                        }
                    }
                }
            })
            .catch(() => {});
        }

        function showFeedback(el, status, msg) {
            if (!el) return;
            el.style.display = 'block';
            if (status === 'warning') {
                el.style.background = '#FFFBEB';
                el.style.border = '1px solid #FDE68A';
                el.style.color = '#92400E';
            } else if (status === true) {
                el.style.background = '#ECFDF5';
                el.style.border = '1px solid #A7F3D0';
                el.style.color = '#065F46';
            } else {
                el.style.background = '#FEF2F2';
                el.style.border = '1px solid #FECACA';
                el.style.color = '#991B1B';
            }
            el.innerHTML = msg;
        }
    </script>
    @endpush

</x-business-layout>
