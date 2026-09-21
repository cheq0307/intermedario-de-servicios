<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class MessageRead implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $conversationId, public int $userId, public int $throughId, public bool $read) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.conversations.'.$this->conversationId), new PrivateChannel('chat.users.'.$this->userId)];
    }

    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId, 'user_id' => $this->userId, 'through_id' => $this->throughId, 'read' => $this->read];
    }
}
