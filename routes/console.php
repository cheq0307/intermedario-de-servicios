<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
    $user->assignRole(Role::findOrCreate($roleName));
    $this->info("El rol {$roleName} fue asignado a {$user->email}.");

    return Command::SUCCESS;
})->purpose('Assign an administrative role to an existing Plaza Local user');

Schedule::command('plaza:expire-reservations')->everyMinute()->withoutOverlapping();
Schedule::command('plaza:health-check --notify')->everyFiveMinutes()->withoutOverlapping();
