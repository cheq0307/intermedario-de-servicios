<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notificaciones - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen pb-24 bg-[#FAF8F4] text-[#17313A] antialiased">
    <x-market-nav :back-url="route('dashboard')" />
    <main class="mx-auto max-w-4xl px-5 py-9">
        <nav class="mb-5 grid grid-cols-2 rounded-2xl bg-[#E8ECEA] p-1"><a class="rounded-xl px-4 py-3 text-center text-sm font-black text-[#536A72]" href="{{ route('conversations.index') }}">Conversaciones @if(auth()->user()->unreadConversationsCount())<span class="ml-1 rounded-full bg-red-500 px-2 py-0.5 text-white">{{ auth()->user()->unreadConversationsCount() }}</span>@endif</a><a class="rounded-xl bg-white px-4 py-3 text-center text-sm font-black shadow-sm" href="{{ route('notifications.index') }}">Notificaciones @if($unreadCount)<span class="ml-1 rounded-full bg-[#F97316] px-2 py-0.5 text-white">{{ $unreadCount }}</span>@endif</a></nav><div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Actividad importante</p><h1 class="mt-2 text-3xl font-black">Notificaciones</h1><p class="mt-2 text-sm font-bold text-[#6B7D83]">{{ $unreadCount ? $unreadCount.' sin leer' : 'No tienes avisos pendientes' }} · Abrir un aviso lo marca como leído.</p></div>@if($unreadCount)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="rounded-full border border-[#123B4A]/10 bg-white px-5 py-2.5 text-sm font-black" type="submit">Marcar todas como leídas</button></form>@endif</div>
        @if(session('status'))<p class="mt-5 rounded-2xl bg-[#E9F7F0] p-4 text-sm font-black text-[#14734A]">{{ session('status') }}</p>@endif
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
                    <button class="relative flex w-full items-start gap-4 overflow-hidden rounded-[1.5rem] border p-5 text-left shadow-sm transition hover:-translate-y-0.5 {{ $notification->read_at ? 'border-[#123B4A]/10 bg-[#F0F2F1] text-[#536A72]' : 'border-[#22A06B]/30 bg-[#E9F7F0] text-[#17313A]' }}" type="submit">
                        <span class="absolute inset-y-0 left-0 w-1.5 {{ $notification->read_at ? 'bg-[#AAB5B1]' : 'bg-[#22A06B]' }}"></span>
                        <span class="mt-1 grid size-10 shrink-0 place-items-center rounded-full {{ $notification->read_at ? 'bg-white text-[#6B7D83]' : 'bg-[#14734A] text-white' }}">{{ $notification->read_at ? '✓' : '•' }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2"><strong class="block {{ $notification->read_at ? 'font-bold' : 'font-black' }}">{{ $notification->data['title'] ?? 'Actividad nueva' }}</strong><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $notification->read_at ? 'bg-white text-[#6B7D83]' : 'bg-[#14734A] text-white' }}">{{ $kindLabel }} · {{ $notification->read_at ? 'Leída' : 'Nueva' }}</span></span>
                            <span class="mt-1 block text-sm font-semibold leading-6 {{ $notification->read_at ? 'text-[#75857F]' : 'text-[#536A72]' }}">{{ $notification->data['body'] ?? '' }}</span>
                            <time class="mt-2 block text-xs font-bold text-[#8A999E]">{{ $notification->created_at->diffForHumans() }}</time>
                        </span>
                        <span class="mt-2 font-black {{ $notification->read_at ? 'text-[#8A999E]' : 'text-[#14734A]' }}">Abrir →</span>
                    </button>
                </form>
            @empty
                <div class="rounded-[1.75rem] border border-dashed border-[#123B4A]/20 bg-white/60 p-12 text-center"><h2 class="text-xl font-black">Todo tranquilo por aquí</h2><p class="mt-2 text-sm font-bold text-[#6B7D83]">Los cambios importantes de propuestas, pedidos y disputas aparecerán aquí.</p></div>
            @endforelse
        </div>
        @if($notifications->hasPages())<div class="mt-6">{{ $notifications->links() }}</div>@endif
        <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-[1.5rem] border border-[#123B4A]/10 bg-white p-5"><div><h2 class="font-black">¿Necesitas ayuda?</h2><p class="mt-1 text-sm font-semibold text-[#6B7D83]">Abre un caso y conserva toda la conversación con soporte.</p></div><a class="rounded-full bg-[#123B4A] px-5 py-3 text-sm font-black text-white" href="{{ route('support.create') }}">Contactar soporte</a></div>
    </main>
<x-bottom-nav active="messages" />
</body>
</html>
