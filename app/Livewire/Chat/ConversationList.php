<?php

namespace App\Livewire\Chat;

use App\ViewData\ChatSummary;
use Livewire\Attributes\Locked;

class ConversationList extends ChatComponent
{
    public string $search = '';

    #[Locked]
    public int $limit = 20;

    #[Locked]
    public ?string $selected = null;

    public function getListeners(): array
    {
        $id = $this->viewer()->id;

        return ["echo-private:chat.users.{$id},MessageSent" => 'refreshList', "echo-private:chat.users.{$id},MessageRead" => 'refreshList', 'chat-updated' => 'refreshList'];
    }

    public function refreshList(): void
    {
        $this->viewer();
    }

    public function updatedSearch(): void
    {
        $this->validate(['search' => 'string|max:100']);
        $this->limit = 20;
    }

    public function loadMore(): void
    {
        $this->viewer();
        $this->limit = min($this->limit + 20, 500);
    }

    public function render()
    {
        $user = $this->viewer();
        $query = $user->conversations()->with(['participants', 'post.media', 'post.listing', 'post.jobRequest', 'order.jobRequest', 'order.items', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where(fn ($sender) => $sender->where('sender_id', '!=', $user->id)->orWhereNotNull('admin_user_id'))
                ->where(fn ($unread) => $unread->whereColumn('messages.id', '>', 'conversation_participants.last_read_message_id')
                    ->orWhere(fn ($legacy) => $legacy->whereNull('conversation_participants.last_read_message_id')
                        ->where(fn ($date) => $date->whereNull('conversation_participants.last_read_at')->orWhereColumn('messages.created_at', '>', 'conversation_participants.last_read_at'))))]);
        $term = trim(mb_substr($this->search, 0, 100));
        if ($term !== '') {
            $query->where(function ($q) use ($term, $user) {
                $q->whereHas('participants', fn ($p) => $p->where('users.id', '!=', $user->id)->where('name', 'like', '%'.$term.'%'))
                    ->orWhereHas('post', fn ($p) => $p->where('body', 'like', '%'.$term.'%'))
                    ->orWhereHas('post.listing', fn ($p) => $p->where('name', 'like', '%'.$term.'%'))
                    ->orWhereHas('post.jobRequest', fn ($p) => $p->where('title', 'like', '%'.$term.'%'))
                    ->orWhereHas('order.items', fn ($p) => $p->where('name_snapshot', 'like', '%'.$term.'%'));
            });
        }
        $chats = $query->orderByDesc('last_message_at')->orderByDesc('conversations.id')->limit($this->limit + 1)->get();
        $hasMore = $chats->count() > $this->limit && $this->limit < 500;
        $groups = $chats->take($this->limit)->map(fn ($chat) => ChatSummary::from($chat, $user->id))->groupBy('group');

        return view('livewire.chat.conversation-list', compact('groups', 'hasMore'));
    }
}
