<?php

namespace App\Services;

use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\SubscriptionPayment;
use App\Support\Phone;

class WhatsAppPassSender
{
    /**
     * Generate wa.me URL for sending login credentials to a Business Owner.
     */
    public static function forBusinessOwner(BusinessOwner $bo, string $password): ?string
    {
        $phone = Phone::toWhatsApp($bo->hp_no ?: $bo->spouse_hp_no);
        if (! $phone) {
            return null;
        }

        $loginUrl = url('/login');
        $name = trim($bo->applicant_name ?: 'Rakan Perniagaan');
        $email = $bo->email ?: ($bo->user?->email ?: '-');

        $message = "Salam {$name},\n\n"
            ."Berikut adalah maklumat akaun log masuk anda ke Portal PIOS Business:\n\n"
            ."🔗 Pautan Log Masuk: {$loginUrl}\n"
            ."👤 ID Log Masuk: {$email}\n"
            ."🔑 Kata Laluan: {$password}\n\n"
            ."Sila log masuk di sini: {$loginUrl}\n\n"
            .'(Anda juga boleh log masuk menggunakan No. Telefon atau No. IC yang berdaftar).';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    /**
     * Generate wa.me URL for sending login credentials to a Partner.
     */
    public static function forPartner(Partner $partner, string $password): ?string
    {
        $phone = Phone::toWhatsApp($partner->hp_no ?: $partner->spouse_hp_no);
        if (! $phone) {
            return null;
        }

        $loginUrl = url('/login');
        $name = trim($partner->applicant_name ?: 'Rakan Kongsi');
        $email = $partner->email ?: ($partner->user?->email ?: '-');

        $message = "Salam {$name},\n\n"
            ."Berikut adalah maklumat akaun log masuk anda ke Portal PIOS Partner:\n\n"
            ."🔗 Pautan Log Masuk: {$loginUrl}\n"
            ."👤 ID Log Masuk: {$email}\n"
            ."🔑 Kata Laluan: {$password}\n\n"
            ."Sila log masuk di sini: {$loginUrl}\n\n"
            .'(Anda juga boleh log masuk menggunakan No. Telefon atau No. IC yang berdaftar).';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    /**
     * Generate wa.me URL for Business Owner contacting their Partner to make BGE subscription payment.
     */
    public static function forBgePaymentToPartner(?Partner $partner, BusinessOwner $bo): ?string
    {
        if (! $partner) {
            return null;
        }

        $phone = Phone::toWhatsApp($partner->hp_no ?: $partner->spouse_hp_no);
        if (! $phone) {
            return null;
        }

        $partnerName = trim($partner->applicant_name ?: 'Partner');
        $boName = trim($bo->applicant_name ?: 'Business Owner');
        $boNo = $bo->business_owner_no ?: ('BO#'.$bo->id);

        $message = "Hello Partner {$partnerName},\n\n"
            ."I am {$boName} ({$boNo}) and would like to make my BizGrowth Engine (BGE) subscription payment to activate/renew my PIOS account.\n\n"
            .'Please provide your payment details (Cash or Bank Transfer) or verify my payment in your Partner Portal. Thank you!';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    /**
     * Generate wa.me URL for Business Owner reminding their Partner to approve submitted BGE payment.
     */
    public static function forBgePendingApprovalReminder(?Partner $partner, BusinessOwner $bo, ?string $paymentNo = null): ?string
    {
        if (! $partner) {
            return null;
        }

        $phone = Phone::toWhatsApp($partner->hp_no ?: $partner->spouse_hp_no);
        if (! $phone) {
            return null;
        }

        $partnerName = trim($partner->applicant_name ?: 'Partner');
        $boName = trim($bo->applicant_name ?: 'Business Owner');
        $boNo = $bo->business_owner_no ?: ('BO#'.$bo->id);
        $payRef = $paymentNo ? " (Payment Ref: {$paymentNo})" : '';

        $message = "Hello Partner {$partnerName},\n\n"
            ."I am {$boName} ({$boNo}) and have submitted my payment for BizGrowth Engine (BGE){$payRef}.\n\n"
            .'Kindly review and confirm the payment in your Partner Portal to activate my subscription countdown. Thank you!';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    /**
     * Generate wa.me URL for Partner sending official subscription receipt to Business Owner.
     */
    public static function forSubscriptionReceiptToBo(SubscriptionPayment $payment): ?string
    {
        $bo = $payment->businessOwner;
        $partner = $payment->partner ?: $bo?->partner;
        $phone = Phone::toWhatsApp($bo?->hp_no);
        if (! $phone) {
            return null;
        }

        $boName = trim($bo?->applicant_name ?: 'Business Owner');
        $boNo = $bo?->business_owner_no ?: '';
        $partnerName = trim($partner?->applicant_name ?: 'Partner');
        $amount = number_format((float) $payment->amount, 2);
        $days = $payment->period_days ?: 30;
        $months = max(1, (int) round($days / 30));
        $receiptUrl = route('subscriptions.receipt', $payment->payment_no);
        $expiresAt = $payment->subscription?->expires_at ? $payment->subscription->expires_at->format('d M Y') : '-';

        $message = "Hello {$boName} ({$boNo}),\n\n"
            ."Your BizGrowth Engine (BGE) subscription payment has been successfully confirmed and verified by your Partner, {$partnerName}.\n\n"
            ."📋 Subscription Plan: {$months} Month(s) ({$days} Days)\n"
            ."💰 Amount Paid: RM {$amount}\n"
            ."🧾 Receipt No: {$payment->receipt_no}\n"
            ."🗓️ Valid Until: {$expiresAt}\n\n"
            ."View and download your official receipt here:\n{$receiptUrl}\n\n"
            .'Thank you for your business!';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    /**
     * Generate wa.me URL for Business Owner notifying their Partner about a submitted fee payment.
     */
    public static function forFeePaymentToPartner(?Partner $partner, ?BusinessOwner $bo, Fee $fee, Payment $payment): ?string
    {
        if (! $partner) {
            return null;
        }

        $phone = Phone::toWhatsApp($partner->hp_no ?: $partner->spouse_hp_no);
        if (! $phone) {
            return null;
        }

        $partnerName = trim($partner->applicant_name ?: 'Partner');
        $boName = trim($bo?->applicant_name ?: 'Business Owner');
        $boNo = $bo?->business_owner_no ?: ('BO#'.($bo?->id ?? ''));
        $amount = number_format((float) $fee->amount, 2);
        $feeType = $fee->fee_type?->label() ?? 'Yuran Platform';
        $period = $fee->billing_period ? " ({$fee->billing_period})" : '';
        $method = match ($payment->payment_type) {
            'cash' => 'Tunai (Cash)',
            'bank_transfer' => 'Pindahan Bank (Online)',
            'cheque' => 'Cek',
            default => ucfirst((string) $payment->payment_type),
        };
        $refText = $payment->reference_no ? "\n🧾 No. Rujukan: {$payment->reference_no}" : '';
        $partnerPortalUrl = url('/partner/bo-fee-approvals');

        $message = "Salam Partner {$partnerName},\n\n"
            ."Saya {$boName} ({$boNo}) telah membuat bayaran yuran berikut:\n\n"
            ."📌 Jenis Yuran: {$feeType}{$period}\n"
            ."💰 Jumlah: RM {$amount}\n"
            ."💳 Kaedah: {$method}{$refText}\n\n"
            ."Sila semak & sahkan bayaran di Portal Partner PIOS anda:\n"
            ."🔗 {$partnerPortalUrl}\n\n"
            .'Terima kasih!';

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
