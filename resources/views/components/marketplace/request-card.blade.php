@props(['request'])

<article {{ $attributes->class(['flex min-w-0 flex-col rounded-[1.4rem] bg-white p-4 shadow-market-card']) }} data-request-card>
    <div class="flex items-center justify-between gap-3">
        <span class="rounded-full bg-brand-orange-soft px-2.5 py-1 text-[11px] font-black text-brand-orange-dark">Solicitud</span>
        <span class="text-[11px] font-bold text-brand-muted">{{ $request->proposals_count }} propuestas</span>
    </div>
    <h3 class="mt-3 line-clamp-2 text-lg font-black leading-6 text-brand">{{ $request->title }}</h3>
    <p class="mt-2 truncate text-xs font-semibold text-brand-muted">{{ $request->category?->name ?? 'Ayuda local' }} · {{ $request->client?->community?->name ?? 'Tu comunidad' }}</p>
    <div class="mt-auto pt-4">
        @if($request->budget_min_amount)
            <strong class="block text-sm text-brand-success">Desde MXN {{ number_format($request->budget_min_amount / 100, 2) }}</strong>
        @else
            <strong class="block text-sm text-brand-success">Presupuesto por acordar</strong>
        @endif
    </div>
</article>
