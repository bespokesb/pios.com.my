<div class="fullscreen-menu" id="fullscreenMenu" aria-hidden="true">
    <div class="fs-menu-header">
        <a href="{{ route('business.dashboard') }}" class="fs-logo-link" style="display: flex; align-items: center; text-decoration: none;">
            <div style="position: relative; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 9999px; border: 2px solid #E7C46A; background: #16436E; padding: 2px; margin-right: 10px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" style="width: 100%; height: 100%; border-radius: 9999px; object-fit: contain;">
            </div>
            <div style="display: flex; flex-direction: column;">
                <span style="font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.25rem; line-height: 1; color: #FFFFFF; letter-spacing: 0.5px;">pios</span>
                <span style="font-family: 'Inter', sans-serif; font-size: 8px; font-weight: 700; letter-spacing: 1.2px; color: #E7C46A; text-transform: uppercase; margin-top: 2px;">BizGrowth Engine</span>
            </div>
        </a>
        <button class="fs-menu-close-btn" id="fsMenuCloseBtn" aria-label="Close Menu" onclick="window.closePiosMenu(event)">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <nav class="fs-nav-list">
        <a href="{{ route('business.dashboard') }}" class="fs-nav-item {{ request()->routeIs('business.dashboard') || request()->is('business', 'dashboard', 'business/dashboard') ? 'active' : '' }}">
            <svg class="fs-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('business.customers.index') }}" class="fs-nav-item {{ request()->routeIs('business.customers.*') || request()->is('customers*', 'business/customers*') ? 'active' : '' }}">
            <svg class="fs-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span>Customer</span>
        </a>

        <a href="{{ route('business.products.index') }}" class="fs-nav-item {{ request()->routeIs('business.products.*') || request()->is('products*', 'business/products*') ? 'active' : '' }}">
            <svg class="fs-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            <span>Products</span>
        </a>

        <a href="{{ route('business.transactions.index') }}" class="fs-nav-item {{ request()->routeIs('business.transactions.*') || request()->is('transactions*', 'business/transactions*', 'business/incoming-orders*') ? 'active' : '' }}">
            <svg class="fs-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <span>Transaction</span>
        </a>

        <a href="{{ route('business.fees.index') }}" class="fs-nav-item {{ request()->routeIs('business.fees.*') || request()->is('fees*', 'business/fees*') ? 'active' : '' }}">
            <svg class="fs-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span>Fees</span>
        </a>

        <a href="{{ route('business.reports.index') }}" class="fs-nav-item {{ request()->routeIs('business.reports.*') || request()->is('reports*', 'business/reports*', 'business/business-sales-report*') ? 'active' : '' }}">
            <svg class="fs-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span>Reports</span>
        </a>
    </nav>

    <div class="fs-menu-footer">
        <div class="fs-darkmode-row">
            <div class="fs-darkmode-label">
                <svg class="fs-icon-moon moon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <svg class="fs-icon-sun sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <span>Dark mode</span>
            </div>
            <label class="fs-switch">
                <input type="checkbox" id="fsDarkModeToggle">
                <span class="fs-slider"></span>
            </label>
        </div>

        <div class="fs-user-profile fs-user-card" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div class="fs-user-left" style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0;">
                <img src="{{ asset('assets/images/lenny_avatar.png') }}" alt="{{ auth()->user()?->name ?? 'User' }}" class="fs-user-avatar fs-avatar-img" style="width: 40px; height: 40px; min-width: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255, 255, 255, 0.25); display: block;" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->businessOwner?->applicant_name ?? auth()->user()?->name ?? 'BO') }}&background=0D9488&color=fff';">
                <div class="fs-user-info" style="flex: 1; min-width: 0;">
                    <span class="fs-user-name" style="font-size: 1rem; font-weight: 600; color: #FFFFFF; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">{{ auth()->user()?->businessOwner?->applicant_name ?? auth()->user()?->name ?? 'Diego Robel' }}</span>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="fs-logout-form" style="display: inline-flex; align-items: center; margin: 0; padding: 0; flex-shrink: 0;">
                @csrf
                <button type="submit" class="fs-logout-btn" aria-label="Sign out" style="background: transparent; border: none; color: rgba(255, 255, 255, 0.85); display: inline-flex; align-items: center; justify-content: center; padding: 8px; border-radius: 8px; cursor: pointer;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
