<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Community;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(User $user): View
    {
        $user->load(['vendor', 'community'])->loadCount(['posts', 'jobRequests']);
        $posts = $user->posts()
            ->with(['listing', 'jobRequest'])
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->paginate(9);
        $rating = $user->reviewsReceived()->where('is_visible', true)->avg('rating');
        $reviewsCount = $user->reviewsReceived()->where('is_visible', true)->count();

        return view('profiles.show', compact('user', 'posts', 'rating', 'reviewsCount'));
    }

    public function edit(): View
    {
        $user = request()->user()->load(['vendor.categories', 'community', 'categoryPreferences']);
        $communities = Community::query()->where('is_active', true)->orderBy('name')->get();
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        return view('profiles.edit', compact('user', 'communities', 'categories'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($request, $user, $validated): void {
            $community = Community::query()->where('is_active', true)->findOrFail($validated['community_id']);
            $userData = collect($validated)->only(['name', 'phone', 'bio', 'community_id'])->all();
            $userData['city'] = $community->municipality;

            if ($request->hasFile('avatar')) {
                $userData['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
            }

            $user->update($userData);

            $user->categoryPreferences()->sync(collect($validated['interests'] ?? [])->mapWithKeys(
                fn (int $categoryId) => [$categoryId => ['interest_score' => 100, 'behavior_score' => 0]],
            )->all());

            if ($request->boolean('offers_services') || $user->vendor) {
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

                $vendor = $user->vendor()->updateOrCreate(
                    ['user_id' => $user->id],
                    array_merge($vendorData, [
                        'slug' => $user->vendor?->slug ?? Str::slug($validated['display_name']).'-'.$user->id,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'status' => $user->vendor?->status ?? 'draft',
                    ]),
                );

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
}
