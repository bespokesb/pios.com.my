/**
 * PIOS BizGrowth Engine | Reports Interactive Script (reports.js)
 * Supports Mobile Menu Toggle, Theme Toggle (Sync with localStorage & Section 8 Standard),
 * Dynamic Date, and Interactive Chart Data Exploration.
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
       3. Dynamic Date in Desktop Topbar
       ========================================================================== */
    const dateElements = document.querySelectorAll('.topbar-date');
    if (dateElements.length > 0) {
        const today = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
        const formattedDate = today.toLocaleDateString('en-US', options);
        dateElements.forEach(el => el.textContent = formattedDate);
    }


    /* ==========================================================================
       4. Interactive Bar Chart Exploration (Hover / Click Tooltip)
       ========================================================================== */
    const chartCols = document.querySelectorAll('.chart-col');
    const ctdMonth = document.getElementById('ctdMonth');
    const ctdVal = document.getElementById('ctdVal');
    const ctdOrders = document.getElementById('ctdOrders');

    chartCols.forEach(col => {
        function activateColumn() {
            chartCols.forEach(c => {
                c.classList.remove('active');
                const bar = c.querySelector('.chart-bar');
                if (bar) bar.classList.remove('active-bar');
            });

            col.classList.add('active');
            const bar = col.querySelector('.chart-bar');
            if (bar) bar.classList.add('active-bar');

            const month = col.getAttribute('data-month');
            const val = col.getAttribute('data-val');
            const orders = col.getAttribute('data-orders');

            if (ctdMonth) ctdMonth.textContent = month;
            if (ctdVal) ctdVal.textContent = val;
            if (ctdOrders) ctdOrders.textContent = `(${orders} orders)`;
        }

        col.addEventListener('mouseenter', activateColumn);
        col.addEventListener('click', activateColumn);
    });

});
