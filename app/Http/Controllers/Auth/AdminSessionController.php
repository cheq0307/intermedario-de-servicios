<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Accounts\SmsVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AdminSessionController extends Controller
{
    public function create()
    {
        return view('auth.admin-login');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'admin-login:'.hash('sha256', mb_strtolower($data['email']).'|'.$request->ip());
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Espera un minuto antes de volver a intentar.');
        RateLimiter::hit($key, 60);
        $admin = AdminUser::where('email', mb_strtolower(trim($data['email'])))->first();
        if (! $admin || ! $admin->active || ! Hash::check($data['password'], $admin->password)) {
            return back()->withErrors(['email' => 'Las credenciales administrativas no son válidas.'])->onlyInput('email');
        }
        $request->session()->invalidate();
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        RateLimiter::clear($key);

        return redirect()->route('admin.verification.notice');
    }

    public function destroy(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function verification(Request $request, SmsVerification $sms)
    {
        if ($request->user()->hasVerifiedEmail() && $request->user()->phone_verified_at) {
            return redirect()->route('admin.index');
        }

        return view('auth.admin-verification', ['identity' => $request->user(), 'configured' => $sms->configured()]);
    }

    public function resend(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            try {
                $request->user()->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors(['email' => 'No pudimos enviar el correo. Inténtalo más tarde.']);
            }
        }

        return back()->with('status', 'Revisa el correo de tu cuenta administrativa.');
    }

    public function verify(Request $request, int $id, string $hash)
    {
        abort_unless($id === $request->user()->id && hash_equals(sha1($request->user()->email), $hash), 403);
        $request->user()->markEmailAsVerified();

        return redirect()->route('admin.verification.notice');
    }
}
