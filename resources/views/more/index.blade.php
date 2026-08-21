<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Más - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#FAF8F4] pb-24 text-[#17313A] antialiased">
<x-market-nav :back-url="route('dashboard')" />
<main class="mx-auto max-w-3xl px-4 py-7 sm:px-6">
    <section class="rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm">
        <a class="flex items-center gap-4" href="{{ route('profile.show', $user) }}">
            @if($user->avatar_path)<img class="size-14 rounded-full object-cover" src="{{ asset('storage/'.$user->avatar_path) }}" alt="">@else<span class="grid size-14 place-items-center rounded-full bg-[#DCEAE6] text-xl font-black">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</span>@endif
            <span class="min-w-0 flex-1"><strong class="block truncate text-lg">{{ $user->name }}</strong><span class="block truncate text-sm font-semibold text-[#6B7D83]">Ver mi perfil público</span></span>
            <span aria-hidden="true">›</span>
        </a>
    </section>

    <section class="mt-5 overflow-hidden rounded-[2rem] border border-[#123B4A]/10 bg-white shadow-sm">
        <a class="flex items-center gap-4 border-b px-6 py-4 font-black hover:bg-[#FAF8F4]" href="{{ route('profile.show', [$user, 'tab'=>'offers']) }}"><span class="grid size-10 place-items-center rounded-full bg-[#E9F7F0]"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4"/></svg></span><span class="flex-1">Mis publicaciones</span><span>›</span></a>
        <a class="flex items-center gap-4 border-b px-6 py-4 font-black hover:bg-[#FAF8F4]" href="{{ route('orders.index') }}"><span class="grid size-10 place-items-center rounded-full bg-[#E9F7F0]"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16v12H4zM8 7V4h8v3M4 11h16"/></svg></span><span class="flex-1">Mis pedidos y trabajos</span><span>›</span></a>
        <a class="flex items-center gap-4 border-b px-6 py-4 font-black hover:bg-[#FAF8F4]" href="{{ route('profile.show', [$user, 'tab'=>'reviews']) }}"><span class="grid size-10 place-items-center rounded-full bg-[#FFF8E6]"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/></svg></span><span class="flex-1">Reseñas de mi perfil</span><span>›</span></a>
        <a class="flex items-center gap-4 border-b px-6 py-4 font-black hover:bg-[#FAF8F4]" href="{{ route('vacancies.mine') }}"><span class="grid size-10 place-items-center rounded-full bg-[#FFF8E6]"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M8 7V4h8v3M3 12h18"/></svg></span><span class="flex-1">Empleo</span><span>›</span></a>
        <a class="flex items-center gap-4 border-b px-6 py-4 font-black hover:bg-[#FAF8F4]" href="{{ route('promotions.index') }}"><span class="grid size-10 place-items-center rounded-full bg-[#FFF1E8]"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 13h4l8 5V6l-8 5H4z"/><path d="M8 14v5"/></svg></span><span class="flex-1">Promociones contratadas</span><span>›</span></a>
        <a class="flex items-center gap-4 border-b px-6 py-4 font-black hover:bg-[#FAF8F4]" href="{{ route('profile.edit') }}"><span class="grid size-10 place-items-center rounded-full bg-[#F1F3F2]"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg></span><span class="flex-1">Configuración del perfil</span><span>›</span></a>
        @if(Route::has('security'))<a class="flex items-center gap-4 border-b px-6 py-4 font-black hover:bg-[#FAF8F4]" href="{{ route('security') }}"><span class="grid size-10 place-items-center rounded-full bg-[#F1F3F2]"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6z"/></svg></span><span class="flex-1">Seguridad de la cuenta</span><span>›</span></a>@endif
        <a class="flex items-center gap-4 px-6 py-4 font-black hover:bg-[#FAF8F4]" href="{{ route('support.index') }}"><span class="grid size-10 place-items-center rounded-full bg-[#F1F3F2]"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4.2 1.8c-1 .8-1.7 1.2-1.7 2.7M12 17h.01"/></svg></span><span class="flex-1">Ayuda y soporte</span><span>›</span></a>
    </section>

    <section class="mt-5 rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4"><div><h2 class="text-lg font-black">Personas y negocios que sigues</h2><p class="mt-1 text-sm font-semibold text-[#6B7D83]">Acceso rápido a cuentas de tu interés.</p></div><span class="rounded-full bg-[#E9F7F0] px-3 py-1 text-xs font-black text-[#14734A]">{{ $following->count() }}</span></div>
        <div class="mt-4 space-y-2">@forelse($following as $account)<a class="flex items-center gap-3 rounded-2xl bg-[#FAF8F4] p-3 hover:bg-[#E9F7F0]" href="{{ route('profile.show',$account) }}"><span class="grid size-10 place-items-center rounded-full bg-white font-black">{{ mb_strtoupper(mb_substr($account->name,0,1)) }}</span><span class="min-w-0 flex-1"><strong class="block truncate">{{ $account->vendor?->display_name ?: $account->name }}</strong><span class="block truncate text-xs font-semibold text-[#6B7D83]">{{ $account->vendor?->specialty ?: $account->community?->display_label }}</span></span><span>›</span></a>@empty<p class="rounded-2xl border border-dashed p-5 text-center text-sm font-bold text-[#6B7D83]">Todavía no sigues ninguna cuenta.</p>@endforelse</div>
    </section>

    <form class="mt-5" method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-2xl border border-red-200 bg-white px-5 py-4 font-black text-red-600" type="submit">Cerrar sesión</button></form>
</main>
<x-bottom-nav active="more" />
</body></html>