<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mensajes - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-brand-surface pb-24 text-brand-ink antialiased">
<x-market-nav :back-url="route('dashboard')" />
<main class="mx-auto max-w-4xl px-4 py-7 sm:px-5">
    <div><p class="text-xs font-black uppercase tracking-[.18em] text-brand-orange">Comunicación protegida</p><h1 class="mt-2 text-3xl font-black">Conversaciones</h1><p class="mt-2 text-sm font-semibold text-brand-muted">Cada publicación, operación y conversación directa conserva su propio contexto.</p></div>
    <div class="mt-5" data-live-fragment data-live-fragment-url="{{ request()->fullUrl() }}" data-live-fragment-revision="{{ $revision }}" data-live-fragment-interval="8000">
        @include('conversations._list', ['conversations' => $conversations])
    </div>
</main><x-bottom-nav active="messages" /></body></html>
