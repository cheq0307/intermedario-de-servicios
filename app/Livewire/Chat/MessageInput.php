<?php

namespace App\Livewire\Chat;

use App\Services\Chat\ChatService;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;

class MessageInput extends ChatComponent
{
    use WithFileUploads;

    #[Locked]
    public string $conversationId;

    public array $images = [];

    public function mount(string $conversationId): void
    {
        $this->conversation($conversationId);
        $this->conversationId = $conversationId;
    }

    public function send(string $body, string $token): array
    {
        $message = app(ChatService::class)->send($this->conversation($this->conversationId), $this->viewer(), $body, $token, $this->images);
        $this->reset('images');
        $this->dispatch('chat-message-sent');
        $this->dispatch('chat-updated');

        return ['id' => $message->id, 'token' => $message->client_message_id];
    }

    public function typing(): void
    {
        app(ChatService::class)->typing($this->conversation($this->conversationId), $this->viewer());
        $this->skipRender();
    }

    public function clearImages(): void
    {
        $this->conversation($this->conversationId);
        $this->reset('images');
        $this->resetValidation();
    }

    public function render()
    {
        $chat = $this->conversation($this->conversationId);

        return view('livewire.chat.message-input', ['canSend' => $chat->acceptsMessages()]);
    }
}
