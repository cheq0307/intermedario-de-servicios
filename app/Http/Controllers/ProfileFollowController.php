<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\MarketplaceActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileFollowController extends Controller
{
    public function toggle(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        abort_if($actor->hasAnyRole(['admin', 'superadmin']) && ! $actor->canUseMarketplace(), 422, 'Las cuentas administrativas no participan en funciones sociales.');
        abort_if($actor->is($user), 422, 'No puedes seguir tu propia cuenta.');
        abort_if($user->hasAnyRole(['admin', 'superadmin']) && ! $user->canUseMarketplace(), 422, 'Las cuentas institucionales no admiten seguidores.');

        $following = $actor->following()->whereKey($user->getKey())->exists();

        if ($following) {
            $actor->following()->detach($user->getKey());
        } else {
            $actor->following()->syncWithoutDetaching([$user->getKey()]);
            $user->notify(new MarketplaceActivity(
                'Nuevo seguidor',
                $actor->name.' comenzó a seguir tu cuenta.',
                'profile.show',
                ['user' => $actor->id],
                'social_follow',
            ));
        }

        return back()->with('status', $following ? 'Dejaste de seguir esta cuenta.' : 'Ahora sigues esta cuenta.');
    }
}
