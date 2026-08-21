<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Actividad comercial de tu comunidad en Plaza Local.">
    <title>Inicio - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen pb-24 bg-[#FAF8F4] text-[#17313A] antialiased selection:bg-[#F97316]/25">
    @php
        $currentUser = auth()->user();
        $isProvider = $activeMode === 'provider';
        $typeLabels = [
            'portfolio' => 'Trabajo realizado',
            'business_update' => 'Novedad',
            'product' => 'Producto',
            'service' => 'Servicio',
            'promotion' => 'Promoción',
            'job_request' => 'Busco ayuda',
        ];
        $moduleLabels = [
            'food' => 'Comida',
            'services' => 'Servicios',
            'products' => 'Productos',
            'transport' => 'Transporte',
        ];
        $feedLabels = [
            'for_you' => 'Para ti',
            'offers' => 'Ofertas',
            'requests' => 'Solicitudes',
        ];
    @endphp

    <x-market-nav />

    <main class="mx-auto grid max-w-5xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[minmax(0,680px)_280px]">
        <div id="inicio" class="min-w-0 space-y-5">
            <nav class="-mx-4 flex gap-6 overflow-x-auto border-b border-[#123B4A]/10 px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:mx-0 sm:px-0" aria-label="Módulos del marketplace">
                @foreach($moduleLabels as $moduleKey => $moduleLabel)
                    <a class="relative shrink-0 px-0.5 pb-3 pt-1 text-sm font-bold transition {{ $module === $moduleKey ? 'text-[#123B4A]' : 'text-[#6B7D83] hover:text-[#123B4A]' }}" href="{{ route('dashboard', ['module' => $moduleKey, 'feed' => $feed]) }}#actividad" @if($module === $moduleKey) aria-current="page" @endif>
                        {{ $moduleLabel }}
                        @if($module === $moduleKey)<span class="absolute inset-x-0 bottom-0 h-0.5 rounded-full bg-[#F97316]"></span>@endif
                    </a>
                @endforeach
            </nav>
            @if (session('status'))
                <div class="rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if (! $currentUser->hasVerifiedEmail())
                <div class="rounded-2xl border border-[#F97316]/20 bg-[#FFF1E8] px-5 py-4" role="status">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-black text-[#A94708]">Verifica tu correo</p><p class="mt-1 text-sm font-semibold text-[#8A6A55]">Te enviamos un enlace a {{ $currentUser->email }}. Podr&aacute;s explorar y completar tu perfil, pero necesitas verificarlo antes de publicar.</p></div>
                        <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="shrink-0 rounded-full bg-[#F97316] px-5 py-2.5 text-sm font-black text-white" type="submit">Reenviar correo</button></form>
                    </div>
                </div>
            @endif

            @if($isProvider && $currentUser->vendor?->status !== 'active')
                <div class="rounded-2xl border border-[#F5D48D] bg-[#FFF8E6] px-5 py-4 text-sm font-bold leading-6 text-[#79551E]" role="status">
                    @switch($currentUser->vendor?->status)
                        @case('draft') Tu perfil está en borrador. Complétalo y envía la solicitud de verificación desde “Editar perfil”. @break
                        @case('pending') Tu solicitud de proveedor está en revisión. Publicar ofertas y enviar propuestas permanecerá bloqueado hasta que sea aprobada. @break
                        @case('rejected') Tu solicitud necesita cambios: {{ $currentUser->vendor?->rejection_reason }} @break
                        @case('suspended') Tu perfil comercial está suspendido. <a class="ml-1 font-black underline" href="{{ route('support.create', ['category' => 'provider_suspension']) }}">Contactar soporte y solicitar revisión</a>. @break
                        @default Completa y envía tu perfil comercial para solicitar la verificación. @break
                    @endswitch
                    <a class="ml-1 underline" href="{{ route('profile.edit') }}">Editar perfil</a>
                </div>
            @endif

            @if($feed === 'for_you' && $showcaseSections->isNotEmpty())
                <section class="space-y-7 rounded-[2rem] border border-[#123B4A]/10 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="escaparate-local">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Escaparate local</p>
                            <h2 id="escaparate-local" class="mt-1 text-2xl font-black">Descubre algo cerca de ti</h2>
                        </div>
                        <a class="shrink-0 text-sm font-black text-[#14734A]" href="{{ route('explore') }}">Ver todo →</a>
                    </div>

                    @foreach($showcaseSections as $section)
                        @php
                            $carouselId = 'escaparate-'.$section['category']->id;
                        @endphp
                        <div data-market-carousel>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <h3 class="truncate text-lg font-black">{{ $section['category']->name }}</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a class="mr-1 hidden text-xs font-black text-[#14734A] sm:inline" href="{{ route('explore', ['category_id' => $section['category']->id]) }}">Ver categoría</a>
                                    <button class="hidden size-9 place-items-center rounded-full border border-[#123B4A]/10 text-lg font-black transition hover:bg-[#E9F7F0] sm:grid" type="button" data-carousel-prev aria-label="Ver ofertas anteriores de {{ $section['category']->name }}">‹</button>
                                    <button class="hidden size-9 place-items-center rounded-full border border-[#123B4A]/10 text-lg font-black transition hover:bg-[#E9F7F0] sm:grid" type="button" data-carousel-next aria-label="Ver más ofertas de {{ $section['category']->name }}">›</button>
                                </div>
                            </div>
                            <div id="{{ $carouselId }}" class="flex snap-x snap-mandatory gap-3 overflow-x-auto pb-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-carousel-track tabindex="0" aria-label="Ofertas de {{ $section['category']->name }}">
                                @foreach($section['listings'] as $listing)
                                    @php
                                        $image = $listing->post?->media?->firstWhere('type', 'image');
                                        $canBuy = $listing->type->value === 'product' && $listing->price_type->value === 'fixed' && $listing->price_amount !== null;
                                        $destination = $canBuy ? route('products.checkout', $listing) : route('profile.show', $listing->vendor->user);
                                    @endphp
                                    <a class="group w-[72vw] max-w-[17rem] shrink-0 snap-start overflow-hidden rounded-[1.4rem] border border-[#123B4A]/10 bg-[#FAF8F4] transition hover:-translate-y-1 hover:shadow-lg sm:w-[15rem]" href="{{ $destination }}">
                                        <div class="relative h-36 overflow-hidden bg-gradient-to-br from-[#E9F7F0] via-[#FFF7E9] to-[#F9D889]">
                                            @if($image)
                                                <img class="size-full object-cover transition duration-500 group-hover:scale-105" src="{{ $image->url }}" alt="{{ $image->alt_text ?: $listing->name }}" loading="lazy">
                                            @else
                                                <span class="grid size-full place-items-center text-sm font-black text-[#6B7D83]">Sin imagen</span>
                                            @endif
                                            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-[#123B4A] shadow-sm">{{ $listing->type->value === 'product' ? 'Producto' : 'Servicio' }}</span>
                                            @if($listing->post?->activePromotion)<span class="absolute right-3 top-3 rounded-full bg-[#17313A]/90 px-2.5 py-1 text-[10px] font-black text-white shadow-sm">Patrocinado</span>@endif
                                        </div>
                                        <div class="p-4">
                                            <h4 class="line-clamp-2 min-h-12 font-black leading-6">{{ $listing->name }}</h4>
                                            <p class="mt-1 truncate text-xs font-bold text-[#6B7D83]">{{ $listing->vendor->display_name }} · {{ $listing->vendor->user->community?->name ?? 'Tu comunidad' }}</p>
                                            <div class="mt-3 flex items-center justify-between gap-2">
                                                <strong class="truncate text-sm text-[#14734A]">@if($listing->price_amount === null)Cotizar @else{{ $listing->price_type->value === 'starting_at' ? 'Desde ' : '' }}${{ number_format($listing->price_amount / 100, 2) }}@endif</strong>
                                                <span class="shrink-0 rounded-full bg-[#123B4A] px-3 py-1.5 text-[11px] font-black text-white">{{ $canBuy ? 'Comprar' : 'Ver' }}</span>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif

            @if($publishAs === 'choose')
            <section id="publicar" class="scroll-mt-24 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Crear publicación</p>
                <h2 class="mt-2 text-2xl font-black">¿Qué quieres publicar?</h2>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <a class="rounded-2xl border p-5 hover:bg-[#E9F7F0]" href="{{ route('dashboard',['publicar'=>'offer']).'#crear-publicacion' }}"><strong class="block">Vender un producto</strong><span class="mt-1 block text-sm text-[#536A72]">Comisión al concretar la venta.</span></a>
                    <a class="rounded-2xl border p-5 hover:bg-[#E9F7F0]" href="{{ route('dashboard',['publicar'=>'offer']).'#crear-publicacion' }}"><strong class="block">Ofrecer un servicio</strong><span class="mt-1 block text-sm text-[#536A72]">Comisión al ser contratado.</span></a>
                    <a class="rounded-2xl border p-5 hover:bg-[#FFF1E8]" href="{{ route('dashboard',['publicar'=>'request']).'#crear-publicacion' }}"><strong class="block">Solicitar algo</strong><span class="mt-1 block text-sm text-[#536A72]">Pide un producto o servicio.</span></a>
                    <a class="rounded-2xl border border-[#E6A700]/30 bg-[#FFF9E7] p-5" href="{{ route('vacancies.create') }}"><strong class="block text-[#8B5B00]">Publicar vacante de empleo</strong><span class="mt-1 block text-sm text-[#79551E]">La tarifa se muestra antes del pago.</span></a>
                </div>
            </section>
            @endif
            @if($showComposer)
            <section id="crear-publicacion" class="scroll-mt-24 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-full bg-[#DCEAE6] font-black text-[#123B4A]">{{ mb_strtoupper(mb_substr($currentUser->name, 0, 1)) }}</span>
                    <div>
                        <h2 class="font-black">Crea una publicación</h2>
                        <p class="text-xs font-semibold text-[#6B7D83]">Aparecerá en la actividad de tu comunidad.</p>
                    </div>
                </div>

                <form class="mt-5 space-y-4" method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data" data-publication-form>
                    @csrf
                    <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
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

                    @if ($isProvider)
                        <div class="grid gap-4 sm:grid-cols-2" data-listing-fields>
                            <label class="block sm:col-span-2">
                                <span class="text-sm font-black">Nombre del producto o servicio</span>
                                <input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold outline-none focus:border-[#F97316]/50 focus:ring-4 focus:ring-[#F97316]/10" type="text" name="title" value="{{ old('title') }}" maxlength="120" placeholder="Ej. Instalación eléctrica o Tacos al pastor" data-listing-required>
                                @error('title') <span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-black">Forma de precio</span>
                                <select class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold outline-none focus:border-[#F97316]/50" name="price_type" data-price-type data-listing-required>
                                    <option value="fixed" @selected(old('price_type') === 'fixed')>Precio fijo</option>
                                    <option value="starting_at" @selected(old('price_type') === 'starting_at')>Desde</option>
                                    <option value="quote" @selected(old('price_type') === 'quote')>Requiere cotización</option>
                                </select>
                                @error('price_type') <span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block" data-price-field>
                                <span class="text-sm font-black">Precio en MXN</span>
                                <input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold outline-none focus:border-[#F97316]/50" type="number" name="price" value="{{ old('price') }}" min="0" step="0.01" inputmode="decimal" placeholder="0.00" data-price-input>
                                @error('price') <span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block" data-product-field>
                                <span class="text-sm font-black">Existencias disponibles</span>
                                <input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold outline-none focus:border-[#F97316]/50" type="number" name="stock" value="{{ old('stock') }}" min="0" step="1" inputmode="numeric" placeholder="Opcional">

                                @error('stock') <span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block sm:col-span-2">
                                <span class="text-sm font-black">¿Qué necesitas?</span>
                                <input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold outline-none focus:border-[#F97316]/50 focus:ring-4 focus:ring-[#F97316]/10" type="text" name="title" value="{{ old('title') }}" minlength="5" maxlength="120" required placeholder="Ej. Busco plomero para reparar una fuga">
                                @error('title') <span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-black">Presupuesto mínimo</span>
                                <input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold outline-none focus:border-[#F97316]/50" type="number" name="budget_min" value="{{ old('budget_min') }}" min="0" step="0.01" inputmode="decimal" placeholder="$ MXN">
                                @error('budget_min') <span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-black">Presupuesto máximo</span>
                                <input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold outline-none focus:border-[#F97316]/50" type="number" name="budget_max" value="{{ old('budget_max') }}" min="0" step="0.01" inputmode="decimal" placeholder="$ MXN">
                                @error('budget_max') <span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-black">¿Para cuándo?</span>
                                <select class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold outline-none focus:border-[#F97316]/50" name="urgency" required>
                                    <option value="normal" @selected(old('urgency') === 'normal')>Sin prisa</option>
                                    <option value="soon" @selected(old('urgency') === 'soon')>En los próximos días</option>
                                    <option value="urgent" @selected(old('urgency') === 'urgent')>Es urgente</option>
                                </select>
                                @error('urgency') <span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="text-sm font-black">Zona aproximada</span>
                                <input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold outline-none focus:border-[#F97316]/50" type="text" name="location_label" value="{{ old('location_label') }}" maxlength="120" placeholder="Colonia, barrio o referencia">
                                @error('location_label') <span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    @endif

                    <label class="block">
                        <span class="text-sm font-black">Rubro o temática</span>
                        <select class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold" name="category_id" required><option value="">Selecciona un rubro</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
                        @error('category_id')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror
                    </label>
                    @unless($isProvider)
                        <fieldset><legend class="text-sm font-black">Localidades donde quieres recibir propuestas</legend><p class="mt-1 text-xs font-semibold text-[#6B7D83]">Las personas del mismo rubro también podrán verla fuera de esta selección.</p><div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach($communities as $community)<label class="cursor-pointer"><input class="peer sr-only" type="checkbox" name="community_ids[]" value="{{ $community->id }}" @checked(in_array($community->id, old('community_ids', [$currentUser->community_id])))><span class="block rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-black peer-checked:border-[#14734A] peer-checked:bg-[#E9F7F0] peer-checked:text-[#14734A]">{{ $community->name }}<small class="mt-1 block font-semibold">{{ $community->municipality }}</small></span></label>@endforeach
                        </div>@error('community_ids')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</fieldset>
                    @endunless
                    <textarea class="min-h-28 w-full resize-y rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold leading-6 outline-none transition placeholder:text-[#8A999E] focus:border-[#F97316]/50 focus:ring-4 focus:ring-[#F97316]/10" name="body" maxlength="1500" required placeholder="{{ $isProvider ? 'Describe lo que ofreces, disponibilidad, entrega y zona de atención…' : 'Explica los detalles necesarios para que los proveedores puedan responderte…' }}">{{ old('body') }}</textarea>
                    @error('body') <p class="text-sm font-bold text-red-600">{{ $message }}</p> @enderror
                    <label class="block rounded-2xl border border-dashed border-[#123B4A]/20 bg-[#FAF8F4] p-4">
                        <span class="text-sm font-black">Fotos o videos</span>
                        <span class="mt-1 block text-xs font-semibold text-[#6B7D83]">Hasta 6 archivos. Maximo 50 MB por archivo.</span>
                        <input class="mt-3 block w-full text-sm" type="file" name="media[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm" multiple data-media-input>
                    </label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3" data-media-preview hidden></div>
                    @error('media') <p class="text-sm font-bold text-red-600">{{ $message }}</p> @enderror
                    @error('media.*') <p class="text-sm font-bold text-red-600">{{ $message }}</p> @enderror

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs font-semibold text-[#8A999E]">La ubicación exacta nunca se mostrará públicamente.</p>
                        <button class="shrink-0 rounded-full bg-[#F97316] px-5 py-2.5 text-sm font-black text-white transition hover:bg-[#E8660C] disabled:cursor-wait disabled:opacity-60" type="submit" data-submit-button>Publicar</button>
                    </div>
                </form>
            </section>

            @endif
            <section id="actividad" class="scroll-mt-24 space-y-4">
                <div class="px-1 pt-1">
                    <h2 class="text-xl font-black">{{ $feedLabels[$feed] ?? 'Para ti' }}</h2>
                    @if($currentUser->community)<p class="mt-1 text-xs font-semibold text-[#6B7D83]">{{ $currentUser->community->name }} · lo más relevante primero</p>@endif
                </div>

                <nav class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Filtros de actividad">
                    @foreach($feedLabels as $feedKey => $feedLabel)
                        <a class="shrink-0 rounded-full border px-4 py-2 text-xs font-bold transition {{ $feed === $feedKey ? 'border-[#F97316] bg-[#F97316] text-white' : 'border-[#123B4A]/10 bg-white text-[#536A72] hover:border-[#F97316]/40 hover:text-[#D85B0B]' }}" href="{{ route('dashboard', ['module' => $module, 'feed' => $feedKey]).'#actividad' }}" @if($feed === $feedKey) aria-current="page" @endif>{{ $feedLabel }}</a>
                    @endforeach
                </nav>

                @forelse ($posts as $post)
                    <article id="post-{{ $post->id }}" class="scroll-mt-24 overflow-hidden rounded-[1.5rem] border border-[#123B4A]/10 bg-white shadow-[0_4px_18px_rgba(18,59,74,.05)]">
                        <div class="p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <a class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-full bg-[#DCEAE6] font-black text-[#123B4A]" href="{{ route('profile.show', $post->user) }}" aria-label="Ver perfil de {{ $post->user->name }}">
                                        @if ($post->user->avatar_path)
                                            <img class="size-full object-cover" src="{{ asset('storage/'.$post->user->avatar_path) }}" alt="">
                                        @else
                                            {{ mb_strtoupper(mb_substr($post->user->name, 0, 1)) }}
                                        @endif
                                    </a>
                                    <div class="min-w-0">
                                        <h3 class="truncate font-black"><a class="hover:text-[#F97316]" href="{{ route('profile.show', $post->user) }}">{{ $post->user->name }}</a></h3>
                                        <p class="mt-0.5 text-xs font-semibold text-[#6B7D83]">{{ $post->published_at->diffForHumans() }} · {{ $post->type === 'job_request' ? 'Solicitud' : ($typeLabels[$post->type] ?? 'Oferta') }}@if($post->user->community) · {{ $post->user->community->name }}@endif</p>
                                    </div>
                                </div>
                                <details class="relative shrink-0">
                                    <summary class="grid size-9 cursor-pointer list-none place-items-center rounded-full text-xl leading-none text-[#6B7D83] hover:bg-[#F4F6F5]" aria-label="Opciones de la publicación">···</summary>
                                    <div class="absolute right-0 top-10 z-20 w-44 rounded-2xl border border-[#123B4A]/10 bg-white p-2 shadow-xl">
                                        <a class="block rounded-xl px-3 py-2 text-sm font-bold hover:bg-[#FAF8F4]" href="{{ route('profile.show', $post->user) }}">Ver perfil</a>
                                        @if($post->user_id === $currentUser->id)<a class="block rounded-xl px-3 py-2 text-sm font-bold hover:bg-[#FAF8F4]" href="{{ route('posts.edit', $post) }}">Editar publicación</a>@endif
                                    </div>
                                </details>
                            </div>

                            <p class="mt-4 whitespace-pre-line text-[15px] font-medium leading-7 text-[#314B54]">{{ $post->body }}</p>
                            @if($post->media->isNotEmpty())
                                <div class="mt-5 grid gap-2 {{ $post->media->count() > 1 ? 'grid-cols-2' : 'grid-cols-1' }}">
                                    @foreach($post->media as $media)
                                        @if($media->type === 'video')
                                            <video class="max-h-[32rem] w-full rounded-2xl bg-black object-contain {{ $post->media->count() === 3 && $loop->first ? 'col-span-2' : '' }}" controls preload="metadata"><source src="{{ $media->url }}"></video>
                                        @else
                                            <img class="max-h-[32rem] w-full rounded-2xl object-cover {{ $post->media->count() === 3 && $loop->first ? 'col-span-2' : '' }}" src="{{ $media->url }}" alt="{{ $media->alt_text ?: 'Imagen de la publicacion' }}" loading="lazy">
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            @if ($post->listing)
                                <div class="mt-4 border-t border-[#123B4A]/10 pt-4">
                                    <h4 class="text-lg font-black text-[#123B4A]">{{ $post->listing->name }}</h4>
                                    <p class="mt-1 text-sm font-black text-[#14734A]">
                                        @if ($post->listing->price_type->value === 'quote')
                                            Cotización
                                        @else
                                            {{ $post->listing->price_type->value === 'starting_at' ? 'Desde ' : '' }}${{ number_format($post->listing->price_amount / 100, 2) }} MXN
                                        @endif
                                    </p>
                                    @if ($post->type === 'product' && $post->listing->stock !== null)<p class="mt-1 text-xs font-bold text-[#6B7D83]">{{ $post->listing->stock }} unidades disponibles</p>@endif
                                </div>
                            @elseif ($post->jobRequest)
                                @php
                                    $urgencyLabels = ['normal' => 'Sin prisa', 'soon' => 'Próximos días', 'urgent' => 'Urgente'];
                                    $minimumBudget = $post->jobRequest->budget_min_amount;
                                    $maximumBudget = $post->jobRequest->budget_max_amount;
                                @endphp
                                <div class="mt-4 border-t border-[#123B4A]/10 pt-4">
                                    <h4 class="text-lg font-black text-[#123B4A]">{{ $post->jobRequest->title }}</h4>
                                    @if ($minimumBudget !== null || $maximumBudget !== null)
                                        <p class="mt-1 text-sm font-black text-[#14734A]">
                                            Presupuesto:
                                            @if ($minimumBudget !== null && $maximumBudget !== null)
                                                ${{ number_format($minimumBudget / 100, 2) }}–${{ number_format($maximumBudget / 100, 2) }} MXN
                                            @elseif ($maximumBudget !== null)
                                                Hasta ${{ number_format($maximumBudget / 100, 2) }} MXN
                                            @else
                                                Desde ${{ number_format($minimumBudget / 100, 2) }} MXN
                                            @endif
                                        </p>
                                    @endif
                                    <p class="mt-1 text-xs font-semibold text-[#6B7D83]">{{ $urgencyLabels[$post->jobRequest->urgency] ?? 'Sin prisa' }}@if($post->jobRequest->location_label) · {{ $post->jobRequest->location_label }}@endif</p>
                                </div>
                            @endif


                        </div>
                        <div class="border-t border-[#123B4A]/8 px-4 pt-2">
                            <div class="flex min-h-11 items-center justify-end text-xs font-black text-[#536A72]">
                            @if ($post->user_id === $currentUser->id)

                                @if ($post->jobRequest)
                                    <a class="rounded-xl px-3 py-2.5 text-center transition hover:bg-[#FAF8F4] hover:text-[#F97316]" href="{{ route('job-proposals.index', $post->jobRequest) }}">Ver propuestas</a>
                                @else
                                    <button class="rounded-xl px-3 py-2.5 transition hover:bg-[#FAF8F4] hover:text-[#F97316]" type="button">Ver respuestas</button>
                                @endif
                            @else
                                @if ($post->jobRequest && $isProvider)
                                    <a class="rounded-xl px-3 py-2.5 text-center transition hover:bg-[#FAF8F4] hover:text-[#F97316]" href="{{ route('job-proposals.index', $post->jobRequest) }}">Enviar propuesta</a>
                                @elseif ($post->jobRequest)
                                    <span class="grid size-10 place-items-center rounded-full text-[#8A999E]" title="Disponible para personas que ofrecen este servicio" aria-label="Disponible para personas que ofrecen este servicio">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7V5.8A1.8 1.8 0 0 1 10.8 4h2.4A1.8 1.8 0 0 1 15 5.8V7m-9 0h12a2 2 0 0 1 2 2v8.5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Zm-2 5h16M10 12v2h4v-2"/></svg>
                                    </span>
                                @elseif ($post->listing?->type?->value === 'product' && $post->listing?->price_type?->value === 'fixed')
                                    <a class="rounded-xl px-3 py-2.5 text-center transition hover:bg-[#FAF8F4] hover:text-[#F97316]" href="{{ route('products.checkout', $post->listing) }}">
                                        Comprar
                                    </a>
                                @else
                                    <button class="rounded-xl px-3 py-2.5 transition hover:bg-[#FAF8F4] hover:text-[#F97316]" type="button">
                                        {{ $post->listing?->price_type?->value === 'quote' ? 'Solicitar cotización' : 'Me interesa' }}
                                    </button>
                                @endif
                            @endif
                            </div>
                            <div class="flex items-center gap-1 border-t border-[#123B4A]/8 py-2 text-[#123B4A]">
                                <form method="POST" action="{{ route('posts.reactions.toggle', $post) }}">
                                    @csrf
                                    <button class="group flex min-h-11 items-center gap-2 rounded-full px-3 transition hover:bg-[#FFF1E8] hover:text-[#D85B0B] {{ $post->reacted_by_user ? 'text-[#E24B35]' : '' }}" type="submit" aria-pressed="{{ $post->reacted_by_user ? 'true' : 'false' }}" title="{{ $post->reacted_by_user ? 'Retirar Me gusta' : 'Marcar con Me gusta' }}">
                                        <svg class="size-6 transition group-hover:scale-110" viewBox="0 0 24 24" fill="{{ $post->reacted_by_user ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/></svg>
                                        <span class="text-sm font-black">{{ $post->reactions_count }}</span>
                                        <span class="sr-only">{{ $post->reacted_by_user ? 'Quitar Me gusta' : 'Me gusta' }}</span>
                                    </button>
                                </form>
                                <button class="group flex min-h-11 items-center gap-2 rounded-full px-3 transition hover:bg-[#E9F7F0] hover:text-[#14734A]" type="button" data-comment-toggle="comment-{{ $post->id }}" title="Comentar">
                                    <svg class="size-6 transition group-hover:scale-110" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.4 8.4 0 0 1-9 8.5 9.7 9.7 0 0 1-4-.9L3 21l1.7-4.5A8.3 8.3 0 1 1 21 11.5Z"/></svg>
                                    <span class="text-sm font-black">{{ $post->comments_count }}</span>
                                    <span class="sr-only">Comentar</span>
                                </button>
                                <form method="POST" action="{{ route('posts.shares.store', $post) }}" data-share-form data-share-url="{{ route('dashboard').'#post-'.$post->id }}" data-share-title="{{ $post->user->name }} en Plaza Local">
                                    @csrf
                                    <input type="hidden" name="channel" value="native">
                                    <button class="group flex min-h-11 items-center gap-2 rounded-full px-3 transition hover:bg-[#EAF3F7] hover:text-[#123B4A]" type="submit" title="Compartir">
                                        <svg class="size-6 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m22 2-7 20-4-9-9-4 20-7Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M22 2 11 13"/></svg>
                                        <span class="text-sm font-black">{{ $post->shares_count }}</span>
                                        <span class="sr-only">Compartir</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <section id="comment-{{ $post->id }}" class="border-t border-[#123B4A]/8 bg-[#FAF8F4]/60 p-4" data-comment-panel>
                            @foreach($post->comments as $comment)
                                <div class="mb-3 flex items-start justify-between gap-3 rounded-2xl bg-white px-4 py-3"><div><strong class="text-sm">{{ $comment->user->name }}</strong><p class="mt-1 line-clamp-3 text-sm leading-6 text-[#536A72]">{{ $comment->body }}</p>@if($comment->updated_at->gt($comment->created_at))<span class="text-[10px] font-bold text-[#8A999E]">Editado</span>@endif</div>@if($comment->user_id === $currentUser->id || $post->user_id === $currentUser->id || $currentUser->hasAnyRole(['admin','superadmin']))<form method="POST" action="{{ route('posts.comments.destroy', $comment) }}">@csrf @method('DELETE')<button class="text-xs font-black text-red-600" type="submit">Eliminar</button></form>@endif</div>
                            @endforeach
                            @if($post->comments_count > $post->comments->count())<a class="mb-3 block text-sm font-black text-[#14734A]" href="{{ route('posts.comments.index', $post) }}">Ver los {{ $post->comments_count }} comentarios</a>@endif
                            @if($post->comments_enabled)<form class="flex gap-2" method="POST" action="{{ route('posts.comments.store', $post) }}">@csrf<input class="min-w-0 flex-1 rounded-full border border-[#123B4A]/10 bg-white px-4 py-2.5 text-sm outline-none" name="body" maxlength="1000" required placeholder="Escribe un comentario"><button class="rounded-full bg-[#123B4A] px-4 py-2 text-xs font-black text-white" type="submit">Publicar</button></form>@endif
                        </section>
                    </article>
                @empty
                    <div class="rounded-[1.75rem] border border-dashed border-[#123B4A]/20 bg-white/60 px-6 py-12 text-center">
                        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-[#FFF1E8] text-2xl font-black text-[#F97316]">+</span>
                        <h3 class="mt-4 text-lg font-black">Sé la primera publicación</h3>
                        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-[#6B7D83]">No hay publicaciones en esta sección todavía. Puedes explorar otra pestaña o crear la primera.</p>
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
                    <p class="text-xs font-black uppercase tracking-[.16em] text-[#8B5117]">Compra protegida</p>
                    <h2 class="mt-2 text-lg font-black">Acuerdos dentro de Plaza Local</h2>
                    <p class="mt-2 text-sm font-semibold leading-6 text-[#6D522D]">Las propuestas, pagos de prueba, entregas y disputas quedan registradas para proteger a ambas partes.</p>
                </section>
            </div>
        </aside>
    </main>

    <x-bottom-nav active="home" />
</body>
</html>
