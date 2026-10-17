<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Busca productos, servicios, personas y solicitudes dentro de Plaza Local.">
    <title>Explorar - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-plaza-cream pb-24 text-plaza-ink antialiased">
    <x-market-nav :back-url="auth()->check() ? route('dashboard') : route('home')" :search-value="$filters['q'] ?? ''" />

    <main class="mx-auto max-w-6xl px-4 py-7 sm:px-6 sm:py-9">
        <header class="max-w-3xl">
            <p class="text-xs font-black uppercase tracking-[.16em] text-plaza-orange">Explora tu comunidad</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">@if(filled($filters['q'] ?? null))Resultados para “{{ $filters['q'] }}”@else Encuentra algo cerca de ti @endif</h1>
            <p class="mt-2 text-sm font-semibold leading-6 text-brand-muted">Distingue productos, servicios, personas y solicitudes antes de abrir cada resultado.</p>
        </header>

        @php
            $typeOptions = ['all' => 'Todo', 'product' => 'Productos', 'service' => 'Servicios', 'provider' => 'Personas', 'job_request' => 'Solicitudes'];
            $baseTypeFilters = collect(['q' => $filters['q'] ?? null, 'category_id' => $categoryId, 'community_id' => $filters['community_id'] ?? null, 'scope' => $scope])->filter(fn ($value) => $value !== null && $value !== '')->all();
        @endphp

        <nav class="-mx-4 mt-6 flex gap-2 overflow-x-auto px-4 pb-2 scrollbar-none sm:mx-0 sm:px-0" aria-label="Tipos de resultado">
            @foreach($typeOptions as $value => $label)
                <a class="min-h-11 shrink-0 rounded-full border px-5 py-3 text-sm font-black transition {{ $type === $value ? 'border-plaza-orange bg-plaza-orange text-white' : 'border-plaza-line bg-white text-brand-copy hover:border-plaza-orange/40 hover:text-plaza-ink' }}" href="{{ route('explore', array_merge($baseTypeFilters, ['type' => $value])) }}" @if($type === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <form class="mt-4" method="GET" action="{{ route('explore') }}">
            <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
            <input type="hidden" name="type" value="{{ $type }}">
            <details class="rounded-3xl border border-plaza-line/80 bg-white shadow-sm" @if(request()->hasAny(['category_id', 'min_price', 'max_price', 'community_id', 'scope', 'radius_km'])) open @endif>
                <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 font-black" aria-label="Filtros avanzados"><span>Filtros avanzados</span><span class="text-sm text-plaza-orange">Ajustar zona, rubro y precio ↓</span></summary>
                <div class="grid gap-3 border-t border-plaza-line/70 p-4 sm:grid-cols-2 lg:grid-cols-4">
                    <label class="block"><span class="mb-1.5 block text-xs font-black">Rubro</span><select class="min-h-12 w-full rounded-2xl bg-brand-surface px-4 py-3" name="category_id"><option value="">Todos los rubros</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-black">Precio mínimo</span><input class="min-h-12 w-full rounded-2xl bg-brand-surface px-4 py-3" type="number" name="min_price" value="{{ $filters['min_price'] ?? '' }}" min="0" step="0.01" placeholder="$ MXN"></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-black">Precio máximo</span><input class="min-h-12 w-full rounded-2xl bg-brand-surface px-4 py-3" type="number" name="max_price" value="{{ $filters['max_price'] ?? '' }}" min="0" step="0.01" placeholder="$ MXN"></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-black">Comunidad</span><select class="min-h-12 w-full rounded-2xl bg-brand-surface px-4 py-3" name="community_id"><option value="">Selecciona una comunidad</option>@foreach($communities as $community)<option value="{{ $community->id }}" @selected((string) ($filters['community_id'] ?? '') === (string) $community->id)>{{ $community->public_location_label }}</option>@endforeach</select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-black">Alcance</span><select class="min-h-12 w-full rounded-2xl bg-brand-surface px-4 py-3" name="scope"><option value="community" @selected($scope === 'community')>Solo esta comunidad</option><option value="nearby" @selected($scope === 'nearby')>Comunidades cercanas</option><option value="all" @selected($scope === 'all')>Todas las comunidades</option></select></label>
                    <label class="block"><span class="mb-1.5 block text-xs font-black">Radio máximo</span><input class="min-h-12 w-full rounded-2xl bg-brand-surface px-4 py-3" type="number" name="radius_km" value="{{ $radiusKm }}" min="1" max="100" step="0.5" placeholder="km"></label>
                    <div class="flex items-end gap-2 sm:col-span-2"><button class="min-h-12 flex-1 rounded-full bg-plaza-ink px-6 py-3 font-black text-white" type="submit">Aplicar filtros</button>@if(request()->hasAny(['q', 'type', 'category_id', 'min_price', 'max_price', 'community_id', 'scope', 'radius_km']))<a class="min-h-12 rounded-full border border-plaza-line px-5 py-3 text-center text-sm font-black text-plaza-orange" href="{{ route('explore') }}">Limpiar</a>@endif</div>
                    <p class="text-xs font-bold leading-5 text-brand-muted sm:col-span-2 lg:col-span-4">El feed social es global. La búsqueda comercial parte de la comunidad elegida.@if($scope === 'nearby' && !$radiusSearchAvailable)<span class="mt-1 block text-brand-danger-warm">Esta comunidad aún no tiene coordenadas; por seguridad mostramos solo resultados de la misma comunidad.</span>@endif</p>
                </div>
            </details>
        </form>
        @if($errors->any())<p class="mt-4 rounded-2xl bg-red-50 p-4 font-bold text-red-700">{{ $errors->first() }}</p>@endif

        @if(in_array($type, ['all', 'product', 'service'], true))
            <section class="mt-10" aria-labelledby="commercial-results">
                <div class="flex items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.16em] text-plaza-orange">Compra y contrata</p><h2 id="commercial-results" class="mt-1 text-2xl font-black">{{ $type === 'product' ? 'Productos' : ($type === 'service' ? 'Servicios' : 'Productos y servicios') }}</h2></div><span class="text-sm font-bold text-brand-muted">{{ $listings->total() }} resultados</span></div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($listings as $listing)
                        @php($listingHref = auth()->check() || $listing->type->value !== 'product' || $listing->price_type->value !== 'fixed' ? null : route('login'))
                        <x-marketplace.listing-card :listing="$listing" :href="$listingHref" />
                    @empty
                        <div class="col-span-full rounded-3xl border border-dashed border-plaza-line bg-white/60 p-8 text-center"><h3 class="font-black">No encontramos {{ $type === 'service' ? 'servicios' : ($type === 'product' ? 'productos' : 'ofertas') }} con esos filtros</h3><p class="mt-2 text-sm font-semibold text-brand-muted">Prueba otro rubro, amplía la zona o limpia los filtros.</p></div>
                    @endforelse
                </div>
                @if($listings->hasPages())<div class="mt-5">{{ $listings->links() }}</div>@endif
            </section>
        @endif

        @if(in_array($type, ['all', 'provider'], true))
            <section class="mt-12" aria-labelledby="provider-results"><div class="flex items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.16em] text-plaza-orange">Personas de confianza</p><h2 id="provider-results" class="mt-1 text-2xl font-black">Perfiles comerciales</h2></div><span class="text-sm font-bold text-brand-muted">{{ $providers->total() }} resultados</span></div><div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@forelse($providers as $vendor)<a class="rounded-3xl border border-plaza-line/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-plaza-orange/35" href="{{ route('profile.show', $vendor->user) }}"><div class="flex items-center gap-3"><span class="grid size-12 place-items-center rounded-2xl bg-plaza-mint font-black">{{ mb_strtoupper(mb_substr($vendor->display_name, 0, 1)) }}</span><div class="min-w-0"><h3 class="truncate font-black">{{ $vendor->display_name }}</h3><p class="truncate text-sm font-bold text-brand-muted">{{ $vendor->specialty ?: 'Perfil comercial local' }}</p></div></div>@if($vendor->description)<p class="mt-4 line-clamp-2 text-sm leading-6">{{ $vendor->description }}</p>@endif<p class="mt-4 text-xs font-black text-brand-success">✓ Actividad comercial aprobada</p></a>@empty<p class="col-span-full rounded-3xl border border-dashed border-plaza-line p-8 text-center font-bold text-brand-muted">No encontramos perfiles comerciales con esos filtros.</p>@endforelse</div>@if($providers->hasPages())<div class="mt-5">{{ $providers->links() }}</div>@endif</section>
        @endif

        @if(in_array($type, ['all', 'job_request'], true))
            <section class="mt-12 pb-8" aria-labelledby="request-results"><div class="flex items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.16em] text-plaza-orange">Personas que buscan ayuda</p><h2 id="request-results" class="mt-1 text-2xl font-black">Solicitudes de la comunidad</h2></div><span class="text-sm font-bold text-brand-muted">{{ $jobRequests->total() }} resultados</span></div><div class="mt-5 grid gap-4 sm:grid-cols-2">@forelse($jobRequests as $job)<article class="rounded-3xl border border-plaza-orange/20 bg-white p-5 shadow-sm"><p class="text-[10px] font-black uppercase tracking-[.12em] text-plaza-orange">Solicitud · {{ $job->category?->name ?? 'Rubro por actualizar' }}</p><h3 class="mt-3 text-lg font-black">{{ $job->title }}</h3><p class="mt-2 line-clamp-2 text-sm leading-6 text-brand-copy-strong">{{ $job->description }}</p><div class="mt-5 flex items-center justify-between gap-3"><span class="text-sm font-bold text-brand-muted">{{ $job->location_label ?: $job->client->city ?: 'Tu comunidad' }}</span>@if(auth()->user()?->canActAsProvider())<a class="rounded-full bg-plaza-ink px-4 py-2 text-sm font-black text-white" href="{{ route('job-proposals.index', $job) }}">Enviar propuesta</a>@elseif(auth()->check())<a class="rounded-full border border-plaza-line px-4 py-2 text-sm font-black" href="{{ route('profile.edit', ['ofrecer' => 1]) }}">Activar servicios</a>@else<a class="rounded-full bg-plaza-ink px-4 py-2 text-sm font-black text-white" href="{{ route('register') }}">Regístrate para responder</a>@endif</div></article>@empty<p class="col-span-full rounded-3xl border border-dashed border-plaza-line p-8 text-center font-bold text-brand-muted">No encontramos solicitudes con esos filtros.</p>@endforelse</div>@if($jobRequests->hasPages())<div class="mt-5">{{ $jobRequests->links() }}</div>@endif</section>
        @endif
    </main>

    <x-bottom-nav />
</body>
</html>
