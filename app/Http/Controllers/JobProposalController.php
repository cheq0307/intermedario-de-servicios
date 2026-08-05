<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\AccountType;
use App\Domain\Marketplace\Enums\JobRequestStatus;
use App\Domain\Marketplace\Enums\OrderStatus;
use App\Domain\Marketplace\Enums\ProposalStatus;
use App\Domain\Marketplace\Services\CommissionCalculator;
use App\Models\Conversation;
use App\Models\JobProposal;
use App\Models\JobRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JobProposalController extends Controller
{
    public function __construct(private readonly CommissionCalculator $commissionCalculator) {}

    public function index(Request $request, JobRequest $jobRequest): View
    {
        $isOwner = $jobRequest->client_id === $request->user()->id;
        $isProvider = $request->user()->account_type === AccountType::Provider;
        abort_unless($isOwner || $isProvider, 403);

        $jobRequest->load('client:id,name,avatar_path');
        $proposals = $jobRequest->proposals()
            ->with(['provider.vendor', 'order'])
            ->when(! $isOwner, fn ($query) => $query->where('provider_id', $request->user()->id))
            ->latest()
            ->get();
        $ownProposal = $isProvider ? $proposals->firstWhere('provider_id', $request->user()->id) : null;

        return view('proposals.index', compact('jobRequest', 'proposals', 'ownProposal', 'isOwner', 'isProvider'));
    }

    public function store(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        abort_unless($request->user()->account_type === AccountType::Provider, 403);
        abort_unless($request->user()->vendor, 422, 'Completa tu perfil comercial antes de enviar propuestas.');
        abort_if($jobRequest->client_id === $request->user()->id, 403);
        abort_unless(in_array($jobRequest->status, [JobRequestStatus::Published, JobRequestStatus::InConversation], true), 422);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:99999999.99'],
            'estimated_days' => ['required', 'integer', 'min:1', 'max:365'],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $proposal = $jobRequest->proposals()->firstOrNew(['provider_id' => $request->user()->id]);
        if ($proposal->exists && $proposal->status !== ProposalStatus::Pending) {
            throw ValidationException::withMessages(['proposal' => 'Esta propuesta ya no puede modificarse.']);
        }

        $proposal->fill([
            'public_id' => $proposal->public_id ?: (string) Str::uuid(),
            'amount' => (int) round(((float) $validated['amount']) * 100),
            'currency' => 'MXN',
            'estimated_days' => $validated['estimated_days'],
            'message' => $validated['message'],
            'status' => ProposalStatus::Pending,
        ])->save();

        if ($jobRequest->status === JobRequestStatus::Published) {
            $jobRequest->update(['status' => JobRequestStatus::InConversation]);
        }

        return redirect()->route('job-proposals.index', $jobRequest)->with('status', 'Tu propuesta fue enviada correctamente.');
    }

    public function accept(Request $request, JobRequest $jobRequest, JobProposal $proposal): RedirectResponse
    {
        $this->assertOwnerAndProposal($request, $jobRequest, $proposal);

        $order = DB::transaction(function () use ($request, $jobRequest, $proposal): Order {
            $lockedRequest = JobRequest::query()->lockForUpdate()->findOrFail($jobRequest->id);
            $lockedProposal = JobProposal::query()->with('provider.vendor')->lockForUpdate()->findOrFail($proposal->id);

            abort_unless(in_array($lockedRequest->status, [JobRequestStatus::Published, JobRequestStatus::InConversation], true), 422);
            abort_unless($lockedProposal->status === ProposalStatus::Pending, 422);
            abort_unless($lockedProposal->provider->vendor, 422, 'El proveedor debe completar su perfil comercial antes de ser contratado.');

            $vendor = $lockedProposal->provider->vendor;
            $commissionAmount = $this->commissionCalculator->calculate(
                $lockedProposal->amount,
                $vendor->commission_rate_basis_points,
            );

            $order = Order::create([
                'public_id' => (string) Str::uuid(),
                'buyer_id' => $lockedRequest->client_id,
                'vendor_id' => $vendor->id,
                'job_request_id' => $lockedRequest->id,
                'job_proposal_id' => $lockedProposal->id,
                'status' => OrderStatus::Accepted,
                'fulfillment_type' => 'service',
                'subtotal_amount' => $lockedProposal->amount,
                'commission_amount' => $commissionAmount,
                'total_amount' => $lockedProposal->amount,
                'currency' => $lockedProposal->currency,
                'buyer_notes' => $lockedRequest->description,
                'accepted_at' => now(),
                'due_at' => now()->addDays($lockedProposal->estimated_days),
            ]);
            $order->items()->create([
                'name_snapshot' => $lockedRequest->title,
                'quantity' => 1,
                'unit_price_amount' => $lockedProposal->amount,
                'line_total_amount' => $lockedProposal->amount,
                'metadata' => [
                    'proposal_message' => $lockedProposal->message,
                    'estimated_days' => $lockedProposal->estimated_days,
                ],
            ]);

            $lockedProposal->update(['status' => ProposalStatus::Accepted, 'responded_at' => now()]);
            $lockedRequest->proposals()
                ->where('id', '!=', $lockedProposal->id)
                ->where('status', ProposalStatus::Pending->value)
                ->update(['status' => ProposalStatus::Rejected->value, 'responded_at' => now()]);
            $lockedRequest->update(['status' => JobRequestStatus::Assigned]);

            $participantIds = collect([$request->user()->id, $lockedProposal->provider_id])->sort()->values();
            $conversation = Conversation::firstOrCreate(
                ['direct_key' => $participantIds->implode(':')],
                ['public_id' => (string) Str::uuid(), 'job_request_id' => $lockedRequest->id],
            );
            if (! $conversation->job_request_id) {
                $conversation->update(['job_request_id' => $lockedRequest->id]);
            }
            $conversation->participants()->syncWithoutDetaching($participantIds->all());
            $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'type' => 'system',
                'body' => 'Propuesta aceptada por $'.number_format($lockedProposal->amount / 100, 2).' MXN, plazo estimado de '.$lockedProposal->estimated_days.' días.',
                'metadata' => ['order_public_id' => $order->public_id],
            ]);
            $conversation->update(['last_message_at' => now()]);

            return $order;
        });

        return redirect()->route('orders.show', $order)->with('status', 'Propuesta aceptada. La contratación quedó registrada.');
    }

    public function reject(Request $request, JobRequest $jobRequest, JobProposal $proposal): RedirectResponse
    {
        $this->assertOwnerAndProposal($request, $jobRequest, $proposal);
        abort_unless($proposal->status === ProposalStatus::Pending, 422);
        $proposal->update(['status' => ProposalStatus::Rejected, 'responded_at' => now()]);

        return back()->with('status', 'Propuesta rechazada.');
    }

    public function withdraw(Request $request, JobRequest $jobRequest, JobProposal $proposal): RedirectResponse
    {
        abort_unless($proposal->job_request_id === $jobRequest->id && $proposal->provider_id === $request->user()->id, 403);
        abort_unless($proposal->status === ProposalStatus::Pending, 422);
        $proposal->update(['status' => ProposalStatus::Withdrawn, 'responded_at' => now()]);

        return back()->with('status', 'Retiraste tu propuesta.');
    }

    private function assertOwnerAndProposal(Request $request, JobRequest $jobRequest, JobProposal $proposal): void
    {
        abort_unless($jobRequest->client_id === $request->user()->id, 403);
        abort_unless($proposal->job_request_id === $jobRequest->id, 404);
    }
}
