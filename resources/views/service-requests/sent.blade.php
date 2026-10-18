<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Solicitud enviada - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-brand-cream pb-24 text-brand-body antialiased">
<x-market-nav :back-url="route('dashboard')" />
<main class="mx-auto max-w-xl px-4 py-8 sm:py-12">
    <section class="rounded-[2rem] border border-brand-line bg-white p-6 text-center shadow-profile-card sm:p-9">
        <span class="mx-auto grid size-20 place-items-center rounded-[1.75rem] bg-brand-success-soft text-4xl text-brand-success">✓</span>
        <p class="mt-6 text-xs font-black uppercase tracking-[.16em] text-brand-orange">Solicitud enviada</p>
        <h1 class="mt-2 text-3xl font-black text-brand-deep">¡Ya se la enviamos a {{ $jobRequest->preferredProvider->vendor?->display_name ?? $jobRequest->preferredProvider->name }}!</h1>
        <p class="mx-auto mt-3 max-w-md text-sm font-semibold leading-6 text-brand-muted-warm">Recibirá una notificación para revisar “{{ $jobRequest->title }}”. Cuando responda, podrás comparar y aceptar su propuesta.</p>

        <ol class="mt-7 space-y-3 text-left" aria-label="Seguimiento de la solicitud">
            <li class="flex gap-3 rounded-2xl bg-brand-success-soft p-4"><span class="font-black text-brand-success">✓</span><div><strong class="block">Solicitud enviada</strong><span class="text-sm font-semibold text-brand-muted-warm">{{ $jobRequest->created_at->format('d/m/Y H:i') }}</span></div></li>
            <li class="flex gap-3 rounded-2xl bg-brand-surface p-4"><span class="font-black text-brand-orange">2</span><div><strong class="block">La persona revisará los detalles</strong><span class="text-sm font-semibold text-brand-muted-warm">Te notificaremos cuando responda.</span></div></li>
            <li class="flex gap-3 rounded-2xl border border-brand-line p-4"><span class="font-black text-brand-muted-warm">3</span><div><strong class="block">Coordinen y registren el acuerdo</strong><span class="text-sm font-semibold text-brand-muted-warm">El chat y el seguimiento aparecerán al aceptar la propuesta.</span></div></li>
        </ol>

        <div class="mt-7 grid gap-3 sm:grid-cols-2"><a class="inline-flex min-h-12 items-center justify-center rounded-full bg-brand-coral px-5 font-black text-white" href="{{ route('job-proposals.index', $jobRequest) }}">Ver solicitud</a><a class="inline-flex min-h-12 items-center justify-center rounded-full border border-brand-line px-5 font-black text-brand-deep" href="{{ route('dashboard') }}">Explorar más</a></div>
    </section>
</main>
<x-bottom-nav active="orders" />
</body></html>
