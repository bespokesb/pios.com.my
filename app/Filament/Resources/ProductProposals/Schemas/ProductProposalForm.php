<?php

namespace App\Filament\Resources\ProductProposals\Schemas;

use App\Enums\ProductProposalStatus;
use App\Models\BusinessOwner;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductProposalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Business Owner')
                    ->schema([
                        Select::make('business_owner_id')
                            ->label('Business Owner')
                            ->options(fn (): array => BusinessOwner::query()
                                ->orderBy('applicant_name')
                                ->get()
                                ->mapWithKeys(fn (BusinessOwner $businessOwner): array => [
                                    $businessOwner->id => $businessOwner->applicant_name.' - '.$businessOwner->business_owner_no,
                                ])
                                ->all())
                            ->default(fn (): ?int => auth()->user()?->businessOwner?->id)
                            ->disabled(fn (): bool => (bool) auth()->user()?->isBusinessOwner())
                            ->searchable()
                            ->required(),
                    ]),

                Section::make('Proposed Product')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('area')
                            ->maxLength(255),
                        TextInput::make('partner_price')
                            ->numeric()
                            ->prefix('RM'),
                        TextInput::make('customer_price')
                            ->numeric()
                            ->prefix('RM'),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Media')
                    ->schema([
                        FileUpload::make('image_video_path')
                            ->label('Image or Video')
                            ->disk('public')
                            ->directory('product-proposals')
                            ->acceptedFileTypes(['image/*', 'video/*'])
                            // Bound the upload (video-appropriate 50 MB), matching
                            // the capped ProductForm::media_assets field.
                            ->maxSize(51200),
                    ]),

                Section::make('Review')
                    ->schema([
                        Select::make('approval_status')
                            ->options(ProductProposalStatus::options())
                            ->default(ProductProposalStatus::Pending->value)
                            ->disabled(fn (): bool => (bool) auth()->user()?->isBusinessOwner())
                            ->required(),
                    ]),
            ]);
    }
}
