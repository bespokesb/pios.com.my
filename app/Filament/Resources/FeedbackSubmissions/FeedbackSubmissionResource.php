<?php

namespace App\Filament\Resources\FeedbackSubmissions;

use App\Filament\Resources\FeedbackSubmissions\Pages\ListFeedbackSubmissions;
use App\Filament\Resources\FeedbackSubmissions\Pages\ViewFeedbackSubmission;
use App\Filament\Resources\FeedbackSubmissions\Schemas\FeedbackSubmissionInfolist;
use App\Filament\Resources\FeedbackSubmissions\Tables\FeedbackSubmissionsTable;
use App\Models\FeedbackSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class FeedbackSubmissionResource extends Resource
{
    protected static ?string $model = FeedbackSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('pios.nav.group.customers');
    }

    public static function getNavigationLabel(): string
    {
        return 'Feedback';
    }

    public static function getModelLabel(): string
    {
        return 'Feedback';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Feedback';
    }

    /**
     * Scope: Partner only sees feedback linked to their partner_id.
     * Owner (admin) sees all feedback.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Admin / PIOS Owner sees everything
        if ($user->isAdmin()) {
            return $query;
        }

        // Partner sees only feedback tagged to their partner record
        if ($user->isPartner() && $partner = $user->partner) {
            return $query->where('partner_id', $partner->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return FeedbackSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedbackSubmissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedbackSubmissions::route('/'),
            'view' => ViewFeedbackSubmission::route('/{record}'),
        ];
    }
}
