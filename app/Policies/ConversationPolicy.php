<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $user->canUseMarketplace() && $user->hasVerifiedEmail() && $conversation->includesUser($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation) && $conversation->acceptsMessages();
    }
}
