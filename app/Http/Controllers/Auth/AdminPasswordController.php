<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccountIdentityLink;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AdminPasswordController extends Controller
{
    public function request()
    {
        return view('auth.admin-forgot-password');
    }

    public function send(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        try {
            Password::broker('admins')->sendResetLink(['email' => Str::lower(trim($data['email'])), 'active' => true]);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => 'No pudimos enviar el enlace. Inténtalo más tarde.']);
        }

        return back()->with('status', 'Si existe una cuenta administrativa activa con ese correo, recibirá un enlace de recuperación.');
    }

    public function reset(Request $request, string $token)
    {
        return view('auth.admin-reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255'], 'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->numbers()]]);
        $data['email'] = Str::lower(trim($data['email']));
        $status = Password::broker('admins')->reset($data + ['active' => true], function (AdminUser $admin, string $password): void {
            $userId = AccountIdentityLink::where('admin_user_id', $admin->id)->value('user_id');
            if ($userId && Hash::check($password, User::findOrFail($userId)->password)) {
                throw ValidationException::withMessages(['password' => 'Usa una contraseña distinta de la de tu cuenta de Plaza Local.']);
            }
            DB::transaction(function () use ($admin, $password): void {
                $admin->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                DB::table('admin_sessions')->where('user_id', $admin->id)->delete();
            });
            event(new PasswordReset($admin));
            if (Auth::guard('admin')->id() === $admin->id) {
                Auth::guard('admin')->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }
        });

        return $status === Password::PasswordReset
            ? redirect()->route('admin.login')->with('status', 'Contraseña administrativa actualizada. Inicia sesión de nuevo.')
            : back()->withErrors(['email' => 'El enlace no es válido o ya caducó. Solicita uno nuevo.']);
    }
}
