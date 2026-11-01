<?php

namespace App\Services;

use App\Enums\FeeStatus;
use App\Enums\PaymentStatus;
use App\Models\Fee;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class FeeService
{
    public function __construct(
        private readonly AuditLogger $audit
    ) {}

    /**
     * Business owner submits fee payment declaration (status enters Pending confirmation).
     */
    public function submitPayment(
        Fee $fee,
        User $actor,
        string $paymentMethod,
        ?string $referenceNo = null,
        ?string $notes = null
    ): Payment {
        if ($fee->status === FeeStatus::Paid) {
            throw ValidationException::withMessages([
                'fee' => 'Yuran ini sudah pun selesai dibayar.',
            ]);
        }

        $payment = Payment::create([
            'fee_id' => $fee->id,
            'payment_type' => $paymentMethod,
            'amount' => $fee->amount,
            'status' => PaymentStatus::Pending,
            'reference_no' => filled($referenceNo) ? $referenceNo : null,
            'paid_at' => now(),
            'recorded_by' => $actor->id,
        ]);

        $fee->update([
            'status' => FeeStatus::Pending,
        ]);

        $this->audit->log(
            actor: $actor,
            event: 'fee_payment_submitted',
            auditable: $fee,
            oldValues: ['status' => $fee->getOriginal('status')],
            newValues: ['status' => FeeStatus::Pending->value, 'payment_id' => $payment->id],
        );

        return $payment;
    }

    /**
     * Sponsoring Partner confirms and approves the fee payment.
     */
    public function approvePayment(
        Fee $fee,
        User $actor,
        ?string $paymentMethod = null,
        ?string $referenceNo = null,
        ?string $notes = null
    ): Payment {
        $payment = $fee->payments()->where('status', PaymentStatus::Pending)->latest()->first();

        if ($payment) {
            $payment->update([
                'payment_type' => $paymentMethod ?: $payment->payment_type,
                'reference_no' => filled($referenceNo) ? $referenceNo : $payment->reference_no,
                'status' => PaymentStatus::Verified,
                'paid_at' => now(),
                'recorded_by' => $actor->id,
            ]);
        } else {
            $payment = Payment::create([
                'fee_id' => $fee->id,
                'payment_type' => $paymentMethod ?: 'cash',
                'amount' => $fee->amount,
                'status' => PaymentStatus::Verified,
                'reference_no' => filled($referenceNo) ? $referenceNo : null,
                'paid_at' => now(),
                'recorded_by' => $actor->id,
            ]);
        }

        $oldStatus = $fee->status;
        $fee->update([
            'status' => FeeStatus::Paid,
        ]);

        $this->audit->log(
            actor: $actor,
            event: 'fee_payment_approved',
            auditable: $fee,
            oldValues: ['status' => $oldStatus instanceof FeeStatus ? $oldStatus->value : $oldStatus],
            newValues: ['status' => FeeStatus::Paid->value, 'payment_id' => $payment->id],
        );

        return $payment;
    }

    /**
     * Sponsoring Partner rejects the fee payment declaration.
     */
    public function rejectPayment(
        Fee $fee,
        User $actor,
        string $reason
    ): void {
        $payment = $fee->payments()->where('status', PaymentStatus::Pending)->latest()->first();

        if ($payment) {
            $payment->update([
                'status' => PaymentStatus::Failed,
            ]);
        }

        $oldStatus = $fee->status;
        $newStatus = ($fee->due_date && $fee->due_date->isPast()) ? FeeStatus::Overdue : FeeStatus::Unpaid;

        $fee->update([
            'status' => $newStatus,
        ]);

        $this->audit->log(
            actor: $actor,
            event: 'fee_payment_rejected',
            auditable: $fee,
            oldValues: ['status' => $oldStatus instanceof FeeStatus ? $oldStatus->value : $oldStatus],
            newValues: ['status' => $newStatus->value, 'reason' => $reason],
        );
    }

    /**
     * Partner directly records payment collected from Business Owner (e.g. collected cash in person).
     */
    public function recordDirectPartnerPayment(
        ?Partner $partner,
        User $actor,
        Fee $fee,
        string $paymentMethod = 'cash',
        ?string $referenceNo = null,
        ?string $notes = null
    ): Payment {
        $payment = $fee->payments()->where('status', PaymentStatus::Pending)->latest()->first();

        if ($payment) {
            $payment->update([
                'payment_type' => $paymentMethod,
                'reference_no' => filled($referenceNo) ? $referenceNo : $payment->reference_no,
                'status' => PaymentStatus::Verified,
                'paid_at' => now(),
                'recorded_by' => $actor->id,
            ]);
        } else {
            $payment = Payment::create([
                'fee_id' => $fee->id,
                'payment_type' => $paymentMethod,
                'amount' => $fee->amount,
                'status' => PaymentStatus::Verified,
                'reference_no' => filled($referenceNo) ? $referenceNo : null,
                'paid_at' => now(),
                'recorded_by' => $actor->id,
            ]);
        }

        $oldStatus = $fee->status;
        $fee->update([
            'status' => FeeStatus::Paid,
        ]);

        $this->audit->log(
            actor: $actor,
            event: 'fee_direct_payment_recorded',
            auditable: $fee,
            oldValues: ['status' => $oldStatus instanceof FeeStatus ? $oldStatus->value : $oldStatus],
            newValues: ['status' => FeeStatus::Paid->value, 'payment_id' => $payment->id],
        );

        return $payment;
    }
}
