<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Partners\PartnerResource;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Partner;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Owner/HQ view of the ENTIRE partner network as a modern, centered org chart
 * (d3-org-chart via a dedicated Vite entry) — pan/zoom, expand/collapse,
 * client-side search, and rich per-partner node cards. The page itself stays a
 * Filament-native Livewire page; the chart is plain JS fed by embedded JSON.
 *
 * Regular partners never see this page — their view stays the direct-children
 * widget on their own partner record (max 4), per the downline visibility rule.
 */
class PartnerNetworkTree extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static string|UnitEnum|null $navigationGroup = 'Register';

    protected string $view = 'filament.pages.partner-network-tree';

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.register');
    }

    public static function getNavigationLabel(): string
    {
        return static::isPartnerPanel()
            ? __('pios.partner.network.my_title')
            : __('pios.partner.network.title');
    }

    public function getTitle(): string
    {
        return static::isPartnerPanel()
            ? __('pios.partner.network.my_title')
            : __('pios.partner.network.title');
    }

    public function getSubheading(): ?string
    {
        return static::isPartnerPanel()
            ? __('pios.partner.network.my_subtitle')
            : __('pios.partner.network.subtitle');
    }

    /**
     * Admin panel: owners/HQ see the whole forest. Partner panel: a partner
     * sees their OWN line, rooted at themselves.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return match (Filament::getCurrentPanel()?->getId()) {
            'admin' => $user?->isAdmin() ?? false,
            'partner' => $user?->isPartner() ?? false,
            default => false,
        };
    }

    protected static function isPartnerPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'partner';
    }

    /**
     * The whole forest as a flat node list for d3-org-chart: three grouped
     * queries total (partners, BO counts, customer counts); occupied-slot and
     * deep-downline numbers computed in PHP. A virtual HQ root anchors the
     * multi-root forest so the chart renders one centered tree.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        // Partner panel: scope strictly to the viewer's own line (self + all
        // descendants) with themselves as the tree's top. A partner-panel user
        // without a linked profile sees nothing.
        $own = static::isPartnerPanel() ? auth()->user()?->partner : null;

        if (static::isPartnerPanel() && ! $own) {
            return ['nodes' => collect(), 'total' => 0];
        }

        $partners = Partner::query()
            ->with('partnerTier')
            ->when($own, fn ($query) => $query->whereIn('id', array_merge([$own->getKey()], $own->descendantIds())))
            ->orderBy('partner_no')
            ->get();

        $byParent = $partners->groupBy(fn (Partner $p): int => (int) $p->parent_partner_id);

        $occupied = $partners
            ->filter(fn (Partner $p): bool => in_array($p->status, Partner::SLOT_OCCUPYING_STATUSES, true))
            ->groupBy(fn (Partner $p): int => (int) $p->parent_partner_id)
            ->map->count();

        $downline = [];
        $countDownline = function (int $id) use (&$countDownline, &$downline, $byParent): int {
            if (isset($downline[$id])) {
                return $downline[$id];
            }

            $total = 0;
            foreach ($byParent->get($id, collect()) as $child) {
                $total += 1 + $countDownline($child->getKey());
            }

            return $downline[$id] = $total;
        };
        $partners->each(fn (Partner $p) => $countDownline($p->getKey()));

        $subtreeIds = $partners->modelKeys();

        $boCounts = BusinessOwner::query()
            ->when($own, fn ($query) => $query->whereIn('partner_id', $subtreeIds))
            ->selectRaw('partner_id, COUNT(*) as c')
            ->groupBy('partner_id')
            ->pluck('c', 'partner_id');

        $customerCounts = Customer::query()
            ->when($own, fn ($query) => $query->whereIn('partner_id', $subtreeIds))
            ->selectRaw('partner_id, COUNT(*) as c')
            ->groupBy('partner_id')
            ->pluck('c', 'partner_id');

        $labels = [
            'slots' => __('pios.partner.network.card.slots'),
            'downline' => __('pios.partner.network.card.downline'),
            'bos' => __('pios.partner.network.card.bos'),
            'customers' => __('pios.partner.network.card.customers'),
        ];

        $nodes = $partners->map(function (Partner $p) use ($own, $occupied, $downline, $boCounts, $customerCounts, $labels): array {
            $used = (int) ($occupied[$p->getKey()] ?? 0);

            return [
                'id' => $p->getKey(),
                // Partner mode: the viewer IS the top of the tree.
                'parentId' => $own && $p->getKey() === $own->getKey()
                    ? ''
                    : ($p->parent_partner_id ?: 'hq'),
                'isRoot' => false,
                'name' => (string) $p->applicant_name,
                'initials' => Str::of((string) $p->applicant_name)
                    ->explode(' ')
                    ->filter()
                    ->take(2)
                    ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
                    ->implode(''),
                'partnerNo' => (string) $p->partner_no,
                'tier' => $p->partnerTier?->name,
                'status' => $p->status?->value ?? 'unknown',
                'used' => $used,
                'max' => Partner::MAX_DIRECT_PARTNERS,
                'hasOpenSlot' => $used < Partner::MAX_DIRECT_PARTNERS,
                'downline' => $downline[$p->getKey()] ?? 0,
                'bos' => (int) ($boCounts[$p->getKey()] ?? 0),
                'customers' => (int) ($customerCounts[$p->getKey()] ?? 0),
                // Only admins may open other partners' edit pages.
                'url' => $own ? null : PartnerResource::getUrl('edit', ['record' => $p]),
                'labels' => $labels,
            ];
        });

        if (! $own) {
            $nodes->prepend([
                'id' => 'hq',
                'parentId' => '',
                'isRoot' => true,
                'name' => __('pios.partner.network.hq'),
                'subtitle' => trans_choice('pios.partner.network.total', $partners->count(), ['count' => $partners->count()]),
                'url' => null,
            ]);
        }

        return [
            'nodes' => $nodes->values(),
            'total' => $partners->count(),
        ];
    }
}
