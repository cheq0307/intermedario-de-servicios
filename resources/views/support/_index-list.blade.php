<div class="grid gap-3">
    @forelse($tickets as $ticket)
        <a class="rounded-3xl border border-brand/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('support.show',$ticket) }}"><div class="flex flex-wrap items-start justify-between gap-3"><div><span class="text-xs font-black uppercase tracking-wide text-brand-danger-warm">{{ $ticket->reference }} · {{ $ticket->category_label }}</span><h2 class="mt-2 text-lg font-black">{{ $ticket->subject }}</h2></div><span class="rounded-full bg-brand-success-soft px-3 py-1 text-xs font-black text-brand-success">{{ $ticket->status_label }}</span></div><p class="mt-3 text-xs font-bold text-brand-muted">{{ $ticket->messages_count }} mensajes · Actualizado {{ optional($ticket->last_message_at)->diffForHumans() }}</p></a>
    @empty
        <div class="rounded-3xl border border-dashed bg-white p-12 text-center"><h2 class="text-xl font-black">No tienes solicitudes</h2><p class="mt-2 text-sm font-semibold text-brand-muted">Cuando necesites ayuda, podrás seguirla desde aquí.</p></div>
    @endforelse
</div>
{{ $tickets->links() }}
