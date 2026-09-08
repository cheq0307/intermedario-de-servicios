<?php

namespace App\Console\Commands;

use App\Domain\Administration\AdminRole;
use App\Models\AdminUser;
use App\Models\User;
use App\Services\Accounts\IdentityContacts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperadmin extends Command
{
    protected $signature = 'plaza:create-superadmin';

    protected $description = 'Crear al único superadministrador con credenciales propias';

    public function handle(): int
    {
        if (AdminUser::where('role', 'superadmin')->exists()) {
            $this->error('Ya existe un superadministrador. No se creó otro.');

            return self::FAILURE;
        }
        $data = ['name' => $this->ask('Nombre completo'), 'email' => mb_strtolower(trim((string) $this->ask('Correo administrativo'))),
            'phone' => $this->ask('Teléfono mexicano de 10 dígitos'), 'password' => $this->secret('Contraseña administrativa (mínimo 12 caracteres)')];
        $data['password_confirmation'] = $this->secret('Repite la contraseña');
        $validator = Validator::make($data, ['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'unique:admin_users,email'],
            'phone' => ['required', 'regex:/^[0-9]{10}$/', 'unique:admin_users,phone'], 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        if (User::whereNull('migrated_to_admin_at')->where(fn ($q) => $q->where('email', $data['email'])->orWhere('phone', $data['phone']))->exists()) {
            $this->error('Crea primero una identidad administrativa con datos que no pertenezcan ya a una cuenta de Plaza Local.');

            return self::FAILURE;
        }
        $created = DB::transaction(function () use ($data): bool {
            IdentityContacts::lock();
            if (AdminUser::where('role', 'superadmin')->exists()
                || AdminUser::where('email', $data['email'])->orWhere('phone', $data['phone'])->exists()
                || User::where('email', $data['email'])->orWhere('phone', $data['phone'])->exists()) {
                return false;
            }
            $admin = new AdminUser(collect($data)->except('password_confirmation')->all() + ['active' => true]);
            $admin->forceFill(['role' => AdminRole::Superadmin, 'owner_slot' => 1])->save();

            return true;
        });
        if (! $created) {
            $this->error('Ya existe el propietario o una identidad con esos datos. No se creó otra cuenta.');

            return self::FAILURE;
        }
        $this->info('Superadministrador creado. Entra en /administracion/login y verifica correo y teléfono.');

        return self::SUCCESS;
    }
}
