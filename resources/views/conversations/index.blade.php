<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mensajes - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased">
    <header class="border-b border-[#123B4A]/10 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-5 py-4"><a class="flex items-center gap-3 font-black" href="{{ route('dashboard') }}"><span class="grid size-10 place-items-center rounded-2xl bg-[#123B4A] text-white">P</span> Plaza Local</a><a class="rounded-full border border-[#123B4A]/10 px-4 py-2 text-sm font-black" href="{{ route('dashboard') }}">Inicio</a></div>
    </header>
    <main class="mx-auto max-w-4xl px-5 py-9">
        <p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Conversaciones privadas</p>
        <h1 class="mt-2 text-4xl font-black tracking-tight">Mensajes</h1>
        <p class="mt-3 text-[#6B7D83]">Habla dentro de Plaza Local para conservar el historial y la protección de la plataforma.</p>

        <section class="mt-7 overflow-hidden rounded-[2rem] border border-[#123B4A]/10 bg-white shadow-sm">
            @forelse ($conversations as $conversation)
                @php
                    $other = $conversation->participants->firstWhere('id', '!=', auth()->id());
                    $latest = $conversation->messages->first();
                    $lastReadAt = $conversation->pivot?->last_read_at ? \Illuminate\Support\Carbon::parse($conversation->pivot->last_read_at) : null;
                    $unread = $latest && $latest->sender_id !== auth()->id() && (! $lastReadAt || $latest->created_at->gt($lastReadAt));
                @endphp
                <a class="flex items-center gap-4 border-b border-[#123B4A]/8 px-5 py-4 transition last:border-0 hover:bg-[#FAF8F4]" href="{{ route('conversations.show', $conversation) }}">
                    @if ($other?->avatar_path)<img class="size-14 rounded-full object-cover" src="{{ asset('storage/'.$other->avatar_path) }}" alt="">@else<span class="grid size-14 shrink-0 place-items-center rounded-full bg-[#DCEAE6] text-lg font-black text-[#123B4A]">{{ $other ? mb_strtoupper(mb_substr($other->name, 0, 1)) : '?' }}</span>@endif
                    <div class="min-w-0 flex-1"><div class="flex items-center justify-between gap-3"><h2 class="truncate font-black">{{ $other?->name ?? 'Conversación' }}</h2>@if($conversation->last_message_at)<time class="shrink-0 text-xs font-bold text-[#8A999E]">{{ $conversation->last_message_at->diffForHumans() }}</time>@endif</div><p class="mt-1 truncate text-sm {{ $unread ? 'font-black text-[#123B4A]' : 'font-semibold text-[#6B7D83]' }}">{{ $latest?->body ?? 'Inicia la conversación de forma segura.' }}</p></div>
                    @if ($unread)<span class="size-3 shrink-0 rounded-full bg-[#F97316]" aria-label="Mensaje sin leer"></span>@endif
                </a>
            @empty
                <div class="px-6 py-16 text-center"><span class="mx-auto grid size-16 place-items-center rounded-3xl bg-[#E8F1EE] text-2xl">💬</span><h2 class="mt-4 text-xl font-black">Todavía no hay conversaciones</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Abre el perfil de una persona y presiona “Contactar dentro de Plaza Local”.</p></div>
            @endforelse
        </section>
        @if ($conversations->hasPages())<div class="mt-6">{{ $conversations->links() }}</div>@endif
    </main>
</body>
</html>
