<?php

namespace App\Services\Accounts;

use App\Models\AccountIdentityLink;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class IdentityContacts
{
    /** Call inside a transaction before creating an identity or changing contact data. */
    public static function lock(): void
    {
        DB::table('identity_registration_locks')->where('id', 1)->lockForUpdate()->firstOrFail();
    }

    public static function assertPhoneAllowed(User|AdminUser $identity, string $phone): void
    {
        $admin = $identity instanceof AdminUser;
        $link = AccountIdentityLink::where($admin ? 'admin_user_id' : 'user_id', $identity->id)->first();
        if ($link && ($link->phone !== $phone || $link->email !== $identity->email)) {
            throw ValidationException::withMessages(['phone' => 'Este teléfono está vinculado a dos cuentas. Contacta al superadministrador antes de cambiarlo.']);
        }
        $other = $admin ? User::query()->whereNull('migrated_to_admin_at') : AdminUser::query();
        $other->where('phone', $phone);
        if ($link) {
            $other->whereKeyNot($admin ? $link->user_id : $link->admin_user_id);
        }
        if ($other->exists()) {
            throw ValidationException::withMessages(['phone' => 'El teléfono pertenece a otra identidad. Solo se comparte entre cuentas vinculadas y autorizadas.']);
        }
    }
}
