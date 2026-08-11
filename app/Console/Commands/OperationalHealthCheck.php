<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OperationalHealthCheck extends Command
{
    protected $signature = 'plaza:health-check {--notify : Enviar correo si alguna comprobacion falla}';

    protected $description = 'Comprueba base de datos, cache, almacenamiento y cola fallida';

    public function handle(): int
    {
        $checks = [];
        $checks['database'] = $this->attempt(fn () => DB::select('select 1'));
        $checks['cache'] = $this->attempt(function (): void {
            Cache::put('health-check', 'ok', 30);
            throw_unless(Cache::get('health-check') === 'ok', new \RuntimeException('No se pudo leer el cache.'));
            Cache::forget('health-check');
        });
        $checks['storage'] = $this->attempt(function (): void {
            Storage::disk('local')->put('health-check.txt', now()->toIso8601String());
            throw_unless(Storage::disk('local')->exists('health-check.txt'), new \RuntimeException('No se pudo escribir en storage.'));
            Storage::disk('local')->delete('health-check.txt');
        });
        $checks['failed_jobs'] = $this->attempt(function (): void {
            if (DB::table('failed_jobs')->count() > 0) {
                throw new \RuntimeException('Existen trabajos fallidos en la cola.');
            }
        });

        foreach ($checks as $name => $result) {
            $this->line(sprintf('%s %s%s', $result['ok'] ? '[OK]' : '[ERROR]', $name, $result['message'] ? ': '.$result['message'] : ''));
        }

        $failures = array_filter($checks, fn (array $check) => ! $check['ok']);
        if ($failures === []) {
            return self::SUCCESS;
        }

        Log::critical('Fallo en comprobacion operativa de Plaza Local', ['checks' => $checks]);
        if ($this->option('notify') && config('marketplace.operations_alert_email') && Cache::add('operations-alert-cooldown', true, now()->addMinutes(15))) {
            Mail::raw("Plaza Local detecto fallos:\n".collect($failures)->map(fn ($v, $k) => $k.': '.$v['message'])->implode("\n"), function ($message): void {
                $message->to(config('marketplace.operations_alert_email'))->subject('[Plaza Local] Alerta operativa');
            });
        }

        return self::FAILURE;
    }

    private function attempt(callable $callback): array
    {
        try {
            $callback();

            return ['ok' => true, 'message' => null];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
    }
}
