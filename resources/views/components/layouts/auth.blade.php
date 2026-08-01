<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Acceso seguro a Plaza Local.">
    <title>{{ $title ?? 'Plaza Local' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f5ef] text-[#17352b] antialiased">
    <main class="grid min-h-screen lg:grid-cols-[.9fr_1.1fr]">
        <section class="relative hidden overflow-hidden bg-[#17352b] p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-32 -top-32 size-96 rounded-full bg-[#f2c66d]/20 blur-3xl"></div>
            <a href="{{ route('home') }}" class="relative flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-2xl bg-[#f2c66d] text-xl font-black text-[#17352b]">P</span>
                <span class="text-xl font-black">Plaza Local</span>
            </a>
            <div class="relative max-w-xl">
                <p class="text-xs font-black uppercase tracking-[.22em] text-[#f2c66d]">Tu comunidad, conectada</p>
                <h1 class="mt-5 text-5xl font-black leading-tight tracking-[-.045em]">Encuentra oportunidades a unos pasos de ti.</h1>
                <p class="mt-6 max-w-lg text-lg leading-8 text-white/65">Compra, ofrece tus servicios y construye una reputación basada en trabajos reales.</p>
            </div>
            <p class="relative text-sm font-semibold text-white/45">Productos, servicios y personas de confianza.</p>
        </section>

        <section class="flex items-center justify-center px-5 py-10 sm:px-10">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-10 flex items-center gap-3 lg:hidden">
                    <span class="grid size-10 place-items-center rounded-xl bg-[#1f6b4f] font-black text-white">P</span>
                    <span class="text-lg font-black">Plaza Local</span>
                </a>
                {{ $slot }}
            </div>
        </section>
    </main>
</body>
</html>
