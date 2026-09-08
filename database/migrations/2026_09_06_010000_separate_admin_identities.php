<?php

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $owners = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_type', User::class)->where('roles.name', 'superadmin')->distinct()->count('model_id');
        if ($owners > 1) {
            throw new RuntimeException('Hay varios superadministradores antiguos. Define al propietario único antes de migrar; no se ha cambiado ninguna tabla.');
        }
        Schema::create('identity_registration_locks', fn (Blueprint $table) => $table->unsignedTinyInteger('id')->primary());
        DB::table('identity_registration_locks')->insert(['id' => 1]);
        Schema::create('admin_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('admin_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable()->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('owner_slot')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->unsignedBigInteger('legacy_user_id')->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('migrated_to_admin_at')->nullable();
        });
        Schema::create('account_identity_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->unique()->constrained('admin_users')->restrictOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('approved_by_admin_id')->nullable()->constrained('admin_users')->restrictOnDelete();
            $table->string('email');
            $table->string('phone', 30);
            $table->timestamp('approved_at');
            $table->timestamps();
        });
        Schema::create('admin_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('phone', 30)->unique();
            $table->string('name', 120);
            $table->string('token_hash', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('invited_by')->constrained('admin_users')->restrictOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
        foreach (['audit_logs', 'users', 'vendors', 'posts', 'vendor_verification_documents', 'post_promotions', 'support_tickets', 'disputes', 'support_messages', 'dispute_messages', 'messages'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('admin_user_id')->nullable()->constrained('admin_users')->restrictOnDelete();
            });
        }
        Schema::table('support_messages', fn (Blueprint $table) => $table->unsignedBigInteger('sender_id')->nullable()->change());
        Schema::table('dispute_messages', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable()->change());
        Schema::table('messages', fn (Blueprint $table) => $table->unsignedBigInteger('sender_id')->nullable()->change());
        $legacy = DB::table('users')->whereExists(function ($query) {
            $query->selectRaw('1')->from('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereColumn('model_has_roles.model_id', 'users.id')->where('model_type', User::class)
                ->whereIn('roles.name', ['admin', 'superadmin']);
        })->get();
        foreach ($legacy as $user) {
            $owner = DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_id', $user->id)->where('model_type', User::class)->where('roles.name', 'superadmin')->exists();
            $id = DB::table('admin_users')->insertGetId([
                'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone, 'password' => $user->password,
                'email_verified_at' => $user->email_verified_at, 'role' => $owner ? 'superadmin' : 'admin',
                'owner_slot' => $owner ? 1 : null, 'active' => $user->account_status === 'active',
                'legacy_user_id' => $user->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (['audit_logs' => 'user_id', 'users' => 'account_status_changed_by_user_id', 'vendors' => 'verified_by_user_id',
                'posts' => 'removed_by_user_id', 'vendor_verification_documents' => 'reviewed_by_user_id',
                'post_promotions' => 'reviewed_by_user_id', 'support_tickets' => 'assigned_admin_id',
                'disputes' => 'resolved_by', 'support_messages' => 'sender_id', 'dispute_messages' => 'user_id', 'messages' => 'sender_id'] as $table => $column) {
                DB::table($table)->where($column, $user->id)->update(['admin_user_id' => $id]);
            }
            DB::table('notifications')->where('notifiable_type', User::class)->where('notifiable_id', $user->id)
                ->update(['notifiable_type' => AdminUser::class, 'notifiable_id' => $id]);
            // Preserve legacy rows as read-only history; they cannot authenticate or appear as marketplace accounts.
            DB::table('users')->where('id', $user->id)->update(['migrated_to_admin_at' => now(), 'account_status' => 'deactivated',
                'email' => 'archived-admin-'.$user->id.'-'.Str::uuid().'@invalid.local', 'phone' => null]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Esta separación conserva historial. Restaura el respaldo completo para revertirla.');
    }
};
