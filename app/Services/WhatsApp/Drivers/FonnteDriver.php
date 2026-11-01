<?php

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\Contracts\WhatsAppDriverInterface;
use App\Support\Phone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteDriver implements WhatsAppDriverInterface
{
    protected string $endpoint = 'https://api.fonnte.com';

    public function testConnection(array $config): array
    {
        $token = trim((string) ($config['token'] ?? ''));
        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Fonnte API Token tidak diisi.',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => $token])
                ->post("{$this->endpoint}/device");

            $data = $response->json();

            if ($response->successful() && ! empty($data['status'])) {
                $deviceName = $data['device'] ?? $data['name'] ?? 'Peranti Fonnte';
                $deviceStatus = $data['device_status'] ?? 'connect';

                return [
                    'success' => true,
                    'message' => "Sambungan Fonnte berjaya disahkan. (Status: {$deviceStatus})",
                    'device' => "{$deviceName} ({$deviceStatus})",
                ];
            }

            $reason = $data['reason'] ?? $data['message'] ?? 'Token tidak sah atau peranti belum disambungkan.';

            return [
                'success' => false,
                'message' => "Gagal menyambung ke Fonnte: {$reason}",
            ];
        } catch (\Throwable $e) {
            Log::warning('Fonnte testConnection exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat rangkaian semasa menghubungi pelayan Fonnte.',
            ];
        }
    }

    public function sendMessage(string $phone, string $text, array $config): array
    {
        $token = trim((string) ($config['token'] ?? ''));
        if (empty($token)) {
            return ['success' => false, 'message' => 'Fonnte API Token tiada.'];
        }

        $cleanPhone = Phone::toWhatsApp($phone);
        if (empty($cleanPhone)) {
            return ['success' => false, 'message' => 'Nombor telefon tidak sah.'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => $token])
                ->post("{$this->endpoint}/send", [
                    'target' => $cleanPhone,
                    'message' => $text,
                    'countryCode' => '60',
                ]);

            $data = $response->json();

            if ($response->successful() && ! empty($data['status'])) {
                return [
                    'success' => true,
                    'message' => 'Mesej berjaya dihantar via Fonnte.',
                    'message_id' => $data['id'] ?? ($data['process'] ?? null),
                ];
            }

            $reason = $data['reason'] ?? $data['message'] ?? 'Penghantaran gagal oleh Fonnte.';

            return [
                'success' => false,
                'message' => "Ralat Fonnte: {$reason}",
            ];
        } catch (\Throwable $e) {
            Log::error('Fonnte sendMessage error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat pelayan semasa menghantar mesej Fonnte: '.$e->getMessage(),
            ];
        }
    }

    public function sendMedia(string $phone, string $text, string $mediaUrl, array $config): array
    {
        $token = trim((string) ($config['token'] ?? ''));
        if (empty($token)) {
            return ['success' => false, 'message' => 'Fonnte API Token tiada.'];
        }

        $cleanPhone = Phone::toWhatsApp($phone);
        if (empty($cleanPhone)) {
            return ['success' => false, 'message' => 'Nombor telefon tidak sah.'];
        }

        try {
            $payload = [
                'target' => $cleanPhone,
                'message' => $text,
                'countryCode' => '60',
            ];

            if (filled($mediaUrl)) {
                $payload['url'] = $mediaUrl;
            }

            $response = Http::timeout(20)
                ->withHeaders(['Authorization' => $token])
                ->post("{$this->endpoint}/send", $payload);

            $data = $response->json();

            if ($response->successful() && ! empty($data['status'])) {
                return [
                    'success' => true,
                    'message' => 'Mesej media berjaya dihantar via Fonnte.',
                    'message_id' => $data['id'] ?? ($data['process'] ?? null),
                ];
            }

            $reason = $data['reason'] ?? $data['message'] ?? 'Penghantaran media gagal oleh Fonnte.';

            return [
                'success' => false,
                'message' => "Ralat Fonnte: {$reason}",
            ];
        } catch (\Throwable $e) {
            Log::error('Fonnte sendMedia error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat pelayan semasa menghantar media Fonnte: '.$e->getMessage(),
            ];
        }
    }
}
