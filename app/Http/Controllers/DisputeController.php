<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\DisputeStatus;
use App\Domain\Marketplace\Enums\JobRequestStatus;
use App\Domain\Marketplace\Enums\OrderStatus;
use App\Domain\Marketplace\Enums\PaymentStatus;
use App\Jobs\FinalizeMarketplacePayment;
use App\Models\Conversation;
use App\Models\Dispute;
use App\Models\Order;
use App\Notifications\MarketplaceActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $this->authorizeAdmin($request);
        $disputes = Dispute::with(['order.jobRequest', 'opener:id,name'])
            ->latest()
            ->paginate(20);

        return view('disputes.admin-index', compact('disputes'));
    }

    public function show(Request $request, Dispute $dispute): View
    {
        $dispute->load([
            'order.buyer:id,name', 'order.vendor.user:id,name', 'order.jobRequest',
            'opener:id,name', 'resolver:id,name', 'messages.user:id,name',
        ]);
        $this->authorizeViewer($request, $dispute);
        $isAdmin = $this->isAdmin($request);

        return view('disputes.show', compact('dispute', 'isAdmin'));
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', Rule::in(['not_delivered', 'different_work', 'price_problem', 'poor_service', 'no_show', 'other'])],
            'description' => ['required', 'string', 'min:30', 'max:3000'],
        ]);

        $dispute = DB::transaction(function () use ($request, $order, $validated): Dispute {
            $lockedOrder = Order::with('vendor')->lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->isParticipant($request->user()), 403);
            abort_unless(in_array($lockedOrder->status, [OrderStatus::Paid, OrderStatus::InProgress, OrderStatus::Ready, OrderStatus::Delivered], true), 422);
            abort_if($lockedOrder->dispute()->exists(), 422, 'Esta orden ya tiene una disputa.');

            $dispute = Dispute::create([
                'public_id' => (string) Str::uuid(),
                'order_id' => $lockedOrder->id,
                'opened_by' => $request->user()->id,
                'reason' => $validated['reason'],
                'status' => DisputeStatus::Open,
                'description' => $validated['description'],
                'order_status_before' => $lockedOrder->status->value,
            ]);
            $dispute->messages()->create(['user_id' => $request->user()->id, 'body' => $validated['description']]);
            $lockedOrder->update(['status' => OrderStatus::Disputed]);
            $this->recordSystemMessage($lockedOrder, 'Se abrió una disputa sobre esta contratación.', $request->user()->id);

            return $dispute;
        });
        $this->notifyParticipants($dispute, 'Se abrió una disputa', 'La otra parte reportó un problema en la contratación.', $request->user()->id);

        return redirect()->route('disputes.show', $dispute)->with('status', 'La incidencia quedó registrada para revisión.');
    }

    public function reply(Request $request, Dispute $dispute): RedirectResponse
    {
        $dispute->load('order.vendor');
        $this->authorizeViewer($request, $dispute);
        abort_unless($dispute->status === DisputeStatus::Open, 422);
        $validated = $request->validate(['body' => ['required', 'string', 'min:2', 'max:2000']]);
        $dispute->messages()->create(['user_id' => $request->user()->id, 'body' => $validated['body']]);

        return back()->with('status', 'Tu respuesta quedó agregada al expediente.');
    }

    public function resolve(Request $request, Dispute $dispute): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'outcome' => ['required', Rule::in(['resume', 'complete', 'cancel'])],
            'resolution' => ['required', 'string', 'min:20', 'max:3000'],
        ]);

        DB::transaction(function () use ($request, $dispute, $validated): void {
            $lockedDispute = Dispute::lockForUpdate()->findOrFail($dispute->id);
            abort_unless($lockedDispute->status === DisputeStatus::Open, 422);
            $order = Order::with(['vendor', 'jobRequest', 'payments'])->lockForUpdate()->findOrFail($lockedDispute->order_id);
            abort_unless($order->status === OrderStatus::Disputed, 422);

            $nextStatus = match ($validated['outcome']) {
                'resume' => OrderStatus::tryFrom($lockedDispute->order_status_before) ?? OrderStatus::InProgress,
                'complete' => OrderStatus::Completed,
                'cancel' => OrderStatus::Cancelled,
            };
            abort_unless(OrderStatus::Disputed->canTransitionTo($nextStatus), 422);

            $orderAttributes = ['status' => $nextStatus];
            if ($nextStatus === OrderStatus::Completed) {
                $orderAttributes['completed_at'] = now();
                $order->jobRequest?->update(['status' => JobRequestStatus::Completed]);
            } elseif ($nextStatus === OrderStatus::Cancelled) {
                $orderAttributes['cancelled_at'] = now();

                $orderAttributes['cancellation_reason'] = 'Resolución administrativa de disputa: '.$validated['resolution'];
                $order->jobRequest?->update(['status' => JobRequestStatus::Cancelled]);
            }

            if ($nextStatus === OrderStatus::Completed) {
                $payment = $order->payments()->where('status', PaymentStatus::Paid->value)->first();
                if ($payment) {
                    $payment->update(['status' => PaymentStatus::ReleasePending]);
                    FinalizeMarketplacePayment::dispatch($payment->id, 'release')->afterCommit();
                }
            } elseif ($nextStatus === OrderStatus::Cancelled) {
                $order->payments()->where('status', PaymentStatus::Pending->value)->update(['status' => PaymentStatus::Cancelled->value]);
                $payment = $order->payments()->where('status', PaymentStatus::Paid->value)->first();
                if ($payment) {
                    $payment->update(['status' => PaymentStatus::RefundPending]);
                    FinalizeMarketplacePayment::dispatch($payment->id, 'refund')->afterCommit();
                }
            }
            $order->update($orderAttributes);
            $lockedDispute->update([
                'status' => DisputeStatus::Resolved,
                'resolution_outcome' => $validated['outcome'],
                'resolution' => $validated['resolution'],
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);
            $this->recordSystemMessage($order, 'La disputa fue resuelta: '.$validated['resolution'], $request->user()->id);
        });
        $this->notifyParticipants($dispute, 'Disputa resuelta', 'Administración registró una resolución en el expediente.');

        return back()->with('status', 'La disputa fue resuelta y la orden actualizada.');
    }

    private function notifyParticipants(Dispute $dispute, string $title, string $body, ?int $excludeUserId = null): void
    {
        $dispute->loadMissing(['order.buyer', 'order.vendor.user']);
        $participants = collect([$dispute->order->buyer, $dispute->order->vendor->user])
            ->filter(fn ($user) => $user->id !== $excludeUserId)
            ->unique('id');

        foreach ($participants as $user) {
            $user->notify(new MarketplaceActivity(
                $title,
                $body,
                'disputes.show',
                ['dispute' => $dispute->public_id],
                'dispute',
            ));
        }
    }

    private function authorizeViewer(Request $request, Dispute $dispute): void
    {
        abort_unless($this->isAdmin($request) || $dispute->order->isParticipant($request->user()), 403);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($this->isAdmin($request), 403);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()->hasAnyRole(['admin', 'superadmin']);
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
