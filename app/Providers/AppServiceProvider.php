<?php

namespace App\Providers;

use App\Contracts\MarketplacePaymentGateway;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\StripeConnectGateway;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, fn () => new StripeClient((string) config('services.stripe.secret')));
        $this->app->bind(MarketplacePaymentGateway::class, function () {
            $driver = config('marketplace.payment_driver');

            if ($driver === 'stripe') {
                throw_unless(config('services.stripe.secret'), new \RuntimeException('Falta STRIPE_SECRET.'));

                return new StripeConnectGateway(app(StripeClient::class));
            }

            if ($driver === 'fake') {
                $allowed = app()->environment(['local', 'testing']) || (app()->environment('staging') && config('marketplace.allow_fake_payments'));
                abort_unless($allowed, 503, 'Configura una pasarela de pago real para este entorno.');

                return new FakePaymentGateway;
            }

            throw new \RuntimeException("Pasarela de pago no soportada: {$driver}");
        });
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
