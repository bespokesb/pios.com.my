<?php

namespace App\Filament\Partner\Resources\Pages;

use App\Enums\FeeStatus;
use App\Filament\Partner\Resources\PartnerBoFeeApprovalResource;
use App\Filament\Partner\Resources\PartnerBoPaymentApprovalResource;
use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Services\SubscriptionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListPartnerBoPaymentApprovals extends ListRecords
{
    protected static string $resource = PartnerBoPaymentApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('goto_fee_approvals')
                ->label('Pengesahan Yuran BO')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('gray')
                ->url(fn (): string => PartnerBoFeeApprovalResource::getUrl('index'))
                ->badge(function (): ?string {
                    $user = auth()->user();
                    $partner = $user?->partner;
                    if (! $partner && ! $user?->isSysAdmin()) {
                        return null;
                    }
                    $count = Fee::query()
                        ->when(! $user->isSysAdmin(), fn ($q) => $q->where('partner_id', $partner?->id))
                        ->where('status', FeeStatus::Pending)
                        ->count();

                    return $count > 0 ? (string) $count : null;
                }),

            Action::make('record_payment')
                ->label('Record & Confirm BO Payment')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('Record & Confirm Business Owner Payment')
                ->modalDescription('Directly record cash or bank transfer payment from a Business Owner under your sponsorship. The subscription will be immediately extended and an official receipt created.')
                ->modalSubmitActionLabel('Confirm & Generate Receipt')
                ->form([
                    Select::make('business_owner_id')
                        ->label('Business Owner')
                        ->options(function (): array {
                            $user = auth()->user();
                            $partner = $user?->partner;
                            if (! $partner && ! $user?->isSysAdmin()) {
                                return [];
                            }

                            $query = BusinessOwner::query();
                            if (! $user->isSysAdmin()) {
                                $query->where('partner_id', $partner?->id);
                            }

                            return $query->orderBy('applicant_name')
                                ->get()
                                ->mapWithKeys(fn (BusinessOwner $bo) => [
                                    $bo->id => "{$bo->applicant_name} ({$bo->business_owner_no})",
                                ])
                                ->toArray();
                        })
                        ->searchable()
                        ->required(),

                    Select::make('months')
                        ->label('Subscription Bundle')
                        ->options([
                            1 => '1 Month (30 Days) — RM 30.00',
                            2 => '2 Months (60 Days) — RM 60.00',
                            3 => '3 Months (90 Days) — RM 90.00',
                            6 => '6 Months (180 Days) — RM 180.00',
                            12 => '12 Months (360 Days) — RM 360.00',
                        ])
                        ->default(1)
                        ->required(),

                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'cash' => 'Cash',
                            'bank_transfer' => 'Bank Transfer (Online)',
                        ])
                        ->default('cash')
                        ->required(),

                    TextInput::make('reference_no')
                        ->label('Reference No (Optional)')
                        ->placeholder('e.g., Bank transfer reference or cash receipt note'),

                    Textarea::make('notes')
                        ->label('Notes (Optional)')
                        ->placeholder('Additional notes...'),
                ])
                ->action(function (array $data): void {
                    $bo = BusinessOwner::findOrFail($data['business_owner_id']);
                    $months = (int) ($data['months'] ?? 1);

                    $payment = app(SubscriptionService::class)->recordPartnerPaymentForBo(
                        auth()->user(),
                        $bo,
                        $months,
                        $data['payment_method'],
                        $data['reference_no'] ?? null,
                        $data['notes'] ?? null
                    );

                    Notification::make()
                        ->title('BO Payment Recorded & Confirmed')
                        ->body('Payment of RM '.number_format((float) $payment->amount, 2)." for {$payment->period_days} days confirmed. Official Receipt {$payment->receipt_no} generated.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
