<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class UserTyping implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $conversationId, public int $userId, public int $expiresAt) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversations.'.$this->conversationId)];
    }

    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId, 'user_id' => $this->userId, 'expires_at' => $this->expiresAt];
    }
}
