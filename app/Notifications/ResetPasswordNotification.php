<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        private readonly string $notificationLocale,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject(__('auth.password_reset.mail.subject', locale: $this->notificationLocale))
            ->greeting(__('auth.password_reset.mail.greeting', locale: $this->notificationLocale))
            ->line(__('auth.password_reset.mail.intro', locale: $this->notificationLocale))
            ->action(__('auth.password_reset.mail.action', locale: $this->notificationLocale), $url)
            ->line(
                __('auth.password_reset.mail.expire', [
                    'minutes' => config('auth.passwords.users.expire'),
                ], $this->notificationLocale),
            )
            ->line(__('auth.password_reset.mail.ignore', locale: $this->notificationLocale))
            ->salutation(__('auth.password_reset.mail.salutation', locale: $this->notificationLocale));
    }
}
