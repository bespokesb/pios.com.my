<?php

namespace App\Services\WhatsApp;

use App\Models\BusinessOwner;
use App\Models\WhatsAppCampaign;
use App\Services\WhatsApp\Contracts\WhatsAppDriverInterface;
use App\Services\WhatsApp\Drivers\FonnteDriver;
use App\Services\WhatsApp\Drivers\ManualDriver;
use App\Services\WhatsApp\Drivers\UltraMsgDriver;
use App\Services\WhatsApp\Drivers\WasaplaDriver;
use App\Services\WhatsApp\Drivers\WatiDriver;
use InvalidArgumentException;

class WhatsAppManager
{
    /**
     * @var array<string, WhatsAppDriverInterface>
     */
    protected array $drivers = [];

    public function driver(?string $name = null): WhatsAppDriverInterface
    {
        $provider = strtolower((string) ($name ?: 'manual'));

        if (! isset($this->drivers[$provider])) {
            $this->drivers[$provider] = match ($provider) {
                'wasapla' => app(WasaplaDriver::class),
                'wati' => app(WatiDriver::class),
                'fonnte' => app(FonnteDriver::class),
                'ultramsg' => app(UltraMsgDriver::class),
                'manual', 'wa.me' => app(ManualDriver::class),
                default => throw new InvalidArgumentException("WhatsApp driver [{$provider}] tidak disokong."),
            };
        }

        return $this->drivers[$provider];
    }

    public function driverFor(BusinessOwner $bo): WhatsAppDriverInterface
    {
        $provider = $bo->whatsapp_provider ?: 'manual';

        return $this->driver($provider);
    }

    /**
     * @return array<string, mixed>
     */
    public function configFor(BusinessOwner $bo): array
    {
        return [
            'provider' => $bo->whatsapp_provider ?: 'manual',
            'token' => $bo->whatsapp_api_token,
            'api_key' => $bo->whatsapp_api_token,
            'bot_token' => $bo->whatsapp_bot_token,
            'instance_id' => $bo->whatsapp_instance_id,
            'matching_url' => $bo->whatsapp_instance_id,
            'endpoint' => $bo->whatsapp_endpoint,
            'domain' => $bo->whatsapp_domain,
        ];
    }

    /**
     * Test ping connection with given provider & credentials or stored config.
     *
     * @param  array<string, mixed>  $customConfig
     * @return array{success: bool, message: string, device?: string|null}
     */
    public function testConnection(BusinessOwner $bo, ?string $provider = null, array $customConfig = []): array
    {
        $prov = $provider ?: ($bo->whatsapp_provider ?: 'manual');
        $driver = $this->driver($prov);

        $config = array_merge($this->configFor($bo), $customConfig);

        $result = $driver->testConnection($config);

        if ($result['success'] && empty($customConfig)) {
            $isDeviceConn = $result['is_device_connected'] ?? true;
            $bo->update([
                'whatsapp_status' => $isDeviceConn ? 'connected' : 'disconnected',
                'whatsapp_last_tested_at' => now(),
            ]);
        } elseif (! $result['success'] && empty($customConfig) && $prov !== 'manual') {
            $bo->update([
                'whatsapp_status' => 'error',
                'whatsapp_last_tested_at' => now(),
            ]);
        }

        return $result;
    }

    /**
     * Get active QR code for device pairing (e.g. Wasapla/Baileys).
     *
     * @param  array<string, mixed>  $customConfig
     * @return array{success: bool, status?: string, is_connected?: bool, qr?: string|null, message?: string}
     */
    public function getQrCode(BusinessOwner $bo, ?string $provider = null, array $customConfig = []): array
    {
        $prov = $provider ?: ($bo->whatsapp_provider ?: 'manual');
        $driver = $this->driver($prov);

        if (method_exists($driver, 'getQrCode')) {
            $config = array_merge($this->configFor($bo), $customConfig);

            $result = $driver->getQrCode($config);

            if (($result['status'] ?? '') === 'connected' || ! empty($result['is_connected'])) {
                $bo->update([
                    'whatsapp_status' => 'connected',
                    'whatsapp_last_tested_at' => now(),
                ]);
            }

            return $result;
        }

        return [
            'success' => false,
            'message' => 'Provider terpilih tidak memerlukan imbasan Kod QR.',
        ];
    }

    /**
     * Send single text message via the BO's active provider.
     *
     * @return array{success: bool, message: string, message_id?: string|null, url?: string|null}
     */
    public function sendText(BusinessOwner $bo, string $phone, string $text): array
    {
        $driver = $this->driverFor($bo);
        $config = $this->configFor($bo);

        return $driver->sendMessage($phone, $text, $config);
    }

    /**
     * Send single media message with optional caption.
     *
     * @return array{success: bool, message: string, message_id?: string|null, url?: string|null}
     */
    public function sendMedia(BusinessOwner $bo, string $phone, string $text, ?string $mediaUrl = null): array
    {
        $driver = $this->driverFor($bo);
        $config = $this->configFor($bo);

        if (empty($mediaUrl)) {
            return $driver->sendMessage($phone, $text, $config);
        }

        return $driver->sendMedia($phone, $text, $mediaUrl, $config);
    }

    /**
     * Blast message to an array of phone numbers.
     *
     * @param  array<int, array{phone: string, name?: string|null}>  $targets
     * @return array{
     *     total: int,
     *     sent: int,
     *     failed: int,
     *     is_manual: bool,
     *     manual_urls: array<int, array{phone: string, name: string, url: string}>,
     *     details: array<int, array{phone: string, success: bool, message: string}>
     * }
     */
    public function sendBlast(BusinessOwner $bo, array $targets, string $templateText, ?string $mediaUrl = null): array
    {
        $isManual = ($bo->whatsapp_provider ?: 'manual') === 'manual';
        $driver = $this->driverFor($bo);
        $config = $this->configFor($bo);

        $total = count($targets);
        $sent = 0;
        $failed = 0;
        $manualUrls = [];
        $details = [];

        foreach ($targets as $target) {
            $phone = trim((string) ($target['phone'] ?? ''));
            $name = trim((string) ($target['name'] ?? 'Pelanggan'));

            if (empty($phone)) {
                continue;
            }

            // Replace dynamic placeholders if any
            $personalizedText = str_replace(
                ['{name}', '{customer_name}', '{nama}'],
                [$name, $name, $name],
                $templateText
            );

            if ($isManual) {
                $res = $driver->sendMessage($phone, $personalizedText, $config);
                $manualUrls[] = [
                    'phone' => $phone,
                    'name' => $name,
                    'url' => $res['url'] ?? '',
                ];
                $sent++;
                $details[] = ['phone' => $phone, 'success' => true, 'message' => 'Pautan manual sedia'];
            } else {
                $res = filled($mediaUrl)
                    ? $driver->sendMedia($phone, $personalizedText, $mediaUrl, $config)
                    : $driver->sendMessage($phone, $personalizedText, $config);

                if (! empty($res['success'])) {
                    $sent++;
                    $details[] = ['phone' => $phone, 'success' => true, 'message' => $res['message']];
                } else {
                    $failed++;
                    $details[] = ['phone' => $phone, 'success' => false, 'message' => $res['message']];
                }
            }
        }

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'is_manual' => $isManual,
            'manual_urls' => $manualUrls,
            'details' => $details,
        ];
    }

    /**
     * Execute a WhatsApp promotion campaign (immediate or scheduled).
     *
     * @return array<string, mixed>
     */
    public function executeCampaign(WhatsAppCampaign $campaign, bool $isBackground = false): array
    {
        $campaign->loadMissing(['businessOwner', 'product']);
        $bo = $campaign->businessOwner;

        if ($bo === null) {
            $campaign->update([
                'status' => WhatsAppCampaign::STATUS_FAILED,
                'sent_at' => now(),
                'result_summary' => ['error' => 'Profil Business Owner tidak ditemui.'],
            ]);

            return ['total' => 0, 'sent' => 0, 'failed' => 0, 'is_manual' => false, 'manual_urls' => [], 'details' => []];
        }

        $targets = $campaign->resolveTargets();
        if (empty($targets)) {
            $campaign->update([
                'status' => WhatsAppCampaign::STATUS_FAILED,
                'total_recipients' => 0,
                'sent_count' => 0,
                'failed_count' => 0,
                'sent_at' => now(),
                'result_summary' => ['error' => 'Tiada penerima WhatsApp yang sah ditemui.'],
            ]);

            return ['total' => 0, 'sent' => 0, 'failed' => 0, 'is_manual' => false, 'manual_urls' => [], 'details' => []];
        }

        $isManual = ($bo->whatsapp_provider ?: 'manual') === 'manual';
        if ($isBackground && $isManual) {
            $campaign->update([
                'status' => WhatsAppCampaign::STATUS_FAILED,
                'total_recipients' => count($targets),
                'sent_count' => 0,
                'failed_count' => count($targets),
                'sent_at' => now(),
                'result_summary' => [
                    'error' => 'Mod Manual (wa.me) tidak menyokong penghantaran automatik berjadual. Sila aktifkan Wasapla.org atau Wati.io di Settings.',
                ],
            ]);

            return [
                'total' => count($targets),
                'sent' => 0,
                'failed' => count($targets),
                'is_manual' => true,
                'manual_urls' => [],
                'details' => [],
            ];
        }

        $campaign->update([
            'status' => WhatsAppCampaign::STATUS_PROCESSING,
            'total_recipients' => count($targets),
        ]);

        $result = $this->sendBlast(
            $bo,
            $targets,
            $campaign->renderMessageTemplate(),
            $campaign->resolveMediaUrl(),
        );

        $finalStatus = match (true) {
            $result['sent'] > 0 && $result['failed'] === 0 => WhatsAppCampaign::STATUS_COMPLETED,
            $result['sent'] > 0 && $result['failed'] > 0 => WhatsAppCampaign::STATUS_PARTIAL,
            default => WhatsAppCampaign::STATUS_FAILED,
        };

        $campaign->update([
            'status' => $finalStatus,
            'total_recipients' => $result['total'],
            'sent_count' => $result['sent'],
            'failed_count' => $result['failed'],
            'sent_at' => now(),
            'result_summary' => $result,
        ]);

        return $result;
    }
}
