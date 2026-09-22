<section class="flex h-full min-h-0 flex-col bg-white" wire:poll.30s.visible="refreshList" aria-label="Lista de conversaciones">
    <div class="shrink-0 border-b border-brand/10 p-4">
        <h1 class="text-xl font-black">Conversaciones</h1>
        <p class="mt-1 text-xs text-brand-copy">Cada publicación tiene su propio chat.</p>
        <label class="mt-3 block"><span class="sr-only">Buscar conversaciones</span><input type="search" wire:model.live.debounce.400ms="search" maxlength="100" placeholder="Persona o publicación…" class="min-h-11 w-full rounded-xl border border-brand/15 bg-brand-surface px-3 text-sm"></label>
        @error('search')<p class="text-sm text-brand-danger-warm">{{ $message }}</p>@enderror
    </div>
    <div class="min-h-0 flex-1 overflow-y-auto">
        @forelse($groups as $key => $items)
            <section wire:key="group-{{ $key }}" class="border-b border-brand/10">
                <h2 class="flex items-center gap-2 bg-brand-surface px-4 py-2 text-xs font-black">
                    @if($items->first()['image'])<img class="size-9 shrink-0 rounded-lg object-cover" src="{{ $items->first()['image'] }}" alt="" loading="lazy">@endif
                    <span class="min-w-0"><span class="block text-brand-orange">{{ $items->first()['kind'] }}</span><span class="line-clamp-2">{{ $items->first()['title'] }}</span></span>
                </h2>
                @foreach($items as $item)
                    <a wire:key="conversation-{{ $item['id'] }}" href="{{ $item['url'] }}" @if($selected === $item['id']) aria-current="page" @endif class="flex min-h-20 items-center gap-3 px-4 py-3 hover:bg-brand-success-soft {{ $selected === $item['id'] ? 'bg-brand-success-soft' : '' }}">
                        @if($item['avatar'])<img src="{{ $item['avatar'] }}" alt="" class="size-10 shrink-0 rounded-full object-cover">@else<span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-avatar-strong font-black" aria-hidden="true">{{ mb_substr($item['name'],0,1) }}</span>@endif
                        <span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-2"><strong class="truncate text-sm">{{ $item['name'] }}</strong><time class="shrink-0 text-[10px] text-brand-muted">{{ $item['at'] }}</time></span><span class="mt-1 block truncate text-xs text-brand-copy">{{ $item['preview'] }}</span></span>
                        @if($item['unread'])<span class="rounded-full bg-brand-orange px-2 py-1 text-xs font-black text-white" aria-label="{{ $item['unread'] }} mensajes sin leer">{{ $item['unread'] > 99 ? '99+' : $item['unread'] }}</span>@endif
                    </a>
                @endforeach
            </section>
        @empty
            <p class="p-6 text-sm text-brand-copy">{{ $search ? 'No hay conversaciones que coincidan.' : 'Todavía no hay conversaciones. Escribe desde una publicación para comenzar.' }}</p>
        @endforelse
        @if($hasMore)<button wire:click="loadMore" wire:loading.attr="disabled" class="min-h-11 w-full p-3 text-sm font-bold text-brand">Cargar más conversaciones</button>@endif
    </div>
</section>
