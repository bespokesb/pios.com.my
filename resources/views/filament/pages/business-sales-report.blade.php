<x-filament-panels::page>
    @php($report = $this->report)

    <div class="flex flex-wrap items-end gap-4">
        <label class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('pios.report.sales.period') }}</span>
            <select wire:model.live="period"
                class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5">
                <option value="daily">{{ __('pios.report.sales.daily') }}</option>
                <option value="weekly">{{ __('pios.report.sales.weekly') }}</option>
            </select>
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('pios.report.sales.date') }}</span>
            <input type="date" wire:model.live="date"
                class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-white/5">
        </label>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $report['label'] }}</p>

        <div class="mt-4 grid grid-cols-2 gap-4 sm:max-w-md">
            <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('pios.report.sales.orders') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($report['orders']) }}</p>
            </div>
            <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-500/10">
                <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('pios.report.sales.revenue') }}</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">RM {{ number_format($report['revenue'], 2) }}</p>
            </div>
        </div>

        @if($report['period'] === 'weekly')
            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <th class="py-2 pr-4">{{ __('pios.report.sales.day') }}</th>
                            <th class="py-2 pr-4 text-right">{{ __('pios.report.sales.orders') }}</th>
                            <th class="py-2 text-right">{{ __('pios.report.sales.revenue') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $row)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4 text-gray-800 dark:text-gray-200">{{ $row['label'] }}</td>
                                <td class="py-2 pr-4 text-right text-gray-800 dark:text-gray-200">{{ number_format($row['orders']) }}</td>
                                <td class="py-2 text-right text-gray-800 dark:text-gray-200">RM {{ number_format($row['revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <p class="mt-4 text-xs text-gray-400">{{ __('pios.report.sales.note') }}</p>
    </div>
</x-filament-panels::page>
