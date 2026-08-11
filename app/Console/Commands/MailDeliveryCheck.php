<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailDeliveryCheck extends Command
{
    protected $signature = 'plaza:mail-check {email : Correo que recibirá la prueba} {--verification : Reenviar la verificación de una cuenta existente no verificada}';

    protected $description = 'Comprueba la configuración enviando un correo real o una verificación';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        $this->line('Mailer: '.config('mail.default'));
        $this->line('Servidor: '.(config('mail.mailers.'.config('mail.default').'.host') ?: 'administrado por el proveedor'));
        $this->line('Remitente: '.config('mail.from.address'));
        $this->line('APP_URL: '.config('app.url'));

        if (config('mail.default') === 'log') {
            $this->error('MAIL_MAILER=log no entrega correos. Configura SMTP o un proveedor transaccional y limpia la caché.');

            return self::FAILURE;
        }

        try {
            if ($this->option('verification')) {
                $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

                if (! $user) {
                    $this->error('No existe una cuenta con ese correo.');

                    return self::FAILURE;
                }

                if ($user->hasVerifiedEmail()) {
                    $this->warn('La cuenta ya tiene el correo verificado; no se envió otro enlace.');

                    return self::FAILURE;
                }

                $user->sendEmailVerificationNotification();
                $this->info('Laravel entregó la verificación al transporte de correo para '.$email.'.');
            } else {
                Mail::raw('Esta es una prueba real de entrega SMTP de Plaza Local.', function ($message) use ($email): void {
                    $message->to($email)->subject('[Plaza Local] Prueba de correo');
                });
                $this->info('Laravel entregó el correo de prueba al transporte para '.$email.'.');
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Falló el envío: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->line('Si no aparece en unos minutos, revisa spam y los registros/rebotes del proveedor SMTP.');

        return self::SUCCESS;
    }
}
