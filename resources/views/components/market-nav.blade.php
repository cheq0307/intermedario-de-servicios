@props(['backUrl' => null, 'searchValue' => '', 'showAccount' => true, 'showNotifications' => false])
@php
    $navUser = auth()->user();
    $administrativeOnly = $navUser?->hasRole('superadmin') || ($navUser?->hasRole('admin') && ! $navUser?->canUseMarketplace());
    $accountUrl = $navUser ? ($administrativeOnly ? route('admin.index') : route('profile.show', $navUser)) : route('login');
@endphp
<header class="sticky top-0 z-40 border-b border-[#123B4A]/10 bg-white/95 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center gap-2 px-3 py-3 sm:gap-3 sm:px-5">
        @if($backUrl)
            <a class="grid size-10 shrink-0 place-items-center rounded-full border border-[#123B4A]/10 bg-white text-[#123B4A] shadow-sm" href="{{ $backUrl }}" aria-label="Regresar"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
        @endif
        <div class="group/search relative min-w-0 flex-1 sm:mx-auto sm:max-w-2xl">
            <form class="flex min-w-0 items-center gap-2 rounded-2xl bg-[#F4F6F5] px-3 py-2.5 ring-[#123B4A]/10 focus-within:ring-2" role="search" method="GET" action="{{ route('explore') }}">
                <input class="min-w-0 flex-1 bg-transparent text-sm font-semibold outline-none placeholder:text-[#8A999E]" type="search" name="q" value="{{ $searchValue }}" maxlength="100" placeholder="Buscar en Plaza Local" autocomplete="off">
                <button class="grid size-8 shrink-0 place-items-center rounded-full text-[#536A72]" type="submit" aria-label="Buscar"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button>
            </form>
            <div class="invisible absolute inset-x-0 top-[calc(100%+.5rem)] z-50 translate-y-1 rounded-2xl border bg-white p-3 opacity-0 shadow-2xl transition group-focus-within/search:visible group-focus-within/search:translate-y-0 group-focus-within/search:opacity-100">
                <p class="px-2 text-[10px] font-black uppercase tracking-[.16em] text-[#F97316]">Explora rápidamente</p>
                <div class="mt-2 grid grid-cols-2 gap-2 text-sm font-black sm:grid-cols-5">
                    <a class="rounded-xl bg-[#FAF8F4] p-3 hover:bg-[#E9F7F0]" href="{{ route('explore', ['q'=>'comida']) }}">Comida</a>
                    <a class="rounded-xl bg-[#FAF8F4] p-3 hover:bg-[#E9F7F0]" href="{{ route('explore', ['q'=>'transporte']) }}">Transporte</a>
                    <a class="rounded-xl bg-[#FAF8F4] p-3 hover:bg-[#E9F7F0]" href="{{ route('explore', ['q'=>'hogar']) }}">Hogar</a>
                    <a class="rounded-xl bg-[#FAF8F4] p-3 hover:bg-[#E9F7F0]" href="{{ route('explore', ['q'=>'productos']) }}">Productos</a>
                    <a class="rounded-xl bg-[#FFF8E6] p-3 text-[#8B5B00]" href="{{ route('vacancies.index') }}">Empleo</a>
                </div>
            </div>
        </div>
        @if($showNotifications && $navUser && ! $administrativeOnly)
            <a class="relative grid size-11 shrink-0 place-items-center rounded-full text-[#536A72] hover:bg-[#E9F7F0]" href="{{ route('notifications.index') }}" aria-label="Notificaciones"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>@if($navUser->unreadNotifications()->exists())<span class="absolute right-1.5 top-1.5 size-2.5 rounded-full bg-[#F97316]"></span>@endif</a>
        @endif
        @if($navUser?->hasAnyRole(['admin', 'superadmin']))
            <a class="hidden rounded-full bg-[#123B4A] px-4 py-2.5 text-xs font-black text-white sm:inline-flex" href="{{ route('admin.index') }}">Abrir administración</a>
        @endif
        @if($showAccount)
            <a class="grid size-11 shrink-0 place-items-center rounded-full text-[#536A72] hover:bg-[#E9F7F0]" href="{{ $accountUrl }}" aria-label="{{ $navUser ? 'Abrir mi cuenta' : 'Iniciar sesión' }}"><svg class="size-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm0 4a3.2 3.2 0 1 1 0 6.4A3.2 3.2 0 0 1 12 6Zm0 13.2a7.2 7.2 0 0 1-5.3-2.3c.7-1.8 2.8-3 5.3-3s4.6 1.2 5.3 3a7.2 7.2 0 0 1-5.3 2.3Z"/></svg></a>
        @endif
        @guest
            <a class="hidden rounded-full bg-[#F97316] px-4 py-2.5 text-xs font-black text-white sm:inline-flex" href="{{ route('register') }}">Crear cuenta</a>
        @endguest
    </div>
</header>