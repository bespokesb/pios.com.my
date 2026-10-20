<x-filament-panels::page>
    <x-filament::section>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <div class="min-w-64 flex-1">
                <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                    <x-filament::input
                        id="pt-search"
                        type="search"
                        :placeholder="__('pios.partner.network.search')"
                    />
                </x-filament::input.wrapper>
            </div>

            <x-filament::button id="pt-expand" color="gray" size="sm" icon="heroicon-o-arrows-pointing-out" tag="button" type="button">
                {{ __('pios.partner.network.expand_all') }}
            </x-filament::button>

            <x-filament::button id="pt-collapse" color="gray" size="sm" icon="heroicon-o-arrows-pointing-in" tag="button" type="button">
                {{ __('pios.partner.network.collapse_all') }}
            </x-filament::button>

            <x-filament::button id="pt-fit" color="gray" size="sm" icon="heroicon-o-viewfinder-circle" tag="button" type="button">
                {{ __('pios.partner.network.fit') }}
            </x-filament::button>

            <x-filament::badge color="info">
                {{ trans_choice('pios.partner.network.total', $total, ['count' => $total]) }}
            </x-filament::badge>
        </div>

        @if ($total === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('pios.partner.downline.empty') }}
            </p>
        @else
            {{-- The org chart canvas: pan with drag, zoom with scroll, click a card to open the partner. --}}
            <div
                id="partner-tree"
                class="h-[42rem] w-full overflow-hidden rounded-xl bg-gray-50 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
            ></div>

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                {{ __('pios.partner.network.hint') }}
            </p>

            <script type="application/json" id="partner-tree-data">@json($nodes)</script>
            @vite('resources/js/partner-tree.js')
        @endif
    </x-filament::section>
</x-filament-panels::page>
