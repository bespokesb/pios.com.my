<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value', 'is_active', 'description'])]
class SystemSetting extends Model
{
    public static function isMarketplaceEnabled(): bool
    {
        return Cache::remember('system_setting_marketplace_enabled', 60, function (): bool {
            $setting = static::query()->where('key', 'marketplace_enabled')->first();

            if (! $setting) {
                return true;
            }

            return (bool) $setting->is_active && $setting->value !== 'false';
        });
    }

    public static function setMarketplaceEnabled(bool $enabled): void
    {
        static::query()->updateOrCreate(
            ['key' => 'marketplace_enabled'],
            [
                'value' => $enabled ? 'true' : 'false',
                'is_active' => $enabled,
                'description' => 'Controls whether the Public Marketplace (/market) is visible and accessible.',
            ]
        );

        Cache::forget('system_setting_marketplace_enabled');
    }

    public static function isPublicProductLoginEnabled(): bool
    {
        return Cache::remember('system_setting_public_product_login_enabled', 60, function (): bool {
            $setting = static::query()->where('key', 'public_product_login_enabled')->first();

            if (! $setting) {
                return false; // Default: hide
            }

            return (bool) $setting->is_active && $setting->value === 'true';
        });
    }

    public static function setPublicProductLoginEnabled(bool $enabled): void
    {
        static::query()->updateOrCreate(
            ['key' => 'public_product_login_enabled'],
            [
                'value' => $enabled ? 'true' : 'false',
                'is_active' => $enabled,
                'description' => 'Controls whether customer login is visible on public product pages (/p/xxxx). Default is hidden.',
            ]
        );

        Cache::forget('system_setting_public_product_login_enabled');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
