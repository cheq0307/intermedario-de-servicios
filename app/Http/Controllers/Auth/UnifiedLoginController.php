<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController as FortifySessionController;
use Laravel\Fortify\Http\Requests\LoginRequest;

class UnifiedLoginController extends Controller
{
    public function store(LoginRequest $request)
    {
        $email = Str::lower(trim($request->string('email')->toString()));
        $request->merge(['email' => $email]);
        $admin = AdminUser::where('email', $email)->first();
        if (! $admin || ! Hash::check($request->string('password')->toString(), $admin->password)) {
            return app(FortifySessionController::class)->store($request);
        }

        $client = User::where('email', $email)->whereNull('migrated_to_admin_at')->first();
        if (! $admin->active || ($client && Hash::check($request->string('password')->toString(), $client->password))) {
            throw ValidationException::withMessages(['email' => 'No fue posible iniciar sesión. Revisa tus credenciales o recupera tu contraseña.']);
        }

        // CSRF was checked against the public login session. Preserve it (including
        // any marketplace login/draft) before starting a separate admin session.
        $session = $request->session();
        $session->save();
        $base = config('session.marketplace_cookie', config('session.cookie'));
        config(['session.cookie' => $base.'_admin', 'session.path' => '/administracion', 'session.table' => 'admin_sessions']);
        $session->flush();
        $session->setName(config('session.cookie'));
        if (config('session.driver') === 'database') {
            $session->setHandler(new DatabaseSessionHandler(app('db')->connection(config('session.connection')), 'admin_sessions', config('session.lifetime'), app()));
        }
        $session->setId(Str::random(40));
        $session->start();
        Auth::shouldUse('admin');
        Auth::guard('admin')->login($admin, $request->boolean('remember'));
        $session->regenerate();

        return redirect()->route('admin.verification.notice');
    }

    public function recovery(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = Str::lower(trim($data['email']));
        $key = 'unified-password-reset:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw ValidationException::withMessages(['email' => 'Espera un momento antes de solicitar otro enlace de recuperación.']);
        }
        RateLimiter::hit($key, 60);
        // Each identity keeps its own broker, token and reset URL. Never reveal
        // which account types exist for the supplied email.
        foreach (['users', 'admins'] as $broker) {
            try {
                Password::broker($broker)->sendResetLink(['email' => $email]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('status', 'Si el correo está registrado, recibirás las instrucciones para recuperar el acceso a tus cuentas.');
    }
}
