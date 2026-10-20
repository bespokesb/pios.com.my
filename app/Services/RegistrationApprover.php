<?php

namespace App\Services;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use App\Exceptions\RegistrationApprovalException;
use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Models\FeeSetting;
use App\Models\Partner;
use App\Models\User;

class RegistrationApprover
{
    /**
     * Approve a partner-recruited sub-partner, enforcing the active downline cap.
     *
     * @throws RegistrationApprovalException when the sponsoring partner already
     *                                       has the maximum active sub-partners.
     */
    public function approvePartner(Partner $partner): void
    {
        $parent = $partner->parentPartner;

        if ($parent && $parent->activeChildPartnersCount() >= Partner::MAX_DIRECT_PARTNERS) {
            throw new RegistrationApprovalException(sprintf(
                '%s already has the maximum of %d active partners.',
                $parent->applicant_name ?? 'This partner',
                Partner::MAX_DIRECT_PARTNERS,
            ));
        }

        $this->activate($partner, $partner->user);

        // A Partner is a Business Owner by default — give the freshly-activated
        // partner a BusinessOwner profile too (idempotent for BO→Partner upgrades).
        app(BusinessOwnerUpgradeService::class)->provisionFor($partner->refresh());
    }

    public function approveBusinessOwner(BusinessOwner $businessOwner): void
    {
        $this->activate($businessOwner, $businessOwner->user);
        $this->generateSetupFee($businessOwner);
    }

    /**
     * Generate the one-time, non-blocking Business Owner setup fee.
     *
     * Idempotent: the firstOrCreate SELECT-then-INSERT (keyed on
     * business_owner_id + fee_type + NULL billing_period) is the guard, since
     * MySQL's unique index does not enforce NULLs. Audit fires only on first
     * creation so re-approval never duplicates the fee or its audit entry.
     * The amount is sourced from FeeSetting, never hardcoded (the literal is a
     * misconfiguration safety net only).
     */
    private function generateSetupFee(BusinessOwner $businessOwner): void
    {
        $amount = FeeSetting::activeAmount(FeeType::BusinessOwnerSetup) ?? 10.00;

        $fee = Fee::firstOrCreate(
            [
                'business_owner_id' => $businessOwner->id,
                'fee_type' => FeeType::BusinessOwnerSetup,
                'billing_period' => null,
            ],
            [
                'partner_id' => $businessOwner->partner_id,
                'amount' => $amount,
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => FeeStatus::Unpaid,
            ],
        );

        if ($fee->wasRecentlyCreated) {
            app(AuditLogger::class)->log(
                actor: auth()->user() ?? User::system(),
                event: 'fee_generated',
                auditable: $fee,
                newValues: $fee->getAttributes(),
            );
        }
    }

    private function activate(Partner|BusinessOwner $record, ?User $user): void
    {
        $oldValues = $record->getOriginal();

        $record->forceFill(['status' => RecordStatus::Active])->save();

        if ($user && $user->status !== UserStatus::Active) {
            $user->forceFill(['status' => UserStatus::Active])->save();
        }

        app(AuditLogger::class)->log(
            actor: auth()->user(),
            event: 'approved',
            auditable: $record,
            oldValues: $oldValues,
            newValues: $record->fresh()->getAttributes(),
        );
    }
}
