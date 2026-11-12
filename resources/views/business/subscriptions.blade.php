<x-business-layout title="PIOS - Subscriptions" headerTitle="Subscriptions" bodyClass="fees-body" mainClass="fees-main" mobileHeaderClass="mobile-fees-header">

    <style>
        /* BGE Subscription Countdown Card (Full Width) */
        .bge-countdown-card {
            background: linear-gradient(135deg, #134C49 0%, #174A78 100%);
            border-radius: 16px;
            padding: 26px 30px;
            color: #FFFFFF;
            margin-bottom: 30px;
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

    </style>

    <!-- Desktop Header Bar -->
    <div class="fees-topbar" style="margin-bottom: 24px;">
        <div class="topbar-left">
            <h1 class="fees-heading">Subscriptions &amp; System Plans</h1>
            <p class="fees-subheading">Manage BizGrowth Engine (BGE) subscription and business software plans from PIOS Owner</p>
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

    <!-- BGE Subscription Day Countdown Card (Full Width) -->
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
                    RM {{ number_format((float)($bgeProduct?->price ?? 30.00), 2) }} <span style="font-size: 0.82rem; font-weight: 500; color: #E2E8F0;">/ 30 Days</span>
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

            <!-- Managed Manually by Partner -->
            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                <div style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.22); border-radius: 12px; padding: 12px 18px; color: #FFFFFF; max-width: 380px;">
                    <div style="font-size: 0.86rem; font-weight: 700; display: flex; align-items: center; gap: 8px; color: #FACC15;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Diuruskan Manual oleh Partner</span>
                    </div>
                    <div style="font-size: 0.76rem; color: #E2E8F0; margin-top: 5px; line-height: 1.4;">
                        Yuran langganan BGE diuruskan secara peribadi (bersemuka/F2F) oleh Sponsoring Partner anda dan direkodkan terus dalam modul laporan &amp; transaksi Partner.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Plans / Products from Owner -->
    <div style="margin-bottom: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0F172A;">Catalog of Additional Plans &amp; Systems</h3>
                <p style="margin: 4px 0 0; font-size: 0.85rem; color: #64748B;">Select business systems provided by PIOS (POS, Management modules, etc.)</p>
            </div>
            @if ($partner)
                <div style="font-size: 0.8rem; color: #0D9488; background: #F0FDFA; border: 1px solid #CCFBF1; padding: 6px 12px; border-radius: 8px;">
                    Sponsoring Partner: <strong>{{ $partner->applicant_name }}</strong> ({{ $partner->partner_no }})
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
                            Mandatory
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
                            {{ $plan->description ?: 'Business software and operational module provided by PIOS.' }}
                        </p>

                        <div style="background: #F8FAFC; border-radius: 8px; padding: 10px 12px; margin-bottom: 16px; font-size: 0.78rem; color: #475569;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span>Subscription Period:</span>
                                <strong>{{ $plan->period_days }} Days</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Your Subscription Status:</span>
                                @if ($isActive)
                                    <strong style="color: #059669;">Active ({{ $userSub->daysRemaining() }} days)</strong>
                                @elseif ($userSub && $userSub->status === 'pending_approval')
                                    <strong style="color: #D97706;">Pending Confirmation</strong>
                                @else
                                    <strong style="color: #64748B;">Not Active</strong>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($plan->code === 'bge')
                        <div style="width: 100%; background: #F8FAFC; border: 1px dashed #CBD5E1; color: #475569; padding: 10px; border-radius: 8px; font-weight: 600; font-size: 0.82rem; text-align: center;">
                            Diuruskan secara manual oleh Partner
                        </div>
                    @else
                        <button type="button" onclick="openSubscribeModal({{ $plan->id }}, '{{ addslashes($plan->name) }}', '{{ number_format((float)$plan->price, 2) }}', '{{ addslashes($plan->payment_instructions ?? '') }}', '{{ $plan->qrCodeUrl() }}')"
                            style="width: 100%; background: {{ $isActive ? '#F1F5F9' : '#0D9488' }}; color: {{ $isActive ? '#0F172A' : '#FFFFFF' }}; border: 1px solid {{ $isActive ? '#CBD5E1' : '#0D9488' }}; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: all 0.2s ease;">
                            {{ $isActive ? 'Renew Subscription' : 'Subscribe &amp; Pay' }}
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Payment History & Official Receipts Table -->
    <div class="customer-table-card fee-desktop-table" style="background: #FFFFFF; border-radius: 14px; border: 1px solid #E2E8F0; overflow: hidden; margin-bottom: 30px;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #0F172A;">Payment History &amp; Official Receipts</h3>
                <p style="margin: 3px 0 0; font-size: 0.82rem; color: #64748B;">List of subscription payment records and download links for verified receipts</p>
            </div>
            <span style="font-size: 0.78rem; font-weight: 600; color: #0D9488; background: #CCFBF1; padding: 4px 10px; border-radius: 12px;">
                {{ $payments->count() }} Transactions
            </span>
        </div>

        <div class="table-responsive-wrapper" style="overflow-x: auto;">
            <table class="customer-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; font-size: 0.78rem; color: #475569; text-transform: uppercase; letter-spacing: 0.04em;">
                        <th style="padding: 12px 18px;">Payment No / Date</th>
                        <th style="padding: 12px 18px;">Product / Plan</th>
                        <th style="padding: 12px 18px;">Amount &amp; Method</th>
                        <th style="padding: 12px 18px;">Payment Proof</th>
                        <th style="padding: 12px 18px;">Status</th>
                        <th style="padding: 12px 18px; text-align: right;">Action / Receipt</th>
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
                                <div style="font-size: 0.76rem; color: #64748B;">Duration: {{ $pay->period_days }} Days</div>
                            </td>
                            <td style="padding: 14px 18px;">
                                <div style="font-weight: 700; color: #0D9488;">RM {{ number_format((float)$pay->amount, 2) }}</div>
                                <div style="font-size: 0.76rem; color: #64748B; text-transform: capitalize;">
                                    @if ($pay->payment_method === 'qr_code')
                                        QR Payment / DuitNow
                                    @elseif ($pay->payment_method === 'cash')
                                        Cash
                                    @else
                                        Bank Transfer (Online)
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 14px 18px;">
                                @if ($pay->payment_proof)
                                    <a href="{{ $pay->paymentProofUrl() }}" target="_blank" style="color: #0D9488; font-size: 0.8rem; font-weight: 600; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                                        </svg>
                                        View Slip
                                    </a>
                                @else
                                    <span style="font-size: 0.78rem; color: #94A3B8;">No file</span>
                                @endif
                            </td>
                            <td style="padding: 14px 18px;">
                                @if ($pay->status === 'approved')
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #ECFDF5; color: #059669; font-weight: 700; font-size: 0.76rem; padding: 4px 10px; border-radius: 12px;">
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #059669;"></span>
                                        Approved &amp; Active
                                    </span>
                                @elseif ($pay->status === 'rejected')
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #FEF2F2; color: #DC2626; font-weight: 700; font-size: 0.76rem; padding: 4px 10px; border-radius: 12px;" title="{{ $pay->rejection_reason }}">
                                        Rejected
                                    </span>
                                @else
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #FFFBEB; color: #D97706; font-weight: 700; font-size: 0.76rem; padding: 4px 10px; border-radius: 12px;">
                                        Pending Confirmation
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
                                        <span>Receipt ({{ $pay->receipt_no }})</span>
                                    </a>
                                @else
                                    <span style="font-size: 0.78rem; color: #94A3B8;">Receipt pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 36px 18px; text-align: center; color: #64748B;">
                                No subscription payment transactions found.
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
                    <h3 id="modalPlanTitle" style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0F172A;">Subscribe to Plan</h3>
                    <div id="modalPlanPriceBadge" style="margin-top: 3px; font-size: 0.85rem; font-weight: 700; color: #0D9488;">RM 30.00 / 30 Days</div>
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
                        <span>Payment Guide &amp; Instructions</span>
                    </div>

                    <div id="modalQrCodeContainer" style="display: none; text-align: center; margin: 12px 0;">
                        <img id="modalQrCodeImg" src="" alt="DuitNow QR Code" style="max-height: 160px; width: auto; border-radius: 8px; border: 1px solid #CBD5E1; margin: 0 auto; display: block;">
                        <span style="font-size: 0.72rem; color: #64748B; margin-top: 4px; display: block;">Scan DuitNow QR for fast payment</span>
                    </div>

                    <p id="modalInstructionsText" style="margin: 0; font-size: 0.82rem; color: #334155; line-height: 1.5; white-space: pre-line;">
                        Please make payment according to the selected method.
                    </p>
                </div>

                <!-- Form Fields -->
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Payment Method *</label>
                        <select name="payment_method" id="paymentMethodSelect" onchange="toggleProofField()" required style="width: 100%; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; background: #FFF;">
                            <option value="qr_code">QR Payment / DuitNow (Upload Slip)</option>
                            <option value="bank_transfer">Online Bank Transfer</option>
                            <option value="cash">Cash (Directly to Partner)</option>
                        </select>
                    </div>

                    <div id="proofUploadField">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Payment Proof / Receipt Slip (Optional / Recommended)</label>
                        <input type="file" name="payment_proof" accept="image/*,.pdf" style="width: 100%; padding: 8px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.85rem; background: #FFF;">
                        <span style="font-size: 0.72rem; color: #64748B; margin-top: 3px; display: block;">Image format (JPG, PNG) or PDF file (Max 10MB)</span>
                    </div>

                    <div id="cashNoticeBox" style="display: none; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 12px; font-size: 0.8rem; color: #92400E;">
                        <strong>Cash Payment Note:</strong> Please pass your cash payment directly to your sponsoring Partner. Your Partner will verify receipt in their portal to activate your subscription and issue an official receipt.
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Transaction Reference No (Optional)</label>
                        <input type="text" name="reference_no" placeholder="e.g. REF987654321 / Bank receipt number" style="width: 100%; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 6px;">Additional Notes (Optional)</label>
                        <textarea name="notes" rows="2" placeholder="Any remarks to sponsor/admin..." style="width: 100%; padding: 10px 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box;"></textarea>
                    </div>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeSubscribeModal()" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 0.88rem; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="background: #0D9488; color: #FFFFFF; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: background 0.2s;">
                        Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openSubscribeModal(id, name, price, instructions, qrUrl) {
            document.getElementById('modalProductId').value = id;
            document.getElementById('modalPlanTitle').textContent = 'Subscribe: ' + name;
            document.getElementById('modalPlanPriceBadge').textContent = 'RM ' + price + ' / 30 Days';
            
            const instEl = document.getElementById('modalInstructionsText');
            instEl.textContent = instructions || 'Please make payment according to the selected method.';

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
