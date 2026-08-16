@props(['backUrl' => null, 'searchValue' => ''])
@php
    $navUser = auth()->user();
    $administrativeOnly = $navUser?->hasRole('superadmin') || ($navUser?->hasRole('admin') && ! $navUser?->canActAsClient() && ! $navUser?->canActAsProvider());
    $accountUrl = $navUser ? ($administrativeOnly ? route('admin.index') : route('profile.show', $navUser)) : route('login');
    $itemClass = 'flex items-center gap-3 rounded-2xl px-4 py-3 transition hover:bg-[#E9F7F0]';
    $activeClass = 'bg-[#123B4A] text-white shadow-sm hover:bg-[#123B4A]';
    $startUrl = $navUser ? ($administrativeOnly ? route('admin.index') : route('dashboard')) : route('home');
@endphp
<header class="sticky top-0 z-50 border-b border-[#123B4A]/10 bg-white/95 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center gap-2 px-3 py-3 sm:gap-3 sm:px-5">
        <button class="grid size-11 shrink-0 place-items-center rounded-full text-[#123B4A] transition hover:bg-[#E9F7F0]" type="button" data-market-menu-open aria-label="Abrir menú" aria-expanded="false">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        @if($backUrl)
            <a class="grid size-10 shrink-0 place-items-center rounded-full border border-[#123B4A]/10 bg-white text-[#123B4A] shadow-sm transition hover:border-[#F97316]/30 hover:text-[#D85B0B]" href="{{ $backUrl }}" aria-label="Regresar" title="Regresar">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </a>
        @endif
        <form class="flex min-w-0 flex-1 items-center gap-2 rounded-2xl bg-[#F4F6F5] px-3 py-2.5 sm:mx-auto sm:max-w-2xl" role="search" method="GET" action="{{ route('explore') }}">
            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-[#123B4A] text-[11px] font-black text-white" aria-hidden="true">P</span>
            <input class="min-w-0 flex-1 bg-transparent text-sm font-semibold outline-none placeholder:text-[#8A999E]" type="search" name="q" value="{{ $searchValue }}" maxlength="100" placeholder="Buscar en Plaza Local">
            <button class="grid size-8 shrink-0 place-items-center rounded-full text-[#536A72] transition hover:bg-white hover:text-[#123B4A]" type="submit" aria-label="Buscar"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg></button>
        </form>
        <a class="grid size-11 shrink-0 place-items-center rounded-full text-[#536A72] transition hover:bg-[#E9F7F0] hover:text-[#123B4A]" href="{{ $accountUrl }}" aria-label="{{ $navUser ? 'Abrir mi cuenta' : 'Iniciar sesión' }}" title="{{ $navUser ? 'Mi cuenta' : 'Iniciar sesión' }}">
            <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 4a3.2 3.2 0 1 1 0 6.4A3.2 3.2 0 0 1 12 6Zm0 13.2a7.2 7.2 0 0 1-5.3-2.3c.7-1.8 2.8-3 5.3-3s4.6 1.2 5.3 3a7.2 7.2 0 0 1-5.3 2.3Z"/></svg>
        </a>
    </div>
</header>
<div class="pointer-events-none invisible fixed inset-0 z-[70] bg-[#071D24]/55 opacity-0 backdrop-blur-[2px] transition-opacity duration-300" data-market-menu-overlay aria-hidden="true"></div>
<aside class="pointer-events-none invisible fixed inset-y-0 left-0 z-[80] flex h-[100dvh] max-h-[100dvh] w-[min(86vw,340px)] -translate-x-full flex-col overflow-hidden bg-white shadow-2xl transition-transform duration-300" style="background-color:#ffffff;opacity:1" data-market-menu role="dialog" aria-modal="true" aria-label="Menú principal" aria-hidden="true">
    <div class="flex items-center justify-between border-b border-[#123B4A]/10 px-5 py-4">
        <a class="flex items-center gap-3 font-black" href="{{ $startUrl }}"><span class="grid size-10 place-items-center rounded-2xl bg-[#123B4A] text-white">P</span><span>Plaza Local</span></a>
        <button class="grid size-10 place-items-center rounded-full hover:bg-[#FAF8F4]" type="button" data-market-menu-close aria-label="Cerrar menú"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/></svg></button>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto p-4 text-sm font-black" aria-label="Menú principal">
        <a class="{{ $itemClass }} {{ request()->routeIs('dashboard', 'home') ? $activeClass : '' }}" href="{{ $startUrl }}"><span aria-hidden="true">⌂</span> Inicio</a>
        <a class="{{ $itemClass }} {{ request()->routeIs('explore') ? $activeClass : '' }}" href="{{ route('explore') }}"><span aria-hidden="true">⌕</span> Explorar productos y servicios</a>
        @auth
            @unless($administrativeOnly)
                <a class="{{ $itemClass }}" href="{{ route('dashboard', ['publicar' => 'request']).'#crear-publicacion' }}"><span aria-hidden="true">＋</span> Publicar</a>
                <a class="{{ $itemClass }} {{ request()->routeIs('orders.*') ? $activeClass : '' }}" href="{{ route('orders.index') }}"><span aria-hidden="true">▣</span> Mis trabajos</a>
                <a class="{{ $itemClass }} {{ request()->routeIs('conversations.*') ? $activeClass : '' }}" href="{{ route('conversations.index') }}"><span aria-hidden="true">◇</span> Mensajes</a>
                <a class="{{ $itemClass }} {{ request()->routeIs('notifications.*') ? $activeClass : '' }}" href="{{ route('notifications.index') }}"><span aria-hidden="true">♢</span> Notificaciones</a>
                <a class="{{ $itemClass }} {{ request()->routeIs('support.*') ? $activeClass : '' }}" href="{{ route('support.index') }}"><span aria-hidden="true">?</span> Soporte</a>
                <a class="{{ $itemClass }} {{ request()->routeIs('profile.*') ? $activeClass : '' }}" href="{{ route('profile.show', $navUser) }}"><span aria-hidden="true">●</span> Mi perfil</a>
            @endunless
            @if($navUser->hasAnyRole(['admin', 'superadmin']))
                <a class="{{ $itemClass }} {{ request()->routeIs('admin.*') ? $activeClass : 'bg-[#FFF1E8] text-[#D85B0B]' }}" href="{{ route('admin.index') }}" aria-label="Abrir administración"><span aria-hidden="true">⚙</span> Administración</a>
            @endif
        @else
            <div class="my-3 border-t border-[#123B4A]/10"></div>
            <a class="flex items-center gap-3 rounded-2xl px-4 py-3 hover:bg-[#E9F7F0]" href="{{ route('login') }}"><span aria-hidden="true">→</span> Iniciar sesión</a>
            <a class="flex items-center gap-3 rounded-2xl bg-[#123B4A] px-4 py-3 text-white" href="{{ route('register') }}"><span aria-hidden="true">＋</span> Crear cuenta</a>
        @endauth
    </nav>
    @auth
        <form class="border-t border-[#123B4A]/10 p-4" method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-2xl px-4 py-3 text-left text-sm font-black text-red-700 hover:bg-red-50" type="submit">Cerrar sesión</button></form>
    @endauth
</aside>
