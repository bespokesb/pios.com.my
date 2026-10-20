<?php

namespace App\Filament\Resources\Partners\RelationManagers;

use App\Filament\Resources\Partners\PartnerResource;
use App\Models\Partner;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Shows a partner's direct sub-partners (recruits) on the Partner edit page,
 * with each leg's total downline size. Each row links to that sub-partner's own
 * page, so a super admin can drill down through every level to the bottom of the
 * tree. Read-only — recruitment happens through the normal registration flow.
 */
class DownlineRelationManager extends RelationManager
{
    protected static string $relationship = 'childPartners';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = auth()->user();

        // Super admins see any partner's downline; a partner sees their own.
        return $user instanceof User && ($user->isAdmin() || $user->isPartner());
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('pios.partner.downline.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn (Partner $record): ?string => PartnerResource::getUrl('edit', ['record' => $record]),
            )
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('partner_no')
                    ->label(__('pios.partner.downline.partner_no'))
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('applicant_name')
                    ->label(__('pios.partner.downline.name'))
                    ->searchable(),
                TextColumn::make('partnerTier.name')
                    ->label(__('pios.partner.downline.tier'))
                    ->placeholder('—'),
                TextColumn::make('downline')
                    ->label(__('pios.partner.downline.count'))
                    ->badge()
                    ->color('info')
                    ->state(fn (Partner $record): int => $record->downlineCount()),
                TextColumn::make('status')
                    ->label(__('pios.partner.downline.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('pios.partner.downline.recruited'))
                    ->date()
                    ->sortable(),
            ]);
    }
}
