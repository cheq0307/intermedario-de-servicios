<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle del trabajo - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased">
    @php
        $isBuyer = $order->buyer_id === auth()->id();
        $statusLabels = ['accepted' => 'Aceptada', 'in_progress' => 'En progreso', 'delivered' => 'Entregada', 'completed' => 'Completada', 'cancelled' => 'Cancelada', 'disputed' => 'En disputa'];
        $steps = ['accepted' => 'Acuerdo', 'in_progress' => 'En progreso', 'delivered' => 'Entregado', 'completed' => 'Completado'];
        $currentStep = array_search($order->status->value, array_keys($steps), true);
    @endphp
    <header class="border-b border-[#123B4A]/10 bg-white"><div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-4"><a class="font-black" href="{{ route('dashboard') }}">Plaza Local</a><a class="rounded-full border border-[#123B4A]/10 px-4 py-2 text-sm font-black" href="{{ route('orders.index') }}">Mis trabajos</a></div></header>
    <main class="mx-auto max-w-5xl px-5 py-9">
        @if (session('status'))<div class="mb-6 rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="mb-6 rounded-2xl bg-red-50 px-5 py-4 text-sm font-black text-red-700">{{ $errors->first() }}</div>@endif

        <section class="rounded-[2rem] bg-[#123B4A] p-6 text-white sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-5"><div><p class="text-xs font-black uppercase tracking-[.18em] text-[#F9B36B]">Orden {{ substr($order->public_id, 0, 8) }}</p><h1 class="mt-3 text-3xl font-black">{{ $order->jobRequest?->title ?? $order->items->first()?->name_snapshot }}</h1><p class="mt-3 text-white/65">{{ $isBuyer ? 'Proveedor: '.$order->vendor->display_name : 'Cliente: '.$order->buyer->name }}</p></div><div class="text-right"><strong class="block text-2xl">${{ number_format($order->total_amount / 100, 2) }} MXN</strong><span class="mt-2 inline-block rounded-full bg-white/10 px-3 py-2 text-xs font-black">{{ $statusLabels[$order->status->value] ?? ucfirst($order->status->value) }}</span></div></div>
        </section>

        @if (! in_array($order->status->value, ['cancelled', 'disputed'], true))
            <section class="mt-6 grid grid-cols-4 gap-2 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-4 sm:p-6">
                @foreach ($steps as $value => $label)
                    @php($stepIndex = array_search($value, array_keys($steps), true))
                    <div class="text-center"><span class="mx-auto grid size-9 place-items-center rounded-full text-sm font-black {{ $currentStep !== false && $stepIndex <= $currentStep ? 'bg-[#14734A] text-white' : 'bg-[#E8F1EE] text-[#6B7D83]' }}">{{ $stepIndex + 1 }}</span><span class="mt-2 block text-[11px] font-black sm:text-xs">{{ $label }}</span></div>
                @endforeach
            </section>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
            <section class="rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black">Acuerdo registrado</h2>
                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-black uppercase tracking-[.12em] text-[#6B7D83]">Precio acordado</dt><dd class="mt-1 font-black">${{ number_format($order->subtotal_amount / 100, 2) }} MXN</dd></div>
                    <div><dt class="text-xs font-black uppercase tracking-[.12em] text-[#6B7D83]">Fecha estimada</dt><dd class="mt-1 font-black">{{ $order->due_at?->translatedFormat('j M Y') ?? 'Por confirmar' }}</dd></div>
                    @if (! $isBuyer)<div><dt class="text-xs font-black uppercase tracking-[.12em] text-[#6B7D83]">Comisión Plaza Local</dt><dd class="mt-1 font-black">${{ number_format($order->commission_amount / 100, 2) }} MXN</dd></div><div><dt class="text-xs font-black uppercase tracking-[.12em] text-[#6B7D83]">Ingreso estimado</dt><dd class="mt-1 font-black text-[#14734A]">${{ number_format(($order->subtotal_amount - $order->commission_amount) / 100, 2) }} MXN</dd></div>@endif
                </dl>
                <div class="mt-6 rounded-2xl bg-[#FAF8F4] p-4"><p class="text-xs font-black uppercase tracking-[.12em] text-[#6B7D83]">Incluye</p><p class="mt-2 whitespace-pre-line leading-7">{{ $order->jobProposal?->message }}</p></div>
                <div class="mt-5 rounded-2xl border border-[#F5D48D] bg-[#FFF8E6] p-4 text-sm font-bold leading-6 text-[#79551E]">El pago en línea todavía no está configurado. Esta orden documenta el acuerdo y su seguimiento, pero no significa que Plaza Local haya recibido o retenido dinero.</div>
                @if ($order->cancellation_reason)<div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-700"><strong>Motivo de cancelación:</strong> {{ $order->cancellation_reason }}</div>@endif
            </section>

            <aside class="space-y-4">
                <section class="rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-5 shadow-sm"><h2 class="font-black">Siguiente acción</h2>
                    <div class="mt-4 space-y-3">
                        @if (! $isBuyer && $order->status->value === 'accepted')<form method="POST" action="{{ route('orders.start', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-[#F97316] px-5 py-3 font-black text-white" type="submit">Iniciar trabajo</button></form>
                        @elseif (! $isBuyer && $order->status->value === 'in_progress')<form method="POST" action="{{ route('orders.deliver', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-[#F97316] px-5 py-3 font-black text-white" type="submit">Marcar como entregado</button></form>
                        @elseif ($isBuyer && $order->status->value === 'delivered')<form method="POST" action="{{ route('orders.complete', $order) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-[#14734A] px-5 py-3 font-black text-white" type="submit">Confirmar entrega</button></form>
                        @else<p class="text-sm font-bold leading-6 text-[#6B7D83]">No tienes una acción pendiente en este momento.</p>@endif
                        @if ($conversation)<a class="block w-full rounded-full border border-[#123B4A]/10 px-5 py-3 text-center text-sm font-black" href="{{ route('conversations.show', $conversation) }}">Abrir conversación</a>@endif
                    </div>
                </section>

                @if ($order->status->value === 'accepted')
                    <section class="rounded-[1.75rem] border border-red-100 bg-white p-5"><h2 class="font-black">Cancelar antes de iniciar</h2><p class="mt-2 text-xs font-bold leading-5 text-[#6B7D83]">El motivo quedará registrado para ambas partes.</p><form class="mt-4 space-y-3" method="POST" action="{{ route('orders.cancel', $order) }}">@csrf @method('PATCH')<textarea class="min-h-24 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Explica el motivo"></textarea><button class="w-full rounded-full border border-red-200 px-5 py-2.5 text-sm font-black text-red-700" type="submit">Cancelar contratación</button></form></section>
                @endif
            </aside>
        </div>
    </main>
</body>
</html>
