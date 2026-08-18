@props(['active' => null])
@auth
    @php
        $bottomUser = auth()->user();
        $administrativeOnly = $bottomUser->hasRole('superadmin') || ($bottomUser->hasRole('admin') && ! $bottomUser->canUseMarketplace());
        $items = $administrativeOnly ? [] : [
            ['key' => 'home', 'label' => 'Inicio', 'route' => route('dashboard'), 'icon' => 'home'],
            ['key' => 'publish', 'label' => 'Publicar', 'route' => route('dashboard', ['publicar' => 'choose']).'#publicar', 'icon' => 'plus'],
            ['key' => 'orders', 'label' => 'Pedidos', 'route' => route('orders.index'), 'icon' => 'orders'],
            ['key' => 'messages', 'label' => 'Mensajes', 'route' => route('conversations.index'), 'icon' => 'messages'],
            ['key' => 'profile', 'label' => 'Perfil', 'route' => route('profile.show', $bottomUser), 'icon' => 'profile'],
        ];
    @endphp
    @if($items !== [])
        <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-[#123B4A]/10 bg-white/95 pb-[env(safe-area-inset-bottom)] shadow-[0_-10px_30px_rgba(18,59,74,.08)] backdrop-blur-xl" aria-label="Navegación principal">
            <div class="mx-auto grid max-w-xl grid-cols-5 px-1">
                @foreach($items as $item)
                    @php($selected = $active === $item['key'])
                    <a class="flex min-h-16 flex-col items-center justify-center gap-1 px-1 text-[10px] font-black {{ $selected ? 'text-[#D85B0B]' : 'text-[#64787F]' }}" href="{{ $item['route'] }}" @if($selected) aria-current="page" @endif>
                        @switch($item['icon'])
                            @case('home')<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/></svg>@break
                            @case('plus')<span class="grid size-8 place-items-center rounded-full {{ $selected ? 'bg-[#F97316] text-white' : 'bg-[#FFF1E8] text-[#D85B0B]' }}"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg></span>@break
                            @case('orders')<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 2h6v4H9zM8 11h8M8 15h6"/></svg>@break
                            @case('messages')<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-8 8H5l-3 2 1-5a9 9 0 1 1 18-5Z"/></svg>@break
                            @default<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        @endswitch
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endif
@endauth