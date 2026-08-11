<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('plaza:grant-admin {email} {--superadmin}', function () {
    $email = mb_strtolower(trim((string) $this->argument('email')));
    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

    if (! $user) {
        $this->error('No existe una cuenta con ese correo.');

        return Command::FAILURE;
    }

    $roleName = $this->option('superadmin') ? 'superadmin' : 'admin';

    DB::transaction(function () use ($user, $roleName): void {
        if ($roleName === 'superadmin') {
            $user->syncRoles([Role::findOrCreate('superadmin')]);
            $user->vendor?->update(['status' => 'suspended', 'verified_at' => null]);

            return;
        }

        $user->assignRole(Role::findOrCreate('admin'));
    });

    $message = $roleName === 'superadmin'
        ? "La cuenta {$user->email} quedó como superadministrador exclusivo, sin capacidades comerciales."
        : "El rol admin fue asignado a {$user->email}.";
    $this->info($message);

    return Command::SUCCESS;
})->purpose('Assign an administrative role to an existing Plaza Local user');

Schedule::command('plaza:expire-reservations')->everyMinute()->withoutOverlapping();
Schedule::command('plaza:health-check --notify')->everyFiveMinutes()->withoutOverlapping();
