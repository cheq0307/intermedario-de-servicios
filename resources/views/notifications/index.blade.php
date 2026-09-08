<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notificaciones - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen pb-24 bg-brand-surface text-brand-ink antialiased">
    <x-market-nav :back-url="route(\App\Support\IdentityRoutes::name('dashboard'))" />
    <main class="mx-auto max-w-4xl px-5 py-9">
        <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.18em] text-brand-orange">Actividad importante</p><h1 class="mt-2 text-3xl font-black">Notificaciones</h1><p class="mt-2 text-sm font-bold text-brand-muted"><span data-live-notification-label>{{ $unreadCount ? $unreadCount.' sin leer' : 'No tienes avisos pendientes' }}</span> · Abrir un aviso lo marca como leído.</p></div><form data-live-notification-read-all class="{{ $unreadCount ? '' : 'hidden' }}" method="POST" action="{{ route(\App\Support\IdentityRoutes::name('notifications.read-all')) }}">@csrf @method('PATCH')<button class="rounded-full border border-brand/10 bg-white px-5 py-2.5 text-sm font-black" type="submit">Marcar todas como leídas</button></form></div>
        <nav class="mt-6 flex gap-2 overflow-x-auto pb-1" aria-label="Filtrar notificaciones">
            @foreach(['all' => 'Todas', 'administrative' => 'Administrativas', 'social' => 'Sociales'] as $filterKey => $filterLabel)
                <a class="shrink-0 rounded-full border px-4 py-2 text-sm font-black transition {{ $filter === $filterKey ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-brand-copy hover:border-brand/30' }}" href="{{ $filterKey === 'all' ? route(\App\Support\IdentityRoutes::name('notifications.index')) : route(\App\Support\IdentityRoutes::name('notifications.index'), ['filter' => $filterKey]) }}" @if($filter === $filterKey) aria-current="page" @endif>{{ $filterLabel }}</a>
            @endforeach
        </nav>
        @if(session('status'))<p class="mt-5 rounded-2xl bg-brand-success-soft p-4 text-sm font-black text-brand-success">{{ session('status') }}</p>@endif
        <div data-live-fragment data-live-fragment-url="{{ request()->fullUrl() }}" data-live-fragment-revision="{{ $revision }}" data-live-fragment-interval="15000">
            @include('notifications._list')
        </div>
        @unless(auth()->user() instanceof \App\Models\AdminUser)
        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-[1.5rem] border border-brand/10 bg-white p-5"><div><h2 class="font-black">¿Necesitas ayuda?</h2><p class="mt-1 text-sm font-semibold text-brand-muted">Abre un caso y conserva toda la conversación con soporte.</p></div><a class="rounded-full bg-brand px-5 py-3 text-sm font-black text-white" href="{{ route('support.create') }}">Contactar soporte</a></div>
        @endunless
    </main>
<x-bottom-nav />
</body>
</html>
