<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\Auth;

class IdentitySession
{
    public function handle(Request $request, Closure $next)
    {
        $admin = $request->is('administracion', 'administracion/*');
        $base = config('session.marketplace_cookie', config('session.cookie'));
        config(['session.marketplace_cookie' => $base, 'session.cookie' => $admin ? $base.'_admin' : $base,
            'session.table' => $admin ? 'admin_sessions' : 'sessions', 'session.path' => $admin ? '/administracion' : '/']);
        // Keep the store used by the guards and StartSession identical. Replacing
        // the manager's driver here would lose flashed errors and saved drafts.
        $store = app('session')->driver();
        $store->setName(config('session.cookie'));
        if (config('session.driver') === 'database') {
            $store->setHandler(new DatabaseSessionHandler(
                app('db')->connection(config('session.connection')),
                config('session.table'), config('session.lifetime'), app(),
            ));
        }
        Auth::shouldUse($admin ? 'admin' : 'web');

        return $next($request);
    }
}
