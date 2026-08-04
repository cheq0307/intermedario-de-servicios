<?php

namespace Tests\Feature;

use App\Models\JobProposal;
use App\Models\JobRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_create_and_update_only_one_proposal_per_request(): void
    {
        [$client, $provider, $jobRequest] = $this->scenario();

        $this->actingAs($provider)->post(route('job-proposals.store', $jobRequest), $this->payload('850'))->assertRedirect();
        $this->actingAs($provider)->post(route('job-proposals.store', $jobRequest), $this->payload('950'))->assertRedirect();

        $this->assertDatabaseCount('job_proposals', 1);
        $this->assertDatabaseHas('job_proposals', ['job_request_id' => $jobRequest->id, 'provider_id' => $provider->id, 'amount' => 95000]);
        $this->assertSame('in_conversation', $jobRequest->fresh()->status->value);
    }

    public function test_client_accepts_one_proposal_and_remaining_proposals_are_rejected(): void
    {
        [$client, $provider, $jobRequest] = $this->scenario();
        $otherProvider = User::factory()->create(['account_type' => 'provider']);
        $this->actingAs($provider)->post(route('job-proposals.store', $jobRequest), $this->payload('850'));
        $this->actingAs($otherProvider)->post(route('job-proposals.store', $jobRequest), $this->payload('900'));
        $accepted = JobProposal::where('provider_id', $provider->id)->firstOrFail();

        $this->actingAs($client)->patch(route('job-proposals.accept', [$jobRequest, $accepted]))->assertRedirect();

        $this->assertSame('accepted', $accepted->fresh()->status->value);
        $this->assertSame('rejected', JobProposal::where('provider_id', $otherProvider->id)->firstOrFail()->status->value);
        $this->assertSame('assigned', $jobRequest->fresh()->status->value);
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseHas('messages', ['type' => 'system', 'sender_id' => $client->id]);
    }

    public function test_provider_cannot_see_competing_proposals(): void
    {
        [, $provider, $jobRequest] = $this->scenario();
        $otherProvider = User::factory()->create(['account_type' => 'provider']);
        $this->actingAs($otherProvider)->post(route('job-proposals.store', $jobRequest), $this->payload('1234'));

        $this->actingAs($provider)
            ->get(route('job-proposals.index', $jobRequest))
            ->assertOk()
            ->assertDontSee('1,234.00');
    }

    public function test_client_cannot_submit_proposal_and_outsider_cannot_accept_it(): void
    {
        [$client, $provider, $jobRequest] = $this->scenario();
        $outsider = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)->post(route('job-proposals.store', $jobRequest), $this->payload('850'))->assertForbidden();
        $this->actingAs($provider)->post(route('job-proposals.store', $jobRequest), $this->payload('850'));
        $proposal = JobProposal::firstOrFail();
        $this->actingAs($outsider)->patch(route('job-proposals.accept', [$jobRequest, $proposal]))->assertForbidden();
    }

    private function scenario(): array
    {
        $client = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create(['account_type' => 'provider']);
        $jobRequest = JobRequest::create([
            'public_id' => (string) Str::uuid(),
            'client_id' => $client->id,
            'title' => 'Reparación de instalación eléctrica',
            'description' => 'Necesito revisar una instalación que presenta fallas.',
            'status' => 'published',
            'urgency' => 'soon',
            'published_at' => now(),
        ]);

        return [$client, $provider, $jobRequest];
    }

    private function payload(string $amount): array
    {
        return ['amount' => $amount, 'estimated_days' => 2, 'message' => 'Incluye diagnóstico, mano de obra y revisión final.'];
    }
}
