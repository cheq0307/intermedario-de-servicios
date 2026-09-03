@forelse($notificationPreview as $item)
    <form class="border-b border-brand/8 last:border-b-0" method="POST" action="{{ route('notifications.open', $item->id) }}" data-notification-item data-notification-category="{{ $item->category }}">
        @csrf
        @method('PATCH')
        <button class="relative flex w-full items-start gap-3 px-4 py-3.5 text-left transition hover:bg-brand-page {{ $item->isRead ? 'bg-white' : 'bg-brand-success-faint' }}" type="submit">
            <span class="grid size-9 shrink-0 place-items-center rounded-full {{ $item->isRead ? 'bg-brand-neutral-page text-brand-copy' : 'bg-brand-teal-surface text-brand' }}" aria-hidden="true">
                @switch($item->context)
                    @case('dispute')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M12 3 2.8 20h18.4zM12 9v5M12 17.5v.5"/></svg>@break
                    @case('verification')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><circle cx="10" cy="8" r="3"/><path d="M4 19c.4-4 2.4-6 6-6 1.3 0 2.4.3 3.3.8M15 17l2 2 4-5"/></svg>@break
                    @case('report')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M6 21V4m0 1h11l-2 4 2 4H6"/></svg>@break
                    @case('message')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M4 5h16v12H9l-5 4z"/></svg>@break
                    @case('like')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M20.8 5.8a5 5 0 0 0-7.1 0L12 7.5l-1.7-1.7a5 5 0 0 0-7.1 7.1L12 21l8.8-8.1a5 5 0 0 0 0-7.1z"/></svg>@break
                    @case('share')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="m3 11 18-8-7 18-3-7zM11 14 21 3"/></svg>@break
                    @case('follow')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3.5 19c.4-4 2.2-6 5.5-6 1.8 0 3.2.6 4.1 1.7M18 10v6M15 13h6"/></svg>@break
                    @default<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/></svg>
                @endswitch
            </span>
            <span class="min-w-0 flex-1">
                <strong class="block truncate text-sm {{ $item->isRead ? 'font-bold text-brand-copy' : 'font-black text-brand-ink' }}">{{ $item->title }}</strong>
                <span class="mt-0.5 block line-clamp-2 text-xs font-semibold leading-5 text-brand-muted">{{ $item->body }}</span>
                <time class="mt-1 block text-[.68rem] font-bold text-brand-caption">{{ $item->createdAtLabel }}</time>
            </span>
            @unless($item->isRead)<span class="mt-2 size-2 shrink-0 rounded-full bg-brand-orange" aria-label="No leída"></span>@endunless
        </button>
    </form>
@empty
    <div class="px-6 py-10 text-center">
        <p class="font-black text-brand-ink">Todo tranquilo por aquí</p>
        <p class="mt-1 text-xs font-bold text-brand-muted">Los avisos importantes aparecerán en esta bandeja.</p>
    </div>
@endforelse
@if($notificationPreview->isNotEmpty())
    <div class="hidden px-6 py-10 text-center" data-notification-filter-empty>
        <p class="font-black text-brand-ink">No hay avisos en este filtro</p>
        <p class="mt-1 text-xs font-bold text-brand-muted">Puedes revisar otra pestaña.</p>
    </div>
@endif
