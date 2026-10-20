<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Models\BusinessOwner;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * BO → Partner upgrade. Creates a PENDING Partner on the Business Owner's
 * EXISTING user account (no new login). The managing partner APPOINTS where in
 * their own line the new partner attaches (self or a descendant with an open
 * direct slot). HQ verification is the existing RegistrationApprover::
 * approvePartner() path, which re-checks the active-downline cap and activates.
 */
class PartnerUpgradeService
{
    public function __construct(
        private IdentifierGenerator $ids,
        private AuditLogger $audit,
    ) {}

    public function initiate(BusinessOwner $bo, int $placementParentId): Partner
    {
        // Independent authorization guard (defense beyond the Filament action's
        // visibility gate). Only HQ or the BO's managing partner may act.
        $user = auth()->user();
        $authorized = $user && ($user->isAdmin()
            || ($user->isPartner() && $user->partner?->id === $bo->partner_id));

        if (! $authorized) {
            throw ValidationException::withMessages([
                'upgrade' => 'You are not authorized to upgrade this Business Owner.',
            ]);
        }

        if ($bo->status !== RecordStatus::Active) {
            throw ValidationException::withMessages([
                'upgrade' => 'Only an active Business Owner can be upgraded to Partner.',
            ]);
        }

        if (blank($bo->user_id)) {
            throw ValidationException::withMessages([
                'upgrade' => 'This Business Owner has no login account to upgrade.',
            ]);
        }

        // The DB already enforces one Partner per user (partners.user_id unique);
        // this pre-check yields a friendly message instead of a QueryException.
        if (Partner::query()->where('user_id', $bo->user_id)->exists()) {
            throw ValidationException::withMessages([
                'upgrade' => 'This user is already a Partner (or an upgrade is already pending).',
            ]);
        }

        $managingPartner = $bo->partner;

        if (! $managingPartner) {
            throw ValidationException::withMessages([
                'upgrade' => 'This Business Owner has no managing partner.',
            ]);
        }

        $placementParent = Partner::query()->find($placementParentId);

        if (! $placementParent) {
            throw ValidationException::withMessages([
                'placement_parent_id' => 'Select where the new partner should be placed.',
            ]);
        }

        // The chosen placement must be inside the managing partner's OWN line —
        // themselves or one of their descendants (bottom of the line).
        if (! $placementParent->isWithinLineOf($managingPartner)) {
            throw ValidationException::withMessages([
                'placement_parent_id' => 'You can only place the new partner under yourself or someone in your own downline.',
            ]);
        }

        return DB::transaction(function () use ($bo, $placementParent): Partner {
            // Race-safe: lock the placement row and re-check the slot inside the tx.
            $locked = Partner::query()->whereKey($placementParent->getKey())->lockForUpdate()->first();

            if (! $locked->hasOpenDirectSlot()) {
                throw new RegistrationApprovalException(sprintf(
                    '%s already has the maximum of %d direct partners.',
                    $locked->applicant_name ?? 'That partner',
                    Partner::MAX_DIRECT_PARTNERS,
                ));
            }

            $partner = Partner::query()->create([
                'user_id' => $bo->user_id,               // REUSE the existing login
                'parent_partner_id' => $locked->id,
                'partner_no' => $this->ids->generate('partner', Partner::class, 'partner_no'),
                'applicant_name' => $bo->applicant_name,
                'ic_no' => $bo->ic_no,
                'hp_no' => $bo->hp_no,
                'email' => $bo->email,
                'address' => $bo->address,
                'bandar' => $bo->bandar,
                'negeri' => $bo->negeri,
                'poskod' => $bo->poskod,
                'status' => RecordStatus::Pending,       // HQ must verify/approve
                'created_by' => auth()->id(),
            ]);

            $this->audit->log(
                actor: auth()->user(),
                event: 'partner_upgrade_initiated',
                auditable: $partner,
                newValues: $partner->getAttributes(),
            );

            return $partner;
        });
    }
}
