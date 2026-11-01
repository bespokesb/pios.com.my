<?php

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\Contracts\WhatsAppDriverInterface;
use App\Support\Phone;

class ManualDriver implements WhatsAppDriverInterface
{
    public function testConnection(array $config): array
    {
        return [
            'success' => true,
            'message' => 'Manual (wa.me) sedia digunakan tanpa konfigurasi API.',
            'device' => 'Pautan wa.me Manual',
        ];
    }

    public function sendMessage(string $phone, string $text, array $config): array
    {
        $cleanPhone = Phone::toWhatsApp($phone);
        $url = 'https://wa.me/'.$cleanPhone.'?text='.rawurlencode($text);

        return [
            'success' => true,
            'message' => 'Pautan WhatsApp manual dijana.',
            'url' => $url,
        ];
    }

    public function sendMedia(string $phone, string $text, string $mediaUrl, array $config): array
    {
        $cleanPhone = Phone::toWhatsApp($phone);
        $fullText = filled($mediaUrl) ? ($text."\n\nLihat Gambar: ".$mediaUrl) : $text;
        $url = 'https://wa.me/'.$cleanPhone.'?text='.rawurlencode($fullText);

        return [
            'success' => true,
            'message' => 'Pautan WhatsApp manual dijana.',
            'url' => $url,
        ];
    }
}
