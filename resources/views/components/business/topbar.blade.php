@props(['title' => 'Dashboard', 'subtitle' => 'Welcome back to your business performance overview.', 'topbarClass' => 'dash-topbar'])

<div class="{{ $topbarClass }}">
    <div class="topbar-left">
        <h1 class="page-title">{{ $title }}</h1>
        <p class="page-subtitle">{{ $subtitle }}</p>
    </div>
    <div class="topbar-right">
        <span class="topbar-date" id="topbarDate">{{ now()->format('l, M d, Y') }}</span>
        <span class="status-pill status-active">
            <span class="status-dot"></span>
            System Active
        </span>
        <button class="theme-toggle-btn" id="desktopThemeToggleBtn" onclick="window.togglePiosTheme(event)" aria-label="Toggle Dark / Light Theme" title="Toggle Dark / Light Theme">
            <!-- Moon icon (Active in light mode) -->
            <svg class="theme-icon-moon moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
            <!-- Sun icon (Active in dark mode) -->
            <svg class="theme-icon-sun sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
        <button class="topbar-icon-btn" aria-label="Notifications">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="notification-indicator"></span>
        </button>
    </div>
</div>
