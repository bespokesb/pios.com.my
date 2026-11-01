<x-business-layout title="PIOS - Fees & Subscriptions" headerTitle="Fees" bodyClass="fees-body" mainClass="fees-main" mobileHeaderClass="mobile-fees-header">

    <!-- Desktop Header Bar -->
    <div class="fees-topbar">
        <div class="topbar-left">
            <h1 class="fees-heading">BGE Fees</h1>
            <p class="fees-subheading">Monthly BGE Subscription, platform dues, and fee payment history</p>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="topbarDate">{{ now()->format('D, M d, Y') }}</span>
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

    <!-- Feedback Messages -->
    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between">
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if (session('whatsapp_url'))
        <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-sm flex flex-wrap items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span style="font-size: 1.5rem;">📱</span>
                <div>
                    <div class="font-bold">WhatsApp Peringatan Sedia Dihantar</div>
                    <div class="text-xs text-emerald-700">Peringatan pengesahan bayaran telah dijana untuk dihantar kepada Partner anda melalui WhatsApp.</div>
                </div>
            </div>
            <a href="{{ session('whatsapp_url') }}" target="_blank" style="background: #25D366; color: #FFFFFF; font-weight: 700; padding: 9px 18px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; box-shadow: 0 2px 6px rgba(37, 211, 102, 0.3);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.301-.15-1.782-.879-2.057-.98-.276-.1-.476-.15-.677.15-.201.3-.777.98-.953 1.18-.175.2-.351.226-.652.075-.301-.15-1.272-.469-2.423-1.496-.896-.799-1.501-1.787-1.677-2.088-.175-.3-.019-.463.132-.613.136-.135.301-.351.452-.527.15-.175.201-.3.301-.501.101-.2.05-.376-.025-.526-.075-.15-.677-1.632-.928-2.235-.245-.587-.493-.508-.677-.517-.175-.01-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.3-1.054 1.03-1.054 2.511 0 1.482 1.079 2.911 1.23 3.112.15.2 2.124 3.243 5.145 4.548.719.311 1.28.497 1.718.636.722.23 1.379.197 1.9-.12.57-.348 1.782-.728 2.033-1.431.25-.703.25-1.306.175-1.431-.075-.125-.276-.2-.577-.35zM12 2a10 10 0 0 0-8.59 15.11L2 22l4.98-1.31A10 10 0 1 0 12 2z"></path>
                </svg>
                <span>Buka WhatsApp Sekarang</span>
            </a>
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                try {
                    window.open("{{ session('whatsapp_url') }}", '_blank');
                } catch(e) {}
            });
        </script>
    @endif

    <!-- Fee Summary Metrics -->
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
        <div class="kpi-card">
            <div class="kpi-value text-teal">RM {{ number_format((float)$totalPaid, 2) }}</div>
            <div class="kpi-title">Total Paid</div>
            <div class="kpi-subtitle">Lifetime verified payments</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value text-orange">RM {{ number_format((float)$totalOutstanding, 2) }}</div>
            <div class="kpi-title">Outstanding Dues</div>
            <div class="kpi-subtitle">{{ $pendingCount }} pending bills</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value text-red-500" style="color: #EF4444;">{{ $overdueCount }}</div>
            <div class="kpi-title">Overdue Invoices</div>
            <div class="kpi-subtitle">Requires immediate attention</div>
        </div>
    </div>

    <!-- Desktop Fee Records Table -->
    <div class="customer-table-card fee-desktop-table">
        <div class="dash-card-header" style="padding: 20px 24px; border-bottom: 1px solid #E2E8F0;">
            <h2 class="dash-card-title">Fee Breakdown &amp; Billing Schedule</h2>
        </div>
        <div class="table-responsive-wrapper">
            <table class="customer-table">
                <thead>
                    <tr>
                        <th>Billing Period</th>
                        <th>Fee Type</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $hasRows = false;
                    @endphp

                    {{-- 1. BGE Subscription (Current Unpaid/Expired or Active Trial) --}}
                    @if ($bgeSub)
                        @php
                            $bgeHasPending = $bgePayments->where('status', 'pending_approval')->isNotEmpty() || $bgeSub->status === 'pending_approval';
                            $daysExpired = $bgeSub->expires_at && $bgeSub->expires_at->isPast() ? (int) $bgeSub->expires_at->diffInDays(now()) : 0;
                        @endphp
                        @if (! $bgeSub->isActive() && ! $bgeHasPending)
                            @php
                                $hasRows = true;
                                $isBgeOverdue = $daysExpired >= 91;
                                $bgeStatusClass = $isBgeOverdue ? 'badge-danger' : 'badge-order';
                                $bgeStatusLabel = $isBgeOverdue ? 'Overdue (>90 Days)' : 'Outstanding';
                            @endphp
                            <tr>
                                <td><strong>{{ $bgeSub->expires_at ? $bgeSub->expires_at->format('F Y') : now()->format('F Y') }}</strong></td>
                                <td>BizGrowth Engine (BGE) — Monthly Platform Subscription</td>
                                <td><strong style="color: #0D9488;">RM {{ number_format((float)($bgeProduct?->price ?? 30.00), 2) }}</strong></td>
                                <td>{{ $bgeSub->expires_at ? $bgeSub->expires_at->format('d M Y') : now()->format('d M Y') }}</td>
                                <td>
                                    <span class="status-pill {{ $bgeStatusClass }}">{{ $bgeStatusLabel }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('business.subscriptions.index') }}" class="btn-action-view" style="text-decoration: none; display: inline-block; background: #0D9488; color: #fff; font-weight: 600; padding: 6px 14px; border-radius: 8px;">
                                        Pay / Renew
                                    </a>
                                </td>
                            </tr>
                        @elseif ($bgeSub->isTrial())
                            @php
                                $hasRows = true;
                            @endphp
                            <tr>
                                <td><strong>Trial Period</strong></td>
                                <td>BizGrowth Engine (BGE) — 48-Hour Free Trial</td>
                                <td><strong style="color: #0D9488;">RM 0.00</strong></td>
                                <td>{{ $bgeSub->expires_at ? $bgeSub->expires_at->format('d M Y') : '-' }}</td>
                                <td>
                                    <span class="status-pill badge-paid">Trial Aktif</span>
                                </td>
                                <td>
                                    <a href="{{ route('business.subscriptions.index') }}" class="btn-action-view" style="text-decoration: none; display: inline-block;">
                                        View Subscription
                                    </a>
                                </td>
                            </tr>
                        @endif
                    @endif

                    {{-- 2. BGE Subscription Payments (Approved or Pending) --}}
                    @foreach ($bgePayments as $payment)
                        @php
                            $hasRows = true;
                            $paymentStatusClass = match ($payment->status) {
                                'approved' => 'badge-paid',
                                'pending_approval' => 'badge-order',
                                default => 'badge-danger',
                            };
                            $paymentStatusLabel = match ($payment->status) {
                                'approved' => 'Paid',
                                'pending_approval' => 'Pending Confirmation',
                                default => 'Rejected',
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $payment->paid_at ? $payment->paid_at->format('F Y') : $payment->created_at->format('F Y') }}</strong></td>
                            <td>BizGrowth Engine (BGE) — {{ $payment->payment_no }}</td>
                            <td><strong style="color: #0D9488;">RM {{ number_format((float)$payment->amount, 2) }}</strong></td>
                            <td>{{ $payment->paid_at ? $payment->paid_at->format('d M Y') : $payment->created_at->format('d M Y') }}</td>
                            <td>
                                <span class="status-pill {{ $paymentStatusClass }}">{{ $paymentStatusLabel }}</span>
                            </td>
                            <td>
                                @if ($payment->status === 'approved')
                                    <span style="color: #059669; font-size: 0.88rem; font-weight: 600;">✓ Settled</span>
                                @elseif ($payment->status === 'pending_approval')
                                    <span style="color: #D97706; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        ⏳ Menunggu Pengesahan
                                    </span>
                                @else
                                    <a href="{{ route('business.subscriptions.index') }}" class="btn-action-view" style="text-decoration: none; display: inline-block;">
                                        Pay / Retry
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    {{-- 3. Regular Fees --}}
                    @foreach ($fees as $fee)
                        @php
                            $hasRows = true;
                            $isFeeOverdue = $fee->due_date && $fee->due_date->isPast() && $fee->due_date->diffInDays(now()) >= 91;
                            $feeStatusClass = match ($fee->status) {
                                \App\Enums\FeeStatus::Paid => 'badge-paid',
                                \App\Enums\FeeStatus::Pending => 'badge-order',
                                \App\Enums\FeeStatus::Waived => 'badge-order',
                                default => $isFeeOverdue ? 'badge-danger' : 'badge-order',
                            };
                            $feeStatusLabel = match ($fee->status) {
                                \App\Enums\FeeStatus::Paid => 'Paid',
                                \App\Enums\FeeStatus::Pending => 'Pending Confirmation',
                                \App\Enums\FeeStatus::Waived => 'Waived',
                                default => $isFeeOverdue ? 'Overdue (>90 Days)' : 'Outstanding',
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $fee->billing_period ?? $fee->created_at->format('F Y') }}</strong></td>
                            <td>{{ $fee->fee_type?->label() ?? 'Monthly Platform Fee' }}</td>
                            <td><strong style="color: #0D9488;">RM {{ number_format((float)$fee->amount, 2) }}</strong></td>
                            <td>{{ $fee->due_date ? $fee->due_date->format('d M Y') : '-' }}</td>
                            <td>
                                <span class="status-pill {{ $feeStatusClass }}">{{ $feeStatusLabel }}</span>
                            </td>
                            <td>
                                @if ($fee->status === \App\Enums\FeeStatus::Paid)
                                    <span style="color: #059669; font-size: 0.88rem; font-weight: 600;">✓ Settled</span>
                                @elseif ($fee->status === \App\Enums\FeeStatus::Pending)
                                    <span style="color: #D97706; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        ⏳ Menunggu Pengesahan
                                    </span>
                                @elseif ($fee->status === \App\Enums\FeeStatus::Waived)
                                    <span style="color: #64748B; font-size: 0.88rem; font-weight: 600;">✓ Waived</span>
                                @else
                                    <button type="button" class="btn-action-view" onclick="openPayModal({{ $fee->id }}, '{{ number_format((float)$fee->amount, 2) }}')">
                                        Pay Now
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if (! $hasRows)
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 32px; color: #64748B;">
                                No fee statements recorded.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Card List (< 992px) — matches business/transactions tx-card layout -->
    <div class="fee-mobile-list">
        @php
            $hasMobileRows = false;
        @endphp

        {{-- 1. BGE Subscription (Current Unpaid/Expired or Active Trial) --}}
        @if ($bgeSub)
            @php
                $bgeHasPending = $bgePayments->where('status', 'pending_approval')->isNotEmpty() || $bgeSub->status === 'pending_approval';
                $daysExpired = $bgeSub->expires_at && $bgeSub->expires_at->isPast() ? (int) $bgeSub->expires_at->diffInDays(now()) : 0;
            @endphp
            @if (! $bgeSub->isActive() && ! $bgeHasPending)
                @php
                    $hasMobileRows = true;
                    $isBgeOverdue = $daysExpired >= 91;
                    $bgeStatusClass = $isBgeOverdue ? 'badge-danger' : 'badge-order';
                    $bgeStatusLabel = $isBgeOverdue ? 'Overdue (>90 Days)' : 'Outstanding';
                @endphp
                <div class="fee-mobile-card">
                    <div class="fee-card-top">
                        <span class="fee-card-period">{{ $bgeSub->expires_at ? $bgeSub->expires_at->format('F Y') : now()->format('F Y') }}</span>
                        <span class="badge-status {{ $bgeStatusClass }}">{{ $bgeStatusLabel }}</span>
                    </div>
                    <div class="fee-card-middle">
                        <span class="fee-card-type">BizGrowth Engine (BGE) — Monthly Platform Subscription</span>
                        <span class="fee-card-amount">RM {{ number_format((float)($bgeProduct?->price ?? 30.00), 2) }}</span>
                    </div>
                    <div class="fee-card-bottom">
                        <span class="fee-card-due">Due: {{ $bgeSub->expires_at ? $bgeSub->expires_at->format('d M Y') : now()->format('d M Y') }}</span>
                        <a href="{{ route('business.subscriptions.index') }}" class="fee-card-pay-btn" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Pay / Renew</a>
                    </div>
                </div>
            @elseif ($bgeSub->isTrial())
                @php
                    $hasMobileRows = true;
                @endphp
                <div class="fee-mobile-card">
                    <div class="fee-card-top">
                        <span class="fee-card-period">Trial Period</span>
                        <span class="badge-status badge-paid">Trial Aktif</span>
                    </div>
                    <div class="fee-card-middle">
                        <span class="fee-card-type">BizGrowth Engine (BGE) — 48-Hour Free Trial</span>
                        <span class="fee-card-amount">RM 0.00</span>
                    </div>
                    <div class="fee-card-bottom">
                        <span class="fee-card-due">Expiry: {{ $bgeSub->expires_at ? $bgeSub->expires_at->format('d M Y') : '-' }}</span>
                        <a href="{{ route('business.subscriptions.index') }}" style="color: #0D9488; font-size: 0.85rem; font-weight: 600; text-decoration: none;">View Subscription</a>
                    </div>
                </div>
            @endif
        @endif

        {{-- 2. BGE Subscription Payments --}}
        @foreach ($bgePayments as $payment)
            @php
                $hasMobileRows = true;
                $paymentStatusClass = match ($payment->status) {
                    'approved' => 'badge-paid',
                    'pending_approval' => 'badge-order',
                    default => 'badge-danger',
                };
                $paymentStatusLabel = match ($payment->status) {
                    'approved' => 'Paid',
                    'pending_approval' => 'Pending Confirmation',
                    default => 'Rejected',
                };
            @endphp
            <div class="fee-mobile-card">
                <div class="fee-card-top">
                    <span class="fee-card-period">{{ $payment->paid_at ? $payment->paid_at->format('F Y') : $payment->created_at->format('F Y') }}</span>
                    <span class="badge-status {{ $paymentStatusClass }}">{{ $paymentStatusLabel }}</span>
                </div>
                <div class="fee-card-middle">
                    <span class="fee-card-type">BizGrowth Engine (BGE) — {{ $payment->payment_no }}</span>
                    <span class="fee-card-amount">RM {{ number_format((float)$payment->amount, 2) }}</span>
                </div>
                <div class="fee-card-bottom">
                    <span class="fee-card-due">Date: {{ $payment->paid_at ? $payment->paid_at->format('d M Y') : $payment->created_at->format('d M Y') }}</span>
                    @if ($payment->status === 'approved')
                        <span class="fee-card-settled">✓ Settled</span>
                    @elseif ($payment->status === 'pending_approval')
                        <span style="color: #D97706; font-size: 0.85rem; font-weight: 600;">⏳ Menunggu Pengesahan</span>
                    @else
                        <a href="{{ route('business.subscriptions.index') }}" class="fee-card-pay-btn" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Pay / Retry</a>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- 3. Regular Fees --}}
        @foreach ($fees as $fee)
            @php
                $hasMobileRows = true;
                $isFeeOverdue = $fee->due_date && $fee->due_date->isPast() && $fee->due_date->diffInDays(now()) >= 91;
                $feeStatusClass = match ($fee->status) {
                    \App\Enums\FeeStatus::Paid => 'badge-paid',
                    \App\Enums\FeeStatus::Pending => 'badge-order',
                    \App\Enums\FeeStatus::Waived => 'badge-order',
                    default => $isFeeOverdue ? 'badge-danger' : 'badge-order',
                };
                $feeStatusLabel = match ($fee->status) {
                    \App\Enums\FeeStatus::Paid => 'Paid',
                    \App\Enums\FeeStatus::Pending => 'Pending Confirmation',
                    \App\Enums\FeeStatus::Waived => 'Waived',
                    default => $isFeeOverdue ? 'Overdue (>90 Days)' : 'Outstanding',
                };
            @endphp
            <div class="fee-mobile-card">
                <div class="fee-card-top">
                    <span class="fee-card-period">{{ $fee->billing_period ?? $fee->created_at->format('F Y') }}</span>
                    <span class="badge-status {{ $feeStatusClass }}">{{ $feeStatusLabel }}</span>
                </div>
                <div class="fee-card-middle">
                    <span class="fee-card-type">{{ $fee->fee_type?->label() ?? 'Monthly Platform Fee' }}</span>
                    <span class="fee-card-amount">RM {{ number_format((float)$fee->amount, 2) }}</span>
                </div>
                <div class="fee-card-bottom">
                    <span class="fee-card-due">Due: {{ $fee->due_date ? $fee->due_date->format('d M Y') : '-' }}</span>
                    @if ($fee->status === \App\Enums\FeeStatus::Paid)
                        <span class="fee-card-settled">✓ Settled</span>
                    @elseif ($fee->status === \App\Enums\FeeStatus::Pending)
                        <span style="color: #D97706; font-size: 0.85rem; font-weight: 600;">⏳ Menunggu Pengesahan</span>
                    @elseif ($fee->status === \App\Enums\FeeStatus::Waived)
                        <span style="color: #64748B; font-size: 0.85rem; font-weight: 600;">✓ Waived</span>
                    @else
                        <button type="button" class="fee-card-pay-btn" onclick="openPayModal({{ $fee->id }}, '{{ number_format((float)$fee->amount, 2) }}')">Pay Now</button>
                    @endif
                </div>
            </div>
        @endforeach

        @if (! $hasMobileRows)
            <div class="fee-mobile-card" style="text-align: center; color: #64748B; padding: 24px;">
                No fee statements recorded.
            </div>
        @endif
    </div>

    <!-- Pay Fee Modal -->
    <div class="modal-overlay" id="payFeeModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 16px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
        <div class="modal-card" style="background: #FFFFFF; border-radius: 16px; width: 100%; max-width: 480px; max-height: calc(100dvh - 32px); overflow-y: auto; margin: auto; box-shadow: 0 20px 50px rgba(15,23,42,0.18);">
            <div style="padding: 20px 24px; border-bottom: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: space-between;">
                <h3 style="font-family: 'Poppins', sans-serif; font-size: 1.25rem; font-weight: 700; color: #0F172A; margin: 0;">Make Fee Payment</h3>
                <button type="button" id="closePayBtn" style="background: transparent; border: none; font-size: 1.5rem; color: #64748B; cursor: pointer;">&times;</button>
            </div>

            <form id="payFeeForm" method="POST" style="padding: 24px;">
                @csrf
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Payment Amount</label>
                    <div style="font-size: 1.4rem; font-weight: 700; color: #0D9488;" id="modalFeeAmount">RM 0.00</div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Payment Method *</label>
                    <select name="payment_method" required style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;">
                        <option value="bank_transfer">Online Bank Transfer (FPX / DuitNow)</option>
                        <option value="cash">Direct Cash / Partner Handover</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin-bottom: 6px;">Reference / Receipt No.</label>
                    <input type="text" name="reference_no" placeholder="e.g. FPX-9982348" style="width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <button type="button" id="cancelPayBtn" style="padding: 10px 20px; border-radius: 12px; border: 1px solid #CBD5E1; background: #FFF; color: #475569; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 10px 24px; border-radius: 12px; border: none; background: #0D9488; color: #FFF; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(13,148,136,0.3);">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('assets/js/fees.js') }}?v={{ file_exists(public_path('assets/js/fees.js')) ? filemtime(public_path('assets/js/fees.js')) : time() }}"></script>
    <script>
        function openPayModal(feeId, amount) {
            const modal = document.getElementById('payFeeModal');
            const form = document.getElementById('payFeeForm');
            const amountEl = document.getElementById('modalFeeAmount');
            if (form) form.action = `/business/fees/${feeId}/pay`;
            if (amountEl) amountEl.textContent = `RM ${amount}`;
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                const card = modal.querySelector('.modal-card');
                if (card) card.scrollTop = 0;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('payFeeModal');
            const closeBtn = document.getElementById('closePayBtn');
            const cancelBtn = document.getElementById('cancelPayBtn');
            const payForm = document.getElementById('payFeeForm');

            function closePayModal() {
                if (modal) modal.style.display = 'none';
                document.body.style.overflow = '';
            }

            if (closeBtn) closeBtn.addEventListener('click', closePayModal);
            if (cancelBtn) cancelBtn.addEventListener('click', closePayModal);
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closePayModal();
                });
            }

            if (payForm) {
                payForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const submitBtn = payForm.querySelector('button[type="submit"]');
                    const origText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Memproses...';

                    const formData = new FormData(payForm);

                    fetch(payForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, data })))
                    .then(({ ok, data }) => {
                        if (ok && data.success) {
                            if (data.whatsapp_url) {
                                window.open(data.whatsapp_url, '_blank');
                            }
                            window.location.reload();
                        } else {
                            alert(data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Ralat semasa memproses bayaran.'));
                            submitBtn.disabled = false;
                            submitBtn.textContent = origText;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        payForm.submit();
                    });
                });
            }
        });
    </script>
    @endpush

</x-business-layout>
