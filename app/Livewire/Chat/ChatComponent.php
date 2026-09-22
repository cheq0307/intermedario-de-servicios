<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

abstract class ChatComponent extends Component
{
    protected function viewer(): User
    {
        abort_unless(Auth::guard('web')->check(), 401);
        $user = User::findOrFail(Auth::guard('web')->id());
        abort_unless($user->canUseMarketplace() && $user->hasVerifiedEmail() && ! $user->migrated_to_admin_at, 403);

        return $user;
    }

    protected function conversation(string $publicId): Conversation
    {
        $conversation = Conversation::where('public_id', $publicId)->firstOrFail();
        Gate::forUser($this->viewer())->authorize('view', $conversation);

        return $conversation;
    }
}
