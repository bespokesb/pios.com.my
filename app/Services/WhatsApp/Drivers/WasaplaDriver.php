<?php

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\Contracts\WhatsAppDriverInterface;
use App\Support\Phone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WasaplaDriver implements WhatsAppDriverInterface
{
    /**
     * Resolve the instance matching URL.
     * e.g. https://api.wasapla.org/v1/instances/kedaianda-com
     */
    public function resolveMatchingUrl(array $config): string
    {
        $instance = trim((string) ($config['instance_id'] ?? $config['matching_url'] ?? ''));
        if (str_starts_with($instance, 'http://') || str_starts_with($instance, 'https://')) {
            return rtrim($instance, '/');
        }

        $base = rtrim((string) ($config['endpoint'] ?? 'https://api.wasapla.org/v1/instances'), '/');

        return $base.'/'.ltrim($instance, '/');
    }

    /**
     * Resolve domain for X-App-Domain header (Mandatori 4-Layer Guard).
     */
    public function resolveDomain(array $config): string
    {
        $domain = trim((string) ($config['domain'] ?? ''));
        if (! empty($domain)) {
            $parsed = parse_url($domain, PHP_URL_HOST);

            return $parsed ?: trim($domain, '/');
        }

        $host = request()?->getHost();
        if (! empty($host)) {
            return $host;
        }

        return parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'pios.com.my';
    }

    public function testConnection(array $config): array
    {
        $apiKey = trim((string) ($config['token'] ?? $config['api_key'] ?? ''));
        $botToken = trim((string) ($config['bot_token'] ?? $config['whatsapp_bot_token'] ?? ''));
        $instance = trim((string) ($config['instance_id'] ?? $config['matching_url'] ?? ''));

        if (empty($apiKey) && ! empty($botToken)) {
            $apiKey = $botToken;
        }

        if (empty($apiKey) || empty($instance)) {
            return [
                'success' => false,
                'message' => 'Wasapla API Key / Bot Token dan Instance (Matching URL) diperlukan.',
            ];
        }

        $matchingUrl = $this->resolveMatchingUrl($config);
        $domain = $this->resolveDomain($config);

        $headers = [
            'Accept' => 'application/json',
            'X-App-Domain' => $domain,
        ];
        if (! empty($botToken)) {
            $headers['X-Bot-Token'] = $botToken;
            $headers['X-Webhook-Secret'] = $botToken;
        }

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders($headers)
                ->timeout(10)
                ->get("{$matchingUrl}/status");

            if (! $response->successful() && $response->status() !== 401) {
                $response = Http::withToken($apiKey)
                    ->withHeaders($headers)
                    ->timeout(10)
                    ->get($matchingUrl);
            }

            // If 401 and botToken is different from apiKey, try with botToken as Bearer token
            if ($response->status() === 401 && ! empty($botToken) && $botToken !== $apiKey) {
                $retryResponse = Http::withToken($botToken)
                    ->withHeaders($headers)
                    ->timeout(10)
                    ->get("{$matchingUrl}/status");

                if ($retryResponse->successful()) {
                    $response = $retryResponse;
                }
            }

            $data = $response->json();

            if ($response->successful() && ! isset($data['error'])) {
                $statusStr = (string) ($data['status'] ?? $data['state'] ?? 'connected');
                $isDeviceConnected = in_array(strtolower($statusStr), ['connected', 'open', 'ready', 'authenticated'], true);

                if ($isDeviceConnected) {
                    $message = "Sambungan Wasapla.org aktif dan peranti WhatsApp disambungkan. (Status: {$statusStr})";
                } else {
                    $message = "Kredensial API Wasapla SAH, tetapi peranti WhatsApp BELUM DIIMBAS (Status: {$statusStr}). Sila imbas Kod QR menggunakan WhatsApp di telefon anda.";
                }

                return [
                    'success' => true,
                    'is_device_connected' => $isDeviceConnected,
                    'status' => $statusStr,
                    'message' => $message,
                    'device' => "Wasapla #{$instance} ({$domain})",
                ];
            }

            $reason = $data['message'] ?? $data['error'] ?? 'API Key atau Bot Token tidak sah.';

            return [
                'success' => false,
                'message' => "Gagal menyambung ke Wasapla.org: {$reason}",
            ];
        } catch (\Throwable $e) {
            Log::warning('Wasapla testConnection exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat rangkaian semasa menghubungi pelayan Wasapla.org.',
            ];
        }
    }

    public function sendMessage(string $phone, string $text, array $config): array
    {
        $apiKey = trim((string) ($config['token'] ?? $config['api_key'] ?? ''));
        $botToken = trim((string) ($config['bot_token'] ?? $config['whatsapp_bot_token'] ?? ''));
        $instance = trim((string) ($config['instance_id'] ?? $config['matching_url'] ?? ''));

        if (empty($apiKey) && ! empty($botToken)) {
            $apiKey = $botToken;
        }

        if (empty($apiKey) || empty($instance)) {
            return ['success' => false, 'message' => 'Wasapla API Key / Bot Token atau Instance tidak diisi.'];
        }

        $cleanPhone = Phone::toWhatsApp($phone);
        if (empty($cleanPhone)) {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            $cleanPhone = str_starts_with($digits, '0') ? ('6'.$digits) : $digits;
        }

        if (empty($cleanPhone)) {
            return ['success' => false, 'message' => 'Nombor telefon tidak sah.'];
        }

        $matchingUrl = $this->resolveMatchingUrl($config);
        $domain = $this->resolveDomain($config);

        $headers = [
            'Accept' => 'application/json',
            'X-App-Domain' => $domain,
        ];
        if (! empty($botToken)) {
            $headers['X-Bot-Token'] = $botToken;
            $headers['X-Webhook-Secret'] = $botToken;
        }

        $payload = [
            'phone' => $cleanPhone,
            'message' => $text,
        ];
        if (! empty($botToken)) {
            $payload['bot_token'] = $botToken;
            $payload['webhook_secret'] = $botToken;
        }

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders($headers)
                ->timeout(15)
                ->post("{$matchingUrl}/send", $payload);

            // Retry with botToken as Bearer if 401
            if ($response->status() === 401 && ! empty($botToken) && $botToken !== $apiKey) {
                $response = Http::withToken($botToken)
                    ->withHeaders($headers)
                    ->timeout(15)
                    ->post("{$matchingUrl}/send", $payload);
            }

            $data = $response->json();

            if ($response->successful() && (empty($data['error']) && ($data['status'] ?? true))) {
                $msgId = $data['id'] ?? $data['message_id'] ?? null;

                return [
                    'success' => true,
                    'message' => 'Mesej WhatsApp berjaya dihantar melalui Wasapla.org.',
                    'message_id' => $msgId ? (string) $msgId : null,
                ];
            }

            $reason = (string) ($data['message'] ?? $data['error'] ?? 'Ralat tidak diketahui daripada Wasapla.');

            if (str_contains(strtolower($reason), 'belum bersambung') || str_contains(strtolower($reason), 'not connected')) {
                $reason = 'Bot WhatsApp belum bersambung. Sila klik butang [Imbas Kod QR WhatsApp] di halaman Tetapan dan imbas menggunakan aplikasi WhatsApp di telefon anda (WhatsApp > Linked Devices).';
            }

            return [
                'success' => false,
                'message' => "Penghantaran Wasapla gagal: {$reason}",
            ];
        } catch (\Throwable $e) {
            Log::error('Wasapla sendMessage exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat rangkaian semasa menghantar mesej WhatsApp melalui Wasapla.',
            ];
        }
    }

    public function sendMedia(string $phone, string $text, string $mediaUrl, array $config): array
    {
        $apiKey = trim((string) ($config['token'] ?? $config['api_key'] ?? ''));
        $botToken = trim((string) ($config['bot_token'] ?? $config['whatsapp_bot_token'] ?? ''));
        $instance = trim((string) ($config['instance_id'] ?? $config['matching_url'] ?? ''));

        if (empty($apiKey) && ! empty($botToken)) {
            $apiKey = $botToken;
        }

        if (empty($apiKey) || empty($instance)) {
            return ['success' => false, 'message' => 'Wasapla API Key / Bot Token atau Instance tidak diisi.'];
        }

        $cleanPhone = Phone::toWhatsApp($phone);
        if (empty($cleanPhone)) {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            $cleanPhone = str_starts_with($digits, '0') ? ('6'.$digits) : $digits;
        }

        if (empty($cleanPhone)) {
            return ['success' => false, 'message' => 'Nombor telefon tidak sah.'];
        }

        $matchingUrl = $this->resolveMatchingUrl($config);
        $domain = $this->resolveDomain($config);

        $headers = [
            'Accept' => 'application/json',
            'X-App-Domain' => $domain,
        ];
        if (! empty($botToken)) {
            $headers['X-Bot-Token'] = $botToken;
            $headers['X-Webhook-Secret'] = $botToken;
        }

        $payload = [
            'phone' => $cleanPhone,
            'message' => $text,
            'url' => $mediaUrl,
            'media_url' => $mediaUrl,
        ];
        if (! empty($botToken)) {
            $payload['bot_token'] = $botToken;
            $payload['webhook_secret'] = $botToken;
        }

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders($headers)
                ->timeout(20)
                ->post("{$matchingUrl}/send", $payload);

            if ($response->status() === 401 && ! empty($botToken) && $botToken !== $apiKey) {
                $response = Http::withToken($botToken)
                    ->withHeaders($headers)
                    ->timeout(20)
                    ->post("{$matchingUrl}/send", $payload);
            }

            $data = $response->json();

            if ($response->successful() && (empty($data['error']) && ($data['status'] ?? true))) {
                $msgId = $data['id'] ?? $data['message_id'] ?? null;

                return [
                    'success' => true,
                    'message' => 'Mesej media WhatsApp berjaya dihantar melalui Wasapla.org.',
                    'message_id' => $msgId ? (string) $msgId : null,
                ];
            }

            return $this->sendMessage($phone, "{$text}\n\n{$mediaUrl}", $config);
        } catch (\Throwable $e) {
            Log::error('Wasapla sendMedia exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat semasa menghantar media WhatsApp melalui Wasapla.',
            ];
        }
    }

    /**
     * Fetch active WhatsApp pairing QR code from Wasapla.
     *
     * @param  array<string, mixed>  $config
     * @return array{success: bool, status?: string, qr?: string|null, message?: string}
     */
    public function getQrCode(array $config): array
    {
        $apiKey = trim((string) ($config['token'] ?? $config['api_key'] ?? ''));
        $botToken = trim((string) ($config['bot_token'] ?? $config['whatsapp_bot_token'] ?? ''));
        $instance = trim((string) ($config['instance_id'] ?? $config['matching_url'] ?? ''));

        if (empty($apiKey) && ! empty($botToken)) {
            $apiKey = $botToken;
        }

        if (empty($apiKey) || empty($instance)) {
            return [
                'success' => false,
                'message' => 'Wasapla API Key dan Matching URL diperlukan untuk menjana Kod QR.',
            ];
        }

        $matchingUrl = $this->resolveMatchingUrl($config);
        $domain = $this->resolveDomain($config);

        $headers = [
            'Accept' => 'application/json',
            'X-App-Domain' => $domain,
        ];
        if (! empty($botToken)) {
            $headers['X-Bot-Token'] = $botToken;
            $headers['X-Webhook-Secret'] = $botToken;
        }

        try {
            $response = Http::withToken($apiKey)
                ->withHeaders($headers)
                ->timeout(12)
                ->get("{$matchingUrl}/qr");

            if ($response->status() === 401 && ! empty($botToken) && $botToken !== $apiKey) {
                $retry = Http::withToken($botToken)
                    ->withHeaders($headers)
                    ->timeout(12)
                    ->get("{$matchingUrl}/qr");

                if ($retry->successful()) {
                    $response = $retry;
                }
            }

            $data = $response->json();

            if ($response->successful() && ! empty($data['success'])) {
                $statusStr = (string) ($data['status'] ?? 'qr');
                $isConn = in_array(strtolower($statusStr), ['connected', 'open', 'ready'], true);

                return [
                    'success' => true,
                    'status' => $statusStr,
                    'is_connected' => $isConn,
                    'qr' => $data['qr'] ?? null,
                    'message' => $isConn
                        ? 'WhatsApp telah disambungkan.'
                        : 'Kod QR sedia untuk diimbas.',
                ];
            }

            $reason = (string) ($data['message'] ?? $data['error'] ?? 'Gagal memuatkan Kod QR daripada Wasapla.');

            return [
                'success' => false,
                'message' => $reason,
            ];
        } catch (\Throwable $e) {
            Log::warning('Wasapla getQrCode exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Ralat rangkaian semasa memuatkan Kod QR Wasapla.',
            ];
        }
    }
}
