<?php

namespace App\Console\Commands;

use App\Models\MessageAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneChatFiles extends Command
{
    protected $signature = 'plaza:prune-chat-files {--dry-run}';

    protected $description = 'Limpia imágenes huérfanas del chat de más de 24 horas en el disco privado';

    public function handle(): int
    {
        $diskName = config('chat.disk');
        $disk = Storage::disk($diskName);
        $count = 0;
        foreach ($disk->allFiles('chat') as $path) {
            if ($disk->lastModified($path) >= now()->subDay()->timestamp
                || MessageAttachment::where('disk', $diskName)->where('path', $path)->exists()) {
                continue;
            }
            if ($this->option('dry-run') || $disk->delete($path)) {
                $count++;
            }
        }
        $this->info($count.' archivos huérfanos '.($this->option('dry-run') ? 'detectados' : 'eliminados'));

        return self::SUCCESS;
    }
}
