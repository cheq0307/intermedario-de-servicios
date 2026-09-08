<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPassword extends ResetPassword
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Recuperar acceso administrativo · Plaza Local')
            ->line('Recibimos una solicitud para restablecer tu contraseña administrativa.')
            ->action('Restablecer contraseña administrativa', $this->resetUrl($notifiable))
            ->line('Este enlace caduca en '.config('auth.passwords.admins.expire').' minutos y no modifica tu cuenta de Plaza Local.')
            ->line('Si no lo solicitaste, ignora este correo.');
    }

    protected function resetUrl($notifiable)
    {
        return route('admin.password.reset', ['token' => $this->token, 'email' => $notifiable->email]);
    }
}
