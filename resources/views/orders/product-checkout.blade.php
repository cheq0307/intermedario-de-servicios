<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprar {{ $listing->name }} - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased">
    <header class="border-b border-[#123B4A]/10 bg-white"><div class="mx-auto flex max-w-4xl items-center justify-between px-5 py-4"><a class="font-black" href="{{ route('dashboard') }}">Plaza Local</a><a class="rounded-full border border-[#123B4A]/10 px-4 py-2 text-sm font-black" href="{{ route('dashboard') }}">Volver</a></div></header>
    <main class="mx-auto grid max-w-4xl gap-6 px-5 py-9 md:grid-cols-[1fr_340px]">
        <section class="rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Compra local</p>
            <h1 class="mt-3 text-3xl font-black">{{ $listing->name }}</h1>
            <p class="mt-2 font-bold text-[#6B7D83]">Vendido por {{ $listing->vendor->display_name }}</p>
            @if($listing->description)<p class="mt-6 leading-7">{{ $listing->description }}</p>@endif
            <div class="mt-6 rounded-2xl bg-[#E9F7F0] p-4 text-sm font-bold text-[#14734A]">Existencias disponibles: {{ $listing->stock ?? 'sin configurar' }}</div>
        </section>
        <aside class="rounded-[2rem] bg-[#123B4A] p-6 text-white shadow-xl">
            <p class="text-sm font-black text-white/65">Precio unitario</p>
            <strong class="mt-1 block text-3xl">${{ number_format($listing->price_amount / 100, 2) }} MXN</strong>
            <form class="mt-7 space-y-5" method="POST" action="{{ route('products.orders.store', $listing) }}">
                @csrf
                <label class="block text-sm font-black">Cantidad<input class="mt-2 w-full rounded-2xl border-0 bg-white px-4 py-3 text-[#17313A]" type="number" name="quantity" min="1" max="{{ min(50, $listing->stock ?? 0) }}" value="{{ old('quantity', 1) }}" required></label>
                <label class="block text-sm font-black">Nota para el comercio<textarea class="mt-2 min-h-24 w-full rounded-2xl border-0 bg-white px-4 py-3 text-[#17313A]" name="buyer_notes" maxlength="1000" placeholder="Opcional">{{ old('buyer_notes') }}</textarea></label>
                @if($errors->any())<p class="rounded-2xl bg-red-100 p-3 text-sm font-black text-red-700">{{ $errors->first() }}</p>@endif
                <button class="w-full rounded-full bg-[#F97316] px-5 py-3.5 font-black text-white disabled:opacity-50" type="submit" @disabled(($listing->stock ?? 0) < 1)>Crear pedido</button>
            </form>
            <p class="mt-5 text-xs font-bold leading-5 text-white/60">Las existencias se reservan durante {{ config('marketplace.reservation_minutes') }} minutos. En esta etapa el pago es simulado y no mueve dinero real.</p>
        </aside>
    </main>
</body>
</html>
