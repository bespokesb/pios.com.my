<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Models\BusinessOwner;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class BusinessHeroWidget extends Widget
{
    protected static ?int $sort = -2;

    protected string $view = 'filament.widgets.business-hero-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'business'
            && (auth()->user()?->isBusinessOwner() ?? false);
    }

    public function getUserProperty(): ?User
    {
        return auth()->user();
    }

    public function getBusinessOwnerProperty(): ?BusinessOwner
    {
        return $this->user?->businessOwner;
    }

    public function getGreetingProperty(): string
    {
        $hour = (int) now('Asia/Kuala_Lumpur')->format('H');

        return match (true) {
            $hour >= 5 && $hour < 12 => 'Selamat Pagi',
            $hour >= 12 && $hour < 14 => 'Selamat Tengah Hari',
            $hour >= 14 && $hour < 19 => 'Selamat Petang',
            default => 'Selamat Malam',
        };
    }

    public function getMetricsProperty(): array
    {
        $user = $this->user;
        if (! $user) {
            return [];
        }

        $customerQuery = Customer::query()->visibleTo($user);
        $totalCustomers = (clone $customerQuery)->count();
        $vipCustomers = (clone $customerQuery)->where('is_vip', true)->count();
        $newThisMonth = (clone $customerQuery)
            ->where('created_at', '>=', CarbonImmutable::now('Asia/Kuala_Lumpur')->startOfMonth())
            ->count();

        $activeProducts = Product::query()->visibleTo($user)
            ->where('status', ProductStatus::Active)
            ->count();

        $boNo = $this->businessOwner?->business_owner_no ?? 'BO-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);

        return [
            'bo_no' => $boNo,
            'name' => $this->businessOwner?->applicant_name ?? $user->name,
            'company' => $this->businessOwner?->company_name ?? 'PIOS BizGrowth Partner',
            'is_amway' => (bool) ($this->businessOwner?->is_amway_agent ?? false),
            'total_customers' => $totalCustomers,
            'vip_customers' => $vipCustomers,
            'new_this_month' => $newThisMonth,
            'active_products' => $activeProducts,
            'store_url' => url('/?ref='.$boNo),
        ];
    }
}
