<x-business-layout title="PIOS - Customer Management" headerTitle="Customer" bodyClass="customer-body" mainClass="cust-main" mobileHeaderClass="mobile-customer-header">

    <!-- Desktop Header Bar -->
    <div class="cust-topbar">
        <div class="topbar-left">
            <h1 class="cust-heading">Customer</h1>
            <p class="cust-subheading">Manage your contacts, prospects, customers and VIPs</p>
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

    <!-- Feedback Messages -->
    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- DESKTOP VIEW: Customer Data Table -->
    <div class="desktop-customer-view">

        <!-- Action Bar: Search & Add Customer (Desktop Only) -->
        <div class="cust-action-bar">
            <form method="GET" action="{{ route('business.customers.index') }}" class="cust-search-wrapper" style="width: 100%;">
                <svg class="cust-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="search" id="desktopSearchInput" class="cust-search-input"
                    placeholder="Search customer name, phone, or email..." value="{{ $search }}" autocomplete="off">
            </form>
            <button type="button" class="btn-add-customer" id="btnAddCustomer" aria-label="Add Customer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Add Customer</span>
            </button>
        </div>

        <!-- Filter Tabs -->
        <div class="cust-tabs-bar" style="display: flex; gap: 8px; margin-bottom: 16px;">
            <a href="{{ route('business.customers.index', ['search' => $search]) }}" class="tab-btn {{ $filter === 'all' ? 'active' : '' }}" style="padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 0.88rem; font-weight: 600; {{ $filter === 'all' ? 'background: #0D9488; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
                All ({{ $totalCount }})
            </a>
            <a href="{{ route('business.customers.index', ['filter' => 'vip', 'search' => $search]) }}" class="tab-btn {{ $filter === 'vip' ? 'active' : '' }}" style="padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 0.88rem; font-weight: 600; {{ $filter === 'vip' ? 'background: #D97706; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
                VIP ({{ $vipCount }})
            </a>
            <a href="{{ route('business.customers.index', ['filter' => 'customer', 'search' => $search]) }}" class="tab-btn {{ $filter === 'customer' ? 'active' : '' }}" style="padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 0.88rem; font-weight: 600; {{ $filter === 'customer' ? 'background: #0D9488; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
                Customer ({{ $customerCount }})
            </a>
            <a href="{{ route('business.customers.index', ['filter' => 'prospect', 'search' => $search]) }}" class="tab-btn {{ $filter === 'prospect' ? 'active' : '' }}" style="padding: 6px 14px; border-radius: 8px; text-decoration: none; font-size: 0.88rem; font-weight: 600; {{ $filter === 'prospect' ? 'background: #134C49; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
                Prospects ({{ $prospectCount }})
            </a>
        </div>

        <div class="customer-table-card">
            <div class="table-responsive-wrapper">
                <table class="customer-table" id="customerTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>HP No.</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        @forelse ($customers as $c)
                            @php
                                $initials = strtoupper(substr($c->name, 0, 2));
                                $statusLabel = $c->is_vip ? 'VIP' : ($c->category?->label() ?? 'Customer');
                                $statusClass = $c->is_vip ? 'vip' : ($c->category === \App\Enums\CustomerCategory::Prospect ? 'prospect' : 'customer');
                                $waUrl = 'https://wa.me/' . preg_replace('/[^0-9]/', '', (string)$c->hp_no);
                            @endphp
                            <tr data-name="{{ $c->name }}" data-phone="{{ $c->hp_no }}" data-email="{{ $c->email ?? '-' }}" data-status="{{ $statusLabel }}">
                                <td>
                                    <div class="customer-name-cell">
                                        <div class="cust-avatar avatar-{{ $statusClass }}">{{ $initials }}</div>
                                        <div class="customer-details">
                                            <span class="customer-primary-name">
                                                @if ($c->is_vip) <span class="vip-star">★</span> @endif
                                                {{ $c->name }}
                                            </span>
                                            @if ($c->customer_no)
                                                <span class="text-xs text-slate-400">#{{ $c->customer_no }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ \App\Support\Phone::toTel($c->hp_no) ?? $c->hp_no }}</td>
                                <td>{{ $c->email ?? '-' }}</td>
                                <td><span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td>
                                    <div class="action-buttons-group">
                                        <a href="{{ $waUrl }}" target="_blank" class="btn-action-wa" title="WhatsApp">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                                            </svg>
                                            <span>WA</span>
                                        </a>
                                        <a href="tel:{{ $c->hp_no }}" class="btn-action-call" title="Call">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                            </svg>
                                            <span>Call</span>
                                        </a>
                                        <button class="btn-action-view" onclick="openViewModal('{{ addslashes($c->name) }}', '{{ addslashes(\App\Support\Phone::toTel($c->hp_no) ?? $c->hp_no) }}', '{{ addslashes($c->email ?? '-') }}', '{{ $statusLabel }}', '{{ addslashes($c->abo_no ?? '-') }}', '{{ addslashes($c->address ?? '-') }}')">View</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-customer-row">
                                <td colspan="5" style="text-align: center; padding: 32px; color: #64748B;">
                                    No customers found. Click <strong>+ Add Customer</strong> to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Desktop Table Footer with Pagination (10 per page) -->
            <div class="customer-table-footer">
                <div class="table-pagination-info" id="desktopTableCount">
                    Showing 1 to {{ min(10, count($customers)) }} of {{ count($customers) }} customers
                </div>
                <div class="table-pagination-controls" id="tablePaginationControls"></div>
            </div>
        </div>
    </div>

    <!-- MOBILE VIEW: Customer List Cards -->
    <div class="mobile-customer-view">
        <div class="mobile-action-bar">
            <form method="GET" action="{{ route('business.customers.index') }}" class="mobile-search-box">
                <svg class="mobile-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="search" id="mobileSearchInput" class="mobile-search-input" placeholder="Search customer name, phone..." value="{{ $search }}" autocomplete="off">
            </form>
            <button type="button" class="mobile-btn-add-cust" id="mobileBtnAddCustomer" aria-label="Add Customer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Add</span>
            </button>
        </div>

        <!-- Filter Tabs for Mobile -->
        <div class="mobile-filter-tabs" style="display: flex; gap: 8px; margin-bottom: 16px; overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch;">
            <a href="{{ route('business.customers.index', ['search' => $search]) }}" style="padding: 7px 14px; border-radius: 10px; text-decoration: none; font-size: 0.84rem; font-weight: 600; white-space: nowrap; {{ $filter === 'all' ? 'background: #134C49; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
                All ({{ $totalCount }})
            </a>
            <a href="{{ route('business.customers.index', ['filter' => 'vip', 'search' => $search]) }}" style="padding: 7px 14px; border-radius: 10px; text-decoration: none; font-size: 0.84rem; font-weight: 600; white-space: nowrap; {{ $filter === 'vip' ? 'background: #D97706; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
                VIP ({{ $vipCount }})
            </a>
            <a href="{{ route('business.customers.index', ['filter' => 'customer', 'search' => $search]) }}" style="padding: 7px 14px; border-radius: 10px; text-decoration: none; font-size: 0.84rem; font-weight: 600; white-space: nowrap; {{ $filter === 'customer' ? 'background: #0D9488; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
                Customer ({{ $customerCount }})
            </a>
            <a href="{{ route('business.customers.index', ['filter' => 'prospect', 'search' => $search]) }}" style="padding: 7px 14px; border-radius: 10px; text-decoration: none; font-size: 0.84rem; font-weight: 600; white-space: nowrap; {{ $filter === 'prospect' ? 'background: #134C49; color: #fff;' : 'background: #fff; color: #475569; border: 1px solid #E2E8F0;' }}">
                Prospects ({{ $prospectCount }})
            </a>
        </div>

        <div class="mobile-customer-cards-list mobile-card-list" style="padding-bottom: 24px;">
            @forelse ($customers as $c)
                @php
                    $initials = strtoupper(substr($c->name, 0, 2));
                    $statusLabel = $c->is_vip ? 'VIP' : ($c->category?->label() ?? 'Customer');
                    $statusClass = $c->is_vip ? 'vip' : ($c->category === \App\Enums\CustomerCategory::Prospect ? 'prospect' : 'customer');
                    $waUrl = 'https://wa.me/' . preg_replace('/[^0-9]/', '', (string)$c->hp_no);
                @endphp
                <div class="mobile-cust-card" data-name="{{ $c->name }}" data-phone="{{ $c->hp_no }}" data-email="{{ $c->email ?? '-' }}" data-status="{{ $statusLabel }}">
                    <div class="mobile-card-top">
                        <div class="cust-avatar avatar-{{ $statusClass }}">{{ $initials }}</div>
                        <div class="mobile-card-info" style="flex: 1; min-width: 0;">
                            <span class="mobile-cust-name" style="display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                @if ($c->is_vip) <span class="vip-star">★</span> @endif
                                {{ $c->name }}
                            </span>
                            <span class="mobile-cust-phone">{{ \App\Support\Phone::toTel($c->hp_no) ?? $c->hp_no }}</span>
                            @if ($c->customer_no)
                                <span class="text-xs text-slate-400" style="display: block; margin-top: 2px;">#{{ $c->customer_no }}</span>
                            @endif
                        </div>
                        <span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="mobile-actions-grid mobile-card-actions">
                        <a href="{{ $waUrl }}" target="_blank" class="mobile-btn mobile-btn-wa" aria-label="WhatsApp {{ $c->name }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                            </svg>
                            <span>WA</span>
                        </a>
                        <a href="tel:{{ $c->hp_no }}" class="mobile-btn mobile-btn-call" aria-label="Call {{ $c->name }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span>Call</span>
                        </a>
                        <button type="button" class="mobile-btn mobile-btn-view" onclick="openViewModal('{{ addslashes($c->name) }}', '{{ addslashes(\App\Support\Phone::toTel($c->hp_no) ?? $c->hp_no) }}', '{{ addslashes($c->email ?? '-') }}', '{{ $statusLabel }}', '{{ addslashes($c->abo_no ?? '-') }}', '{{ addslashes($c->address ?? '-') }}')" aria-label="View details of {{ $c->name }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <span>View</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="empty-customer-card" style="padding: 24px; text-align: center; color: #64748B;">
                    No customers found. Click <strong>+ Add Customer</strong> to create one.
                </div>
            @endforelse
        </div>

        <!-- Mobile Pagination (10 per page) -->
        <div class="mobile-pagination-wrapper">
            <div class="mobile-pagination-info" id="mobileTableCount">
                Showing 1 to {{ min(10, count($customers)) }} of {{ count($customers) }} customers
            </div>
            <div class="mobile-pagination-controls" id="mobilePaginationControls"></div>
        </div>
    </div>

    <!-- Add Customer Modal (Register Customer) -->
    <div class="modal-overlay" id="addCustomerModal">
        <div class="modal-card modal-register-card">
            <!-- Mobile Header Bar -->
            <div class="mobile-reg-header">
                <div class="mobile-header-left">
                    <button type="button" class="mobile-reg-back-btn" id="mobileRegBackBtn" aria-label="Back">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <h2 class="mobile-reg-title">Register Customer</h2>
                </div>
                <button type="button" class="mobile-reg-menu-btn" id="mobileRegMenuBtn" aria-label="Close" onclick="closeAddModal()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Desktop Header -->
            <div class="modal-header desktop-modal-header">
                <h3 class="modal-title">Register Customer</h3>
                <button type="button" class="modal-close-btn" id="closeAddModalBtn" aria-label="Close modal">&times;</button>
            </div>

            <form action="{{ route('business.customers.store') }}" method="POST" id="addCustomerForm">
                @csrf

                @if ($errors->any())
                    <div style="margin: 16px 28px 0; padding: 12px 16px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #EF4444; font-size: 0.88rem;">
                        <ul style="margin: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="modal-body modal-grid-layout">
                    <!-- Left Column -->
                    <div class="modal-col modal-card-section">
                        <!-- Full Name -->
                        <div class="form-group">
                            <label class="form-label" for="customerNameInput">Full Name</label>
                            <input type="text" class="form-control" id="customerNameInput" name="name"
                                placeholder="Enter full name" required value="{{ old('name') }}" autocomplete="name">
                            @error('name')
                                <span style="color: #EF4444; font-size: 0.8rem; margin-top: 4px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Email Address -->
                        <div class="form-group">
                            <label class="form-label" for="customerEmailInput">Email Address</label>
                            <input type="email" class="form-control" id="customerEmailInput" name="email"
                                placeholder="contact@company.com" value="{{ old('email') }}" autocomplete="email">
                            @error('email')
                                <span style="color: #EF4444; font-size: 0.8rem; margin-top: 4px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Phone Number -->
                        <div class="form-group">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px;">
                                <label class="form-label" for="customerPhoneInput" style="margin-bottom: 0;">Phone Number</label>
                                <span style="font-size: 0.76rem; color: #64748B; font-weight: 600;">Format WhatsApp (+60)</span>
                            </div>
                            <div class="phone-input-group" id="phoneInputContainer" style="position: relative; display: flex; align-items: stretch; border: 1.5px solid #CBD5E1; border-radius: 8px; background: #FFFFFF; transition: all 0.2s ease; overflow: hidden;">
                                <div class="phone-prefix-badge" style="display: flex; align-items: center; gap: 6px; padding: 0 12px; background: #F8FAFC; border-right: 1.5px solid #E2E8F0; color: #1E293B; font-weight: 700; font-size: 0.95rem; user-select: none;">
                                    <span style="font-size: 1.15rem; line-height: 1;">🇲🇾</span>
                                    <span>+60</span>
                                </div>
                                <input type="tel" class="form-control phone-input-field" id="customerPhoneInput" name="hp_no"
                                    style="border: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 10px 14px; font-weight: 600; font-size: 0.95rem; color: #0F172A; width: 100%; letter-spacing: 0.5px; outline: none !important;"
                                    placeholder="12-345 6789" required
                                    value="{{ old('hp_no') ? \App\Support\Phone::national(old('hp_no')) : '' }}"
                                    autocomplete="tel">
                            </div>
                            <div id="phoneDuplicateMsg" style="display: none; font-size: 0.82rem; margin-top: 6px; font-weight: 600; line-height: 1.4;"></div>
                            @error('hp_no')
                                <span id="serverPhoneError" style="color: #EF4444; font-size: 0.82rem; margin-top: 6px; font-weight: 600; display: block;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="modal-col modal-card-section">
                        <!-- Address -->
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <div class="address-inputs-stack">
                                <input type="text" class="form-control" name="address"
                                    placeholder="Address line 1" value="{{ old('address') }}">
                                <input type="text" class="form-control" name="address_line_2"
                                    placeholder="Address line 2" value="{{ old('address_line_2') }}">
                                <input type="text" class="form-control" name="address_line_3"
                                    placeholder="Address line 3" value="{{ old('address_line_3') }}">
                            </div>
                        </div>

                        <!-- Postcode -->
                        <div class="form-group">
                            <label class="form-label" for="postcodeSelect">Postcode</label>
                            <div class="select-wrapper">
                                <select class="form-control form-select" name="poskod" id="postcodeSelect">
                                    <option value="">Select postcode</option>
                                </select>
                            </div>
                        </div>

                        <!-- City -->
                        <div class="form-group">
                            <label class="form-label" for="citySelect">City</label>
                            <div class="select-wrapper">
                                <select class="form-control form-select" name="bandar" id="citySelect">
                                    <option value="">Select city</option>
                                </select>
                            </div>
                        </div>

                        <!-- State -->
                        <div class="form-group">
                            <label class="form-label" for="stateSelect">State</label>
                            <div class="select-wrapper">
                                <select class="form-control form-select" name="negeri" id="stateSelect">
                                    <option value="">Select state</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer-divider"></div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" id="cancelAddBtn">Cancel</button>
                    <button type="submit" class="btn-modal-submit" id="submitCustomerBtn">Register Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Customer Modal -->
    <div class="modal-overlay" id="viewCustomerModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 16px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
        <div class="modal-card" style="background: #FFFFFF; border-radius: 16px; width: 100%; max-width: 500px; max-height: calc(100dvh - 32px); overflow-y: auto; margin: auto; box-shadow: 0 20px 50px rgba(15,23,42,0.18); padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <div>
                    <h3 id="viewCustName" style="font-size: 1.25rem; font-weight: 700; color: #0F172A; margin: 0;"></h3>
                    <span id="viewCustStatus" class="status-pill customer" style="margin-top: 6px; display: inline-block;"></span>
                </div>
                <button type="button" id="closeViewModalBtn" style="background: transparent; border: none; font-size: 1.5rem; color: #64748B; cursor: pointer;">&times;</button>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.95rem; color: #334155;">
                <div><strong>Phone:</strong> <span id="viewCustPhone"></span></div>
                <div><strong>Email:</strong> <span id="viewCustEmail"></span></div>
                <div><strong>ABO / VIP No:</strong> <span id="viewCustAbo"></span></div>
                <div><strong>Address:</strong> <span id="viewCustAddress"></span></div>
            </div>
            <div style="margin-top: 24px; text-align: right;">
                <button type="button" id="closeViewBtn2" style="padding: 8px 18px; border-radius: 8px; border: 1px solid #CBD5E1; background: #FFF; color: #475569; font-weight: 600; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>

    @push('scripts')
    @include('partials.malaysia-locations-script')
    <script src="{{ asset('assets/js/customer.js') }}?v={{ file_exists(public_path('assets/js/customer.js')) ? filemtime(public_path('assets/js/customer.js')) : time() }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addModal = document.getElementById('addCustomerModal');
            const viewModal = document.getElementById('viewCustomerModal');
            const btnAdd = document.getElementById('btnAddCustomer');
            const mobileBtnAddTop = document.getElementById('mobileBtnAddCustomer');
            const closeAdd = document.getElementById('closeAddModalBtn');
            const cancelAdd = document.getElementById('cancelAddBtn');
            const closeView = document.getElementById('closeViewModalBtn');
            const closeView2 = document.getElementById('closeViewBtn2');

            function openAddModal() {
                if (addModal) {
                    addModal.classList.add('active');
                    addModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            }
            function closeAddModal() {
                if (addModal) {
                    addModal.classList.remove('active');
                    addModal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            }

            window.openAddModal = openAddModal;
            window.closeAddModal = closeAddModal;

            if (btnAdd) btnAdd.addEventListener('click', openAddModal);
            if (mobileBtnAddTop) mobileBtnAddTop.addEventListener('click', openAddModal);
            if (closeAdd) closeAdd.addEventListener('click', closeAddModal);
            if (cancelAdd) cancelAdd.addEventListener('click', closeAddModal);

            function closeViewModal() {
                if (viewModal) viewModal.style.display = 'none';
                document.body.style.overflow = '';
            }

            if (closeView) closeView.addEventListener('click', closeViewModal);
            if (closeView2) closeView2.addEventListener('click', closeViewModal);
            if (viewModal) {
                viewModal.addEventListener('click', (e) => {
                    if (e.target === viewModal) closeViewModal();
                });
            }
            if (addModal) {
                addModal.addEventListener('click', (e) => {
                    if (e.target === addModal) closeAddModal();
                });
            }

            // --- Malaysia Location Synchronization (Shared Database Set) ---
            window.attachMalaysiaLocationSync({
                stateSelect: 'stateSelect',
                citySelect: 'citySelect',
                postcodeSelect: 'postcodeSelect',
                initialState: @json(old('negeri', '')),
                initialCity: @json(old('bandar', '')),
                initialPostcode: @json(old('poskod', ''))
            });

            // --- Live Phone Number Validation (+60 WhatsApp Format & Sufficiency) ---
            const phoneInput = document.getElementById('customerPhoneInput');
            const phoneInputContainer = document.getElementById('phoneInputContainer');
            const phoneDuplicateMsg = document.getElementById('phoneDuplicateMsg');
            const submitCustomerBtn = document.getElementById('submitCustomerBtn');
            const addCustomerForm = document.getElementById('addCustomerForm');
            let phoneCheckTimeout = null;
            let isPhoneValidAndAvailable = false;

            /**
             * Extract subscriber digits and validate Malaysian mobile sufficiency.
             * Malaysian mobile starts with 1 (010..019) and has 9-10 digits after +60.
             */
            function inspectSubscriberDigits(raw) {
                if (!raw) return { valid: false, reason: 'empty', digits: '' };

                // Strip non-digits
                let digits = (raw || '').replace(/\D/g, '');

                // Strip leading 60 if typed or pasted
                if (digits.startsWith('60')) {
                    digits = digits.substring(2);
                }
                // Strip leading 0 if typed or pasted
                if (digits.startsWith('0')) {
                    digits = digits.substring(1);
                }

                if (!digits) {
                    return { valid: false, reason: 'empty', digits: '' };
                }

                // Malaysian mobile numbers must start with 1 (prefixes: 10, 11, 12, 13, 14, 15, 16, 17, 18, 19)
                if (!digits.startsWith('1')) {
                    return {
                        valid: false,
                        reason: 'not_mobile',
                        digits: digits,
                        message: 'Nombor telefon bimbit mesti bermula dengan 1 (cth: 12, 13, 11, 19).'
                    };
                }

                // Minimum 9 digits (e.g. 12-345 6789 -> 9 digits, total with 60 is 11 digits)
                // For prefix 11, typically 10 digits (e.g. 11-1234 5678 -> 10 digits, total with 60 is 12 digits)
                const minDigits = digits.startsWith('11') ? 10 : 9;
                const maxDigits = 10;

                if (digits.length < minDigits) {
                    const remaining = minDigits - digits.length;
                    return {
                        valid: false,
                        reason: 'too_short',
                        digits: digits,
                        message: `Nombor telefon tidak mencukupi (perlu sekurang-kurangnya ${remaining} digit lagi, cth: 12-345 6789).`
                    };
                }

                if (digits.length > maxDigits) {
                    return {
                        valid: false,
                        reason: 'too_long',
                        digits: digits,
                        message: 'Nombor telefon melebihi had digit (maksimum 10 digit selepas +60).'
                    };
                }

                return {
                    valid: true,
                    digits: digits,
                    fullE164: '+60' + digits,
                    canonical: '60' + digits
                };
            }

            function setPhoneStatus(type, msg) {
                if (!phoneDuplicateMsg || !phoneInputContainer) return;

                if (type === 'valid') {
                    phoneDuplicateMsg.textContent = '✓ ' + (msg || 'Nombor telefon sah dan tersedia');
                    phoneDuplicateMsg.style.color = '#10B981';
                    phoneDuplicateMsg.style.display = 'block';
                    phoneInputContainer.style.borderColor = '#10B981';
                    phoneInputContainer.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.15)';
                    if (submitCustomerBtn) {
                        submitCustomerBtn.disabled = false;
                        submitCustomerBtn.style.opacity = '1';
                        submitCustomerBtn.style.cursor = 'pointer';
                    }
                } else if (type === 'error') {
                    phoneDuplicateMsg.textContent = '⚠️ ' + (msg || 'Nombor telefon tidak sah');
                    phoneDuplicateMsg.style.color = '#EF4444';
                    phoneDuplicateMsg.style.display = 'block';
                    phoneInputContainer.style.borderColor = '#EF4444';
                    phoneInputContainer.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.15)';
                    if (submitCustomerBtn) {
                        submitCustomerBtn.disabled = true;
                        submitCustomerBtn.style.opacity = '0.5';
                        submitCustomerBtn.style.cursor = 'not-allowed';
                    }
                } else {
                    phoneDuplicateMsg.style.display = 'none';
                    phoneDuplicateMsg.textContent = '';
                    phoneInputContainer.style.borderColor = '#CBD5E1';
                    phoneInputContainer.style.boxShadow = 'none';
                    if (submitCustomerBtn) {
                        submitCustomerBtn.disabled = false;
                        submitCustomerBtn.style.opacity = '1';
                        submitCustomerBtn.style.cursor = 'pointer';
                    }
                }
            }

            function validateAndCheckPhone(val) {
                const inspection = inspectSubscriberDigits(val);

                // Auto-clean input value if user typed 0 or +60
                if (val && inspection.digits && val !== inspection.digits && (val.startsWith('0') || val.startsWith('+60') || val.startsWith('60'))) {
                    phoneInput.value = inspection.digits;
                }

                if (inspection.reason === 'empty') {
                    setPhoneStatus('idle');
                    isPhoneValidAndAvailable = false;
                    return;
                }

                if (!inspection.valid) {
                    setPhoneStatus('error', inspection.message);
                    isPhoneValidAndAvailable = false;
                    return;
                }

                // Check duplicate on server with canonical full number
                const checkUrl = '{{ route("business.customers.check-phone") }}?hp_no=' + encodeURIComponent(inspection.fullE164);

                fetch(checkUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const serverErr = document.getElementById('serverPhoneError');
                    if (serverErr) serverErr.style.display = 'none';

                    if (data.valid === false) {
                        isPhoneValidAndAvailable = false;
                        setPhoneStatus('error', data.message || 'Nombor telefon tidak mencukupi atau format tidak sah.');
                    } else if (data.duplicate) {
                        isPhoneValidAndAvailable = false;
                        setPhoneStatus('error', data.message || 'Nombor telefon ini telah didaftarkan untuk pelanggan lain.');
                    } else {
                        isPhoneValidAndAvailable = true;
                        setPhoneStatus('valid', 'Nombor telefon sah dan sedia untuk blast WhatsApp (+60' + inspection.digits + ')');
                    }
                })
                .catch(err => {
                    console.error('Phone check error:', err);
                });
            }

            if (phoneInput) {
                phoneInput.addEventListener('focus', function() {
                    if (phoneInputContainer && phoneInputContainer.style.borderColor !== 'rgb(239, 68, 68)' && phoneInputContainer.style.borderColor !== 'rgb(16, 185, 129)') {
                        phoneInputContainer.style.borderColor = '#134C49';
                        phoneInputContainer.style.boxShadow = '0 0 0 3px rgba(19, 76, 73, 0.15)';
                    }
                });

                phoneInput.addEventListener('input', function() {
                    const serverErr = document.getElementById('serverPhoneError');
                    if (serverErr) serverErr.style.display = 'none';

                    clearTimeout(phoneCheckTimeout);
                    phoneCheckTimeout = setTimeout(() => {
                        validateAndCheckPhone(phoneInput.value);
                    }, 300);
                });

                phoneInput.addEventListener('blur', function() {
                    if (phoneInputContainer && phoneInputContainer.style.borderColor !== 'rgb(239, 68, 68)' && phoneInputContainer.style.borderColor !== 'rgb(16, 185, 129)') {
                        phoneInputContainer.style.borderColor = '#CBD5E1';
                        phoneInputContainer.style.boxShadow = 'none';
                    }
                    validateAndCheckPhone(phoneInput.value);
                });
            }

            if (addCustomerForm) {
                addCustomerForm.addEventListener('submit', function(e) {
                    const inspection = inspectSubscriberDigits(phoneInput ? phoneInput.value : '');

                    if (!inspection.valid) {
                        e.preventDefault();
                        if (phoneInput) phoneInput.focus();
                        setPhoneStatus('error', inspection.message || 'Nombor telefon tidak mencukupi.');
                        return false;
                    }

                    if (!isPhoneValidAndAvailable) {
                        // Allow submission only if validation was verified
                        // Prepend +60 to ensure full international format sent to backend
                        phoneInput.value = inspection.fullE164;
                    } else {
                        phoneInput.value = inspection.fullE164;
                    }
                });
            }

            // Auto-open modal if validation errors exist or hash is #register
            @if ($errors->any())
                openAddModal();
            @endif

            if (window.location.hash === '#register' || window.location.hash === '#register-customer') {
                openAddModal();
            }
        });

        function openViewModal(name, phone, email, status, abo, address) {
            document.getElementById('viewCustName').textContent = name;
            document.getElementById('viewCustPhone').textContent = phone;
            document.getElementById('viewCustEmail').textContent = email;
            document.getElementById('viewCustStatus').textContent = status;
            document.getElementById('viewCustAbo').textContent = abo || '-';
            document.getElementById('viewCustAddress').textContent = address || '-';
            var vm = document.getElementById('viewCustomerModal');
            if (vm) {
                vm.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                var mc = vm.querySelector('.modal-card');
                if (mc) mc.scrollTop = 0;
            }
        }
    </script>
    @endpush

</x-business-layout>
