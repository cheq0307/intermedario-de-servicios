<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Promociones - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#FAF8F4] pb-24 text-[#17313A] antialiased">
<x-market-nav :back-url="route('more.index')" />
<main class="mx-auto max-w-4xl px-4 py-7 sm:px-6">
    <div><p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Visibilidad pagada</p><h1 class="mt-1 text-3xl font-black">Promociones contratadas</h1><p class="mt-2 font-semibold text-[#6B7D83]">Una promoción mejora la posición de una oferta, pero nunca modifica su reputación ni la convierte en verificada.</p></div>
    @if(session('status'))<div class="mt-5 rounded-2xl bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mt-5 rounded-2xl bg-red-50 px-5 py-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif

    <section class="mt-6 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-black">Promocionar una publicación</h2>
        <form class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]" method="POST" action="{{ route('promotions.store') }}">@csrf
            <select class="rounded-2xl bg-[#FAF8F4] px-4 py-3" name="post_id" required><option value="">Elige una oferta publicada</option>@foreach($eligiblePosts as $post)<option value="{{ $post->id }}">{{ $post->listing?->name ?: \Illuminate\Support\Str::limit($post->body, 55) }}</option>@endforeach</select>
            <select class="rounded-2xl bg-[#FAF8F4] px-4 py-3" name="duration_days" required>@foreach(config('marketplace.promotion_prices') as $days=>$amount)<option value="{{ $days }}">{{ $days }} días · ${{ number_format($amount / 100, 2) }}</option>@endforeach</select>
            <button class="rounded-full bg-[#123B4A] px-5 py-3 font-black text-white" type="submit">Continuar al pago</button>
        </form>
        <p class="mt-3 text-xs font-semibold text-[#6B7D83]">Siempre se identificará con la leyenda “Patrocinado”. El pago real se habilitará al conectar la pasarela.</p>
    </section>

    <section class="mt-6 space-y-3"><h2 class="text-xl font-black">Historial</h2>@forelse($promotions as $promotion)<article class="flex flex-wrap items-center justify-between gap-4 rounded-3xl border border-[#123B4A]/10 bg-white p-5"><div><strong>{{ $promotion->post->listing?->name ?: \Illuminate\Support\Str::limit($promotion->post->body, 65) }}</strong><p class="mt-1 text-sm font-semibold text-[#6B7D83]">{{ $promotion->duration_days }} días · ${{ number_format($promotion->amount / 100, 2) }} {{ $promotion->currency }}</p></div><span class="rounded-full bg-[#FFF1E8] px-3 py-1.5 text-xs font-black text-[#D85B0B]">{{ ['pending_payment'=>'Pendiente de pago','pending_review'=>'En revisión','active'=>'Activa','paused'=>'Pausada','ended'=>'Finalizada','rejected'=>'Rechazada'][$promotion->status] ?? $promotion->status }}</span></article>@empty<p class="rounded-3xl border border-dashed border-[#123B4A]/20 p-8 text-center font-bold text-[#6B7D83]">Todavía no has contratado promociones.</p>@endforelse{{ $promotions->links() }}</section>
</main><x-bottom-nav active="more" /></body></html>
