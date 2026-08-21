<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mensajes - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#FAF8F4] pb-24 text-[#17313A] antialiased">
<x-market-nav :back-url="route('dashboard')" />
<main class="mx-auto max-w-4xl px-4 py-7 sm:px-5">
    <div><p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Comunicación protegida</p><h1 class="mt-2 text-3xl font-black">Mensajes</h1><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Tus conversaciones, avisos sociales y actividad operativa viven aquí.</p></div>
    <nav class="mt-5 grid grid-cols-2 rounded-2xl bg-[#E8ECEA] p-1" aria-label="Mensajes y notificaciones"><a class="rounded-xl bg-white px-4 py-3 text-center text-sm font-black shadow-sm" href="{{ route('conversations.index') }}">Conversaciones @if(auth()->user()->unreadConversationsCount())<span class="ml-1 inline-flex min-w-5 justify-center rounded-full bg-red-500 px-1.5 text-xs leading-5 text-white">{{ auth()->user()->unreadConversationsCount() }}</span>@endif</a><a class="rounded-xl px-4 py-3 text-center text-sm font-black text-[#536A72]" href="{{ route('notifications.index') }}">Notificaciones @if(auth()->user()->unreadNotifications()->count())<span class="ml-1 inline-flex min-w-5 justify-center rounded-full bg-[#F97316] px-1.5 text-xs leading-5 text-white">{{ auth()->user()->unreadNotifications()->count() }}</span>@endif</a></nav>
    <section class="mt-5 overflow-hidden rounded-[2rem] border border-[#123B4A]/10 bg-white shadow-sm">
        @forelse($conversations as $conversation)
            @php
                $other=$conversation->participants->firstWhere('id','!=',auth()->id()); $latest=$conversation->messages->first();
                $lastReadAt=$conversation->pivot?->last_read_at ? \Illuminate\Support\Carbon::parse($conversation->pivot->last_read_at) : null;
                $unread=$latest && $latest->sender_id!==auth()->id() && (!$lastReadAt || $latest->created_at->gt($lastReadAt));
                $support=$other?->hasAnyRole(['admin','superadmin']) && !$other?->canUseMarketplace();
            @endphp
            <a class="relative flex items-center gap-4 border-b px-5 py-4 transition last:border-0 {{ $unread ? 'bg-[#FFF8F2]' : 'hover:bg-[#FAF8F4]' }}" href="{{ route('conversations.show',$conversation) }}">
                @if($other?->avatar_path)<img class="size-14 rounded-full object-cover" src="{{ $other->avatarUrl() }}" alt="">@else<span class="grid size-14 shrink-0 place-items-center rounded-full {{ $support ? 'bg-[#123B4A] text-white' : 'bg-[#DCEAE6] text-[#123B4A]' }} text-lg font-black">{{ $other ? mb_strtoupper(mb_substr($other->name,0,1)) : '?' }}</span>@endif
                <span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-3"><strong class="truncate">{{ $support ? 'Soporte de Plaza Local' : ($other?->name ?? 'Conversación') }}</strong>@if($conversation->last_message_at)<time class="shrink-0 text-xs font-bold text-[#8A999E]">{{ $conversation->last_message_at->diffForHumans() }}</time>@endif</span>@if($support)<span class="mt-1 inline-flex rounded-full bg-[#E9F7F0] px-2 py-0.5 text-[10px] font-black text-[#14734A]">CANAL OFICIAL</span>@endif<span class="mt-1 block truncate text-sm {{ $unread ? 'font-black text-[#123B4A]' : 'font-semibold text-[#6B7D83]' }}">{{ $latest?->body ?? 'Inicia la conversación de forma segura.' }}</span></span>
                @if($unread)<span class="relative size-3 shrink-0"><span class="absolute inset-0 animate-ping rounded-full bg-[#F97316] opacity-60"></span><span class="relative block size-3 rounded-full bg-[#F97316]"></span></span>@endif
            </a>
        @empty
            <div class="px-6 py-16 text-center"><span class="mx-auto grid size-16 place-items-center rounded-3xl bg-[#E8F1EE]"><svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-8 8H5l-3 2 1-5a9 9 0 1 1 18-5Z"/></svg></span><h2 class="mt-4 text-xl font-black">Todavía no hay conversaciones</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Contacta desde un perfil o abre un caso con soporte.</p><a class="mt-5 inline-flex rounded-full border px-5 py-3 text-sm font-black" href="{{ route('support.create') }}">Contactar soporte</a></div>
        @endforelse
    </section>
    @if($conversations->hasPages())<div class="mt-6">{{ $conversations->links() }}</div>@endif
</main><x-bottom-nav active="messages" /></body></html>