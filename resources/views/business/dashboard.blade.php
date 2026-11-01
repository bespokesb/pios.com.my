<div>
    <!-- Desktop Header Bar -->
    <div class="dash-topbar">
        <div class="topbar-left">
            <h1 class="dash-heading">Dashboard</h1>
            <p class="dash-subheading">Your customers, products, promotions and transactions at a glance</p>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="topbarDate">{{ now()->format('D, M d, Y') }}</span>
            <div class="system-status-badge">
                <span class="status-dot"></span>
                <span>Business Owner</span>
            </div>
            <button class="theme-toggle-btn" id="desktopThemeToggleBtn" onclick="window.togglePiosTheme(event)" aria-label="Toggle Dark / Light Theme" title="Toggle Dark / Light Theme">
                <svg class="theme-icon-moon moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
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
            <button class="notif-bell-btn" aria-label="Notifications" title="Notifications">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span class="notif-badge-dot"></span>
            </button>
        </div>
    </div>

    <!-- 6 Metric / KPI Cards (3x2 Grid on Desktop) -->
    <div class="kpi-grid" aria-label="Key Performance Indicators">

        <!-- Card 1: Total Customers -->
        <a href="{{ route('business.customers.index') }}" class="kpi-card" title="View all customers">
            <div class="kpi-card-header">
                <div class="kpi-icon-wrapper">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="#10B981" stroke-width="2.5" />
                        <circle cx="12" cy="12" r="4" fill="#10B981" />
                    </svg>
                </div>
                <span class="mtd-badge desktop-only">MTD</span>
            </div>
            <div class="kpi-value text-teal">{{ number_format($totalCustomers) }}</div>
            <div class="kpi-title">Total Customers</div>
            <div class="kpi-subtitle">Active client database</div>
        </a>

        <!-- Card 2: Prospects -->
        <a href="{{ route('business.customers.index', ['filter' => 'prospect']) }}" class="kpi-card" title="View prospects">
            <div class="kpi-card-header">
                <div class="kpi-icon-wrapper">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2.2">
                        <polygon points="12 2 21 7.5 21 16.5 12 22 3 16.5 3 7.5 12 2" />
                    </svg>
                </div>
                <span class="mtd-badge desktop-only">MTD</span>
            </div>
            <div class="kpi-value text-blue">{{ number_format($prospectsCount) }}</div>
            <div class="kpi-title">Prospects</div>
            <div class="kpi-subtitle">In sales conversion funnel</div>
        </a>

        <!-- Card 3: VIP Customers -->
        <a href="{{ route('business.customers.index', ['filter' => 'vip']) }}" class="kpi-card" title="View VIP customers">
            <div class="kpi-card-header">
                <div class="kpi-icon-wrapper">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#F59E0B">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                    </svg>
                </div>
                <span class="mtd-badge desktop-only">MTD</span>
            </div>
            <div class="kpi-value text-yellow">{{ number_format($vipCount) }}</div>
            <div class="kpi-title">VIP Customers</div>
            <div class="kpi-subtitle">Exclusive & Line-of-Sponsorship</div>
        </a>

        <!-- Card 4: Products -->
        <a href="{{ route('business.products.index') }}" class="kpi-card" title="View product catalog">
            <div class="kpi-card-header">
                <div class="kpi-icon-wrapper">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#A855F7">
                        <polygon points="12 2 22 9.5 18 21 6 21 2 9.5 12 2" />
                    </svg>
                </div>
                <span class="mtd-badge desktop-only">{{ $productsCount }}/20</span>
            </div>
            <div class="kpi-value text-purple">{{ number_format($productsCount) }} <span style="font-size: 0.95rem; font-weight: 500; color: #64748B;">/ 20</span></div>
            <div class="kpi-title">Products</div>
            <div class="kpi-subtitle">{{ $productsCount }} of 20 products used</div>
        </a>

        <!-- Card 5: Open Promotions -->
        <a href="{{ route('business.products.index') }}" class="kpi-card" title="View promotions">
            <div class="kpi-card-header">
                <div class="kpi-icon-wrapper">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2.2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </div>
                <span class="mtd-badge desktop-only">MTD</span>
            </div>
            <div class="kpi-value text-green">{{ number_format($promotionsCount) }}</div>
            <div class="kpi-title">Open Promotions</div>
            <div class="kpi-subtitle">Active campaigns running</div>
        </a>

        <!-- Card 6: Pending Transactions -->
        <a href="{{ route('business.transactions.index') }}" class="kpi-card" title="View transactions">
            <div class="kpi-card-header">
                <div class="kpi-icon-wrapper">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#F97316">
                        <path d="M12 2L2 12l10 10 10-10L12 2z" />
                    </svg>
                </div>
                <span class="mtd-badge desktop-only">MTD</span>
            </div>
            <div class="kpi-value text-orange">{{ number_format($openOrdersCount) }}</div>
            <div class="kpi-title">Pending Transactions</div>
            <div class="kpi-subtitle">Awaiting confirmation / delivery</div>
        </a>

    </div>

    <!-- Bottom Section: Customer Pipeline (Left) & Recent Activity (Right) -->
    <div class="dash-bottom-grid">

        <!-- Left Card: Customer Pipeline -->
        <section class="dash-card customer-pipeline-card" aria-label="Customer Pipeline">
            <div class="dash-card-header">
                <h2 class="dash-card-title">Customer Pipeline</h2>
                <a href="{{ route('business.customers.index') }}" class="pipeline-total-badge">{{ $totalCustomers }} Total</a>
            </div>

            <div class="pipeline-items-list">
                @foreach ($pipeline as $p)
                    <a href="{{ route('business.customers.index') }}" class="pipeline-item">
                        <div class="pipeline-item-label-row">
                            <span class="pipeline-stage-name">{{ $p['stage'] }}</span>
                            <span class="pipeline-stage-count">{{ $p['count'] }}</span>
                        </div>
                        <div class="pipeline-bar-track">
                            <div class="pipeline-bar-fill bar-{{ strtolower($p['stage']) }}" style="width: {{ $p['pct'] }}%;"></div>
                        </div>
                        <div class="pipeline-pct-label text-{{ $p['color'] }}">{{ $p['pct'] }}% of pipeline</div>
                    </a>
                @endforeach
            </div>
        </section>

        <!-- Right Card: Recent Activity (Desktop only in Figma) -->
        <section class="dash-card recent-activity-card desktop-only" aria-label="Recent Activity">
            <div class="dash-card-header">
                <h2 class="dash-card-title">Recent Activity</h2>
                <a href="{{ route('business.transactions.index') }}" class="activity-subtitle-badge">Recent updates</a>
            </div>

            <div class="activity-list">
                @forelse ($recentActivities as $act)
                    <a href="{{ route('business.transactions.index') }}" class="activity-row">
                        <span class="activity-time">{{ $act['time'] }}</span>
                        <div class="activity-info">
                            <div class="activity-main-text">{{ $act['title'] }}</div>
                            <div class="activity-sub-text">{{ $act['desc'] }}</div>
                        </div>
                        <span class="activity-badge {{ $act['badge_class'] }}">{{ $act['badge'] }}</span>
                    </a>
                @empty
                    <div class="activity-row">
                        <span class="activity-time">Today</span>
                        <div class="activity-info">
                            <div class="activity-main-text">No recent transactions recorded</div>
                            <div class="activity-sub-text">New incoming orders and customer updates will appear here</div>
                        </div>
                        <span class="activity-badge badge-order">Ready</span>
                    </div>
                @endforelse
            </div>
        </section>

    </div>

</div>
