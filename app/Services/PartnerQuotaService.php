<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Partner;
use Carbon\CarbonImmutable;

/**
 * Advisory, read-only read model for the partner monthly customer quota.
 *
 * Counts the customers a partner registered in the current Malaysia-time
 * calendar month against a configurable target. This service performs ONLY
 * reads: it never blocks customer creation, never alters a policy, and never
 * writes. It is the single source the quota widget (Plan 02) and HQ report
 * (Plan 02) reuse, and the seam a future v2 enforcement (QUOTA-05) would call.
 */
class PartnerQuotaService
{
    /**
     * Count the partner's customers created in the current MYT calendar month.
     *
     * Uses a half-open interval [start, nextStart) anchored to Asia/Kuala_Lumpur
     * so the boundary is correct regardless of the app default timezone, and
     * counts the single canonical attribution column customers.partner_id only
     * (no business_owner aggregation — avoids double counting). Relies on the
     * composite (partner_id, created_at) index from Phase 6.
     */
    public function monthToDateCount(Partner $partner, ?\DateTimeInterface $reference = null): int
    {
        $start = CarbonImmutable::parse($reference ?? now(), 'Asia/Kuala_Lumpur')->startOfMonth();
        $nextStart = $start->addMonth();

        return Customer::query()
            ->where('partner_id', $partner->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $nextStart)
            ->count();
    }

    /**
     * The configurable monthly target (default 100, env-overridable).
     */
    public function target(): int
    {
        return (int) config('pios.partner_monthly_customer_target', 100);
    }

    /**
     * Month-to-date attainment breakdown for the partner.
     *
     * @return array{count: int, target: int, percent: int}
     */
    public function attainment(Partner $partner, ?\DateTimeInterface $reference = null): array
    {
        $count = $this->monthToDateCount($partner, $reference);
        $target = $this->target();
        $percent = $target > 0 ? (int) floor(($count / $target) * 100) : 0;

        return [
            'count' => $count,
            'target' => $target,
            'percent' => $percent,
        ];
    }
}
