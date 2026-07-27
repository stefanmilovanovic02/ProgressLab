<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

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
            ->subject('Reset your ProgressLab password')
            ->greeting('Hello!')
            ->line('We received a request to reset the password for your ProgressLab account.')
            ->action('Reset password', $url)
            ->line('This link expires in ' . config('auth.passwords.users.expire') . ' minutes.')
            ->line('If you did not request this change, you can safely ignore this email.');
    }
}
