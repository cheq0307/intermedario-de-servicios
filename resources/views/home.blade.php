<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Encuentra productos, comercios y servicios de tu comunidad en un solo lugar.">
    <title>Plaza Local - Todo cerca de ti</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen w-full overflow-x-clip bg-[#f7f5ef] text-[#17352b] antialiased selection:bg-[#f2c66d] selection:text-[#17352b]">
    <header class="sticky top-0 z-50 border-b border-[#17352b]/10 bg-[#f7f5ef]/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-4 sm:gap-4 sm:px-5 lg:px-8">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 sm:gap-3" aria-label="Ir al inicio">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-[#1f6b4f] text-lg font-black text-white shadow-[0_8px_24px_rgba(31,107,79,.22)] sm:size-11 sm:text-xl">P</span>
                <span class="min-w-0">
                    <span class="block text-base font-black leading-none tracking-tight sm:text-lg">Plaza Local</span>
                    <span class="mt-1 block text-[8px] font-bold uppercase leading-3 tracking-[.14em] text-[#d2693c] sm:text-[10px] sm:tracking-[.22em]">Tu comunidad, conectada</span>
                </span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-bold md:flex" aria-label="Navegación principal">
                <a class="transition hover:text-[#d2693c]" href="#categorias">Categorías</a>
                <a class="transition hover:text-[#d2693c]" href="#como-funciona">Cómo funciona</a>
                <a class="transition hover:text-[#d2693c]" href="#proveedores">Para negocios</a>
            </nav>

            <div class="flex items-center gap-2">
                <a class="hidden rounded-full px-4 py-2 text-sm font-extrabold transition hover:bg-white sm:block" href="{{ route('login') }}">Ingresar</a>
                <a class="shrink-0 whitespace-nowrap rounded-full bg-[#17352b] px-3.5 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-[#244b3e] sm:px-4 sm:text-sm" href="{{ route('register') }}">Crear cuenta</a>
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 opacity-70" aria-hidden="true">
                <div class="absolute -left-24 top-14 size-72 rounded-full bg-[#f2c66d]/25 blur-3xl"></div>
                <div class="absolute -right-20 top-0 size-96 rounded-full bg-[#70aa8e]/20 blur-3xl"></div>
            </div>

            <div class="relative mx-auto grid min-w-0 max-w-7xl items-center gap-12 px-4 py-14 sm:px-5 sm:py-16 lg:grid-cols-[1.08fr_.92fr] lg:px-8 lg:py-24">
                <div class="min-w-0 max-w-full">
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-[#1f6b4f]/15 bg-white/80 px-4 py-2 text-xs font-extrabold text-[#1f6b4f] shadow-sm">
                        <span class="size-2 rounded-full bg-[#d2693c]"></span>
                        Compra y contrata dentro de tu comunidad
                    </div>

                    <h1 class="max-w-full break-words text-[clamp(2.5rem,11vw,4.5rem)] font-black leading-[1.02] tracking-[-.05em]">
                        Todo lo que necesitas,
                        <span class="text-[#d2693c]">cerca de ti.</span>
                    </h1>
                    <p class="mt-6 max-w-full break-words text-base leading-7 text-[#45635a] sm:max-w-2xl sm:text-lg sm:leading-8">
                        Descubre comida, productos, comercios y personas de confianza que ofrecen sus servicios en tu propia comunidad.
                    </p>

                    <form class="mt-8 flex w-full max-w-2xl flex-col gap-3 overflow-hidden rounded-[1.75rem] bg-white p-3 shadow-[0_24px_70px_rgba(23,53,43,.13)] sm:flex-row" role="search">
                        <label class="flex min-w-0 flex-1 items-center gap-3 px-3" for="busqueda">
                            <span class="text-xl" aria-hidden="true">⌕</span>
                            <span class="sr-only">Buscar</span>
                            <input id="busqueda" class="w-full border-0 bg-transparent py-3 text-base font-semibold text-[#17352b] outline-none placeholder:text-[#8b9c96]" type="search" placeholder="¿Qué estás buscando?">
                        </label>
                        <button class="w-full rounded-2xl bg-[#d2693c] px-7 py-3.5 font-extrabold text-white transition hover:bg-[#b9552d] sm:w-auto" type="submit">Buscar cerca</button>
                    </form>

                    <div class="mt-6 flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-bold text-[#6f827b]">Búsquedas frecuentes:</span>
                        <a class="rounded-full bg-white px-3 py-1.5 font-bold shadow-sm hover:text-[#d2693c]" href="#categorias">Tacos</a>
                        <a class="rounded-full bg-white px-3 py-1.5 font-bold shadow-sm hover:text-[#d2693c]" href="#categorias">Plomero</a>
                        <a class="rounded-full bg-white px-3 py-1.5 font-bold shadow-sm hover:text-[#d2693c]" href="#categorias">Papelería</a>
                    </div>
                </div>

                <div class="relative mx-auto min-w-0 w-full max-w-full sm:max-w-xl">
                    <div class="absolute -left-5 top-10 z-10 hidden rotate-[-7deg] rounded-2xl bg-[#f2c66d] px-4 py-3 text-sm font-black text-[#17352b] shadow-xl sm:block">A 5 minutos de ti</div>
                    <div class="max-w-full overflow-hidden rounded-[2.25rem] bg-[#17352b] p-3 shadow-[0_35px_90px_rgba(23,53,43,.25)] sm:p-4">
                        <div class="min-w-0 max-w-full overflow-hidden rounded-[1.65rem] bg-[#eef1e8] p-4 sm:p-6">
                            <div class="flex min-w-0 items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-black uppercase tracking-[.18em] text-[#d2693c]">Abierto ahora</p>
                                    <h2 class="mt-1 break-words text-xl font-black leading-tight sm:text-2xl">Lo más cerca de ti</h2>
                                </div>
                                <span class="hidden shrink-0 rounded-full bg-white px-3 py-2 text-xs font-extrabold shadow-sm sm:inline-flex">Tu comunidad</span>
                            </div>

                            <div class="mt-6 space-y-3">
                                @foreach ([
                                    ['🌮', '#fee3d1', 'Tacos El Comalito', 'Comida · A 350 m', '4.9'],
                                    ['🔧', '#dbeae3', 'Reparaciones Martínez', 'Hogar · Disponible hoy', '4.8'],
                                    ['✏️', '#f8ebba', 'Papelería La Esquina', 'Productos · A 600 m', '4.7'],
                                ] as [$icon, $color, $name, $details, $rating])
                                    <article class="group flex min-w-0 max-w-full items-center gap-3 rounded-2xl bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:gap-4">
                                        <div class="grid size-14 shrink-0 place-items-center rounded-2xl text-2xl sm:size-16 sm:text-3xl" style="background-color: {{ $color }}" aria-hidden="true">{{ $icon }}</div>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="truncate font-black">{{ $name }}</h3>
                                            <p class="mt-1 text-xs font-semibold text-[#6f827b]">{{ $details }}</p>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-[#e2f1e9] px-2 py-1 text-[11px] font-black text-[#1f6b4f] sm:px-2.5 sm:text-xs">{{ $rating }} ★</span>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-6 right-5 hidden rounded-2xl bg-white px-4 py-3 text-sm font-black shadow-xl sm:block">Compra local ♡</div>
                </div>
            </div>
        </section>

        <section id="categorias" class="mx-auto min-w-0 max-w-7xl scroll-mt-28 px-4 py-16 sm:px-5 lg:px-8">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Explora tu comunidad</p>
                    <h2 class="mt-2 break-words text-3xl font-black tracking-tight sm:text-4xl">¿Qué necesitas hoy?</h2>
                </div>
                <a class="text-sm font-black text-[#1f6b4f] hover:text-[#d2693c]" href="#">Ver todas las categorías →</a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ([
                    ['🌮', 'Comida', 'Antojitos y más'],
                    ['🛠️', 'Oficios', 'Ayuda experta'],
                    ['🛍️', 'Tiendas', 'Productos locales'],
                    ['🏠', 'Para el hogar', 'Todo en orden'],
                    ['🐾', 'Mascotas', 'Cuidado cercano'],
                    ['📚', 'Papelería', 'Escuela y oficina'],
                ] as [$icon, $name, $description])
                    <a class="group rounded-3xl border border-[#17352b]/10 bg-white p-5 transition hover:-translate-y-1 hover:border-[#d2693c]/30 hover:shadow-[0_18px_45px_rgba(23,53,43,.10)]" href="#">
                        <span class="grid size-12 place-items-center rounded-2xl bg-[#f7f5ef] text-2xl transition group-hover:scale-110" aria-hidden="true">{{ $icon }}</span>
                        <h3 class="mt-4 font-black">{{ $name }}</h3>
                        <p class="mt-1 text-xs font-semibold leading-5 text-[#7b8d86]">{{ $description }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <section id="como-funciona" class="scroll-mt-24 bg-[#17352b] text-white">
            <div class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
                <div class="max-w-2xl">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#f2c66d]">Simple y transparente</p>
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
                            <span class="text-sm font-black text-[#f2c66d]">{{ $number }}</span>
                            <h3 class="mt-6 text-2xl font-black">{{ $title }}</h3>
                            <p class="mt-3 max-w-sm leading-7 text-white/65">{{ $copy }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="proveedores" class="mx-auto max-w-7xl scroll-mt-24 px-5 py-20 lg:px-8">
            <div class="relative overflow-hidden rounded-[2.25rem] bg-[#f2c66d] px-6 py-12 sm:px-12 lg:flex lg:items-center lg:justify-between lg:px-16">
                <div class="relative max-w-2xl">
                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#8b4c21]">Para comercios y proveedores</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Haz visible lo que sabes hacer.</h2>
                    <p class="mt-4 max-w-xl leading-7 text-[#5d4a29]">Publica productos o servicios sin pagar suscripción. La plataforma genera ingresos únicamente cuando ayuda a concretar una operación.</p>
                </div>
                <button class="relative mt-8 rounded-full bg-[#17352b] px-6 py-3.5 font-black text-white transition hover:-translate-y-0.5 hover:bg-[#244b3e] lg:mt-0" type="button">Quiero vender aquí</button>
            </div>
        </section>
    </main>

    <footer class="border-t border-[#17352b]/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm font-semibold text-[#6f827b] sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <p><span class="font-black text-[#17352b]">Plaza Local</span> · Hecho para fortalecer la comunidad.</p>
            <p>Prototipo inicial · {{ date('Y') }}</p>
        </div>
    </footer>
</body>
</html>
