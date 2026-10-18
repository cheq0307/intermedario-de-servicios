<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\JobRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function create(Request $request, User $provider): View
    {
        abort_if($request->user()->is($provider), 404);

        $provider->load(['vendor.categories', 'community']);
        abort_unless(
            $provider->vendor?->status === 'active' && $provider->vendor->categories->isNotEmpty(),
            404,
        );

        $communities = Community::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'municipality']);

        return view('service-requests.create', [
            'provider' => $provider,
            'vendor' => $provider->vendor,
            'categories' => $provider->vendor->categories->sortBy('name')->values(),
            'communities' => $communities,
            'submissionToken' => (string) Str::uuid(),
        ]);
    }

    public function sent(Request $request, JobRequest $jobRequest): View
    {
        abort_unless($jobRequest->client_id === $request->user()->id, 403);
        abort_unless($jobRequest->preferred_provider_id !== null, 404);

        $jobRequest->load(['preferredProvider.vendor', 'category']);

        return view('service-requests.sent', compact('jobRequest'));
    }
}
