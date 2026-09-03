<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;

final class LiveUpdates
{
    public static function revision(LengthAwarePaginator $page): string
    {
        // Fingerprint the bounded page, including status and message IDs, not only second-resolution timestamps.
        return hash('sha256', json_encode([
            $page->items(), $page->total(), $page->currentPage(), now()->format('Y-m-d H:i'),
        ], JSON_THROW_ON_ERROR));
    }
}
