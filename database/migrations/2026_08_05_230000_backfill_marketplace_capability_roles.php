<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['client', 'provider'] as $roleName) {
            DB::table('roles')->insertOrIgnore([
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', ['client', 'provider'])
            ->pluck('id', 'name');

        DB::table('users')
            ->select(['id', 'account_type'])
            ->whereIn('account_type', ['client', 'provider'])
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($roleIds): void {
                $rows = $users->map(fn ($user): array => [
                    'role_id' => $roleIds[$user->account_type],
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ])->all();

                DB::table('model_has_roles')->insertOrIgnore($rows);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Los roles pueden haber sido combinados después de esta migración.
        // No se eliminan para evitar pérdida de autorización o trazabilidad.
    }
};
