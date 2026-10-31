<?php

namespace App\Filament\Partner\Resources\Pages;

use App\Filament\Partner\Resources\PartnerSubscriptionResource;
use App\Models\Subscription;
use App\Models\SubscriptionProduct;
use App\Services\SubscriptionService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ListPartnerSubscriptions extends ListRecords
{
    protected static string $resource = PartnerSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $bgeProduct = SubscriptionProduct::where('code', 'bge')->first();
        $bgeSub = $user ? Subscription::where('user_id', $user->id)->where('subscription_product_id', $bgeProduct?->id)->first() : null;

        return [
            Action::make('subscribe')
                ->label(($bgeSub && $bgeSub->isActive()) ? 'Perbaharui Langganan (Renew)' : 'Langgan Produk / BGE')
                ->icon(Heroicon::OutlinedCreditCard)
                ->color('primary')
                ->modalHeading('Langganan Produk & Sistem PIOS')
                ->modalDescription('Pilih pelan langganan dan buat bayaran kepada PIOS Owner melalui QR DuitNow, Tunai, atau Pindahan Bank.')
                ->form([
                    Select::make('subscription_product_id')
                        ->label('Pilih Pelan / Produk')
                        ->options(fn (): array => SubscriptionProduct::query()
                            ->active()
                            ->forRole('partner')
                            ->orderBy('sort_order')
                            ->pluck('name', 'id')
                            ->all())
                        ->default($bgeProduct?->id)
                        ->required()
                        ->live(),

                    Placeholder::make('plan_info')
                        ->label('Maklumat Pelan & Arahan Bayaran')
                        ->content(function (Get $get): HtmlString {
                            $prodId = $get('subscription_product_id');
                            $prod = $prodId ? SubscriptionProduct::find($prodId) : null;
                            if (! $prod) {
                                return new HtmlString('<span class="text-sm text-gray-500">Sila pilih produk di atas.</span>');
                            }

                            $html = '<div class="p-3 bg-teal-50 border border-teal-200 rounded-lg text-sm text-teal-900">';
                            $html .= '<div class="font-bold text-base mb-1">'.$prod->name.' — RM '.number_format((float) $prod->price, 2).' / '.$prod->period_days.' Hari</div>';
                            if ($prod->description) {
                                $html .= '<p class="text-xs text-gray-600 mb-2">'.e($prod->description).'</p>';
                            }

                            if ($prod->qrCodeUrl()) {
                                $html .= '<div class="text-center my-3"><img src="'.$prod->qrCodeUrl().'" class="mx-auto h-36 rounded border" alt="QR DuitNow"><span class="text-xs text-gray-500 block mt-1">Imbas DuitNow QR</span></div>';
                            }

                            if ($prod->payment_instructions) {
                                $html .= '<div class="text-xs whitespace-pre-line bg-white p-2 rounded border border-teal-100">'.e($prod->payment_instructions).'</div>';
                            }
                            $html .= '</div>';

                            return new HtmlString($html);
                        }),

                    Select::make('payment_method')
                        ->label('Kaedah Bayaran')
                        ->options([
                            'qr_code' => 'QR Payment / DuitNow',
                            'bank_transfer' => 'Pindahan Bank Dalam Talian (Online Transfer)',
                            'cash' => 'Tunai (Cash)',
                        ])
                        ->default('qr_code')
                        ->required(),

                    FileUpload::make('payment_proof')
                        ->label('Muat Naik Slip / Bukti Bayaran')
                        ->disk('public')
                        ->directory('subscriptions/proofs')
                        ->image()
                        ->helperText('Muat naik resit pindahan atau slip QR DuitNow untuk pengesahan segera oleh PIOS Owner.'),

                    TextInput::make('reference_no')
                        ->label('No. Rujukan Transaksi (Pilihan)')
                        ->placeholder('Contoh: REF12345678'),

                    Textarea::make('notes')
                        ->label('Catatan Tambahan (Pilihan)')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $user = auth()->user();
                    $product = SubscriptionProduct::findOrFail($data['subscription_product_id']);

                    $payment = app(SubscriptionService::class)->submitPayment($user, $product, [
                        'payment_method' => $data['payment_method'],
                        'payment_proof' => $data['payment_proof'] ?? null,
                        'reference_no' => $data['reference_no'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Permohonan Langganan Dihantar')
                        ->body("Bayaran {$product->name} (No: {$payment->payment_no}) telah dihantar dan menunggu pengesahan daripada PIOS Owner.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
