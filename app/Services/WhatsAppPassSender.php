<?php

namespace App\Services;

use App\Models\BusinessOwner;
use App\Models\Partner;
use App\Support\Phone;

class WhatsAppPassSender
{
    /**
     * Generate wa.me URL for sending login credentials to a Business Owner.
     */
    public static function forBusinessOwner(BusinessOwner $bo, string $password): ?string
    {
        $phone = Phone::toWhatsApp($bo->hp_no);
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
        $phone = Phone::toWhatsApp($partner->hp_no);
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
}
