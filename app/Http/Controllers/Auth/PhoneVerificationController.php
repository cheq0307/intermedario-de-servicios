<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccountIdentityLink;
use App\Models\AdminUser;
use App\Services\Accounts\IdentityContacts;
use App\Services\Accounts\SmsVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class PhoneVerificationController extends Controller
{
    public function show(Request $request, SmsVerification $sms)
    {
        return view('auth.verify-phone', ['identity' => $request->user(), 'administrative' => $request->user() instanceof AdminUser, 'configured' => $sms->configured()]);
    }

    public function send(Request $request, SmsVerification $sms)
    {
        $identity = $request->user();
        $admin = $identity instanceof AdminUser;
        abort_if($admin && ! $identity->active, 403);
        $data = $request->validate(['phone' => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique($admin ? 'admin_users' : 'users', 'phone')->ignore($identity->id)]]);
        IdentityContacts::assertPhoneAllowed($identity, $data['phone']);
        $link = AccountIdentityLink::where($admin ? 'admin_user_id' : 'user_id', $identity->id)->first();
        abort_if($link && $link->phone !== $data['phone'], 422, 'El teléfono está vinculado a tus dos cuentas. Contacta al superadministrador para actualizar la identidad.');
        foreach (['sms:ip:'.$request->ip() => 10, 'sms:phone:'.$data['phone'] => 5, 'sms:identity:'.($admin ? 'admin:' : 'user:').$identity->id => 5] as $key => $limit) {
            abort_if(RateLimiter::tooManyAttempts($key, $limit), 429, 'Demasiados SMS solicitados. Inténtalo más tarde.');
            RateLimiter::hit($key, 3600);
        }
        $sid = $sms->send($data['phone']);
        $request->session()->put($admin ? 'admin.phone_challenge' : 'user.phone_challenge',
            ['sid' => $sid, 'phone' => $data['phone'], 'identity_id' => $identity->id, 'expires' => now()->addMinutes(10)->timestamp, 'attempts' => 0]);

        return back()->with('status', 'Enviamos un código por SMS. Caduca en 10 minutos.');
    }

    public function verify(Request $request, SmsVerification $sms)
    {
        $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{4}$/']], ['code.regex' => 'El código debe contener exactamente 4 dígitos.']);
        $identity = $request->user();
        $admin = $identity instanceof AdminUser;
        $key = $admin ? 'admin.phone_challenge' : 'user.phone_challenge';
        $challenge = $request->session()->get($key);
        abort_unless($challenge && $challenge['identity_id'] === $identity->id && $challenge['expires'] > now()->timestamp && $challenge['attempts'] < 5, 422, 'Solicita un código nuevo.');
        $challenge['attempts']++;
        $request->session()->put($key, $challenge);
        if (! $sms->check($challenge['sid'], $request->string('code')->toString())) {
            return back()->withErrors(['code' => 'El código es incorrecto.']);
        }
        DB::transaction(function () use ($identity, $challenge, $admin) {
            IdentityContacts::lock();
            $locked = $identity->newQuery()->lockForUpdate()->findOrFail($identity->id);
            abort_if($admin && ! $locked->active, 403);
            IdentityContacts::assertPhoneAllowed($locked, $challenge['phone']);
            $link = AccountIdentityLink::where($admin ? 'admin_user_id' : 'user_id', $identity->id)->first();
            abort_if($link && $link->phone !== $challenge['phone'], 422);
            abort_if($identity->newQuery()->where('phone', $challenge['phone'])->whereKeyNot($identity->id)->exists(), 422, 'El teléfono ya pertenece a otra cuenta.');
            $locked->forceFill(['phone' => $challenge['phone'], 'phone_verified_at' => now()])->save();
            if (! $admin && $locked->vendor) {
                $locked->vendor->update(['phone' => $challenge['phone']]);
            }
        });
        $request->session()->forget($key);

        return redirect()->route($admin ? 'admin.verification.notice' : 'profile.edit')->with('status', 'Teléfono verificado correctamente.');
    }
}
