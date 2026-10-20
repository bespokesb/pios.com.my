/**
 * PIOS BizGrowth Engine | Dashboard Interaction Logic
 */

(function () {
    window.openPiosMenu = function (e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const fullscreenMenu = document.getElementById('fullscreenMenu');
        const mobileMenuToggleBtn = document.getElementById('mobileMenuToggleBtn');
        if (fullscreenMenu) {
            fullscreenMenu.classList.add('active', 'open');
            fullscreenMenu.style.setProperty('display', 'flex', 'important');
            fullscreenMenu.style.setProperty('opacity', '1', 'important');
            fullscreenMenu.style.setProperty('pointer-events', 'auto', 'important');
            fullscreenMenu.style.setProperty('transform', 'none', 'important');
            fullscreenMenu.style.setProperty('visibility', 'visible', 'important');
            fullscreenMenu.style.setProperty('z-index', '9999999', 'important');
            fullscreenMenu.setAttribute('aria-hidden', 'false');
            if (mobileMenuToggleBtn) mobileMenuToggleBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closePiosMenu = function (e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const fullscreenMenu = document.getElementById('fullscreenMenu');
        const mobileMenuToggleBtn = document.getElementById('mobileMenuToggleBtn');
        if (fullscreenMenu) {
            fullscreenMenu.classList.remove('active', 'open');
            fullscreenMenu.style.setProperty('display', 'none', 'important');
            fullscreenMenu.style.setProperty('opacity', '0', 'important');
            fullscreenMenu.style.setProperty('pointer-events', 'none', 'important');
            fullscreenMenu.style.setProperty('transform', 'translateY(-100%)', 'important');
            fullscreenMenu.setAttribute('aria-hidden', 'true');
            if (mobileMenuToggleBtn) mobileMenuToggleBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
    };

    window.togglePiosMenu = function (e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const fullscreenMenu = document.getElementById('fullscreenMenu');
        if (!fullscreenMenu) return;

        const isOpen = fullscreenMenu.classList.contains('active') ||
            fullscreenMenu.classList.contains('open') ||
            fullscreenMenu.style.display === 'flex';

        if (isOpen) {
            window.closePiosMenu(e);
        } else {
            window.openPiosMenu(e);
        }
    };

    function attachListeners() {
        const toggleBtn = document.getElementById('mobileMenuToggleBtn');
        if (toggleBtn) {
            toggleBtn.onclick = function (e) {
                window.togglePiosMenu(e);
            };
        }

        const closeBtn = document.getElementById('fsMenuCloseBtn');
        if (closeBtn) {
            closeBtn.onclick = function (e) {
                window.closePiosMenu(e);
            };
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            const fullscreenMenu = document.getElementById('fullscreenMenu');
            if (e.key === 'Escape' && fullscreenMenu && fullscreenMenu.classList.contains('active')) {
                window.closePiosMenu(e);
            }
        });

        // Dark / Light mode is handled globally by window.togglePiosTheme in layouts/business.blade.php
        function setDarkMode(isDark) {
            if (typeof window.applyPiosTheme === 'function') {
                window.applyPiosTheme(isDark);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachListeners);
    } else {
        attachListeners();
    }
})();
