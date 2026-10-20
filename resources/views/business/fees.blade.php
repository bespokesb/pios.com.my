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
                    @forelse ($fees as $fee)
                        @php
                            $statusClass = match ($fee->status) {
                                \App\Enums\FeeStatus::Paid => 'badge-paid',
                                \App\Enums\FeeStatus::Overdue => 'badge-danger',
                                default => 'badge-order',
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ $fee->billing_period ?? $fee->created_at->format('F Y') }}</strong></td>
                            <td>{{ $fee->fee_type?->label() ?? 'Monthly Platform Fee' }}</td>
                            <td><strong style="color: #0D9488;">RM {{ number_format((float)$fee->amount, 2) }}</strong></td>
                            <td>{{ $fee->due_date ? $fee->due_date->format('d M Y') : '-' }}</td>
                            <td>
                                <span class="status-pill {{ $statusClass }}">{{ $fee->status->value }}</span>
                            </td>
                            <td>
                                @if ($fee->status !== \App\Enums\FeeStatus::Paid)
                                    <button type="button" class="btn-action-view" onclick="openPayModal({{ $fee->id }}, '{{ number_format((float)$fee->amount, 2) }}')">
                                        Pay Now
                                    </button>
                                @else
                                    <span style="color: #059669; font-size: 0.88rem; font-weight: 600;">✓ Settled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 32px; color: #64748B;">
                                No fee statements recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Card List (< 992px) — matches business/transactions tx-card layout -->
    <div class="fee-mobile-list">
        @forelse ($fees as $fee)
            @php
                $statusClass = match ($fee->status) {
                    \App\Enums\FeeStatus::Paid => 'badge-paid',
                    \App\Enums\FeeStatus::Overdue => 'badge-danger',
                    default => 'badge-order',
                };
            @endphp
            <div class="fee-mobile-card">
                <div class="fee-card-top">
                    <span class="fee-card-period">{{ $fee->billing_period ?? $fee->created_at->format('F Y') }}</span>
                    <span class="badge-status {{ $statusClass }}">{{ $fee->status->value }}</span>
                </div>
                <div class="fee-card-middle">
                    <span class="fee-card-type">{{ $fee->fee_type?->label() ?? 'Monthly Platform Fee' }}</span>
                    <span class="fee-card-amount">RM {{ number_format((float)$fee->amount, 2) }}</span>
                </div>
                <div class="fee-card-bottom">
                    <span class="fee-card-due">Due: {{ $fee->due_date ? $fee->due_date->format('d M Y') : '-' }}</span>
                    @if ($fee->status !== \App\Enums\FeeStatus::Paid)
                        <button type="button" class="fee-card-pay-btn" onclick="openPayModal({{ $fee->id }}, '{{ number_format((float)$fee->amount, 2) }}')">Pay Now</button>
                    @else
                        <span class="fee-card-settled">✓ Settled</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="fee-mobile-card" style="text-align: center; color: #64748B; padding: 24px;">
                No fee statements recorded.
            </div>
        @endforelse
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
        });
    </script>
    @endpush

</x-business-layout>
