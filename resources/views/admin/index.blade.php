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
                <a class="rounded-full border border-[#123B4A]/10 bg-white px-4 py-2 text-sm font-black" href="#moderacion-proveedores">Proveedores</a><a class="rounded-full border border-[#123B4A]/10 bg-white px-4 py-2 text-sm font-black" href="{{ route('disputes.admin-index') }}">Disputas</a>
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

        <section class="mt-7 grid gap-4 grid-cols-2 lg:grid-cols-4">
            @foreach(['Usuarios'=>$metrics['users'],'Pendientes'=>$metrics['pending_vendors'],'Disputas abiertas'=>$metrics['open_disputes'],'Órdenes activas'=>$metrics['active_orders']] as $label=>$value)
                <div class="rounded-3xl border border-[#123B4A]/10 bg-white p-5 shadow-sm"><strong class="text-3xl">{{ $value }}</strong><p class="mt-2 text-sm font-bold text-[#6B7D83]">{{ $label }}</p></div>
            @endforeach
        </section>

        <section class="mt-8 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6">
            <p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Autoridad y responsabilidades</p>
            <h2 class="mt-1 text-xl font-black">¿Qué puede hacer cada administrador?</h2>
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <article class="rounded-2xl bg-[#E9F7F0] p-5"><h3 class="font-black text-[#14734A]">Superadministrador · control global</h3><p class="mt-2 text-sm font-semibold leading-6 text-[#536A72]">Dirige toda Plaza Local: crea comunidades, delega o retira administradores, consulta métricas globales, supervisa auditoría y atiende excepciones. Esta cuenta no compra ni vende.</p></article>
                <article class="rounded-2xl bg-[#FAF8F4] p-5"><h3 class="font-black">Administrador · operación</h3><p class="mt-2 text-sm font-semibold leading-6 text-[#536A72]">Registra comunidades, revisa proveedores, solicita correcciones, aprueba o suspende perfiles y atiende disputas. No puede nombrar otros administradores ni obtener control de superadministrador.</p></article>
            </div>
        </section>

        <section class="mt-8 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6" id="comunidades">
            <div><p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Cobertura territorial</p><h2 class="mt-1 text-xl font-black">Comunidades disponibles</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">La distancia se mide desde la sede o comunidad inicial de Plaza Local.</p></div>
            <form class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5" method="POST" action="{{ route('admin.communities.store') }}">@csrf
                <label><span class="text-xs font-black">Nombre de la comunidad</span><input class="mt-2 w-full rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-3 py-2.5" name="name" required maxlength="120" placeholder="Ej. San Miguel"></label>
                <label><span class="text-xs font-black">Municipio o ciudad</span><input class="mt-2 w-full rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-3 py-2.5" name="municipality" required maxlength="120"></label>
                <label><span class="text-xs font-black">Estado</span><input class="mt-2 w-full rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-3 py-2.5" name="state" maxlength="120"></label>
                <label><span class="text-xs font-black">Distancia desde la sede (km)</span><input class="mt-2 w-full rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-3 py-2.5" type="number" name="distance_km" required min="0" max="9999.99" step="0.01" value="0"></label>
                <button class="self-end rounded-full bg-[#123B4A] px-5 py-3 text-sm font-black text-white" type="submit">Agregar comunidad</button>
            </form>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($communities as $community)
                    <article class="rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div><h3 class="font-black">{{ $community->name }}</h3><p class="mt-1 text-xs font-bold text-[#6B7D83]">{{ $community->municipality }}{{ $community->state ? ', '.$community->state : '' }}</p></div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black">{{ (float) $community->distance_km === 0.0 ? 'Sede' : number_format((float) $community->distance_km, 1).' km' }}</span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-[#536A72]">{{ $community->users_count }} usuarios · {{ $community->is_active ? 'Activa' : 'Inactiva' }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="mt-8 rounded-[1.75rem] border border-[#F97316]/20 bg-white p-5 shadow-sm sm:p-6" id="aprobaciones">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Bandeja de revisión</p><h2 class="mt-1 text-xl font-black">Proveedores pendientes</h2></div><span class="rounded-full bg-[#FFF1E8] px-4 py-2 text-xs font-black text-[#D85B0B]">{{ $pendingVendors->count() }} pendientes</span></div>
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                @forelse($pendingVendors as $vendor)
                    @php($missing = $vendor->missingReviewRequirements())
                    @php($ready = $vendor->isReadyForReview())
                    <article class="rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] p-5">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-black">{{ $vendor->display_name }}</h3><p class="mt-1 text-xs font-bold text-[#6B7D83]">{{ $vendor->user->email }}</p></div><span class="rounded-full px-3 py-1 text-xs font-black {{ $ready ? 'bg-[#E9F7F0] text-[#14734A]' : 'bg-[#FFF4D6] text-[#79551E]' }}">{{ $ready ? 'Lista para revisar' : 'Incompleta' }}</span></div>
                        <div class="mt-4 text-sm font-semibold leading-6 text-[#536A72]"><p>Correo: <strong>{{ $vendor->user->hasVerifiedEmail() ? 'verificado' : 'sin verificar' }}</strong></p><p>Especialidad: <strong>{{ $vendor->specialty ?: 'pendiente' }}</strong></p><p>Zona: <strong>{{ $vendor->service_area ?: 'pendiente' }}</strong></p></div>
                        @if(!$ready)<p class="mt-3 rounded-xl bg-[#FFF4D6] px-3 py-2 text-xs font-bold text-[#79551E]">Falta: {{ collect($missing)->values()->join(', ') }}{{ !$vendor->user->hasVerifiedEmail() ? ($missing ? ', ' : '').'verificar correo' : '' }}.</p>@endif
                        <div class="mt-4 flex flex-wrap gap-2"><a class="rounded-full border border-[#123B4A]/15 bg-white px-4 py-2 text-xs font-black" href="{{ route('profile.show', $vendor->user) }}">Ver perfil</a>@if($ready)<form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">@csrf @method('PATCH')<button class="rounded-full bg-[#14734A] px-4 py-2 text-xs font-black text-white" type="submit">Aprobar proveedor</button></form><form class="flex min-w-[240px] flex-1 gap-2" method="POST" action="{{ route('admin.vendors.reject', $vendor) }}">@csrf @method('PATCH')<input class="min-w-0 flex-1 rounded-full border border-red-200 bg-white px-3 py-2 text-xs" name="reason" minlength="10" maxlength="1000" required placeholder="Motivo de devolución"><button class="rounded-full border border-red-200 px-4 py-2 text-xs font-black text-red-700" type="submit">Solicitar cambios</button></form>@else<span class="rounded-full bg-[#E5E9E7] px-4 py-2 text-xs font-black text-[#70817B]">Esperando datos</span>@endif</div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-[#123B4A]/20 p-8 text-center font-bold text-[#6B7D83] lg:col-span-2">No hay proveedores pendientes por revisar.</div>
                @endforelse
            </div>
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
            <section class="rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6"><h2 class="text-xl font-black">Usuarios y autoridad</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Solo el superadministrador puede delegar o retirar administradores.</p><div class="mt-5 space-y-3">@foreach($users as $user)<article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-[#FAF8F4] p-4"><div><strong>{{ $user->name }}</strong><p class="text-xs font-bold text-[#6B7D83]">{{ $user->email }} · {{ $user->roles->pluck('name')->join(', ') }}</p></div>@if($isSuperadmin && !$user->hasRole('superadmin'))<div>@if($user->hasRole('admin'))<form method="POST" action="{{ route('admin.users.revoke', $user) }}">@csrf @method('DELETE')<button class="rounded-full border border-red-200 px-4 py-2 text-xs font-black text-red-700">Retirar admin</button></form>@else<form method="POST" action="{{ route('admin.users.grant', $user) }}">@csrf<button class="rounded-full bg-[#123B4A] px-4 py-2 text-xs font-black text-white">Hacer admin</button></form>@endif</div>@endif</article>@endforeach</div></section>
        </div>


        <section class="mt-6 rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6"><h2 class="text-xl font-black">Auditoría reciente</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Registro de quién realizó cada cambio administrativo y sobre qué elemento.</p><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[650px] text-left text-sm"><thead><tr class="text-[#6B7D83]"><th class="p-3">Fecha</th><th class="p-3">Responsable</th><th class="p-3">Qué ocurrió</th><th class="p-3">Elemento afectado</th></tr></thead><tbody>@forelse($auditLogs as $log)<tr class="border-t border-[#123B4A]/10"><td class="p-3">{{ $log->created_at->format('d/m/Y H:i') }}</td><td class="p-3">{{ $log->user?->name ?? 'Sistema' }}</td><td class="p-3 font-black">{{ $auditActions[$log->action] ?? str_replace(['.', '_'], ' ', ucfirst($log->action)) }}</td><td class="p-3">{{ $auditSubjects[class_basename($log->subject_type)] ?? class_basename($log->subject_type) }} #{{ $log->subject_id }}</td></tr>@empty<tr><td class="p-6 text-center font-bold text-[#6B7D83]" colspan="4">Todavía no hay acciones administrativas registradas.</td></tr>@endforelse</tbody></table></div></section>
    </main>
</body>
</html>
