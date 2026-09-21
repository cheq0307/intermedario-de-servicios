<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class MessageSent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $conversationId, public int $messageId, private array $userIds) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversations.'.$this->conversationId),
            ...array_map(fn ($id) => new PrivateChannel('chat.users.'.$id), $this->userIds)];
    }

    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId, 'message_id' => $this->messageId];
    }
}
