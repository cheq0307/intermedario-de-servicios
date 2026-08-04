<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Verifica tu correo en Plaza Local')
                ->greeting('¡Hola!')
                ->line('Recibimos un registro en Plaza Local usando este correo electrónico.')
                ->line('Presiona el botón para confirmar que la cuenta te pertenece.')
                ->action('Verificar mi correo', $url)
                ->line('Si tú no creaste esta cuenta, puedes ignorar este mensaje.');
        });
    }
}
