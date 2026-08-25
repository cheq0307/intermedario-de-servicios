<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Conversación - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#EEF3F1] pb-20 text-[#17313A] antialiased">
@php
    $negotiationTitle=$conversation->post?->listing?->name ?? $conversation->post?->jobRequest?->title ?? ($conversation->post ? \Illuminate\Support\Str::limit($conversation->post->body,60) : null);
    $negotiationActive=$conversation->isNegotiation() && $conversation->acceptsMessages();
    $extensionAvailable=$negotiationActive && $conversation->extension_count < config('marketplace.negotiation_max_extensions',1)
        && $conversation->expires_at?->lte(now()->addDays(config('marketplace.negotiation_extension_window_days',2)));
    $extensionPending=$negotiationActive && $conversation->extension_count < config('marketplace.negotiation_max_extensions',1) && ! $extensionAvailable;
@endphp
<div class="mx-auto flex min-h-screen max-w-4xl flex-col bg-white shadow-xl">
<header class="sticky top-0 z-20 flex items-center gap-3 border-b border-[#123B4A]/10 bg-white/95 px-4 py-3 backdrop-blur-xl">
<a class="grid size-10 place-items-center rounded-full hover:bg-[#FAF8F4]" href="{{ route('conversations.index') }}" aria-label="Volver"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
<a class="flex min-w-0 flex-1 items-center gap-3" href="{{ $otherUser ? route('profile.show',$otherUser) : '#' }}">@if($otherUser?->avatar_path)<img class="size-11 rounded-full object-cover" src="{{ $otherUser->avatarUrl() }}" alt="">@else<span class="grid size-11 place-items-center rounded-full {{ $supportConversation ? 'bg-[#123B4A] text-white' : 'bg-[#DCEAE6]' }} font-black">{{ $otherUser ? mb_strtoupper(mb_substr($otherUser->name,0,1)) : '?' }}</span>@endif<span class="min-w-0"><strong class="block truncate">{{ $supportConversation ? 'Soporte de Plaza Local' : ($operationOrder ? 'Chat de operación' : ($otherUser?->name ?? 'Conversación')) }}</strong><span class="block truncate text-xs font-bold {{ $supportConversation ? 'text-[#14734A]' : 'text-[#6B7D83]' }}">{{ $supportConversation ? 'Canal oficial de atención' : ($operationOrder ? 'Acuerdo protegido dentro de Plaza Local' : ($conversation->isNegotiation() ? 'Negociación: '.$negotiationTitle : 'Conversación directa')) }}</span></span></a>
</header>
<main class="flex-1 space-y-3 bg-[radial-gradient(circle_at_top,#E8F1EE,transparent_45%)] px-4 py-6 sm:px-8">
@if(session('status'))<div class="mx-auto mb-4 max-w-lg rounded-2xl bg-[#E9F7F0] px-4 py-3 text-center text-sm font-black text-[#14734A]">{{ session('status') }}</div>@endif
@if($operationOrder)
@php($operationTitle=$operationOrder->jobRequest?->title ?? $operationOrder->items->first()?->name_snapshot ?? 'Operación')
<div class="mx-auto mb-5 max-w-lg rounded-2xl border border-[#123B4A]/10 bg-white px-4 py-3 shadow-sm"><p class="text-[10px] font-black uppercase tracking-[.16em] text-[#F97316]">Operación registrada</p><div class="mt-1 flex items-center justify-between gap-3"><strong class="truncate text-sm">{{ $operationTitle }}</strong><a class="shrink-0 text-xs font-black text-[#14734A]" href="{{ route('orders.show', $operationOrder) }}">Ver pedido</a></div></div>
@elseif($conversation->isNegotiation())
<div class="mx-auto mb-5 max-w-lg rounded-2xl border border-[#F97316]/20 bg-white px-4 py-4 shadow-sm">
    <p class="text-[10px] font-black uppercase tracking-[.16em] text-[#F97316]">Conversación de publicación</p><strong class="mt-1 block text-sm">{{ $negotiationTitle }}</strong>
    @if($conversation->agreementOrder)<a class="mt-3 inline-flex text-xs font-black text-[#14734A]" href="{{ route('orders.show',$conversation->agreementOrder) }}">Continuar en el chat del pedido →</a>@endif
    @if($negotiationActive)
        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-bold text-[#6B7D83]"><span>Disponible hasta {{ $conversation->expires_at?->format('d/m/Y H:i') }}</span>
        @if($extensionAvailable)<form method="POST" action="{{ route('conversations.extend',$conversation) }}">@csrf @method('PATCH')<button class="rounded-full border border-[#123B4A]/15 px-3 py-2 font-black text-[#123B4A]" type="submit">Extender 7 días</button></form>@endif
        @if($extensionPending)<span class="rounded-full bg-[#FAF8F4] px-3 py-2 text-[.7rem]">La extensión se habilitará 48 horas antes del vencimiento.</span>@endif
        <form method="POST" action="{{ route('conversations.close',$conversation) }}" onsubmit="return confirm('¿Terminar esta conversación sin registrar un acuerdo?')">@csrf @method('PATCH')<button class="rounded-full border border-red-200 px-3 py-2 font-black text-red-600" type="submit">Terminar chat</button></form></div>
    @endif
</div>
@endif
<div class="mx-auto mb-6 max-w-lg rounded-2xl px-4 py-3 text-center text-xs font-bold leading-5 {{ $conversation->state === 'under_review' ? 'bg-[#FFF1E8] text-[#B45309]' : ($conversation->state === 'archived' ? 'bg-[#E8ECEA] text-[#536A72]' : 'bg-[#FFF4D6] text-[#7C5B1C]') }}">
@if($conversation->state === 'under_review') El chat está pausado mientras se revisa la disputa. Usa el expediente para aportar información. @elseif($conversation->state === 'archived' && $conversation->isNegotiation()) Esta negociación terminó y es de solo lectura. El sistema conservará temporalmente el historial como respaldo. @elseif($conversation->state === 'archived') Esta operación finalizó; este historial permanece como respaldo y ya no admite mensajes. @elseif($supportConversation) Este chat corresponde al equipo oficial. Nunca te pediremos contraseñas ni códigos. @elseif($conversation->isNegotiation()) Este chat corresponde solamente a esta publicación. Si llegan a un acuerdo, regístrenlo en Plaza Local para continuar en un chat de operación separado. @else Mantén acuerdos y pagos dentro de la plataforma para conservar respaldo. @endif
</div>
@forelse($messages as $message)
@php($mine=$message->sender_id===auth()->id())
<div class="flex {{ $mine?'justify-end':'justify-start' }}"><div class="max-w-[82%] rounded-2xl px-4 py-3 shadow-sm {{ $message->type === 'system' ? 'border border-[#123B4A]/10 bg-[#F7F9F8] text-[#536A72]' : ($mine?'rounded-br-md bg-[#DDF3E8]':'rounded-bl-md bg-white') }}"><p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $message->body }}</p><p class="mt-1 flex items-center justify-end gap-1 text-[10px] font-bold text-[#7A8C91]"><span>{{ $message->created_at->format('H:i') }}</span>@if($mine && $loop->last && $message->type !== 'system')<span>{{ $otherLastReadAt && $otherLastReadAt->gte($message->created_at) ? 'Visto' : 'Enviado' }}</span>@endif</p></div></div>
@empty<div class="py-16 text-center text-sm font-bold text-[#6B7D83]">Escribe el primer mensaje para comenzar.</div>@endforelse
<div id="ultimo-mensaje"></div></main>
@if($conversation->acceptsMessages())
<form class="sticky bottom-0 flex items-end gap-3 border-t border-[#123B4A]/10 bg-white p-3 pb-[max(.75rem,env(safe-area-inset-bottom))] sm:p-4" method="POST" action="{{ route('conversations.messages.store',$conversation) }}">@csrf<label class="min-w-0 flex-1"><span class="sr-only">Mensaje</span><textarea class="max-h-36 min-h-12 w-full resize-none rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm outline-none focus:border-[#F97316]/40 focus:ring-4 focus:ring-[#F97316]/10" name="body" maxlength="2000" required placeholder="Escribe un mensaje…">{{ old('body') }}</textarea>@error('body')<span class="mt-1 block text-xs font-bold text-red-600">{{ $message }}</span>@enderror</label><button class="grid size-12 shrink-0 place-items-center rounded-full bg-[#F97316] text-white shadow-lg" type="submit" aria-label="Enviar"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4zM22 2 11 13"/></svg></button></form>
@endif
</div><x-bottom-nav active="messages" /></body></html>
