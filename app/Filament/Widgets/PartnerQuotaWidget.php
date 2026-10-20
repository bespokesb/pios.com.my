<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\PartnerQuotaService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Partner-panel stats widget showing the logged-in partner's month-to-date
 * customer count vs the configured target and percent progress.
 *
 * Advisory only: it reads ONLY the current partner's own number via
 * PartnerQuotaService (Plan 01) and never another partner's. It overrides
 * canView() so it renders exclusively for an actual partner — this also
 * prevents the AdminPanelProvider discoverWidgets() auto-registration from
 * mounting it (with empty/misleading data) on the /admin dashboard.
 */
class PartnerQuotaWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isPartner() && $user->partner !== null;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->partner === null) {
            return [];
        }

        $attainment = app(PartnerQuotaService::class)->attainment($user->partner);

        return [
            Stat::make(
                __('pios.widgets.monthly_quota'),
                $attainment['count'].' / '.$attainment['target'],
            )
                ->description($attainment['percent'].'% '.__('pios.widgets.monthly_quota_hint'))
                ->descriptionIcon('heroicon-m-flag')
                ->color($attainment['percent'] >= 100 ? 'success' : ($attainment['percent'] >= 50 ? 'primary' : 'warning')),
        ];
    }
}
