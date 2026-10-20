<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('pios.partner.downline.children_title')"
        icon="heroicon-o-share"
        collapsible
    >
        <x-slot name="description">
            {{ trans_choice('pios.partner.downline.children_count', $total, ['count' => $total]) }}
        </x-slot>

        @if ($total === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('pios.partner.downline.empty') }}
            </p>
        @else
            <div class="space-y-0.5">
                @foreach ($rows as $row)
                    <a
                        href="{{ $row['url'] }}"
                        @class([
                            'flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg px-2 py-1.5 text-sm transition hover:bg-gray-50 dark:hover:bg-white/5',
                            'ring-1 ring-success-300 bg-success-50 dark:bg-success-500/10 dark:ring-success-500/40' => $row['has_open_slot'],
                        ])
                    >
                        <span class="text-gray-300 dark:text-gray-600">&#9492;&#9472;</span>
                        <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row['partner_no'] }}</span>
                        <span class="font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</span>

                        @if ($row['tier'])
                            <x-filament::badge color="gray" size="sm">{{ $row['tier'] }}</x-filament::badge>
                        @endif

                        @if ($row['status'])
                            <x-filament::badge
                                :color="match ($row['status']) {
                                    'active' => 'success',
                                    'pending' => 'warning',
                                    'suspended' => 'gray',
                                    'inactive' => 'danger',
                                    default => 'gray',
                                }"
                                size="sm"
                            >{{ ucfirst($row['status']) }}</x-filament::badge>
                        @endif

                        <x-filament::badge :color="$row['has_open_slot'] ? 'success' : 'danger'" size="sm">
                            {{ $row['used'] }}/{{ $row['max'] }} {{ __('pios.partner.downline.direct_slots') }}
                        </x-filament::badge>

                        @if ($row['downline'] > 0)
                            <x-filament::badge color="info" size="sm">
                                +{{ $row['downline'] }} {{ __('pios.partner.downline.count') }}
                            </x-filament::badge>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
