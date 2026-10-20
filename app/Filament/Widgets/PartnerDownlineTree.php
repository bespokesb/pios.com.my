<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Partners\PartnerResource;
use App\Models\Partner;
use App\Models\User;
use Filament\Widgets\Widget;

/**
 * Footer widget on the Partner edit page for PARTNER viewers: their direct
 * sub-partners only (max 4), per the downline visibility rule. Owners/HQ get
 * the full interactive network on the dedicated PartnerNetworkTree page
 * instead, so this widget hides for admins.
 */
class PartnerDownlineTree extends Widget
{
    protected string $view = 'filament.widgets.partner-downline-tree';

    protected int|string|array $columnSpan = 'full';

    public ?Partner $record = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isPartner() && ! $user->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        if (! $this->record instanceof Partner) {
            return ['rows' => [], 'total' => 0];
        }

        // Fetch this partner's whole subtree ONCE, then derive each direct child's
        // occupied-slot count and downline size in memory — no per-row count()/BFS
        // queries. Mirrors PartnerNetworkTree::getViewData().
        $subtree = Partner::query()
            ->with('partnerTier')
            ->whereIn('id', array_merge([$this->record->getKey()], $this->record->descendantIds()))
            ->orderBy('partner_no')
            ->get();

        $byParent = $subtree->groupBy(fn (Partner $p): int => (int) $p->parent_partner_id);

        $occupied = $subtree
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

        $children = $byParent->get($this->record->getKey(), collect());

        return [
            'rows' => $children->map(function (Partner $partner) use ($occupied, $countDownline): array {
                $used = (int) ($occupied[$partner->getKey()] ?? 0);

                return [
                    'partner_no' => $partner->partner_no,
                    'name' => $partner->applicant_name,
                    'tier' => $partner->partnerTier?->name,
                    'status' => $partner->status?->value,
                    'downline' => $countDownline($partner->getKey()),
                    'used' => $used,
                    'max' => Partner::MAX_DIRECT_PARTNERS,
                    'has_open_slot' => $used < Partner::MAX_DIRECT_PARTNERS,
                    'url' => PartnerResource::getUrl('edit', ['record' => $partner]),
                ];
            })->values()->all(),
            'total' => $children->count(),
        ];
    }
}
