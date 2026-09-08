<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminIdentityMigrationTest extends TestCase
{
    public function test_legacy_actors_and_notifications_survive_separation_in_an_isolated_database(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.migration_trial' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('migration_trial');
        try {
            $migrationPath = database_path('migrations/2026_09_06_010000_separate_admin_identities.php');
            foreach (glob(database_path('migrations/*.php')) as $path) {
                if (basename($path) === basename($migrationPath)) {
                    continue;
                }
                (require $path)->up();
            }
            $client = DB::table('users')->insertGetId(['name' => 'Cliente', 'email' => 'cliente@example.test', 'password' => 'hash', 'phone' => '2220000001']);
            $oldAdmin = DB::table('users')->insertGetId(['name' => 'Propietario', 'email' => 'owner@example.test', 'password' => 'preserved-hash', 'phone' => '2220000002', 'email_verified_at' => now()]);
            $role = DB::table('roles')->where('name', 'superadmin')->where('guard_name', 'web')->value('id')
                ?? DB::table('roles')->insertGetId(['name' => 'superadmin', 'guard_name' => 'web']);
            DB::table('model_has_roles')->insert(['role_id' => $role, 'model_type' => User::class, 'model_id' => $oldAdmin]);
            DB::table('audit_logs')->insert(['user_id' => $oldAdmin, 'action' => 'account.suspended', 'subject_type' => User::class, 'subject_id' => $client, 'created_at' => now()]);
            $notificationId = (string) Str::uuid();
            DB::table('notifications')->insert(['id' => $notificationId, 'type' => 'test', 'notifiable_type' => User::class, 'notifiable_id' => $oldAdmin, 'data' => '{}']);
            (require $migrationPath)->up();
            $admin = DB::table('admin_users')->where('legacy_user_id', $oldAdmin)->first();
            $this->assertSame('owner@example.test', $admin->email);
            $this->assertSame('preserved-hash', $admin->password);
            $this->assertNull($admin->phone_verified_at);
            $this->assertSame($admin->id, DB::table('audit_logs')->value('admin_user_id'));
            $this->assertSame(AdminUser::class, DB::table('notifications')->where('id', $notificationId)->value('notifiable_type'));
            $this->assertSame($admin->id, DB::table('notifications')->where('id', $notificationId)->value('notifiable_id'));
            $this->assertNotNull(DB::table('users')->where('id', $oldAdmin)->value('migrated_to_admin_at'));
            $this->assertSame('cliente@example.test', DB::table('users')->where('id', $client)->value('email'));
            $this->assertTrue(Schema::hasTable('admin_password_reset_tokens'));
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('migration_trial');
        }
    }
}
