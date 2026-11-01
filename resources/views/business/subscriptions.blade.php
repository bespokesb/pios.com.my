<x-business-layout title="PIOS - Subscriptions" headerTitle="Subscriptions" bodyClass="fees-body" mainClass="fees-main" mobileHeaderClass="mobile-fees-header">

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

    <!-- BGE Core Subscription Highlight Banner -->
    <div style="background: linear-gradient(135deg, #134C49 0%, #174A78 100%); border-radius: 16px; padding: 26px 30px; color: #FFFFFF; margin-bottom: 30px; box-shadow: 0 10px 25px -5px rgba(19, 76, 73, 0.3); display: flex; flex-direction: column; gap: 16px;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <span style="font-size: 0.76rem; font-weight: 800; background: #E7C46A; color: #0F172A; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em;">Default Mandatory</span>
                    @if ($bgeSubscription && $bgeSubscription->isTrial())
                        <span style="font-size: 0.78rem; font-weight: 700; background: #F59E0B; color: #FFFFFF; padding: 3px 12px; border-radius: 20px;">
                            ⭐ 48-Hour Free Trial ({{ $bgeSubscription->hoursRemaining() }}h Left)
                        </span>
                    @elseif ($bgeSubscription && $bgeSubscription->isActive())
                        <span style="font-size: 0.78rem; font-weight: 700; background: #10B981; color: #FFFFFF; padding: 3px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: #FFF;"></span>
                            Active ({{ $bgeSubscription->daysRemaining() }} Days Remaining)
                        </span>
                    @elseif ($bgeSubscription && $bgeSubscription->status === 'pending_approval')
                        <span style="font-size: 0.78rem; font-weight: 700; background: #F59E0B; color: #FFFFFF; padding: 3px 12px; border-radius: 20px;">
                            Pending Payment Confirmation
                        </span>
                    @else
                        <span style="font-size: 0.78rem; font-weight: 700; background: #EF4444; color: #FFFFFF; padding: 3px 12px; border-radius: 20px;">
                            Expired / Subscription Required
                        </span>
                    @endif
                </div>
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.01em;">BizGrowth Engine (BGE)</h2>
                <p style="margin: 4px 0 0; font-size: 0.9rem; color: #E2E8F0; max-width: 650px; line-height: 1.5;">
                    The core operational engine of your PIOS business. Grants access to product catalog, order management, customer database, and daily sales.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; align-items: flex-start; sm:align-items: flex-end; gap: 6px;">
                <div style="font-size: 1.7rem; font-weight: 900; color: #F8FAFC;">
                    RM {{ number_format((float)($bgeProduct?->price ?? 30.00), 2) }}
                    <span style="font-size: 0.85rem; font-weight: 500; color: #CBD5E1;">/ 30 Days</span>
                </div>
                <div style="font-size: 0.78rem; color: #E2E8F0;">
                    Renews every 30 days from confirmation date
                </div>
            </div>
        </div>

        <div style="border-top: 1px solid rgba(255, 255, 255, 0.15); padding-top: 16px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px;">
            <div style="font-size: 0.85rem; color: #F1F5F9;">
                @if ($bgeSubscription && $bgeSubscription->isActive() && $bgeSubscription->expires_at)
                    <span>Subscription Expiry Date: <strong>{{ $bgeSubscription->expires_at->format('d M Y, h:i A') }}</strong></span>
                @elseif ($bgeSubscription && $bgeSubscription->status === 'pending_approval')
                    <span>Status: <strong>Payment is pending confirmation by your Partner</strong></span>
                @else
                    <span style="color: #FECACA;">Notice: Please renew your BGE subscription to maintain full portal access.</span>
                @endif
            </div>

            @if ($bgeProduct)
                <button type="button" onclick="triggerPartnerWhatsAppRenew()"
                    style="background: #E7C46A; color: #0F172A; border: none; padding: 10px 22px; border-radius: 10px; font-weight: 700; font-size: 0.92rem; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <span>{{ ($bgeSubscription && $bgeSubscription->isActive()) ? 'Renew Subscription' : 'Subscribe Now' }}</span>
                </button>
            @endif
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
                        <button type="button" onclick="triggerPartnerWhatsAppRenew()"
                            style="width: 100%; background: #059669; color: #FFFFFF; border: 1px solid #059669; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.201.3-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.787-1.677-2.088-.175-.3-.019-.463.132-.613.136-.135.301-.351.452-.527.15-.175.201-.3.301-.501.101-.2.05-.376-.025-.526-.075-.15-.677-1.632-.928-2.235-.245-.587-.493-.508-.677-.517-.175-.01-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.3-1.054 1.03-1.054 2.511 0 1.482 1.079 2.911 1.23 3.112.15.2 2.124 3.243 5.145 4.548.719.311 1.28.497 1.718.636.722.23 1.379.197 1.9-.12.57-.348 1.782-.728 2.033-1.431.25-.703.25-1.306.175-1.431-.075-.125-.276-.2-.577-.35zM12 2a10 10 0 0 0-8.59 15.11L2 22l4.98-1.31A10 10 0 1 0 12 2z"></path>
                            </svg>
                            <span>{{ $isActive ? 'Renew via WhatsApp Partner' : 'Subscribe via WhatsApp Partner' }}</span>
                        </button>
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

                <!-- Partner Direct WhatsApp Option -->
                @if ($partner)
                    <div style="background: #F0FDF4; border: 1.5px solid #BBF7D0; border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 0.86rem; font-weight: 800; color: #166534; display: flex; align-items: center; gap: 6px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="#16A34A"><path d="M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.201.3-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.787-1.677-2.088-.175-.3-.019-.463.132-.613.136-.135.301-.351.452-.527.15-.175.201-.3.301-.501.101-.2.05-.376-.025-.526-.075-.15-.677-1.632-.928-2.235-.245-.587-.493-.508-.677-.517-.175-.01-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.3-1.054 1.03-1.054 2.511 0 1.482 1.079 2.911 1.23 3.112.15.2 2.124 3.243 5.145 4.548.719.311 1.28.497 1.718.636.722.23 1.379.197 1.9-.12.57-.348 1.782-.728 2.033-1.431.25-.703.25-1.306.175-1.431-.075-.125-.276-.2-.577-.35zM12 2a10 10 0 0 0-8.59 15.11L2 22l4.98-1.31A10 10 0 1 0 12 2z"></path></svg>
                                <span>Renew Directly via Partner WhatsApp</span>
                            </div>
                            <div style="font-size: 0.78rem; color: #15803D; margin-top: 3px;">
                                Sponsoring Partner: <strong>{{ $partner->applicant_name }}</strong> ({{ $partner->partner_no }})
                            </div>
                        </div>
                        <button type="button" onclick="triggerPartnerWhatsAppRenew()" style="background: #16A34A; color: #FFFFFF; border: none; padding: 9px 16px; border-radius: 8px; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(22,163,74,0.3);">
                            <span>Open Partner WhatsApp</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </button>
                    </div>
                @endif

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

        function triggerPartnerWhatsAppRenew() {
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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
            .catch(function(err) {
                window.open("{{ $partnerWhatsAppRenewUrl ?? '#' }}", '_blank');
            });
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
