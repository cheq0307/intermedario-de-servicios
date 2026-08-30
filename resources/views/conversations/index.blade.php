<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mensajes - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-brand-surface pb-24 text-brand-ink antialiased">
<x-market-nav :back-url="route('dashboard')" />
<main class="mx-auto max-w-4xl px-4 py-7 sm:px-5">
    <div><p class="text-xs font-black uppercase tracking-[.18em] text-brand-orange">Comunicación protegida</p><h1 class="mt-2 text-3xl font-black">Conversaciones</h1><p class="mt-2 text-sm font-semibold text-brand-muted">Cada publicación, operación y conversación directa conserva su propio contexto.</p></div>
    <section class="mt-5 overflow-hidden rounded-[2rem] border border-brand/10 bg-white shadow-sm">
        @forelse($conversations as $conversation)
            @php
                $other=$conversation->participants->firstWhere('id','!=',auth()->id());
                $latest=$conversation->messages->first();
                $lastReadAt=$conversation->pivot?->last_read_at ? \Illuminate\Support\Carbon::parse($conversation->pivot->last_read_at) : null;
                $unread=$latest && $latest->sender_id!==auth()->id() && (!$lastReadAt || $latest->created_at->gt($lastReadAt));
                $support=$other?->hasAnyRole(['admin','superadmin']) && !$other?->canUseMarketplace();
                $negotiationTitle=$conversation->post?->listing?->name ?? $conversation->post?->jobRequest?->title ?? null;
                $operationTitle=$conversation->order?->jobRequest?->title ?? $conversation->order?->items?->first()?->name_snapshot;
                $contextTitle=$conversation->isNegotiation() ? $negotiationTitle : ($conversation->isOperation() ? $operationTitle : null);
            @endphp
            <a class="relative flex items-center gap-4 border-b px-5 py-4 transition last:border-0 {{ $unread ? 'bg-brand-coral-faint' : 'hover:bg-brand-surface' }}" href="{{ route('conversations.show',$conversation) }}">
                @if($other?->avatar_path)<img class="size-14 rounded-full object-cover" src="{{ $other->avatarUrl() }}" alt="">@else<span class="grid size-14 shrink-0 place-items-center rounded-full {{ $support ? 'bg-brand text-white' : 'bg-brand-avatar-strong text-brand' }} text-lg font-black">{{ $other ? mb_strtoupper(mb_substr($other->name,0,1)) : '?' }}</span>@endif
                <span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-3"><strong class="truncate">{{ $support ? 'Soporte de Plaza Local' : ($other?->name ?? 'Conversación') }}</strong>@if($conversation->last_message_at)<time class="shrink-0 text-xs font-bold text-brand-caption">{{ $conversation->last_message_at->diffForHumans() }}</time>@endif</span><span class="mt-1 flex min-w-0 items-center gap-2">@if($support)<span class="inline-flex rounded-full bg-brand-success-soft px-2 py-0.5 text-[10px] font-black text-brand-success">CANAL OFICIAL</span>@elseif($conversation->isNegotiation())<span class="inline-flex shrink-0 rounded-full bg-brand-orange-soft px-2 py-0.5 text-[10px] font-black text-brand-orange-burnt">NEGOCIACIÓN</span>@elseif($conversation->isOperation())<span class="inline-flex shrink-0 rounded-full bg-brand-success-soft px-2 py-0.5 text-[10px] font-black text-brand-success">OPERACIÓN</span>@endif @if($contextTitle)<span class="truncate text-xs font-black text-brand-copy">{{ $contextTitle }}</span>@endif</span><span class="mt-1 block truncate text-sm {{ $unread ? 'font-black text-brand' : 'font-semibold text-brand-muted' }}">{{ $latest?->body ?? 'Inicia la conversación de forma segura.' }}</span></span>
                @if($unread)<span class="relative size-3 shrink-0"><span class="absolute inset-0 animate-ping rounded-full bg-brand-orange opacity-60"></span><span class="relative block size-3 rounded-full bg-brand-orange"></span></span>@endif
            </a>
        @empty
            <div class="px-6 py-16 text-center"><span class="mx-auto grid size-16 place-items-center rounded-3xl bg-brand-avatar-soft"><svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-8 8H5l-3 2 1-5a9 9 0 1 1 18-5Z"/></svg></span><h2 class="mt-4 text-xl font-black">Todavía no hay conversaciones</h2><p class="mt-2 text-sm font-semibold text-brand-muted">Abre el chat de una publicación o contacta con soporte.</p><a class="mt-5 inline-flex rounded-full border px-5 py-3 text-sm font-black" href="{{ route('support.create') }}">Contactar soporte</a></div>
        @endforelse
    </section>
    @if($conversations->hasPages())<div class="mt-6">{{ $conversations->links() }}</div>@endif
</main><x-bottom-nav active="messages" /></body></html>
