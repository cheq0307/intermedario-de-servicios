<?php

use App\Services\Accounts\PruneUnverifiedAccounts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('plaza:prune-unverified-accounts {--days=7} {--dry-run}', function (PruneUnverifiedAccounts $pruner) {
    $days = max(1, (int) $this->option('days'));
    $dryRun = (bool) $this->option('dry-run');
    $affected = $pruner->prune($days, $dryRun);

    $this->info($dryRun
        ? "{$affected} cuentas cumplen las condiciones; no se eliminó ninguna."
        : "{$affected} cuentas sin verificar fueron eliminadas.");

    return Command::SUCCESS;
})->purpose('Remove inactive, unverified accounts without marketplace or support history');

Schedule::command('plaza:expire-reservations')->everyMinute()->withoutOverlapping();
Schedule::command('plaza:maintain-conversations')->hourly()->withoutOverlapping();
Schedule::command('plaza:health-check --notify')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('plaza:prune-unverified-accounts --days=7')->dailyAt('03:30')->withoutOverlapping();
