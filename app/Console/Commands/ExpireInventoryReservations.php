<?php

namespace App\Console\Commands;

use App\Domain\Marketplace\Enums\OrderStatus;
use App\Models\InventoryReservation;
use App\Models\Listing;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireInventoryReservations extends Command
{
    protected $signature = 'plaza:expire-reservations';

    protected $description = 'Libera inventario de pedidos que no avanzaron a preparación a tiempo';

    public function handle(): int
    {
        $ids = InventoryReservation::where('status', 'active')->where('expires_at', '<=', now())->pluck('id');
        $released = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$released): void {
                $reference = InventoryReservation::find($id);
                if (! $reference) {
                    return;
                }
                // Match order cancellation locking order: order, then reservation.
                $order = Order::lockForUpdate()->findOrFail($reference->order_id);
                $reservation = InventoryReservation::lockForUpdate()->find($id);
                if (! $reservation || $reservation->status !== 'active' || $reservation->expires_at->isFuture()) {
                    return;
                }

                if ($order->status !== OrderStatus::Accepted) {
                    return;
                }

                Listing::lockForUpdate()->findOrFail($reservation->listing_id)->increment('stock', $reservation->quantity);
                $reservation->update(['status' => 'released', 'released_at' => now()]);
                $order->update([
                    'status' => OrderStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'La reserva de inventario venció antes de preparar el pedido.',
                ]);
                $order->conversation?->archive();
                $released++;
            });
        }

        $this->info("Reservas liberadas: {$released}");

        return self::SUCCESS;
    }
}
