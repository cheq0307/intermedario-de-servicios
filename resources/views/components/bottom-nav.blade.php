@props(['active' => null])
@auth
    @php
        $bottomUser = auth()->user();
        $administrativeOnly = $bottomUser->hasRole('superadmin') || ($bottomUser->hasRole('admin') && ! $bottomUser->canUseMarketplace());
        $messageBadge = $administrativeOnly ? 0 : $bottomUser->unreadNotifications()->count() + $bottomUser->unreadConversationsCount();
        $items = $administrativeOnly ? [] : [
            ['key' => 'home', 'label' => 'Inicio', 'route' => route('dashboard'), 'icon' => 'home'],
            ['key' => 'orders', 'label' => 'Pedidos', 'route' => route('orders.index'), 'icon' => 'orders'],
            ['key' => 'publish', 'label' => 'Publicar', 'route' => route('dashboard', ['publicar' => 'choose']).'#publicar', 'icon' => 'plus'],
            ['key' => 'messages', 'label' => 'Mensajes', 'route' => route('conversations.index'), 'icon' => 'messages'],
            ['key' => 'more', 'label' => 'Más', 'route' => route('more.index'), 'icon' => 'more'],
        ];
    @endphp
    @if($items !== [])
        <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-brand/10 bg-white/95 pb-[env(safe-area-inset-bottom)] shadow-bottom-navigation backdrop-blur-xl" aria-label="Navegación principal">
            <div class="mx-auto grid max-w-xl grid-cols-5 px-1">
                @foreach($items as $item)
                    @php($selected = $active === $item['key'])
                    <a class="relative flex min-h-16 flex-col items-center justify-center gap-1 px-1 text-[10px] font-black {{ $selected ? 'text-brand-danger-warm' : 'text-brand-slate' }} {{ $item['key']==='publish' ? '-translate-y-2' : '' }}" href="{{ $item['route'] }}" @if($selected) aria-current="page" @endif>
                        @switch($item['icon'])
                            @case('home')<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/></svg>@break
                            @case('plus')<span class="grid size-11 place-items-center rounded-full border-4 border-white bg-brand-orange text-white shadow-lg"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg></span>@break
                            @case('orders')<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 2h6v4H9zM8 11h8M8 15h6"/></svg>@break
                            @case('messages')<span class="relative"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-8 8H5l-3 2 1-5a9 9 0 1 1 18-5Z"/></svg>@if($messageBadge)<span class="absolute -right-2.5 -top-2"><span class="absolute inset-0 animate-ping rounded-full bg-red-400 opacity-60"></span><span class="relative block min-w-4 rounded-full bg-red-500 px-1 text-center text-[9px] leading-4 text-white">{{ $messageBadge > 99 ? '99+' : $messageBadge }}</span></span>@endif</span>@break
                            @case('more')<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>@break
                        @endswitch
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endif
@endauth
