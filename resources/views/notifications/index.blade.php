<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notificaciones - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased">
    <header class="border-b border-[#123B4A]/10 bg-white"><div class="mx-auto flex max-w-4xl items-center justify-between px-5 py-4"><a class="font-black" href="{{ route('dashboard') }}">Plaza Local</a><a class="rounded-full border border-[#123B4A]/10 px-4 py-2 text-sm font-black" href="{{ route('dashboard') }}">Volver</a></div></header>
    <main class="mx-auto max-w-4xl px-5 py-9">
        <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Actividad importante</p><h1 class="mt-2 text-3xl font-black">Notificaciones</h1></div>@if(auth()->user()->unreadNotifications()->exists())<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="rounded-full border border-[#123B4A]/10 bg-white px-5 py-2.5 text-sm font-black" type="submit">Marcar todas como leídas</button></form>@endif</div>
        @if(session('status'))<p class="mt-5 rounded-2xl bg-[#E9F7F0] p-4 text-sm font-black text-[#14734A]">{{ session('status') }}</p>@endif
        <div class="mt-7 space-y-3">
            @forelse($notifications as $notification)
                <form method="POST" action="{{ route('notifications.open', $notification->id) }}">@csrf @method('PATCH')<button class="flex w-full items-start gap-4 rounded-[1.5rem] border p-5 text-left shadow-sm transition hover:-translate-y-0.5 {{ $notification->read_at ? 'border-[#123B4A]/8 bg-white/70' : 'border-[#F97316]/20 bg-white' }}" type="submit"><span class="mt-1 grid size-10 shrink-0 place-items-center rounded-full {{ $notification->read_at ? 'bg-[#E8F1EE]' : 'bg-[#FFF1E8] text-[#D85B0B]' }}">{{ $notification->read_at ? '✓' : '•' }}</span><span class="min-w-0 flex-1"><strong class="block">{{ $notification->data['title'] ?? 'Actividad nueva' }}</strong><span class="mt-1 block text-sm font-semibold leading-6 text-[#6B7D83]">{{ $notification->data['body'] ?? '' }}</span><time class="mt-2 block text-xs font-bold text-[#8A999E]">{{ $notification->created_at->diffForHumans() }}</time></span><span class="mt-2 text-[#F97316]">→</span></button></form>
            @empty
                <div class="rounded-[1.75rem] border border-dashed border-[#123B4A]/20 bg-white/60 p-12 text-center"><h2 class="text-xl font-black">Todo tranquilo por aquí</h2><p class="mt-2 text-sm font-bold text-[#6B7D83]">Los cambios importantes de propuestas, pedidos y disputas aparecerán aquí.</p></div>
            @endforelse
        </div>
        @if($notifications->hasPages())<div class="mt-6">{{ $notifications->links() }}</div>@endif
    </main>
</body>
</html>
