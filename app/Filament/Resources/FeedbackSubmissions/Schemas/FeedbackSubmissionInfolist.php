<?php

namespace App\Filament\Resources\FeedbackSubmissions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FeedbackSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maklumat Pengisi')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('full_name')
                                ->label('Nama Penuh'),

                            TextEntry::make('hp_no')
                                ->label('HP No'),

                            TextEntry::make('partner_id_input')
                                ->label('Partner ID Diisi')
                                ->placeholder('—'),

                            TextEntry::make('partner.partner_no')
                                ->label('Partner Ditemui')
                                ->placeholder('—'),

                            TextEntry::make('created_at')
                                ->label('Tarikh Hantar')
                                ->dateTime('d M Y, H:i')
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Soal Selidik')
                    ->schema([
                        Grid::make(3)->schema([
                            IconEntry::make('q1_income_growth')
                                ->label('S1: Mahukan pertumbuhan pendapatan?')
                                ->boolean()
                                ->trueIcon(Heroicon::OutlinedCheckCircle)
                                ->falseIcon(Heroicon::OutlinedXCircle)
                                ->trueColor('success')
                                ->falseColor('danger'),

                            IconEntry::make('q2_seeking_way')
                                ->label('S2: Mencari cara terbaik untuk mencapainya?')
                                ->boolean()
                                ->trueIcon(Heroicon::OutlinedCheckCircle)
                                ->falseIcon(Heroicon::OutlinedXCircle)
                                ->trueColor('success')
                                ->falseColor('danger'),

                            IconEntry::make('q3_interested_tools')
                                ->label('S3: Berminat menggunakan alatan automatik?')
                                ->boolean()
                                ->trueIcon(Heroicon::OutlinedCheckCircle)
                                ->falseIcon(Heroicon::OutlinedXCircle)
                                ->trueColor('success')
                                ->falseColor('danger'),
                        ]),
                    ]),

                Section::make('Komen / Remarks')
                    ->schema([
                        TextEntry::make('remarks')
                            ->label('Komen')
                            ->placeholder('Tiada komen.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Rekod Pelanggan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('customer.customer_no')
                                ->label('Customer No')
                                ->placeholder('—'),

                            TextEntry::make('customer.category')
                                ->label('Kategori')
                                ->badge(),
                        ]),
                    ]),
            ]);
    }
}

