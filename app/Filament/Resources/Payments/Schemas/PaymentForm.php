<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use App\Models\Fee;
use App\Models\Order;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Target')
                    ->schema([
                        Select::make('fee_id')
                            ->label('Fee')
                            ->options(fn (): array => Fee::query()
                                ->with(['businessOwner', 'customer'])
                                ->latest()
                                ->get()
                                ->mapWithKeys(fn (Fee $fee): array => [
                                    $fee->id => trim("Fee #{$fee->id} {$fee->fee_type->label()} RM{$fee->amount}"),
                                ])
                                ->all())
                            ->searchable(),
                        Select::make('order_id')
                            ->label('Order')
                            ->options(fn (): array => Order::query()
                                ->latest()
                                ->get()
                                ->mapWithKeys(fn (Order $order): array => [
                                    $order->id => "{$order->order_no} - RM{$order->total}",
                                ])
                                ->all())
                            ->searchable(),
                    ])
                    ->columns(2),

                Section::make('Manual Verification')
                    ->schema([
                        TextInput::make('payment_type')
                            ->required()
                            ->default('manual')
                            ->maxLength(255),
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('RM'),
                        Select::make('status')
                            ->options(PaymentStatus::options())
                            ->default(PaymentStatus::Pending->value)
                            ->required(),
                        TextInput::make('reference_no')
                            ->label('Reference No')
                            ->maxLength(255),
                        DateTimePicker::make('paid_at'),
                    ])
                    ->columns(2),
            ]);
    }
}
