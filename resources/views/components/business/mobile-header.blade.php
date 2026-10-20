@props(['title' => 'Dashboard', 'backUrl' => null, 'customHeaderClass' => 'mobile-dash-header'])

<header class="{{ $customHeaderClass }}">
    <div class="mobile-dash-left mobile-header-left">
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="back-btn mobile-back-btn" aria-label="Back">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>
        @else
            <a href="{{ route('business.dashboard') }}" class="back-btn mobile-back-btn" aria-label="Back to Dashboard">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>
        @endif
        <span class="mobile-dash-title mobile-header-title">{{ $title }}</span>
    </div>
    <button class="mobile-menu-toggle-btn mobile-hamburger-btn" id="mobileMenuToggleBtn" aria-label="Toggle Fullscreen Menu" aria-expanded="false" onclick="window.togglePiosMenu(event)">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
</header>
