<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a Partner/Business Owner login is created. Instead of an admin
 * setting the password, the user receives a panel-scoped link to set their own
 * and activate their account. Queued so a mail hiccup never blocks the
 * registration flow.
 */
class AccountActivation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $url,
        private string $panelName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('pios.activation.subject'))
            ->greeting(__('pios.activation.greeting', ['name' => $notifiable->name]))
            ->line(__('pios.activation.intro', ['panel' => $this->panelName]))
            ->action(__('pios.activation.action'), $this->url)
            ->line(__('pios.activation.expiry'))
            ->line(__('pios.activation.ignore'));
    }
}
