@props(['user', 'unreadCount' => 0])

@php
    $notificationPreview = $user?->notifications()
        ->latest()
        ->limit(8)
        ->get()
        ->map(function ($notification) {
            $kind = (string) ($notification->data['kind'] ?? 'activity');
            $category = str_starts_with($kind, 'social_') ? 'social' : 'administrative';
            $context = match (true) {
                str_contains($kind, 'dispute') => 'dispute',
                str_contains($kind, 'vendor') => 'verification',
                str_contains($kind, 'report'), str_contains($kind, 'removed') => 'report',
                str_contains($kind, 'support'), str_contains($kind, 'conversation'), str_contains($kind, 'comment') => 'message',
                str_contains($kind, 'like') => 'like',
                str_contains($kind, 'share') => 'share',
                str_contains($kind, 'follow') => 'follow',
                default => 'system',
            };

            return [
                'model' => $notification,
                'category' => $category,
                'context' => $context,
                'title' => $notification->data['title'] ?? 'Actividad nueva',
                'body' => $notification->data['body'] ?? '',
            ];
        }) ?? collect();
@endphp

<div class="relative" data-notification-center>
    <button class="relative grid size-10 place-items-center rounded-full border border-[#123B4A]/10 bg-white transition hover:bg-[#F4F7F6]" type="button" aria-label="Abrir notificaciones" aria-haspopup="dialog" aria-expanded="false" data-notification-trigger>
        <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M6 9a6 6 0 0 1 12 0c0 7 3 7 3 8H3c0-1 3-1 3-8M9.5 20h5"/></svg>
        @if($unreadCount)<span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-500 px-1.5 py-0.5 text-center text-[.62rem] font-black text-white">{{ min(99, $unreadCount) }}</span>@endif
    </button>

    <section class="absolute right-0 top-12 z-50 hidden w-[min(25rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-[#123B4A]/15 bg-white shadow-2xl" role="dialog" aria-label="Vista rápida de notificaciones" data-notification-panel>
        <div class="flex items-center justify-between gap-3 border-b border-[#123B4A]/10 px-4 py-3.5">
            <div>
                <h2 class="font-black text-[#17313A]">Notificaciones</h2>
                <p class="text-[.7rem] font-bold text-[#6B7D83]">{{ $unreadCount ? $unreadCount.' sin leer' : 'Todo está al día' }}</p>
            </div>
            @if($unreadCount)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button class="text-xs font-black text-[#14734A] hover:underline" type="submit">Marcar todas leídas</button>
                </form>
            @endif
        </div>

        <div class="flex gap-5 border-b border-[#123B4A]/10 px-4" role="tablist" aria-label="Filtrar notificaciones">
            <button class="notification-filter-tab py-3 text-xs font-black" type="button" role="tab" aria-selected="true" data-notification-tab="all">Todas</button>
            <button class="notification-filter-tab py-3 text-xs font-black" type="button" role="tab" aria-selected="false" data-notification-tab="administrative">Administrativas</button>
            <button class="notification-filter-tab py-3 text-xs font-black" type="button" role="tab" aria-selected="false" data-notification-tab="social">Sociales</button>
        </div>

        <div class="max-h-[26rem] overflow-y-auto" data-notification-list>
            @forelse($notificationPreview as $item)
                @php($notification = $item['model'])
                <form class="border-b border-[#123B4A]/8 last:border-b-0" method="POST" action="{{ route('notifications.open', $notification->id) }}" data-notification-item data-notification-category="{{ $item['category'] }}">
                    @csrf
                    @method('PATCH')
                    <button class="relative flex w-full items-start gap-3 px-4 py-3.5 text-left transition hover:bg-[#F4F7F6] {{ $notification->read_at ? 'bg-white' : 'bg-[#EFF8F5]' }}" type="submit">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full {{ $notification->read_at ? 'bg-[#F0F2F1] text-[#536A72]' : 'bg-[#DDEBE7] text-[#123B4A]' }}" aria-hidden="true">
                            @switch($item['context'])
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
                            <strong class="block truncate text-sm {{ $notification->read_at ? 'font-bold text-[#536A72]' : 'font-black text-[#17313A]' }}">{{ $item['title'] }}</strong>
                            <span class="mt-0.5 block line-clamp-2 text-xs font-semibold leading-5 text-[#6B7D83]">{{ $item['body'] }}</span>
                            <time class="mt-1 block text-[.68rem] font-bold text-[#8A999E]">{{ $notification->created_at->diffForHumans() }}</time>
                        </span>
                        @unless($notification->read_at)<span class="mt-2 size-2 shrink-0 rounded-full bg-[#F97316]" aria-label="No leída"></span>@endunless
                    </button>
                </form>
            @empty
                <div class="px-6 py-10 text-center">
                    <p class="font-black text-[#17313A]">Todo tranquilo por aquí</p>
                    <p class="mt-1 text-xs font-bold text-[#6B7D83]">Los avisos importantes aparecerán en esta bandeja.</p>
                </div>
            @endforelse
            @if($notificationPreview->isNotEmpty())
                <div class="hidden px-6 py-10 text-center" data-notification-filter-empty>
                    <p class="font-black text-[#17313A]">No hay avisos en este filtro</p>
                    <p class="mt-1 text-xs font-bold text-[#6B7D83]">Puedes revisar otra pestaña.</p>
                </div>
            @endif
        </div>

        <a class="block border-t border-[#123B4A]/10 px-4 py-3 text-center text-xs font-black text-[#14734A] hover:bg-[#F4F7F6]" href="{{ route('notifications.index') }}">Ver todas las notificaciones</a>
    </section>
</div>
