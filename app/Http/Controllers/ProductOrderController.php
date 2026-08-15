<?php

namespace App\Http\Controllers;

use App\Contracts\MarketplacePaymentGateway;
use App\Domain\Marketplace\Enums\ListingType;
use App\Domain\Marketplace\Enums\OrderStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Domain\Marketplace\Enums\PriceType;
use App\Models\Conversation;
use App\Models\InventoryReservation;
use App\Models\Listing;
use App\Models\Order;
use App\Models\User;
use App\Notifications\MarketplaceActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductOrderController extends Controller
{
    public function checkout(Request $request, Listing $listing): View
    {
        $listing->load('vendor.user');
        $this->ensurePurchasable($request, $listing);

        return view('orders.product-checkout', compact('listing'));
    }

    public function store(Request $request, Listing $listing, MarketplacePaymentGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'buyer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($request, $listing, $validated, $gateway): Order {
            $lockedListing = Listing::with('vendor')->lockForUpdate()->findOrFail($listing->id);
            $this->ensurePurchasable($request, $lockedListing);

            $quantity = (int) $validated['quantity'];
            abort_if($lockedListing->stock === null, 422, 'Este producto todavía no tiene inventario configurado.');
            abort_if($lockedListing->stock < $quantity, 422, 'No hay suficientes piezas disponibles.');

            $subtotal = $lockedListing->price_amount * $quantity;
            $commission = (int) round($subtotal * $lockedListing->vendor->commission_rate_basis_points / 10000);

            $order = Order::create([
                'public_id' => (string) Str::uuid(),
                'buyer_id' => $request->user()->id,
                'vendor_id' => $lockedListing->vendor_id,
                'status' => OrderStatus::AwaitingPayment,
                'fulfillment_type' => 'pickup',
                'subtotal_amount' => $subtotal,
                'commission_amount' => $commission,
                'total_amount' => $subtotal,
                'currency' => $lockedListing->currency,
                'buyer_notes' => $validated['buyer_notes'] ?? null,
            ]);

            $order->items()->create([
                'listing_id' => $lockedListing->id,
                'name_snapshot' => $lockedListing->name,
                'quantity' => $quantity,
                'unit_price_amount' => $lockedListing->price_amount,
                'line_total_amount' => $subtotal,
                'metadata' => ['vendor_name' => $lockedListing->vendor->display_name],
            ]);

            $lockedListing->decrement('stock', $quantity);
            $order->inventoryReservation()->create([
                'listing_id' => $lockedListing->id,
                'quantity' => $quantity,
                'status' => 'active',
                'expires_at' => now()->addMinutes(config('marketplace.reservation_minutes')),
            ]);

            $gatewayData = $gateway->createPayment($order);
            $order->payments()->create([
                'provider' => $gatewayData['provider'],
                'provider_reference' => $gatewayData['reference'],
                'status' => PaymentStatus::Pending,
                'gross_amount' => $subtotal,
                'commission_amount' => $commission,
                'vendor_net_amount' => $subtotal - $commission,
                'currency' => $lockedListing->currency,
                'provider_payload' => $gatewayData['payload'],
            ]);

            $participantIds = collect([$request->user()->id, $lockedListing->vendor->user_id])->sort()->values();
            $conversation = Conversation::firstOrCreate(
                ['direct_key' => $participantIds->implode(':')],
                ['public_id' => (string) Str::uuid()],
            );
            $conversation->participants()->syncWithoutDetaching($participantIds->all());
            $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'type' => 'system',
                'body' => 'Se creó un pedido de producto pendiente de pago.',
                'metadata' => ['order_public_id' => $order->public_id],
            ]);
            $conversation->update(['last_message_at' => now()]);

            return $order;
        });
        $this->notify($order->vendor->user, 'Nuevo pedido recibido', 'Un cliente reservó '.$order->items()->firstOrFail()->name_snapshot.'.', $order, 'order_created');

        return redirect()->route('orders.show', $order)->with('status', 'Pedido creado. El inventario quedó reservado temporalmente.');
    }

    public function simulatePayment(Request $request, Order $order): RedirectResponse
    {
        $allowed = app()->environment(['local', 'testing']) || (app()->environment('staging') && config('marketplace.allow_fake_payments'));
        abort_unless($allowed, 404);

        DB::transaction(function () use ($request, $order): void {
            $lockedOrder = Order::with(['payments', 'inventoryReservation'])->lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->buyer_id === $request->user()->id, 403);
            abort_unless($lockedOrder->status === OrderStatus::AwaitingPayment, 422);
            abort_if($lockedOrder->inventoryReservation?->expires_at->isPast(), 422, 'La reserva venció. Cancela el pedido y vuelve a intentarlo.');

            $payment = $lockedOrder->payments->firstOrFail();
            abort_unless($payment->provider === 'fake' && $payment->status === PaymentStatus::Pending, 422);

            $payment->update(['status' => PaymentStatus::Paid, 'method' => 'simulated', 'paid_at' => now()]);
            $lockedOrder->inventoryReservation?->update(['status' => 'consumed', 'consumed_at' => now()]);
            $lockedOrder->update(['status' => OrderStatus::Paid]);
        });
        $this->notify($order->vendor->user, 'Pago confirmado', 'El pago del pedido fue confirmado; ya puedes prepararlo.', $order, 'payment_paid');

        return back()->with('status', 'Pago simulado correctamente. En producción esta acción será reemplazada por la pasarela real.');
    }

    public function ready(Request $request, Order $order): RedirectResponse
    {
        $this->vendorTransition($request, $order, OrderStatus::Paid, OrderStatus::Ready);
        $this->notify($order->buyer, 'Tu pedido está listo', 'El comercio marcó tu pedido como listo para entregar.', $order, 'order_ready');

        return back()->with('status', 'Pedido marcado como listo para entregar.');
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $this->vendorTransition($request, $order, OrderStatus::Ready, OrderStatus::Delivered, ['delivered_at' => now()]);
        $this->notify($order->buyer, 'Entrega registrada', 'El comercio registró la entrega; confirma que recibiste correctamente.', $order, 'order_delivered');

        return back()->with('status', 'Entrega registrada. Falta la confirmación del comprador.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order): void {
            $lockedOrder = Order::with(['payments', 'inventoryReservation.listing'])->lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->buyer_id === $request->user()->id, 403);
            abort_unless($lockedOrder->status === OrderStatus::AwaitingPayment, 422);

            $this->releaseReservation($lockedOrder->inventoryReservation);
            $lockedOrder->payments()->where('status', PaymentStatus::Pending->value)->update(['status' => PaymentStatus::Cancelled->value]);
            $lockedOrder->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => 'Cancelado por el comprador antes del pago.',
            ]);
        });
        $this->notify($order->vendor->user, 'Pedido cancelado', 'El comprador canceló el pedido antes del pago y se liberó el inventario.', $order, 'order_cancelled');

        return back()->with('status', 'Pedido cancelado e inventario liberado.');
    }

    private function notify(User $recipient, string $title, string $body, Order $order, string $kind): void
    {
        $recipient->notify(new MarketplaceActivity(
            $title,
            $body,
            'orders.show',
            ['order' => $order->public_id],
            $kind,
        ));
    }

    private function ensurePurchasable(Request $request, Listing $listing): void
    {
        $listing->loadMissing('post');
        abort_unless($request->user()->canActAsClient(), 403);
        abort_if($listing->post?->removed_at !== null, 404, 'Esta publicación ya no está disponible.');
        abort_unless($listing->type === ListingType::Product, 404);
        abort_unless($listing->is_active && $listing->price_type === PriceType::Fixed && $listing->price_amount !== null, 422, 'Este producto no admite compra directa.');
        abort_unless($listing->vendor->status === 'active', 422, 'El comercio no está disponible en este momento.');
        abort_if($listing->vendor->user_id === $request->user()->id, 422, 'No puedes comprar tu propio producto.');
    }

    private function vendorTransition(Request $request, Order $order, OrderStatus $from, OrderStatus $to, array $attributes = []): void
    {
        DB::transaction(function () use ($request, $order, $from, $to, $attributes): void {
            $lockedOrder = Order::with('vendor')->lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->fulfillment_type === 'pickup', 422);
            abort_unless($lockedOrder->vendor->user_id === $request->user()->id, 403);
            abort_unless($lockedOrder->status === $from && $from->canTransitionTo($to), 422);
            $lockedOrder->update(['status' => $to, ...$attributes]);
        });
    }

    private function releaseReservation(?InventoryReservation $reservation): void
    {
        if (! $reservation || $reservation->status !== 'active') {
            return;
        }

        $listing = Listing::lockForUpdate()->findOrFail($reservation->listing_id);
        $listing->increment('stock', $reservation->quantity);
        $reservation->update(['status' => 'released', 'released_at' => now()]);
    }
}
