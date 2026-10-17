<x-layouts.auth title="Acceso de pruebas - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-coral-strong">Entorno local de validación</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight">Perfiles de prueba</h1>
        <p class="mt-3 text-sm font-semibold text-brand-sage-muted">Entra con un perfil ficticio sin contraseña. Los pagos de este modo siempre son simulados.</p>
    </div>

    <div class="mt-8 space-y-7">
        @foreach ($profiles as $type => $items)
            <section>
                <h2 class="mb-3 text-sm font-black uppercase tracking-wide">{{ $profileLabels[$type] }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($items as $profile)
                        <form method="POST" action="{{ route('demo.access.store') }}">
                            @csrf
                            <input type="hidden" name="guard" value="web">
                            <input type="hidden" name="id" value="{{ $profile->id }}">
                            <button class="w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3 text-left transition hover:border-brand-coral-strong hover:bg-brand-cream" type="submit">
                                <span class="block font-black">{{ $profile->name }}</span>
                                <span class="mt-1 block text-xs font-semibold text-brand-sage-muted">{{ $profile->email }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section>
            <h2 class="mb-3 text-sm font-black uppercase tracking-wide">Administradores</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($administrators as $administrator)
                    <form method="POST" action="{{ route('demo.access.store') }}">
                        @csrf
                        <input type="hidden" name="guard" value="admin">
                        <input type="hidden" name="id" value="{{ $administrator->id }}">
                        <button class="w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3 text-left transition hover:border-brand-coral-strong hover:bg-brand-cream" type="submit">
                            <span class="block font-black">{{ $administrator->name }}</span>
                            <span class="mt-1 block text-xs font-semibold text-brand-sage-muted">Administración separada</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.auth>
