<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\OrderStatus;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\AccountIdentityLink;
use App\Models\Category;
use App\Models\Community;
use App\Models\Order;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\PostShare;
use App\Models\User;
use App\Services\Accounts\IdentityContacts;
use App\ViewData\ProfileEditData;
use App\ViewData\ProfileShowData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(User $user): View
    {
        abort_if($user->migrated_to_admin_at, 404);
        $user->load(['vendor.categories', 'community'])->loadCount([
            'posts' => fn ($query) => $query->whereNull('removed_at'),
            'jobRequests' => fn ($query) => $query->whereHas('post', fn ($post) => $post->whereNull('removed_at')),
        ]);
        $isStaff = $user->hasRole('superadmin') || ($user->hasRole('admin') && ! $user->canUseMarketplace());
        $tab = request()->query('tab', $user->vendor?->status === 'active' ? 'offers' : 'needs');
        if (! in_array($tab, ['offers', 'needs', 'work', 'reviews'], true)) {
            $tab = 'offers';
        }

        $posts = $isStaff
            ? $user->posts()->whereRaw('1 = 0')->paginate(9)
            : $user->posts()
                ->with(['listing', 'jobRequest', 'media'])
                ->whereNotNull('published_at')
                ->whereNull('removed_at')
                ->when($tab === 'offers', fn ($query) => $query->whereNotNull('listing_id'))
                ->when($tab === 'needs', fn ($query) => $query->where(fn ($nested) => $nested->whereNotNull('job_request_id')->orWhere('type', 'job_request')))
                ->when(in_array($tab, ['work', 'reviews'], true), fn ($query) => $query->whereRaw('1 = 0'))
                ->latest('published_at')
                ->paginate(9)
                ->withQueryString();

        $completedOrders = Order::query()
            ->with(['buyer:id,name', 'jobRequest.post.media', 'items'])
            ->whereHas('vendor', fn ($query) => $query->where('user_id', $user->id))
            ->where('status', OrderStatus::Completed->value)
            ->latest('completed_at')
            ->limit(24)
            ->get();
        $reviewsQuery = $user->reviewsReceived()->where('is_visible', true);
        $rating = (clone $reviewsQuery)->avg('rating');
        $reviewsCount = (clone $reviewsQuery)->count();
        $reviews = $reviewsQuery
            ->with('author:id,name,avatar_path,avatar_disk')
            ->latest()
            ->limit(30)
            ->get();
        $completedOrdersCount = Order::query()
            ->whereHas('vendor', fn ($query) => $query->where('user_id', $user->id))
            ->where('status', OrderStatus::Completed->value)
            ->count();

        $publishedPostIds = $user->posts()->whereNotNull('published_at')->whereNull('removed_at')->select('id');
        $socialMetrics = [
            'followers' => $user->followers()->count(),
            'likes' => PostReaction::query()->whereIn('post_id', clone $publishedPostIds)->count(),
            'comments' => PostComment::query()->whereIn('post_id', clone $publishedPostIds)->count(),
            'shares' => PostShare::query()->whereIn('post_id', clone $publishedPostIds)->count(),
        ];
        $administrativePreview = auth()->check()
            && auth()->user()->hasAnyRole(['admin', 'superadmin'])
            && ! auth()->user()->canUseMarketplace()
            && ! auth()->user()->is($user);
        $isFollowing = auth()->check()
            && ! $administrativePreview
            && ! auth()->user()->is($user)
            && auth()->user()->following()->whereKey($user->id)->exists();

        $profile = ProfileShowData::from($user, request()->user(), $isStaff);
        $isOwner = $profile->isOwner;
        $isProvider = $profile->isProvider;
        $vendor = $profile->vendor;
        $staffLabel = $profile->roleLabel;

        return view('profiles.show', compact('user', 'profile', 'isOwner', 'isStaff', 'isProvider', 'vendor', 'staffLabel', 'posts', 'rating', 'reviewsCount', 'completedOrdersCount', 'completedOrders', 'reviews', 'tab', 'socialMetrics', 'isFollowing', 'administrativePreview'));
    }

    public function edit(): View
    {
        $user = request()->user()->load(['vendor.categories', 'vendor.verificationDocuments', 'community', 'interests']);
        $communities = Community::query()->where('is_active', true)->orderBy('name')->get();
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $profileForm = ProfileEditData::from($user);

        return view('profiles.edit', compact('user', 'communities', 'categories', 'profileForm'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($request, $user, $validated): void {
            IdentityContacts::lock();
            $user->refresh();
            IdentityContacts::assertPhoneAllowed($user, $validated['phone']);
            $community = Community::query()->where('is_active', true)->findOrFail($validated['community_id']);
            $userData = collect($validated)->only(['name', 'phone', 'bio', 'community_id'])->all();
            if (($userData['phone'] ?? null) !== $user->phone) {
                $link = AccountIdentityLink::where('user_id', $user->id)->first();
                abort_if($link && $link->phone !== ($userData['phone'] ?? null), 422, 'El teléfono está vinculado a tu identidad administrativa. Solicita al superadministrador la actualización.');
                $user->phone_verified_at = null;
            }
            $userData['city'] = $community->municipality;

            if ($request->hasFile('avatar')) {
                $disk = (string) config('marketplace.media_disk', 'public');
                $userData['avatar_path'] = $request->file('avatar')->store('avatars', $disk);
                $userData['avatar_disk'] = $disk;
            }

            $user->update($userData);

            if ($user->vendor) {
                $vendorData = collect($validated)->only([
                    'display_name',
                    'description',
                    'specialty',
                    'service_area',
                    'years_experience',
                    'availability_status',
                    'certifications',
                    'tools',
                ])->all();
                $vendorData['business_hours'] = [
                    'days' => array_values($validated['business_days']),
                    'opens_at' => $validated['business_opens_at'],
                    'closes_at' => $validated['business_closes_at'],
                    'timezone' => config('marketplace.business_timezone'),
                ];

                $vendor = $user->vendor;
                $vendor->update(array_merge($vendorData, [
                    'phone' => $user->phone,
                    'email' => $user->email,
                ]));

                $vendor->categories()->sync($validated['offered_categories'] ?? []);

                if ($vendor->status === 'pending') {
                    $vendor->update([
                        'status' => 'draft',
                        'submitted_at' => null,
                        'reviewed_at' => null,
                        'rejection_reason' => null,
                    ]);
                }
            }
        });

        return redirect()->route('profile.show', $user)->with('status', 'Tu perfil se actualizó correctamente.');
    }

    public function updateInterests(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'interests' => ['nullable', 'array', 'max:10'],
            'interests.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('is_active', true)],
        ]);
        $user = $request->user();
        $selectedCategoryIds = collect($validated['interests'] ?? [])
            ->map(fn ($categoryId): int => (int) $categoryId)
            ->unique()
            ->values();

        $preferences = $user->categoryPreferences()->get();
        $syncData = $preferences->mapWithKeys(function (Category $category) use ($selectedCategoryIds): array {
            return [$category->id => [
                'interest_score' => $selectedCategoryIds->contains((int) $category->id) ? 100 : 0,
                'behavior_score' => (int) $category->pivot->behavior_score,
            ]];
        })->all();

        foreach ($selectedCategoryIds as $categoryId) {
            $syncData[$categoryId] ??= ['interest_score' => 100, 'behavior_score' => 0];
        }

        $user->categoryPreferences()->sync($syncData);

        return redirect()->route('profile.edit')->with('status', 'Tus intereses se actualizaron correctamente.');
    }
}
