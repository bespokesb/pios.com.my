<?php

namespace App\Filament\Resources\SubscriptionProducts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Product / Plan Information')
                    ->description('Set plan details, monthly pricing, validity period, and target user roles.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Plan / Product Name')
                                ->placeholder('e.g. BizGrowth Engine (BGE) / Smart POS System')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('code')
                                ->label('System Unique Code')
                                ->placeholder('e.g. bge, partner_fee, pos_system')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('price')
                                ->label('Subscription Price (MYR)')
                                ->numeric()
                                ->prefix('RM')
                                ->default(30.00)
                                ->required(),

                            TextInput::make('period_days')
                                ->label('Validity Period (Days)')
                                ->numeric()
                                ->suffix('Days')
                                ->default(30)
                                ->required(),

                            Select::make('target_role')
                                ->label('Target User Role')
                                ->options([
                                    'all' => 'All (Partner & Business Owner)',
                                    'partner' => 'Partner Only',
                                    'business_owner' => 'Business Owner Only',
                                ])
                                ->default('all')
                                ->required(),
                        ]),

                        Grid::make(3)->schema([
                            Toggle::make('is_mandatory')
                                ->label('Mandatory (Default Core Plan)')
                                ->helperText('If enabled, this plan is mandatory for targeted users.')
                                ->default(false),

                            Toggle::make('is_active')
                                ->label('Active (Available for Subscription)')
                                ->helperText('Disable to hide this plan from the subscription portal.')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->label('Display Order')
                                ->numeric()
                                ->default(0),
                        ]),

                        Textarea::make('description')
                            ->label('Product Description & Features')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Payment Details & DuitNow QR')
                    ->description('Set DuitNow QR code and bank account payment instructions for payer reference.')
                    ->schema([
                        FileUpload::make('qr_code_image')
                            ->label('DuitNow QR Code Image')
                            ->disk('public')
                            ->directory('subscriptions/qrcodes')
                            ->image()
                            ->visibility('public')
                            ->helperText('Upload a DuitNow QR code image for fast scan and pay.'),

                        Textarea::make('payment_instructions')
                            ->label('Payment Instructions & Bank Account Details')
                            ->rows(4)
                            ->placeholder("Bank: Maybank\nAccount No: 512345678901\nAccount Name: Pak Itam Online Supermarket Sdn Bhd\nReference: <Your Phone No / BO No>")
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
