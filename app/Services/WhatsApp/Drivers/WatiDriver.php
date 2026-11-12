<?php

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\Contracts\WhatsAppDriverInterface;
use App\Support\Phone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WatiDriver implements WhatsAppDriverInterface
{
    /**
     * Resolve the Wati API endpoint.
     * e.g. https://live-mt-server.wati.io/302812 or custom Wati URL.
     */
    public function resolveEndpoint(array $config): string
    {
        $endpoint = trim((string) ($config['endpoint'] ?? $config['instance_id'] ?? ''));

        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            return rtrim($endpoint, '/');
        }

        // If user provided a numeric tenant id like 302812
        if (! empty($endpoint)) {
            return 'https://live-mt-server.wati.io/'.ltrim($endpoint, '/');
        }

        return 'https://live-mt-server.wati.io';
    }

    public function testConnection(array $config): array
    {
        $token = trim((string) ($config['token'] ?? $config['api_key'] ?? ''));
        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Wati.io Access Token tidak diisi.',
            ];
        }

        $endpoint = $this->resolveEndpoint($config);

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(10)
                ->get("{$endpoint}/api/v1/getMessageTemplates");

            if (! $response->successful()) {
                $response = Http::withToken($token)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->timeout(10)
                    ->get("{$endpoint}/api/v1/checkChannelStatus");
            }

            $data = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Sambungan Wati.io (Official WhatsApp API) berjaya disahkan.',
                    'device' => 'Wati.io Official API',
                ];
            }

            $errorMsg = $data['message'] ?? $data['error'] ?? 'Wati Access Token atau Endpoint URL tidak sah.';

            return [
                'success' => false,
                'message' => "Gagal menyambung ke Wati.io: {$errorMsg}",
            ];
        } catch (\Throwable $e) {
            Log::warning('Wati testConnection exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat rangkaian semasa menghubungi pelayan Wati.io.',
            ];
        }
    }

    public function sendMessage(string $phone, string $text, array $config): array
    {
        $token = trim((string) ($config['token'] ?? $config['api_key'] ?? ''));
        if (empty($token)) {
            return ['success' => false, 'message' => 'Wati.io Access Token tidak diisi.'];
        }

        $cleanPhone = Phone::toWhatsApp($phone);
        if (empty($cleanPhone)) {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            $cleanPhone = str_starts_with($digits, '0') ? ('6'.$digits) : $digits;
        }

        if (empty($cleanPhone)) {
            return ['success' => false, 'message' => 'Nombor telefon tidak sah.'];
        }

        $endpoint = $this->resolveEndpoint($config);

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(15)
                ->post("{$endpoint}/api/v1/sendSessionMessage/{$cleanPhone}", [
                    'messageText' => $text,
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['result'] ?? true) && ! isset($data['error'])) {
                $msgId = $data['id'] ?? $data['messageId'] ?? null;

                return [
                    'success' => true,
                    'message' => 'Mesej WhatsApp berjaya dihantar melalui Wati.io.',
                    'message_id' => $msgId ? (string) $msgId : null,
                ];
            }

            $errorMsg = $data['message'] ?? $data['error'] ?? 'Ralat tidak diketahui daripada Wati.';

            return [
                'success' => false,
                'message' => "Penghantaran Wati.io gagal: {$errorMsg}",
            ];
        } catch (\Throwable $e) {
            Log::error('Wati sendMessage exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat semasa menghantar mesej WhatsApp melalui Wati.io.',
            ];
        }
    }

    public function sendMedia(string $phone, string $text, string $mediaUrl, array $config): array
    {
        $token = trim((string) ($config['token'] ?? $config['api_key'] ?? ''));
        if (empty($token)) {
            return ['success' => false, 'message' => 'Wati.io Access Token tidak diisi.'];
        }

        $cleanPhone = Phone::toWhatsApp($phone);
        if (empty($cleanPhone)) {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            $cleanPhone = str_starts_with($digits, '0') ? ('6'.$digits) : $digits;
        }

        if (empty($cleanPhone)) {
            return ['success' => false, 'message' => 'Nombor telefon tidak sah.'];
        }

        $endpoint = $this->resolveEndpoint($config);

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(20)
                ->post("{$endpoint}/api/v1/sendSessionFile/{$cleanPhone}", [
                    'file' => $mediaUrl,
                    'caption' => $text,
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['result'] ?? true) && ! isset($data['error'])) {
                $msgId = $data['id'] ?? $data['messageId'] ?? null;

                return [
                    'success' => true,
                    'message' => 'Mesej media WhatsApp berjaya dihantar melalui Wati.io.',
                    'message_id' => $msgId ? (string) $msgId : null,
                ];
            }

            return $this->sendMessage($phone, "{$text}\n\n{$mediaUrl}", $config);
        } catch (\Throwable $e) {
            Log::error('Wati sendMedia exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat semasa menghantar media WhatsApp melalui Wati.io.',
            ];
        }
    }
}
