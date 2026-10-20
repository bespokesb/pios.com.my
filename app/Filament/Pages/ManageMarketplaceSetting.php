<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageMarketplaceSetting extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'System Settings';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.manage-marketplace-setting';

    public bool $is_marketplace_enabled = true;

    public bool $is_public_product_login_enabled = false;

    public function mount(): void
    {
        $this->is_marketplace_enabled = SystemSetting::isMarketplaceEnabled();
        $this->is_public_product_login_enabled = SystemSetting::isPublicProductLoginEnabled();
    }

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return 'Marketplace & Public Settings';
    }

    public function getTitle(): string
    {
        return 'Marketplace & Public Store Settings';
    }

    public function save(): void
    {
        SystemSetting::setMarketplaceEnabled($this->is_marketplace_enabled);
        SystemSetting::setPublicProductLoginEnabled($this->is_public_product_login_enabled);

        Notification::make()
            ->title('System Settings Saved')
            ->body('Marketplace and Public Product settings have been updated successfully.')
            ->success()
            ->send();
    }
}
