<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\JobRequestStatus;
use App\Domain\Marketplace\Enums\OrderStatus;
use App\Models\Conversation;
use App\Models\Order;
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
        $order->load(['buyer:id,name,avatar_path', 'vendor.user:id,name,avatar_path', 'jobRequest', 'jobProposal', 'items', 'dispute', 'reviews.author:id,name']);
        $this->authorizeParticipant($request, $order);

        $participantIds = collect([$order->buyer_id, $order->vendor->user_id])->sort()->values();
        $conversation = Conversation::where('direct_key', $participantIds->implode(':'))->first();

        return view('orders.show', compact('order', 'conversation'));
    }

    public function start(Request $request, Order $order): RedirectResponse
    {
        $this->transition($request, $order, OrderStatus::Accepted, OrderStatus::InProgress, 'provider', [
            'started_at' => now(),
        ], 'El proveedor inició el trabajo.');

        return back()->with('status', 'Trabajo marcado como iniciado.');
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $this->transition($request, $order, OrderStatus::InProgress, OrderStatus::Delivered, 'provider', [
            'delivered_at' => now(),
        ], 'El proveedor marcó el trabajo como entregado. Falta la confirmación del cliente.');

        return back()->with('status', 'Entrega registrada. Esperamos la confirmación del cliente.');
    }

    public function complete(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order): void {
            $lockedOrder = $this->lockedOrderFor($request, $order, 'buyer');
            abort_unless($lockedOrder->status === OrderStatus::Delivered, 422);

            $lockedOrder->update([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
            ]);
            $lockedOrder->jobRequest?->update(['status' => JobRequestStatus::Completed]);
            $this->recordSystemMessage($lockedOrder, 'El cliente confirmó la entrega. Trabajo completado.', $request->user()->id);
        });

        return back()->with('status', 'Confirmaste la entrega. El trabajo quedó completado.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $order, $validated): void {
            $lockedOrder = $this->lockedOrderFor($request, $order, 'participant');
            abort_unless($lockedOrder->status === OrderStatus::Accepted, 422);

            $lockedOrder->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'],
            ]);
            $lockedOrder->jobRequest?->update(['status' => JobRequestStatus::Cancelled]);
            $this->recordSystemMessage($lockedOrder, 'La contratación fue cancelada antes de iniciar. Motivo: '.$validated['reason'], $request->user()->id);
        });

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
