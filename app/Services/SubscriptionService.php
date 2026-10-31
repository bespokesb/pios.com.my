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
     */
    public function canApprove(User $user, SubscriptionPayment $payment): bool
    {
        // PIOS Owner and SysAdmin can approve ANY payment
        if ($user->isSysAdmin() || $user->isPiosOwner()) {
            return true;
        }

        // If payment was made by Partner, ONLY Owner can approve
        if ($payment->payer_role === 'partner') {
            return false;
        }

        // If payment was made by Business Owner, their sponsoring Partner can approve
        if ($payment->payer_role === 'business_owner' && $user->isPartner()) {
            return $user->partner && $user->partner->id === $payment->partner_id;
        }

        return false;
    }

    /**
     * Approve a subscription payment and activate/extend the subscriber's period.
     */
    public function approvePayment(SubscriptionPayment $payment, User $approver): void
    {
        if (! $this->canApprove($approver, $payment)) {
            throw ValidationException::withMessages([
                'approver' => 'Anda tidak mempunyai kebenaran untuk mengesahkan bayaran ini.',
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
                'approver' => 'Anda tidak mempunyai kebenaran untuk menolak bayaran ini.',
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

