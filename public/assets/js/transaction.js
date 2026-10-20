/**
 * PIOS BizGrowth Engine - Business Transactions Script (transaction.js)
 * Handles real-time search, status filtering, transaction flow pipeline,
 * order lifecycle progression (Submitted -> Paid -> Packed -> Shipped -> Completed),
 * tracking number assignment, status modal, details popup, and dark/light mode.
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- Elements ---
    const searchInput = document.getElementById('txSearchInput');
    const paymentFilterSelect = document.getElementById('paymentFilterSelect');
    const tableBody = document.getElementById('transactionTableBody');
    const mobileList = document.getElementById('txMobileList');
    const txCountText = document.getElementById('txCountText');
    const flowSteps = document.querySelectorAll('.tx-flow-step');

    // Details Modal Elements
    const txModal = document.getElementById('txModal');
    const closeTxModalBtn = document.getElementById('closeTxModalBtn');
    const cancelTxModalBtn = document.getElementById('cancelTxModalBtn');
    const modalOrderNo = document.getElementById('modalOrderNo');
    const modalStatusBadge = document.getElementById('modalStatusBadge');
    const modalCustomerName = document.getElementById('modalCustomerName');
    const modalCustomerPhone = document.getElementById('modalCustomerPhone');
    const modalOrderDate = document.getElementById('modalOrderDate');
    const modalTotalAmount = document.getElementById('modalTotalAmount');
    const modalPaymentStatus = document.getElementById('modalPaymentStatus');
    const modalPaidAt = document.getElementById('modalPaidAt');
    const modalShippedAt = document.getElementById('modalShippedAt');
    const modalTrackingSection = document.getElementById('modalTrackingSection');
    const modalTrackingNumber = document.getElementById('modalTrackingNumber');
    const modalItemsContainer = document.getElementById('modalItemsContainer');
    const detailsStepper = document.getElementById('detailsStepper');
    const modalActionBtn = document.getElementById('modalActionBtn');

    // Update Status Modal Elements
    const updateStatusModal = document.getElementById('updateStatusModal');
    const closeStatusModalBtn = document.getElementById('closeStatusModalBtn');
    const cancelStatusModalBtn = document.getElementById('cancelStatusModalBtn');
    const updateStatusForm = document.getElementById('updateStatusForm');
    const statusModalOrderId = document.getElementById('statusModalOrderId');
    const statusModalOrderNo = document.getElementById('statusModalOrderNo');
    const statusModalCustomer = document.getElementById('statusModalCustomer');
    const statusModalCurrentBadge = document.getElementById('statusModalCurrentBadge');
    const statusStepper = document.getElementById('statusStepper');
    const statusOptionsGrid = document.getElementById('statusOptionsGrid');
    const trackingInputGroup = document.getElementById('trackingInputGroup');
    const trackingNumberInput = document.getElementById('trackingNumberInput');
    const statusModalError = document.getElementById('statusModalError');
    const saveStatusBtn = document.getElementById('saveStatusBtn');
    const saveStatusBtnText = document.getElementById('saveStatusBtnText');

    // Mark Paid Modal Elements
    const markPaidModal = document.getElementById('markPaidModal');
    const closeMarkPaidModalBtn = document.getElementById('closeMarkPaidModalBtn');
    const cancelMarkPaidModalBtn = document.getElementById('cancelMarkPaidModalBtn');
    const markPaidForm = document.getElementById('markPaidForm');
    const markPaidOrderId = document.getElementById('markPaidOrderId');
    const markPaidOrderTitle = document.getElementById('markPaidOrderTitle');
    const markPaidCustomer = document.getElementById('markPaidCustomer');
    const markPaidAmount = document.getElementById('markPaidAmount');
    const paymentReferenceInput = document.getElementById('paymentReferenceInput');
    const paymentMethodSelect = document.getElementById('paymentMethodSelect');
    const markPaidModalError = document.getElementById('markPaidModalError');
    const submitMarkPaidBtn = document.getElementById('submitMarkPaidBtn');
    const submitMarkPaidBtnText = document.getElementById('submitMarkPaidBtnText');

    // Ship Order Modal Elements
    const shipOrderModal = document.getElementById('shipOrderModal');
    const closeShipOrderModalBtn = document.getElementById('closeShipOrderModalBtn');
    const cancelShipOrderModalBtn = document.getElementById('cancelShipOrderModalBtn');
    const shipOrderForm = document.getElementById('shipOrderForm');
    const shipOrderId = document.getElementById('shipOrderId');
    const shipOrderTitle = document.getElementById('shipOrderTitle');
    const shipOrderCustomer = document.getElementById('shipOrderCustomer');
    const shipDeliveryProviderSelect = document.getElementById('shipDeliveryProviderSelect');
    const customProviderWrap = document.getElementById('customProviderWrap');
    const customProviderInput = document.getElementById('customProviderInput');
    const shipTrackingNumberInput = document.getElementById('shipTrackingNumberInput');
    const shipOrderModalError = document.getElementById('shipOrderModalError');
    const submitShipOrderBtn = document.getElementById('submitShipOrderBtn');
    const submitShipOrderBtnText = document.getElementById('submitShipOrderBtnText');

    // Toast
    const toastNotification = document.getElementById('toastNotification');
    const toastMessage = document.getElementById('toastMessage');

    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // State
    let currentSearchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';
    let currentStatusFilter = paymentFilterSelect ? paymentFilterSelect.value : 'all';

    // --- Helper: Toast Notification ---
    function showToast(msg) {
        if (!toastNotification || !toastMessage) return;
        toastMessage.textContent = msg;
        toastNotification.classList.add('show');
        setTimeout(() => {
            toastNotification.classList.remove('show');
        }, 3200);
    }

    // --- Helper: Render Stepper Nodes ---
    const STEP_ORDER = ['submitted', 'paid', 'packed', 'shipped', 'completed'];

    function renderStepper(stepperEl, currentStatus) {
        if (!stepperEl) return;
        const normalized = (currentStatus || '').toLowerCase();
        const currentIndex = STEP_ORDER.indexOf(normalized);

        const nodes = stepperEl.querySelectorAll('.stepper-node');
        nodes.forEach(node => {
            const stepName = node.getAttribute('data-step');
            const stepIndex = STEP_ORDER.indexOf(stepName);
            const bar = node.querySelector('.stepper-bar');

            node.classList.remove('done', 'active', 'upcoming', 'cancelled');
            if (bar) bar.classList.remove('done');

            if (normalized === 'cancelled') {
                node.classList.add('cancelled');
            } else if (currentIndex !== -1) {
                if (stepIndex < currentIndex) {
                    node.classList.add('done');
                    if (bar) bar.classList.add('done');
                } else if (stepIndex === currentIndex) {
                    node.classList.add('active');
                } else {
                    node.classList.add('upcoming');
                }
            } else {
                node.classList.add('upcoming');
            }
        });
    }

    // --- 1. Filtering Functionality ---
    function applyFilters() {
        const tableRows = tableBody ? tableBody.querySelectorAll('.tx-row') : [];
        const mobileCards = mobileList ? mobileList.querySelectorAll('.tx-card') : [];
        let visibleCount = 0;

        tableRows.forEach(row => {
            const order = (row.getAttribute('data-order') || '').toLowerCase();
            const customer = (row.getAttribute('data-customer') || '').toLowerCase();
            const product = (row.getAttribute('data-product') || '').toLowerCase();
            const tracking = (row.getAttribute('data-tracking') || '').toLowerCase();
            const status = (row.getAttribute('data-status') || '').toLowerCase();

            const matchesSearch = !currentSearchQuery || 
                order.includes(currentSearchQuery) || 
                customer.includes(currentSearchQuery) || 
                product.includes(currentSearchQuery) ||
                tracking.includes(currentSearchQuery);

            const matchesStatus = currentStatusFilter === 'all' || 
                status === currentStatusFilter.toLowerCase();

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        mobileCards.forEach(card => {
            const order = (card.getAttribute('data-order') || '').toLowerCase();
            const customer = (card.getAttribute('data-customer') || '').toLowerCase();
            const product = (card.getAttribute('data-product') || '').toLowerCase();
            const tracking = (card.getAttribute('data-tracking') || '').toLowerCase();
            const status = (card.getAttribute('data-status') || '').toLowerCase();

            const matchesSearch = !currentSearchQuery || 
                order.includes(currentSearchQuery) || 
                customer.includes(currentSearchQuery) || 
                product.includes(currentSearchQuery) ||
                tracking.includes(currentSearchQuery);

            const matchesStatus = currentStatusFilter === 'all' || 
                status === currentStatusFilter.toLowerCase();

            if (matchesSearch && matchesStatus) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        if (txCountText) {
            txCountText.textContent = `Showing ${visibleCount} of ${tableRows.length} transactions`;
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearchQuery = e.target.value.toLowerCase().trim();
            applyFilters();
        });
    }

    if (paymentFilterSelect) {
        paymentFilterSelect.addEventListener('change', (e) => {
            currentStatusFilter = e.target.value;
            flowSteps.forEach(s => {
                if (s.getAttribute('data-status').toLowerCase() === currentStatusFilter.toLowerCase()) {
                    s.classList.add('active-filter');
                } else {
                    s.classList.remove('active-filter');
                }
            });
            applyFilters();
        });
    }

    flowSteps.forEach(step => {
        step.addEventListener('click', () => {
            const stepStatus = step.getAttribute('data-status');
            if (currentStatusFilter.toLowerCase() === stepStatus.toLowerCase()) {
                currentStatusFilter = 'all';
                step.classList.remove('active-filter');
                if (paymentFilterSelect) paymentFilterSelect.value = 'all';
            } else {
                flowSteps.forEach(s => s.classList.remove('active-filter'));
                step.classList.add('active-filter');
                currentStatusFilter = stepStatus;
                if (paymentFilterSelect) paymentFilterSelect.value = stepStatus;
            }
            applyFilters();
        });
    });

    // --- 2. Order Details Modal ---
    function openOrderDetailsById(orderId) {
        const row = document.getElementById(`txRow-${orderId}`) || document.getElementById(`txCard-${orderId}`);
        if (!row || !txModal) return;

        const orderNo = row.getAttribute('data-order') || '-';
        const customer = row.getAttribute('data-customer') || '-';
        const customerPhone = row.getAttribute('data-customer-phone') || '-';
        const status = row.getAttribute('data-status') || '';
        const statusLabel = row.getAttribute('data-status-label') || status;
        const tracking = row.getAttribute('data-tracking') || '';
        const amount = row.getAttribute('data-amount') || '0';
        const date = row.getAttribute('data-date') || '-';
        const paidAt = row.getAttribute('data-paid-at') || '-';
        const shippedAt = row.getAttribute('data-shipped-at') || '-';
        const isTerminal = row.getAttribute('data-is-terminal') === '1';

        let items = [];
        try {
            items = JSON.parse(row.getAttribute('data-items') || '[]');
        } catch (e) {
            items = [];
        }

        if (modalOrderNo) modalOrderNo.textContent = `#${orderNo}`;
        if (modalCustomerName) modalCustomerName.textContent = customer;
        if (modalCustomerPhone) modalCustomerPhone.textContent = customerPhone;
        if (modalOrderDate) modalOrderDate.textContent = date;
        if (modalTotalAmount) modalTotalAmount.textContent = `RM ${Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (modalPaidAt) modalPaidAt.textContent = paidAt;

        if (modalStatusBadge) {
            modalStatusBadge.textContent = statusLabel;
            modalStatusBadge.className = `badge-status badge-${status.toLowerCase()}`;
        }

        // Stepper
        renderStepper(detailsStepper, status);

        // Tracking Section
        if (modalTrackingSection) {
            if (tracking) {
                modalTrackingSection.style.display = 'flex';
                if (modalTrackingNumber) modalTrackingNumber.textContent = tracking;
                if (modalShippedAt) modalShippedAt.textContent = shippedAt;
            } else {
                modalTrackingSection.style.display = 'none';
            }
        }

        // Items Table
        if (modalItemsContainer) {
            if (items.length > 0) {
                let html = `
                    <table class="modal-items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="text-align: center;">Qty</th>
                                <th style="text-align: right;">Unit (RM)</th>
                                <th style="text-align: right;">Total (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                items.forEach(it => {
                    html += `
                        <tr>
                            <td><strong>${escapeHtml(it.name)}</strong></td>
                            <td style="text-align: center;">${it.qty}</td>
                            <td style="text-align: right;">${it.price}</td>
                            <td style="text-align: right; font-weight: 600;">${it.total}</td>
                        </tr>
                    `;
                });
                html += `</tbody></table>`;
                modalItemsContainer.innerHTML = html;
            } else {
                modalItemsContainer.innerHTML = `<div style="color: #64748B; font-size: 0.85rem; padding: 6px 0;">No item details available.</div>`;
            }
        }

        // Payment Description
        if (modalPaymentStatus) {
            if (status === 'completed') {
                modalPaymentStatus.textContent = 'Order completed and delivered.';
            } else if (status === 'shipped') {
                modalPaymentStatus.textContent = `In transit via courier (Tracking: ${tracking || 'Assigned'}).`;
            } else if (status === 'packed') {
                modalPaymentStatus.textContent = 'Items packed, ready for shipment.';
            } else if (status === 'paid') {
                modalPaymentStatus.textContent = 'Payment confirmed and verified.';
            } else if (status === 'submitted') {
                modalPaymentStatus.textContent = 'Order submitted, pending payment confirmation.';
            } else {
                modalPaymentStatus.textContent = `Status: ${statusLabel}`;
            }
        }

        // Modal Action Button (dynamic based on status)
        if (modalActionBtn) {
            const normalized = (status || '').toLowerCase().trim();
            // Reset classes
            modalActionBtn.classList.remove('btn-step-paid', 'btn-step-ship', 'btn-step-complete');
            modalActionBtn.style.display = 'none';
            modalActionBtn.onclick = null;

            if (!isTerminal && normalized !== 'cancelled') {
                // Store context on the button for the click handler
                const _orderId = orderId;
                const _orderNo = orderNo;
                const _customer = customer;
                const _amount = amount;
                const _tracking = tracking;

                modalActionBtn.dataset.orderId = _orderId;
                modalActionBtn.dataset.orderNo = _orderNo;
                modalActionBtn.dataset.customer = _customer;
                modalActionBtn.dataset.amount = _amount;
                modalActionBtn.dataset.tracking = _tracking;

                if (normalized === 'draft') {
                    modalActionBtn.style.display = 'inline-flex';
                    modalActionBtn.classList.add('btn-step-paid');
                    modalActionBtn.textContent = 'Submit Order';
                    modalActionBtn.onclick = function(e) {
                        if (e) { e.preventDefault(); e.stopPropagation(); }
                        closeTxModal();
                        openQuickStatusUpdate(_orderId, _orderNo, 'submitted', 'Submitted');
                    };
                } else if (normalized === 'submitted') {
                    modalActionBtn.style.display = 'inline-flex';
                    modalActionBtn.classList.add('btn-step-paid');
                    modalActionBtn.textContent = 'Mark Paid';
                    modalActionBtn.onclick = function(e) {
                        if (e) { e.preventDefault(); e.stopPropagation(); }
                        closeTxModal();
                        openMarkPaidModal(_orderId, _orderNo, _customer, _amount);
                    };
                } else if (normalized === 'paid' || normalized === 'packed') {
                    modalActionBtn.style.display = 'inline-flex';
                    modalActionBtn.classList.add('btn-step-ship');
                    modalActionBtn.textContent = 'Ship Order';
                    modalActionBtn.onclick = function(e) {
                        if (e) { e.preventDefault(); e.stopPropagation(); }
                        closeTxModal();
                        openShipOrderModal(_orderId, _orderNo, _customer, _tracking);
                    };
                } else if (normalized === 'shipped') {
                    modalActionBtn.style.display = 'inline-flex';
                    modalActionBtn.classList.add('btn-step-complete');
                    modalActionBtn.textContent = 'Mark Completed';
                    modalActionBtn.onclick = function(e) {
                        if (e) { e.preventDefault(); e.stopPropagation(); }
                        closeTxModal();
                        openQuickStatusUpdate(_orderId, _orderNo, 'completed', 'Completed');
                    };
                }
            }
        }

        txModal.classList.add('active');
        txModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    window.openOrderDetailsById = openOrderDetailsById;

    function closeTxModal() {
        if (!txModal) return;
        txModal.classList.remove('active');
        txModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (closeTxModalBtn) closeTxModalBtn.addEventListener('click', closeTxModal);
    if (cancelTxModalBtn) cancelTxModalBtn.addEventListener('click', closeTxModal);
    if (txModal) {
        txModal.addEventListener('click', (e) => {
            if (e.target === txModal) closeTxModal();
        });
    }

    // --- 3. Dedicated Update Order Status Modal ---
    const STATUS_DESCRIPTIONS = {
        'submitted': { title: 'Pending', desc: 'Order submitted, awaiting payment' },
        'paid': { title: 'Paid', desc: 'Mark payment as verified and received' },
        'packed': { title: 'Packed', desc: 'Items verified, boxed, and packed' },
        'shipped': { title: 'Shipped', desc: 'Handed to courier with tracking number' },
        'completed': { title: 'Completed', desc: 'Order successfully delivered to customer' },
        'cancelled': { title: 'Cancelled', desc: 'Cancel order and stop fulfillment' }
    };

    function openUpdateStatusModal(orderId, preselectedStatus = null) {
        const row = document.getElementById(`txRow-${orderId}`) || document.getElementById(`txCard-${orderId}`);
        if (!row || !updateStatusModal) return;

        const orderNo = row.getAttribute('data-order') || '-';
        const customer = row.getAttribute('data-customer') || '-';
        const currentStatus = row.getAttribute('data-status') || '';
        const currentLabel = row.getAttribute('data-status-label') || currentStatus;
        const currentTracking = row.getAttribute('data-tracking') || '';

        let nextStatuses = [];
        try {
            nextStatuses = JSON.parse(row.getAttribute('data-next-statuses') || '[]');
        } catch (e) {
            nextStatuses = [];
        }

        if (statusModalOrderId) statusModalOrderId.value = orderId;
        if (statusModalOrderNo) statusModalOrderNo.textContent = `Update Status - #${orderNo}`;
        if (statusModalCustomer) statusModalCustomer.textContent = customer;

        if (statusModalCurrentBadge) {
            statusModalCurrentBadge.textContent = currentLabel;
            statusModalCurrentBadge.className = `badge-status badge-${currentStatus.toLowerCase()}`;
        }

        renderStepper(statusStepper, currentStatus);

        // Populate Status Radio Cards
        if (statusOptionsGrid) {
            statusOptionsGrid.innerHTML = '';
            let targetToSelect = preselectedStatus;

            // Default to primary forward step if no pre-selection
            if (!targetToSelect && nextStatuses.length > 0) {
                targetToSelect = nextStatuses[0].value;
            }

            nextStatuses.forEach(st => {
                const isSelected = (st.value === targetToSelect);
                const info = STATUS_DESCRIPTIONS[st.value] || { title: st.label, desc: '' };
                const isCancelled = (st.value === 'cancelled');

                const card = document.createElement('div');
                card.className = `status-radio-card ${isSelected ? 'selected' : ''} ${isCancelled ? 'card-cancelled' : ''}`;
                card.innerHTML = `
                    <input type="radio" name="target_status" value="${st.value}" ${isSelected ? 'checked' : ''}>
                    <span class="status-radio-name">${info.title}</span>
                    <span class="status-radio-desc">${info.desc}</span>
                `;

                card.addEventListener('click', () => {
                    statusOptionsGrid.querySelectorAll('.status-radio-card').forEach(c => {
                        c.classList.remove('selected');
                        const r = c.querySelector('input[type="radio"]');
                        if (r) r.checked = false;
                    });
                    card.classList.add('selected');
                    const radio = card.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;

                    toggleTrackingField(st.value);
                });

                statusOptionsGrid.appendChild(card);
            });

            toggleTrackingField(targetToSelect);
        }

        if (trackingNumberInput) {
            trackingNumberInput.value = currentTracking;
        }

        if (statusModalError) {
            statusModalError.style.display = 'none';
            statusModalError.textContent = '';
        }

        updateStatusModal.classList.add('active');
        updateStatusModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    window.openUpdateStatusModal = openUpdateStatusModal;

    function toggleTrackingField(statusValue) {
        if (!trackingInputGroup) return;
        if (statusValue === 'shipped') {
            trackingInputGroup.style.display = 'flex';
            if (trackingNumberInput) {
                setTimeout(() => trackingNumberInput.focus(), 80);
            }
        } else {
            trackingInputGroup.style.display = 'none';
        }
    }

    function closeStatusModal() {
        if (!updateStatusModal) return;
        updateStatusModal.classList.remove('active');
        updateStatusModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (closeStatusModalBtn) closeStatusModalBtn.addEventListener('click', closeStatusModal);
    if (cancelStatusModalBtn) cancelStatusModalBtn.addEventListener('click', closeStatusModal);
    if (updateStatusModal) {
        updateStatusModal.addEventListener('click', (e) => {
            if (e.target === updateStatusModal) closeStatusModal();
        });
    }

    // --- 4. Mark Paid Modal (Payment Reference Popup) ---
    function openMarkPaidModal(orderId, orderNo, customerName, totalAmount) {
        if (!markPaidModal) return;
        if (markPaidOrderId) markPaidOrderId.value = orderId;
        if (markPaidOrderTitle) markPaidOrderTitle.textContent = `Confirm Payment - #${orderNo}`;
        if (markPaidCustomer) markPaidCustomer.textContent = customerName || '-';
        if (markPaidAmount) {
            const raw = String(totalAmount ?? '').replace(/[^0-9.]/g, '');
            const num = parseFloat(raw);
            if (!isNaN(num)) {
                markPaidAmount.textContent = `RM ${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            } else {
                markPaidAmount.textContent = totalAmount ? String(totalAmount) : 'RM 0.00';
            }
        }
        if (paymentReferenceInput) {
            paymentReferenceInput.value = '';
            setTimeout(() => paymentReferenceInput.focus(), 80);
        }
        if (markPaidModalError) {
            markPaidModalError.style.display = 'none';
            markPaidModalError.textContent = '';
        }
        markPaidModal.classList.add('active');
        markPaidModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    window.openMarkPaidModal = openMarkPaidModal;

    function closeMarkPaidModal() {
        if (!markPaidModal) return;
        markPaidModal.classList.remove('active');
        markPaidModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (closeMarkPaidModalBtn) closeMarkPaidModalBtn.addEventListener('click', closeMarkPaidModal);
    if (cancelMarkPaidModalBtn) cancelMarkPaidModalBtn.addEventListener('click', closeMarkPaidModal);
    if (markPaidModal) {
        markPaidModal.addEventListener('click', (e) => {
            if (e.target === markPaidModal) closeMarkPaidModal();
        });
    }

    if (markPaidForm) {
        markPaidForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const orderId = markPaidOrderId ? markPaidOrderId.value : null;
            if (!orderId) return;

            const referenceNo = paymentReferenceInput ? paymentReferenceInput.value.trim() : '';
            if (!referenceNo) {
                if (markPaidModalError) {
                    markPaidModalError.textContent = 'Please enter a payment reference number.';
                    markPaidModalError.style.display = 'block';
                }
                if (paymentReferenceInput) paymentReferenceInput.focus();
                return;
            }

            const paymentType = paymentMethodSelect ? paymentMethodSelect.value : 'bank_transfer';

            if (submitMarkPaidBtn) submitMarkPaidBtn.disabled = true;
            if (submitMarkPaidBtnText) submitMarkPaidBtnText.textContent = 'Verifying...';

            try {
                const response = await fetch(`/business/transactions/${orderId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        status: 'paid',
                        reference_no: referenceNo,
                        payment_type: paymentType
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    if (markPaidModalError) {
                        markPaidModalError.textContent = data.message || 'Failed to record payment.';
                        markPaidModalError.style.display = 'block';
                    }
                    return;
                }

                updateOrderRowInDOM(data.order);
                if (data.metrics) {
                    updateMetricsInDOM(data.metrics);
                }

                closeMarkPaidModal();
                showToast(`Payment verified! Order #${data.order.order_no} is now Paid and ready to Ship.`);
                applyFilters();

            } catch (err) {
                if (markPaidModalError) {
                    markPaidModalError.textContent = 'Network error occurred. Please try again.';
                    markPaidModalError.style.display = 'block';
                }
            } finally {
                if (submitMarkPaidBtn) submitMarkPaidBtn.disabled = false;
                if (submitMarkPaidBtnText) submitMarkPaidBtnText.textContent = 'Confirm & Mark Paid';
            }
        });
    }

    // --- 5. Ship Order Modal (Delivery Provider & Tracking Number Popup) ---
    function openShipOrderModal(orderId, orderNo, customerName, existingTracking = '') {
        if (!shipOrderModal) return;
        if (shipOrderId) shipOrderId.value = orderId;
        if (shipOrderTitle) shipOrderTitle.textContent = `Ship Order - #${orderNo}`;
        if (shipOrderCustomer) shipOrderCustomer.textContent = customerName || '-';

        let providerVal = '';
        let trackVal = existingTracking || '';
        if (trackVal.includes(' - ')) {
            const parts = trackVal.split(' - ');
            providerVal = parts[0].trim();
            trackVal = parts.slice(1).join(' - ').trim();
        }

        if (shipDeliveryProviderSelect) {
            let matched = false;
            for (let i = 0; i < shipDeliveryProviderSelect.options.length; i++) {
                if (shipDeliveryProviderSelect.options[i].value.toLowerCase() === providerVal.toLowerCase()) {
                    shipDeliveryProviderSelect.selectedIndex = i;
                    matched = true;
                    break;
                }
            }
            if (!matched && providerVal) {
                shipDeliveryProviderSelect.value = 'Other';
                if (customProviderWrap) customProviderWrap.style.display = 'block';
                if (customProviderInput) customProviderInput.value = providerVal;
            } else {
                if (!matched) shipDeliveryProviderSelect.selectedIndex = 0;
                if (customProviderWrap) customProviderWrap.style.display = 'none';
                if (customProviderInput) customProviderInput.value = '';
            }
        }

        if (shipTrackingNumberInput) {
            shipTrackingNumberInput.value = trackVal;
            setTimeout(() => shipTrackingNumberInput.focus(), 80);
        }

        if (shipOrderModalError) {
            shipOrderModalError.style.display = 'none';
            shipOrderModalError.textContent = '';
        }

        shipOrderModal.classList.add('active');
        shipOrderModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    window.openShipOrderModal = openShipOrderModal;

    function closeShipOrderModal() {
        if (!shipOrderModal) return;
        shipOrderModal.classList.remove('active');
        shipOrderModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (closeShipOrderModalBtn) closeShipOrderModalBtn.addEventListener('click', closeShipOrderModal);
    if (cancelShipOrderModalBtn) cancelShipOrderModalBtn.addEventListener('click', closeShipOrderModal);
    if (shipOrderModal) {
        shipOrderModal.addEventListener('click', (e) => {
            if (e.target === shipOrderModal) closeShipOrderModal();
        });
    }

    if (shipDeliveryProviderSelect) {
        shipDeliveryProviderSelect.addEventListener('change', (e) => {
            if (e.target.value === 'Other') {
                if (customProviderWrap) {
                    customProviderWrap.style.display = 'block';
                    if (customProviderInput) customProviderInput.focus();
                }
            } else {
                if (customProviderWrap) customProviderWrap.style.display = 'none';
            }
        });
    }

    if (shipOrderForm) {
        shipOrderForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const orderId = shipOrderId ? shipOrderId.value : null;
            if (!orderId) return;

            let provider = shipDeliveryProviderSelect ? shipDeliveryProviderSelect.value : '';
            if (provider === 'Other' && customProviderInput) {
                provider = customProviderInput.value.trim();
            }

            if (!provider) {
                if (shipOrderModalError) {
                    shipOrderModalError.textContent = 'Please select or specify a delivery provider / courier.';
                    shipOrderModalError.style.display = 'block';
                }
                if (shipDeliveryProviderSelect) shipDeliveryProviderSelect.focus();
                return;
            }

            const trackingNumber = shipTrackingNumberInput ? shipTrackingNumberInput.value.trim() : '';
            if (!trackingNumber) {
                if (shipOrderModalError) {
                    shipOrderModalError.textContent = 'Please enter a shipping tracking number.';
                    shipOrderModalError.style.display = 'block';
                }
                if (shipTrackingNumberInput) shipTrackingNumberInput.focus();
                return;
            }

            if (submitShipOrderBtn) submitShipOrderBtn.disabled = true;
            if (submitShipOrderBtnText) submitShipOrderBtnText.textContent = 'Dispatching...';

            try {
                const response = await fetch(`/business/transactions/${orderId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        status: 'shipped',
                        delivery_provider: provider,
                        tracking_number: trackingNumber
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    if (shipOrderModalError) {
                        shipOrderModalError.textContent = data.message || 'Failed to update shipping status.';
                        shipOrderModalError.style.display = 'block';
                    }
                    return;
                }

                updateOrderRowInDOM(data.order);
                if (data.metrics) {
                    updateMetricsInDOM(data.metrics);
                }

                closeShipOrderModal();
                showToast(`Shipment confirmed! Order #${data.order.order_no} status changed to Shipped.`);
                applyFilters();

            } catch (err) {
                if (shipOrderModalError) {
                    shipOrderModalError.textContent = 'Network error occurred. Please try again.';
                    shipOrderModalError.style.display = 'block';
                }
            } finally {
                if (submitShipOrderBtn) submitShipOrderBtn.disabled = false;
                if (submitShipOrderBtnText) submitShipOrderBtnText.textContent = 'Confirm Shipment';
            }
        });
    }

    // Shortcut: Open Ship Modal
    window.openShipModal = function(orderId, orderNo, trackingNo = '') {
        const row = document.getElementById(`txRow-${orderId}`) || document.getElementById(`txCard-${orderId}`);
        const customer = row ? row.getAttribute('data-customer') : '';
        window.openShipOrderModal(orderId, orderNo, customer, trackingNo);
    };

    // Quick Update: Open modal pre-selected to target step
    function openQuickStatusUpdate(orderId, orderNo, targetStatus, targetLabel) {
        openUpdateStatusModal(orderId, targetStatus);
    }
    window.openQuickStatusUpdate = openQuickStatusUpdate;

    // --- 4. Submit Status Update via AJAX ---
    if (updateStatusForm) {
        updateStatusForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const orderId = statusModalOrderId ? statusModalOrderId.value : null;
            if (!orderId) return;

            const selectedRadio = statusOptionsGrid ? statusOptionsGrid.querySelector('input[name="target_status"]:checked') : null;
            if (!selectedRadio) {
                showModalError('Please select a target status.');
                return;
            }

            const targetStatus = selectedRadio.value;
            const trackingNumber = trackingNumberInput ? trackingNumberInput.value.trim() : '';

            if (targetStatus === 'shipped' && !trackingNumber) {
                showModalError('Courier tracking number is required to ship the order.');
                if (trackingNumberInput) trackingNumberInput.focus();
                return;
            }

            // Set loading state
            if (saveStatusBtn) saveStatusBtn.disabled = true;
            if (saveStatusBtnText) saveStatusBtnText.textContent = 'Updating...';

            try {
                const response = await fetch(`/business/transactions/${orderId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        status: targetStatus,
                        tracking_number: trackingNumber
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    showModalError(data.message || 'Failed to update order status.');
                    return;
                }

                // Success: Update DOM dynamically
                updateOrderRowInDOM(data.order);
                if (data.metrics) {
                    updateMetricsInDOM(data.metrics);
                }

                closeStatusModal();
                showToast(data.message || `Order status updated to ${data.order.status_label}.`);
                applyFilters();

            } catch (err) {
                showModalError('Network error occurred. Please try again.');
            } finally {
                if (saveStatusBtn) saveStatusBtn.disabled = false;
                if (saveStatusBtnText) saveStatusBtnText.textContent = 'Confirm Update';
            }
        });
    }

    function showModalError(msg) {
        if (!statusModalError) return;
        statusModalError.textContent = msg;
        statusModalError.style.display = 'block';
    }

    // --- 5. Dynamic DOM Synchronization after Status Update ---
    function updateOrderRowInDOM(order) {
        const row = document.getElementById(`txRow-${order.id}`);
        const card = document.getElementById(`txCard-${order.id}`);

        [row, card].forEach(el => {
            if (!el) return;

            el.setAttribute('data-status', order.status);
            el.setAttribute('data-status-label', order.status_label);
            el.setAttribute('data-tracking', order.tracking_number || '');
            el.setAttribute('data-is-terminal', order.is_terminal ? '1' : '0');
            el.setAttribute('data-next-statuses', JSON.stringify(order.next_statuses || []));
            if (order.paid_at) el.setAttribute('data-paid-at', order.paid_at);
            if (order.shipped_at) el.setAttribute('data-shipped-at', order.shipped_at);

            // Update Status Badge
            const badge = el.querySelector('.badge-status');
            if (badge) {
                badge.className = `badge-status badge-${order.status}`;
                badge.textContent = order.status_label;
            }

            // Update Tracking Tag
            const trackingContainer = el.querySelector('.row-tracking-wrap') || el.querySelector('.card-tracking-wrap');
            if (trackingContainer) {
                if (order.tracking_number) {
                    trackingContainer.innerHTML = `
                        <div class="tracking-tag" title="Tracking Number: ${order.tracking_number}">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            <span>${escapeHtml(order.tracking_number)}</span>
                        </div>
                    `;
                } else {
                    trackingContainer.innerHTML = '';
                }
            }

            // Update Action Buttons
            updateActionButtons(el, order);
        });
    }

    function updateActionButtons(container, order) {
        const actionGroup = container.querySelector('.tx-action-group') || container.querySelector('.tx-card-actions');
        if (!actionGroup) return;

        // Clean up any stray step/update buttons in the Action column (next action lives inside View modal)
        const stepBtn = actionGroup.querySelector('.btn-tx-step');
        if (stepBtn) stepBtn.remove();
        const statusBtn = actionGroup.querySelector('.btn-tx-status');
        if (statusBtn) statusBtn.remove();

        // If order is paid/packed/shipped/completed, ensure Receipt button exists
        const isPaid = ['paid', 'packed', 'shipped', 'completed'].includes((order.status || '').toLowerCase());
        let receiptLink = actionGroup.querySelector('a[title="View Receipt"]');
        if (isPaid && !receiptLink) {
            receiptLink = document.createElement('a');
            receiptLink.href = `/order/${order.id}/receipt`;
            receiptLink.target = '_blank';
            receiptLink.className = 'btn-tx-view';
            receiptLink.style.textDecoration = 'none';
            receiptLink.title = 'View Receipt';
            receiptLink.textContent = 'Receipt';
            actionGroup.appendChild(receiptLink);
        } else if (!isPaid && receiptLink) {
            receiptLink.remove();
        }
    }

    function updateMetricsInDOM(metrics) {
        const paidCountEl = document.querySelector('.tx-flow-step[data-status="paid"] .step-num');
        const pendingCountEl = document.querySelector('.tx-flow-step[data-status="submitted"] .step-num');
        const shippedCountEl = document.querySelector('.tx-flow-step[data-status="shipped"] .step-num');
        const completedCountEl = document.querySelector('.tx-flow-step[data-status="completed"] .step-num');

        if (paidCountEl && metrics.paidCount !== undefined) paidCountEl.textContent = metrics.paidCount;
        if (pendingCountEl && metrics.pendingCount !== undefined) pendingCountEl.textContent = metrics.pendingCount;
        if (shippedCountEl && metrics.shippedCount !== undefined) shippedCountEl.textContent = metrics.shippedCount;
        if (completedCountEl && metrics.completedCount !== undefined) completedCountEl.textContent = metrics.completedCount;

        // KPI cards
        const kpiValues = document.querySelectorAll('.kpi-value');
        if (kpiValues.length >= 4) {
            if (metrics.totalRevenue !== undefined) kpiValues[0].textContent = `RM ${metrics.totalRevenue}`;
            if (metrics.paidCount !== undefined) kpiValues[2].textContent = metrics.paidCount;
            if (metrics.pendingCount !== undefined) kpiValues[3].textContent = metrics.pendingCount;
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    // --- 6. Dynamic Date Sync ---
    const dateElements = document.querySelectorAll('.topbar-date');
    if (dateElements.length > 0) {
        const today = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
        const formattedDate = today.toLocaleDateString('en-US', options);
        dateElements.forEach(el => el.textContent = formattedDate);
    }
});
