<x-business-layout title="PIOS - Langganan (Subscriptions)" headerTitle="Langganan" bodyClass="fees-body" mainClass="fees-main" mobileHeaderClass="mobile-fees-header">

    <!-- Desktop Header Bar -->
    <div class="fees-topbar" style="margin-bottom: 24px;">
        <div class="topbar-left">
            <h1 class="fees-heading">Langganan &amp; Pelan Sistem (Subscriptions)</h1>
            <p class="fees-subheading">Pengurusan langganan BizGrowth Engine (BGE) dan sistem perniagaan daripada PIOS Owner</p>
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
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('success'))
        <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #065F46; font-size: 0.92rem; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; color: #991B1B; font-size: 0.92rem;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- BGE Core Subscription Highlight Banner -->
    <div style="background: linear-gradient(135deg, #134C49 0%, #174A78 100%); border-radius: 16px; padding: 26px 30px; color: #FFFFFF; margin-bottom: 30px; box-shadow: 0 10px 25px -5px rgba(19, 76, 73, 0.3); display: flex; flex-direction: column; gap: 16px;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <span style="font-size: 0.76rem; font-weight: 800; background: #E7C46A; color: #0F172A; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em;">Default Wajib Langgan</span>
                    @if ($bgeSubscription && $bgeSubscription->isActive())
                        <span style="font-size: 0.78rem; font-weight: 700; background: #10B981; color: #FFFFFF; padding: 3px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #FFF;"></span>
                            Aktif (Berbaki {{ $bgeSubscription->daysRemaining() }} Hari)
                        </span>
                    @elseif ($bgeSubscription && $bgeSubscription->status === 'pending_approval')
                        <span style="font-size: 0.78rem; font-weight: 700; background: #F59E0B; color: #FFFFFF; padding: 3px 12px; border-radius: 20px;">
                            Menunggu Pengesahan Bayaran
                        </span>
                    @else
                        <span style="font-size: 0.78rem; font-weight: 700; background: #EF4444; color: #FFFFFF; padding: 3px 12px; border-radius: 20px;">
                            Tamat Tempoh / Belum Dilanggan
                        </span>
                    @endif
                </div>
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.01em;">BizGrowth Engine (BGE)</h2>
                <p style="margin: 4px 0 0; font-size: 0.9rem; color: #E2E8F0; max-width: 650px; line-height: 1.5;">
                    Enjin pengurusan utama perniagaan PIOS anda. Akses katalog produk, pengurusan pesanan, pangkalan data pelanggan, dan jualan harian.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; align-items: flex-start; sm:align-items: flex-end; gap: 6px;">
                <div style="font-size: 1.7rem; font-weight: 900; color: #F8FAFC;">
                    RM {{ number_format((float)($bgeProduct?->price ?? 30.00), 2) }}
                    <span style="font-size: 0.85rem; font-weight: 500; color: #CBD5E1;">/ 30 hari</span>
                </div>
                <div style="font-size: 0.78rem; color: #E2E8F0;">
                    Expired every 30 hari dari tarikh pengesahan
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid rgba(255, 255, 255, 0.15); padding-top: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px;">
            <div style="font-size: 0.85rem; color: #F1F5F9;">
                @if ($bgeSubscription && $bgeSubscription->isActive() && $bgeSubscription->expires_at)
                    <span>Tarikh Luput Langganan: <strong>{{ $bgeSubscription->expires_at->format('d M Y, h:i A') }}</strong></span>
                @elseif ($bgeSubscription && $bgeSubscription->status === 'pending_approval')
                    <span>Status: <strong>Bayaran sedang disahkan oleh Rakan Kongsi anda</strong></span>
                @else
                    <span style="color: #FECACA;">Perhatian: Sila langgan atau perbaharui BGE untuk mengekalkan akses portal.</span>
                @endif
            </div>

            @if ($bgeProduct)
                <button type="button" onclick="openSubscribeModal({{ $bgeProduct->id }}, '{{ addslashes($bgeProduct->name) }}', '{{ number_format((float)$bgeProduct->price, 2) }}', '{{ addslashes($bgeProduct->payment_instructions ?? '') }}', '{{ $bgeProduct->qrCodeUrl() }}')"
                    style="background: #E7C46A; color: #0F172A; border: none; padding: 10px 22px; border-radius: 10px; font-weight: 700; font-size: 0.92rem; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <span>{{ ($bgeSubscription && $bgeSubscription->isActive()) ? 'Perbaharui Langganan (Renew)' : 'Langgan Sekarang' }}</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Available Plans / Products from Owner -->
    <div style="margin-bottom: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0F172A;">Katalog Pelan &amp; Sistem Tambahan</h3>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #64748B;">Pilih pelan atau sistem perniagaan dari PIOS Owner (POS, Modul Pengurusan, dsb.)</p>
            </div>
            @if ($partner)
                <div style="font-size: 0.8rem; color: #0D9488; background: #F0FDFA; border: 1px solid #CCFBF1; padding: 6px 12px; border-radius: 8px;">
                    Rakan Kongsi Pengesah: <strong>{{ $partner->applicant_name }}</strong> ({{ $partner->partner_no }})
                </div>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px;">
            @foreach ($products as $plan)
                @php
                    $userSub = $subscriptions->firstWhere('subscription_product_id', $plan->id);
                    $isActive = $userSub && $userSub->isActive();
                @endphp
                <div style="background: #FFFFFF; border: 1px solid {{ $plan->is_mandatory ? '#0D9488' : '#E2E8F0' }}; border-radius: 14px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    @if ($plan->is_mandatory)
                        <span style="position: absolute; top: -10px; right: 20px; background: #0D9488; color: #FFFFFF; font-size: 0.7rem; font-weight: 700; padding: 2px 10px; border-radius: 12px; text-transform: uppercase;">
                            Wajib Langgan
                        </span>
                    @endif

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                            <h4 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #1E293B;">{{ $plan->name }}</h4>
                            <span style="font-size: 1.15rem; font-weight: 800; color: #0D9488;">
                                RM {{ number_format((float)$plan->price, 2) }}
                            </span>
                        </div>

                        <p style="margin: 0 0 16px; font-size: 0.85rem; color: #64748B; line-height: 1.5; min-height: 48px;">
                            {{ $plan->description ?: 'Pelan sistem sokongan perniagaan disediakan oleh PIOS Owner.' }}
                        </p>

                        <div style="background: #F8FAFC; border-radius: 8px; padding: 10px 12px; margin-bottom: 16px; font-size: 0.78rem; color: #475569;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span>Tempoh Langganan:</span>
                                <strong>{{ $plan->period_days }} Hari</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Status Langganan Anda:</span>
                                @if ($isActive)
                                    <strong style="color: #059669;">Aktif ({{ $userSub->daysRemaining() }} hari)</strong>
                                @elseif ($userSub && $userSub->status === 'pending_approval')
                                    <strong style="color: #D97706;">Menunggu Pengesahan</strong>
                                @else
                                    <strong style="color: #64748B;">Belum Aktif</strong>
                                @endif
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="openSubscribeModal({{ $plan->id }}, '{{ addslashes($plan->name) }}', '{{ number_format((float)$plan->price, 2) }}', '{{ addslashes($plan->payment_instructions ?? '') }}', '{{ $plan->qrCodeUrl() }}')"
                        style="width: 100%; background: {{ $isActive ? '#F1F5F9' : '#0D9488' }}; color: {{ $isActive ? '#0F172A' : '#FFFFFF' }}; border: 1px solid {{ $isActive ? '#CBD5E1' : '#0D9488' }}; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: all 0.2s ease;">
                        {{ $isActive ? 'Perbaharui Langganan (Renew)' : 'Langgan & Bayar' }}
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Payment History & Official Receipts Table -->
    <div class="customer-table-card fee-desktop-table" style="background: #FFFFFF; border-radius: 14px; border: 1px solid #E2E8F0; overflow: hidden; margin-bottom: 30px;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0F172A;">Sejarah Bayaran &amp; Resit Rasmi</h3>
                <p style="margin: 3px 0 0; font-size: 0.82rem; color: #64748B;">Senarai rekod pembayaran langganan dan muat turun resit yang telah disahkan</p>
            </div>
            <span style="font-size: 0.78rem; font-weight: 600; color: #0D9488; background: #CCFBF1; padding: 4px 10px; border-radius: 12px;">
                {{ $payments->count() }} Transaksi
            </span>
        </div>

        <div class="table-responsive-wrapper" style="overflow-x: auto;">
            <table class="customer-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; font-size: 0.78rem; color: #475569; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th style="padding: 12px 18px;">No. Bayaran / Tarikh</th>
                        <th style="padding: 12px 18px;">Pelan / Produk</th>
                        <th style="padding: 12px 18px;">Jumlah &amp; Kaedah</th>
                        <th style="padding: 12px 18px;">Bukti Bayaran</th>
                        <th style="padding: 12px 18px;">Status</th>
                        <th style="padding: 12px 18px; text-align: right;">Tindakan / Resit</th>
                    </tr>
                </thead>
                <tbody style="font-size: 0.88rem; color: #1E293B;">
                    @forelse ($payments as $pay)
                        <tr style="border-bottom: 1px solid #F1F5F9;">
                            <td style="padding: 14px 18px;">
                                <div style="font-weight: 700; color: #0F172A;">{{ $pay->payment_no }}</div>
                                <div style="font-size: 0.76rem; color: #64748B;">{{ $pay->paid_at ? $pay->paid_at->format('d M Y, h:i A') : $pay->created_at->format('d M Y') }}</div>
                            </td>
                            <td style="padding: 14px 18px;">
                                <div style="font-weight: 600;">{{ $pay->product?->name }}</div>
                                <div style="font-size: 0.76rem; color: #64748B;">Tempoh: {{ $pay->period_days }} Hari</div>
                            </td>
                            <td style="padding: 14px 18px;">
                                <div style="font-weight: 700; color: #0D9488;">RM {{ number_format((float)$pay->amount, 2) }}</div>
                                <div style="font-size: 0.76rem; color: #64748B; text-transform: capitalize;">
                                    @if ($pay->payment_method === 'qr_code')
                                        QR Payment / DuitNow
                                    @elseif ($pay->payment_method === 'cash')
                                        Tunai (Cash)
                                    @else
                                        Pindahan Bank
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 14px 18px;">
                                @if ($pay->payment_proof)
                                    <a href="{{ $pay->paymentProofUrl() }}" target="_blank" style="color: #0D9488; font-size: 0.8rem; font-weight: 600; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                        </svg>
                                        Lihat Slip
                                    </a>
                                @else
                                    <span style="font-size: 0.78rem; color: #94A3B8;">Tiada fail</span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px;">
                                @if ($pay->status === 'approved')
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #ECFDF5; color: #059669; font-weight: 700; font-size: 0.76rem; padding: 4px 10px; border-radius: 12px;">
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #059669;"></span>
                                        Disahkan &amp; Aktif
                                    </span>
                                @elseif ($pay->status === 'rejected')
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #FEF2F2; color: #DC2626; font-weight: 700; font-size: 0.76rem; padding: 4px 10px; border-radius: 12px;" title="{{ $pay->rejection_reason }}">
                                        Ditolak
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #FFFBEB; color: #D97706; font-weight: 700; font-size: 0.76rem; padding: 4px 10px; border-radius: 12px;">
                                        Menunggu Pengesahan
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px; text-align: right;">
                                @if ($pay->isApproved() && $pay->receipt_no)
                                    <a href="{{ route('subscriptions.receipt', $pay->payment_no) }}" target="_blank"
                                        style="display: inline-flex; align-items: center; gap: 6px; background: #0D9488; color: #FFFFFF; text-decoration: none; font-size: 0.8rem; font-weight: 700; padding: 6px 14px; border-radius: 8px; transition: all 0.2s ease;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                            <rect x="6" y="14" width="12" height="8"></rect>
                                        </svg>
                                        <span>Resit ({{ $pay->receipt_no }})</span>
                                    </a>
                                @else
                                    <span style="font-size: 0.78rem; color: #94A3B8;">Resit belum sedia</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 36px 18px; text-align: center; color: #64748B;">
                                Tiada rekod transaksi pembayaran langganan buat masa ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Subscribe / Renew Modal -->
    <div id="subscribeModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 16px;">
        <div style="background: #FFFFFF; border-radius: 16px; width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); position: relative;">
            
            <div style="padding: 20px 24px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; background: #F8FAFC; border-radius: 16px 16px 0 0;">
                <div>
                    <h3 id="modalPlanTitle" style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0F172A;">Langgan Pelan</h3>
                    <div id="modalPlanPriceBadge" style="margin-top: 3px; font-size: 0.85rem; font-weight: 700; color: #0D9488;">RM 30.00 / 30 Hari</div>
                </div>
                <button type="button" onclick="closeSubscribeModal()" style="background: none; border: none; font-size: 1.5rem; color: #64748B; cursor: pointer; line-height: 1;">&times;</button>
            </div>

            <form action="{{ route('business.subscriptions.store') }}" method="POST" enctype="multipart/form-data" style="padding: 24px;">
                @csrf
                <input type="hidden" name="subscription_product_id" id="modalProductId" value="">

                <!-- Payment Instructions & QR Box -->
                <div style="background: #F0FDFA; border: 1px solid #CCFBF1; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: #0F766E; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        <span>Panduan &amp; Arahan Pembayaran</span>
                    </div>

                    <div id="modalQrCodeContainer" style="display: none; text-align: center; margin: 12px 0;">
                        <img id="modalQrCodeImg" src="" alt="DuitNow QR Code" style="max-height: 160px; width: auto; border-radius: 8px; border: 1px solid #CBD5E1; margin: 0 auto; display: block;">
                        <span style="font-size: 0.72rem; color: #64748B; margin-top: 4px; display: block;">Imbas DuitNow QR untuk bayaran pantas</span>
                    </div>

                    <p id="modalInstructionsText" style="margin: 0; font-size: 0.82rem; color: #334155; line-height: 1.5; white-space: pre-line;">
                        Sila buat bayaran langganan ke akaun rasmi PIOS.
                    </p>
                </div>

                <!-- Form Fields -->
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Kaedah Bayaran *</label>
                        <select name="payment_method" id="paymentMethodSelect" onchange="toggleProofField()" required style="width: 100%; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; background: #FFF;">
                            <option value="qr_code">QR Payment / DuitNow (Muat Naik Slip)</option>
                            <option value="bank_transfer">Pindahan Bank Dalam Talian (Online Transfer)</option>
                            <option value="cash">Tunai (Cash kepada Rakan Kongsi / Partner)</option>
                        </select>
                    </div>

                    <div id="proofUploadField">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Bukti Bayaran / Slip Resit (Pilihan / Digalakkan)</label>
                        <input type="file" name="payment_proof" accept="image/*,.pdf" style="width: 100%; padding: 8px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.85rem; background: #FFF;">
                        <span style="font-size: 0.72rem; color: #64748B; margin-top: 3px; display: block;">Format imej (JPG, PNG) atau fail PDF (Maks 10MB)</span>
                    </div>

                    <div id="cashNoticeBox" style="display: none; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 12px; font-size: 0.8rem; color: #92400E;">
                        <strong>Nota Bayaran Tunai:</strong> Sila serahkan bayaran tunai terus kepada Rakan Kongsi (Partner) penaja anda. Rakan Kongsi anda akan mengesahkan penerimaan tunai melalui portal mereka untuk mengaktifkan langganan dan mengeluarkan resit.
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px;">No. Rujukan Transaksi (Pilihan)</label>
                        <input type="text" name="reference_no" placeholder="Contoh: REF987654321 / No Resit Bank" style="width: 100%; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Nota Tambahan (Pilihan)</label>
                        <textarea name="notes" rows="2" placeholder="Sebarang catatan kepada penaja/admin..." style="width: 100%; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box;"></textarea>
                    </div>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeSubscribeModal()" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; cursor: pointer;">
                        Batal
                    </button>
                    <button type="submit" style="background: #0D9488; color: #FFFFFF; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: background 0.2s;">
                        Hantar Bayaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openSubscribeModal(id, name, price, instructions, qrUrl) {
            document.getElementById('modalProductId').value = id;
            document.getElementById('modalPlanTitle').textContent = 'Langgan: ' + name;
            document.getElementById('modalPlanPriceBadge').textContent = 'RM ' + price + ' / 30 Hari';
            
            const instEl = document.getElementById('modalInstructionsText');
            instEl.textContent = instructions || 'Sila buat bayaran langganan mengikut kaedah yang dipilih.';

            const qrBox = document.getElementById('modalQrCodeContainer');
            const qrImg = document.getElementById('modalQrCodeImg');
            if (qrUrl && qrUrl !== 'null' && qrUrl !== '') {
                qrImg.src = qrUrl;
                qrBox.style.display = 'block';
            } else {
                qrBox.style.display = 'none';
            }

            const modal = document.getElementById('subscribeModal');
            modal.style.display = 'flex';
        }

        function closeSubscribeModal() {
            document.getElementById('subscribeModal').style.display = 'none';
        }

        function toggleProofField() {
            const method = document.getElementById('paymentMethodSelect').value;
            const cashNotice = document.getElementById('cashNoticeBox');
            const proofField = document.getElementById('proofUploadField');

            if (method === 'cash') {
                cashNotice.style.display = 'block';
                proofField.style.display = 'none';
            } else {
                cashNotice.style.display = 'none';
                proofField.style.display = 'block';
            }
        }

        // Close on background click
        document.getElementById('subscribeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSubscribeModal();
            }
        });
    </script>
    @endpush

</x-business-layout>

