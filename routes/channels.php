<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('chat.conversations.{publicId}', function (User $user, string $publicId): bool {
    $conversation = Conversation::where('public_id', $publicId)->first();

    return $conversation && Gate::forUser($user)->allows('view', $conversation);
}, ['guards' => ['web']]);

Broadcast::channel('chat.users.{id}', fn (User $user, string $id): bool => (string) $user->id === $id
    && $user->canUseMarketplace() && $user->hasVerifiedEmail(), ['guards' => ['web']]);
