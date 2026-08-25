<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Services\Marketplace\NegotiationConversationService;
use Illuminate\Console\Command;

class MaintainConversations extends Command
{
    protected $signature = 'plaza:maintain-conversations';

    protected $description = 'Cierra negociaciones vencidas y elimina historiales cuyo periodo de conservación terminó';

    public function handle(NegotiationConversationService $service): int
    {
        $expired = 0;
        Conversation::where('type', 'negotiation')
            ->where('state', 'active')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($conversations) use ($service, &$expired): void {
                foreach ($conversations as $conversation) {
                    $service->expireIfNeeded($conversation);
                    $expired++;
                }
            });

        $purged = Conversation::where('type', 'negotiation')
            ->where('state', 'archived')
            ->whereNotNull('retention_until')
            ->where('retention_until', '<=', now())
            ->delete();

        $this->info("Conversaciones vencidas: {$expired}; historiales eliminados: {$purged}");

        return self::SUCCESS;
    }
}
