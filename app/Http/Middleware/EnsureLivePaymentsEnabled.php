<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureLivePaymentsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            config('marketplace.payments_enabled') && config('marketplace.live_payments_enabled'),
            503,
            'Los pagos reales están deshabilitados en esta versión de Plaza Local.',
        );

        return $next($request);
    }
}
