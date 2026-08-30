<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Publicidad y anuncios - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-brand-surface pb-24 text-brand-ink antialiased">
<x-market-nav :back-url="route('more.index')" />
<main class="mx-auto max-w-4xl px-4 py-7 sm:px-6">
    <div><p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Visibilidad pagada</p><h1 class="mt-1 text-3xl font-black">Publicidad y anuncios</h1><p class="mt-2 font-semibold text-brand-muted">Promociona una publicación en el escaparate y los carruseles relevantes. Siempre se identificará como patrocinada y convivirá con contenido orgánico ordenado por intereses y comunidad.</p></div>
    @if(session('status'))<div class="mt-5 rounded-2xl bg-brand-success-soft px-5 py-4 text-sm font-black text-brand-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mt-5 rounded-2xl bg-red-50 px-5 py-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif

    <section class="mt-6 rounded-[1.75rem] border border-brand/10 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-black">Crear anuncio no invasivo</h2>
        <form class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]" method="POST" action="{{ route('promotions.store') }}">@csrf
            <select class="rounded-2xl bg-brand-surface px-4 py-3" name="post_id" required><option value="">Elige una oferta publicada</option>@foreach($eligiblePosts as $post)<option value="{{ $post->id }}">{{ $post->listing?->name ?: \Illuminate\Support\Str::limit($post->body, 55) }}</option>@endforeach</select>
            <select class="rounded-2xl bg-brand-surface px-4 py-3" name="duration_days" required>@foreach(config('marketplace.promotion_prices') as $days=>$amount)<option value="{{ $days }}">{{ $days }} días · ${{ number_format($amount / 100, 2) }}</option>@endforeach</select>
            <button class="rounded-full bg-brand px-5 py-3 font-black text-white" type="submit">Continuar al pago</button>
        </form>
        <p class="mt-3 text-xs font-semibold text-brand-muted">Siempre se identificará con la leyenda “Patrocinado”. El pago se procesa fuera de Plaza Local mediante Checkout Pro; no almacenamos datos de tarjeta.</p>
    </section>

    <section class="mt-6 space-y-3"><h2 class="text-xl font-black">Historial</h2>@forelse($promotions as $promotion)<article class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-brand/10 bg-white p-5"><div><strong>{{ $promotion->post->listing?->name ?: \Illuminate\Support\Str::limit($promotion->post->body, 65) }}</strong><p class="mt-1 text-sm font-semibold text-brand-muted">{{ $promotion->duration_days }} días · ${{ number_format($promotion->amount / 100, 2) }} {{ $promotion->currency }}</p><p class="mt-1 text-xs font-bold text-brand-muted">{{ $promotion->payment_provider === 'mercadopago' ? 'Mercado Pago' : 'Pago aún no iniciado' }}</p></div><div class="flex items-center gap-2"><span class="rounded-full bg-brand-orange-soft px-3 py-1.5 text-xs font-black text-brand-danger-warm">{{ ['pending_payment'=>'Pendiente de pago','payment_failed'=>'Pago no aprobado','pending_review'=>'En revisión','active'=>'Activa','paused'=>'Pausada','ended'=>'Finalizada','rejected'=>'Rechazada'][$promotion->status] ?? $promotion->status }}</span>@if(in_array($promotion->status, ['pending_payment','payment_failed'], true))<form method="POST" action="{{ route('promotions.checkout', $promotion) }}">@csrf<button class="rounded-full bg-brand px-4 py-2 text-xs font-black text-white" type="submit">Pagar con Mercado Pago</button></form>@endif</div></article>@empty<p class="rounded-3xl border border-dashed border-brand/20 p-8 text-center font-bold text-brand-muted">Todavía no has contratado promociones.</p>@endforelse{{ $promotions->links() }}</section>
</main><x-bottom-nav active="more" /></body></html>
