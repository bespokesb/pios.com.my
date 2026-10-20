<?php

namespace App\Notifications;

use App\Filament\Resources\IncomingOrders\IncomingOrderResource;
use App\Models\Order;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the Business Owner's user when a customer places an order from the
 * public product page. Delivered in-panel (Filament database bell — works even
 * while the mailer is offline) plus a queued email for when mail is configured.
 */
class OrderPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->success()
            ->icon('heroicon-o-shopping-bag')
            ->title(__('pios.order.notification.title', ['order_no' => $this->order->order_no]))
            ->body(__('pios.order.notification.body', [
                'customer' => $this->order->customer?->name ?? '—',
                'total' => number_format((float) $this->order->total, 2),
            ]))
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('pios.order.notification.title', ['order_no' => $this->order->order_no]))
            ->greeting(__('pios.order.notification.greeting', ['name' => $notifiable->name]))
            ->line(__('pios.order.notification.body', [
                'customer' => $this->order->customer?->name ?? '—',
                'total' => number_format((float) $this->order->total, 2),
            ]))
            ->action(
                __('pios.order.notification.action'),
                IncomingOrderResource::getUrl('view', ['record' => $this->order], panel: 'business'),
            );
    }
}
