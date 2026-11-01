<?php

namespace App\Services\WhatsApp\Contracts;

interface WhatsAppDriverInterface
{
    /**
     * Test connection or ping the provider account.
     *
     * @param  array<string, mixed>  $config
     * @return array{success: bool, message: string, device?: string|null}
     */
    public function testConnection(array $config): array;

    /**
     * Send a plain text message.
     *
     * @param  array<string, mixed>  $config
     * @return array{success: bool, message: string, message_id?: string|null, url?: string|null}
     */
    public function sendMessage(string $phone, string $text, array $config): array;

    /**
     * Send a media message (image/photo/file) with caption.
     *
     * @param  array<string, mixed>  $config
     * @return array{success: bool, message: string, message_id?: string|null, url?: string|null}
     */
    public function sendMedia(string $phone, string $text, string $mediaUrl, array $config): array;
}
