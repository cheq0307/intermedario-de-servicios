<?php

namespace App\Support;

class IdentityRoutes
{
    public static function name(string $name): string
    {
        if (! request()->is('administracion', 'administracion/*')) {
            return $name;
        }
        if ($name === 'dashboard') {
            return 'admin.index';
        }

        return app('router')->has('admin.'.$name) ? 'admin.'.$name : $name;
    }
}
