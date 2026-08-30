<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar publicación - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-surface text-brand-ink antialiased">
    <x-market-nav :back-url="route('dashboard')" />
    <main class="mx-auto max-w-3xl px-5 py-9">
        <p class="text-xs font-black uppercase tracking-[.18em] text-brand-orange">Tu publicación</p>
        <h1 class="mt-2 text-3xl font-black">Editar información</h1>
        <p class="mt-3 leading-7 text-brand-muted">Los cambios afectan la publicación futura. Los pedidos ya creados conservan el precio y las condiciones acordadas.</p>

        <form class="mt-7 space-y-5 rounded-[2rem] border border-brand/10 bg-white p-6 shadow-sm sm:p-8" method="POST" action="{{ route('posts.update', $post) }}">
            @csrf @method('PUT')
            @if($post->listing)
                <label class="block"><span class="text-sm font-black">Nombre</span><input class="mt-2 w-full rounded-2xl bg-brand-surface px-4 py-3" name="title" value="{{ old('title', $post->listing->name) }}" required maxlength="120"></label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block"><span class="text-sm font-black">Forma de precio</span><select class="mt-2 w-full rounded-2xl bg-brand-surface px-4 py-3" name="price_type"><option value="fixed" @selected(old('price_type', $post->listing->price_type->value) === 'fixed')>Precio fijo</option><option value="starting_at" @selected(old('price_type', $post->listing->price_type->value) === 'starting_at')>Desde</option><option value="quote" @selected(old('price_type', $post->listing->price_type->value) === 'quote')>Requiere cotización</option></select></label>
                    <label class="block"><span class="text-sm font-black">Precio MXN</span><input class="mt-2 w-full rounded-2xl bg-brand-surface px-4 py-3" type="number" name="price" value="{{ old('price', $post->listing->price_amount !== null ? number_format($post->listing->price_amount / 100, 2, '.', '') : '') }}" min="0" step="0.01"></label>
                    @if($post->listing->type->value === 'product')<label class="block"><span class="text-sm font-black">Existencias disponibles</span><input class="mt-2 w-full rounded-2xl bg-brand-surface px-4 py-3" type="number" name="stock" value="{{ old('stock', $post->listing->stock) }}" min="0" step="1"></label>@endif
                </div>
            @elseif($post->jobRequest)
                <label class="block"><span class="text-sm font-black">¿Qué necesitas?</span><input class="mt-2 w-full rounded-2xl bg-brand-surface px-4 py-3" name="title" value="{{ old('title', $post->jobRequest->title) }}" required maxlength="120"></label>
                <div class="grid gap-4 sm:grid-cols-2"><label class="block"><span class="text-sm font-black">Presupuesto mínimo</span><input class="mt-2 w-full rounded-2xl bg-brand-surface px-4 py-3" type="number" name="budget_min" value="{{ old('budget_min', $post->jobRequest->budget_min_amount !== null ? number_format($post->jobRequest->budget_min_amount / 100, 2, '.', '') : '') }}" min="0" step="0.01"></label><label class="block"><span class="text-sm font-black">Presupuesto máximo</span><input class="mt-2 w-full rounded-2xl bg-brand-surface px-4 py-3" type="number" name="budget_max" value="{{ old('budget_max', $post->jobRequest->budget_max_amount !== null ? number_format($post->jobRequest->budget_max_amount / 100, 2, '.', '') : '') }}" min="0" step="0.01"></label><label class="block"><span class="text-sm font-black">Urgencia</span><select class="mt-2 w-full rounded-2xl bg-brand-surface px-4 py-3" name="urgency"><option value="normal" @selected(old('urgency', $post->jobRequest->urgency) === 'normal')>Sin prisa</option><option value="soon" @selected(old('urgency', $post->jobRequest->urgency) === 'soon')>Pronto</option><option value="urgent" @selected(old('urgency', $post->jobRequest->urgency) === 'urgent')>Urgente</option></select></label><label class="block"><span class="text-sm font-black">Zona aproximada</span><input class="mt-2 w-full rounded-2xl bg-brand-surface px-4 py-3" name="location_label" value="{{ old('location_label', $post->jobRequest->location_label) }}" maxlength="120"></label></div>
            @endif
            <label class="block"><span class="text-sm font-black">Descripción</span><textarea class="mt-2 min-h-40 w-full rounded-2xl bg-brand-surface px-4 py-3" name="body" minlength="10" maxlength="1500" required>{{ old('body', $post->body) }}</textarea></label>
            @if($errors->any())<p class="rounded-2xl bg-red-50 p-4 text-sm font-black text-red-700">{{ $errors->first() }}</p>@endif
            <button class="w-full rounded-full bg-brand px-6 py-3.5 font-black text-white" type="submit">Guardar cambios</button>
        </form>
    </main>
</body>
</html>
