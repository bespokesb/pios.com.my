<?php

namespace App\Services;

use App\Models\BusinessOwner;
use App\Models\IdentifierSequence;
use App\Models\Partner;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /**
     * Ensure the Business Owner has an initial 48-hour trial subscription for BGE.
     */
    public function ensureInitialBgeSubscription(BusinessOwner $bo): Subscription
    {
        $bgeProduct = SubscriptionProduct::where('code', 'bge')->first();
        if (! $bgeProduct) {
            $bgeProduct = SubscriptionProduct::create([
                'name' => 'BizGrowth Engine (BGE)',
                'code' => 'bge',
                'description' => 'Enjin pengurusan utama perniagaan PIOS.',
                'price' => 30.00,
                'period_days' => 30,
                'target_role' => 'all',
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::where('business_owner_id', $bo->id)
            ->where('subscription_product_id', $bgeProduct->id)
            ->first();

        if (! $subscription) {
            $startsAt = $bo->created_at ?: now();
            $expiresAt = $startsAt->copy()->addHours(48);
            $isStillActive = now()->isBefore($expiresAt);

            $subscription = Subscription::create([
                'subscription_no' => $this->generateSubscriptionNo(),
                'subscription_product_id' => $bgeProduct->id,
                'user_id' => $bo->user_id,
                'subscriber_type' => 'business_owner',
                'partner_id' => $bo->partner_id,
                'business_owner_id' => $bo->id,
                'status' => $isStillActive ? 'trial' : 'expired',
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
            ]);
        } else {
            if ($bo->user_id && ! $subscription->user_id) {
                $subscription->update(['user_id' => $bo->user_id]);
            }

            if ($subscription->status === 'trial' && $subscription->expires_at && now()->isAfter($subscription->expires_at)) {
                $subscription->update(['status' => 'expired']);
            }
        }

        return $subscription;
    }

    /**
     * Submit a new subscription or renewal payment request.
     *
     * @param array{
     *     payment_method: string,
     *     payment_proof?: UploadedFile|string|null,
     *     reference_no?: string|null,
     *     notes?: string|null,
     * } $data
     */
    public function submitPayment(User $user, SubscriptionProduct $product, array $data): SubscriptionPayment
    {
        return DB::transaction(function () use ($user, $product, $data): SubscriptionPayment {
            $isPartner = $user->isPartner();
            $isBO = $user->isBusinessOwner();

            /** @var Partner|null $partner */
            $partner = $user->partner;

            /** @var BusinessOwner|null $bo */
            $bo = $user->businessOwner;

            $payerRole = $isPartner ? 'partner' : 'business_owner';
            $partnerId = $isPartner ? $partner?->id : $bo?->partner_id;
            $boId = $isBO ? $bo?->id : null;

            // 1. Find or create existing Subscription record for this user and product
            /** @var Subscription $subscription */
            $subscription = Subscription::firstOrCreate(
                [
                    'subscription_product_id' => $product->id,
                    'user_id' => $user->id,
                ],
                [
                    'subscription_no' => $this->generateSubscriptionNo(),
                    'subscriber_type' => $payerRole,
                    'partner_id' => $partnerId,
                    'business_owner_id' => $boId,
                    'status' => 'pending_approval',
                ]
            );

            // Handle payment proof upload
            $proofPath = null;
            if (isset($data['payment_proof']) && $data['payment_proof'] instanceof UploadedFile) {
                $proofPath = $data['payment_proof']->store('subscriptions/proofs', 'public');
            } elseif (isset($data['payment_proof']) && is_string($data['payment_proof'])) {
                $proofPath = $data['payment_proof'];
            }

            // 2. Generate Payment No
            $paymentNo = $this->generatePaymentNo();

            // 3. Create Subscription Payment record
            /** @var SubscriptionPayment $payment */
            $payment = SubscriptionPayment::create([
                'payment_no' => $paymentNo,
                'subscription_id' => $subscription->id,
                'subscription_product_id' => $product->id,
                'user_id' => $user->id,
                'payer_role' => $payerRole,
                'partner_id' => $partnerId,
                'business_owner_id' => $boId,
                'amount' => $product->price,
                'period_days' => $product->period_days ?: 30,
                'payment_method' => $data['payment_method'] ?? 'qr_code',
                'payment_proof' => $proofPath,
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
                'status' => 'pending_approval',
            ]);

            // Keep subscription in pending_approval if not already active
            if ($subscription->status !== 'active') {
                $subscription->update(['status' => 'pending_approval']);
            }

            return $payment;
        });
    }

    /**
     * Check if a user can approve a given subscription payment.
     * Business Owner BGE payments are verified and approved exclusively by their sponsoring Partner.
     */
    public function canApprove(User $user, SubscriptionPayment $payment): bool
    {
        // If payment was made by Business Owner, ONLY their sponsoring Partner can approve (or SysAdmin)
        if ($payment->payer_role === 'business_owner') {
            if ($user->isSysAdmin()) {
                return true;
            }

            return $user->isPartner() && $user->partner && $user->partner->id === $payment->partner_id;
        }

        // If payment was made by Partner, PIOS Owner / SysAdmin approves
        if ($payment->payer_role === 'partner') {
            return $user->isSysAdmin() || $user->isPiosOwner();
        }

        return false;
    }

    /**
     * Get available BGE subscription bundles.
     *
     * @return array<int, array{months: int, days: int, price: float, label: string}>
     */
    public static function getBgeBundles(): array
    {
        return [
            1 => ['months' => 1, 'days' => 30, 'price' => 30.00, 'label' => '1 Month (30 Days) — RM 30.00'],
            2 => ['months' => 2, 'days' => 60, 'price' => 60.00, 'label' => '2 Months (60 Days) — RM 60.00'],
            3 => ['months' => 3, 'days' => 90, 'price' => 90.00, 'label' => '3 Months (90 Days) — RM 90.00'],
            6 => ['months' => 6, 'days' => 180, 'price' => 180.00, 'label' => '6 Months (180 Days) — RM 180.00'],
            12 => ['months' => 12, 'days' => 360, 'price' => 360.00, 'label' => '12 Months (360 Days) — RM 360.00'],
        ];
    }

    /**
     * Partner directly records and confirms a subscription payment for their sponsored Business Owner.
     * Immediately marks as approved, generates receipt, and extends subscription by the bundle days.
     */
    public function recordPartnerPaymentForBo(
        User $partnerUser,
        BusinessOwner $bo,
        int $months = 1,
        string $paymentMethod = 'cash',
        ?string $referenceNo = null,
        ?string $notes = null
    ): SubscriptionPayment {
        $partner = $partnerUser->partner;
        $isAuthorized = ($partner && $partner->id === $bo->partner_id) || $partnerUser->isSysAdmin();

        if (! $isAuthorized) {
            throw ValidationException::withMessages([
                'business_owner_id' => 'You are not authorized to confirm payments for this Business Owner.',
            ]);
        }

        return DB::transaction(function () use ($partnerUser, $bo, $months, $paymentMethod, $referenceNo, $notes): SubscriptionPayment {
            $bgeProduct = SubscriptionProduct::where('code', 'bge')->first();
            if (! $bgeProduct) {
                $bgeProduct = SubscriptionProduct::create([
                    'name' => 'BizGrowth Engine (BGE)',
                    'code' => 'bge',
                    'description' => 'Core PIOS business management engine.',
                    'price' => 30.00,
                    'period_days' => 30,
                    'target_role' => 'all',
                    'is_mandatory' => true,
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
            }

            $months = max(1, $months);
            $days = $months * 30;
            $amount = $months * (float) $bgeProduct->price;

            $subscription = $this->ensureInitialBgeSubscription($bo);

            // Generate receipt number
            $seq = IdentifierSequence::firstOrCreate(
                ['key' => 'receipt_no'],
                ['prefix' => 'RCT'.date('ym'), 'next_number' => 1, 'padding' => 5]
            );
            $receiptNo = $seq->prefix.str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
            $seq->increment('next_number');

            $paymentNo = $this->generatePaymentNo();

            /** @var SubscriptionPayment $payment */
            $payment = SubscriptionPayment::create([
                'payment_no' => $paymentNo,
                'receipt_no' => $receiptNo,
                'subscription_id' => $subscription->id,
                'subscription_product_id' => $bgeProduct->id,
                'user_id' => $bo->user_id,
                'payer_role' => 'business_owner',
                'partner_id' => $bo->partner_id,
                'business_owner_id' => $bo->id,
                'amount' => $amount,
                'period_days' => $days,
                'payment_method' => $paymentMethod,
                'reference_no' => $referenceNo,
                'notes' => $notes,
                'paid_at' => now(),
                'status' => 'approved',
                'approved_by' => $partnerUser->id,
                'approved_at' => now(),
            ]);

            // Update Subscription countdown
            if ($subscription->starts_at === null) {
                $subscription->starts_at = now();
            }

            if ($subscription->isActive() && $subscription->expires_at && $subscription->expires_at->isFuture()) {
                $subscription->expires_at = $subscription->expires_at->copy()->addDays($days);
            } else {
                $subscription->expires_at = now()->addDays($days);
            }

            $subscription->status = 'active';
            $subscription->last_renewed_at = now();
            $subscription->save();

            return $payment;
        });
    }

    /**
     * Approve a subscription payment and activate/extend the subscriber's period.
     */
    public function approvePayment(SubscriptionPayment $payment, User $approver): void
    {
        if (! $this->canApprove($approver, $payment)) {
            throw ValidationException::withMessages([
                'approver' => 'You do not have authorization to approve this payment.',
            ]);
        }

        $payment->approve($approver);
    }

    /**
     * Reject a subscription payment with reason.
     */
    public function rejectPayment(SubscriptionPayment $payment, User $approver, ?string $reason = null): void
    {
        if (! $this->canApprove($approver, $payment)) {
            throw ValidationException::withMessages([
                'approver' => 'You do not have authorization to reject this payment.',
            ]);
        }

        $payment->reject($approver, $reason);

        // If subscription has no active period, reset status
        $sub = $payment->subscription;
        if ($sub && ! $sub->isActive()) {
            $sub->update(['status' => 'pending_payment']);
        }
    }

    private function generateSubscriptionNo(): string
    {
        $seq = IdentifierSequence::firstOrCreate(
            ['key' => 'subscription_no'],
            ['prefix' => 'SUB'.date('ym'), 'next_number' => 1, 'padding' => 5]
        );
        $no = $seq->prefix.str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
        $seq->increment('next_number');

        return $no;
    }

    private function generatePaymentNo(): string
    {
        $seq = IdentifierSequence::firstOrCreate(
            ['key' => 'sub_payment_no'],
            ['prefix' => 'SPAY'.date('ym'), 'next_number' => 1, 'padding' => 5]
        );
        $no = $seq->prefix.str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
        $seq->increment('next_number');

        return $no;
    }
}
