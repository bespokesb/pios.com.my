<x-business-layout title="PIOS - WhatsApp Promotions & Scheduled Blast" headerTitle="WA Promotions" bodyClass="fees-body" mainClass="fees-main" mobileHeaderClass="mobile-fees-header">

    <style>
        .fees-main,
        .fees-main * {
            font-family: var(--font-sans, 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
        }

        h1, h2, h3, h4,
        .promo-hero-title,
        .promo-card-title {
            font-family: var(--font-heading, 'Poppins', var(--font-sans, sans-serif)) !important;
        }

        .fees-main {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        .promo-hero-banner {
            background: linear-gradient(135deg, #0F3B36 0%, #0D9488 100%);
            border-radius: 18px;
            padding: 26px 30px;
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(13, 148, 136, 0.18);
        }

        .promo-hero-title {
            margin: 6px 0 4px;
            font-size: 1.55rem;
            font-weight: 800;
            color: #FFFFFF;
        }

        .promo-hero-sub {
            margin: 0;
            font-size: 0.9rem;
            color: #CCFBF1;
            max-width: 660px;
            line-height: 1.5;
        }

        .promo-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        @media (max-width: 980px) {
            .promo-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .promo-stat-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
        }

        .promo-stat-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .promo-stat-val {
            font-size: 1.65rem;
            font-weight: 800;
            color: #0F172A;
            margin-top: 4px;
        }

        .promo-layout-grid {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1100px) {
            .promo-layout-grid {
                grid-template-columns: 1fr;
            }
        }

        .promo-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
        }

        .promo-card-title {
            font-size: 1.12rem;
            font-weight: 800;
            color: #0F172A;
            margin: 0 0 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 0.84rem;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 6px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 13px;
            border: 1.5px solid #CBD5E1;
            border-radius: 10px;
            font-size: 0.88rem;
            color: #0F172A;
            background: #FFFFFF;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: #0D9488;
        }

        .template-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 11px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            border: 1px solid #CCFBF1;
            background: #F0FDFA;
            color: #0F766E;
            cursor: pointer;
            transition: all 0.15s;
        }

        .template-pill:hover {
            background: #0D9488;
            color: #FFFFFF;
            border-color: #0D9488;
        }

        .target-radio-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        @media (max-width: 560px) {
            .target-radio-grid {
                grid-template-columns: 1fr;
            }
        }

        .target-option-label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            background: #FFFFFF;
            transition: all 0.15s;
        }

        .target-option-label.active {
            border-color: #0D9488;
            background: #F0FDFA;
            color: #0F766E;
        }

        .wa-preview-phone {
            background: #E5DDD5;
            border-radius: 16px;
            padding: 16px;
            border: 1px solid #CBD5E1;
        }

        .wa-preview-bubble {
            background: #FFFFFF;
            border-radius: 12px 12px 12px 2px;
            padding: 12px 14px;
            max-width: 92%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
            font-size: 0.84rem;
            line-height: 1.5;
            color: #111827;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .campaign-item-card {
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 12px;
            background: #FAFAFA;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .btn-primary-promo {
            width: 100%;
            background: #0D9488;
            color: #FFFFFF;
            border: none;
            padding: 12px 20px;
            border-radius: 11px;
            font-size: 0.94rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-primary-promo:hover {
            background: #0F766E;
        }

        .btn-mini {
            padding: 5px 10px;
            border-radius: 7px;
            font-size: 0.76rem;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid #CBD5E1;
            background: #FFFFFF;
            color: #334155;
        }

        /* Dark Mode Support */
        body.dark-mode-active .promo-stat-card,
        body.dark-mode-active .promo-card {
            background: #1E293B !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
        }

        body.dark-mode-active .promo-stat-val,
        body.dark-mode-active .promo-card-title,
        body.dark-mode-active .form-label {
            color: #F8FAFC !important;
        }

        body.dark-mode-active .form-input,
        body.dark-mode-active .form-select,
        body.dark-mode-active .form-textarea,
        body.dark-mode-active .target-option-label,
        body.dark-mode-active .campaign-item-card {
            background: #0F172A !important;
            border-color: #334155 !important;
            color: #F8FAFC !important;
        }

        body.dark-mode-active .target-option-label.active {
            background: rgba(13, 148, 136, 0.2) !important;
            border-color: #0D9488 !important;
        }
    </style>

    <!-- Mobile Header -->
    <div class="mobile-fees-header">
        <a href="{{ route('business.dashboard') }}" class="mobile-back-btn" aria-label="Back">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </a>
        <h1 class="mobile-fees-title">WA Promotions</h1>
        <button type="button" class="mobile-menu-toggle" onclick="toggleMobileFullscreenMenu()" aria-label="Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
    </div>

    <!-- Flash Alerts -->
    @if (session('success'))
        <div style="background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; padding: 14px 18px; border-radius: 12px; margin-bottom: 18px; font-weight: 600; font-size: 0.9rem;">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 14px 18px; border-radius: 12px; margin-bottom: 18px; font-weight: 600; font-size: 0.9rem;">
            ❌ {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div style="background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; padding: 14px 18px; border-radius: 12px; margin-bottom: 18px; font-size: 0.86rem;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Hero Banner -->
    <div class="promo-hero-banner">
        <div>
            <span style="display: inline-block; background: rgba(231, 196, 106, 0.22); color: #FDE68A; padding: 4px 12px; border-radius: 999px; font-size: 0.74rem; font-weight: 700; text-transform: uppercase;">
                Pemasaran Automatik
            </span>
            <h2 class="promo-hero-title">WhatsApp Promotion &amp; Scheduled Blast</h2>
            <p class="promo-hero-sub">
                Bina kempen promosi produk, pilih kumpulan pelanggan sasaran, dan tetapkan tarikh serta masa untuk penghantaran automatik ke WhatsApp pelanggan anda.
            </p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 0.76rem; color: #CCFBF1; text-transform: uppercase;">Provider Aktif</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: #FDE68A;">
                {{ $bo->whatsAppProviderLabel() }}
            </div>
            <div style="font-size: 0.76rem; margin-top: 4px;">
                Status: <strong>{{ ucfirst($bo->whatsapp_status ?? 'unconfigured') }}</strong>
                &bull; <a href="{{ route('business.settings.index') }}" style="color: #FFFFFF; text-decoration: underline;">Tetapan</a>
            </div>
        </div>
    </div>

    @if (($bo->whatsapp_provider ?: 'manual') === 'manual' || ($bo->whatsapp_status ?? '') !== 'connected')
        <div style="background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.86rem; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div>
                ⚠️ <strong>Perhatian:</strong> Untuk penghantaran automatik &amp; <em>Scheduled Blast</em>, sila pastikan anda mengaktifkan <strong>Wasapla.org</strong> atau <strong>Wati.io</strong> serta mengimbas Kod QR WhatsApp di halaman Tetapan.
            </div>
            <a href="{{ route('business.settings.index') }}" style="background: #D97706; color: #FFFFFF; padding: 7px 14px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.8rem; white-space: nowrap;">
                Buka Tetapan WhatsApp
            </a>
        </div>
    @endif

    <!-- Stats Row -->
    <div class="promo-stats-grid">
        <div class="promo-stat-card">
            <div class="promo-stat-label">Jumlah Kempen</div>
            <div class="promo-stat-val">{{ $stats['total_campaigns'] }}</div>
        </div>
        <div class="promo-stat-card">
            <div class="promo-stat-label">Dalam Jadual</div>
            <div class="promo-stat-val" style="color: #D97706;">{{ $stats['scheduled_count'] }}</div>
        </div>
        <div class="promo-stat-card">
            <div class="promo-stat-label">Kempen Selesai</div>
            <div class="promo-stat-val" style="color: #0D9488;">{{ $stats['completed_count'] }}</div>
        </div>
        <div class="promo-stat-card">
            <div class="promo-stat-label">Mesej Dihantar</div>
            <div class="promo-stat-val" style="color: #16A34A;">{{ $stats['total_sent'] }}</div>
        </div>
    </div>

    <!-- Main 2-Column Builder & History -->
    <div class="promo-layout-grid">
        <!-- Left: Create Campaign / Scheduled Blast -->
        <div class="promo-card">
            <h3 class="promo-card-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2.2"><path d="M22 2L11 13"></path><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                Cipta Promosi / Jadual Blast Baru
            </h3>

            <form action="{{ route('business.promotions.store') }}" method="POST" id="createPromoForm">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="promoTitle">Tajuk Kempen <span style="color: #DC2626;">*</span></label>
                    <input type="text" name="title" id="promoTitle" class="form-input" required value="{{ old('title') }}" placeholder="Contoh: Promosi Hujung Minggu / Stok Baru">
                </div>

                <div class="form-group">
                    <label class="form-label" for="promoProduct">Pilih Produk (Pilihan)</label>
                    <select name="product_id" id="promoProduct" class="form-select" onchange="onProductChange(this)">
                        <option value="" data-name="" data-price="" data-link="" data-image="">-- Promosi Umum (Tanpa Produk Khusus) --</option>
                        @foreach ($products as $prod)
                            <option
                                value="{{ $prod->id }}"
                                data-name="{{ $prod->name }}"
                                data-price="RM {{ number_format((float) $prod->selling_price, 2) }}"
                                data-link="{{ $prod->publicUrl() }}"
                                data-image="{{ $prod->firstShareableImageUrl() ?? '' }}"
                                {{ (string) old('product_id') === (string) $prod->id ? 'selected' : '' }}
                            >
                                {{ $prod->name }} — RM {{ number_format((float) $prod->selling_price, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                        <label class="form-label" for="promoMessage" style="margin: 0;">Mesej Promosi WhatsApp <span style="color: #DC2626;">*</span></label>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <button type="button" class="template-pill" onclick="applyPromoTemplate('offer')">🔥 Tawaran</button>
                            <button type="button" class="template-pill" onclick="applyPromoTemplate('restock')">⚡ Stok Baru</button>
                            <button type="button" class="template-pill" onclick="applyPromoTemplate('vip')">👑 Khas VIP</button>
                        </div>
                    </div>
                    <textarea name="message" id="promoMessage" rows="6" class="form-textarea" required oninput="updateLivePreview()" placeholder="Tulis mesej promosi anda di sini...">{{ old('message', "Salam {name},\n\nKami ada tawaran istimewa untuk anda hari ini!\n\nProduk: *{product_name}*\nHarga: *{price}*\n\nKlik pautan di bawah untuk membuat pesanan terus:\n{link}") }}</textarea>
                    <div style="font-size: 0.74rem; color: #64748B; margin-top: 4px;">
                        Pemboleh ubah automatik: <code>{name}</code>, <code>{product_name}</code>, <code>{price}</code>, <code>{link}</code>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="promoMediaUrl">Pautan Gambar Promosi (Pilihan)</label>
                    <input type="url" name="media_url" id="promoMediaUrl" class="form-input" value="{{ old('media_url') }}" oninput="updateLivePreview()" placeholder="Auto guna gambar produk jika dipilih, atau tampal URL gambar">
                </div>

                <div class="form-group">
                    <label class="form-label">Pilih Penerima (Pelanggan) <span style="color: #DC2626;">*</span></label>
                    <div class="target-radio-grid">
                        <label class="target-option-label active" onclick="selectTargetType('all', this)">
                            <input type="radio" name="target_type" value="all" checked style="accent-color: #0D9488;">
                            <span>👥 Semua Pelanggan ({{ $customers->count() }})</span>
                        </label>
                        <label class="target-option-label" onclick="selectTargetType('vip', this)">
                            <input type="radio" name="target_type" value="vip" style="accent-color: #0D9488;">
                            <span>👑 VIP Sahaja ({{ $customers->where('is_vip', true)->count() }})</span>
                        </label>
                        <label class="target-option-label" onclick="selectTargetType('selected', this)">
                            <input type="radio" name="target_type" value="selected" style="accent-color: #0D9488;">
                            <span>☑️ Pilih Pelanggan</span>
                        </label>
                        <label class="target-option-label" onclick="selectTargetType('custom', this)">
                            <input type="radio" name="target_type" value="custom" style="accent-color: #0D9488;">
                            <span>📱 Nombor Khas</span>
                        </label>
                    </div>
                </div>

                <!-- Selected Customers Box -->
                <div id="selectedCustomersBox" style="display: none; border: 1px solid #CBD5E1; border-radius: 12px; padding: 12px; margin-bottom: 16px; background: #F8FAFC;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; gap: 8px;">
                        <input type="text" id="customerSearchFilter" class="form-input" placeholder="Cari nama / no telefon..." oninput="filterCustomerList(this.value)" style="padding: 6px 10px; font-size: 0.8rem;">
                        <button type="button" onclick="toggleSelectAllCustomers()" class="btn-mini" style="white-space: nowrap;">Pilih Semua</button>
                    </div>
                    <div style="max-height: 170px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;" id="customerChecklistWrap">
                        @forelse ($customers as $cust)
                            <label class="customer-check-row" data-search="{{ strtolower($cust->name.' '.$cust->hp_no) }}" style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 0.82rem; cursor: pointer;">
                                <span style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="target_customer_ids[]" value="{{ $cust->id }}" class="promo-customer-checkbox" style="accent-color: #0D9488;">
                                    <strong>{{ $cust->name }}</strong>
                                    @if ($cust->is_vip)
                                        <span style="background: #FEF3C7; color: #92400E; font-size: 0.68rem; padding: 1px 6px; border-radius: 999px; font-weight: 700;">VIP</span>
                                    @endif
                                </span>
                                <span style="color: #64748B; font-size: 0.78rem;">{{ $cust->hp_no }}</span>
                            </label>
                        @empty
                            <div style="font-size: 0.82rem; color: #64748B; text-align: center; padding: 12px;">Tiada pelanggan berdaftar.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Custom Phones Box -->
                <div id="customPhonesBox" class="form-group" style="display: none;">
                    <label class="form-label" for="customPhonesInput">Masukkan Nombor Telefon (Asingkan dengan baris baru atau koma)</label>
                    <textarea name="custom_phones" id="customPhonesInput" rows="3" class="form-textarea" placeholder="Contoh:&#10;0123456789&#10;601111458944">{{ old('custom_phones') }}</textarea>
                </div>

                <!-- Send Mode: Now vs Scheduled -->
                <div class="form-group">
                    <label class="form-label">Masa Penghantaran <span style="color: #DC2626;">*</span></label>
                    <div class="target-radio-grid">
                        <label class="target-option-label active" onclick="selectSendMode('now', this)">
                            <input type="radio" name="send_mode" value="now" checked style="accent-color: #0D9488;">
                            <span>⚡ Hantar Sekarang</span>
                        </label>
                        <label class="target-option-label" onclick="selectSendMode('schedule', this)">
                            <input type="radio" name="send_mode" value="schedule" style="accent-color: #0D9488;">
                            <span>🗓️ Jadualkan Tarikh &amp; Masa</span>
                        </label>
                    </div>
                </div>

                <div id="scheduledAtBox" class="form-group" style="display: none; background: #FFFBEB; border: 1px solid #FDE68A; padding: 14px; border-radius: 12px;">
                    <label class="form-label" for="scheduledAtInput" style="color: #92400E;">Tetapkan Tarikh &amp; Masa Auto-Hantar <span style="color: #DC2626;">*</span></label>
                    <input type="datetime-local" name="scheduled_at" id="scheduledAtInput" class="form-input" value="{{ old('scheduled_at', now()->addHour()->format('Y-m-d\TH:i')) }}">
                    <p style="margin: 6px 0 0; font-size: 0.76rem; color: #B45309;">
                        Sistem akan menghantar mesej promosi ini secara automatik apabila tiba tarikh &amp; masa yang ditetapkan.
                    </p>
                </div>

                <button type="submit" id="promoSubmitBtn" class="btn-primary-promo">
                    🚀 Hantar Blast Sekarang
                </button>
            </form>
        </div>

        <!-- Right: Live Preview + Campaign History -->
        <div>
            <!-- Live WhatsApp Bubble Preview -->
            <div class="promo-card">
                <h3 class="promo-card-title">
                    <span style="color: #25D366;">💬</span> Pratonton Mesej WhatsApp
                </h3>
                <div class="wa-preview-phone">
                    <div class="wa-preview-bubble">
                        <div id="previewImageWrap" style="display: none; margin-bottom: 8px;">
                            <img id="previewImageEl" src="" alt="Preview" style="width: 100%; max-height: 180px; object-fit: cover; border-radius: 8px;">
                        </div>
                        <div id="previewMessageText"></div>
                    </div>
                </div>
            </div>

            <!-- Scheduled & Past Campaigns -->
            <div class="promo-card">
                <h3 class="promo-card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0D9488" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Senarai Kempen &amp; Jadual Blast
                </h3>

                @forelse ($campaigns as $camp)
                    @php
                        $badgeColors = match ($camp->status) {
                            'scheduled' => ['bg' => '#FEF3C7', 'text' => '#92400E'],
                            'completed' => ['bg' => '#DCFCE7', 'text' => '#166534'],
                            'partial' => ['bg' => '#DBEAFE', 'text' => '#1E40AF'],
                            'processing' => ['bg' => '#E0E7FF', 'text' => '#3730A3'],
                            'cancelled' => ['bg' => '#F1F5F9', 'text' => '#475569'],
                            default => ['bg' => '#FEE2E2', 'text' => '#991B1B'],
                        };
                    @endphp
                    <div class="campaign-item-card">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 6px;">
                            <div>
                                <div style="font-weight: 800; font-size: 0.94rem;">{{ $camp->title }}</div>
                                <div style="font-size: 0.78rem; color: #64748B;">
                                    {{ $camp->targetTypeLabel() }} ({{ $camp->total_recipients }} penerima)
                                    @if ($camp->product)
                                        &bull; Produk: <strong>{{ $camp->product->name }}</strong>
                                    @endif
                                </div>
                            </div>
                            <span class="status-badge" style="background: {{ $badgeColors['bg'] }}; color: {{ $badgeColors['text'] }};">
                                {{ $camp->statusLabel() }}
                            </span>
                        </div>

                        <div style="font-size: 0.78rem; color: #475569; margin-bottom: 8px;">
                            @if ($camp->status === 'scheduled' && $camp->scheduled_at)
                                🗓️ Dijadualkan: <strong>{{ $camp->scheduled_at->format('d M Y, h:i A') }}</strong>
                            @elseif ($camp->sent_at)
                                ✅ Dihantar: <strong>{{ $camp->sent_at->format('d M Y, h:i A') }}</strong>
                                &bull; Berjaya: <strong style="color: #16A34A;">{{ $camp->sent_count }}</strong>
                                @if ($camp->failed_count > 0)
                                    / Gagal: <strong style="color: #DC2626;">{{ $camp->failed_count }}</strong>
                                @endif
                            @endif

                            @if (! empty($camp->result_summary['error']))
                                <div style="color: #DC2626; margin-top: 4px;">Ralat: {{ $camp->result_summary['error'] }}</div>
                            @endif
                        </div>

                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            @if ($camp->status === 'scheduled')
                                <form action="{{ route('business.promotions.run', $camp) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn-mini" style="background: #0D9488; color: #FFFFFF; border-color: #0D9488;">
                                        ⚡ Hantar Sekarang
                                    </button>
                                </form>
                                <form action="{{ route('business.promotions.cancel', $camp) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn-mini" style="color: #B45309; border-color: #FDE68A;">
                                        Batal Jadual
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('business.promotions.destroy', $camp) }}" method="POST" onsubmit="return confirm('Padam rekod kempen ini?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-mini" style="color: #DC2626; border-color: #FECACA;">
                                    Padam
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 30px 16px; color: #64748B; font-size: 0.88rem;">
                        Belum ada kempen promosi lagi. Cipta kempen pertama anda di sebelah kiri!
                    </div>
                @endforelse

                @if ($campaigns->hasPages())
                    <div style="margin-top: 14px;">
                        {{ $campaigns->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function selectTargetType(type, el) {
            el.closest('.target-radio-grid').querySelectorAll('.target-option-label').forEach(l => l.classList.remove('active'));
            el.classList.add('active');

            const selectedBox = document.getElementById('selectedCustomersBox');
            const customBox = document.getElementById('customPhonesBox');

            if (selectedBox) selectedBox.style.display = (type === 'selected') ? 'block' : 'none';
            if (customBox) customBox.style.display = (type === 'custom') ? 'block' : 'none';
        }

        function selectSendMode(mode, el) {
            el.closest('.target-radio-grid').querySelectorAll('.target-option-label').forEach(l => l.classList.remove('active'));
            el.classList.add('active');

            const scheduledBox = document.getElementById('scheduledAtBox');
            const submitBtn = document.getElementById('promoSubmitBtn');

            if (scheduledBox) scheduledBox.style.display = (mode === 'schedule') ? 'block' : 'none';
            if (submitBtn) {
                submitBtn.innerHTML = (mode === 'schedule') ? '🗓️ Jadualkan Blast Automatik' : '🚀 Hantar Blast Sekarang';
            }
        }

        function onProductChange(select) {
            const opt = select.options[select.selectedIndex];
            const image = opt.getAttribute('data-image') || '';
            const mediaInput = document.getElementById('promoMediaUrl');
            if (mediaInput) {
                mediaInput.value = image;
            }
            updateLivePreview();
        }

        function applyPromoTemplate(type) {
            const msgEl = document.getElementById('promoMessage');
            if (!msgEl) return;

            if (type === 'offer') {
                msgEl.value = "Salam {name},\n\n🔥 *TAWARAN ISTIMEWA HARI INI!*\n\nProduk: *{product_name}*\nHarga Promosi: *{price}*\n\nStok terhad. Klik pautan di bawah untuk tempahan pantas:\n{link}";
            } else if (type === 'restock') {
                msgEl.value = "Hai {name},\n\n⚡ *STOK BARU SUDAH TIBA!*\n\nProduk pilihan ramai *{product_name}* kini kembali ada stok pada harga *{price}*.\n\nTempah sekarang sebelum kehabisan:\n{link}";
            } else if (type === 'vip') {
                msgEl.value = "Salam sejahtera {name},\n\n👑 *PROMOSI KHAS AHLI VIP*\n\nNikmati akses eksklusif untuk produk *{product_name}* ({price}).\n\nKlik pautan ini untuk pembelian terus:\n{link}";
            }
            updateLivePreview();
        }

        function updateLivePreview() {
            const select = document.getElementById('promoProduct');
            const opt = select ? select.options[select.selectedIndex] : null;
            const prodName = opt?.getAttribute('data-name') || 'Produk Pilihan Anda';
            const prodPrice = opt?.getAttribute('data-price') || 'RM 0.00';
            const prodLink = opt?.getAttribute('data-link') || 'https://stag.pios.com.my/p/contoh';

            const rawMsg = document.getElementById('promoMessage')?.value || '';
            const rendered = rawMsg
                .replace(/\{name\}|\{customer_name\}|\{nama\}/g, 'Ahmad')
                .replace(/\{product_name\}|\{produk\}/g, prodName)
                .replace(/\{price\}|\{harga\}/g, prodPrice)
                .replace(/\{link\}|\{pautan\}/g, prodLink);

            const previewTextEl = document.getElementById('previewMessageText');
            if (previewTextEl) {
                previewTextEl.textContent = rendered;
            }

            const mediaUrl = document.getElementById('promoMediaUrl')?.value?.trim() || '';
            const imgWrap = document.getElementById('previewImageWrap');
            const imgEl = document.getElementById('previewImageEl');
            if (imgWrap && imgEl) {
                if (mediaUrl) {
                    imgEl.src = mediaUrl;
                    imgWrap.style.display = 'block';
                } else {
                    imgWrap.style.display = 'none';
                }
            }
        }

        function filterCustomerList(query) {
            const q = (query || '').toLowerCase().trim();
            document.querySelectorAll('.customer-check-row').forEach(row => {
                const text = row.getAttribute('data-search') || '';
                row.style.display = text.includes(q) ? 'flex' : 'none';
            });
        }

        function toggleSelectAllCustomers() {
            const boxes = Array.from(document.querySelectorAll('.promo-customer-checkbox'));
            const allChecked = boxes.length > 0 && boxes.every(b => b.checked);
            boxes.forEach(b => { b.checked = !allChecked; });
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateLivePreview();
        });
    </script>
    @endpush

</x-business-layout>

