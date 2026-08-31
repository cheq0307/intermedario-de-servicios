<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\JobRequestStatus;
use App\Models\JobRequest;
use App\Models\Listing;
use App\Models\Post;
use App\Models\Vendor;
use App\Services\Marketplace\NotifyMatchingProviders;
use App\Services\Marketplace\PublicationDraftService;
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
        if (! $request->user()->hasVerifiedEmail()) {
            return app(PublicationDraftService::class)->saveAndRequestVerification($request);
        }

        $providerTypes = ['portfolio', 'business_update', 'product', 'service', 'promotion'];
        $allowedTypes = $request->user()->canUseMarketplace()
            ? array_merge(['job_request'], $providerTypes)
            : [];

        $postData = $request->validate([
            'submission_token' => ['required', 'uuid'],
            'type' => ['required', 'string', Rule::in($allowedTypes)],
            'body' => ['required', 'string', 'min:10', 'max:1500'],
            'media' => ['nullable', 'array', 'max:6'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'community_ids' => ['nullable', 'array', 'max:25'],
            'community_ids.*' => ['integer', 'distinct', Rule::exists('communities', 'id')->where('is_active', true)],
            'media.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm', 'max:51200'],
        ]);

        $isProvider = in_array($postData['type'], $providerTypes, true);
        if ($isProvider && $request->user()->vendor?->status !== 'active') {
            $message = match ($request->user()->vendor?->status) {
                'pending' => 'Tu solicitud comercial sigue en revisión. Puedes publicar solicitudes mientras administración la revisa.',
                'rejected' => 'Tu perfil comercial necesita correcciones antes de publicar ofertas.',
                'suspended' => 'Tu perfil comercial está suspendido. Contacta a soporte para solicitar una revisión.',
                default => 'Completa y envía tu perfil comercial antes de publicar productos o servicios.',
            };

            return back()->withInput()->withErrors(['type' => $message]);
        }

        return Cache::lock('publication:'.$postData['submission_token'], 10)
            ->block(5, function () use ($request, $postData, $isProvider): RedirectResponse {
                if (Post::where('submission_token', $postData['submission_token'])->exists()) {
                    return redirect()
                        ->route('dashboard')
                        ->with('status', 'La publicación ya había sido procesada; no se creó un duplicado.');
                }

                if (! $isProvider) {
                    $post = $this->publishJobRequest($request, $postData);
                } elseif (in_array($postData['type'], ['product', 'service'], true)) {
                    $post = $this->publishListing($request, $postData);
                } else {
                    $post = Post::create([
                        ...$postData,
                        'user_id' => $request->user()->id,
                        'published_at' => now(),
                    ]);
                }

                $this->storeMedia($request, $post);

                if ($post->jobRequest) {
                    app(NotifyMatchingProviders::class)->handle($post->jobRequest);
                }
                $request->user()->publicationDraft()->delete();

                return redirect()
                    ->route('dashboard')
                    ->with('status', 'Tu publicación ya está visible en la comunidad.');
            });
    }

    private function publishJobRequest(Request $request, array $postData): Post
    {
        $details = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'budget_min' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'gte:budget_min'],
            'urgency' => ['required', 'string', Rule::in(['normal', 'soon', 'urgent'])],
            'location_label' => ['nullable', 'string', 'max:120'],
        ]);

        return DB::transaction(function () use ($request, $postData, $details): Post {
            $jobRequest = JobRequest::create([
                'public_id' => (string) Str::uuid(),
                'client_id' => $request->user()->id,
                'title' => $details['title'],
                'description' => $postData['body'],
                'budget_min_amount' => $this->toMinorUnits($details['budget_min'] ?? null),
                'category_id' => $postData['category_id'] ?? null,
                'budget_max_amount' => $this->toMinorUnits($details['budget_max'] ?? null),
                'urgency' => $details['urgency'],
                'status' => JobRequestStatus::Published->value,
                'location_label' => $details['location_label'] ?? null,
                'published_at' => now(),
            ]);

            $communityIds = $postData['community_ids'] ?? array_filter([$request->user()->community_id]);
            if ($communityIds !== []) {
                $jobRequest->communities()->sync($communityIds);
            }

            return Post::create([
                ...$postData,
                'user_id' => $request->user()->id,
                'job_request_id' => $jobRequest->id,
                'published_at' => now(),
            ]);
        });
    }

    private function publishListing(Request $request, array $postData): Post
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

        return DB::transaction(function () use ($request, $postData, $details): Post {
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
                'category_id' => $postData['category_id'] ?? null,
                'price_amount' => $this->toMinorUnits($details['price'] ?? null),
                'stock' => $postData['type'] === 'product' ? ($details['stock'] ?? null) : null,
                'is_active' => true,
            ]);

            if ($listing->category_id) {
                $vendor->categories()->syncWithoutDetaching([$listing->category_id]);
            }

            return Post::create([
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

    private function storeMedia(Request $request, Post $post): void
    {
        $disk = (string) config('marketplace.media_disk', 'public');
        foreach ($request->file('media', []) as $position => $file) {
            $type = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
            $path = $file->store('post-media/'.now()->format('Y/m'), $disk);
            if (! $path) {
                throw new \RuntimeException('No fue posible guardar uno de los archivos.');
            }
            $post->media()->create([
                'type' => $type,
                'path' => $path,
                'disk' => $disk,
                'position' => $position,
                'alt_text' => $type === 'image' ? 'Imagen de la publicacion de '.$request->user()->name : null,
            ]);
        }
    }

    private function toMinorUnits(mixed $amount): ?int
    {
        return $amount === null || $amount === ''
            ? null
            : (int) round(((float) $amount) * 100);
    }
}
