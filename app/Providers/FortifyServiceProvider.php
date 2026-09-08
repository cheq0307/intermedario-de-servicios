<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->whereNull('migrated_to_admin_at')->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['admin', 'superadmin']))->where('email', Str::lower((string) $request->input(Fortify::username())))->first();

            if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            if (! $user->isAccountActive()) {
                $message = $user->account_status === 'deactivated'
                    ? 'Esta cuenta fue dada de baja. Contacta a soporte para solicitar una revisión.'
                    : 'Esta cuenta está suspendida. Contacta a soporte para solicitar una revisión.';

                throw ValidationException::withMessages(['email' => $message]);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($key);
        });

    }
}
