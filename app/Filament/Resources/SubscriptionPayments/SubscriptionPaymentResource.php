<?php

namespace App\Filament\Resources\SubscriptionPayments;

use App\Filament\Resources\SubscriptionPayments\Pages\ListSubscriptionPayments;
use App\Filament\Resources\SubscriptionPayments\Tables\SubscriptionPaymentsTable;
use App\Models\SubscriptionPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SubscriptionPaymentResource extends Resource
{
    protected static ?string $model = SubscriptionPayment::class;

    protected static ?string $slug = 'subscription-payments';

    protected static ?string $navigationLabel = 'Payment Approvals & Receipts';

    protected static ?string $modelLabel = 'Subscription Payment';

    protected static ?string $pluralModelLabel = 'Subscription Payments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Subscriptions';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSysAdmin() || $user->isPiosOwner());
    }

    public static function table(Table $table): Table
    {
        return SubscriptionPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionPayments::route('/'),
        ];
    }
}
