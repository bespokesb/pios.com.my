<script src="{{ asset('assets/js/dashboard.js') }}?v={{ file_exists(public_path('assets/js/dashboard.js')) ? filemtime(public_path('assets/js/dashboard.js')) : time() }}"></script>
<script>
    (function () {
        const toggleBtn = document.getElementById('mobileMenuToggleBtn');
        if (toggleBtn) {
            toggleBtn.onclick = function (e) {
                if (typeof window.togglePiosMenu === 'function') {
                    window.togglePiosMenu(e);
                }
            };
        }
        const closeBtn = document.getElementById('fsMenuCloseBtn');
        if (closeBtn) {
            closeBtn.onclick = function (e) {
                if (typeof window.closePiosMenu === 'function') {
                    window.closePiosMenu(e);
                }
            };
        }
    })();
</script>

<!-- PWA Service Worker Registration -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js')
                .then(function (reg) { console.log('PIOS PWA registered:', reg.scope); })
                .catch(function (err) { console.log('PIOS PWA registration failed:', err); });
        });
    }
</script>
