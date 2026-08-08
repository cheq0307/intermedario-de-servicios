<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\JobRequestStatus;
use App\Models\JobRequest;
use App\Models\Listing;
use App\Models\Post;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $providerTypes = ['portfolio', 'business_update', 'product', 'service', 'promotion'];
        $allowedTypes = array_merge(
            $request->user()->canActAsClient() ? ['job_request'] : [],
            $request->user()->canActAsProvider() ? $providerTypes : [],
        );

        $postData = $request->validate([
            'submission_token' => ['required', 'uuid'],
            'type' => ['required', 'string', Rule::in($allowedTypes)],
            'body' => ['required', 'string', 'min:10', 'max:1500'],
        ]);

        $isProvider = in_array($postData['type'], $providerTypes, true);
        if ($isProvider) {
            abort_unless($request->user()->vendor?->status === 'active', 422, 'Tu perfil comercial debe ser aprobado antes de publicar ofertas.');
        }

        return Cache::lock('publication:'.$postData['submission_token'], 10)
            ->block(5, function () use ($request, $postData, $isProvider): RedirectResponse {
                if (Post::where('submission_token', $postData['submission_token'])->exists()) {
                    return redirect()
                        ->route('dashboard')
                        ->with('status', 'La publicación ya había sido procesada; no se creó un duplicado.');
                }

                if (! $isProvider) {
                    $this->publishJobRequest($request, $postData);
                } elseif (in_array($postData['type'], ['product', 'service'], true)) {
                    $this->publishListing($request, $postData);
                } else {
                    Post::create([
                        ...$postData,
                        'user_id' => $request->user()->id,
                        'published_at' => now(),
                    ]);
                }

                return redirect()
                    ->route('dashboard')
                    ->with('status', 'Tu publicación ya está visible en la comunidad.');
            });
    }

    private function publishJobRequest(Request $request, array $postData): void
    {
        $details = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'budget_min' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'gte:budget_min'],
            'urgency' => ['required', 'string', Rule::in(['normal', 'soon', 'urgent'])],
            'location_label' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($request, $postData, $details): void {
            $jobRequest = JobRequest::create([
                'public_id' => (string) Str::uuid(),
                'client_id' => $request->user()->id,
                'title' => $details['title'],
                'description' => $postData['body'],
                'budget_min_amount' => $this->toMinorUnits($details['budget_min'] ?? null),
                'budget_max_amount' => $this->toMinorUnits($details['budget_max'] ?? null),
                'urgency' => $details['urgency'],
                'status' => JobRequestStatus::Published->value,
                'location_label' => $details['location_label'] ?? null,
                'published_at' => now(),
            ]);

            Post::create([
                ...$postData,
                'user_id' => $request->user()->id,
                'job_request_id' => $jobRequest->id,
                'published_at' => now(),
            ]);
        });
    }

    private function publishListing(Request $request, array $postData): void
    {
        $details = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'price_type' => ['required', 'string', Rule::in(['fixed', 'starting_at', 'quote'])],
            'price' => [
                Rule::requiredIf(fn (): bool => $request->input('price_type') !== 'quote'),
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'stock' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
        ]);

        DB::transaction(function () use ($request, $postData, $details): void {
            $vendor = Vendor::firstOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'display_name' => $request->user()->name,
                    'slug' => Str::slug($request->user()->name).'-'.$request->user()->id,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                    'status' => 'pending',
                ],
            );

            $listing = Listing::create([
                'vendor_id' => $vendor->id,
                'type' => $postData['type'],
                'name' => $details['title'],
                'slug' => $this->uniqueListingSlug($vendor, $details['title']),
                'description' => $postData['body'],
                'price_type' => $details['price_type'],
                'price_amount' => $this->toMinorUnits($details['price'] ?? null),
                'stock' => $postData['type'] === 'product' ? ($details['stock'] ?? null) : null,
                'is_active' => true,
            ]);

            Post::create([
                ...$postData,
                'user_id' => $request->user()->id,
                'vendor_id' => $vendor->id,
                'listing_id' => $listing->id,
                'published_at' => now(),
            ]);
        });
    }

    private function uniqueListingSlug(Vendor $vendor, string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'publicacion';
        $slug = $baseSlug;
        $suffix = 2;

        while ($vendor->listings()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function toMinorUnits(mixed $amount): ?int
    {
        return $amount === null || $amount === ''
            ? null
            : (int) round(((float) $amount) * 100);
    }
}
