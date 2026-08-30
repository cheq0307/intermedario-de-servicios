<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis operaciones - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen pb-24 bg-brand-surface text-brand-ink antialiased">
    @php($statusLabels = ['accepted' => 'Contratación aceptada', 'in_progress' => 'En progreso', 'delivered' => 'Esperando confirmación', 'completed' => 'Completado', 'cancelled' => 'Cancelado', 'disputed' => 'En disputa'])
    @php($statusLabels = array_merge($statusLabels, ['awaiting_payment' => 'Pendiente de pago', 'paid' => 'Pagado', 'ready' => 'Listo para entregar']))
    <x-market-nav :back-url="route('more.index')" />
    <main class="mx-auto max-w-5xl px-5 py-9">
        <p class="text-xs font-black uppercase tracking-[.18em] text-brand-orange">Contrataciones</p>
        <h1 class="mt-2 text-3xl font-black">Mis operaciones</h1>
        <p class="mt-3 max-w-2xl leading-7 text-brand-muted">Sigue compras y contrataciones sin confundirlas con las vacantes de empleo.</p><nav class="mt-5 flex gap-2"><a class="rounded-full px-5 py-2.5 text-sm font-black {{ $role === 'client' ? 'bg-brand text-white' : 'border bg-white' }}" href="{{ route('orders.index', ['como' => 'client']) }}">Como cliente</a><a class="rounded-full px-5 py-2.5 text-sm font-black {{ $role === 'provider' ? 'bg-brand text-white' : 'border bg-white' }}" href="{{ route('orders.index', ['como' => 'provider']) }}">Como proveedor</a></nav>

        <div class="mt-8 space-y-4">
            @forelse ($orders as $order)
                @php($isBuyer = $order->buyer_id === auth()->id())
                @php($isProduct = $order->fulfillment_type === 'pickup')
                <a class="block rounded-[1.75rem] border border-brand/10 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-orange/25" href="{{ route('orders.show', $order) }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <span class="rounded-full bg-brand-avatar-soft px-3 py-1.5 text-xs font-black text-brand-success">{{ $isProduct ? ($isBuyer ? 'Compraste' : 'Te compraron') : ($isBuyer ? 'Contrataste' : 'Te contrataron') }}</span>
                            <h2 class="mt-3 text-xl font-black">{{ $order->jobRequest?->title ?? $order->items->first()?->name_snapshot ?? 'Trabajo local' }}</h2>
                            <p class="mt-2 text-sm font-bold text-brand-muted">Con {{ $isBuyer ? $order->vendor->display_name : $order->buyer->name }}</p>
                        </div>
                        <div class="text-right">
                            <strong class="block text-xl">${{ number_format($order->total_amount / 100, 2) }} MXN</strong>
                            <span class="mt-2 inline-block rounded-full bg-brand-orange-soft px-3 py-1.5 text-xs font-black text-brand-danger-warm">{{ $statusLabels[$order->status->value] ?? ucfirst($order->status->value) }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-[1.75rem] border border-dashed border-brand/20 bg-white/60 px-6 py-14 text-center">
                    <h2 class="text-xl font-black">Todavía no tienes contrataciones</h2>
                    <p class="mt-2 text-sm font-bold text-brand-muted">Aparecerán aquí cuando una propuesta sea aceptada.</p>
                </div>
            @endforelse
        </div>
        @if ($orders->hasPages())<div class="mt-6">{{ $orders->links() }}</div>@endif
    </main>
<x-bottom-nav active="orders" />
</body>
</html>
