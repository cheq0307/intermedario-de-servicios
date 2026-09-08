<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminVerifyEmail extends Notification
{
    public function __construct(private readonly string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Verifica tu acceso administrativo a Plaza Local')
            ->line('Confirma el correo de tu cuenta administrativa.')
            ->action('Verificar correo administrativo', $this->url);
    }
}
