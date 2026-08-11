<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PharData;

class VerifyBackup extends Command
{
    protected $signature = 'plaza:verify-backup {database : Archivo .sql.gz} {uploads? : Archivo .tar.gz opcional}';

    protected $description = 'Valida integridad y contenido minimo de respaldos de Plaza Local';

    public function handle(): int
    {
        $database = realpath((string) $this->argument('database'));
        if (! $database || ! str_ends_with($database, '.sql.gz')) {
            return $this->failure('El respaldo de base no existe o no es .sql.gz.');
        }
        $stream = gzopen($database, 'rb');
        if ($stream === false) {
            return $this->failure('No se pudo abrir el gzip de base de datos.');
        }
        $sample = '';
        while (! gzeof($stream) && strlen($sample) < 5_000_000) {
            $sample .= (string) gzread($stream, 8192);
        }
        gzclose($stream);
        foreach (['users', 'orders', 'payments'] as $table) {
            if (! str_contains($sample, '`'.$table.'`')) {
                return $this->failure("El SQL no contiene la tabla {$table}.");
            }
        }

        if ($uploads = $this->argument('uploads')) {
            $uploads = realpath((string) $uploads);
            if (! $uploads) {
                return $this->failure('El respaldo de archivos no existe.');
            }
            try {
                new PharData($uploads);
            } catch (\Throwable $e) {
                return $this->failure('El respaldo de archivos no es legible: '.$e->getMessage());
            }
        }

        $this->info('Respaldos integros y con las tablas minimas esperadas.');

        return self::SUCCESS;
    }

    private function failure(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}
