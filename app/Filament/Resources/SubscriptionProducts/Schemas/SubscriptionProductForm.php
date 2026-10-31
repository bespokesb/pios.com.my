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
                Section::make('Maklumat Produk / Pelan Langganan')
                    ->description('Tetapkan butiran pelan, harga bulanan, dan had sasaran peranan.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nama Pelan / Produk')
                                ->placeholder('Contoh: BizGrowth Engine (BGE) / Sistem POS Pintar')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('code')
                                ->label('Kod Unik Sistem')
                                ->placeholder('Contoh: bge, partner_fee, pos_system')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('price')
                                ->label('Harga Langganan (RM)')
                                ->numeric()
                                ->prefix('RM')
                                ->default(30.00)
                                ->required(),

                            TextInput::make('period_days')
                                ->label('Tempoh Sah (Hari)')
                                ->numeric()
                                ->suffix('Hari')
                                ->default(30)
                                ->required(),

                            Select::make('target_role')
                                ->label('Sasaran Pengguna')
                                ->options([
                                    'all' => 'Semua (Partner & Business Owner)',
                                    'partner' => 'Partner Sahaja',
                                    'business_owner' => 'Business Owner Sahaja',
                                ])
                                ->default('all')
                                ->required(),
                        ]),

                        Grid::make(3)->schema([
                            Toggle::make('is_mandatory')
                                ->label('Wajib Langgan (Default Mandatory)')
                                ->helperText('Jika diaktifkan, produk ini adalah wajib dilanggan oleh pengguna yang disasarkan.')
                                ->default(false),

                            Toggle::make('is_active')
                                ->label('Status Aktif (Boleh Dilanggan)')
                                ->helperText('Nyahaktifkan untuk menyembunyikan pelan ini daripada portal langganan.')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->label('Susunan Paparan')
                                ->numeric()
                                ->default(0),
                        ]),

                        Textarea::make('description')
                            ->label('Keterangan & Ciri-ciri Produk')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Maklumat Bayaran & DuitNow QR')
                    ->description('Tetapkan kod QR DuitNow dan arahan bayaran bank untuk rujukan pembayar.')
                    ->schema([
                        FileUpload::make('qr_code_image')
                            ->label('Imej Kod QR DuitNow / QR Pay')
                            ->disk('public')
                            ->directory('subscriptions/qrcodes')
                            ->image()
                            ->visibility('public')
                            ->helperText('Muat naik gambar QR DuitNow untuk memudahkan pembayar mengimbas dan membayar.'),

                        Textarea::make('payment_instructions')
                            ->label('Arahan Pembayaran & Butiran Akaun Bank')
                            ->rows(4)
                            ->placeholder("Contoh:\nBank: Maybank\nNo. Akaun: 5621-0678-9012\nNama: Pak Itam Online Supermarket Sdn Bhd\n\nSila muat naik slip transaksi selepas membuat bayaran.")
                            ->helperText('Arahan ini akan dipaparkan dalam modal pembayaran pengguna.'),
                    ]),
            ]);
    }
}
