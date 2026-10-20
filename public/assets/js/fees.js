/**
 * PIOS BizGrowth Engine | BGE Fees Interactive Script (fees.js)
 * Supports Mobile Menu Toggle, Theme Toggle (Sync with localStorage),
 * and Interactive Payment Checkout Simulation.
 */

document.addEventListener('DOMContentLoaded', () => {

    /* ==========================================================================
       1. Theme Toggle (Handled globally by window.togglePiosTheme in layouts/business.blade.php)
       ========================================================================== */
    function applyTheme(theme) {
        if (typeof window.applyPiosTheme === 'function') {
            window.applyPiosTheme(theme === 'dark');
        }
    }


    /* ==========================================================================
       2. Fullscreen Mobile Navigation Menu (handled globally by window.togglePiosMenu)
       ========================================================================== */


    /* ==========================================================================
       3. Interactive Payment Checkout Modal
       ========================================================================== */
    const paymentModalBackdrop = document.getElementById('paymentModalBackdrop');
    const payNowBtn = document.getElementById('payNowBtn');
    const btnTablePayAug = document.getElementById('btnTablePayAug');
    const btnMobilePayAug = document.getElementById('btnMobilePayAug');
    const closePaymentModalBtn = document.getElementById('closePaymentModalBtn');
    const cancelPaymentModalBtn = document.getElementById('cancelPaymentModalBtn');
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
    const confirmBtnText = document.getElementById('confirmBtnText');
    const feeToast = document.getElementById('feeToast');
    const feeToastMessage = document.getElementById('feeToastMessage');

    // Method selection cards
    const methodCards = document.querySelectorAll('.method-card');
    methodCards.forEach(card => {
        card.addEventListener('click', () => {
            methodCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    function openPaymentModal() {
        if (!paymentModalBackdrop) return;
        paymentModalBackdrop.classList.add('open');
        paymentModalBackdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closePaymentModal() {
        if (!paymentModalBackdrop) return;
        paymentModalBackdrop.classList.remove('open');
        paymentModalBackdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (payNowBtn) payNowBtn.addEventListener('click', openPaymentModal);
    if (btnTablePayAug) btnTablePayAug.addEventListener('click', openPaymentModal);
    if (btnMobilePayAug) btnMobilePayAug.addEventListener('click', openPaymentModal);
    if (closePaymentModalBtn) closePaymentModalBtn.addEventListener('click', closePaymentModal);
    if (cancelPaymentModalBtn) cancelPaymentModalBtn.addEventListener('click', closePaymentModal);

    // Close on clicking backdrop outside dialog
    if (paymentModalBackdrop) {
        paymentModalBackdrop.addEventListener('click', (e) => {
            if (e.target === paymentModalBackdrop) {
                closePaymentModal();
            }
        });
    }

    function showToast(message) {
        if (!feeToast) return;
        if (feeToastMessage) feeToastMessage.textContent = message;
        feeToast.classList.add('show');
        setTimeout(() => {
            feeToast.classList.remove('show');
        }, 4000);
    }

    // Payment confirmation simulation
    let isPaid = false;
    if (confirmPaymentBtn) {
        confirmPaymentBtn.addEventListener('click', () => {
            if (isPaid) return;

            // Loading state
            confirmPaymentBtn.disabled = true;
            if (confirmBtnText) confirmBtnText.textContent = 'Processing Payment...';

            setTimeout(() => {
                isPaid = true;
                confirmPaymentBtn.disabled = false;
                if (confirmBtnText) confirmBtnText.textContent = 'Confirm & Pay RM 900.00';
                closePaymentModal();

                // 1. Update Card 1 (Current Monthly Fee)
                const currentFeeBadge = document.getElementById('currentFeeBadge');
                if (currentFeeBadge) {
                    currentFeeBadge.className = 'badge-status-paid';
                    currentFeeBadge.textContent = 'Paid';
                }
                if (payNowBtn) {
                    payNowBtn.innerHTML = `
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Paid &bull; August 2026</span>
                    `;
                    payNowBtn.style.backgroundColor = '#10B981';
                    payNowBtn.style.cursor = 'default';
                    payNowBtn.disabled = true;
                }

                // 2. Update Card 3 (Total Paid - Desktop) & Mobile Banner
                const totalPaidAmount = document.getElementById('totalPaidAmount');
                const mobileTotalPaidAmount = document.getElementById('mobileTotalPaidAmount');
                const paymentsOnTimeText = document.getElementById('paymentsOnTimeText');
                const progressFill = document.getElementById('progressFill');
                const progressPctText = document.getElementById('progressPctText');

                if (totalPaidAmount) totalPaidAmount.textContent = 'RM 4,950';
                if (mobileTotalPaidAmount) mobileTotalPaidAmount.textContent = 'RM 4,950';
                if (paymentsOnTimeText) paymentsOnTimeText.textContent = '6 payments on time';
                if (progressFill) progressFill.style.width = '100%';
                if (progressPctText) progressPctText.textContent = '100%';

                // 3. Update Desktop Table Row for Aug 2026
                const datePaidAug = document.getElementById('datePaidAug');
                if (datePaidAug) {
                    datePaidAug.textContent = 'Aug 21, 2026';
                    datePaidAug.classList.add('text-teal', 'font-semibold');
                }
                const statusActionAug = document.getElementById('statusActionAug');
                if (statusActionAug) {
                    statusActionAug.innerHTML = `<span class="badge-status-paid">Paid</span>`;
                }

                // 4. Update Mobile Item for Aug 2026
                const mobileDatePaidAug = document.getElementById('mobileDatePaidAug');
                if (mobileDatePaidAug) {
                    mobileDatePaidAug.style.display = 'block';
                }
                const mobileStatusRowAug = document.getElementById('mobileStatusRowAug');
                if (mobileStatusRowAug) {
                    mobileStatusRowAug.innerHTML = `<span class="badge-status-paid">Paid</span>`;
                }

                // 5. Update Footer Summary
                const footerOutstandingAmount = document.getElementById('footerOutstandingAmount');
                const footerTotalPaidAmount = document.getElementById('footerTotalPaidAmount');
                if (footerOutstandingAmount) {
                    footerOutstandingAmount.textContent = 'RM 0';
                    footerOutstandingAmount.className = 'text-teal font-bold';
                }
                if (footerTotalPaidAmount) {
                    footerTotalPaidAmount.textContent = 'RM 4,950';
                }

                // 6. Show Toast
                showToast('Payment successful! August 2026 fee is now Paid.');
            }, 800);
        });
    }

});
