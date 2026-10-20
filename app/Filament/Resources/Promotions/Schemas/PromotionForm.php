<?php

namespace App\Filament\Resources\Promotions\Schemas;

use App\Enums\ProductSelectionStatus;
use App\Enums\ProductStatus;
use App\Enums\PromotionMode;
use App\Enums\PromotionStatus;
use App\Models\BusinessOwner;
use App\Models\BusinessOwnerProductSelection;
use App\Models\Customer;
use App\Models\Product;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Business Transaction')
                    ->description('Run a manual promotion now or schedule an auto promotion for selected customers.')
                    ->schema([
                        Select::make('business_owner_id')
                            ->label('Business Owner')
                            ->options(fn (): array => BusinessOwner::query()
                                ->visibleTo(auth()->user())
                                ->orderBy('applicant_name')
                                ->get()
                                ->mapWithKeys(fn (BusinessOwner $businessOwner): array => [
                                    $businessOwner->id => $businessOwner->applicant_name.' - '.$businessOwner->business_owner_no,
                                ])
                                ->all())
                            ->default(fn (): ?int => auth()->user()?->businessOwner?->id)
                            ->disabled(fn (): bool => (bool) auth()->user()?->isBusinessOwner())
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('product_id', null);
                                $set('customer_ids', []);
                            })
                            ->searchable()
                            ->required(),
                        ToggleButtons::make('mode')
                            ->label('Promotion Mode')
                            ->options(PromotionMode::options())
                            ->colors([
                                PromotionMode::Manual->value => 'warning',
                                PromotionMode::Auto->value => 'success',
                            ])
                            ->icons([
                                PromotionMode::Manual->value => 'heroicon-m-hand-raised',
                                PromotionMode::Auto->value => 'heroicon-m-signal',
                            ])
                            ->default(PromotionMode::Manual->value)
                            ->grouped()
                            ->live()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make(fn (Get $get): string => $get('mode') === PromotionMode::Auto->value
                    ? 'Schedule Promotion Time & Date'
                    : 'Set Promotion Time & Date')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Promotion Starts')
                            ->seconds(false)
                            ->required(fn (Get $get): bool => $get('mode') === PromotionMode::Auto->value),
                        DateTimePicker::make('ends_at')
                            ->label('Promotion Ends')
                            ->seconds(false)
                            ->required(fn (Get $get): bool => $get('mode') === PromotionMode::Auto->value)
                            ->after('starts_at'),
                    ])
                    ->columns(2),

                Section::make('Select Product')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn (Get $get): array => self::productOptions((int) $get('business_owner_id')))
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('customer_ids', []))
                            ->searchable()
                            ->required(),
                        TextInput::make('discount_value')
                            ->label('Discount')
                            ->numeric()
                            ->minValue(0)
                            ->prefix(fn (Get $get): string => $get('discount_type') === 'percent' ? '%' : 'RM'),
                        Select::make('discount_type')
                            ->label('Discount Type')
                            ->options([
                                'amount' => 'RM Discount',
                                'percent' => 'Percent Discount',
                            ])
                            ->default('amount')
                            ->required(),
                        TextInput::make('max_customers')
                            ->label('Max Customers')
                            ->numeric()
                            ->integer()
                            ->minValue(1),
                    ])
                    ->columns(4),

                Section::make('Promotion Media')
                    ->schema([
                        FileUpload::make('photo_1_path')
                            ->label('Photo 1')
                            ->disk('public')
                            ->directory('promotions')
                            ->image(),
                        FileUpload::make('photo_2_path')
                            ->label('Photo 2')
                            ->disk('public')
                            ->directory('promotions')
                            ->image(),
                    ])
                    ->columns(2),

                Section::make('Special Offer')
                    ->schema([
                        Textarea::make('offer_terms')
                            ->label('Offer / T&C')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Select Customer')
                    ->description('Only customers under the selected Business Owner and eligible for the selected product can be selected.')
                    ->schema([
                        CheckboxList::make('customer_ids')
                            ->label('Customers')
                            ->options(fn (Get $get): array => self::customerOptions(
                                (int) $get('business_owner_id'),
                                (int) $get('product_id'),
                            ))
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Run State')
                    ->schema([
                        Select::make('status')
                            ->options(PromotionStatus::options())
                            ->default(PromotionStatus::Draft->value)
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('ran_at')
                            ->label('Ran At')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function productOptions(int $businessOwnerId): array
    {
        if ($businessOwnerId === 0) {
            return [];
        }

        $productIds = BusinessOwnerProductSelection::query()
            ->where('business_owner_id', $businessOwnerId)
            ->where('status', '!=', ProductSelectionStatus::Inactive->value)
            ->pluck('product_id');

        return Product::query()
            ->where('status', ProductStatus::Active->value)
            ->whereIn('id', $productIds)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => $product->name.' - '.$product->product_no,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function customerOptions(int $businessOwnerId, int $productId): array
    {
        if ($businessOwnerId === 0) {
            return [];
        }

        $product = $productId > 0 ? Product::query()->find($productId) : null;

        return Customer::query()
            ->visibleTo(auth()->user())
            ->where('business_owner_id', $businessOwnerId)
            ->orderBy('name')
            ->get()
            ->filter(fn (Customer $customer): bool => ! $product || $customer->canBuyProduct($product))
            ->mapWithKeys(fn (Customer $customer): array => [
                $customer->id => trim($customer->name.' - '.$customer->customer_no.' '.($customer->hp_no ? "({$customer->hp_no})" : '')),
            ])
            ->all();
    }
}
