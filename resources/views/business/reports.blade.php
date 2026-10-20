<x-business-layout title="PIOS - Business Reports" headerTitle="Reports" bodyClass="reports-body" mainClass="reports-main" mobileHeaderClass="mobile-reports-header">

    <!-- Desktop Header Bar -->
    <div class="reports-topbar">
        <div class="topbar-left">
            <h1 class="reports-heading">Reports & Analytics</h1>
            <p class="reports-subheading">Sales performance, revenue trends, customer growth and top product analysis</p>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="topbarDate">{{ now()->format('D, M d, Y') }}</span>
            <div class="system-status-badge">
                <span class="status-dot"></span>
                <span>Business Owner</span>
            </div>
            <button class="theme-toggle-btn" id="desktopThemeToggleBtn" onclick="window.togglePiosTheme(event)" aria-label="Toggle Dark / Light Theme" title="Toggle Dark / Light Theme">
                <svg class="theme-icon-moon moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <svg class="theme-icon-sun sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

    <!-- Metrics Overview -->
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
        <div class="kpi-card">
            <div class="kpi-value text-teal">RM {{ number_format((float)$totalRevenue, 2) }}</div>
            <div class="kpi-title">Gross Revenue</div>
            <div class="kpi-subtitle">Completed sales volume</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value text-blue">{{ number_format($totalOrders) }}</div>
            <div class="kpi-title">Orders Processed</div>
            <div class="kpi-subtitle">Customer checkouts</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value text-purple">RM {{ number_format((float)$avgOrderValue, 2) }}</div>
            <div class="kpi-title">Average Order Value</div>
            <div class="kpi-subtitle">Per transaction average</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value text-green">{{ number_format($totalCustomers) }}</div>
            <div class="kpi-title">Customer Base</div>
            <div class="kpi-subtitle">+{{ $newCustomersThisMonth }} this month</div>
        </div>
    </div>

    <!-- Desktop Top Products Performance Table -->
    <div class="customer-table-card report-desktop-table">
        <div class="dash-card-header" style="padding: 20px 24px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
            <h2 class="dash-card-title">Top Performing Products</h2>
            <span class="text-sm font-semibold text-teal-600" style="color: #0D9488;">Sales Volume</span>
        </div>
        <div class="table-responsive-wrapper">
            <table class="customer-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Units Sold</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topProducts as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product?->name ?? 'Product' }}</strong>
                                @if ($item->product?->product_no)
                                    <span class="text-xs text-slate-400">#{{ $item->product->product_no }}</span>
                                @endif
                            </td>
                            <td><span class="status-pill customer">{{ number_format($item->total_qty) }} units</span></td>
                            <td><strong style="color: #0D9488;">RM {{ number_format((float)$item->total_sales, 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 32px; color: #64748B;">
                                No sales data recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Card List (< 992px) — matches business/transactions tx-card layout -->
    <div class="report-mobile-list">
        @forelse ($topProducts as $item)
            <div class="report-mobile-card">
                <div class="report-card-top">
                    <span class="report-card-label">Top Product</span>
                    <span class="status-pill customer">{{ number_format($item->total_qty) }} units</span>
                </div>
                <div class="report-card-middle">
                    <span class="report-card-name">{{ $item->product?->name ?? 'Product' }}</span>
                    <span class="report-card-revenue">RM {{ number_format((float)$item->total_sales, 2) }}</span>
                </div>
                <div class="report-card-bottom">
                    @if ($item->product?->product_no)
                        <span class="report-card-id">#{{ $item->product->product_no }}</span>
                    @else
                        <span class="report-card-id">—</span>
                    @endif
                    <span class="report-card-subtitle">Sales Volume</span>
                </div>
            </div>
        @empty
            <div class="report-mobile-card" style="text-align: center; color: #64748B; padding: 24px;">
                No sales data recorded yet.
            </div>
        @endforelse
    </div>

    @push('scripts')
    <script src="{{ asset('assets/js/reports.js') }}?v={{ file_exists(public_path('assets/js/reports.js')) ? filemtime(public_path('assets/js/reports.js')) : time() }}"></script>
    @endpush

</x-business-layout>
