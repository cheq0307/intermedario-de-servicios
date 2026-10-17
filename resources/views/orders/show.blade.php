<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle del trabajo - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen pb-24 bg-brand-surface text-brand-ink antialiased">
    @php
        $isBuyer = $order->buyer_id === auth()->id();
        $statusLabels = ['accepted' => 'Confirmada', 'in_progress' => 'En progreso', 'ready' => 'Lista para entregar', 'delivered' => 'Entregada', 'completed' => 'Completada', 'cancelled' => 'Cancelada', 'disputed' => 'En disputa'];
        $steps = ['accepted' => 'Acuerdo', 'in_progress' => 'En progreso', 'delivered' => 'Entregado', 'completed' => 'Completado'];
        $isProduct = $order->fulfillment_type === 'pickup';
        if ($isProduct) {
            $steps = ['accepted' => 'Confirmado', 'ready' => 'Listo', 'delivered' => 'Entregado', 'completed' => 'Completado'];
        }

        $currentStep = array_search($order->status->value, array_keys($steps), true);
        $ownReview = $order->reviews->firstWhere('author_id', auth()->id());
    @endphp
    <x-market-nav :back-url="route('orders.index')" />
    <main class="mx-auto max-w-5xl px-5 py-9">
        @if (session('status'))<div class="mb-6 rounded-2xl border border-brand-success-bright/20 bg-brand-success-soft px-5 py-4 text-sm font-black text-brand-success">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-6 rounded-2xl bg-red-50 px-5 py-4 text-sm font-black text-red-700">{{ $errors->first() }}</div>@endif

        <section class="rounded-[2rem] bg-brand p-6 text-white sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-5"><div><p class="text-xs font-black uppercase tracking-[.18em] text-brand-peach-muted">Orden {{ substr($order->public_id, 0, 8) }}</p><h1 class="mt-3 text-3xl font-black">{{ $order->jobRequest?->title ?? $order->items->first()?->name_snapshot }}</h1><p class="mt-3 text-white/65">{{ $isBuyer ? 'Proveedor: '.$order->vendor->display_name : 'Cliente: '.$order->buyer->name }}</p></div><div class="text-right"><strong class="block text-2xl">${{ number_format($order->total_amount / 100, 2) }} MXN</strong><span class="mt-2 inline-block rounded-full bg-white/10 px-3 py-2 text-xs font-black">{{ $statusLabels[$order->status->value] ?? ucfirst($order->status->value) }}</span></div></div>
        </section>

        @if (! in_array($order->status->value, ['cancelled', 'disputed'], true))
            <section class="mt-6 grid gap-2 rounded-[1.75rem] border border-brand/10 bg-white p-4 sm:p-6" style="grid-template-columns: repeat({{ count($steps) }}, minmax(0, 1fr))">
                @foreach ($steps as $value => $label)
                    @php($stepIndex = array_search($value, array_keys($steps), true))
                    <div class="text-center"><span class="mx-auto grid size-9 place-items-center rounded-full text-sm font-black {{ $currentStep !== false && $stepIndex <= $currentStep ? 'bg-brand-success text-white' : 'bg-brand-avatar-soft text-brand-muted' }}">{{ $stepIndex + 1 }}</span><span class="mt-2 block text-[11px] font-black sm:text-xs">{{ $label }}</span></div>
                @endforeach
            </section>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
            <section class="rounded-[1.75rem] border border-brand/10 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black">Acuerdo registrado</h2>
                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-black uppercase tracking-[.12em] text-brand-muted">Precio acordado</dt><dd class="mt-1 font-black">${{ number_format($order->subtotal_amount / 100, 2) }} MXN</dd></div>
                    <div><dt class="text-xs font-black uppercase tracking-[.12em] text-brand-muted">Fecha estimada</dt><dd class="mt-1 font-black">{{ $order->due_at?->translatedFormat('j M Y') ?? 'Por confirmar' }}</dd></div>
                    @if (! $isBuyer)<div><dt class="text-xs font-black uppercase tracking-[.12em] text-brand-muted">Importe acordado</dt><dd class="mt-1 font-black text-brand-success">${{ number_format($order->subtotal_amount / 100, 2) }} MXN</dd></div>@endif
                </dl>
                <div class="mt-6 rounded-2xl bg-brand-surface p-4"><p class="text-xs font-black uppercase tracking-[.12em] text-brand-muted">Incluye</p><p class="mt-2 whitespace-pre-line leading-7">{{ $order->jobProposal?->message }}</p></div>
                @if ($order->cancellation_reason)<div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-700"><strong>Motivo de cancelación:</strong> {{ $order->cancellation_reason }}</div>@endif
                @if($order->reviews->isNotEmpty())<div class="mt-6 border-t border-brand/10 pt-6"><h2 class="text-xl font-black">Calificaciones verificadas</h2><div class="mt-4 space-y-3">@foreach($order->reviews as $review)<article class="rounded-2xl bg-brand-surface p-4"><strong>{{ $review->author->name }} · {{ str_repeat('★', $review->rating) }}</strong>@if($review->comment)<p class="mt-2 text-sm leading-6 text-brand-copy">{{ $review->comment }}</p>@endif</article>@endforeach</div></div>@endif
                @if($order->status->value === 'completed' && ! $ownReview)
                    <form class="mt-6 border-t border-brand/10 pt-6" method="POST" action="{{ route('reviews.store', $order) }}">@csrf<h2 class="text-xl font-black">Califica esta experiencia</h2><div class="mt-4 grid gap-3 sm:grid-cols-[160px_1fr]"><select class="rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="rating" required><option value="5">5 - Excelente</option><option value="4">4 - Muy buena</option><option value="3">3 - Regular</option><option value="2">2 - Mala</option><option value="1">1 - Muy mala</option></select><textarea class="min-h-24 rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="comment" maxlength="1500" placeholder="Comentario opcional"></textarea></div><button class="mt-3 rounded-full bg-brand px-5 py-3 text-sm font-black text-white" type="submit">Publicar calificación</button></form>
                @elseif($ownReview)
                    <details class="mt-6 rounded-2xl bg-brand-success-soft p-4"><summary class="cursor-pointer text-sm font-black text-brand-success">Ya calificaste · Editar reseña</summary><form class="mt-4 grid gap-3" method="POST" action="{{ route('reviews.update', $ownReview) }}">@csrf @method('PATCH')<select class="rounded-xl border bg-white px-3 py-2" name="rating">@foreach(range(5,1) as $score)<option value="{{ $score }}" @selected($ownReview->rating === $score)>{{ $score }} estrellas</option>@endforeach</select><textarea class="rounded-xl border bg-white px-3 py-2" name="comment" maxlength="1500">{{ $ownReview->comment }}</textarea><button class="rounded-full bg-brand px-4 py-2 text-sm font-black text-white">Guardar cambios</button></form><p class="mt-3 text-xs font-bold text-brand-copy">Solo quienes participaron en una contratación completada pueden calificar. La reseña puede actualizarse después.</p></details>
                @endif
            </section>

            <aside class="space-y-4">
                <section class="rounded-[1.75rem] border border-brand/10 bg-white p-5 shadow-sm"><h2 class="font-black">Siguiente acción</h2>
                    <div class="mt-4 space-y-3">
                        @if ($isProduct && ! $isBuyer && $order->status->value === 'accepted')<form method="POST" action="{{ route('products.orders.ready', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-brand-orange px-5 py-3 font-black text-white" type="submit">Marcar pedido listo</button></form>
                        @elseif ($isProduct && ! $isBuyer && $order->status->value === 'ready')<form method="POST" action="{{ route('products.orders.deliver', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-brand-orange px-5 py-3 font-black text-white" type="submit">Registrar entrega</button></form>
                        @elseif (! $isProduct && ! $isBuyer && $order->status->value === 'accepted')<form method="POST" action="{{ route('orders.start', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-brand-orange px-5 py-3 font-black text-white" type="submit">Iniciar trabajo</button></form>
                        @elseif (! $isBuyer && $order->status->value === 'in_progress')<form method="POST" action="{{ route('orders.deliver', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-brand-orange px-5 py-3 font-black text-white" type="submit">Marcar como entregado</button></form>
                        @elseif ($isBuyer && $order->status->value === 'delivered')<form method="POST" action="{{ route('orders.complete', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-brand-success px-5 py-3 font-black text-white" type="submit">Confirmar entrega</button></form>
                        @else<p class="text-sm font-bold leading-6 text-brand-muted">No tienes una acción pendiente en este momento.</p>@endif
                        @if ($conversation)<a class="block w-full rounded-full border border-brand/10 px-5 py-3 text-center text-sm font-black" href="{{ route('conversations.show', $conversation) }}">{{ $conversation->state === 'archived' ? 'Ver historial de operación' : 'Abrir chat de operación' }}</a>@endif
                    </div>
                </section>

                @if (! $isProduct && $order->status->value === 'accepted')
                    <section class="rounded-[1.75rem] border border-red-100 bg-white p-5"><h2 class="font-black">Cancelar antes de iniciar</h2><p class="mt-2 text-xs font-bold leading-5 text-brand-muted">El motivo quedará registrado para ambas partes.</p><form class="mt-4 space-y-3" method="POST" action="{{ route('orders.cancel', $order) }}">@csrf @method('PATCH')<textarea class="min-h-24 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Explica el motivo"></textarea><button class="w-full rounded-full border border-red-200 px-5 py-2.5 text-sm font-black text-red-700" type="submit">Cancelar contratación</button></form></section>
                @endif
                @if ($isProduct && $isBuyer && $order->status->value === 'accepted')<section class="rounded-[1.75rem] border border-red-100 bg-white p-5"><h2 class="font-black">Cancelar pedido</h2><p class="mt-2 text-xs font-bold leading-5 text-brand-muted">Se devolverán inmediatamente las piezas reservadas al inventario.</p><form class="mt-4" method="POST" action="{{ route('products.orders.cancel', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-full border border-red-200 px-5 py-2.5 text-sm font-black text-red-700" type="submit">Cancelar pedido</button></form></section>@endif
                @if($order->dispute)
                    <a class="block rounded-[1.75rem] bg-brand-orange-soft p-5 font-black text-brand-danger-warm" href="{{ route('disputes.show', $order->dispute) }}">Ver expediente de disputa →</a>
                @elseif(in_array($order->status->value, ['paid', 'in_progress', 'ready', 'delivered'], true))
                    <section class="rounded-[1.75rem] border border-red-100 bg-white p-5"><h2 class="font-black">Reportar un problema</h2><p class="mt-2 text-xs font-bold leading-5 text-brand-muted">Se pausará el flujo hasta una resolución administrativa.</p><form class="mt-4 space-y-3" method="POST" action="{{ route('disputes.store', $order) }}">@csrf<select class="w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3 text-sm" name="reason" required><option value="not_delivered">No entregó</option><option value="different_work">Trabajo diferente</option><option value="price_problem">Problema con el precio</option><option value="poor_service">Mal servicio</option><option value="no_show">No se presentó</option><option value="other">Otro</option></select><textarea class="min-h-28 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3 text-sm" name="description" minlength="30" maxlength="3000" required placeholder="Describe con detalle qué ocurrió"></textarea><button class="w-full rounded-full border border-red-200 px-5 py-2.5 text-sm font-black text-red-700" type="submit">Abrir disputa</button></form></section>
                @endif
            </aside>
        </div>
    </main>
<x-bottom-nav active="orders" />
</body>
</html>
