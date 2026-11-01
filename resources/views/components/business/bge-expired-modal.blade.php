@php
    $bo = auth()->user()?->businessOwner;
    $partner = $sponsoringPartner ?? $bo?->partner;
    $partnerPhone = $partner ? ($partner->hp_no ?: $partner->spouse_hp_no) : null;
    $cleanPhone = $partnerPhone ? \App\Support\Phone::toWhatsApp($partnerPhone) : null;
    $pendingPayment = $bgeSubscription?->latestPayment;
    $isPending = isset($isBgePendingApproval) && $isBgePendingApproval;

    if ($isPending) {
        $waUrl = \App\Services\WhatsAppPassSender::forBgePendingApprovalReminder($partner, $bo, $pendingPayment?->payment_no);
    } else {
        $waUrl = \App\Services\WhatsAppPassSender::forBgePaymentToPartner($partner, $bo);
    }
@endphp

<div id="bgeExpiredModalOverlay" class="bge-modal-overlay" style="display: flex;">
    <div class="bge-modal-card">
        <!-- Top Gradient Header -->
        <div class="bge-modal-header {{ $isPending ? 'bge-header-pending' : 'bge-header-expired' }}">
            <div class="bge-modal-icon-wrapper">
                @if ($isPending)
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                @else
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                @endif
            </div>
            <button type="button" class="bge-modal-close" onclick="closeBgeModal()" aria-label="Close">&times;</button>
            <h2 class="bge-modal-title">
                {{ $isPending ? 'BGE Payment Pending Confirmation' : 'BGE Subscription Required' }}
            </h2>
            <p class="bge-modal-subtitle">
                {{ $isPending ? 'Your subscription request is being reviewed by your Partner.' : 'Your 48-hour BizGrowth Engine (BGE) free trial period has ended.' }}
            </p>
        </div>

        <!-- Modal Body Content -->
        <div class="bge-modal-body">
            <!-- Plan Info Pill -->
            <div class="bge-plan-box">
                <div class="bge-plan-left">
                    <span class="bge-plan-badge">BizGrowth Engine (BGE)</span>
                    <span class="bge-plan-status {{ $isPending ? 'status-pending' : 'status-expired' }}">
                        {{ $isPending ? 'Processing' : '48h Trial Ended' }}
                    </span>
                </div>
                <div class="bge-plan-price">
                    RM 30.00 <span class="bge-plan-period">/ 30 Days</span>
                </div>
            </div>

            <!-- Informational Text -->
            <p class="bge-desc-text">
                @if ($isPending)
                    Your payment details have been submitted. Please contact your sponsoring Partner below to expedite approval and unlock full system access.
                @else
                    BizGrowth Engine (BGE) is the core operational engine of your PIOS business. To continue using the platform, please contact your <strong>Partner</strong> to make your subscription payment.
                @endif
            </p>

            <!-- Sponsoring Partner Contact Card -->
            <div class="bge-partner-card">
                <div class="bge-partner-card-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#134C49" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Your Sponsoring Partner:</span>
                </div>
                <div class="bge-partner-details">
                    <div class="bge-partner-name">{{ $partner?->applicant_name ?? 'PIOS HQ Management' }}</div>
                    <div class="bge-partner-meta">
                        @if ($partner?->partner_no)
                            <span class="bge-partner-id">ID No: <strong>{{ $partner->partner_no }}</strong></span>
                        @endif
                        @if ($partnerPhone)
                            <span class="bge-partner-phone">Tel: <strong>{{ $partnerPhone }}</strong></span>
                        @endif
                    </div>
                </div>

                @if ($waUrl)
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="bge-btn-whatsapp">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.455 5.711 1.456h.005c6.554 0 11.89-5.336 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <span>Contact Partner via WhatsApp</span>
                    </a>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="bge-modal-actions">
                <a href="{{ route('business.subscriptions.index') }}" class="bge-btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <span>Manage Subscription in Portal</span>
                </a>
                <button type="button" class="bge-btn-dismiss" onclick="closeBgeModal()">
                    Dismiss
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.bge-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(15, 23, 42, 0.78);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    opacity: 1;
    transition: opacity 0.25s ease;
}

.bge-modal-card {
    background: #FFFFFF;
    border-radius: 20px;
    max-width: 500px;
    width: 100%;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    border: 1px solid #E2E8F0;
    animation: bgeModalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes bgeModalPop {
    from {
        opacity: 0;
        transform: scale(0.92) translateY(16px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.bge-modal-header {
    padding: 26px 24px 20px;
    text-align: center;
    position: relative;
    border-bottom: 1px solid #F1F5F9;
}

.bge-header-expired {
    background: linear-gradient(180deg, #FEF2F2 0%, #FFFFFF 100%);
}

.bge-header-pending {
    background: linear-gradient(180deg, #FFFBEB 0%, #FFFFFF 100%);
}

.bge-modal-icon-wrapper {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    margin: 0 auto 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #FFFFFF;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}

.bge-modal-close {
    position: absolute;
    top: 14px;
    right: 16px;
    background: transparent;
    border: none;
    font-size: 24px;
    line-height: 1;
    color: #94A3B8;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    transition: all 0.2s;
}

.bge-modal-close:hover {
    color: #334155;
    background: #F1F5F9;
}

.bge-modal-title {
    font-size: 1.28rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 6px;
    letter-spacing: -0.01em;
}

.bge-modal-subtitle {
    font-size: 0.88rem;
    color: #64748B;
    margin: 0;
    line-height: 1.45;
}

.bge-modal-body {
    padding: 20px 24px 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.bge-plan-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 12px 16px;
}

.bge-plan-left {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.bge-plan-badge {
    font-weight: 700;
    font-size: 0.92rem;
    color: #134C49;
}

.bge-plan-status {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-block;
    width: fit-content;
}

.status-expired {
    background: #FEE2E2;
    color: #DC2626;
}

.status-pending {
    background: #FEF3C7;
    color: #B45309;
}

.bge-plan-price {
    font-size: 1.25rem;
    font-weight: 900;
    color: #0F172A;
}

.bge-plan-period {
    font-size: 0.75rem;
    font-weight: 500;
    color: #64748B;
}

.bge-desc-text {
    font-size: 0.88rem;
    color: #475569;
    line-height: 1.55;
    margin: 0;
}

.bge-partner-card {
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    border-radius: 14px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.bge-partner-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #166534;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.bge-partner-details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.bge-partner-name {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0F172A;
}

.bge-partner-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.82rem;
    color: #475569;
}

.bge-btn-whatsapp {
    background: #25D366;
    color: #FFFFFF !important;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.92rem;
    padding: 11px 18px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
    transition: all 0.2s ease;
}

.bge-btn-whatsapp:hover {
    background: #20BA5A;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(37, 211, 102, 0.4);
}

.bge-modal-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 4px;
}

.bge-btn-primary {
    background: #134C49;
    color: #FFFFFF !important;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.92rem;
    padding: 11px 18px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.bge-btn-primary:hover {
    background: #0E3B39;
}

.bge-btn-dismiss {
    background: transparent;
    border: none;
    color: #64748B;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 8px;
    cursor: pointer;
    border-radius: 8px;
    transition: color 0.2s;
}

.bge-btn-dismiss:hover {
    color: #0F172A;
}

/* Dark Mode Overrides */
html.dark .bge-modal-card {
    background: #1E293B;
    border-color: #334155;
    color: #F8FAFC;
}

html.dark .bge-modal-header {
    border-bottom-color: #334155;
}

html.dark .bge-header-expired {
    background: linear-gradient(180deg, rgba(239, 68, 68, 0.15) 0%, #1E293B 100%);
}

html.dark .bge-header-pending {
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.15) 0%, #1E293B 100%);
}

html.dark .bge-modal-icon-wrapper {
    background: #0F172A;
}

html.dark .bge-modal-title {
    color: #F8FAFC;
}

html.dark .bge-plan-box {
    background: #0F172A;
    border-color: #334155;
}

html.dark .bge-plan-badge {
    color: #2DD4BF;
}

html.dark .bge-plan-price {
    color: #F8FAFC;
}

html.dark .bge-desc-text {
    color: #CBD5E1;
}

html.dark .bge-partner-card {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.25);
}

html.dark .bge-partner-name {
    color: #F8FAFC;
}

html.dark .bge-partner-card-header {
    color: #4ADE80;
}

html.dark .bge-partner-meta {
    color: #94A3B8;
}

html.dark .bge-btn-dismiss {
    color: #94A3B8;
}

html.dark .bge-btn-dismiss:hover {
    color: #F8FAFC;
}
</style>

<script>
function closeBgeModal() {
    const modal = document.getElementById('bgeExpiredModalOverlay');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 200);
    }
}
</script>

