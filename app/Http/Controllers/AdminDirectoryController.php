<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminDirectoryController extends Controller
{
    public function posts(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kind' => ['nullable', Rule::in(['offer', 'request'])],
            'status' => ['nullable', Rule::in(['active', 'removed'])],
            'community_id' => ['nullable', 'integer', Rule::exists('communities', 'id')],
        ]);
        $term = trim($filters['q'] ?? '');
        $posts = Post::query()
            ->with(['user.community:id,name,municipality', 'listing.category:id,name', 'jobRequest.category:id,name', 'removedBy:id,name'])
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('body', 'like', "%{$term}%")
                ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
                ->orWhereHas('listing', fn (Builder $listing) => $listing->where('name', 'like', "%{$term}%"))
                ->orWhereHas('jobRequest', fn (Builder $job) => $job->where('title', 'like', "%{$term}%"))))
            ->when(($filters['kind'] ?? null) === 'offer', fn (Builder $query) => $query->whereNotNull('listing_id'))
            ->when(($filters['kind'] ?? null) === 'request', fn (Builder $query) => $query->whereNotNull('job_request_id'))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->whereNull('removed_at'))
            ->when(($filters['status'] ?? null) === 'removed', fn (Builder $query) => $query->whereNotNull('removed_at'))
            ->when($filters['community_id'] ?? null, fn (Builder $query, int|string $community) => $query->whereHas('user', fn (Builder $user) => $user->where('community_id', $community)))
            ->latest('published_at')
            ->paginate(24)
            ->withQueryString();
        $communities = Community::query()->orderBy('name')->get(['id', 'name', 'municipality']);

        return view('admin.posts', compact('posts', 'communities', 'filters'));
    }

    public function users(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['client', 'provider', 'admin', 'superadmin'])],
            'community_id' => ['nullable', 'integer', Rule::exists('communities', 'id')],
            'verification' => ['nullable', Rule::in(['verified', 'pending'])],
        ]);
        $term = trim($filters['q'] ?? '');
        $users = User::query()
            ->with(['roles:id,name', 'community:id,name,municipality'])
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")))
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $role)))
            ->when($filters['community_id'] ?? null, fn (Builder $query, int|string $community) => $query->where('community_id', $community))
            ->when(($filters['verification'] ?? null) === 'verified', fn (Builder $query) => $query->whereNotNull('email_verified_at'))
            ->when(($filters['verification'] ?? null) === 'pending', fn (Builder $query) => $query->whereNull('email_verified_at'))
            ->latest()
            ->paginate(25)
            ->withQueryString();
        $communities = Community::query()->orderBy('name')->get(['id', 'name', 'municipality']);

        return view('admin.users', compact('users', 'communities', 'filters'));
    }

    public function vendors(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'pending', 'active', 'rejected', 'suspended'])],
            'community_id' => ['nullable', 'integer', Rule::exists('communities', 'id')],
        ]);
        $term = trim($filters['q'] ?? '');
        $vendors = Vendor::query()
            ->with(['user:id,name,email,email_verified_at,community_id', 'user.community:id,name,municipality'])
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('display_name', 'like', "%{$term}%")
                ->orWhere('specialty', 'like', "%{$term}%")
                ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', "%{$term}%"))))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['community_id'] ?? null, fn (Builder $query, int|string $community) => $query->whereHas('user', fn (Builder $user) => $user->where('community_id', $community)))
            ->latest()
            ->paginate(25)
            ->withQueryString();
        $communities = Community::query()->orderBy('name')->get(['id', 'name', 'municipality']);

        return view('admin.vendors', compact('vendors', 'communities', 'filters'));
    }

    public function showVendor(Request $request, Vendor $vendor): View
    {
        $this->authorizeAdmin($request);
        abort_if($vendor->user_id === $request->user()->id, 403, 'No puedes revisar tu propio perfil comercial.');

        $vendor->load([
            'categories:id,name,slug',
            'user.community:id,name,municipality,state,postal_code',
            'user.categoryPreferences:id,name,slug',
            'verificationDocuments.reviewedBy:id,name',
        ])->loadCount(['listings', 'orders']);

        return view('admin.vendors.show', compact('vendor'));
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'superadmin']), 403);
    }
}
