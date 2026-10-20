<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A Partner is a Business Owner by default: whenever a Partner becomes active,
 * they automatically get a BusinessOwner profile on the same user account so
 * they can also use the /business panel. Managed by their recruiter (parent
 * partner); a top-level partner manages their own BO account. No fee.
 *
 * Idempotent: does nothing if the partner is not active, has no login, or the
 * user already holds a BusinessOwner profile (e.g. a BO who activated INTO a
 * Partner — that direction stays a deliberate upgrade and keeps its own BO).
 */
class BusinessOwnerUpgradeService
{
    public function __construct(
        private IdentifierGenerator $ids,
        private AuditLogger $audit,
    ) {}

    public function provisionFor(Partner $partner): ?BusinessOwner
    {
        if ($partner->status !== RecordStatus::Active || blank($partner->user_id)) {
            return null;
        }

        if (BusinessOwner::query()->where('user_id', $partner->user_id)->exists()) {
            return null;
        }

        // Managed by the recruiter (parent); a top-level partner manages their own.
        $recruiter = $partner->parentPartner ?? $partner;

        return DB::transaction(function () use ($partner, $recruiter): BusinessOwner {
            $bo = BusinessOwner::query()->create([
                'user_id' => $partner->user_id,          // REUSE the partner's login
                'partner_id' => $recruiter->id,
                'business_owner_no' => $this->ids->generate('business_owner', BusinessOwner::class, 'business_owner_no'),
                'applicant_name' => $partner->applicant_name,
                'ic_no' => $partner->ic_no,
                'hp_no' => $partner->hp_no,
                'email' => $partner->email,
                'address' => $partner->address,
                'bandar' => $partner->bandar,
                'negeri' => $partner->negeri,
                'poskod' => $partner->poskod,
                'status' => RecordStatus::Active,
                'created_by' => auth()->id(),
            ]);

            $this->audit->log(
                actor: auth()->user() ?? User::system(),
                event: 'business_owner_provisioned',
                auditable: $bo,
                newValues: $bo->getAttributes(),
            );

            return $bo;
        });
    }
}
