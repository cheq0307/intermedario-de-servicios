        <div class="mt-7 space-y-3">
            @forelse($notifications as $notification)
                @php
                    $kind = $notification->data['kind'] ?? 'activity';
                    $kindLabel = match(true) {
                        str_starts_with($kind, 'social_') => 'Social',
                        str_contains($kind, 'support') => 'Soporte',
                        default => 'Actividad',
                    };
                @endphp
                <form method="POST" action="{{ route('notifications.open', $notification->id) }}">@csrf @method('PATCH')
                    <button class="relative flex w-full items-start gap-4 overflow-hidden rounded-[1.5rem] border p-5 text-left shadow-sm transition hover:-translate-y-0.5 {{ $notification->read_at ? 'border-brand/10 bg-brand-neutral-page text-brand-copy' : 'border-brand-success-bright/30 bg-brand-success-soft text-brand-ink' }}" type="submit">
                        <span class="absolute inset-y-0 left-0 w-1.5 {{ $notification->read_at ? 'bg-brand-line-strong' : 'bg-brand-success-bright' }}"></span>
                        <span class="mt-1 grid size-10 shrink-0 place-items-center rounded-full {{ $notification->read_at ? 'bg-white text-brand-muted' : 'bg-brand-success text-white' }}">{{ $notification->read_at ? '✓' : '•' }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2"><strong class="block {{ $notification->read_at ? 'font-bold' : 'font-black' }}">{{ $notification->data['title'] ?? 'Actividad nueva' }}</strong><span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide {{ $notification->read_at ? 'bg-white text-brand-muted' : 'bg-brand-success text-white' }}">{{ $kindLabel }} · {{ $notification->read_at ? 'Leída' : 'Nueva' }}</span></span>
                            <span class="mt-1 block text-sm font-semibold leading-6 {{ $notification->read_at ? 'text-brand-form-muted' : 'text-brand-copy' }}">{{ $notification->data['body'] ?? '' }}</span>
                            <time class="mt-2 block text-xs font-bold text-brand-caption">{{ $notification->created_at->diffForHumans() }}</time>
                        </span>
                        <span class="mt-2 font-black {{ $notification->read_at ? 'text-brand-caption' : 'text-brand-success' }}">Abrir →</span>
                    </button>
                </form>
            @empty
                <div class="rounded-[1.75rem] border border-dashed border-brand/20 bg-white/60 p-12 text-center"><h2 class="text-xl font-black">No hay avisos en este filtro</h2><p class="mt-2 text-sm font-bold text-brand-muted">Puedes revisar otra categoría de notificaciones.</p></div>
            @endforelse
        </div>
        @if($notifications->hasPages())<div class="mt-6">{{ $notifications->links() }}</div>@endif
