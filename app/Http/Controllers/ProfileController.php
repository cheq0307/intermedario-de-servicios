<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(User $user): View
    {
        $user->load('vendor')->loadCount(['posts', 'jobRequests']);
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
        $user = request()->user()->load('vendor');

        return view('profiles.edit', compact('user'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($request, $user, $validated): void {
            $userData = collect($validated)->only(['name', 'phone', 'bio', 'city'])->all();

            if ($request->hasFile('avatar')) {
                $userData['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
            }

            $user->update($userData);

            if ($user->canActAsProvider()) {
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

                $user->vendor()->updateOrCreate(
                    ['user_id' => $user->id],
                    array_merge($vendorData, [
                        'slug' => $user->vendor?->slug ?? Str::slug($validated['display_name']).'-'.$user->id,
                        'phone' => $user->phone,
                        'email' => $user->email,
                    ]),
                );
            }
        });

        return redirect()->route('profile.show', $user)->with('status', 'Tu perfil se actualizó correctamente.');
    }
}
