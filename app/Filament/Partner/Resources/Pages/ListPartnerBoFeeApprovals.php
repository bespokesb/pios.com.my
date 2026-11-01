<?php

namespace App\Filament\Partner\Resources\Pages;

use App\Enums\FeeStatus;
use App\Filament\Partner\Resources\PartnerBoFeeApprovalResource;
use App\Filament\Partner\Resources\PartnerBoPaymentApprovalResource;
use App\Models\BusinessOwner;
use App\Models\Fee;
use App\Models\SubscriptionPayment;
use App\Services\FeeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class ListPartnerBoFeeApprovals extends ListRecords
{
    protected static string $resource = PartnerBoFeeApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('goto_subscription_approvals')
                ->label('Pengesahan Langganan BGE')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('warning')
                ->url(fn (): string => PartnerBoPaymentApprovalResource::getUrl('index'))
                ->badge(function (): ?string {
                    $user = auth()->user();
                    $partner = $user?->partner;
                    if (! $partner && ! $user?->isSysAdmin()) {
                        return null;
                    }
                    $count = SubscriptionPayment::query()
                        ->when(! $user->isSysAdmin(), fn ($q) => $q->where('partner_id', $partner?->id))
                        ->where('payer_role', 'business_owner')
                        ->where('status', 'pending_approval')
                        ->count();

                    return $count > 0 ? (string) $count : null;
                }),

            Action::make('record_payment')
                ->label('Rekod Bayaran Yuran BO')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('Rekod Bayaran Yuran Business Owner (Kutipan Terus)')
                ->modalDescription('Gunakan fungsi ini apabila anda telah berjumpa Business Owner dan mengutip tunai atau menerima bayaran secara terus. Yuran akan disahkan serta-merta.')
                ->modalSubmitActionLabel('Rekod & Sahkan')
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
                        ->live()
                        ->required(),

                    Select::make('fee_id')
                        ->label('Yuran Tertunggak / Belum Sah')
                        ->options(function (Get $get): array {
                            $boId = $get('business_owner_id');
                            if (! $boId) {
                                return [];
                            }

                            return Fee::query()
                                ->where('business_owner_id', $boId)
                                ->whereIn('status', [FeeStatus::Unpaid, FeeStatus::Pending, FeeStatus::Overdue])
                                ->orderBy('due_date')
                                ->get()
                                ->mapWithKeys(function (Fee $fee): array {
                                    $label = ($fee->fee_type?->label() ?? 'Yuran').' — RM '.number_format((float) $fee->amount, 2);
                                    if ($fee->billing_period) {
                                        $label .= " ({$fee->billing_period})";
                                    } elseif ($fee->due_date) {
                                        $label .= ' (Due: '.$fee->due_date->format('d M Y').')';
                                    }

                                    return [$fee->id => $label];
                                })
                                ->toArray();
                        })
                        ->searchable()
                        ->required(),

                    Select::make('payment_method')
                        ->label('Kaedah Bayaran')
                        ->options([
                            'cash' => 'Tunai (Kutipan Bersemuka)',
                            'bank_transfer' => 'Pindahan Bank (Online)',
                            'cheque' => 'Cek',
                        ])
                        ->default('cash')
                        ->required(),

                    TextInput::make('reference_no')
                        ->label('No. Rujukan / Resit (Pilihan)')
                        ->placeholder('cth: Kutipan Tunai di kedai'),

                    Textarea::make('notes')
                        ->label('Catatan (Pilihan)')
                        ->placeholder('cth: Dikutip semasa lawatan tapak'),
                ])
                ->action(function (array $data): void {
                    $fee = Fee::findOrFail($data['fee_id']);
                    $user = auth()->user();

                    app(FeeService::class)->recordDirectPartnerPayment(
                        partner: $fee->partner,
                        actor: $user,
                        fee: $fee,
                        paymentMethod: $data['payment_method'] ?? 'cash',
                        referenceNo: $data['reference_no'] ?? null,
                        notes: $data['notes'] ?? null
                    );

                    Notification::make()
                        ->title('Bayaran Yuran Berjaya Direkod & Disahkan')
                        ->body('Bayaran RM '.number_format((float) $fee->amount, 2)." bagi {$fee->businessOwner?->applicant_name} telah disahkan.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
