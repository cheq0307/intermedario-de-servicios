<?php

use App\Http\Middleware\AdministrativeAccess;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\IdentitySession;
use App\Http\Middleware\MarketplaceIdentity;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prependToGroup('web', IdentitySession::class);
        $middleware->redirectGuestsTo(fn (Request $request) => route($request->is('administracion', 'administracion/*') ? 'admin.login' : 'login'));
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
            'administrative.access' => AdministrativeAccess::class,
            'marketplace.identity' => MarketplaceIdentity::class,
        ]);
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
        $middleware->validateCsrfTokens(except: ['webhooks/stripe', 'webhooks/mercado-pago']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
