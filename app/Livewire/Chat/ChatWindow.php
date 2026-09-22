<?php

namespace App\Livewire\Chat;

use App\Services\Chat\ChatService;
use App\Services\Marketplace\NegotiationConversationService;
use App\ViewData\ChatSummary;
use Livewire\Attributes\Locked;

class ChatWindow extends ChatComponent
{
    #[Locked]
    public string $conversationId;

    #[Locked]
    public int $oldestId = 0;

    #[Locked]
    public int $newestId = 0;

    #[Locked]
    public int $typingUntil = 0;

    public function mount(string $conversationId): void
    {
        $this->conversationId = $conversationId;
        $chat = $this->conversation($conversationId);
        $ids = $chat->messages()->latest('id')->limit(config('chat.page_size'))->pluck('id');
        $this->oldestId = (int) ($ids->min() ?? 0);
        $this->newestId = (int) ($ids->max() ?? 0);
    }

    public function getListeners(): array
    {
        return ["echo-private:chat.conversations.{$this->conversationId},MessageSent" => 'refreshMessages',
            "echo-private:chat.conversations.{$this->conversationId},MessageRead" => 'refreshMessages',
            "echo-private:chat.conversations.{$this->conversationId},UserTyping" => 'receiveTyping', 'chat-message-sent' => 'refreshMessages'];
    }

    public function refreshMessages(): void
    {
        $chat = $this->conversation($this->conversationId);
        $ids = $chat->messages()->latest('id')->limit(config('chat.page_size'))->pluck('id');
        $this->newestId = (int) ($ids->max() ?? 0);
        if (! $this->oldestId) {
            $this->oldestId = (int) ($ids->min() ?? 0);
        }
    }

    public function loadOlder(): void
    {
        $chat = $this->conversation($this->conversationId);
        $id = $chat->messages()->where('id', '<', $this->oldestId)->latest('id')->limit(config('chat.page_size'))->pluck('id')->min();
        if ($id) {
            $this->oldestId = $id;
        }
    }

    public function acknowledge(int $throughId, bool $read): void
    {
        abort_if($throughId > $this->newestId, 422);
        $chat = $this->conversation($this->conversationId);
        app(ChatService::class)->acknowledge($chat, $this->viewer(), $throughId, $read);
        $this->dispatch('chat-updated');
        $this->skipRender();
    }

    public function viewing(bool $visible): void
    {
        app(ChatService::class)->viewing($this->conversation($this->conversationId), $this->viewer(), $visible);
        $this->skipRender();
    }

    public function receiveTyping(array $event): void
    {
        $chat = $this->conversation($this->conversationId);
        if (($event['user_id'] ?? null) !== $this->viewer()->id && $chat->participants()->whereKey($event['user_id'] ?? 0)->exists()) {
            $this->typingUntil = min((int) ($event['expires_at'] ?? 0), now()->addSeconds(5)->timestamp);
        }
    }

    public function render()
    {
        $chat = $this->conversation($this->conversationId);
        $chat = app(NegotiationConversationService::class)->expireIfNeeded($chat);
        $chat->load(['participants', 'post.media', 'post.listing', 'post.jobRequest', 'order.items', 'order.jobRequest', 'agreementOrder']);
        $user = $this->viewer();
        $messages = $chat->messages()->whereBetween('id', [$this->oldestId, $this->newestId])->with(['attachments', 'receipts', 'sender'])->orderBy('id')->get();
        $hasOlder = $this->oldestId && $chat->messages()->where('id', '<', $this->oldestId)->exists();
        $summary = ChatSummary::from($chat, $user->id);

        return view('livewire.chat.chat-window', compact('chat', 'messages', 'hasOlder', 'summary', 'user'));
    }
}
