<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Encuentra productos, comercios y servicios de tu comunidad en un solo lugar.">
    <title>Plaza Local - Todo cerca de ti</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen w-full overflow-x-clip bg-brand-forest-cream text-brand-forest-deep antialiased selection:bg-brand-gold-soft selection:text-brand-forest-deep">
    <x-market-nav />

    <main>
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 opacity-70" aria-hidden="true">
                <div class="absolute -left-24 top-14 size-72 rounded-full bg-brand-gold-soft/25 blur-3xl"></div>
                <div class="absolute -right-20 top-0 size-96 rounded-full bg-brand-sage/20 blur-3xl"></div>
            </div>

            <div class="relative mx-auto grid min-w-0 max-w-7xl items-center gap-10 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-[1.08fr_.92fr] lg:gap-12 lg:px-8 lg:py-24">
                <div class="min-w-0 max-w-full">
                    <div class="mb-6 inline-flex max-w-full items-center gap-2 rounded-full border border-brand-forest-strong/15 bg-white/80 px-3 py-2 text-[11px] font-extrabold leading-4 text-brand-forest-strong shadow-sm sm:px-4 sm:text-xs">
                        <span class="size-2 rounded-full bg-brand-coral-strong"></span>
                        Compra y contrata dentro de tu comunidad
                    </div>

                    <h1 class="max-w-full break-words text-[clamp(2.4rem,11vw,4.5rem)] font-black leading-[1.02] tracking-[-.05em]">
                        Todo lo que necesitas,
                        <span class="text-brand-coral-strong">cerca de ti.</span>
                    </h1>
                    <p class="mt-6 max-w-full break-words text-base leading-7 text-brand-forest-muted sm:max-w-2xl sm:text-lg sm:leading-8">
                        Descubre comida, productos, comercios y personas de confianza que ofrecen sus servicios en tu propia comunidad.
                    </p>

                    <form class="mt-8 flex w-full max-w-2xl flex-col gap-3 overflow-hidden rounded-[1.75rem] bg-white p-3 shadow-market-search lg:flex-row" role="search" method="GET" action="{{ route('explore') }}">
                        <label class="flex min-w-0 flex-1 items-center gap-3 px-3" for="busqueda">
                            <span class="text-xl" aria-hidden="true">⌕</span>
                            <span class="sr-only">Buscar</span>
                            <input id="busqueda" class="w-full border-0 bg-transparent py-3 text-base font-semibold text-brand-forest-deep outline-none placeholder:text-brand-placeholder" type="search" name="q" maxlength="100" placeholder="¿Qué estás buscando?">
                        </label>
                        <button class="w-full rounded-2xl bg-brand-coral-strong px-7 py-3.5 font-extrabold text-white transition hover:bg-brand-coral-dark lg:w-auto" type="submit">Buscar cerca</button>
                    </form>

                    <div class="mt-6 flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-bold text-brand-sage-muted">Búsquedas frecuentes:</span>
                        <a class="rounded-full bg-white px-3 py-1.5 font-bold shadow-sm hover:text-brand-coral-strong" href="#categorias">Tacos</a>
                        <a class="rounded-full bg-white px-3 py-1.5 font-bold shadow-sm hover:text-brand-coral-strong" href="#categorias">Plomero</a>
                        <a class="rounded-full bg-white px-3 py-1.5 font-bold shadow-sm hover:text-brand-coral-strong" href="#categorias">Papelería</a>
                    </div>
                </div>

                <div class="relative mx-auto min-w-0 w-full max-w-2xl lg:max-w-xl">
                    <div class="absolute -left-5 top-10 z-10 hidden rotate-[-7deg] rounded-2xl bg-brand-gold-soft px-4 py-3 text-sm font-black text-brand-forest-deep shadow-xl lg:block">A 5 minutos de ti</div>
                    <div class="max-w-full overflow-hidden rounded-[2.25rem] bg-brand-forest-deep p-3 shadow-market-feature sm:p-4">
                        <div class="min-w-0 max-w-full overflow-hidden rounded-[1.65rem] bg-brand-green-cream p-4 sm:p-6">
                            <div class="flex min-w-0 items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase tracking-[.18em] text-brand-coral-strong">Abierto ahora</p>
                                    <h2 class="mt-1 break-words text-xl font-black leading-tight sm:text-2xl">Lo más cerca de ti</h2>
                                </div>
                                <span class="hidden shrink-0 rounded-full bg-white px-3 py-2 text-xs font-extrabold shadow-sm lg:inline-flex">Tu comunidad</span>
                            </div>

                            <div class="mt-6 space-y-3">
                                @forelse($featuredListings as $listing)
                                    <a class="group flex min-w-0 max-w-full items-center gap-3 rounded-2xl bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:gap-4" href="{{ route('explore', ['q' => $listing->name]) }}">
                                        <div class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-2xl bg-brand-sage-surface text-2xl sm:size-16">
                                            @if($listing->post?->media?->first()?->type === 'image')<img class="size-full object-cover" src="{{ $listing->post->media->first()->url }}" alt="">@else{{ $listing->type->value === 'product' ? '🛍️' : '🛠️' }}@endif
                                        </div>
                                        <div class="min-w-0 flex-1"><h3 class="truncate font-black">{{ $listing->name }}</h3><p class="mt-1 truncate text-xs font-semibold text-brand-sage-muted">{{ $listing->vendor->display_name }} · {{ $listing->vendor->user->city ?: 'Tu comunidad' }}</p></div>
                                        <span class="shrink-0 rounded-full bg-brand-success-surface px-2 py-1 text-[11px] font-black text-brand-forest-strong">{{ $listing->price_amount === null ? 'Cotizar' : '$'.number_format($listing->price_amount / 100, 0) }}</span>
                                    </a>
                                @empty
                                    <div class="rounded-2xl bg-white p-5 text-center"><p class="font-black">La plaza esta por abrir</p><p class="mt-1 text-sm text-brand-sage-muted">Las primeras ofertas aprobadas apareceran aqui.</p></div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-6 right-5 hidden rounded-2xl bg-white px-4 py-3 text-sm font-black shadow-xl lg:block">Compra local ♡</div>
                </div>
            </div>
        </section>

        <section id="categorias" class="mx-auto min-w-0 max-w-7xl scroll-mt-28 px-4 py-16 sm:px-5 lg:px-8">
            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-brand-coral-strong">Explora tu comunidad</p>
                    <h2 class="mt-2 break-words text-3xl font-black tracking-tight sm:text-4xl">¿Qué necesitas hoy?</h2>
                </div>
                <a class="text-sm font-black text-brand-forest-strong hover:text-brand-coral-strong" href="#">Ver todas las categorías →</a>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-3 min-[420px]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ([
                    ['🌮', 'Comida', 'Antojitos y más'],
                    ['🛠️', 'Oficios', 'Ayuda experta'],
                    ['🛍️', 'Tiendas', 'Productos locales'],
                    ['🏠', 'Para el hogar', 'Todo en orden'],
                    ['🐾', 'Mascotas', 'Cuidado cercano'],
                    ['📚', 'Papelería', 'Escuela y oficina'],
                ] as [$icon, $name, $description])
                    <a class="group rounded-3xl border border-brand-forest-deep/10 bg-white p-5 transition hover:-translate-y-1 hover:border-brand-coral-strong/30 hover:shadow-market-card-hover" href="#">
                        <span class="grid size-12 place-items-center rounded-2xl bg-brand-forest-cream text-2xl transition group-hover:scale-110" aria-hidden="true">{{ $icon }}</span>
                        <h3 class="mt-4 font-black">{{ $name }}</h3>
                        <p class="mt-1 text-xs font-semibold leading-5 text-brand-sage-copy">{{ $description }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <section id="como-funciona" class="scroll-mt-24 bg-brand-forest-deep text-white">
            <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
                <div class="max-w-2xl">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-brand-gold-soft">Simple y transparente</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">De la búsqueda a la entrega</h2>
                    <p class="mt-4 leading-7 text-white/65">Cada operación queda registrada para proteger la reputación de compradores y proveedores.</p>
                </div>

                <div class="mt-12 grid gap-8 md:grid-cols-3">
                    @foreach ([
                        ['01', 'Encuentra', 'Busca productos y servicios disponibles dentro de tu comunidad.'],
                        ['02', 'Pide o cotiza', 'El negocio confirma disponibilidad, precio y tiempo estimado.'],
                        ['03', 'Recibe y califica', 'Confirma la entrega y comparte una evaluación útil para todos.'],
                    ] as [$number, $title, $copy])
                        <article class="border-t border-white/20 pt-6">
                            <span class="text-sm font-black text-brand-gold-soft">{{ $number }}</span>
                            <h3 class="mt-6 text-2xl font-black">{{ $title }}</h3>
                            <p class="mt-3 max-w-sm leading-7 text-white/65">{{ $copy }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="proveedores" class="mx-auto max-w-7xl scroll-mt-24 px-5 py-20 lg:px-8">
            <div class="relative overflow-hidden rounded-[2.25rem] bg-brand-gold-soft px-6 py-12 sm:px-12 lg:flex lg:items-center lg:justify-between lg:px-16">
                <div class="relative max-w-2xl">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-brand-earth-orange">Para comercios y proveedores</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Haz visible lo que sabes hacer.</h2>
                    <p class="mt-4 max-w-xl leading-7 text-brand-earth">Publica productos o servicios sin pagar suscripción. La plataforma genera ingresos únicamente cuando ayuda a concretar una operación.</p>
                </div>
                <button class="relative mt-8 rounded-full bg-brand-forest-deep px-6 py-3.5 font-black text-white transition hover:-translate-y-0.5 hover:bg-brand-forest-hover lg:mt-0" type="button">Quiero vender aquí</button>
            </div>
        </section>
    </main>

    <footer class="border-t border-brand-forest-deep/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm font-semibold text-brand-sage-muted sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <p><span class="font-black text-brand-forest-deep">Plaza Local</span> · Hecho para fortalecer la comunidad.</p>
            <p>Prototipo inicial · {{ date('Y') }}</p>
        </div>
    </footer>
</body>
</html>
