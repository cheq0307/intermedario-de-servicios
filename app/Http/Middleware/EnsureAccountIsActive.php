<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->isAccountActive()) {
            return $next($request);
        }

        $message = $user->account_status === 'deactivated'
            ? 'Esta cuenta fue dada de baja. Contacta a soporte si necesitas solicitar una revisión.'
            : 'Esta cuenta está suspendida. Contacta a soporte si necesitas solicitar una revisión.';

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
