<x-filament-panels::page>
    @php
        $reports = $this->reports;
        $summary = $reports['summary'];
        $activeFilterCount = $this->activeFilterCount;

        $moneyTotal = $summary['fee_total'] + $summary['order_total'];
        $collectionRate = $moneyTotal > 0 ? min(100, ($summary['payment_total'] / $moneyTotal) * 100) : 0;
        $vipShare = $summary['customer_count'] > 0 ? min(100, ($summary['vip_customer_count'] / $summary['customer_count']) * 100) : 0;

        $statCards = [
            [
                'label' => 'Business Owners',
                'value' => number_format($summary['business_owner_count']),
                'detail' => 'Registered operators in the current hierarchy',
                'icon' => 'heroicon-m-building-storefront',
                'accent' => '#215F9A',
            ],
            [
                'label' => 'Customers',
                'value' => number_format($summary['customer_count']),
                'detail' => number_format($vipShare, 1).'% VIP / LOS customer mix',
                'icon' => 'heroicon-m-users',
                'accent' => '#059669',
            ],
            [
                'label' => 'VIP Customers',
                'value' => number_format($summary['vip_customer_count']),
                'detail' => 'VIP-tagged customers',
                'icon' => 'heroicon-m-identification',
                'accent' => '#7c3aed',
            ],
            [
                'label' => 'Orders',
                'value' => number_format($summary['order_count']),
                'detail' => 'PIOS Mart orders in selected scope',
                'icon' => 'heroicon-m-shopping-bag',
                'accent' => '#d97706',
            ],
        ];

        $moneyCards = [
            ['label' => 'Fee Total', 'value' => $summary['fee_total'], 'detail' => 'Manual fees raised', 'accent' => '#215F9A'],
            ['label' => 'Order Total', 'value' => $summary['order_total'], 'detail' => 'PIOS Mart transaction value', 'accent' => '#d97706'],
            ['label' => 'Payment Total', 'value' => $summary['payment_total'], 'detail' => number_format($collectionRate, 1).'% collection coverage', 'accent' => '#059669'],
        ];
    @endphp

    <div class="pios-report pios-shell">
        <section class="pios-hero">
            <div class="pios-hero-grid">
                <div>
                    <div class="pios-kicker">
                        <x-filament::icon icon="heroicon-m-chart-bar-square" />
                        Operational reports
                    </div>

                    <h2 class="pios-hero-title">PIOS hierarchy performance dashboard</h2>

                    <p class="pios-hero-copy">
                        Ownership scope, customer eligibility, fees, collections, and PIOS Mart order value in one operating view.
                    </p>
                </div>

                <aside class="pios-hero-aside">
                    <div class="pios-hero-meta">Fees + orders in current scope</div>
                    <div class="pios-hero-total">RM {{ number_format($moneyTotal, 2) }}</div>

                    <div class="mt-5 space-y-2">
                        <div class="flex items-center justify-between gap-3 text-sm font-semibold">
                            <span>Collection coverage</span>
                            <span>{{ number_format($collectionRate, 1) }}%</span>
                        </div>
                        <div class="pios-progress" style="--value: {{ $collectionRate }}%">
                            <span></span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="pios-hero-meta">Payments</div>
                            <div class="font-semibold">RM {{ number_format($summary['payment_total'], 2) }}</div>
                        </div>
                        <div>
                            <div class="pios-hero-meta">VIP mix</div>
                            <div class="font-semibold">{{ number_format($vipShare, 1) }}%</div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="pios-filter-panel">
            <div class="pios-filter-header">
                <div>
                    <div class="pios-section-eyebrow">Scope controls</div>
                    <h3 class="pios-panel-title">Report filters</h3>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="pios-chip">{{ $activeFilterCount }} active {{ str('filter')->plural($activeFilterCount) }}</span>
                    <x-filament::button
                        color="gray"
                        icon="heroicon-m-arrow-path"
                        size="sm"
                        wire:click="clearFilters"
                        :disabled="! $this->hasActiveFilters"
                    >
                        Reset
                    </x-filament::button>
                </div>
            </div>

            <div class="pios-filter-grid">
                <label>
                    <span class="pios-label">Date From</span>
                    <input type="date" wire:model.live="date_from" class="pios-input" />
                </label>

                <label>
                    <span class="pios-label">Date To</span>
                    <input type="date" wire:model.live="date_to" class="pios-input" />
                </label>

                <label>
                    <span class="pios-label">Partner</span>
                    <select wire:model.live="partner_id" class="pios-input">
                        <option value="">All partners</option>
                        @foreach ($this->partnerOptions() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="pios-label">Business Owner</span>
                    <select wire:model.live="business_owner_id" class="pios-input">
                        <option value="">All business owners</option>
                        @foreach ($this->businessOwnerOptions() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="pios-label">Customer</span>
                    <select wire:model.live="customer_category" class="pios-input">
                        <option value="">All categories</option>
                        @foreach ($this->customerCategoryOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="pios-label">Category</span>
                    <select wire:model.live="product_type_id" class="pios-input">
                        <option value="">All types</option>
                        @foreach ($this->productTypeOptions() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="pios-label">Territory</span>
                    <select wire:model.live="territory_id" class="pios-input">
                        <option value="">All territories</option>
                        @foreach ($this->territoryOptions() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="pios-metric-grid">
            @foreach ($statCards as $card)
                <article class="pios-metric" style="--accent: {{ $card['accent'] }}">
                    <div class="pios-metric-top">
                        <div>
                            <div class="pios-metric-label">{{ $card['label'] }}</div>
                            <div class="pios-metric-value">{{ $card['value'] }}</div>
                        </div>
                        <span class="pios-icon">
                            <x-filament::icon :icon="$card['icon']" />
                        </span>
                    </div>
                    <p class="pios-metric-detail">{{ $card['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="pios-money-grid">
            @foreach ($moneyCards as $card)
                <article class="pios-money-card" style="--accent: {{ $card['accent'] }}">
                    <div class="pios-money-label">{{ $card['label'] }}</div>
                    <div class="pios-money-value">RM {{ number_format($card['value'], 2) }}</div>
                    <p class="pios-money-detail">{{ $card['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="pios-section-grid">
            <article class="pios-panel">
                <div class="pios-panel-header">
                    <div>
                        <div class="pios-section-eyebrow">Network</div>
                        <h3 class="pios-panel-title">Recent Business Owners</h3>
                    </div>
                    <span class="pios-chip">{{ number_format($summary['business_owner_count']) }} total</span>
                </div>

                <div class="pios-table-wrap">
                    <table class="pios-table">
                        <thead>
                            <tr>
                                <th>Business Owner</th>
                                <th>Partner</th>
                                <th>Territory</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports['business_owners'] as $businessOwner)
                                <tr>
                                    <td>
                                        <div class="pios-main-text">{{ $businessOwner->applicant_name }}</div>
                                        <div class="pios-sub-text">{{ $businessOwner->business_owner_no }}</div>
                                    </td>
                                    <td>{{ $businessOwner->partner?->applicant_name ?? '-' }}</td>
                                    <td>{{ $businessOwner->territory?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="pios-empty">No business owners match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="pios-panel">
                <div class="pios-panel-header">
                    <div>
                        <div class="pios-section-eyebrow">Customers</div>
                        <h3 class="pios-panel-title">Recent Contacts</h3>
                    </div>
                    <span class="pios-chip">{{ number_format($summary['customer_count']) }} total</span>
                </div>

                <div class="pios-table-wrap">
                    <table class="pios-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Category</th>
                                <th>Business Owner</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports['customers'] as $customer)
                                <tr>
                                    <td>
                                        <div class="pios-main-text">{{ $customer->name }}</div>
                                        <div class="pios-sub-text">{{ $customer->customer_no }}</div>
                                    </td>
                                    <td>
                                        <span class="{{ $customer->category->value === 'vip_customer' ? 'pios-badge pios-badge-violet' : 'pios-badge' }}">
                                            {{ $customer->category->label() }}
                                        </span>
                                    </td>
                                    <td>{{ $customer->businessOwner?->applicant_name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="pios-empty">No customers match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="pios-section-grid pios-section-grid-wide">
            <article class="pios-panel" data-section="partner-quota">
                <div class="pios-panel-header">
                    <div>
                        <div class="pios-section-eyebrow">Quota</div>
                        <h3 class="pios-panel-title">{{ __('pios.widgets.partner_quota_title') }}</h3>
                    </div>
                    <span class="pios-chip">{{ now('Asia/Kuala_Lumpur')->translatedFormat('F Y') }}</span>
                </div>

                <div class="pios-table-wrap">
                    <table class="pios-table">
                        <thead>
                            <tr>
                                <th>{{ __('pios.widgets.partner_quota_partner') }}</th>
                                <th class="text-right">{{ __('pios.widgets.partner_quota_customers') }}</th>
                                <th class="text-right">{{ __('pios.widgets.partner_quota_target') }}</th>
                                <th class="text-right">{{ __('pios.widgets.partner_quota_attainment') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->partnerQuotaAttainment as $row)
                                <tr>
                                    <td>
                                        <div class="pios-main-text">{{ $row['partner'] }}</div>
                                        <div class="pios-sub-text">{{ $row['partner_no'] ?? '-' }}</div>
                                    </td>
                                    <td class="text-right font-semibold">{{ number_format($row['count']) }}</td>
                                    <td class="text-right">{{ number_format($row['target']) }}</td>
                                    <td class="text-right">
                                        <span class="{{ $row['percent'] >= 100 ? 'pios-badge' : ($row['percent'] >= 50 ? 'pios-badge pios-badge-violet' : 'pios-badge pios-badge-amber') }}">
                                            {{ $row['percent'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="pios-empty">{{ __('pios.widgets.partner_quota_empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="pios-panel">
                <div class="pios-panel-header">
                    <div>
                        <div class="pios-section-eyebrow">PIOS Mart</div>
                        <h3 class="pios-panel-title">Recent Orders</h3>
                    </div>
                    <span class="pios-chip">RM {{ number_format($summary['order_total'], 2) }}</span>
                </div>

                <div class="pios-table-wrap">
                    <table class="pios-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports['orders'] as $order)
                                <tr>
                                    <td>
                                        <div class="pios-main-text">{{ $order->order_no }}</div>
                                        <div class="pios-sub-text">{{ $order->businessOwner?->applicant_name ?? '-' }}</div>
                                    </td>
                                    <td>{{ $order->customer?->name ?? '-' }}</td>
                                    <td>
                                        <span class="{{ in_array($order->status->value, ['draft', 'submitted'], true) ? 'pios-badge pios-badge-amber' : 'pios-badge' }}">
                                            {{ $order->status->label() }}
                                        </span>
                                    </td>
                                    <td class="text-right font-semibold">RM {{ number_format((float) $order->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="pios-empty">No orders match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="pios-panel">
                <div class="pios-panel-header">
                    <div>
                        <div class="pios-section-eyebrow">Finance</div>
                        <h3 class="pios-panel-title">Money Movement</h3>
                    </div>
                    <span class="pios-chip">Recent</span>
                </div>

                <div class="pios-stack-list">
                    <div>
                        <div class="pios-section-eyebrow mb-2">Fees</div>
                        <div class="grid gap-2">
                            @forelse ($reports['fees']->take(5) as $fee)
                                <div class="pios-stack-item">
                                    <div>
                                        <div class="pios-main-text">{{ $fee->fee_type->label() }}</div>
                                        <div class="pios-sub-text">{{ $fee->businessOwner?->applicant_name ?? $fee->partner?->applicant_name ?? $fee->customer?->name ?? '-' }}</div>
                                    </div>
                                    <div class="pios-stack-value">
                                        RM {{ number_format((float) $fee->amount, 2) }}
                                        <div class="pios-sub-text">{{ $fee->status->label() }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="pios-empty">No fees match these filters.</div>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <div class="pios-section-eyebrow mb-2">Payments</div>
                        <div class="grid gap-2">
                            @forelse ($reports['payments']->take(5) as $payment)
                                <div class="pios-stack-item">
                                    <div>
                                        <div class="pios-main-text">{{ $payment->reference_no ?: 'Manual payment' }}</div>
                                        <div class="pios-sub-text">{{ $payment->order?->order_no ?? $payment->fee?->fee_type?->label() ?? '-' }}</div>
                                    </div>
                                    <div class="pios-stack-value">
                                        RM {{ number_format((float) $payment->amount, 2) }}
                                        <div class="pios-sub-text">{{ $payment->status->label() }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="pios-empty">No payments match these filters.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
