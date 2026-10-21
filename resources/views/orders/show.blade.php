<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tracking->pageTitle }} - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-surface pb-24 text-brand-ink antialiased">
    <x-market-nav :back-url="route('orders.index')" />

    <main class="mx-auto max-w-3xl px-4 py-6 sm:px-6 sm:py-9">
        @if (session('status'))
            <div class="mb-5 rounded-2xl border border-brand-success-bright/20 bg-brand-success-soft px-4 py-3 text-sm font-bold text-brand-success" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700" role="alert">{{ $errors->first() }}</div>
        @endif

        <header class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Seguimiento</p>
                <h1 class="mt-1 text-2xl font-black sm:text-3xl">{{ $tracking->pageTitle }}</h1>
            </div>
            <span class="rounded-full border border-brand/10 bg-white px-3 py-2 text-xs font-black">{{ $tracking->statusLabel }}</span>
        </header>

        <section class="mt-5 rounded-3xl border border-brand/10 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex items-center gap-3">
                <div class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-avatar-soft text-lg font-black text-brand">{{ mb_substr($tracking->counterpartyName, 0, 1) }}</div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-brand-muted">{{ $tracking->counterpartyLabel }}</p>
                    <h2 class="truncate font-black">{{ $tracking->counterpartyName }}</h2>
                    <p class="mt-1 truncate text-sm text-brand-copy">{{ $tracking->itemTitle }}</p>
                </div>
            </div>
        </section>

        @if (! in_array($order->status->value, ['cancelled', 'disputed', 'refunded'], true))
            <section class="mt-5 rounded-3xl border border-brand/10 bg-white px-5 py-6 shadow-sm" aria-labelledby="tracking-title">
                <h2 id="tracking-title" class="sr-only">Progreso de la operación</h2>
                <ol class="space-y-0">
                    @foreach ($tracking->steps as $step)
                        <li class="relative flex min-h-20 gap-4 pb-5 last:min-h-0 last:pb-0">
                            @unless ($loop->last)
                                <span class="absolute left-[15px] top-8 h-[calc(100%-1rem)] w-0.5 {{ $step['state'] === 'complete' ? 'bg-brand-success' : 'bg-brand/10' }}" aria-hidden="true"></span>
                            @endunless
                            <span class="relative z-10 mt-0.5 grid size-8 shrink-0 place-items-center rounded-full border-2 text-sm font-black {{ $step['state'] === 'complete' ? 'border-brand-success bg-brand-success text-white' : ($step['state'] === 'current' ? 'border-brand-orange bg-brand-orange text-white' : 'border-brand/10 bg-white text-brand-muted') }}">
                                {{ $step['state'] === 'complete' ? '✓' : ($step['state'] === 'current' ? '•' : '') }}
                            </span>
                            <div>
                                <p class="font-black {{ $step['state'] === 'pending' ? 'text-brand-muted' : '' }}">{{ $step['label'] }}</p>
                                @if ($step['date'])<time class="mt-1 block text-xs text-brand-copy">{{ $step['date'] }}</time>@endif
                                @if (! $tracking->isProduct && $loop->index === 1 && $step['state'] !== 'pending')<p class="mt-1 text-xs text-brand-copy">{{ $order->vendor->display_name }} aceptó atenderte.</p>@endif
                                @if ($loop->index === 2 && $step['state'] === 'current' && $order->due_at)<p class="mt-1 text-xs text-brand-copy">Fecha estimada: {{ $order->due_at->translatedFormat('j M Y') }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        @if ($order->cancellation_reason)
            <section class="mt-5 rounded-3xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
                <h2 class="font-black">Operación cancelada</h2>
                <p class="mt-2 leading-6">{{ $order->cancellation_reason }}</p>
            </section>
        @endif

        <section class="mt-5 rounded-3xl border border-brand/10 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div><p class="text-xs font-black uppercase tracking-[.14em] text-brand-muted">Acuerdo</p><h2 class="mt-1 text-lg font-black">Detalles confirmados</h2></div>
                <strong class="whitespace-nowrap text-brand-success">${{ number_format($order->subtotal_amount / 100, 2) }} MXN</strong>
            </div>
            @if ($order->jobProposal?->message)<p class="mt-4 whitespace-pre-line border-t border-brand/10 pt-4 text-sm leading-6 text-brand-copy">{{ $order->jobProposal->message }}</p>@endif
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-brand-surface px-4 py-3"><dt class="text-xs font-bold text-brand-muted">Fecha estimada</dt><dd class="mt-1 font-black">{{ $order->due_at?->translatedFormat('j M Y') ?? 'Por confirmar' }}</dd></div>
                <div class="rounded-2xl bg-brand-surface px-4 py-3"><dt class="text-xs font-bold text-brand-muted">Referencia</dt><dd class="mt-1 font-black">{{ substr($order->public_id, 0, 8) }}</dd></div>
            </dl>
        </section>

        @if ($tracking->action || $conversation)
            <section class="mt-5 grid gap-3 sm:grid-cols-2">
                @if ($tracking->action)
                    <form method="POST" action="{{ route($tracking->actionRouteName, $order) }}">@csrf @method('PATCH')<button class="min-h-12 w-full rounded-full bg-brand-orange px-5 py-3 text-sm font-black text-white shadow-sm" type="submit">{{ $tracking->actionLabel }}</button></form>
                @endif
                @if ($conversation)<a class="grid min-h-12 place-items-center rounded-full border border-brand px-5 py-3 text-center text-sm font-black text-brand" href="{{ route('conversations.show', $conversation) }}">Ver conversación</a>@endif
            </section>
        @endif

        @if ($order->status->value === 'completed' && ! $tracking->ownReview)
            <section class="mt-5 rounded-3xl border border-brand/10 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.14em] text-brand-orange">Experiencia</p>
                <h2 class="mt-1 text-xl font-black">¿Cómo fue tu experiencia?</h2>
                <form class="mt-5" method="POST" action="{{ route('reviews.store', $order) }}">
                    @csrf
                    <fieldset><legend class="sr-only">Calificación de una a cinco estrellas</legend><div class="grid grid-cols-5 gap-2">
                        @foreach (range(1, 5) as $score)
                            <label class="cursor-pointer text-center"><input class="peer sr-only" type="radio" name="rating" value="{{ $score }}" required @checked($score === 5)><span class="grid min-h-12 place-items-center rounded-2xl border border-brand/10 text-xl text-brand-muted peer-checked:border-brand-orange peer-checked:bg-brand-orange-soft peer-checked:text-brand-orange">★</span><span class="mt-1 block text-[10px] font-bold text-brand-muted">{{ $score }}</span></label>
                        @endforeach
                    </div></fieldset>
                    <label class="mt-4 block text-sm font-black" for="review-comment">Cuéntanos sobre el servicio <span class="font-normal text-brand-muted">(opcional)</span></label>
                    <textarea id="review-comment" class="mt-2 min-h-24 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="comment" maxlength="1500" placeholder="¿Qué salió bien? ¿Qué podría mejorar?"></textarea>
                    <button class="mt-3 min-h-12 w-full rounded-full bg-brand px-5 py-3 text-sm font-black text-white" type="submit">Publicar calificación</button>
                </form>
            </section>
        @elseif ($tracking->ownReview)
            <details class="mt-5 rounded-3xl border border-brand-success-bright/20 bg-brand-success-soft p-5">
                <summary class="cursor-pointer font-black text-brand-success">Tu calificación: {{ $tracking->ownReview->rating }} de 5 · Editar</summary>
                <form class="mt-4 grid gap-3" method="POST" action="{{ route('reviews.update', $tracking->ownReview) }}">@csrf @method('PATCH')<select class="min-h-11 rounded-xl border border-brand/10 bg-white px-3 py-2" name="rating">@foreach(range(5, 1) as $score)<option value="{{ $score }}" @selected($tracking->ownReview->rating === $score)>{{ $score }} estrellas</option>@endforeach</select><textarea class="min-h-24 rounded-xl border border-brand/10 bg-white px-3 py-2" name="comment" maxlength="1500">{{ $tracking->ownReview->comment }}</textarea><button class="min-h-11 rounded-full bg-brand px-4 py-2 text-sm font-black text-white">Guardar cambios</button></form>
            </details>
        @endif

        @if ($order->reviews->isNotEmpty())
            <section class="mt-5 rounded-3xl border border-brand/10 bg-white p-5"><h2 class="font-black">Calificaciones de esta operación</h2><div class="mt-4 space-y-3">@foreach($order->reviews as $review)<article class="rounded-2xl bg-brand-surface p-4"><strong>{{ $review->author->name }} · {{ str_repeat('★', $review->rating) }}</strong>@if($review->comment)<p class="mt-2 text-sm leading-6 text-brand-copy">{{ $review->comment }}</p>@endif</article>@endforeach</div></section>
        @endif

        @if ($tracking->canCancel || $tracking->canDispute || $order->dispute)
            <details class="mt-5 rounded-3xl border border-brand/10 bg-white p-5">
                <summary class="cursor-pointer text-sm font-black">¿Necesitas ayuda con esta operación?</summary>
                <div class="mt-4 border-t border-brand/10 pt-4">
                    @if ($order->dispute)
                        <a class="font-black text-brand-danger-warm" href="{{ route('disputes.show', $order->dispute) }}">Ver expediente de disputa →</a>
                    @elseif ($tracking->canDispute)
                        <form class="space-y-3" method="POST" action="{{ route('disputes.store', $order) }}">@csrf<label class="block text-sm font-black" for="dispute-reason">Tipo de problema</label><select id="dispute-reason" class="min-h-11 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4" name="reason" required><option value="not_delivered">No entregó</option><option value="different_work">Trabajo diferente</option><option value="price_problem">Problema con el precio acordado</option><option value="poor_service">Mal servicio</option><option value="no_show">No se presentó</option><option value="other">Otro</option></select><textarea class="min-h-28 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3 text-sm" name="description" minlength="30" maxlength="3000" required placeholder="Describe con detalle qué ocurrió"></textarea><button class="min-h-11 w-full rounded-full border border-red-200 px-5 py-2 text-sm font-black text-red-700" type="submit">Solicitar revisión</button></form>
                    @endif
                    @if ($tracking->canCancel)
                        @if ($tracking->isProduct && $tracking->isBuyer)
                            <form class="mt-4" method="POST" action="{{ route('products.orders.cancel', $order) }}">@csrf @method('PATCH')<button class="min-h-11 w-full rounded-full border border-red-200 px-5 py-2 text-sm font-black text-red-700" type="submit">Cancelar pedido</button></form>
                        @elseif (! $tracking->isProduct)
                            <form class="mt-4 space-y-3" method="POST" action="{{ route('orders.cancel', $order) }}">@csrf @method('PATCH')<textarea class="min-h-24 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Explica por qué necesitas cancelar"></textarea><button class="min-h-11 w-full rounded-full border border-red-200 px-5 py-2 text-sm font-black text-red-700" type="submit">Cancelar contratación</button></form>
                        @endif
                    @endif
                </div>
            </details>
        @endif
    </main>

    <x-bottom-nav active="orders" />
</body>
</html>
