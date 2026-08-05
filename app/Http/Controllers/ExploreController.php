<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\JobRequestStatus;
use App\Models\JobRequest;
use App\Models\Listing;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExploreController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['all', 'product', 'service', 'provider', 'job_request'])],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        if (isset($filters['min_price'], $filters['max_price']) && (float) $filters['max_price'] < (float) $filters['min_price']) {
            throw ValidationException::withMessages(['max_price' => 'El precio máximo debe ser mayor o igual al mínimo.']);
        }

        $term = trim($filters['q'] ?? '');
        $type = $filters['type'] ?? 'all';
        $minPrice = $this->minorUnits($filters['min_price'] ?? null);
        $maxPrice = $this->minorUnits($filters['max_price'] ?? null);

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
            ->when(filled($filters['city'] ?? null), fn (Builder $query) => $query->whereHas('vendor.user', fn (Builder $user) => $user->where('city', 'like', '%'.$filters['city'].'%')))
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
            ->when(filled($filters['city'] ?? null), fn (Builder $query) => $query->whereHas('user', fn (Builder $user) => $user->where('city', 'like', '%'.$filters['city'].'%')))
            ->latest('verified_at')
            ->paginate(12, ['*'], 'providers_page')
            ->withQueryString();

        $jobRequests = JobRequest::query()
            ->with('client:id,name,avatar_path,city')
            ->whereIn('status', [JobRequestStatus::Published, JobRequestStatus::InConversation])
            ->when($type !== 'all' && $type !== 'job_request', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('title', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%")))
            ->when(filled($filters['city'] ?? null), fn (Builder $query) => $query->whereHas('client', fn (Builder $user) => $user->where('city', 'like', '%'.$filters['city'].'%')))
            ->latest('published_at')
            ->paginate(12, ['*'], 'requests_page')
            ->withQueryString();

        return view('explore.index', compact('filters', 'listings', 'providers', 'jobRequests', 'type'));
    }

    private function minorUnits(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) round(((float) $value) * 100);
    }
}
