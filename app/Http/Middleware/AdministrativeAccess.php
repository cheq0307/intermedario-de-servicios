<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdministrativeAccess
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('admin');
        if (! Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Inicia sesión en administración.'], 401);
            }

            return redirect()->guest(route('admin.login'));
        }
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof AdminUser, 403);
        abort_unless($admin->active, 403, 'Tu acceso administrativo está suspendido.');
        if (! $admin->hasVerifiedEmail() || ! $admin->phone_verified_at) {
            return redirect()->route('admin.verification.notice');
        }

        return $next($request);
    }
}
