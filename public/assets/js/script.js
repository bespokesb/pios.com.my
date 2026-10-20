/**
 * PIOS - BizGrowth Engine | Helper UI interactions (Toggle only, no form submit interception)
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- 1. Password Visibility Toggle ---
    const passwordInput = document.getElementById('password') || document.getElementById('passwordInput');
    const passwordToggleBtn = document.getElementById('passwordToggleBtn') || document.getElementById('boPasswordToggleBtn');
    const eyeShow = passwordToggleBtn ? passwordToggleBtn.querySelector('.eye-show') : null;
    const eyeHide = passwordToggleBtn ? passwordToggleBtn.querySelector('.eye-hide') : null;

    if (passwordToggleBtn && passwordInput) {
        passwordToggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            if (eyeShow && eyeHide) {
                if (isPassword) {
                    eyeShow.style.display = 'none';
                    eyeHide.style.display = 'block';
                    passwordToggleBtn.setAttribute('aria-label', 'Hide password');
                } else {
                    eyeShow.style.display = 'block';
                    eyeHide.style.display = 'none';
                    passwordToggleBtn.setAttribute('aria-label', 'Show password');
                }
            }
        });
    }

    // --- 2. Mobile Drawer Navigation Toggle ---
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mobileDrawer = document.getElementById('mobileDrawer');
    const drawerOverlay = document.getElementById('drawerOverlay');
    const drawerCloseBtn = document.getElementById('drawerCloseBtn');

    function openDrawer() {
        if (mobileDrawer && drawerOverlay) {
            mobileDrawer.classList.add('active');
            drawerOverlay.classList.add('active');
            if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeDrawer() {
        if (mobileDrawer && drawerOverlay) {
            mobileDrawer.classList.remove('active');
            drawerOverlay.classList.remove('active');
            if (hamburgerBtn) hamburgerBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
    }

    if (hamburgerBtn) hamburgerBtn.addEventListener('click', openDrawer);
    if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', closeDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileDrawer && mobileDrawer.classList.contains('active')) {
            closeDrawer();
        }
    });

    // --- 3. Dark Mode Toggle ---
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        if (localStorage.getItem('pios_theme') === 'dark' || document.body.classList.contains('dark-mode-active')) {
            darkModeToggle.checked = true;
            document.body.classList.add('dark-mode-active');
        }
        darkModeToggle.addEventListener('change', (e) => {
            if (e.target.checked) {
                document.body.classList.add('dark-mode-active');
                try { localStorage.setItem('pios_theme', 'dark'); } catch(e){}
            } else {
                document.body.classList.remove('dark-mode-active');
                try { localStorage.setItem('pios_theme', 'light'); } catch(e){}
            }
        });
    }
});
