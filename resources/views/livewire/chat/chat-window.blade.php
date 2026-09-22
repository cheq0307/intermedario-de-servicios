<section class="flex h-full min-h-0 min-w-0 flex-col" x-data="plazaChatWindow($wire)" data-chat-window data-newest="{{ $this->newestId }}" data-typing-until="{{ $this->typingUntil }}" wire:poll.30s.visible="refreshMessages">
    <header class="flex shrink-0 items-center gap-3 border-b border-brand/10 bg-white p-3">
        <a href="{{ route('conversations.index') }}" class="grid size-11 shrink-0 place-items-center rounded-full border border-brand/10 lg:hidden" aria-label="Volver a conversaciones">←</a>
        <div class="min-w-0 flex-1"><h2 class="truncate font-black">{{ $summary['name'] }}</h2><p class="truncate text-xs text-brand-copy">{{ $summary['title'] }}</p></div>
        <span class="max-w-28 text-right text-[10px] text-brand-muted" x-text="connection" role="status"></span>
    </header>
    <div class="shrink-0 border-b border-brand/10 bg-brand-surface px-4 py-2 text-xs">
        <div class="flex flex-wrap gap-3 font-bold">
            @if($chat->post && !$chat->post->removed_at)<a class="text-brand-success underline" href="{{ route('chat.publication', $chat) }}">Ver publicación</a>@endif
            @if($chat->order ?? $chat->agreementOrder)<a class="text-brand-success underline" href="{{ route('orders.show', $chat->order ?? $chat->agreementOrder) }}">Ver estado de la orden</a>@endif
        </div>
        @if($chat->isNegotiation() && $chat->acceptsMessages())
            <div class="mt-2 flex flex-wrap items-center gap-2"><span>Disponible hasta {{ $chat->expires_at?->format('d/m/Y H:i') }}</span>
                @if($chat->extension_count < config('marketplace.negotiation_max_extensions',1) && $chat->expires_at?->lte(now()->addDays(config('marketplace.negotiation_extension_window_days', 2))))
                    <form method="POST" action="{{ route('conversations.extend',$chat) }}">@csrf @method('PATCH')<button class="min-h-9 rounded-full border px-3 font-bold">Extender 7 días</button></form>
                @endif
                <form method="POST" action="{{ route('conversations.close',$chat) }}" x-on:submit="if (!confirm('¿Terminar esta conversación?')) $event.preventDefault()">@csrf @method('PATCH')<button class="min-h-9 rounded-full border px-3 font-bold text-brand-danger-warm">Terminar chat</button></form>
            </div>
        @endif
        @if(!$chat->acceptsMessages())<p class="mt-2 font-bold">Este chat es de solo lectura. Si existe una disputa, continúa en su expediente.</p>@endif
    </div>
    <div x-ref="scroll" x-on:scroll.passive="onScroll()" class="conversation-thread-background min-h-0 flex-1 overflow-y-auto px-4 py-4" data-chat-scroll>
        @if($hasOlder)<button type="button" x-on:click="older()" class="mb-4 min-h-11 w-full rounded-xl bg-white p-2 text-sm font-bold" x-bind:disabled="loadingOlder">Cargar mensajes anteriores</button>@endif
        <div class="space-y-3" data-chat-rows role="log" aria-label="Mensajes">
            @forelse($messages as $message)
                @php
                    $mine = $message->admin_user_id === null && $message->sender_id === $user->id;
                    $system = $message->type->value === 'system';
                    $read = $message->receipts->contains(fn($receipt) => $receipt->read_at !== null);
                    $delivered = $message->receipts->contains(fn($receipt) => $receipt->delivered_at !== null);
                    $legacyRead = $chat->participants->contains(fn($person) => $person->id !== $user->id && ($person->pivot->last_read_message_id !== null ? $person->pivot->last_read_message_id >= $message->id : ($person->pivot->last_read_at && \Illuminate\Support\Carbon::parse($person->pivot->last_read_at)->gte($message->created_at))));
                @endphp
                <article wire:key="message-{{ $message->id }}" data-message-id="{{ $message->id }}" data-client-token="{{ $message->client_message_id }}" class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm {{ $system ? 'bg-brand-page-soft text-brand-copy' : ($mine ? 'bg-brand text-white' : 'bg-white text-brand-ink') }}">
                        @if($message->body)<p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $message->body }}</p>@endif
                        @foreach($message->attachments as $attachment)<a href="{{ route('chat.attachments.show',$attachment) }}" target="_blank" rel="noopener"><img class="mt-2 max-h-64 max-w-full rounded-xl object-contain" src="{{ route('chat.attachments.show',$attachment) }}" alt="Imagen adjunta" loading="lazy" width="{{ $attachment->width }}" height="{{ $attachment->height }}"></a>@endforeach
                        <p class="mt-1 text-right text-[10px] opacity-80"><time>{{ $message->created_at->format('H:i') }}</time>@if($mine && !$system) · {{ $read || $legacyRead ? 'Leído · Visto' : ($delivered ? 'Entregado' : 'Enviado') }}@endif</p>
                    </div>
                </article>
            @empty<p class="py-10 text-center text-sm text-brand-muted">Escribe el primer mensaje para comenzar.</p>@endforelse
        </div>
    </div>
    <button x-cloak x-show="newMessages" x-on:click="bottom()" class="shrink-0 bg-brand-orange px-4 py-2 text-sm font-black text-white">Nuevos mensajes ↓</button>
    <p class="min-h-6 shrink-0 bg-white px-4 text-xs text-brand-copy" x-text="typing ? 'Escribiendo…' : ''" aria-live="polite"></p>
    <livewire:chat.message-input :conversation-id="$chat->public_id" :key="'input-'.$chat->public_id.'-'.(int)$chat->acceptsMessages()" />
</section>
