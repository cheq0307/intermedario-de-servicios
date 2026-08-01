<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f5ef] text-[#17352b] antialiased">
    <header class="sticky top-0 z-40 border-b border-[#17352b]/10 bg-[#f7f5ef]/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-[#1f6b4f] font-black text-white">P</span>
                <span class="font-black">Plaza Local</span>
            </a>
            <div class="flex items-center gap-3">
                <span class="hidden text-sm font-bold text-[#6f827b] sm:block">Hola, {{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-full border border-[#17352b]/15 bg-white px-4 py-2 text-sm font-black" type="submit">Salir</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-5 py-10 pb-28">
        <section class="rounded-[2rem] bg-[#17352b] p-7 text-white sm:p-10">
            <p class="text-xs font-black uppercase tracking-[.2em] text-[#f2c66d]">Panel inicial</p>
            @if (auth()->user()->account_type->value === 'provider')
                <h1 class="mt-3 text-4xl font-black tracking-tight">Haz visible tu trabajo.</h1>
                <p class="mt-4 max-w-2xl leading-7 text-white/65">Completa tu perfil, publica tu primer producto o servicio y comienza a recibir solicitudes de tu comunidad.</p>
                <button class="mt-7 rounded-full bg-[#f2c66d] px-6 py-3 font-black text-[#17352b]" type="button">Publicar servicio</button>
            @else
                <h1 class="mt-3 text-4xl font-black tracking-tight">¿Qué necesitas resolver hoy?</h1>
                <p class="mt-4 max-w-2xl leading-7 text-white/65">Busca una opción cercana o publica una solicitud para que proveedores disponibles puedan responderte.</p>
                <button class="mt-7 rounded-full bg-[#f2c66d] px-6 py-3 font-black text-[#17352b]" type="button">Publicar solicitud</button>
            @endif
        </section>

        <section class="mt-10">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.18em] text-[#d2693c]">Explorar</p>
                    <h2 class="mt-2 text-3xl font-black">Actividad cerca de ti</h2>
                </div>
                <span class="rounded-full bg-white px-4 py-2 text-xs font-black shadow-sm">Tu comunidad</span>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ([
                    ['🌮', 'Producto', 'Tacos El Comalito', 'Servicio disponible esta tarde.'],
                    ['🔧', 'Servicio', 'Reparaciones Martínez', 'Instalaciones y reparaciones para el hogar.'],
                    ['📣', 'Solicitud', 'Se busca electricista', 'Trabajo para revisar una instalación esta semana.'],
                ] as [$icon, $type, $title, $copy])
                    <article class="rounded-3xl border border-[#17352b]/10 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <span class="grid size-12 place-items-center rounded-2xl bg-[#f7f5ef] text-2xl">{{ $icon }}</span>
                            <span class="rounded-full bg-[#e6f1eb] px-3 py-1 text-xs font-black text-[#1f6b4f]">{{ $type }}</span>
                        </div>
                        <h3 class="mt-5 text-lg font-black">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-[#6f827b]">{{ $copy }}</p>
                        <button class="mt-5 text-sm font-black text-[#d2693c]" type="button">Ver detalles →</button>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-[#17352b]/10 bg-white/95 px-3 py-2 backdrop-blur-xl" aria-label="Navegación de la aplicación">
        <div class="mx-auto grid max-w-lg grid-cols-5">
            @foreach ([['⌂', 'Inicio'], ['⌕', 'Buscar'], ['＋', 'Publicar'], ['✉', 'Mensajes'], ['○', 'Perfil']] as [$icon, $label])
                <button class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-xs font-black {{ $label === 'Inicio' ? 'text-[#d2693c]' : 'text-[#6f827b]' }}" type="button">
                    <span class="text-xl" aria-hidden="true">{{ $icon }}</span>
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </nav>
</body>
</html>
