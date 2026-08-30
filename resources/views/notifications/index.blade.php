<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notificaciones - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen pb-24 bg-brand-surface text-brand-ink antialiased">
    <x-market-nav :back-url="route('dashboard')" />
    <main class="mx-auto max-w-4xl px-5 py-9">
        <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.18em] text-brand-orange">Actividad importante</p><h1 class="mt-2 text-3xl font-black">Notificaciones</h1><p class="mt-2 text-sm font-bold text-brand-muted">{{ $unreadCount ? $unreadCount.' sin leer' : 'No tienes avisos pendientes' }} · Abrir un aviso lo marca como leído.</p></div>@if($unreadCount)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="rounded-full border border-brand/10 bg-white px-5 py-2.5 text-sm font-black" type="submit">Marcar todas como leídas</button></form>@endif</div>
        <nav class="mt-6 flex gap-2 overflow-x-auto pb-1" aria-label="Filtrar notificaciones">
            @foreach(['all' => 'Todas', 'administrative' => 'Administrativas', 'social' => 'Sociales'] as $filterKey => $filterLabel)
                <a class="shrink-0 rounded-full border px-4 py-2 text-sm font-black transition {{ $filter === $filterKey ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-brand-copy hover:border-brand/30' }}" href="{{ $filterKey === 'all' ? route('notifications.index') : route('notifications.index', ['filter' => $filterKey]) }}" @if($filter === $filterKey) aria-current="page" @endif>{{ $filterLabel }}</a>
            @endforeach
        </nav>
        @if(session('status'))<p class="mt-5 rounded-2xl bg-brand-success-soft p-4 text-sm font-black text-brand-success">{{ session('status') }}</p>@endif
        <div class="mt-7 space-y-3">
            @forelse($notifications as $notification)
                @php
                    $kind = $notification->data['kind'] ?? 'activity';
                    $kindLabel = match(true) {
                        str_starts_with($kind, 'social_') => 'Social',
                        str_contains($kind, 'support') => 'Soporte',
                        default => 'Actividad',
                    };
                @endphp
                <form method="POST" action="{{ route('notifications.open', $notification->id) }}">@csrf @method('PATCH')
                    <button class="relative flex w-full items-start gap-4 overflow-hidden rounded-[1.5rem] border p-5 text-left shadow-sm transition hover:-translate-y-0.5 {{ $notification->read_at ? 'border-brand/10 bg-brand-neutral-page text-brand-copy' : 'border-brand-success-bright/30 bg-brand-success-soft text-brand-ink' }}" type="submit">
                        <span class="absolute inset-y-0 left-0 w-1.5 {{ $notification->read_at ? 'bg-brand-line-strong' : 'bg-brand-success-bright' }}"></span>
                        <span class="mt-1 grid size-10 shrink-0 place-items-center rounded-full {{ $notification->read_at ? 'bg-white text-brand-muted' : 'bg-brand-success text-white' }}">{{ $notification->read_at ? '✓' : '•' }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2"><strong class="block {{ $notification->read_at ? 'font-bold' : 'font-black' }}">{{ $notification->data['title'] ?? 'Actividad nueva' }}</strong><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $notification->read_at ? 'bg-white text-brand-muted' : 'bg-brand-success text-white' }}">{{ $kindLabel }} · {{ $notification->read_at ? 'Leída' : 'Nueva' }}</span></span>
                            <span class="mt-1 block text-sm font-semibold leading-6 {{ $notification->read_at ? 'text-brand-form-muted' : 'text-brand-copy' }}">{{ $notification->data['body'] ?? '' }}</span>
                            <time class="mt-2 block text-xs font-bold text-brand-caption">{{ $notification->created_at->diffForHumans() }}</time>
                        </span>
                        <span class="mt-2 font-black {{ $notification->read_at ? 'text-brand-caption' : 'text-brand-success' }}">Abrir →</span>
                    </button>
                </form>
            @empty
                <div class="rounded-[1.75rem] border border-dashed border-brand/20 bg-white/60 p-12 text-center"><h2 class="text-xl font-black">No hay avisos en este filtro</h2><p class="mt-2 text-sm font-bold text-brand-muted">Puedes revisar otra categoría de notificaciones.</p></div>
            @endforelse
        </div>
        @if($notifications->hasPages())<div class="mt-6">{{ $notifications->links() }}</div>@endif
        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-[1.5rem] border border-brand/10 bg-white p-5"><div><h2 class="font-black">¿Necesitas ayuda?</h2><p class="mt-1 text-sm font-semibold text-brand-muted">Abre un caso y conserva toda la conversación con soporte.</p></div><a class="rounded-full bg-brand px-5 py-3 text-sm font-black text-white" href="{{ route('support.create') }}">Contactar soporte</a></div>
    </main>
<x-bottom-nav />
</body>
</html>
