<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F4F7F6] text-[#17313A] antialiased">
    <header class="sticky top-0 z-40 border-b border-[#123B4A]/10 bg-white/95 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a class="flex items-center gap-3 font-black" href="{{ route('admin.index') }}"><span class="grid size-10 place-items-center rounded-2xl bg-[#123B4A] text-white">P</span><span>Plaza Local</span></a>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <span class="hidden rounded-full bg-[#FFF1E8] px-4 py-2 text-xs font-black text-[#D85B0B] sm:inline-flex">{{ $isSuperadmin ? 'Superadministrador' : 'Administrador' }}</span>
                <a class="rounded-full border border-[#123B4A]/10 bg-white px-4 py-2 text-sm font-black" href="{{ route('admin.users.index') }}">Usuarios</a><a class="rounded-full border border-[#123B4A]/10 bg-white px-4 py-2 text-sm font-black" href="{{ route('admin.vendors.index') }}">Proveedores</a><a class="rounded-full border border-[#123B4A]/10 bg-white px-4 py-2 text-sm font-black" href="{{ route('admin.posts.index') }}">Publicaciones</a><a class="rounded-full border border-[#123B4A]/10 bg-white px-4 py-2 text-sm font-black" href="{{ route('disputes.admin-index') }}">Disputas</a><a class="rounded-full border border-[#123B4A]/10 bg-white px-4 py-2 text-sm font-black" href="{{ route('admin.support.index') }}">Soporte @if($metrics['open_support_tickets'])<span class="ml-1 rounded-full bg-[#F97316] px-2 py-0.5 text-[10px] text-white">{{ min(99,$metrics['open_support_tickets']) }}</span>@endif</a>
                @unless($isSuperadmin)<a class="rounded-full border border-[#123B4A]/10 bg-white px-4 py-2 text-sm font-black" href="{{ route('explore') }}">Explorar plaza</a>@endunless
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-full border border-red-200 bg-white px-4 py-2 text-sm font-black text-red-700" type="submit">Cerrar sesión</button></form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        @if(session('status'))<div class="mb-6 rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-black text-red-700" role="alert">{{ $errors->first() }}</div>@endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Espacio administrativo</p><h1 class="mt-2 text-3xl font-black">Control general de Plaza Local</h1><p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-[#6B7D83]">Controla usuarios, proveedores, operaciones y seguridad desde un solo lugar. Las comunidades futuras se administrarán desde este mismo panel central.</p></div>
            @if($isSuperadmin)
                <span class="rounded-full bg-[#E9F7F0] px-5 py-3 text-center text-sm font-black text-[#14734A]">Control global de comunidades</span>
            @elseif(auth()->user()->canActAsClient() || auth()->user()->canActAsProvider())
                <a class="rounded-full bg-[#123B4A] px-5 py-3 text-center text-sm font-black text-white" href="{{ route('dashboard') }}">Ir a mi cuenta comercial</a>
            @else
                <a class="rounded-full bg-[#123B4A] px-5 py-3 text-center text-sm font-black text-white" href="{{ route('explore') }}">Explorar la plaza</a>
            @endif
        </div>

        <section class="mt-7 grid gap-4 grid-cols-2 lg:grid-cols-6">
            <a class="rounded-3xl border border-[#123B4A]/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('admin.users.index') }}"><strong class="text-3xl">{{ $metrics['users'] }}</strong><p class="mt-2 text-sm font-bold text-[#6B7D83]">Usuarios</p><span class="mt-3 block text-xs font-black text-[#14734A]">Abrir directorio →</span></a>
            <a class="rounded-3xl border border-[#123B4A]/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('admin.vendors.index') }}"><strong class="text-3xl">{{ $metrics['vendors'] }}</strong><p class="mt-2 text-sm font-bold text-[#6B7D83]">Proveedores</p><span class="mt-3 block text-xs font-black text-[#14734A]">Gestionar →</span></a>
            <a class="rounded-3xl border border-[#123B4A]/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('admin.posts.index') }}"><strong class="text-3xl">{{ $metrics['active_posts'] }}</strong><p class="mt-2 text-sm font-bold text-[#6B7D83]">Publicaciones activas</p><span class="mt-3 block text-xs font-black text-[#14734A]">Moderar →</span></a>
            <a class="rounded-3xl border border-[#F97316]/20 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('admin.vendors.index', ['status' => 'pending']) }}"><strong class="text-3xl">{{ $metrics['pending_vendors'] }}</strong><p class="mt-2 text-sm font-bold text-[#6B7D83]">Pendientes</p><span class="mt-3 block text-xs font-black text-[#D85B0B]">Revisar →</span></a>
            <a class="rounded-3xl border border-[#123B4A]/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('disputes.admin-index') }}"><strong class="text-3xl">{{ $metrics['open_disputes'] }}</strong><p class="mt-2 text-sm font-bold text-[#6B7D83]">Disputas abiertas</p><span class="mt-3 block text-xs font-black text-[#14734A]">Atender →</span></a>
            <div class="rounded-3xl border border-[#123B4A]/10 bg-white p-5 shadow-sm"><strong class="text-3xl">{{ $metrics['active_orders'] }}</strong><p class="mt-2 text-sm font-bold text-[#6B7D83]">Órdenes activas</p><span class="mt-3 block text-xs font-bold text-[#8A999E]">Resumen operativo</span></div>
        </section>

        <section class="mt-8 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6">
            <p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Autoridad y responsabilidades</p>
            <h2 class="mt-1 text-xl font-black">¿Qué puede hacer cada administrador?</h2>
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <article class="rounded-2xl bg-[#E9F7F0] p-5"><h3 class="font-black text-[#14734A]">Superadministrador · control global</h3><p class="mt-2 text-sm font-semibold leading-6 text-[#536A72]">Dirige toda Plaza Local: crea comunidades, delega o retira administradores, consulta métricas globales, supervisa auditoría y atiende excepciones. Esta cuenta no compra ni vende.</p></article>
                <article class="rounded-2xl bg-[#FAF8F4] p-5"><h3 class="font-black">Administrador · operación</h3><p class="mt-2 text-sm font-semibold leading-6 text-[#536A72]">Registra comunidades, revisa proveedores, solicita correcciones, aprueba o suspende perfiles y atiende disputas. No puede nombrar otros administradores ni obtener control de superadministrador.</p></article>
            </div>
        </section>

            <div class="mt-5 rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] p-4">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm font-black">Catálogo postal listo</p><p class="mt-1 text-xs font-semibold text-[#6B7D83]">{{ number_format($metrics['postal_codes']) }} asentamientos disponibles para autocompletar comunidades.</p></div><span class="rounded-full bg-[#E9F7F0] px-3 py-1 text-xs font-black text-[#14734A]">Mantenimiento global</span></div>
                @if($isSuperadmin)
                    <details class="mt-3 border-t border-[#123B4A]/10 pt-3">
                        <summary class="cursor-pointer text-xs font-black text-[#123B4A]">Actualizar catálogo postal</summary>
                        <p class="mt-2 text-xs font-semibold leading-5 text-[#6B7D83]">Solo es necesario cuando Correos de México publique un catálogo nuevo. Los registros se actualizan sin duplicarse.</p>
                        <form class="mt-3 flex flex-col gap-2 sm:flex-row" method="POST" action="{{ route('admin.postal-codes.import') }}" enctype="multipart/form-data">@csrf
                            <input class="max-w-sm rounded-xl border border-[#123B4A]/10 bg-white px-3 py-2 text-sm" type="file" name="catalog" accept=".txt,text/plain" required>
                            <button class="rounded-full bg-[#14734A] px-5 py-2.5 text-sm font-black text-white" type="submit">Importar actualización</button>
                            <a class="self-center text-xs font-black text-[#14734A] underline" href="https://www.correosdemexico.gob.mx/SSLServicios/ConsultaCP/CodigoPostal_Exportar.aspx" target="_blank" rel="noopener noreferrer">Descargar catálogo oficial</a>
                        </form>
                        @error('catalog')<p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
                    </details>
                @endif
            </div>
        <section class="mt-8 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6" id="comunidades">
            <div><p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Cobertura territorial</p><h2 class="mt-1 text-xl font-black">Comunidades disponibles</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">El código postal completa los datos oficiales. Las personas elegirán comunidades concretas al solicitar u ofrecer; las coordenadas y el radio son opcionales y solo mejoran “cerca de mí”.</p></div>
            <form class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5" method="POST" action="{{ route('admin.communities.store') }}">@csrf
                <label><span class="text-xs font-black">Código postal</span><input id="community-postal-code" class="mt-2 w-full rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-3 py-2.5" name="postal_code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" autocomplete="postal-code" placeholder="5 dígitos"><span id="postal-code-status" class="mt-1 block text-xs font-bold text-[#6B7D83]">Al completar 5 dígitos buscaremos automáticamente.</span></label>
                <label><span class="text-xs font-black">Nombre de la comunidad</span><input id="community-name" class="mt-2 w-full rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-3 py-2.5" name="name" list="postal-settlements" required maxlength="120" placeholder="Ej. San Miguel"><datalist id="postal-settlements"></datalist></label>
                <label><span class="text-xs font-black">Municipio o ciudad</span><input id="community-municipality" class="mt-2 w-full rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-3 py-2.5" name="municipality" required maxlength="120"></label>
                <label><span class="text-xs font-black">Estado</span><input id="community-state" class="mt-2 w-full rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-3 py-2.5" name="state" maxlength="120"></label>

                <button class="self-end rounded-full bg-[#123B4A] px-5 py-3 text-sm font-black text-white" type="submit">Agregar comunidad</button>
            </form>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($communities as $community)
                    <article class="rounded-2xl border p-4 {{ $community->is_active ? 'border-[#123B4A]/10 bg-[#FAF8F4]' : 'border-[#D85B0B]/20 bg-orange-50/40' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div><h3 class="font-black">{{ $community->name }}</h3><p class="mt-1 text-xs font-bold text-[#6B7D83]">{{ $community->municipality }}{{ $community->state ? ', '.$community->state : '' }}</p></div>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $community->is_active ? 'bg-[#E9F7F0] text-[#14734A]' : 'bg-orange-100 text-[#D85B0B]' }}">{{ $community->is_active ? 'Activa' : 'Suspendida' }}</span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-[#536A72]">{{ $community->users_count }} usuarios · {{ $community->job_requests_count }} solicitudes · {{ $community->postal_code ? 'CP '.$community->postal_code : 'Sin CP' }}</p>
                        <p class="mt-1 text-xs font-bold {{ $community->hasCoordinates() ? 'text-[#14734A]' : 'text-[#D85B0B]' }}">{{ $community->hasCoordinates() ? 'Centro configurado · radio '.number_format((float) $community->default_radius_km, 1).' km' : 'Sin coordenadas: funciona por selección explícita de comunidad' }}</p>
                        <details class="mt-3"><summary class="cursor-pointer text-xs font-black text-[#123B4A]">Editar comunidad y ubicación</summary>
                            <form class="mt-3 grid gap-2" method="POST" action="{{ route('admin.communities.update', $community) }}">@csrf @method('PATCH')
                                <label class="text-xs font-black">Nombre completo<input class="mt-1 w-full rounded-xl border border-[#123B4A]/10 bg-white px-3 py-2 text-sm font-normal" name="name" value="{{ $community->name }}" required maxlength="120"></label>
                                <label class="text-xs font-black">Municipio o ciudad<input class="mt-1 w-full rounded-xl border border-[#123B4A]/10 bg-white px-3 py-2 text-sm font-normal" name="municipality" value="{{ $community->municipality }}" required maxlength="120"></label>
                                <label class="text-xs font-black">Estado<input class="mt-1 w-full rounded-xl border border-[#123B4A]/10 bg-white px-3 py-2 text-sm font-normal" name="state" value="{{ $community->state }}" maxlength="120"></label>
                                <label class="text-xs font-black">Código postal<input class="mt-1 w-full rounded-xl border border-[#123B4A]/10 bg-white px-3 py-2 text-sm font-normal" name="postal_code" value="{{ $community->postal_code }}" inputmode="numeric" pattern="[0-9]{5}" maxlength="5"></label>
                                <div class="grid grid-cols-2 gap-2"><label class="text-xs font-black">Latitud<input class="mt-1 w-full rounded-xl border border-[#123B4A]/10 bg-white px-3 py-2 text-sm font-normal" type="number" name="latitude" value="{{ $community->latitude }}" min="-90" max="90" step="0.0000001" placeholder="Opcional"></label><label class="text-xs font-black">Longitud<input class="mt-1 w-full rounded-xl border border-[#123B4A]/10 bg-white px-3 py-2 text-sm font-normal" type="number" name="longitude" value="{{ $community->longitude }}" min="-180" max="180" step="0.0000001" placeholder="Opcional"></label></div>
                                <label class="text-xs font-black">Radio auxiliar en km<input class="mt-1 w-full rounded-xl border border-[#123B4A]/10 bg-white px-3 py-2 text-sm font-normal" type="number" name="default_radius_km" value="{{ $community->default_radius_km }}" min="1" max="100" step="0.5" required></label>
                                <button class="rounded-full bg-[#123B4A] px-4 py-2 text-xs font-black text-white" type="submit">Guardar cambios</button>
                            </form>
                        </details>
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-[#123B4A]/10 pt-3">
                            <form method="POST" action="{{ route('admin.communities.toggle', $community) }}">@csrf @method('PATCH')<button class="text-xs font-black {{ $community->is_active ? 'text-[#D85B0B]' : 'text-[#14734A]' }}" type="submit">{{ $community->is_active ? 'Suspender comunidad' : 'Reactivar comunidad' }}</button></form>
                            @if($isSuperadmin)
                                @if(!$community->is_active && $community->users_count === 0 && $community->job_requests_count === 0)
                                    <form method="POST" action="{{ route('admin.communities.destroy', $community) }}" onsubmit="return confirm('¿Eliminar definitivamente esta comunidad vacía?')">@csrf @method('DELETE')<button class="text-xs font-black text-red-700" type="submit">Eliminar definitivamente</button></form>
                                @else
                                    <span class="text-[.68rem] font-bold text-[#6B7D83]">Para eliminar: debe estar suspendida y sin historial asociado.</span>
                                @endif
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-8 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6" id="rubros">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Configuración global</p><h2 class="mt-1 text-xl font-black">Catálogo maestro de rubros</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Define las opciones disponibles para toda Plaza Local. No representa los intereses personales de esta cuenta administrativa: cada usuario elige “Mis intereses” y, por separado, “Lo que ofrezco”.</p></div>
                <form class="flex gap-2" method="POST" action="{{ route('admin.categories.store') }}">@csrf<input class="min-w-0 rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="name" required maxlength="100" placeholder="Ej. Taxi o comida local"><button class="rounded-full bg-[#123B4A] px-5 py-3 text-sm font-black text-white" type="submit">Agregar rubro</button></form>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($categories as $category)
                    <article class="rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] p-4">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-black">{{ $category->name }}</h3><p class="mt-1 text-xs font-bold text-[#6B7D83]">{{ $category->users_count }} interesados · {{ $category->vendors_count }} personas ofrecen · {{ $category->listings_count + $category->job_requests_count }} publicaciones</p></div><span class="rounded-full px-3 py-1 text-xs font-black {{ $category->is_active ? 'bg-[#E9F7F0] text-[#14734A]' : 'bg-[#E5E9E7] text-[#536A72]' }}">{{ $category->is_active ? 'Activo' : 'Inactivo' }}</span></div>
                        <form class="mt-3" method="POST" action="{{ route('admin.categories.toggle', $category) }}">@csrf @method('PATCH')<button class="text-xs font-black {{ $category->is_active ? 'text-red-700' : 'text-[#14734A]' }}" type="submit">{{ $category->is_active ? 'Desactivar' : 'Reactivar' }}</button></form>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-8 rounded-[1.75rem] border border-[#F97316]/20 bg-white p-5 shadow-sm sm:p-6" id="aprobaciones">
            <div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Avisos de revisión</p><h2 class="mt-1 text-xl font-black">{{ $pendingVendors->count() }} solicitudes pendientes</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Esta tarjeta solo avisa el trabajo pendiente. La información completa y las decisiones están concentradas en el módulo de proveedores.</p></div><a class="rounded-full bg-[#123B4A] px-5 py-3 text-sm font-black text-white" href="{{ route('admin.vendors.index', ['status' => 'pending']) }}">Abrir pendientes</a></div>
        </section>

        <div class="mt-8 grid gap-6 xl:grid-cols-2">
            <section class="rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6" id="moderacion-proveedores">
                <p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Confianza y seguridad</p>
                <h2 class="mt-1 text-xl font-black">Moderación de proveedores</h2>
                <p class="mt-2 text-sm font-semibold leading-6 text-[#6B7D83]">Suspende un proveedor activo cuando exista una infracción. El motivo queda registrado en auditoría, se notifica al proveedor y sus ofertas dejan de mostrarse.</p>
                <div class="mt-5 space-y-4">
                    @forelse($vendors as $vendor)
                        <article class="rounded-2xl border p-4 {{ $vendor->status === 'suspended' ? 'border-red-200 bg-red-50' : 'border-[#123B4A]/10 bg-[#FAF8F4]' }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <strong>{{ $vendor->display_name }}</strong>
                                    <p class="text-xs font-bold text-[#6B7D83]">{{ $vendor->user->email }}</p>
                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-black {{ $vendor->status === 'active' ? 'bg-[#E9F7F0] text-[#14734A]' : 'bg-red-100 text-red-700' }}">{{ $vendor->status === 'active' ? 'Activo' : 'Suspendido' }}</span>
                                </div>
                                <a class="rounded-full border border-[#123B4A]/15 bg-white px-4 py-2 text-xs font-black" href="{{ route('profile.show', $vendor->user) }}">Ver perfil</a>
                            </div>
                            @if($vendor->status === 'suspended')
                                <div class="mt-3 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-red-800">
                                    <strong>Motivo:</strong> {{ $vendor->suspension_reason ?: 'Registrado en auditoría antes de esta actualización.' }}
                                    @if($vendor->suspended_at)<span class="mt-1 block text-xs text-[#6B7D83]">{{ $vendor->suspended_at->format('d/m/Y H:i') }}</span>@endif
                                </div>
                                @if($vendor->isReadyForReview())
                                    <form class="mt-3" method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">@csrf @method('PATCH')<button class="rounded-full bg-[#14734A] px-4 py-2 text-xs font-black text-white" type="submit">Reactivar proveedor</button></form>
                                @endif
                            @else
                                <form class="mt-3 grid gap-2 sm:grid-cols-[1fr_auto]" method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">@csrf @method('PATCH')
                                    <label><span class="sr-only">Motivo de suspensión</span><input class="w-full rounded-xl border border-red-200 bg-white px-3 py-2.5 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Describe la infracción o motivo (mínimo 10 caracteres)"></label>
                                    <button class="rounded-full border border-red-300 bg-white px-5 py-2.5 text-xs font-black text-red-700" type="submit">Suspender proveedor</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-[#123B4A]/20 p-6 text-sm font-bold text-[#6B7D83]">Aún no hay proveedores aprobados para moderar.</p>
                    @endforelse
                </div>
            </section>
            <section class="rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6">
                <h2 class="text-xl font-black">Administradores y delegación</h2>
                <p class="mt-2 text-sm font-semibold text-[#6B7D83]">Aquí solo aparecen los administradores actuales. Busca por nombre o correo para delegar a otra persona.</p>
                @if($isSuperadmin)
                    <form class="mt-5 flex flex-col gap-2 sm:flex-row" method="GET" action="{{ route('admin.index') }}">
                        <label class="min-w-0 flex-1"><span class="sr-only">Buscar usuario</span><input class="w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" type="search" name="admin_q" value="{{ $adminSearch }}" minlength="2" maxlength="100" placeholder="Nombre o correo del usuario" required></label>
                        <button class="rounded-full bg-[#123B4A] px-5 py-3 text-sm font-black text-white" type="submit">Buscar usuario</button>
                        @if($adminSearch !== '')<a class="self-center px-3 text-sm font-black text-[#D85B0B]" href="{{ route('admin.index') }}">Limpiar</a>@endif
                    </form>
                    @if($adminSearch !== '')
                        <div class="mt-4 space-y-2">
                            <p class="text-xs font-black uppercase tracking-[.12em] text-[#F97316]">Resultados para “{{ $adminSearch }}”</p>
                            @forelse($adminCandidates as $candidate)
                                <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#123B4A]/10 bg-white p-4"><div><strong>{{ $candidate->name }}</strong><p class="mt-1 text-xs font-bold text-[#6B7D83]">{{ $candidate->email }} · {{ $candidate->commercialRoleLabel() }}{{ $candidate->community ? ' · '.$candidate->community->name : '' }}</p></div><form method="POST" action="{{ route('admin.users.grant', $candidate) }}">@csrf<button class="rounded-full bg-[#123B4A] px-4 py-2 text-xs font-black text-white" type="submit">Hacer administrador</button></form></article>
                            @empty
                                <p class="rounded-2xl border border-dashed border-[#123B4A]/20 p-5 text-sm font-bold text-[#6B7D83]">No encontramos usuarios disponibles con ese nombre o correo.</p>
                            @endforelse
                        </div>
                    @endif
                @endif
                <div class="mt-6 space-y-3">
                    <p class="text-xs font-black uppercase tracking-[.12em] text-[#14734A]">Administradores actuales</p>
                    @forelse($administrators as $administrator)
                        <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-[#FAF8F4] p-4"><div><strong>{{ $administrator->name }}</strong><p class="mt-1 text-xs font-bold text-[#6B7D83]">{{ $administrator->email }}{{ $administrator->community ? ' · '.$administrator->community->name : '' }}</p></div>@if($isSuperadmin)<form method="POST" action="{{ route('admin.users.revoke', $administrator) }}">@csrf @method('DELETE')<button class="rounded-full border border-red-200 px-4 py-2 text-xs font-black text-red-700" type="submit">Retirar administrador</button></form>@endif</article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-[#123B4A]/20 p-5 text-sm font-bold text-[#6B7D83]">Todavía no hay administradores delegados.</p>
                    @endforelse
                </div>
            </section>
        </div>


        <section class="mt-6 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6"><h2 class="text-xl font-black">Auditoría reciente</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Registro de quién realizó cada cambio administrativo y sobre qué elemento.</p><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[650px] text-left text-sm"><thead><tr class="text-[#6B7D83]"><th class="p-3">Fecha</th><th class="p-3">Responsable</th><th class="p-3">Qué ocurrió</th><th class="p-3">Elemento afectado</th></tr></thead><tbody>@forelse($auditLogs as $log)<tr class="border-t border-[#123B4A]/10"><td class="p-3">{{ $log->created_at->format('d/m/Y H:i') }}</td><td class="p-3">{{ $log->user?->name ?? 'Sistema' }}</td><td class="p-3 font-black">{{ $auditActions[$log->action] ?? str_replace(['.', '_'], ' ', ucfirst($log->action)) }}</td><td class="p-3">{{ $auditSubjects[class_basename($log->subject_type)] ?? class_basename($log->subject_type) }} #{{ $log->subject_id }}</td></tr>@empty<tr><td class="p-6 text-center font-bold text-[#6B7D83]" colspan="4">Todavía no hay acciones administrativas registradas.</td></tr>@endforelse</tbody></table></div></section>
    </main>
    <script>
        const postalInput = document.getElementById('community-postal-code');
        const postalStatus = document.getElementById('postal-code-status');
        let postalLookupTimer;

        const lookupPostalCode = async () => {
            const postalCode = postalInput?.value.trim() ?? '';
            if (!/^\d{5}$/.test(postalCode)) {
                postalStatus.textContent = 'Escribe exactamente 5 dígitos.';
                postalStatus.className = 'mt-1 block text-xs font-bold text-red-600';
                return;
            }
            postalStatus.textContent = 'Consultando catálogo postal…';
            postalStatus.className = 'mt-1 block text-xs font-bold text-[#6B7D83]';
            try {
                const url = @json(route('postal-codes.show', ['postalCode' => '00000'])).replace('00000', postalCode);
                const response = await fetch(url, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
                if (!response.ok) throw new Error('lookup-failed');
                const data = await response.json();
                if (!data.found || !Array.isArray(data.places) || data.places.length === 0) {
                    throw new Error(data.catalog_available
                        ? 'Ese CP no aparece en el catálogo cargado. Puedes completar los datos manualmente.'
                        : 'El catálogo postal aún está vacío. Importa arriba el TXT oficial de Correos de México y vuelve a consultar.');
                }
                const first = data.places[0];
                document.getElementById('community-municipality').value = first.municipality ?? '';
                document.getElementById('community-state').value = first.state ?? '';
                document.getElementById('community-name').value = data.places.length === 1 ? (first.settlement ?? '') : '';
                const options = document.getElementById('postal-settlements');
                options.replaceChildren(...data.places.map(place => {
                    const option = document.createElement('option');
                    option.value = place.settlement;
                    option.label = [place.settlement_type, place.municipality, place.state].filter(Boolean).join(' · ');
                    return option;
                }));
                postalStatus.textContent = data.places.length === 1
                    ? `${first.settlement}, ${first.municipality}, ${first.state}. Datos completados.`
                    : `${data.places.length} asentamientos encontrados. Elige uno en “Nombre de la comunidad”; municipio y estado ya fueron completados.`;
                postalStatus.className = 'mt-1 block text-xs font-bold text-[#14734A]';
            } catch (error) {
                postalStatus.textContent = error.message === 'lookup-failed'
                    ? 'No pudimos consultar el CP. Revisa la conexión y vuelve a intentarlo.'
                    : error.message;
                postalStatus.className = 'mt-1 block text-xs font-bold text-red-600';
            }
        };

        postalInput?.addEventListener('input', () => {
            postalInput.value = postalInput.value.replace(/\D/g, '').slice(0, 5);
            clearTimeout(postalLookupTimer);
            if (postalInput.value.length === 5) postalLookupTimer = setTimeout(lookupPostalCode, 250);
        });
        postalInput?.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); lookupPostalCode(); }
        });
    </script>
</body>
</html>
