<?php

namespace App\Services;

use App\Models\BusinessOwner;
use Illuminate\Support\Facades\Http;

/**
 * Sends WhatsApp broadcasts through the UstazAI blaster service (ustazai.my).
 *
 * Each Business Owner has their OWN UstazAI account: the API key and sender are
 * read from the BusinessOwner record (key is encrypted at rest). Only the base
 * URL + send endpoint are shared, from config/services.php.
 *
 * The HTTP contract in `blast()` (endpoint path + JSON payload keys + auth) is a
 * sensible default and is the ONLY thing to adjust to match UstazAI's real API.
 */
class UstazAiBlaster
{
    public function isConfiguredFor(?BusinessOwner $businessOwner): bool
    {
        return $businessOwner !== null && filled($businessOwner->ustazai_api_key);
    }

    /**
     * Broadcast a message to many WhatsApp recipients using the Business Owner's
     * own UstazAI credentials.
     *
     * @param  array<int, string>  $recipients  International WhatsApp digits, e.g. "60123456789".
     * @return array{ok: bool, sent: int, failed: int, message: ?string}
     */
    public function blast(BusinessOwner $businessOwner, array $recipients, string $message): array
    {
        $recipients = array_values(array_unique(array_filter($recipients)));

        if (! $this->isConfiguredFor($businessOwner)) {
            return ['ok' => false, 'sent' => 0, 'failed' => count($recipients), 'message' => 'UstazAI is not configured for this account.'];
        }

        if ($recipients === []) {
            return ['ok' => false, 'sent' => 0, 'failed' => 0, 'message' => 'No recipients.'];
        }

        $base = rtrim((string) config('services.ustazai.base_url'), '/');
        $endpoint = '/'.ltrim((string) config('services.ustazai.endpoint', '/send'), '/');

        // TODO: confirm endpoint + payload against UstazAI's API docs.
        $response = Http::acceptJson()
            ->withToken((string) $businessOwner->ustazai_api_key)
            ->post($base.$endpoint, array_filter([
                'sender' => $businessOwner->ustazai_sender,
                'recipients' => $recipients,
                'message' => $message,
            ], fn ($value): bool => $value !== null && $value !== ''));

        if ($response->successful()) {
            return ['ok' => true, 'sent' => count($recipients), 'failed' => 0, 'message' => null];
        }

        return ['ok' => false, 'sent' => 0, 'failed' => count($recipients), 'message' => 'UstazAI request failed ('.$response->status().').'];
    }
}
