<?php

namespace App\Console\Commands;

use App\Models\PostalCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportPostalCodes extends Command
{
    protected $signature = 'plaza:import-postal-codes {file : TXT oficial de Correos de México}';

    protected $description = 'Importa el Catálogo Nacional de Códigos Postales desde el TXT oficial delimitado por |';

    public function handle(): int
    {
        $path = realpath((string) $this->argument('file'));
        if ($path === false || ! is_readable($path)) {
            $this->error('No se puede leer el archivo indicado.');

            return self::FAILURE;
        }

        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('No fue posible abrir el catálogo.');
        }

        $header = $this->readColumns($stream);
        $indexes = array_flip(array_map(fn (string $value) => mb_strtolower(trim($value)), $header));
        foreach (['d_codigo', 'd_asenta', 'd_mnpio', 'd_estado'] as $required) {
            if (! array_key_exists($required, $indexes)) {
                fclose($stream);
                $this->error("El TXT no contiene la columna oficial {$required}.");

                return self::FAILURE;
            }
        }

        $count = 0;
        $batch = [];
        while (($columns = $this->readColumns($stream)) !== []) {
            if (count($columns) < count($header)) {
                continue;
            }
            $value = fn (string $column): ?string => isset($indexes[$column]) ? ($columns[$indexes[$column]] !== '' ? $columns[$indexes[$column]] : null) : null;
            $batch[] = [
                'postal_code' => str_pad((string) $value('d_codigo'), 5, '0', STR_PAD_LEFT),
                'settlement' => (string) $value('d_asenta'),
                'settlement_type' => $value('d_tipo_asenta'),
                'municipality' => (string) $value('d_mnpio'),
                'state' => (string) $value('d_estado'),
                'city' => $value('d_ciudad'),
                'state_code' => $value('c_estado'),
                'municipality_code' => $value('c_mnpio'),
                'settlement_code' => $value('id_asenta_cpcons'),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                $count += $this->store($batch);
                $batch = [];
            }
        }
        fclose($stream);
        $count += $this->store($batch);

        $this->info("Catálogo importado: {$count} asentamientos procesados.");

        return self::SUCCESS;
    }

    private function readColumns(mixed $stream): array
    {
        $line = fgets($stream);
        if ($line === false) {
            return [];
        }

        $line = mb_convert_encoding($line, 'UTF-8', ['UTF-8', 'Windows-1252', 'ISO-8859-1']);
        $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;

        return array_map('trim', explode('|', trim($line)));
    }

    private function store(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        DB::transaction(fn () => PostalCode::query()->upsert(
            $rows,
            ['postal_code', 'settlement', 'municipality'],
            ['settlement_type', 'state', 'city', 'state_code', 'municipality_code', 'settlement_code', 'updated_at'],
        ));

        return count($rows);
    }
}
