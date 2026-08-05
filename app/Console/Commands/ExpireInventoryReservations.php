<?php

namespace App\Console\Commands;

use App\Domain\Marketplace\Enums\OrderStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Models\InventoryReservation;
use App\Models\Listing;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireInventoryReservations extends Command
{
    protected $signature = 'plaza:expire-reservations';

    protected $description = 'Libera inventario de pedidos cuyo pago no se completó a tiempo';

    public function handle(): int
    {
        $ids = InventoryReservation::where('status', 'active')->where('expires_at', '<=', now())->pluck('id');
        $released = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$released): void {
                $reservation = InventoryReservation::lockForUpdate()->find($id);
                if (! $reservation || $reservation->status !== 'active' || $reservation->expires_at->isFuture()) {
                    return;
                }

                $order = Order::lockForUpdate()->findOrFail($reservation->order_id);
                if ($order->status !== OrderStatus::AwaitingPayment) {
                    return;
                }

                Listing::lockForUpdate()->findOrFail($reservation->listing_id)->increment('stock', $reservation->quantity);
                $reservation->update(['status' => 'released', 'released_at' => now()]);
                $order->payments()->where('status', PaymentStatus::Pending->value)->update(['status' => PaymentStatus::Cancelled->value]);
                $order->update([
                    'status' => OrderStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'La reserva de inventario venció antes del pago.',
                ]);
                $released++;
            });
        }

        $this->info("Reservas liberadas: {$released}");

        return self::SUCCESS;
    }
}
