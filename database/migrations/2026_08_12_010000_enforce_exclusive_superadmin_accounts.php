<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $superadminRoleId = DB::table('roles')->where('name', 'superadmin')->value('id');
        $commercialRoleIds = DB::table('roles')->whereIn('name', ['client', 'provider'])->pluck('id');

        if (! $superadminRoleId || $commercialRoleIds->isEmpty()) {
            return;
        }

        $superadminIds = DB::table('model_has_roles')
            ->where('role_id', $superadminRoleId)
            ->where('model_type', User::class)
            ->pluck('model_id');

        if ($superadminIds->isEmpty()) {
            return;
        }

        DB::table('model_has_roles')
            ->whereIn('model_id', $superadminIds)
            ->where('model_type', User::class)
            ->whereIn('role_id', $commercialRoleIds)
            ->delete();

        DB::table('vendors')
            ->whereIn('user_id', $superadminIds)
            ->update([
                'status' => 'suspended',
                'verified_at' => null,
                'suspension_reason' => 'Cuenta reservada exclusivamente para superadministración.',
                'suspended_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // La separación de autoridad no se revierte automáticamente para evitar
        // conceder capacidades comerciales sin una decisión expresa.
    }
};
