/**
 * PIOS BizGrowth Engine | Customer Management Logic (customer.js)
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Mobile Fullscreen Menu Toggle (delegated to window.openPiosMenu / window.closePiosMenu) ---
    function openFullscreenMenu(e) {
        if (typeof window.openPiosMenu === 'function') {
            window.openPiosMenu(e);
        }
    }

    function closeFullscreenMenu(e) {
        if (typeof window.closePiosMenu === 'function') {
            window.closePiosMenu(e);
        }
    }

    // --- 2. Dark / Light Mode handled globally by window.togglePiosTheme in layouts/business.blade.php ---
    function setDarkMode(isDark) {
        if (typeof window.applyPiosTheme === 'function') {
            window.applyPiosTheme(isDark);
        }
    }

    // --- 3. Synchronized Live Search Filtering & Pagination (Max 10 per page) ---
    const PAGE_SIZE = 10;
    let currentPage = 1;

    const desktopSearchInput = document.getElementById('desktopSearchInput');
    const mobileSearchInput = document.getElementById('mobileSearchInput');
    let activeFilterQuery = (desktopSearchInput?.value || mobileSearchInput?.value || '').trim();

    const desktopTableCount = document.getElementById('desktopTableCount');
    const tablePaginationControls = document.getElementById('tablePaginationControls');
    const mobileTableCount = document.getElementById('mobileTableCount');
    const mobilePaginationControls = document.getElementById('mobilePaginationControls');
    const customerTableBody = document.getElementById('customerTableBody');
    const mobileCustomerList = document.querySelector('.mobile-customer-list');

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function getMatchingRows(query) {
        const q = (query || '').toLowerCase().trim();
        const allRows = Array.from(document.querySelectorAll('#customerTableBody tr:not(.empty-customer-row):not(.no-search-results-row)'));
        if (!q) return allRows;

        return allRows.filter(row => {
            const name = (row.getAttribute('data-name') || '').toLowerCase();
            const phone = (row.getAttribute('data-phone') || '').toLowerCase();
            const email = (row.getAttribute('data-email') || '').toLowerCase();
            const status = (row.getAttribute('data-status') || '').toLowerCase();
            return name.includes(q) || phone.includes(q) || email.includes(q) || status.includes(q);
        });
    }

    function getMatchingCards(query) {
        const q = (query || '').toLowerCase().trim();
        const allCards = Array.from(document.querySelectorAll('.mobile-cust-card:not(.empty-customer-card):not(.no-search-results-card)'));
        if (!q) return allCards;

        return allCards.filter(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const phone = (card.getAttribute('data-phone') || '').toLowerCase();
            const email = (card.getAttribute('data-email') || '').toLowerCase();
            const status = (card.getAttribute('data-status') || '').toLowerCase();
            return name.includes(q) || phone.includes(q) || email.includes(q) || status.includes(q);
        });
    }

    function getPageNumbers(current, total) {
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }
        if (current <= 4) {
            return [1, 2, 3, 4, 5, '...', total];
        }
        if (current >= total - 3) {
            return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        }
        return [1, '...', current - 1, current, current + 1, '...', total];
    }

    function renderPagination() {
        const matchedRows = getMatchingRows(activeFilterQuery);
        const matchedCards = getMatchingCards(activeFilterQuery);

        const totalItems = matchedRows.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * PAGE_SIZE;
        const endIdx = Math.min(startIdx + PAGE_SIZE, totalItems);

        // Hide all customer rows, display current page slice
        const allRows = document.querySelectorAll('#customerTableBody tr:not(.empty-customer-row):not(.no-search-results-row)');
        allRows.forEach(row => row.style.display = 'none');

        for (let i = startIdx; i < endIdx; i++) {
            if (matchedRows[i]) {
                matchedRows[i].style.display = '';
            }
        }

        // Handle desktop no search results state
        let noResultsRow = document.getElementById('noSearchResultsRow');
        const hasBaseRows = allRows.length > 0;
        if (hasBaseRows && totalItems === 0 && activeFilterQuery) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noSearchResultsRow';
                noResultsRow.className = 'no-search-results-row';
                customerTableBody?.appendChild(noResultsRow);
            }
            noResultsRow.innerHTML = `<td colspan="5" style="text-align: center; padding: 32px; color: #64748B;">No customers matching "<strong>${escapeHtml(activeFilterQuery)}</strong>" found.</td>`;
            noResultsRow.style.display = '';
        } else if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }

        // Hide all mobile cards, display current page slice
        const allCards = document.querySelectorAll('.mobile-cust-card:not(.empty-customer-card):not(.no-search-results-card)');
        allCards.forEach(card => card.style.display = 'none');

        for (let i = startIdx; i < endIdx; i++) {
            if (matchedCards[i]) {
                matchedCards[i].style.display = 'flex';
            }
        }

        // Handle mobile no search results state
        let noResultsCard = document.getElementById('noSearchResultsCard');
        const hasBaseCards = allCards.length > 0;
        if (hasBaseCards && totalItems === 0 && activeFilterQuery) {
            if (!noResultsCard) {
                noResultsCard = document.createElement('div');
                noResultsCard.id = 'noSearchResultsCard';
                noResultsCard.className = 'no-search-results-card';
                noResultsCard.style.cssText = 'padding: 24px; text-align: center; color: #64748B;';
                mobileCustomerList?.appendChild(noResultsCard);
            }
            noResultsCard.innerHTML = `No customers matching "<strong>${escapeHtml(activeFilterQuery)}</strong>" found.`;
            noResultsCard.style.display = '';
        } else if (noResultsCard) {
            noResultsCard.style.display = 'none';
        }

        // Update Desktop Counter Info
        if (desktopTableCount) {
            if (totalItems === 0) {
                desktopTableCount.textContent = 'Showing 0 of 0 customers';
            } else {
                desktopTableCount.textContent = `Showing ${startIdx + 1} to ${endIdx} of ${totalItems} customers`;
            }
        }

        // Update Mobile Counter Info
        if (mobileTableCount) {
            if (totalItems === 0) {
                mobileTableCount.textContent = 'Showing 0 of 0 customers';
            } else {
                mobileTableCount.textContent = `Showing ${startIdx + 1} to ${endIdx} of ${totalItems} customers`;
            }
        }

        // Render Pagination Button Controls
        renderControls(tablePaginationControls, totalPages, false);
        renderControls(mobilePaginationControls, totalPages, true);
    }

    function renderControls(container, totalPages, isMobile = false) {
        if (!container) return;
        container.innerHTML = '';

        if (totalPages <= 1) return;

        function scrollToTableTop() {
            if (isMobile) {
                const mobileTarget = document.querySelector('.mobile-action-bar') || container;
                mobileTarget?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                const desktopTarget = document.querySelector('.customer-table-card') || container;
                desktopTarget?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Prev Button
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'pagination-btn';
        prevBtn.innerHTML = `&lsaquo; Prev`;
        prevBtn.disabled = (currentPage === 1);
        prevBtn.setAttribute('aria-label', 'Previous Page');
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderPagination();
                scrollToTableTop();
            }
        });
        container.appendChild(prevBtn);

        // Smart Page Number Buttons
        const pageNumbers = getPageNumbers(currentPage, totalPages);
        pageNumbers.forEach(p => {
            if (p === '...') {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'pagination-ellipsis';
                ellipsis.textContent = '...';
                container.appendChild(ellipsis);
            } else {
                const pageBtn = document.createElement('button');
                pageBtn.type = 'button';
                pageBtn.className = `pagination-btn ${p === currentPage ? 'active' : ''}`;
                pageBtn.textContent = p;
                pageBtn.setAttribute('aria-label', `Page ${p}`);
                if (p === currentPage) {
                    pageBtn.setAttribute('aria-current', 'page');
                }
                pageBtn.addEventListener('click', () => {
                    if (currentPage !== p) {
                        currentPage = p;
                        renderPagination();
                        scrollToTableTop();
                    }
                });
                container.appendChild(pageBtn);
            }
        });

        // Next Button
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'pagination-btn';
        nextBtn.innerHTML = `Next &rsaquo;`;
        nextBtn.disabled = (currentPage === totalPages);
        nextBtn.setAttribute('aria-label', 'Next Page');
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderPagination();
                scrollToTableTop();
            }
        });
        container.appendChild(nextBtn);
    }

    function performSearch(query) {
        activeFilterQuery = (query || '').trim();
        currentPage = 1;
        renderPagination();
    }

    if (desktopSearchInput) {
        desktopSearchInput.addEventListener('input', (e) => {
            if (mobileSearchInput && mobileSearchInput.value !== e.target.value) {
                mobileSearchInput.value = e.target.value;
            }
            performSearch(e.target.value);
        });
    }

    if (mobileSearchInput) {
        mobileSearchInput.addEventListener('input', (e) => {
            if (desktopSearchInput && desktopSearchInput.value !== e.target.value) {
                desktopSearchInput.value = e.target.value;
            }
            performSearch(e.target.value);
        });
    }

    // Initial render of pagination (showing first 10)
    renderPagination();

    // --- 4. Modals (Add Customer & View Details) ---
    const addCustomerModal = document.getElementById('addCustomerModal');
    const btnAddCustomer = document.getElementById('btnAddCustomer');
    const closeAddModalBtn = document.getElementById('closeAddModalBtn');
    const cancelAddModalBtn = document.getElementById('cancelAddModalBtn');
    const addCustomerForm = document.getElementById('addCustomerForm');
    const mobileRegisterCustomerBtn = document.getElementById('mobileRegisterCustomerBtn');

    function openAddModal() {
        if (addCustomerModal) {
            addCustomerModal.classList.add('active');
            addCustomerModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeAddModal() {
        if (addCustomerModal) {
            addCustomerModal.classList.remove('active');
            addCustomerModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    const mobileBtnAddCustomer = document.getElementById('mobileBtnAddCustomer');
    const mobileRegBackBtn = document.getElementById('mobileRegBackBtn');
    const mobileRegMenuBtn = document.getElementById('mobileRegMenuBtn');
    const desktopRegisterCustomerBtn = document.getElementById('desktopRegisterCustomerBtn');

    if (btnAddCustomer) btnAddCustomer.addEventListener('click', openAddModal);
    if (mobileBtnAddCustomer) mobileBtnAddCustomer.addEventListener('click', openAddModal);
    if (desktopRegisterCustomerBtn) {
        desktopRegisterCustomerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openAddModal();
        });
    }
    const cancelAddBtn = document.getElementById('cancelAddBtn');
    if (closeAddModalBtn) closeAddModalBtn.addEventListener('click', closeAddModal);
    if (cancelAddModalBtn) cancelAddModalBtn.addEventListener('click', closeAddModal);
    if (cancelAddBtn) cancelAddBtn.addEventListener('click', closeAddModal);
    if (mobileRegBackBtn) mobileRegBackBtn.addEventListener('click', closeAddModal);
    if (mobileRegMenuBtn) mobileRegMenuBtn.addEventListener('click', openFullscreenMenu);

    if (mobileRegisterCustomerBtn) {
        mobileRegisterCustomerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeFullscreenMenu();
            openAddModal();
        });
    }

    // Auto-open Register Customer modal if URL contains #register
    if (window.location.hash === '#register' || window.location.hash === '#register-customer' || window.location.search.includes('register')) {
        setTimeout(openAddModal, 80);
    }
    window.addEventListener('hashchange', () => {
        if (window.location.hash === '#register' || window.location.hash === '#register-customer') {
            openAddModal();
        }
    });

    // addCustomerForm is submitted natively to the Laravel backend route('business.customers.store')

    // Keyboard ESC listener
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeFullscreenMenu();
            closeAddModal();
            closeViewModal();
        }
    });

    // Dynamic Topbar Date
    const dateElement = document.querySelector('.topbar-date');
    if (dateElement) {
        const now = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
        dateElement.textContent = now.toLocaleDateString('en-US', options);
    }
});

// --- View Customer Modal Global Function ---
function openViewModal(name, phone, email, status, contactPerson = '', address = '') {
    const modal = document.getElementById('viewCustomerModal');
    if (!modal) return;

    const nameElem = document.getElementById('viewModalName');
    const phoneElem = document.getElementById('viewModalPhone');
    const emailElem = document.getElementById('viewModalEmail');
    const badgeElem = document.getElementById('viewModalStatusBadge');
    const avatarElem = document.getElementById('viewModalAvatar');

    if (nameElem) nameElem.textContent = name;
    if (phoneElem) phoneElem.textContent = phone;
    if (emailElem) emailElem.textContent = email;

    if (badgeElem) {
        badgeElem.textContent = status;
        badgeElem.className = `status-pill ${(status || 'customer').toLowerCase()}`;
    }

    if (avatarElem) {
        const parts = name.split(' ');
        const initials = parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : name.substring(0, 2).toUpperCase();
        avatarElem.textContent = initials;
        const badgeClass = (status || 'customer').toLowerCase();
        avatarElem.className = `cust-avatar avatar-${badgeClass === 'customer' ? 'cust' : badgeClass}`;
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    const closeBtn = document.getElementById('closeViewModalBtn');
    const closeBtn2 = document.getElementById('closeViewModalBtn2');

    if (closeBtn) closeBtn.onclick = closeViewModal;
    if (closeBtn2) closeBtn2.onclick = closeViewModal;
}

function closeViewModal() {
    const modal = document.getElementById('viewCustomerModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// --- Toast Notification Utility ---
function showToastNotification(message) {
    let toast = document.getElementById('custToastNotification');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'custToastNotification';
        toast.style.cssText = `
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: #009688;
            color: #FFFFFF;
            padding: 14px 22px;
            border-radius: 12px;
            font-family: var(--font-sans, sans-serif);
            font-size: 0.92rem;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0, 150, 136, 0.4);
            z-index: 999999;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        `;
        document.body.appendChild(toast);
    }

    toast.innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span>${message}</span>
    `;

    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
    }, 3500);
}

