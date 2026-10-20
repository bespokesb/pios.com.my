<x-business-layout title="PIOS - Transactions & Orders" headerTitle="Transaction" bodyClass="transaction-body" mainClass="tx-main" mobileHeaderClass="mobile-tx-header">

    <style>
        /* Table Action Buttons explicit styles ensuring immediate design rendering */
        .tx-action-group {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 6px !important;
            flex-wrap: wrap !important;
        }

        .btn-tx-step {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 4px !important;
            padding: 0 14px !important;
            height: 32px !important;
            line-height: 32px !important;
            border-radius: 8px !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            border: none !important;
            transition: all 0.18s ease !important;
            white-space: nowrap !important;
            text-decoration: none !important;
            font-family: inherit !important;
            box-sizing: border-box !important;
        }

        .btn-tx-step.btn-step-paid {
            background-color: #0D9488 !important;
            color: #FFFFFF !important;
            box-shadow: 0 1px 3px rgba(13, 148, 136, 0.35) !important;
        }
        .btn-tx-step.btn-step-paid:hover {
            background-color: #0F766E !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 3px 6px rgba(13, 148, 136, 0.45) !important;
        }

        .btn-tx-step.btn-step-ship {
            background-color: #2563EB !important;
            color: #FFFFFF !important;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.35) !important;
        }
        .btn-tx-step.btn-step-ship:hover {
            background-color: #1D4ED8 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 3px 6px rgba(37, 99, 235, 0.45) !important;
        }

        .btn-tx-step.btn-step-complete {
            background-color: #059669 !important;
            color: #FFFFFF !important;
            box-shadow: 0 1px 3px rgba(5, 150, 105, 0.35) !important;
        }
        .btn-tx-step.btn-step-complete:hover {
            background-color: #047857 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 3px 6px rgba(5, 150, 105, 0.45) !important;
        }

        .btn-tx-view {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            background-color: #F1F5F9 !important;
            border: 1px solid #CBD5E1 !important;
            color: #475569 !important;
            font-family: inherit !important;
            font-size: 0.82rem !important;
            font-weight: 600 !important;
            padding: 0 16px !important;
            height: 32px !important;
            line-height: 30px !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            box-sizing: border-box !important;
        }
        .btn-tx-view:hover {
            background-color: #E2E8F0 !important;
            color: #0F172A !important;
            border-color: #94A3B8 !important;
        }

        /* Dark Mode Overrides */
        body.dark-mode-active .btn-tx-view,
        body.transaction-body.dark-mode-active .btn-tx-view {
            background-color: #1E293B !important;
            border-color: #334155 !important;
            color: #CBD5E1 !important;
        }
        body.dark-mode-active .btn-tx-view:hover,
        body.transaction-body.dark-mode-active .btn-tx-view:hover {
            background-color: #334155 !important;
            color: #FFFFFF !important;
        }

        /* ====== Order Lifecycle Stepper (Icon Boxes) ====== */
        .order-lifecycle-stepper {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            padding: 18px 10px 14px !important;
            background: linear-gradient(135deg, #F8FAFC 0%, #F0FDFA 100%) !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 14px !important;
            margin-bottom: 18px !important;
            position: relative !important;
            gap: 0 !important;
        }

        .stepper-node {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 6px !important;
            text-align: center !important;
            flex: 1 !important;
            position: relative !important;
            z-index: 2 !important;
        }

        .stepper-dot {
            width: 36px !important;
            height: 36px !important;
            border-radius: 10px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0 !important;
            font-weight: 700 !important;
            transition: all 0.25s ease !important;
            background: #FFFFFF !important;
            color: #94A3B8 !important;
            border: 2px solid #CBD5E1 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06) !important;
        }

        .stepper-node.done .stepper-dot {
            background: #0D9488 !important;
            color: #FFFFFF !important;
            border-color: #0D9488 !important;
            box-shadow: 0 2px 6px rgba(13, 148, 136, 0.3) !important;
        }

        .stepper-node.active .stepper-dot {
            background: #134C49 !important;
            color: #FFFFFF !important;
            border-color: #134C49 !important;
            box-shadow: 0 0 0 3px rgba(19, 76, 73, 0.2), 0 2px 8px rgba(19, 76, 73, 0.25) !important;
            transform: scale(1.08) !important;
        }

        .stepper-node.upcoming .stepper-dot {
            background: #F1F5F9 !important;
            color: #94A3B8 !important;
            border-color: #E2E8F0 !important;
        }

        .stepper-node.cancelled .stepper-dot {
            background: #FEF2F2 !important;
            color: #EF4444 !important;
            border-color: #FECACA !important;
        }

        .stepper-title {
            font-size: 0.68rem !important;
            font-weight: 600 !important;
            color: #64748B !important;
            text-transform: uppercase !important;
            letter-spacing: 0.03em !important;
            line-height: 1.2 !important;
        }

        .stepper-node.done .stepper-title,
        .stepper-node.active .stepper-title {
            color: #0F172A !important;
            font-weight: 700 !important;
        }

        .stepper-bar {
            position: absolute !important;
            top: 18px !important;
            left: 50% !important;
            width: 100% !important;
            height: 3px !important;
            background: #E2E8F0 !important;
            z-index: 1 !important;
            border-radius: 2px !important;
        }

        .stepper-bar.done {
            background: #0D9488 !important;
        }

        /* Dark Mode Stepper */
        body.dark-mode-active .order-lifecycle-stepper,
        body.transaction-body.dark-mode-active .order-lifecycle-stepper {
            background: linear-gradient(135deg, #1E293B 0%, #0F2524 100%) !important;
            border-color: #334155 !important;
        }
        body.dark-mode-active .stepper-dot,
        body.transaction-body.dark-mode-active .stepper-dot {
            background: #1E293B !important;
            border-color: #475569 !important;
            color: #64748B !important;
        }
        body.dark-mode-active .stepper-node.done .stepper-dot,
        body.transaction-body.dark-mode-active .stepper-node.done .stepper-dot {
            background: #0D9488 !important;
            border-color: #0D9488 !important;
            color: #FFFFFF !important;
        }
        body.dark-mode-active .stepper-node.active .stepper-dot,
        body.transaction-body.dark-mode-active .stepper-node.active .stepper-dot {
            background: #14B8A6 !important;
            border-color: #14B8A6 !important;
            color: #FFFFFF !important;
        }
        body.dark-mode-active .stepper-title,
        body.transaction-body.dark-mode-active .stepper-title {
            color: #94A3B8 !important;
        }
        body.dark-mode-active .stepper-node.done .stepper-title,
        body.dark-mode-active .stepper-node.active .stepper-title,
        body.transaction-body.dark-mode-active .stepper-node.done .stepper-title,
        body.transaction-body.dark-mode-active .stepper-node.active .stepper-title {
            color: #E2E8F0 !important;
        }
        body.dark-mode-active .stepper-bar,
        body.transaction-body.dark-mode-active .stepper-bar {
            background: #334155 !important;
        }
        body.dark-mode-active .stepper-bar.done,
        body.transaction-body.dark-mode-active .stepper-bar.done {
            background: #0D9488 !important;
        }
    </style>

    <!-- Desktop Header Bar -->
    <div class="tx-topbar">
        <div class="topbar-left">
            <h1 class="tx-heading">Transactions</h1>
            <p class="tx-subheading">Track orders, customer purchases, payment verification and fulfillment</p>
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

    <!-- Transaction Summary Stats -->
    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
        <div class="kpi-card">
            <div class="kpi-value text-teal">RM {{ number_format((float)$totalRevenue, 2) }}</div>
            <div class="kpi-title">Total Revenue</div>
            <div class="kpi-subtitle">Completed & Paid</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value text-blue">{{ number_format($totalOrdersCount) }}</div>
            <div class="kpi-title">Total Orders</div>
            <div class="kpi-subtitle">Lifetime orders placed</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value text-green">{{ number_format($paidCount) }}</div>
            <div class="kpi-title">Paid Orders</div>
            <div class="kpi-subtitle">Ready to ship</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-value text-orange">{{ number_format($pendingCount) }}</div>
            <div class="kpi-title">Pending Orders</div>
            <div class="kpi-subtitle">Awaiting payment</div>
        </div>
    </div>

    <!-- Transaction Flow Pipeline -->
    <div class="tx-flow-card">
        <div class="tx-flow-header">
            <div class="tx-flow-title">Transaction Pipeline & Fulfilment Flow</div>
        </div>
        <div class="tx-flow-steps">
            <div class="tx-flow-step {{ $status === 'all' ? 'active-filter' : '' }}" data-status="all">
                <div class="step-num text-purple">{{ $totalOrdersCount }}</div>
                <div class="step-label">All Orders</div>
            </div>
            <div class="step-arrow">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </div>
            <div class="tx-flow-step {{ $status === 'submitted' ? 'active-filter' : '' }}" data-status="submitted">
                <div class="step-num text-orange">{{ $pendingCount }}</div>
                <div class="step-label">Pending</div>
            </div>
            <div class="step-arrow">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </div>
            <div class="tx-flow-step {{ $status === 'paid' ? 'active-filter' : '' }}" data-status="paid">
                <div class="step-num text-teal">{{ $paidCount }}</div>
                <div class="step-label">Paid</div>
            </div>
            <div class="step-arrow">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </div>
            <div class="tx-flow-step {{ $status === 'shipped' ? 'active-filter' : '' }}" data-status="shipped">
                <div class="step-num text-blue">{{ $shippedCount }}</div>
                <div class="step-label">Shipped</div>
            </div>
            <div class="step-arrow">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </div>
            <div class="tx-flow-step {{ $status === 'completed' ? 'active-filter' : '' }}" data-status="completed">
                <div class="step-num text-amber">{{ $completedCount }}</div>
                <div class="step-label">Completed</div>
            </div>
        </div>
    </div>

    <!-- Filter and Search Bar -->
    <div class="tx-action-bar">
        <form method="GET" action="{{ route('business.transactions.index') }}" class="tx-search-wrapper">
            <svg class="tx-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" name="search" id="txSearchInput" class="tx-search-input" placeholder="Search order #, customer, or product..." value="{{ $search }}">
        </form>

        <div class="tx-filter-wrapper">
            <select id="paymentFilterSelect" class="tx-filter-select">
                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                <option value="submitted" {{ $status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="shipped" {{ $status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <div class="tx-select-arrow">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
        </div>
    </div>

    <!-- Desktop Transactions Data Table -->
    <div class="tx-table-card">
        <div class="table-responsive-wrapper">
            <table class="tx-table" id="transactionTable">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Product / Summary</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="transactionTableBody">
                    @forelse ($orders as $order)
                        @php
                            $badgeClass = match ($order->status) {
                                \App\Enums\OrderStatus::Completed => 'badge-complete',
                                \App\Enums\OrderStatus::Paid => 'badge-paid',
                                \App\Enums\OrderStatus::Packed => 'badge-paid',
                                \App\Enums\OrderStatus::Shipped => 'badge-delivery',
                                \App\Enums\OrderStatus::Submitted => 'badge-order',
                                \App\Enums\OrderStatus::Draft => 'badge-invoice',
                                \App\Enums\OrderStatus::Cancelled => 'badge-cancelled',
                                default => 'badge-order',
                            };
                            $firstItem = $order->items->first();
                            $itemSummary = $firstItem ? $firstItem->product_name . ($order->items->count() > 1 ? ' +' . ($order->items->count() - 1) . ' more' : '') : 'Order Items';
                            $customerName = $order->customer?->name ?? 'Direct Customer';
                        @endphp
                        <tr class="tx-row"
                            id="txRow-{{ $order->id }}"
                            data-id="{{ $order->id }}"
                            data-order="{{ $order->order_no }}" 
                            data-customer="{{ $customerName }}" 
                            data-customer-phone="{{ $order->customer?->hp_no ?? '-' }}"
                            data-product="{{ $itemSummary }}" 
                            data-status="{{ $order->status->value }}" 
                            data-status-label="{{ $order->status->label() }}" 
                            data-tracking="{{ $order->tracking_number ?? '' }}"
                            data-amount="{{ (float)$order->total }}" 
                            data-date="{{ $order->created_at->format('d M Y, h:i A') }}"
                            data-paid-at="{{ $order->paid_at?->format('d M Y, h:i A') ?? '-' }}"
                            data-shipped-at="{{ $order->shipped_at?->format('d M Y, h:i A') ?? '-' }}"
                            data-is-terminal="{{ $order->status->isTerminal() ? '1' : '0' }}"
                            data-next-statuses="{{ json_encode(collect($order->status->nextStatuses())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])) }}"
                            data-items="{{ json_encode($order->items->map(fn ($it) => ['name' => $it->product_name, 'qty' => $it->quantity, 'price' => number_format((float)$it->unit_price, 2), 'total' => number_format((float)$it->line_total, 2)])) }}">
                            <td class="col-order">
                                <strong>#{{ $order->order_no }}</strong>
                                <div class="row-tracking-wrap">
                                    @if ($order->tracking_number)
                                        <div class="tracking-tag" title="Tracking Number: {{ $order->tracking_number }}">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                            <span>{{ $order->tracking_number }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="col-customer">
                                <div>{{ $customerName }}</div>
                                <div style="font-size: 0.75rem; color: #64748B; font-weight: normal;">{{ $order->customer?->hp_no ?? '-' }}</div>
                            </td>
                            <td class="col-product">{{ $itemSummary }}</td>
                            <td class="col-date">{{ $order->created_at->format('d M Y') }}</td>
                            <td class="col-amount">RM {{ number_format((float)$order->total, 2) }}</td>
                            <td class="col-status-cell">
                                <span class="badge-status {{ $badgeClass }}">{{ $order->status->label() }}</span>
                            </td>
                            <td style="text-align: right;">
                                <div class="tx-action-group">
                                    <button type="button" class="btn-tx-view" onclick="openOrderDetailsById({{ $order->id }})">View</button>
                                    <a href="{{ route('order.invoice', $order) }}" target="_blank" class="btn-tx-view" style="text-decoration: none;" title="View Invoice">Invoice</a>
                                    @if ($order->isPaid())
                                        <a href="{{ route('order.receipt', $order) }}" target="_blank" class="btn-tx-view" style="text-decoration: none;" title="View Receipt">Receipt</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 36px; color: #64748B;">
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="tx-table-footer">
            <div class="tx-count-text" id="txCountText">
                Showing {{ $orders->count() }} of {{ $orders->count() }} transactions
            </div>
        </div>
    </div>

    <!-- Mobile Card List (< 992px) -->
    <div class="tx-mobile-list" id="txMobileList">
        @forelse ($orders as $order)
            @php
                $badgeClass = match ($order->status) {
                    \App\Enums\OrderStatus::Completed => 'badge-complete',
                    \App\Enums\OrderStatus::Paid => 'badge-paid',
                    \App\Enums\OrderStatus::Packed => 'badge-paid',
                    \App\Enums\OrderStatus::Shipped => 'badge-delivery',
                    \App\Enums\OrderStatus::Submitted => 'badge-order',
                    \App\Enums\OrderStatus::Draft => 'badge-invoice',
                    \App\Enums\OrderStatus::Cancelled => 'badge-cancelled',
                    default => 'badge-order',
                };
                $firstItem = $order->items->first();
                $itemSummary = $firstItem ? $firstItem->product_name . ($order->items->count() > 1 ? ' +' . ($order->items->count() - 1) . ' more' : '') : 'Order Items';
                $customerName = $order->customer?->name ?? 'Direct Customer';
            @endphp
            <div class="tx-card"
                id="txCard-{{ $order->id }}"
                data-id="{{ $order->id }}"
                data-order="{{ $order->order_no }}" 
                data-customer="{{ $customerName }}" 
                data-customer-phone="{{ $order->customer?->hp_no ?? '-' }}"
                data-product="{{ $itemSummary }}" 
                data-status="{{ $order->status->value }}" 
                data-status-label="{{ $order->status->label() }}" 
                data-tracking="{{ $order->tracking_number ?? '' }}"
                data-amount="{{ (float)$order->total }}" 
                data-date="{{ $order->created_at->format('d M Y, h:i A') }}"
                data-paid-at="{{ $order->paid_at?->format('d M Y, h:i A') ?? '-' }}"
                data-shipped-at="{{ $order->shipped_at?->format('d M Y, h:i A') ?? '-' }}"
                data-is-terminal="{{ $order->status->isTerminal() ? '1' : '0' }}"
                data-next-statuses="{{ json_encode(collect($order->status->nextStatuses())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])) }}"
                data-items="{{ json_encode($order->items->map(fn ($it) => ['name' => $it->product_name, 'qty' => $it->quantity, 'price' => number_format((float)$it->unit_price, 2), 'total' => number_format((float)$it->line_total, 2)])) }}">
                <div class="tx-card-top">
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        <span class="tx-card-order">#{{ $order->order_no }}</span>
                        <div class="card-tracking-wrap">
                            @if ($order->tracking_number)
                                <div class="tracking-tag" title="Tracking Number">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                    <span>{{ $order->tracking_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <span class="badge-status {{ $badgeClass }}">{{ $order->status->label() }}</span>
                </div>
                <div class="tx-card-middle">
                    <span class="tx-card-customer">{{ $customerName }}</span>
                    <span class="tx-card-amount">RM {{ number_format((float)$order->total, 2) }}</span>
                </div>
                <div class="tx-card-bottom">
                    <span class="tx-card-product">{{ $itemSummary }}</span>
                    <span class="tx-card-date">{{ $order->created_at->format('d M Y') }}</span>
                </div>
                <!-- Mobile Card Actions -->
                <div class="tx-card-actions" style="display: flex; gap: 8px; margin-top: 12px; padding-top: 10px; border-top: 1px solid #F1F5F9; flex-wrap: wrap;">
                    <button type="button" class="btn-tx-view" style="flex: 1;" onclick="openOrderDetailsById({{ $order->id }})">Details</button>
                </div>
            </div>
        @empty
            <div class="tx-card" style="text-align: center; color: #64748B; padding: 24px;">
                No transactions found.
            </div>
        @endforelse
    </div>

    <!-- Dedicated Update Order Status Modal -->
    <div class="tx-modal-overlay" id="updateStatusModal" aria-hidden="true">
        <div class="tx-modal-card" style="max-width: 520px;">
            <div class="tx-modal-header">
                <div class="tx-modal-title-wrap">
                    <span class="tx-modal-title" id="statusModalOrderNo">Update Order Status</span>
                </div>
                <button type="button" class="tx-modal-close" id="closeStatusModalBtn" aria-label="Close modal" onclick="window.closeUpdateStatusModal ? window.closeUpdateStatusModal() : (document.getElementById('updateStatusModal').classList.remove('active'), document.getElementById('updateStatusModal').style.display='none', document.body.style.overflow='')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <form id="updateStatusForm">
                <input type="hidden" id="statusModalOrderId" name="order_id" value="">
                <div class="tx-modal-body">
                    <!-- Visual Order Lifecycle Stepper -->
                    <div class="order-lifecycle-stepper" id="statusStepper">
                        <div class="stepper-node" data-step="submitted">
                            <div class="stepper-dot">1</div>
                            <span class="stepper-title">Pending</span>
                            <div class="stepper-bar"></div>
                        </div>
                        <div class="stepper-node" data-step="paid">
                            <div class="stepper-dot">2</div>
                            <span class="stepper-title">Paid</span>
                            <div class="stepper-bar"></div>
                        </div>
                        <div class="stepper-node" data-step="packed">
                            <div class="stepper-dot">3</div>
                            <span class="stepper-title">Packed</span>
                            <div class="stepper-bar"></div>
                        </div>
                        <div class="stepper-node" data-step="shipped">
                            <div class="stepper-dot">4</div>
                            <span class="stepper-title">Shipped</span>
                            <div class="stepper-bar"></div>
                        </div>
                        <div class="stepper-node" data-step="completed">
                            <div class="stepper-dot">5</div>
                            <span class="stepper-title">Completed</span>
                        </div>
                    </div>

                    <!-- Customer / Order Mini Summary -->
                    <div class="tx-modal-section full-width" style="margin-bottom: 14px;">
                        <div class="info-row">
                            <span class="info-key">Customer:</span>
                            <span class="info-val font-semibold" id="statusModalCustomer">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Current Status:</span>
                            <span id="statusModalCurrentBadge" class="badge-status">-</span>
                        </div>
                    </div>

                    <!-- Next Status Selection -->
                    <div class="section-label" style="margin-bottom: 4px;">Select Next Status:</div>
                    <div class="status-options-grid" id="statusOptionsGrid">
                        <!-- Populated dynamically by JS based on legal nextStatuses() -->
                    </div>

                    <!-- Tracking Number Input Group (visible when status === 'shipped') -->
                    <div class="tracking-input-card" id="trackingInputGroup" style="display: none;">
                        <label for="trackingNumberInput" class="section-label" style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Courier & Tracking Number <span style="color: #EF4444;">*</span></span>
                            <span style="font-size: 0.72rem; text-transform: none; color: #64748B;">J&T, Pos Laju, Ninja Van, etc.</span>
                        </label>
                        <input type="text" name="tracking_number" id="trackingNumberInput" class="tracking-input-field" placeholder="e.g. JNT8273619283MY">
                        <div style="font-size: 0.76rem; color: #64748B; margin-top: 2px;">
                            A valid tracking code is required to advance the order to <strong>Shipped</strong>.
                        </div>
                    </div>

                    <!-- Error Alert Box -->
                    <div id="statusModalError" style="display: none; margin-top: 12px; padding: 10px 14px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; color: #991B1B; font-size: 0.85rem;"></div>
                </div>
                <div class="tx-modal-footer" style="display: flex !important; align-items: center !important; justify-content: flex-end !important; gap: 10px !important; padding: 16px 24px !important; border-top: 1px solid #E2E8F0 !important; background-color: #FFFFFF !important;">
                    <button type="button" class="btn-cancel" id="cancelStatusModalBtn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; height: 42px !important; min-height: 42px !important; max-height: 42px !important; line-height: 40px !important; padding: 0 20px !important; border-radius: 10px !important; font-size: 0.875rem !important; font-weight: 600 !important; background: #FFFFFF !important; border: 1px solid #CBD5E1 !important; color: #475569 !important; cursor: pointer !important; box-sizing: border-box !important;">Cancel</button>
                    <button type="submit" class="btn-tx-step btn-step-paid" id="saveStatusBtn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; height: 42px !important; min-height: 42px !important; max-height: 42px !important; line-height: 40px !important; padding: 0 22px !important; border-radius: 10px !important; font-size: 0.875rem !important; font-weight: 600 !important; background: #0D9488 !important; border: 1px solid #0D9488 !important; color: #FFFFFF !important; cursor: pointer !important; box-sizing: border-box !important; box-shadow: 0 2px 6px rgba(13, 148, 136, 0.35) !important;">
                        <span id="saveStatusBtnText">Confirm Update</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div class="tx-modal-overlay" id="txModal" aria-hidden="true">
        <div class="tx-modal-card" style="max-width: 620px;">
            <div class="tx-modal-header">
                <div class="tx-modal-title-wrap">
                    <span class="tx-modal-title" id="modalOrderNo">#ORD-0000</span>
                    <span class="badge-status" id="modalStatusBadge">Status</span>
                </div>
                <button type="button" class="tx-modal-close" id="closeTxModalBtn" aria-label="Close modal" onclick="window.closeTxModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="tx-modal-body">
                <!-- Lifecycle Stepper in Details Modal (Icon Boxes) -->
                <div class="order-lifecycle-stepper" id="detailsStepper">
                    <div class="stepper-node" data-step="submitted">
                        <div class="stepper-dot">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        </div>
                        <span class="stepper-title">Pending</span>
                        <div class="stepper-bar"></div>
                    </div>
                    <div class="stepper-node" data-step="paid">
                        <div class="stepper-dot">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </div>
                        <span class="stepper-title">Paid</span>
                        <div class="stepper-bar"></div>
                    </div>
                    <div class="stepper-node" data-step="packed">
                        <div class="stepper-dot">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        </div>
                        <span class="stepper-title">Packed</span>
                        <div class="stepper-bar"></div>
                    </div>
                    <div class="stepper-node" data-step="shipped">
                        <div class="stepper-dot">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                        </div>
                        <span class="stepper-title">Shipped</span>
                        <div class="stepper-bar"></div>
                    </div>
                    <div class="stepper-node" data-step="completed">
                        <div class="stepper-dot">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <span class="stepper-title">Completed</span>
                    </div>
                </div>

                <div class="tx-modal-grid">
                    <div class="tx-modal-section">
                        <div class="section-label">Customer Information</div>
                        <div class="info-row">
                            <span class="info-key">Name:</span>
                            <span class="info-val font-semibold" id="modalCustomerName">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Phone:</span>
                            <span class="info-val" id="modalCustomerPhone">-</span>
                        </div>
                    </div>
                    <div class="tx-modal-section">
                        <div class="section-label">Order Details</div>
                        <div class="info-row">
                            <span class="info-key">Date:</span>
                            <span class="info-val" id="modalOrderDate">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Total:</span>
                            <span class="info-val font-bold text-teal" id="modalTotalAmount">-</span>
                        </div>
                    </div>

                    <!-- Tracking Section -->
                    <div class="tx-modal-section full-width" id="modalTrackingSection" style="display: none;">
                        <div class="section-label">Shipment & Delivery Tracking</div>
                        <div class="info-row">
                            <span class="info-key">Tracking Number:</span>
                            <span class="info-val" style="font-family: monospace; font-weight: 700; color: #0D9488;" id="modalTrackingNumber">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Shipped At:</span>
                            <span class="info-val" id="modalShippedAt">-</span>
                        </div>
                    </div>

                    <!-- Items List Breakdown -->
                    <div class="tx-modal-section full-width">
                        <div class="section-label">Ordered Items</div>
                        <div id="modalItemsContainer" style="overflow-x: auto; margin-top: 4px;">
                            <!-- Populated by JS -->
                        </div>
                    </div>

                    <div class="tx-modal-section full-width">
                        <div class="section-label">Payment & Timestamps</div>
                        <div class="payment-box">
                            <div class="info-row">
                                <span class="info-key">Paid Date:</span>
                                <span class="info-val" id="modalPaidAt">-</span>
                            </div>
                            <div class="info-row">
                                <span class="info-key">Status:</span>
                                <span class="info-val" id="modalPaymentStatus">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tx-modal-footer" style="display: flex !important; align-items: center !important; justify-content: flex-end !important; gap: 10px !important; padding: 16px 24px !important; border-top: 1px solid #E2E8F0 !important; background-color: #FFFFFF !important; flex-wrap: wrap !important;">
                <button type="button" class="btn-cancel" id="cancelTxModalBtn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; height: 42px !important; min-height: 42px !important; max-height: 42px !important; line-height: 40px !important; padding: 0 20px !important; border-radius: 10px !important; font-size: 0.875rem !important; font-weight: 600 !important; background: #FFFFFF !important; border: 1px solid #CBD5E1 !important; color: #475569 !important; cursor: pointer !important; box-sizing: border-box !important; margin: 0 !important; white-space: nowrap !important;" onclick="window.closeTxModal()">Close</button>
                <button type="button" class="btn-tx-step btn-step-paid" id="modalActionBtn" style="display: none; align-items: center !important; justify-content: center !important; height: 42px !important; min-height: 42px !important; max-height: 42px !important; line-height: 40px !important; padding: 0 22px !important; border-radius: 10px !important; font-size: 0.875rem !important; font-weight: 600 !important; background: #0D9488 !important; border: 1px solid #0D9488 !important; color: #FFFFFF !important; cursor: pointer !important; box-sizing: border-box !important; margin: 0 !important; box-shadow: 0 2px 6px rgba(13, 148, 136, 0.35) !important; white-space: nowrap !important;" onclick="window.handleModalActionClick(this, event)">
                    Mark Paid
                </button>
            </div>
        </div>
    </div>

    <!-- Dedicated Mark Paid Modal (Payment Reference Popup) -->
    <div class="tx-modal-overlay" id="markPaidModal" aria-hidden="true" style="z-index: 1060;">
        <div class="tx-modal-card" style="max-width: 480px;">
            <div class="tx-modal-header">
                <div class="tx-modal-title-wrap">
                    <span class="tx-modal-title" id="markPaidOrderTitle">Confirm Payment</span>
                </div>
                <button type="button" class="tx-modal-close" id="closeMarkPaidModalBtn" aria-label="Close modal" onclick="window.closeMarkPaidModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <form id="markPaidForm" onsubmit="window.handleMarkPaidSubmit(event)">
                <input type="hidden" id="markPaidOrderId" name="order_id" value="">
                <div class="tx-modal-body">
                    <div class="tx-modal-section full-width" style="margin-bottom: 16px; background: #F0FDFA; border: 1px solid #CCFBF1; border-radius: 10px; padding: 14px;">
                        <div class="info-row" style="margin-bottom: 6px;">
                            <span class="info-key" style="color: #0F766E;">Customer:</span>
                            <span class="info-val font-semibold" id="markPaidCustomer" style="color: #134E4A;">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key" style="color: #0F766E;">Order Total:</span>
                            <span class="info-val font-bold text-teal" style="font-size: 1.15rem;" id="markPaidAmount">RM 0.00</span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="paymentReferenceInput" class="section-label" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Payment Reference / Rujukan Pembayaran <span style="color: #EF4444;">*</span>
                        </label>
                        <input type="text" name="reference_no" id="paymentReferenceInput" class="tracking-input-field" placeholder="e.g. DuitNow Ref, MBB-837192, Resit-001" required style="font-family: inherit;">
                        <div style="font-size: 0.78rem; color: #64748B; margin-top: 4px;">
                            Enter bank transaction reference number or receipt code for payment verification.
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="paymentMethodSelect" class="section-label" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Payment Method
                        </label>
                        <select name="payment_type" id="paymentMethodSelect" class="tracking-input-field" style="font-family: inherit; cursor: pointer;">
                            <option value="bank_transfer" selected>Online Banking / DuitNow / FPX</option>
                            <option value="cdm">Cash Deposit Machine (CDM)</option>
                            <option value="qr_pay">QR Pay / E-Wallet (TnG, GrabPay)</option>
                            <option value="cash">Cash Payment</option>
                            <option value="card">Credit / Debit Card</option>
                            <option value="other">Other Method</option>
                        </select>
                    </div>

                    <div id="markPaidModalError" style="display: none; margin-top: 10px; padding: 10px 14px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; color: #991B1B; font-size: 0.85rem;"></div>
                </div>
                <div class="tx-modal-footer" style="display: flex !important; align-items: center !important; justify-content: flex-end !important; gap: 10px !important; padding: 16px 24px !important; border-top: 1px solid #E2E8F0 !important; background-color: #FFFFFF !important;">
                    <button type="button" class="btn-cancel" id="cancelMarkPaidModalBtn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; height: 42px !important; min-height: 42px !important; max-height: 42px !important; line-height: 40px !important; padding: 0 20px !important; border-radius: 10px !important; font-size: 0.875rem !important; font-weight: 600 !important; background: #FFFFFF !important; border: 1px solid #CBD5E1 !important; color: #475569 !important; cursor: pointer !important; box-sizing: border-box !important;" onclick="window.closeMarkPaidModal()">Cancel</button>
                    <button type="submit" class="btn-tx-step btn-step-paid" id="submitMarkPaidBtn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; height: 42px !important; min-height: 42px !important; max-height: 42px !important; line-height: 40px !important; padding: 0 22px !important; border-radius: 10px !important; font-size: 0.875rem !important; font-weight: 600 !important; background: #0D9488 !important; border: 1px solid #0D9488 !important; color: #FFFFFF !important; cursor: pointer !important; box-sizing: border-box !important; box-shadow: 0 2px 6px rgba(13, 148, 136, 0.35) !important;">
                        <span id="submitMarkPaidBtnText">Confirm & Mark Paid</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Dedicated Shipping Modal (Delivery Provider & Tracking Number Popup) -->
    <div class="tx-modal-overlay" id="shipOrderModal" aria-hidden="true" style="z-index: 1060;">
        <div class="tx-modal-card" style="max-width: 500px;">
            <div class="tx-modal-header">
                <div class="tx-modal-title-wrap">
                    <span class="tx-modal-title" id="shipOrderTitle">Ship Order</span>
                </div>
                <button type="button" class="tx-modal-close" id="closeShipOrderModalBtn" aria-label="Close modal" onclick="window.closeShipOrderModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <form id="shipOrderForm" onsubmit="window.handleShipOrderSubmit(event)">
                <input type="hidden" id="shipOrderId" name="order_id" value="">
                <div class="tx-modal-body">
                    <div class="tx-modal-section full-width" style="margin-bottom: 16px; background: #EFF6FF; border: 1px solid #DBEAFE; border-radius: 10px; padding: 14px;">
                        <div class="info-row" style="margin-bottom: 4px;">
                            <span class="info-key" style="color: #1E40AF;">Customer:</span>
                            <span class="info-val font-semibold" id="shipOrderCustomer" style="color: #1E3A8A;">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key" style="color: #1E40AF;">Status Transition:</span>
                            <span class="info-val font-semibold" style="color: #2563EB;">Paid ➔ In Transit (Shipped)</span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="shipDeliveryProviderSelect" class="section-label" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Delivery Provider / Courier <span style="color: #EF4444;">*</span>
                        </label>
                        <select name="delivery_provider" id="shipDeliveryProviderSelect" class="tracking-input-field" style="font-family: inherit; cursor: pointer;" required onchange="var customWrap = document.getElementById('customProviderWrap'); if (customWrap) { customWrap.style.display = this.value === 'Other' ? 'block' : 'none'; }">
                            <option value="" disabled selected>-- Select Courier Service --</option>
                            <option value="J&T Express">J&T Express</option>
                            <option value="Pos Laju">Pos Laju</option>
                            <option value="Ninja Van">Ninja Van</option>
                            <option value="DHL eCommerce">DHL eCommerce</option>
                            <option value="Shopee Express">Shopee Express (SPX)</option>
                            <option value="Flash Express">Flash Express</option>
                            <option value="City-Link Express">City-Link Express</option>
                            <option value="Lalamove">Lalamove</option>
                            <option value="GrabExpress">GrabExpress</option>
                            <option value="Other">Other Delivery Service</option>
                        </select>
                    </div>

                    <div class="form-group" id="customProviderWrap" style="display: none; margin-bottom: 16px;">
                        <label for="customProviderInput" class="section-label" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Specify Delivery Provider Name
                        </label>
                        <input type="text" id="customProviderInput" class="tracking-input-field" placeholder="e.g. Line Clear, GDex, Runner">
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="shipTrackingNumberInput" class="section-label" style="display: block; margin-bottom: 6px; font-weight: 600;">
                            Shipping Tracking Number <span style="color: #EF4444;">*</span>
                        </label>
                        <input type="text" name="tracking_number" id="shipTrackingNumberInput" class="tracking-input-field" placeholder="e.g. JNT8293710294MY" required>
                        <div style="font-size: 0.78rem; color: #64748B; margin-top: 4px;">
                            This tracking code will be recorded for customer package tracing.
                        </div>
                    </div>

                    <div id="shipOrderModalError" style="display: none; margin-top: 10px; padding: 10px 14px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; color: #991B1B; font-size: 0.85rem;"></div>
                </div>
                <div class="tx-modal-footer" style="display: flex !important; align-items: center !important; justify-content: flex-end !important; gap: 10px !important; padding: 16px 24px !important; border-top: 1px solid #E2E8F0 !important; background-color: #FFFFFF !important;">
                    <button type="button" class="btn-cancel" id="cancelShipOrderModalBtn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; height: 42px !important; min-height: 42px !important; max-height: 42px !important; line-height: 40px !important; padding: 0 20px !important; border-radius: 10px !important; font-size: 0.875rem !important; font-weight: 600 !important; background: #FFFFFF !important; border: 1px solid #CBD5E1 !important; color: #475569 !important; cursor: pointer !important; box-sizing: border-box !important;" onclick="window.closeShipOrderModal()">Cancel</button>
                    <button type="submit" class="btn-tx-step btn-step-ship" id="submitShipOrderBtn" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; height: 42px !important; min-height: 42px !important; max-height: 42px !important; line-height: 40px !important; padding: 0 22px !important; border-radius: 10px !important; font-size: 0.875rem !important; font-weight: 600 !important; background: #2563EB !important; border: 1px solid #2563EB !important; color: #FFFFFF !important; cursor: pointer !important; box-sizing: border-box !important; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35) !important;">
                        <span id="submitShipOrderBtnText">Confirm Shipment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toastNotification" class="toast-notification">
        <span id="toastMessage"></span>
    </div>

    <style>
        /* ==========================================================================
           PIOS Modal System - Viewport Responsive & Smooth Touch Scrolling
           Tested & verified for iPhone (iOS Safari) and MacBook (Desktop)
           ========================================================================== */

        /* 1. Modal Overlay Container */
        .tx-modal-overlay {
            position: fixed !important;
            inset: 0 !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: 100% !important;
            height: 100dvh !important;
            background-color: rgba(15, 23, 42, 0.65) !important;
            backdrop-filter: blur(4px) !important;
            -webkit-backdrop-filter: blur(4px) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 24px 16px !important;
            z-index: 999 !important;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain !important;
            box-sizing: border-box !important;
            transition: opacity 0.2s ease, visibility 0.2s ease !important;
        }

        .tx-modal-overlay.active {
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }

        /* Modal Layering & Stacking */
        #markPaidModal,
        #shipOrderModal,
        #updateStatusModal {
            z-index: 1060 !important;
        }

        /* 2. Modal Dialog Card */
        .tx-modal-card {
            background-color: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 16px !important;
            width: 100% !important;
            max-width: 620px;
            max-height: calc(100vh - 48px) !important;
            max-height: calc(100dvh - 48px) !important;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2), 0 4px 12px rgba(0, 0, 0, 0.05) !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            margin: auto !important;
            flex-shrink: 0 !important;
            position: relative !important;
            box-sizing: border-box !important;
            transform: translateY(20px) scale(0.98);
            transition: transform 0.25s ease !important;
        }

        .tx-modal-overlay.active .tx-modal-card {
            transform: translateY(0) scale(1) !important;
        }

        /* Pass-through flex for forms inside modal cards */
        .tx-modal-card > form {
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow: hidden !important;
            margin: 0 !important;
        }

        /* 3. Sticky Modal Header */
        .tx-modal-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 18px 24px !important;
            border-bottom: 1px solid #E2E8F0 !important;
            background-color: #FFFFFF !important;
            flex-shrink: 0 !important;
            z-index: 2 !important;
        }

        /* 4. Scrollable Modal Body */
        .tx-modal-body {
            padding: 22px 24px !important;
            background-color: #FFFFFF !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch !important;
            overscroll-behavior: contain !important;
        }

        /* Custom subtle scrollbar */
        .tx-modal-body::-webkit-scrollbar {
            width: 6px !important;
        }
        .tx-modal-body::-webkit-scrollbar-track {
            background: transparent !important;
        }
        .tx-modal-body::-webkit-scrollbar-thumb {
            background-color: #CBD5E1 !important;
            border-radius: 9999px !important;
        }
        .tx-modal-body::-webkit-scrollbar-thumb:hover {
            background-color: #94A3B8 !important;
        }

        /* 5. Sticky Modal Footer */
        .tx-modal-footer {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 10px !important;
            padding: 16px 24px !important;
            border-top: 1px solid #E2E8F0 !important;
            background-color: #FFFFFF !important;
            flex-shrink: 0 !important;
            flex-wrap: wrap !important;
            z-index: 2 !important;
        }

        .tx-modal-footer .btn-cancel,
        .tx-modal-footer #cancelTxModalBtn,
        .tx-modal-footer .btn-tx-step,
        .tx-modal-footer #modalActionBtn,
        .tx-modal-footer .btn-print,
        .tx-modal-footer #printReceiptBtn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            height: 42px !important;
            min-height: 42px !important;
            max-height: 42px !important;
            line-height: 42px !important;
            padding: 0 20px !important;
            box-sizing: border-box !important;
            border-radius: 10px !important;
            font-family: inherit !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            white-space: nowrap !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
            margin: 0 !important;
        }

        /* Neutral Close / Cancel Button */
        .tx-modal-footer .btn-cancel,
        .tx-modal-footer #cancelTxModalBtn {
            background: #FFFFFF !important;
            border: 1px solid #CBD5E1 !important;
            color: #475569 !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        }
        .tx-modal-footer .btn-cancel:hover,
        .tx-modal-footer #cancelTxModalBtn:hover {
            background-color: #F8FAFC !important;
            border-color: #94A3B8 !important;
            color: #0F172A !important;
        }

        /* Primary Hero Step Progression Buttons */
        .tx-modal-footer .btn-tx-step.btn-step-paid,
        .tx-modal-footer #modalActionBtn.btn-step-paid {
            background: #0D9488 !important;
            color: #FFFFFF !important;
            border: 1px solid #0D9488 !important;
            box-shadow: 0 2px 6px rgba(13, 148, 136, 0.35) !important;
        }
        .tx-modal-footer .btn-tx-step.btn-step-paid:hover,
        .tx-modal-footer #modalActionBtn.btn-step-paid:hover {
            background: #0F766E !important;
            border-color: #0F766E !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 10px rgba(13, 148, 136, 0.45) !important;
        }

        .tx-modal-footer .btn-tx-step.btn-step-ship,
        .tx-modal-footer #modalActionBtn.btn-step-ship {
            background: #2563EB !important;
            color: #FFFFFF !important;
            border: 1px solid #2563EB !important;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35) !important;
        }
        .tx-modal-footer .btn-tx-step.btn-step-ship:hover,
        .tx-modal-footer #modalActionBtn.btn-step-ship:hover {
            background: #1D4ED8 !important;
            border-color: #1D4ED8 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.45) !important;
        }

        .tx-modal-footer .btn-tx-step.btn-step-complete,
        .tx-modal-footer #modalActionBtn.btn-step-complete {
            background: #059669 !important;
            color: #FFFFFF !important;
            border: 1px solid #059669 !important;
            box-shadow: 0 2px 6px rgba(5, 150, 105, 0.35) !important;
        }
        .tx-modal-footer .btn-tx-step.btn-step-complete:hover,
        .tx-modal-footer #modalActionBtn.btn-step-complete:hover {
            background: #047857 !important;
            border-color: #047857 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.45) !important;
        }

        /* 6. Responsive: Tablets & Mobile (< 992px) */
        @media screen and (max-width: 991px) {
            .tx-modal-overlay {
                padding: 12px 10px !important;
                padding-top: max(12px, env(safe-area-inset-top)) !important;
                padding-bottom: max(12px, env(safe-area-inset-bottom)) !important;
                padding-left: max(10px, env(safe-area-inset-left)) !important;
                padding-right: max(10px, env(safe-area-inset-right)) !important;
            }

            .tx-modal-card {
                max-height: calc(100dvh - 24px) !important;
                border-radius: 14px !important;
                width: 100% !important;
                margin: auto !important;
            }

            .tx-modal-header {
                padding: 14px 18px !important;
            }

            .tx-modal-body {
                padding: 16px 14px !important;
            }

            .tx-modal-footer {
                padding: 12px 16px !important;
            }
        }

        /* 7. Responsive: Small Mobile & iPhone (< 640px) */
        @media screen and (max-width: 640px) {
            .tx-modal-overlay {
                padding: 8px 6px !important;
                padding-top: max(8px, env(safe-area-inset-top)) !important;
                padding-bottom: max(8px, env(safe-area-inset-bottom)) !important;
                padding-left: max(6px, env(safe-area-inset-left)) !important;
                padding-right: max(6px, env(safe-area-inset-right)) !important;
            }

            .tx-modal-card {
                max-height: calc(100dvh - 16px) !important;
                border-radius: 14px !important;
            }

            .tx-modal-grid {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }

            .tx-modal-header {
                padding: 12px 14px !important;
            }

            .tx-modal-title {
                font-size: 1.1rem !important;
            }

            .tx-modal-body {
                padding: 14px 12px !important;
            }

            .tx-modal-footer {
                padding: 12px 14px !important;
                gap: 8px !important;
            }

            .tx-modal-footer .btn-cancel,
            .tx-modal-footer #cancelTxModalBtn,
            .tx-modal-footer .btn-tx-step,
            .tx-modal-footer #modalActionBtn {
                height: 38px !important;
                min-height: 38px !important;
                max-height: 38px !important;
                line-height: 38px !important;
                padding: 0 16px !important;
                font-size: 0.82rem !important;
            }
        }

        /* 8. Dark Theme Support */
        body.transaction-body.dark-mode-active .tx-modal-card {
            background-color: #141E2D !important;
            border-color: #23344A !important;
        }
        body.transaction-body.dark-mode-active .tx-modal-header,
        body.transaction-body.dark-mode-active .tx-modal-body,
        body.transaction-body.dark-mode-active .tx-modal-footer {
            background-color: #141E2D !important;
            border-color: #23344A !important;
        }
        body.transaction-body.dark-mode-active .tx-modal-footer .btn-cancel,
        body.transaction-body.dark-mode-active .tx-modal-footer #cancelTxModalBtn {
            background-color: #192536 !important;
            border-color: #2A3B52 !important;
            color: #CBD5E1 !important;
        }
        body.transaction-body.dark-mode-active .tx-modal-body::-webkit-scrollbar-thumb {
            background-color: #334155 !important;
        }
        body.transaction-body.dark-mode-active .tx-modal-body::-webkit-scrollbar-thumb:hover {
            background-color: #475569 !important;
        }
    </style>

    <script>
        (function() {
            // 1. Details Modal
            window.openOrderDetailsById = function(orderId) {
                var row = document.getElementById('txRow-' + orderId) || document.getElementById('txCard-' + orderId);
                var txModal = document.getElementById('txModal');
                if (!row || !txModal) return;

                var orderNo = row.getAttribute('data-order') || '-';
                var customer = row.getAttribute('data-customer') || '-';
                var customerPhone = row.getAttribute('data-customer-phone') || '-';
                var status = (row.getAttribute('data-status') || '').toLowerCase().trim();
                var statusLabel = row.getAttribute('data-status-label') || status;
                var tracking = row.getAttribute('data-tracking') || '';
                var amount = row.getAttribute('data-amount') || '0';
                var date = row.getAttribute('data-date') || '-';
                var paidAt = row.getAttribute('data-paid-at') || '-';
                var shippedAt = row.getAttribute('data-shipped-at') || '-';
                var isTerminal = row.getAttribute('data-is-terminal') === '1';

                var modalOrderNo = document.getElementById('modalOrderNo');
                if (modalOrderNo) modalOrderNo.textContent = '#' + orderNo;

                var modalCustomerName = document.getElementById('modalCustomerName');
                if (modalCustomerName) modalCustomerName.textContent = customer;

                var modalCustomerPhone = document.getElementById('modalCustomerPhone');
                if (modalCustomerPhone) modalCustomerPhone.textContent = customerPhone;

                var modalOrderDate = document.getElementById('modalOrderDate');
                if (modalOrderDate) modalOrderDate.textContent = date;

                var modalTotalAmount = document.getElementById('modalTotalAmount');
                if (modalTotalAmount) {
                    var num = parseFloat(String(amount).replace(/[^0-9.]/g, '')) || 0;
                    modalTotalAmount.textContent = 'RM ' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                var modalPaidAt = document.getElementById('modalPaidAt');
                if (modalPaidAt) modalPaidAt.textContent = paidAt;

                var modalStatusBadge = document.getElementById('modalStatusBadge');
                if (modalStatusBadge) {
                    modalStatusBadge.textContent = statusLabel;
                    modalStatusBadge.className = 'badge-status badge-' + status;
                }

                // Stepper
                var detailsStepper = document.getElementById('detailsStepper');
                if (detailsStepper && typeof window.renderStepper === 'function') {
                    window.renderStepper(detailsStepper, status);
                }

                // Tracking Section
                var modalTrackingSection = document.getElementById('modalTrackingSection');
                var modalTrackingNumber = document.getElementById('modalTrackingNumber');
                var modalShippedAt = document.getElementById('modalShippedAt');
                if (modalTrackingSection) {
                    if (tracking) {
                        modalTrackingSection.style.display = 'flex';
                        if (modalTrackingNumber) modalTrackingNumber.textContent = tracking;
                        if (modalShippedAt) modalShippedAt.textContent = shippedAt;
                    } else {
                        modalTrackingSection.style.display = 'none';
                    }
                }

                // Items Container
                var modalItemsContainer = document.getElementById('modalItemsContainer');
                if (modalItemsContainer) {
                    var items = [];
                    try { items = JSON.parse(row.getAttribute('data-items') || '[]'); } catch(e) { items = []; }
                    if (items.length > 0) {
                        var html = '<table class="modal-items-table"><thead><tr><th>Item</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit (RM)</th><th style="text-align:right;">Total (RM)</th></tr></thead><tbody>';
                        items.forEach(function(it) {
                            html += '<tr><td><strong>' + (it.name || '') + '</strong></td><td style="text-align:center;">' + (it.qty || 1) + '</td><td style="text-align:right;">' + (it.price || '0.00') + '</td><td style="text-align:right; font-weight:600;">' + (it.total || '0.00') + '</td></tr>';
                        });
                        html += '</tbody></table>';
                        modalItemsContainer.innerHTML = html;
                    } else {
                        modalItemsContainer.innerHTML = '<div style="color: #64748B; font-size: 0.85rem; padding: 6px 0;">No item details available.</div>';
                    }
                }

                // Payment Status Description
                var modalPaymentStatus = document.getElementById('modalPaymentStatus');
                if (modalPaymentStatus) {
                    if (status === 'completed') {
                        modalPaymentStatus.textContent = 'Order completed and delivered.';
                    } else if (status === 'shipped') {
                        modalPaymentStatus.textContent = 'In transit via courier (Tracking: ' + (tracking || 'Assigned') + ').';
                    } else if (status === 'packed') {
                        modalPaymentStatus.textContent = 'Items packed, ready for shipment.';
                    } else if (status === 'paid') {
                        modalPaymentStatus.textContent = 'Payment confirmed and verified.';
                    } else if (status === 'submitted') {
                        modalPaymentStatus.textContent = 'Order submitted, pending payment confirmation.';
                    } else {
                        modalPaymentStatus.textContent = 'Status: ' + statusLabel;
                    }
                }

                // Action Button inside Modal
                var modalActionBtn = document.getElementById('modalActionBtn');
                if (modalActionBtn) {
                    modalActionBtn.setAttribute('data-order-id', orderId);
                    modalActionBtn.setAttribute('data-order-no', orderNo);
                    modalActionBtn.setAttribute('data-customer', customer);
                    modalActionBtn.setAttribute('data-amount', amount);
                    modalActionBtn.setAttribute('data-tracking', tracking);

                    if (!isTerminal && status !== 'cancelled') {
                        if (status === 'draft') {
                            modalActionBtn.style.display = 'inline-flex';
                            modalActionBtn.style.backgroundColor = '#0D9488';
                            modalActionBtn.style.borderColor = '#0D9488';
                            modalActionBtn.style.color = '#FFFFFF';
                            modalActionBtn.textContent = 'Submit Order';
                            modalActionBtn.setAttribute('data-action', 'submit');
                            modalActionBtn.className = 'btn-tx-step btn-step-paid';
                        } else if (status === 'submitted') {
                            modalActionBtn.style.display = 'inline-flex';
                            modalActionBtn.style.backgroundColor = '#0D9488';
                            modalActionBtn.style.borderColor = '#0D9488';
                            modalActionBtn.style.color = '#FFFFFF';
                            modalActionBtn.textContent = 'Mark Paid';
                            modalActionBtn.setAttribute('data-action', 'mark-paid');
                            modalActionBtn.className = 'btn-tx-step btn-step-paid';
                        } else if (status === 'paid' || status === 'packed') {
                            modalActionBtn.style.display = 'inline-flex';
                            modalActionBtn.style.backgroundColor = '#2563EB';
                            modalActionBtn.style.borderColor = '#2563EB';
                            modalActionBtn.style.color = '#FFFFFF';
                            modalActionBtn.textContent = 'Ship Order';
                            modalActionBtn.setAttribute('data-action', 'ship');
                            modalActionBtn.className = 'btn-tx-step btn-step-ship';
                        } else if (status === 'shipped') {
                            modalActionBtn.style.display = 'inline-flex';
                            modalActionBtn.style.backgroundColor = '#059669';
                            modalActionBtn.style.borderColor = '#059669';
                            modalActionBtn.style.color = '#FFFFFF';
                            modalActionBtn.textContent = 'Mark Completed';
                            modalActionBtn.setAttribute('data-action', 'complete');
                            modalActionBtn.className = 'btn-tx-step btn-step-complete';
                        } else {
                            modalActionBtn.style.display = 'none';
                        }
                    } else {
                        modalActionBtn.style.display = 'none';
                    }
                }

                txModal.style.display = 'flex';
                txModal.style.opacity = '1';
                txModal.style.visibility = 'visible';
                txModal.style.pointerEvents = 'auto';
                txModal.classList.add('active');
                txModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                var modalBody = txModal.querySelector('.tx-modal-body');
                if (modalBody) modalBody.scrollTop = 0;
            };

            window.closeTxModal = function() {
                var txModal = document.getElementById('txModal');
                if (!txModal) return;
                txModal.classList.remove('active');
                txModal.style.display = 'none';
                txModal.style.opacity = '0';
                txModal.style.visibility = 'hidden';
                txModal.style.pointerEvents = 'none';
                txModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            // 2. Action Button Click Handler (inside Details Modal)
            window.handleModalActionClick = function(btn, e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                var targetBtn = btn || document.getElementById('modalActionBtn');
                if (!targetBtn) return;

                var orderId = targetBtn.getAttribute('data-order-id');
                var orderNo = targetBtn.getAttribute('data-order-no');
                var customer = targetBtn.getAttribute('data-customer');
                var amount = targetBtn.getAttribute('data-amount');
                var tracking = targetBtn.getAttribute('data-tracking');
                var action = (targetBtn.getAttribute('data-action') || 'mark-paid').toLowerCase();

                window.closeTxModal();

                if (action === 'mark-paid' || action === 'submitted') {
                    window.openMarkPaidModal(orderId, orderNo, customer, amount);
                } else if (action === 'ship' || action === 'paid' || action === 'packed') {
                    window.openShipOrderModal(orderId, orderNo, customer, tracking);
                } else if (action === 'complete' || action === 'shipped') {
                    window.openQuickStatusUpdate(orderId, orderNo, 'completed', 'Completed');
                } else if (action === 'submit' || action === 'draft') {
                    window.openQuickStatusUpdate(orderId, orderNo, 'submitted', 'Submitted');
                }
            };

            // 3. Mark Paid Modal
            window.openMarkPaidModal = function(orderId, orderNo, customerName, totalAmount) {
                var modal = document.getElementById('markPaidModal');
                if (!modal) return;

                var orderIdInput = document.getElementById('markPaidOrderId');
                if (orderIdInput) orderIdInput.value = orderId;

                var title = document.getElementById('markPaidOrderTitle');
                if (title) title.textContent = 'Confirm Payment - #' + (orderNo || '');

                var cust = document.getElementById('markPaidCustomer');
                if (cust) cust.textContent = customerName || '-';

                var amt = document.getElementById('markPaidAmount');
                if (amt) {
                    var clean = String(totalAmount || '0').replace(/[^0-9.]/g, '');
                    var num = parseFloat(clean) || 0;
                    amt.textContent = 'RM ' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                var refInput = document.getElementById('paymentReferenceInput');
                if (refInput) {
                    refInput.value = '';
                    setTimeout(function() { refInput.focus(); }, 120);
                }

                var errBox = document.getElementById('markPaidModalError');
                if (errBox) {
                    errBox.style.display = 'none';
                    errBox.textContent = '';
                }

                var submitBtn = document.getElementById('submitMarkPaidBtn');
                var submitBtnText = document.getElementById('submitMarkPaidBtnText');
                if (submitBtn) submitBtn.disabled = false;
                if (submitBtnText) submitBtnText.textContent = 'Confirm & Mark Paid';

                modal.style.display = 'flex';
                modal.style.opacity = '1';
                modal.style.visibility = 'visible';
                modal.style.pointerEvents = 'auto';
                modal.style.zIndex = '1060';
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                var modalBody = modal.querySelector('.tx-modal-body');
                if (modalBody) modalBody.scrollTop = 0;
            };

            window.closeMarkPaidModal = function() {
                var modal = document.getElementById('markPaidModal');
                if (!modal) return;
                modal.classList.remove('active');
                modal.style.display = 'none';
                modal.style.opacity = '0';
                modal.style.visibility = 'hidden';
                modal.style.pointerEvents = 'none';
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            // 4. Ship Order Modal
            window.openShipOrderModal = function(orderId, orderNo, customerName, currentTracking) {
                var modal = document.getElementById('shipOrderModal');
                if (!modal) return;

                var idInput = document.getElementById('shipOrderId');
                if (idInput) idInput.value = orderId;

                var title = document.getElementById('shipOrderTitle');
                if (title) title.textContent = 'Ship Order - #' + (orderNo || '');

                var cust = document.getElementById('shipOrderCustomer');
                if (cust) cust.textContent = customerName || '-';

                var trackingInput = document.getElementById('shipTrackingNumberInput');
                if (trackingInput) {
                    trackingInput.value = currentTracking || '';
                    setTimeout(function() { trackingInput.focus(); }, 120);
                }

                var providerSelect = document.getElementById('shipDeliveryProviderSelect');
                if (providerSelect) providerSelect.value = '';

                var customWrap = document.getElementById('customProviderWrap');
                if (customWrap) customWrap.style.display = 'none';

                var errBox = document.getElementById('shipOrderModalError');
                if (errBox) {
                    errBox.style.display = 'none';
                    errBox.textContent = '';
                }

                var submitBtn = document.getElementById('submitShipOrderBtn');
                var submitBtnText = document.getElementById('submitShipOrderBtnText');
                if (submitBtn) submitBtn.disabled = false;
                if (submitBtnText) submitBtnText.textContent = 'Confirm Shipment';

                modal.style.display = 'flex';
                modal.style.opacity = '1';
                modal.style.visibility = 'visible';
                modal.style.pointerEvents = 'auto';
                modal.style.zIndex = '1060';
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                var modalBody = modal.querySelector('.tx-modal-body');
                if (modalBody) modalBody.scrollTop = 0;
            };

            window.closeShipOrderModal = function() {
                var modal = document.getElementById('shipOrderModal');
                if (!modal) return;
                modal.classList.remove('active');
                modal.style.display = 'none';
                modal.style.opacity = '0';
                modal.style.visibility = 'hidden';
                modal.style.pointerEvents = 'none';
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            // 5. Submit Handlers
            window.handleMarkPaidSubmit = async function(e) {
                if (e) e.preventDefault();
                var orderId = document.getElementById('markPaidOrderId') ? document.getElementById('markPaidOrderId').value : '';
                if (!orderId) return;

                var refInput = document.getElementById('paymentReferenceInput');
                var refNo = refInput ? refInput.value.trim() : '';
                var errBox = document.getElementById('markPaidModalError');

                if (!refNo) {
                    if (errBox) {
                        errBox.textContent = 'Please enter a payment reference number.';
                        errBox.style.display = 'block';
                    }
                    if (refInput) refInput.focus();
                    return;
                }

                var pTypeSelect = document.getElementById('paymentMethodSelect');
                var pType = pTypeSelect ? pTypeSelect.value : 'bank_transfer';

                var submitBtn = document.getElementById('submitMarkPaidBtn');
                var submitBtnText = document.getElementById('submitMarkPaidBtnText');
                if (submitBtn) submitBtn.disabled = true;
                if (submitBtnText) submitBtnText.textContent = 'Recording Payment...';

                try {
                    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    var response = await fetch('/business/transactions/' + orderId + '/status', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            status: 'paid',
                            reference_no: refNo,
                            payment_type: pType
                        })
                    });

                    var data = await response.json();
                    if (!response.ok || !data.success) {
                        if (errBox) {
                            errBox.textContent = data.message || 'Failed to update payment status.';
                            errBox.style.display = 'block';
                        }
                        if (submitBtn) submitBtn.disabled = false;
                        if (submitBtnText) submitBtnText.textContent = 'Confirm & Mark Paid';
                        return;
                    }

                    window.closeMarkPaidModal();
                    window.location.reload();
                } catch(err) {
                    if (errBox) {
                        errBox.textContent = 'Network error occurred. Please try again.';
                        errBox.style.display = 'block';
                    }
                    if (submitBtn) submitBtn.disabled = false;
                    if (submitBtnText) submitBtnText.textContent = 'Confirm & Mark Paid';
                }
            };

            window.handleShipOrderSubmit = async function(e) {
                if (e) e.preventDefault();
                var orderId = document.getElementById('shipOrderId') ? document.getElementById('shipOrderId').value : '';
                if (!orderId) return;

                var provSelect = document.getElementById('shipDeliveryProviderSelect');
                var custInput = document.getElementById('customProviderInput');
                var provider = (provSelect && provSelect.value === 'Other') ? (custInput ? custInput.value.trim() : '') : (provSelect ? provSelect.value : '');

                var trackInput = document.getElementById('shipTrackingNumberInput');
                var tracking = trackInput ? trackInput.value.trim() : '';

                var errBox = document.getElementById('shipOrderModalError');
                if (!tracking) {
                    if (errBox) {
                        errBox.textContent = 'Please enter a shipping tracking number.';
                        errBox.style.display = 'block';
                    }
                    if (trackInput) trackInput.focus();
                    return;
                }

                var submitBtn = document.getElementById('submitShipOrderBtn');
                var submitBtnText = document.getElementById('submitShipOrderBtnText');
                if (submitBtn) submitBtn.disabled = true;
                if (submitBtnText) submitBtnText.textContent = 'Confirming Shipment...';

                try {
                    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    var response = await fetch('/business/transactions/' + orderId + '/status', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            status: 'shipped',
                            delivery_provider: provider,
                            tracking_number: tracking
                        })
                    });

                    var data = await response.json();
                    if (!response.ok || !data.success) {
                        if (errBox) {
                            errBox.textContent = data.message || 'Failed to update shipping status.';
                            errBox.style.display = 'block';
                        }
                        if (submitBtn) submitBtn.disabled = false;
                        if (submitBtnText) submitBtnText.textContent = 'Confirm Shipment';
                        return;
                    }

                    window.closeShipOrderModal();
                    window.location.reload();
                } catch(err) {
                    if (errBox) {
                        errBox.textContent = 'Network error occurred. Please try again.';
                        errBox.style.display = 'block';
                    }
                    if (submitBtn) submitBtn.disabled = false;
                    if (submitBtnText) submitBtnText.textContent = 'Confirm Shipment';
                }
            };

            // Overlay backdrop click to close modal
            ['txModal', 'updateStatusModal', 'markPaidModal', 'shipOrderModal'].forEach(function(modalId) {
                var m = document.getElementById(modalId);
                if (m) {
                    m.addEventListener('click', function(e) {
                        if (e.target === m) {
                            if (modalId === 'txModal' && window.closeTxModal) window.closeTxModal();
                            else if (modalId === 'markPaidModal' && window.closeMarkPaidModal) window.closeMarkPaidModal();
                            else if (modalId === 'shipOrderModal' && window.closeShipOrderModal) window.closeShipOrderModal();
                            else if (modalId === 'updateStatusModal') {
                                if (window.closeUpdateStatusModal) window.closeUpdateStatusModal();
                                else {
                                    m.classList.remove('active');
                                    m.style.display = 'none';
                                    document.body.style.overflow = '';
                                }
                            }
                        }
                    });
                }
            });
        })();
    </script>

    @push('scripts')
    <script src="{{ asset('assets/js/transaction.v2.js') }}?v={{ file_exists(public_path('assets/js/transaction.v2.js')) ? filemtime(public_path('assets/js/transaction.v2.js')) : time() }}"></script>
    @endpush

</x-business-layout>
