<?php

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\Contracts\WhatsAppDriverInterface;
use App\Support\Phone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UltraMsgDriver implements WhatsAppDriverInterface
{
    protected string $endpoint = 'https://api.ultramsg.com';

    public function testConnection(array $config): array
    {
        $instanceId = trim((string) ($config['instance_id'] ?? ''));
        $token = trim((string) ($config['token'] ?? ''));

        if (empty($instanceId) || empty($token)) {
            return [
                'success' => false,
                'message' => 'UltraMsg Instance ID dan Token diperlukan.',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->get("{$this->endpoint}/{$instanceId}/instance/status", [
                    'token' => $token,
                ]);

            $data = $response->json();

            if ($response->successful() && ! isset($data['error'])) {
                $statusVal = $data['status'] ?? 'connected';
                if (is_array($statusVal)) {
                    $statusStr = isset($statusVal['accountStatus']['authenticated']) && $statusVal['accountStatus']['authenticated']
                        ? 'authenticated'
                        : 'connected';
                } else {
                    $statusStr = (string) $statusVal;
                }

                return [
                    'success' => true,
                    'message' => "Sambungan UltraMsg berjaya disahkan. (Status: {$statusStr})",
                    'device' => "Instance #{$instanceId} ({$statusStr})",
                ];
            }

            $errorMsg = $data['error'] ?? 'Instance ID atau Token tidak sah.';

            return [
                'success' => false,
                'message' => "Ralat UltraMsg: {$errorMsg}",
            ];
        } catch (\Throwable $e) {
            Log::warning('UltraMsg testConnection exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat rangkaian semasa menghubungi pelayan UltraMsg.',
            ];
        }
    }

    public function sendMessage(string $phone, string $text, array $config): array
    {
        $instanceId = trim((string) ($config['instance_id'] ?? ''));
        $token = trim((string) ($config['token'] ?? ''));

        if (empty($instanceId) || empty($token)) {
            return ['success' => false, 'message' => 'UltraMsg Instance ID dan Token diperlukan.'];
        }

        $cleanPhone = Phone::toWhatsApp($phone);
        if (empty($cleanPhone)) {
            return ['success' => false, 'message' => 'Nombor telefon tidak sah.'];
        }

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->post("{$this->endpoint}/{$instanceId}/messages/chat", [
                    'token' => $token,
                    'to' => '+'.$cleanPhone,
                    'body' => $text,
                ]);

            $data = $response->json();

            if ($response->successful() && (! isset($data['error']) || empty($data['error']))) {
                return [
                    'success' => true,
                    'message' => 'Mesej berjaya dihantar via UltraMsg.',
                    'message_id' => $data['id'] ?? null,
                ];
            }

            $errorMsg = $data['error'] ?? 'Penghantaran gagal oleh UltraMsg.';

            return [
                'success' => false,
                'message' => "Ralat UltraMsg: {$errorMsg}",
            ];
        } catch (\Throwable $e) {
            Log::error('UltraMsg sendMessage error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat pelayan semasa menghantar mesej UltraMsg.',
            ];
        }
    }

    public function sendMedia(string $phone, string $text, string $mediaUrl, array $config): array
    {
        $instanceId = trim((string) ($config['instance_id'] ?? ''));
        $token = trim((string) ($config['token'] ?? ''));

        if (empty($instanceId) || empty($token)) {
            return ['success' => false, 'message' => 'UltraMsg Instance ID dan Token diperlukan.'];
        }

        $cleanPhone = Phone::toWhatsApp($phone);
        if (empty($cleanPhone)) {
            return ['success' => false, 'message' => 'Nombor telefon tidak sah.'];
        }

        try {
            if (empty($mediaUrl)) {
                return $this->sendMessage($phone, $text, $config);
            }

            $response = Http::timeout(20)
                ->asForm()
                ->post("{$this->endpoint}/{$instanceId}/messages/image", [
                    'token' => $token,
                    'to' => '+'.$cleanPhone,
                    'image' => $mediaUrl,
                    'caption' => $text,
                ]);

            $data = $response->json();

            if ($response->successful() && (! isset($data['error']) || empty($data['error']))) {
                return [
                    'success' => true,
                    'message' => 'Mesej gambar berjaya dihantar via UltraMsg.',
                    'message_id' => $data['id'] ?? null,
                ];
            }

            $errorMsg = $data['error'] ?? 'Penghantaran gambar gagal oleh UltraMsg.';

            return [
                'success' => false,
                'message' => "Ralat UltraMsg: {$errorMsg}",
            ];
        } catch (\Throwable $e) {
            Log::error('UltraMsg sendMedia error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat pelayan semasa menghantar media UltraMsg.',
            ];
        }
    }
}
