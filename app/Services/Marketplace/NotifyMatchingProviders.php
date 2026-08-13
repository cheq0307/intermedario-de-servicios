<?php

namespace App\Services\Marketplace;

use App\Models\JobRequest;
use App\Models\User;
use App\Notifications\MarketplaceActivity;

class NotifyMatchingProviders
{
    public function handle(JobRequest $jobRequest): int
    {
        if (! $jobRequest->category_id) {
            return 0;
        }

        $jobRequest->loadMissing(['communities:id,name', 'client:id,name']);
        $targetIds = $jobRequest->communities->pluck('id');
        $targetNames = $jobRequest->communities->pluck('name')->join(', ');
        $notified = 0;

        User::query()
            ->whereKeyNot($jobRequest->client_id)
            ->whereHas('vendor', fn ($vendor) => $vendor
                ->where('status', 'active')
                ->whereHas('categories', fn ($categories) => $categories->whereKey($jobRequest->category_id)))
            ->with('community:id,name')
            ->chunkById(100, function ($users) use ($jobRequest, $targetIds, $targetNames, &$notified): void {
                foreach ($users as $user) {
                    $insideTarget = $targetIds->contains($user->community_id);
                    $area = $insideTarget
                        ? 'Tu localidad está dentro del área solicitada.'
                        : 'La solicitud eligió '.($targetNames ?: 'otras localidades').', pero puedes proponer si puedes atenderla.';
                    $user->notify(new MarketplaceActivity(
                        'Nueva solicitud relacionada con tus servicios',
                        $jobRequest->client->name.' necesita “'.$jobRequest->title.'”. '.$area,
                        'job-proposals.index',
                        ['jobRequest' => $jobRequest->public_id],
                        $insideTarget ? 'matching_request_local' : 'matching_request_nearby',
                    ));
                    $notified++;
                }
            });

        return $notified;
    }
}
