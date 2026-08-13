<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\JobRequestStatus;
use App\Models\Community;
use App\Models\JobRequest;
use App\Models\Listing;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExploreController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()?->hasRole('superadmin')) {
            return redirect()->route('admin.index');
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['all', 'product', 'service', 'provider', 'job_request'])],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'community_id' => ['nullable', 'integer', Rule::exists('communities', 'id')->where('is_active', true)],
            'scope' => ['nullable', Rule::in(['community', 'nearby', 'all'])],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:100'],
        ]);

        if (isset($filters['min_price'], $filters['max_price']) && (float) $filters['max_price'] < (float) $filters['min_price']) {
            throw ValidationException::withMessages(['max_price' => 'El precio máximo debe ser mayor o igual al mínimo.']);
        }

        $term = trim($filters['q'] ?? '');
        $type = $filters['type'] ?? 'all';
        $minPrice = $this->minorUnits($filters['min_price'] ?? null);
        $maxPrice = $this->minorUnits($filters['max_price'] ?? null);
        $communities = Community::query()->where('is_active', true)->orderBy('name')->get();
        $scope = $filters['scope'] ?? 'community';
        $communityId = isset($filters['community_id'])
            ? (int) $filters['community_id']
            : $request->user()?->community_id;
        $selectedCommunity = $communityId ? $communities->firstWhere('id', $communityId) : null;
        $radiusKm = (float) ($filters['radius_km'] ?? $selectedCommunity?->default_radius_km ?? 8);
        $radiusSearchAvailable = $selectedCommunity?->hasCoordinates() ?? false;
        $territoryCommunityIds = match ($scope) {
            'all' => null,
            'nearby' => $selectedCommunity
                ? $communities->filter(function (Community $community) use ($selectedCommunity, $radiusKm): bool {
                    $distance = $selectedCommunity->distanceTo($community);

                    return $community->is($selectedCommunity) || ($distance !== null && $distance <= $radiusKm);
                })->pluck('id')->all()
                : null,
            default => $selectedCommunity ? [$selectedCommunity->id] : null,
        };
        $filters = array_merge($filters, ['community_id' => $communityId, 'scope' => $scope, 'radius_km' => $radiusKm]);

        $listings = Listing::query()
            ->with(['vendor.user:id,name,avatar_path,city'])
            ->where('is_active', true)
            ->whereHas('vendor', fn (Builder $query) => $query->where('status', 'active'))
            ->when(in_array($type, ['product', 'service'], true), fn (Builder $query) => $query->where('type', $type))
            ->when(in_array($type, ['provider', 'job_request'], true), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('vendor', fn (Builder $vendor) => $vendor->where('display_name', 'like', "%{$term}%"));
                });
            })
            ->when($minPrice !== null, fn (Builder $query) => $query->where('price_amount', '>=', $minPrice))
            ->when($maxPrice !== null, fn (Builder $query) => $query->where('price_amount', '<=', $maxPrice))
            ->when($territoryCommunityIds !== null, fn (Builder $query) => $query->whereHas('vendor.user', fn (Builder $user) => $user->whereIn('community_id', $territoryCommunityIds)))
            ->latest()
            ->paginate(12, ['*'], 'listings_page')
            ->withQueryString();

        $providers = Vendor::query()
            ->with('user:id,name,avatar_path,city')
            ->where('status', 'active')
            ->when(in_array($type, ['product', 'service', 'job_request'], true), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('display_name', 'like', "%{$term}%")
                        ->orWhere('specialty', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($territoryCommunityIds !== null, fn (Builder $query) => $query->whereHas('user', fn (Builder $user) => $user->whereIn('community_id', $territoryCommunityIds)))
            ->latest('verified_at')
            ->paginate(12, ['*'], 'providers_page')
            ->withQueryString();

        $jobRequests = JobRequest::query()
            ->with('client:id,name,avatar_path,city')
            ->whereIn('status', [JobRequestStatus::Published, JobRequestStatus::InConversation])
            ->when($type !== 'all' && $type !== 'job_request', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")))
            ->when($territoryCommunityIds !== null, fn (Builder $query) => $query->whereHas('client', fn (Builder $user) => $user->whereIn('community_id', $territoryCommunityIds)))
            ->latest('published_at')
            ->paginate(12, ['*'], 'requests_page')
            ->withQueryString();

        return view('explore.index', compact('filters', 'listings', 'providers', 'jobRequests', 'type', 'communities', 'scope', 'selectedCommunity', 'radiusKm', 'radiusSearchAvailable'));
    }

    private function minorUnits(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) round(((float) $value) * 100);
    }
}
