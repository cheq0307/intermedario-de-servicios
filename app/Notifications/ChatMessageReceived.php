<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ChatMessageReceived extends Notification
{
    public function __construct(private string $conversationId, private int $messageId) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Nuevo mensaje', 'body' => 'Tienes un mensaje en Plaza Local.', 'kind' => 'chat_message',
            'route_name' => 'conversations.show', 'route_parameters' => ['conversation' => $this->conversationId],
            'conversation_id' => $this->conversationId, 'message_id' => $this->messageId];
    }
}
