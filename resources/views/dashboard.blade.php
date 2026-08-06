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
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:flex-nowrap sm:gap-4 sm:px-6">
            <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3">
                <span class="grid size-10 place-items-center rounded-2xl bg-[#123B4A] text-lg font-black text-white shadow-[0_8px_24px_rgba(18,59,74,.2)]">P</span>
                <span class="hidden sm:block">
                    <span class="block font-black leading-none">Plaza Local</span>
                    <span class="mt-1 block text-[9px] font-black uppercase tracking-[.2em] text-[#F97316]">Cerca y confiable</span>
                </span>
            </a>

            <form class="order-last flex w-full items-center gap-3 rounded-full border border-[#123B4A]/10 bg-white px-4 py-2.5 shadow-sm sm:order-none sm:max-w-xl sm:flex-1" method="GET" action="{{ route('explore') }}">
                <svg class="size-5 shrink-0 text-[#6B7D83]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input id="global-search" class="min-w-0 flex-1 bg-transparent text-sm font-semibold outline-none placeholder:text-[#8A999E]" type="search" name="q" maxlength="100" placeholder="Buscar productos, servicios o personas">
                <button class="sr-only" type="submit">Buscar</button>
            </form>

            <div class="flex shrink-0 items-center gap-2">
                <a class="relative grid size-10 place-items-center rounded-full border border-[#123B4A]/10 bg-white font-black" href="{{ route('notifications.index') }}" aria-label="Notificaciones">🔔@if($currentUser->unreadNotifications()->count())<span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-[#F97316] px-1 text-[10px] text-white">{{ min(99, $currentUser->unreadNotifications()->count()) }}</span>@endif</a>
                <span class="hidden max-w-36 truncate text-sm font-bold text-[#536A72] md:block">{{ $currentUser->name }}</span>
                <a class="grid size-10 place-items-center rounded-full bg-[#DCEAE6] font-black text-[#123B4A] transition hover:ring-4 hover:ring-[#22A06B]/15" href="{{ route('profile.show', $currentUser) }}" aria-label="Ver mi perfil">{{ mb_strtoupper(mb_substr($currentUser->name, 0, 1)) }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="grid size-10 place-items-center rounded-full border border-[#123B4A]/10 bg-white text-[#536A72] transition hover:border-[#F97316]/30 hover:text-[#F97316] sm:hidden" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M14 3h4a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-4"/></svg></button>
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
                    ['Explorar', route('explore'), false],
                    ['Publicar', '#crear-publicacion', false],
                    ['Mensajes', route('conversations.index'), false],
                    ['Notificaciones', route('notifications.index'), false],
                    ['Mis trabajos', route('orders.index'), false],
                    ['Mi perfil', route('profile.show', $currentUser), false],
                ] as [$label, $href, $active])
                    <a class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-black transition {{ $active ? 'bg-[#123B4A] text-white shadow-lg shadow-[#123B4A]/10' : 'text-[#536A72] hover:bg-white hover:text-[#123B4A]' }}" href="{{ $href }}">
                        <span class="size-2 rounded-full {{ $active ? 'bg-[#F97316]' : 'bg-[#B8C4C7]' }}"></span>{{ $label }}
                    </a>
                @endforeach
                @if($currentUser->hasAnyRole(['admin', 'superadmin']))
                    <a class="mt-3 flex items-center gap-3 rounded-2xl bg-[#FFF1E8] px-4 py-3 text-sm font-black text-[#D85B0B]" href="{{ route('admin.index') }}">
                        <span class="size-2 rounded-full bg-[#F97316]"></span>Administración
                    </a>
                @endif
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
                    Tu perfil comercial está {{ $currentUser->vendor?->status === 'suspended' ? 'suspendido' : 'pendiente de aprobación' }}. Puedes completar tu perfil, pero publicar ofertas y enviar propuestas permanecerá bloqueado hasta la aprobación administrativa.
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

                <form class="mt-5 space-y-4" method="POST" action="{{ route('posts.store') }}" data-publication-form>
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

                    <textarea class="min-h-28 w-full resize-y rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3 text-sm font-semibold leading-6 outline-none transition placeholder:text-[#8A999E] focus:border-[#F97316]/50 focus:ring-4 focus:ring-[#F97316]/10" name="body" maxlength="1500" required placeholder="{{ $isProvider ? 'Describe lo que ofreces, disponibilidad, entrega y zona de atención…' : 'Explica los detalles necesarios para que los proveedores puedan responderte…' }}">{{ old('body') }}</textarea>
                    @error('body') <p class="text-sm font-bold text-red-600">{{ $message }}</p> @enderror

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs font-semibold text-[#8A999E]">La ubicación exacta nunca se mostrará públicamente.</p>
                        <button class="shrink-0 rounded-full bg-[#F97316] px-5 py-2.5 text-sm font-black text-white transition hover:bg-[#E8660C] disabled:cursor-wait disabled:opacity-60" type="submit" data-submit-button>Publicar</button>
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
                                    <a class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-full bg-[#DCEAE6] font-black text-[#123B4A]" href="{{ route('profile.show', $post->user) }}" aria-label="Ver perfil de {{ $post->user->name }}">
                                        @if ($post->user->avatar_path)
                                            <img class="size-full object-cover" src="{{ asset('storage/'.$post->user->avatar_path) }}" alt="">
                                        @else
                                            {{ mb_strtoupper(mb_substr($post->user->name, 0, 1)) }}
                                        @endif
                                    </a>
                                    <div class="min-w-0">
                                        <h3 class="truncate font-black"><a class="hover:text-[#F97316]" href="{{ route('profile.show', $post->user) }}">{{ $post->user->name }}</a></h3>
                                        <p class="mt-0.5 text-xs font-semibold text-[#6B7D83]">{{ $post->published_at->diffForHumans() }} · Tu comunidad</p>
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full bg-[#FFF1E8] px-3 py-1.5 text-[11px] font-black text-[#D85B0B]">{{ $typeLabels[$post->type] ?? 'Publicación' }}</span>
                            </div>

                            <p class="mt-5 whitespace-pre-line text-[15px] font-medium leading-7 text-[#314B54]">{{ $post->body }}</p>

                            @if ($post->listing)
                                <div class="mt-5 rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-black uppercase tracking-[.14em] text-[#6B7D83]">{{ $post->type === 'product' ? 'Producto disponible' : 'Servicio disponible' }}</p>
                                            <h4 class="mt-1 text-lg font-black text-[#123B4A]">{{ $post->listing->name }}</h4>
                                        </div>
                                        <span class="rounded-full bg-white px-3 py-1.5 text-sm font-black text-[#D85B0B] shadow-sm">
                                            @if ($post->listing->price_type->value === 'quote')
                                                Solicitar cotización
                                            @else
                                                {{ $post->listing->price_type->value === 'starting_at' ? 'Desde ' : '' }}${{ number_format($post->listing->price_amount / 100, 2) }} MXN
                                            @endif
                                        </span>
                                    </div>
                                    @if ($post->type === 'product' && $post->listing->stock !== null)
                                        <p class="mt-3 text-xs font-bold text-[#6B7D83]">{{ $post->listing->stock }} unidades disponibles</p>
                                    @endif
                                </div>
                            @elseif ($post->jobRequest)
                                @php
                                    $urgencyLabels = ['normal' => 'Sin prisa', 'soon' => 'Próximos días', 'urgent' => 'Urgente'];
                                    $minimumBudget = $post->jobRequest->budget_min_amount;
                                    $maximumBudget = $post->jobRequest->budget_max_amount;
                                @endphp
                                <div class="mt-5 rounded-2xl border border-[#F97316]/15 bg-[#FFF8F2] p-4">
                                    <p class="text-xs font-black uppercase tracking-[.14em] text-[#D85B0B]">Solicitud de la comunidad</p>
                                    <h4 class="mt-1 text-lg font-black text-[#123B4A]">{{ $post->jobRequest->title }}</h4>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-black">
                                        @if ($minimumBudget !== null || $maximumBudget !== null)
                                            <span class="rounded-full bg-white px-3 py-1.5 text-[#14734A] shadow-sm">
                                                Presupuesto:
                                                @if ($minimumBudget !== null && $maximumBudget !== null)
                                                    ${{ number_format($minimumBudget / 100, 2) }}–${{ number_format($maximumBudget / 100, 2) }} MXN
                                                @elseif ($maximumBudget !== null)
                                                    Hasta ${{ number_format($maximumBudget / 100, 2) }} MXN
                                                @else
                                                    Desde ${{ number_format($minimumBudget / 100, 2) }} MXN
                                                @endif
                                            </span>
                                        @endif
                                        <span class="rounded-full bg-white px-3 py-1.5 text-[#D85B0B] shadow-sm">{{ $urgencyLabels[$post->jobRequest->urgency] ?? 'Sin prisa' }}</span>
                                        @if ($post->jobRequest->location_label)
                                            <span class="rounded-full bg-white px-3 py-1.5 text-[#536A72] shadow-sm">{{ $post->jobRequest->location_label }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($post->type === 'service' || $post->type === 'product' || $post->type === 'promotion')
                                <div class="mt-5 flex items-center gap-2 rounded-2xl bg-[#E9F7F0] px-4 py-3 text-sm font-black text-[#14734A]">
                                    <span class="size-2 rounded-full bg-[#22A06B]"></span> Disponible para recibir solicitudes
                                </div>
                            @endif
                        </div>
                        <div class="grid grid-cols-3 border-t border-[#123B4A]/8 px-3 py-2 text-xs font-black text-[#6B7D83]">
                            @if ($post->user_id === $currentUser->id)
                                <a class="rounded-xl px-3 py-2.5 text-center transition hover:bg-[#FAF8F4] hover:text-[#F97316]" href="{{ route('posts.edit', $post) }}">Editar</a>
                                @if ($post->jobRequest)
                                    <a class="rounded-xl px-3 py-2.5 text-center transition hover:bg-[#FAF8F4] hover:text-[#F97316]" href="{{ route('job-proposals.index', $post->jobRequest) }}">Ver propuestas</a>
                                @else
                                    <button class="rounded-xl px-3 py-2.5 transition hover:bg-[#FAF8F4] hover:text-[#F97316]" type="button">Ver respuestas</button>
                                @endif
                            @else
                                @if ($post->jobRequest && $isProvider)
                                    <a class="rounded-xl px-3 py-2.5 text-center transition hover:bg-[#FAF8F4] hover:text-[#F97316]" href="{{ route('job-proposals.index', $post->jobRequest) }}">Enviar propuesta</a>
                                @elseif ($post->jobRequest)
                                    <span class="rounded-xl px-3 py-2.5 text-center text-[#A4B0B4]">Solo proveedores</span>
                                @elseif ($post->listing?->type?->value === 'product' && $post->listing?->price_type?->value === 'fixed')
                                    <a class="rounded-xl px-3 py-2.5 text-center transition hover:bg-[#FAF8F4] hover:text-[#F97316]" href="{{ route('products.checkout', $post->listing) }}">
                                        Comprar
                                    </a>
                                @else
                                    <button class="rounded-xl px-3 py-2.5 transition hover:bg-[#FAF8F4] hover:text-[#F97316]" type="button">
                                        {{ $post->listing?->price_type?->value === 'quote' ? 'Solicitar cotización' : 'Me interesa' }}
                                    </button>
                                @endif
                                <button class="rounded-xl px-3 py-2.5 transition hover:bg-[#FAF8F4] hover:text-[#F97316]" type="button">Comentar</button>
                            @endif
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
                ['Publicar', '#crear-publicacion'],
                ['Trabajos', route('orders.index')],
                ['Mensajes', route('conversations.index')],
                ['Perfil', route('profile.show', $currentUser)],
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
