<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\JobRequestStatus;
use App\Domain\Marketplace\Enums\OrderStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Jobs\FinalizeMarketplacePayment;
use App\Models\Conversation;
use App\Models\Order;
use App\Notifications\MarketplaceActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['buyer:id,name,avatar_path', 'vendor.user:id,name,avatar_path', 'jobRequest:id,public_id,title', 'items:id,order_id,name_snapshot'])
            ->where(function ($query) use ($request) {
                $query->where('buyer_id', $request->user()->id)
                    ->orWhereHas('vendor', fn ($vendorQuery) => $vendorQuery->where('user_id', $request->user()->id));
            })
            ->latest()
            ->paginate(15);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        $order->load(['buyer:id,name,avatar_path', 'vendor.user:id,name,avatar_path', 'jobRequest', 'jobProposal', 'items', 'payments', 'dispute', 'reviews.author:id,name']);
        $this->authorizeParticipant($request, $order);

        $participantIds = collect([$order->buyer_id, $order->vendor->user_id])->sort()->values();
        $conversation = Conversation::where('direct_key', $participantIds->implode(':'))->first();

        return view('orders.show', compact('order', 'conversation'));
    }

    public function simulatePayment(Request $request, Order $order): RedirectResponse
    {
        $allowed = app()->environment(['local', 'testing']) || (app()->environment('staging') && config('marketplace.allow_fake_payments'));
        abort_unless($allowed, 404);

        DB::transaction(function () use ($request, $order): void {
            $lockedOrder = Order::with(['vendor', 'jobRequest', 'payments'])->lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->buyer_id === $request->user()->id, 403);
            abort_unless($lockedOrder->fulfillment_type === 'service', 422);
            abort_unless($lockedOrder->status === OrderStatus::AwaitingPayment, 422);

            $payment = $lockedOrder->payments->firstOrFail();
            abort_unless($payment->provider === 'fake' && $payment->status === PaymentStatus::Pending, 422);

            $payment->update(['status' => PaymentStatus::Paid, 'method' => 'simulated', 'paid_at' => now()]);
            $lockedOrder->update(['status' => OrderStatus::Paid]);
            $this->recordSystemMessage($lockedOrder, 'El pago del servicio fue confirmado.', $request->user()->id);
        });
        $this->notifyCounterpart($order, $request->user()->id, 'Pago confirmado', 'El cliente confirmó el pago acordado; ya puedes iniciar el trabajo.', 'payment_paid');

        return back()->with('status', 'Pago simulado correctamente. El importe queda representado como retenido hasta completar la orden.');
    }

    public function start(Request $request, Order $order): RedirectResponse
    {
        $this->transition($request, $order, OrderStatus::Paid, OrderStatus::InProgress, 'provider', [
            'started_at' => now(),
        ], 'El proveedor inició el trabajo.');
        $this->notifyCounterpart($order, $request->user()->id, 'Trabajo iniciado', 'El proveedor marcó la contratación como iniciada.', 'order_started');

        return back()->with('status', 'Trabajo marcado como iniciado.');
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $this->transition($request, $order, OrderStatus::InProgress, OrderStatus::Delivered, 'provider', [
            'delivered_at' => now(),
        ], 'El proveedor marcó el trabajo como entregado. Falta la confirmación del cliente.');
        $this->notifyCounterpart($order, $request->user()->id, 'Trabajo entregado', 'El proveedor registró la entrega; revisa y confirma el resultado.', 'order_delivered');

        return back()->with('status', 'Entrega registrada. Esperamos la confirmación del cliente.');
    }

    public function complete(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order): void {
            $lockedOrder = $this->lockedOrderFor($request, $order, 'buyer');
            abort_unless($lockedOrder->status === OrderStatus::Delivered, 422);
            abort_unless(in_array($lockedOrder->fulfillment_type, ['service', 'pickup'], true), 422);

            $lockedOrder->update([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
            ]);
            $payment = $lockedOrder->payments()->where('status', PaymentStatus::Paid->value)->first();
            if ($payment) {
                $payment->update(['status' => PaymentStatus::ReleasePending]);
                FinalizeMarketplacePayment::dispatch($payment->id, 'release')->afterCommit();
            }
            $lockedOrder->jobRequest?->update(['status' => JobRequestStatus::Completed]);
            $this->recordSystemMessage($lockedOrder, 'El cliente confirmó la entrega. Trabajo completado.', $request->user()->id);
        });
        $this->notifyCounterpart($order, $request->user()->id, 'Trabajo completado', 'El cliente confirmó la entrega de la contratación.', 'order_completed');

        return back()->with('status', 'Confirmaste la entrega. El trabajo quedó completado.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $order, $validated): void {
            $lockedOrder = $this->lockedOrderFor($request, $order, 'participant');
            abort_unless(in_array($lockedOrder->status, [OrderStatus::Accepted, OrderStatus::AwaitingPayment], true), 422);

            $lockedOrder->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'],
            ]);
            $lockedOrder->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Cancelled->value]);
            $lockedOrder->jobRequest?->update(['status' => JobRequestStatus::Cancelled]);
            $this->recordSystemMessage($lockedOrder, 'La contratación fue cancelada antes de iniciar. Motivo: '.$validated['reason'], $request->user()->id);
        });
        $this->notifyCounterpart($order, $request->user()->id, 'Contratación cancelada', 'La otra parte canceló la contratación antes de iniciar.', 'order_cancelled');

        return back()->with('status', 'La contratación fue cancelada y el motivo quedó registrado.');
    }

    private function transition(
        Request $request,
        Order $order,
        OrderStatus $from,
        OrderStatus $to,
        string $actor,
        array $attributes,
        string $message,
    ): void {
        abort_unless($from->canTransitionTo($to), 500);

        DB::transaction(function () use ($request, $order, $from, $to, $actor, $attributes, $message): void {
            $lockedOrder = $this->lockedOrderFor($request, $order, $actor);
            abort_unless($lockedOrder->status === $from, 422);
            $lockedOrder->update(['status' => $to, ...$attributes]);
            $this->recordSystemMessage($lockedOrder, $message, $request->user()->id);
        });
    }

    private function lockedOrderFor(Request $request, Order $order, string $actor): Order
    {
        $lockedOrder = Order::query()->with(['vendor', 'jobRequest'])->lockForUpdate()->findOrFail($order->id);
        $isBuyer = $lockedOrder->buyer_id === $request->user()->id;
        $isProvider = $lockedOrder->vendor->user_id === $request->user()->id;

        abort_unless(match ($actor) {
            'buyer' => $isBuyer,
            'provider' => $isProvider,
            default => $isBuyer || $isProvider,
        }, 403);

        return $lockedOrder;
    }

    private function notifyCounterpart(Order $order, int $actorId, string $title, string $body, string $kind): void
    {
        $order->loadMissing(['buyer', 'vendor.user']);
        $recipient = $order->buyer_id === $actorId ? $order->vendor->user : $order->buyer;
        $recipient->notify(new MarketplaceActivity(
            $title,
            $body,
            'orders.show',
            ['order' => $order->public_id],
            $kind,
        ));
    }

    private function authorizeParticipant(Request $request, Order $order): void
    {
        abort_unless($order->isParticipant($request->user()), 403);
    }

    private function recordSystemMessage(Order $order, string $body, int $actorId): void
    {
        $participantIds = collect([$order->buyer_id, $order->vendor->user_id])->sort()->values();
        $conversation = Conversation::where('direct_key', $participantIds->implode(':'))->first();

        if (! $conversation) {
            return;
        }

        $conversation->messages()->create([
            'sender_id' => $actorId,
            'type' => 'system',
            'body' => $body,
            'metadata' => ['order_public_id' => $order->public_id],
        ]);
        $conversation->update(['last_message_at' => now()]);
    }
}
