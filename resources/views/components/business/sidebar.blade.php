<aside class="desktop-sidebar dash-sidebar" aria-label="Business Owner Navigation">
    <div class="sidebar-brand dash-sidebar-header">
        <a href="{{ route('business.dashboard') }}" class="dash-logo-link" style="display: flex; align-items: center; text-decoration: none;">
            <div style="position: relative; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 9999px; border: 2px solid #E7C46A; background: #16436E; padding: 2px; margin-right: 10px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.25);">
                <img src="{{ asset('favicon-192x192.png') }}" alt="PIOS" style="width: 100%; height: 100%; border-radius: 9999px; object-fit: contain;">
            </div>
            <div style="display: flex; flex-direction: column;">
                <span style="font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.3rem; line-height: 1; color: #FFFFFF; letter-spacing: 0.5px;">pios</span>
                <span style="font-family: 'Inter', sans-serif; font-size: 8px; font-weight: 700; letter-spacing: 1.2px; color: #E7C46A; text-transform: uppercase; margin-top: 2px;">BizGrowth Engine</span>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav dash-nav-list">
        <a href="{{ route('business.dashboard') }}" class="sidebar-item dash-nav-item {{ request()->routeIs('business.dashboard') || request()->is('business', 'dashboard', 'business/dashboard') ? 'active' : '' }}">
            <svg class="sidebar-icon dash-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('business.customers.index') }}" class="sidebar-item dash-nav-item {{ request()->routeIs('business.customers.*') || request()->is('customers*', 'business/customers*') ? 'active' : '' }}">
            <svg class="sidebar-icon dash-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span>Customer</span>
        </a>

        <a href="{{ route('business.products.index') }}" class="sidebar-item dash-nav-item {{ request()->routeIs('business.products.*') || request()->is('products*', 'business/products*') ? 'active' : '' }}">
            <svg class="sidebar-icon dash-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            <span>Products</span>
        </a>

        <a href="{{ route('business.transactions.index') }}" class="sidebar-item dash-nav-item {{ request()->routeIs('business.transactions.*') || request()->is('transactions*', 'business/transactions*', 'business/incoming-orders*') ? 'active' : '' }}">
            <svg class="sidebar-icon dash-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <span>Transaction</span>
        </a>

        <a href="{{ route('business.fees.index') }}" class="sidebar-item dash-nav-item {{ request()->routeIs('business.fees.*') || request()->is('fees*', 'business/fees*') ? 'active' : '' }}">
            <svg class="sidebar-icon dash-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            <span>Fees</span>
        </a>

        <a href="{{ route('business.reports.index') }}" class="sidebar-item dash-nav-item {{ request()->routeIs('business.reports.*') || request()->is('reports*', 'business/reports*', 'business/business-sales-report*') ? 'active' : '' }}">
            <svg class="sidebar-icon dash-nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <span>Reports</span>
        </a>
    </nav>

    <div class="sidebar-footer dash-sidebar-footer">
        <div class="sidebar-user dash-user-profile">
            <img src="{{ asset('assets/images/lenny_avatar.png') }}" alt="{{ auth()->user()?->name ?? 'User' }}" class="user-avatar-img" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->businessOwner?->applicant_name ?? auth()->user()?->name ?? 'BO') }}&background=0D9488&color=fff';">
            <div class="user-profile-info">
                <span class="user-name-text">{{ auth()->user()?->businessOwner?->applicant_name ?? auth()->user()?->name ?? 'Diego Robel' }}</span>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="sidebar-logout-btn" title="Sign out" aria-label="Sign out">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </button>
        </form>
    </div>
</aside>
