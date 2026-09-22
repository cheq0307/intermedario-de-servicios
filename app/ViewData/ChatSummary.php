<?php

namespace App\ViewData;

use App\Models\Conversation;
use Illuminate\Support\Str;

class ChatSummary
{
    public static function from(Conversation $chat, int $viewerId): array
    {
        $post = $chat->post;
        $order = $chat->order;
        $title = $post?->listing?->name ?? $post?->jobRequest?->title
            ?? ($post ? Str::limit($post->body, 70) : ($order?->jobRequest?->title ?? $order?->items->first()?->name_snapshot ?? 'Conversación directa'));
        $other = $chat->participants->firstWhere('id', '!=', $viewerId);

        return ['id' => $chat->public_id, 'title' => $title,
            'group' => $post ? 'post:'.$post->id : ($order ? 'order:'.$order->id : 'direct'),
            'kind' => $post ? 'Publicación' : ($order ? 'Operación' : 'Directa'),
            'image' => $post?->media->firstWhere('type', 'image')?->url,
            'name' => $other?->name ?? 'Conversación', 'avatar' => $other?->avatar_path ? $other->avatarUrl() : null,
            'preview' => Str::limit($chat->latestMessage?->body ?: ($chat->latestMessage?->type->value === 'image' ? 'Imagen adjunta' : 'Inicia la conversación'), 100),
            'at' => $chat->last_message_at?->format('d/m H:i'), 'unread' => (int) ($chat->unread_count ?? 0),
            'url' => route('conversations.show', $chat)];
    }
}
