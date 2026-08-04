<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat con {{ $otherUser?->name ?? 'usuario' }} - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#EEF3F1] text-[#17313A] antialiased">
    <div class="mx-auto flex min-h-screen max-w-4xl flex-col bg-white shadow-xl">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-[#123B4A]/10 bg-white/95 px-4 py-3 backdrop-blur-xl">
            <a class="grid size-10 place-items-center rounded-full text-xl font-black hover:bg-[#FAF8F4]" href="{{ route('conversations.index') }}" aria-label="Volver">‹</a>
            <a class="flex min-w-0 flex-1 items-center gap-3" href="{{ $otherUser ? route('profile.show', $otherUser) : '#' }}">
                @if ($otherUser?->avatar_path)<img class="size-11 rounded-full object-cover" src="{{ asset('storage/'.$otherUser->avatar_path) }}" alt="">@else<span class="grid size-11 place-items-center rounded-full bg-[#DCEAE6] font-black">{{ $otherUser ? mb_strtoupper(mb_substr($otherUser->name, 0, 1)) : '?' }}</span>@endif
                <span class="min-w-0"><strong class="block truncate">{{ $otherUser?->name ?? 'Conversación' }}</strong><span class="block text-xs font-bold text-[#22A06B]">Conversación dentro de Plaza Local</span></span>
            </a>
        </header>

        <main class="flex-1 space-y-3 bg-[radial-gradient(circle_at_top,#E8F1EE,transparent_45%)] px-4 py-6 sm:px-8">
            <div class="mx-auto mb-6 max-w-lg rounded-2xl bg-[#FFF4D6] px-4 py-3 text-center text-xs font-bold leading-5 text-[#7C5B1C]">Por seguridad, mantén acuerdos y pagos dentro de la plataforma. No compartas contraseñas ni códigos de verificación.</div>
            @forelse ($messages as $message)
                @php($mine = $message->sender_id === auth()->id())
                <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[82%] rounded-2xl px-4 py-3 shadow-sm {{ $mine ? 'rounded-br-md bg-[#DDF3E8]' : 'rounded-bl-md bg-white' }}">
                        <p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $message->body }}</p>
                        <p class="mt-1 text-right text-[10px] font-bold text-[#7A8C91]">{{ $message->created_at->format('H:i') }}</p>
                    </div>
                </div>
            @empty
                <div class="py-16 text-center text-sm font-bold text-[#6B7D83]">Escribe el primer mensaje para comenzar.</div>
            @endforelse
            <div id="ultimo-mensaje"></div>
        </main>

        <form class="sticky bottom-0 flex items-end gap-3 border-t border-[#123B4A]/10 bg-white p-3 pb-[max(.75rem,env(safe-area-inset-bottom))] sm:p-4" method="POST" action="{{ route('conversations.messages.store', $conversation) }}">
            @csrf
            <label class="min-w-0 flex-1"><span class="sr-only">Mensaje</span><textarea class="max-h-36 min-h-12 w-full resize-none rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm outline-none focus:border-[#F97316]/40 focus:ring-4 focus:ring-[#F97316]/10" name="body" maxlength="2000" required placeholder="Escribe un mensaje…">{{ old('body') }}</textarea>@error('body')<span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror</label>
            <button class="grid size-12 shrink-0 place-items-center rounded-full bg-[#F97316] font-black text-white shadow-lg shadow-[#F97316]/20" type="submit" aria-label="Enviar">➤</button>
        </form>
    </div>
</body>
</html>
