<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Actividad comercial de tu comunidad en Plaza Local.">
    <title>Inicio - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased selection:bg-[#F97316]/25">
    @php
        $currentUser = auth()->user();
        $isProvider = $currentUser->account_type->value === 'provider';
        $typeLabels = [
            'portfolio' => 'Trabajo realizado',
            'business_update' => 'Novedad',
            'product' => 'Producto',
            'service' => 'Servicio',
            'promotion' => 'Promoción',
            'job_request' => 'Busco ayuda',
        ];
    @endphp

    <header class="sticky top-0 z-40 border-b border-[#123B4A]/10 bg-[#FAF8F4]/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3">
                <span class="grid size-10 place-items-center rounded-2xl bg-[#123B4A] text-lg font-black text-white shadow-[0_8px_24px_rgba(18,59,74,.2)]">P</span>
                <span class="hidden sm:block">
                    <span class="block font-black leading-none">Plaza Local</span>
                    <span class="mt-1 block text-[9px] font-black uppercase tracking-[.2em] text-[#F97316]">Cerca y confiable</span>
                </span>
            </a>

            <label class="flex max-w-xl flex-1 items-center gap-3 rounded-full border border-[#123B4A]/10 bg-white px-4 py-2.5 shadow-sm" for="global-search">
                <svg class="size-5 shrink-0 text-[#6B7D83]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input id="global-search" class="min-w-0 flex-1 bg-transparent text-sm font-semibold outline-none placeholder:text-[#8A999E]" type="search" placeholder="Buscar productos, servicios o personas">
            </label>

            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden max-w-36 truncate text-sm font-bold text-[#536A72] md:block">{{ $currentUser->name }}</span>
                <span class="grid size-10 place-items-center rounded-full bg-[#DCEAE6] font-black text-[#123B4A]">{{ mb_strtoupper(mb_substr($currentUser->name, 0, 1)) }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="hidden rounded-full border border-[#123B4A]/10 bg-white px-4 py-2 text-sm font-black transition hover:border-[#F97316]/30 hover:text-[#F97316] sm:block" type="submit">Salir</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto grid max-w-7xl gap-6 px-4 py-6 pb-28 sm:px-6 lg:grid-cols-[230px_minmax(0,640px)_280px]">
        <aside class="hidden lg:block">
            <nav class="sticky top-24 space-y-1" aria-label="Navegación principal">
                @foreach ([
                    ['Inicio', '#inicio', true],
                    ['Explorar', '#actividad', false],
                    ['Publicar', '#crear-publicacion', false],
                    ['Mensajes', '#proximamente', false],
                    ['Mi perfil', '#proximamente', false],
                ] as [$label, $href, $active])
                    <a class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-black transition {{ $active ? 'bg-[#123B4A] text-white shadow-lg shadow-[#123B4A]/10' : 'text-[#536A72] hover:bg-white hover:text-[#123B4A]' }}" href="{{ $href }}">
                        <span class="size-2 rounded-full {{ $active ? 'bg-[#F97316]' : 'bg-[#B8C4C7]' }}"></span>{{ $label }}
                    </a>
                @endforeach
                <div class="mt-6 rounded-3xl bg-[#E8F1EE] p-5">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-[#22A06B]">Tu comunidad</p>
                    <p class="mt-2 text-sm font-bold leading-6 text-[#536A72]">Compra y contrata dentro de la plataforma para conservar respaldo y reputación.</p>
                </div>
            </nav>
        </aside>

        <div id="inicio" class="min-w-0 space-y-5">
            @if (session('status'))
                <div class="rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-[2rem] bg-[#123B4A] p-6 text-white shadow-[0_22px_55px_rgba(18,59,74,.16)] sm:p-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.2em] text-[#F9B36B]">Hola, {{ explode(' ', trim($currentUser->name))[0] }}</p>
                        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                            {{ $isProvider ? 'Haz visible lo que sabes hacer.' : '¿Qué necesitas resolver hoy?' }}
                        </h1>
                        <p class="mt-3 max-w-xl leading-7 text-white/65">
                            {{ $isProvider ? 'Comparte productos, servicios, promociones y trabajos reales con personas cercanas.' : 'Publica lo que necesitas para que proveedores de tu comunidad puedan encontrarte.' }}
                        </p>
                    </div>
                    <a class="shrink-0 rounded-full bg-[#F97316] px-6 py-3 text-center font-black text-white shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:bg-[#E8660C]" href="#crear-publicacion">Publicar ahora</a>
                </div>
            </section>

            <section id="crear-publicacion" class="scroll-mt-24 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-full bg-[#DCEAE6] font-black text-[#123B4A]">{{ mb_strtoupper(mb_substr($currentUser->name, 0, 1)) }}</span>
                    <div>
                        <h2 class="font-black">Crea una publicación</h2>
                        <p class="text-xs font-semibold text-[#6B7D83]">Aparecerá en la actividad de tu comunidad.</p>
                    </div>
                </div>

                <form class="mt-5 space-y-4" method="POST" action="{{ route('posts.store') }}">
                    @csrf
                    <div class="flex gap-3 overflow-x-auto pb-1">
                        @foreach ($isProvider ? [
                            'service' => 'Servicio',
                            'product' => 'Producto',
                            'promotion' => 'Promoción',
                            'portfolio' => 'Trabajo realizado',
                            'business_update' => 'Novedad',
                        ] : ['job_request' => 'Busco ayuda'] as $value => $label)
                            <label class="shrink-0 cursor-pointer">
                                <input class="peer sr-only" type="radio" name="type" value="{{ $value }}" {{ old('type', $isProvider ? 'service' : 'job_request') === $value ? 'checked' : '' }}>
                                <span class="block rounded-full border border-[#123B4A]/10 px-4 py-2 text-xs font-black text-[#536A72] transition peer-checked:border-[#F97316] peer-checked:bg-[#FFF1E8] peer-checked:text-[#D85B0B]">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('type') <p class="text-sm font-bold text-red-600">{{ $message }}</p> @enderror

                    <textarea class="min-h-28 w-full resize-y rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold leading-6 outline-none transition placeholder:text-[#8A999E] focus:border-[#F97316]/50 focus:ring-4 focus:ring-[#F97316]/10" name="body" maxlength="1500" required placeholder="{{ $isProvider ? 'Cuéntale a la comunidad qué ofreces, precio aproximado, disponibilidad y zona de atención…' : 'Describe el trabajo que necesitas, presupuesto aproximado, zona y cuándo lo requieres…' }}">{{ old('body') }}</textarea>
                    @error('body') <p class="text-sm font-bold text-red-600">{{ $message }}</p> @enderror

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs font-semibold text-[#8A999E]">Fotos, precio y ubicación se incorporarán en el siguiente módulo.</p>
                        <button class="shrink-0 rounded-full bg-[#F97316] px-5 py-2.5 text-sm font-black text-white transition hover:bg-[#E8660C]" type="submit">Publicar</button>
                    </div>
                </form>
            </section>

            <section id="actividad" class="scroll-mt-24 space-y-4">
                <div class="flex items-end justify-between gap-4 px-1 pt-2">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Actividad local</p>
                        <h2 class="mt-1 text-2xl font-black">Lo nuevo cerca de ti</h2>
                    </div>
                    <span class="rounded-full bg-[#E8F1EE] px-3 py-1.5 text-xs font-black text-[#14734A]">Comunidad activa</span>
                </div>

                @forelse ($posts as $post)
                    <article class="overflow-hidden rounded-[1.75rem] border border-[#123B4A]/10 bg-white shadow-sm">
                        <div class="p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="grid size-12 shrink-0 place-items-center rounded-full bg-[#DCEAE6] font-black text-[#123B4A]">{{ mb_strtoupper(mb_substr($post->user->name, 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <h3 class="truncate font-black">{{ $post->user->name }}</h3>
                                        <p class="mt-0.5 text-xs font-semibold text-[#6B7D83]">{{ $post->published_at->diffForHumans() }} · Tu comunidad</p>
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full bg-[#FFF1E8] px-3 py-1.5 text-[11px] font-black text-[#D85B0B]">{{ $typeLabels[$post->type] ?? 'Publicación' }}</span>
                            </div>

                            <p class="mt-5 whitespace-pre-line text-[15px] font-medium leading-7 text-[#314B54]">{{ $post->body }}</p>

                            @if ($post->type === 'service' || $post->type === 'product' || $post->type === 'promotion')
                                <div class="mt-5 flex items-center gap-2 rounded-2xl bg-[#E9F7F0] px-4 py-3 text-sm font-black text-[#14734A]">
                                    <span class="size-2 rounded-full bg-[#22A06B]"></span> Disponible para recibir solicitudes
                                </div>
                            @endif
                        </div>
                        <div class="grid grid-cols-3 border-t border-[#123B4A]/8 px-3 py-2 text-xs font-black text-[#6B7D83]">
                            <button class="rounded-xl px-3 py-2.5 transition hover:bg-[#FAF8F4] hover:text-[#F97316]" type="button">Me interesa</button>
                            <button class="rounded-xl px-3 py-2.5 transition hover:bg-[#FAF8F4] hover:text-[#F97316]" type="button">Comentar</button>
                            <button class="rounded-xl px-3 py-2.5 transition hover:bg-[#FAF8F4] hover:text-[#F97316]" type="button">Compartir</button>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[1.75rem] border border-dashed border-[#123B4A]/20 bg-white/60 px-6 py-12 text-center">
                        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-[#FFF1E8] text-2xl font-black text-[#F97316]">+</span>
                        <h3 class="mt-4 text-lg font-black">Sé la primera publicación</h3>
                        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[#6B7D83]">La actividad real aparecerá aquí conforme clientes y proveedores compartan lo que ofrecen o necesitan.</p>
                    </div>
                @endforelse

                @if ($posts->hasPages())
                    <div class="pt-2">{{ $posts->links() }}</div>
                @endif
            </section>
        </div>

        <aside class="hidden xl:block">
            <div class="sticky top-24 space-y-4">
                <section class="rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="font-black">Explora por categoría</h2>
                        <span class="text-xs font-black text-[#F97316]">Ver todo</span>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach (['Comida local', 'Hogar y reparaciones', 'Productos y tiendas', 'Belleza y cuidado', 'Transporte'] as $category)
                            <button class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-bold text-[#536A72] transition hover:bg-[#FAF8F4] hover:text-[#123B4A]" type="button">
                                {{ $category }} <span class="text-[#A4B0B4]">›</span>
                            </button>
                        @endforeach
                    </div>
                </section>

                <section id="proximamente" class="rounded-[1.75rem] bg-[#F5D48D] p-5">
                    <p class="text-xs font-black uppercase tracking-[.16em] text-[#8B5117]">Siguiente etapa</p>
                    <h2 class="mt-2 text-lg font-black">Perfiles y conversaciones</h2>
                    <p class="mt-2 text-sm font-semibold leading-6 text-[#6D522D]">Los botones visibles se activarán conforme construyamos cada flujo con datos reales.</p>
                </section>
            </div>
        </aside>
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-[#123B4A]/10 bg-white/95 px-2 pb-[max(.5rem,env(safe-area-inset-bottom))] pt-2 backdrop-blur-xl lg:hidden" aria-label="Navegación móvil">
        <div class="mx-auto grid max-w-lg grid-cols-5">
            @foreach ([
                ['Inicio', '#inicio'],
                ['Explorar', '#actividad'],
                ['Publicar', '#crear-publicacion'],
                ['Mensajes', '#proximamente'],
                ['Perfil', '#proximamente'],
            ] as [$label, $href])
                <a class="flex flex-col items-center gap-1 rounded-xl px-1 py-2 text-[11px] font-black {{ $label === 'Publicar' ? 'text-[#F97316]' : 'text-[#6B7D83]' }}" href="{{ $href }}">
                    <span class="grid size-6 place-items-center rounded-lg {{ $label === 'Publicar' ? 'bg-[#FFF1E8] text-lg' : 'bg-transparent' }}">{{ $label === 'Publicar' ? '+' : '•' }}</span>
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </nav>
</body>
</html>
