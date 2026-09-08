<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class MarketplaceIdentity
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user instanceof User && ! $user->migrated_to_admin_at && ! $user->hasAnyRole(['admin', 'superadmin']), 403, 'Utiliza una cuenta de Plaza Local para esta acción.');

        return $next($request);
    }
}
