<x-layouts.auth title="Acceso administrativo de pruebas - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-coral-strong">Entorno local de validación</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight">Perfiles administrativos</h1>
        <p class="mt-3 text-sm font-semibold text-brand-sage-muted">Esta entrada usa la sesión administrativa separada de las cuentas del marketplace.</p>
    </div>

    <div class="mt-8 grid gap-3 sm:grid-cols-2">
        @foreach ($administrators as $administrator)
            <form method="POST" action="{{ route('demo.admin-access.store') }}">
                @csrf
                <input type="hidden" name="id" value="{{ $administrator->id }}">
                <button class="w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3 text-left transition hover:border-brand-coral-strong hover:bg-brand-cream" type="submit">
                    <span class="block font-black">{{ $administrator->name }}</span>
                    <span class="mt-1 block text-xs font-semibold text-brand-sage-muted">Administración separada</span>
                </button>
            </form>
        @endforeach
    </div>

    <a class="mt-7 inline-flex text-sm font-black text-brand-teal hover:text-brand-coral-strong" href="{{ route('demo.access') }}">← Volver a perfiles del marketplace</a>
</x-layouts.auth>
