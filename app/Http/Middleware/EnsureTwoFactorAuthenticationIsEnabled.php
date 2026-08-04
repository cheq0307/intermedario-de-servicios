<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('security')->with('status', 'two-factor-required');
        }

        return $next($request);
    }
}
