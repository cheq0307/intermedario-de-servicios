<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expediente de proveedor - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F3F7F6] text-[#17313A] antialiased">
@php
    $statusLabels = ['draft' => 'Borrador', 'pending' => 'Pendiente de revisión', 'active' => 'Aprobado', 'rejected' => 'Cambios solicitados', 'suspended' => 'Suspendido'];
    $availabilityLabels = ['available' => 'Disponible para nuevos trabajos', 'busy' => 'Realizando un trabajo', 'unavailable' => 'No disponible temporalmente'];
    $dayLabels = ['monday' => 'lunes', 'tuesday' => 'martes', 'wednesday' => 'miércoles', 'thursday' => 'jueves', 'friday' => 'viernes', 'saturday' => 'sábado', 'sunday' => 'domingo'];
    $hours = $vendor->business_hours ?? [];
    $missing = $vendor->missingReviewRequirements();
    $ready = $vendor->isReadyForReview();
@endphp
<header class="border-b border-[#123B4A]/10 bg-white"><div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4"><a class="font-black" href="{{ route('admin.vendors.index', ['status' => $vendor->status]) }}">← Proveedores</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-black" type="submit">Cerrar sesión</button></form></div></header>
<main class="mx-auto max-w-6xl px-5 py-8">
    @if(session('status'))<div class="mb-6 rounded-2xl bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-6 rounded-2xl bg-red-50 px-5 py-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif

    <section class="rounded-[2rem] bg-[#123B4A] p-6 text-white sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-5"><div><p class="text-xs font-black uppercase tracking-[.18em] text-[#FFB067]">Expediente administrativo privado</p><h1 class="mt-2 text-3xl font-black">{{ $vendor->display_name }}</h1><p class="mt-2 text-sm font-semibold text-white/75">Solicitud enviada {{ $vendor->submitted_at?->format('d/m/Y H:i') ?? 'sin fecha' }}</p></div><span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black">{{ $statusLabels[$vendor->status] ?? $vendor->status }}</span></div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
        <div class="space-y-6">
            <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Identidad y contacto</h2><p class="mt-2 text-xs font-bold text-[#D85B0B]">Información privada: solo administración puede verla.</p>
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2"><div><dt class="font-bold text-[#6B7D83]">Nombre completo</dt><dd class="mt-1 font-black">{{ $vendor->user->name }}</dd></div><div><dt class="font-bold text-[#6B7D83]">Correo</dt><dd class="mt-1 font-black break-all">{{ $vendor->user->email }} · {{ $vendor->user->hasVerifiedEmail() ? 'verificado' : 'sin verificar' }}</dd></div><div><dt class="font-bold text-[#6B7D83]">Teléfono</dt><dd class="mt-1 font-black">{{ $vendor->user->phone ?: $vendor->phone ?: 'No proporcionado' }}</dd></div><div><dt class="font-bold text-[#6B7D83]">Comunidad</dt><dd class="mt-1 font-black">{{ $vendor->user->community?->name ?? 'Sin comunidad' }}{{ $vendor->user->community?->municipality ? ' · '.$vendor->user->community->municipality : '' }}{{ $vendor->user->community?->postal_code ? ' · CP '.$vendor->user->community->postal_code : '' }}</dd></div></dl>
            </section>

            <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Actividad comercial declarada</h2>
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2"><div><dt class="font-bold text-[#6B7D83]">Especialidad</dt><dd class="mt-1 font-black">{{ $vendor->specialty ?: 'No indicada' }}</dd></div><div><dt class="font-bold text-[#6B7D83]">Zona de servicio</dt><dd class="mt-1 font-black">{{ $vendor->service_area ?: 'No indicada' }}</dd></div><div><dt class="font-bold text-[#6B7D83]">Experiencia</dt><dd class="mt-1 font-black">{{ $vendor->years_experience !== null ? $vendor->years_experience.' años' : 'No indicada' }}</dd></div><div><dt class="font-bold text-[#6B7D83]">Disponibilidad</dt><dd class="mt-1 font-black">{{ $availabilityLabels[$vendor->availability_status] ?? 'No indicada' }}</dd></div></dl>
                <div class="mt-5"><h3 class="text-sm font-bold text-[#6B7D83]">Rubros que ofrece</h3><div class="mt-2 flex flex-wrap gap-2">@forelse($vendor->categories as $category)<span class="rounded-full bg-[#FFF1E8] px-3 py-1.5 text-xs font-black text-[#D85B0B]">{{ $category->name }}</span>@empty<span class="text-sm font-bold text-red-700">No seleccionó rubros</span>@endforelse</div></div>
                <div class="mt-5"><h3 class="text-sm font-bold text-[#6B7D83]">Descripción</h3><p class="mt-2 whitespace-pre-line text-sm leading-6">{{ $vendor->description ?: 'No proporcionada' }}</p></div>
                <div class="mt-5 grid gap-5 sm:grid-cols-2"><div><h3 class="text-sm font-bold text-[#6B7D83]">Certificaciones o preparación</h3><p class="mt-2 whitespace-pre-line text-sm leading-6">{{ $vendor->certifications ?: 'No proporcionadas' }}</p></div><div><h3 class="text-sm font-bold text-[#6B7D83]">Herramientas y capacidades</h3><p class="mt-2 whitespace-pre-line text-sm leading-6">{{ $vendor->tools ?: 'No proporcionadas' }}</p></div></div>
                <div class="mt-5"><h3 class="text-sm font-bold text-[#6B7D83]">Horario declarado</h3>@if($vendor->businessHoursConfigured())<p class="mt-2 text-sm font-black">{{ collect($hours['days'])->map(fn ($day) => $dayLabels[$day] ?? $day)->join(', ') }} · {{ $hours['opens_at'] }} a {{ $hours['closes_at'] }}</p>@else<p class="mt-2 text-sm font-bold text-red-700">No configurado</p>@endif</div>
            </section>

            <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Contexto de la cuenta</h2><div class="mt-4 grid gap-4 sm:grid-cols-3"><div class="rounded-2xl bg-[#FAF8F4] p-4"><strong class="text-2xl">{{ $vendor->listings_count }}</strong><p class="text-xs font-bold text-[#6B7D83]">Ofertas publicadas</p></div><div class="rounded-2xl bg-[#FAF8F4] p-4"><strong class="text-2xl">{{ $vendor->orders_count }}</strong><p class="text-xs font-bold text-[#6B7D83]">Operaciones</p></div><div class="rounded-2xl bg-[#FAF8F4] p-4"><strong class="text-2xl">{{ $vendor->user->categoryPreferences->count() }}</strong><p class="text-xs font-bold text-[#6B7D83]">Intereses personales</p></div></div><a class="mt-5 inline-flex rounded-full border border-[#123B4A]/15 px-4 py-2 text-xs font-black" href="{{ route('profile.show', $vendor->user) }}">Ver perfil público por separado</a></section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Evaluación</h2><div class="mt-4 rounded-2xl p-4 {{ $ready ? 'bg-[#E9F7F0] text-[#14734A]' : 'bg-[#FFF4D6] text-[#79551E]' }}"><p class="font-black">{{ $ready ? 'Expediente listo para decisión' : 'Expediente incompleto' }}</p>@if(!$ready)<p class="mt-2 text-sm font-bold">Falta: {{ collect($missing)->values()->join(', ') }}{{ ! $vendor->user->hasVerifiedEmail() ? ($missing ? ', ' : '').'verificar correo' : '' }}.</p>@endif</div>
                <ul class="mt-4 space-y-2 text-sm font-bold"><li>{{ $vendor->user->hasVerifiedEmail() ? '✓' : '×' }} Correo verificado</li><li>{{ $vendor->categories->isNotEmpty() ? '✓' : '×' }} Al menos un rubro ofrecido</li><li>{{ $vendor->businessHoursConfigured() ? '✓' : '×' }} Horario configurado</li><li>{{ blank($vendor->description) ? '×' : '✓' }} Descripción profesional</li><li>{{ $vendor->user->community ? '✓' : '×' }} Comunidad asignada</li></ul>
            </section>

            <section class="rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black">Documentos de verificación</h2>
                <p class="mt-2 text-sm font-semibold text-[#6B7D83]">Archivos privados. Contrasta identidad y vigencia; no copies datos sensibles fuera del expediente.</p>
                <div class="mt-4 space-y-3">
                    @forelse($vendor->verificationDocuments->sortByDesc('id') as $document)
                        <article class="rounded-2xl bg-[#FAF8F4] p-4"><div class="flex flex-wrap items-center justify-between gap-2"><div><strong class="text-sm">{{ $document->label() }}</strong><p class="text-xs font-bold text-[#6B7D83]">{{ \App\Models\VendorVerificationDocument::STATUSES[$document->status] ?? $document->status }} · {{ number_format($document->size / 1024, 0) }} KB</p></div><a class="rounded-full border border-[#123B4A]/15 px-3 py-2 text-xs font-black" href="{{ route('verification-documents.download', $document) }}">Abrir</a></div>
                        @if($document->status === 'pending')<form class="mt-3 space-y-2" method="POST" action="{{ route('admin.verification-documents.review', $document) }}">@csrf @method('PATCH')<textarea class="min-h-20 w-full rounded-xl border border-[#123B4A]/10 px-3 py-2 text-sm" name="review_note" minlength="10" maxlength="1000" required placeholder="Resultado de la revisión"></textarea><div class="flex gap-2"><button class="flex-1 rounded-full bg-[#14734A] px-3 py-2 text-xs font-black text-white" name="decision" value="approved">Aprobar</button><button class="flex-1 rounded-full border border-red-200 px-3 py-2 text-xs font-black text-red-700" name="decision" value="rejected">Rechazar</button></div></form>@elseif($document->review_note)<p class="mt-2 text-xs font-semibold text-[#6B7D83]">{{ $document->review_note }}</p>@endif
                        </article>
                    @empty<p class="rounded-2xl border border-dashed border-[#123B4A]/20 p-5 text-sm font-bold text-[#6B7D83]">La persona aún no ha enviado documentos.</p>@endforelse
                </div>
            </section>            @if($vendor->status === 'active')
                <section class="rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black">Distintivo de confianza</h2>
                    @if($vendor->verified_at)
                        <div class="mt-4 rounded-2xl bg-[#E9F7F0] p-4 text-sm text-[#14734A]"><strong>Proveedor verificado</strong><p class="mt-1 font-semibold">Nivel: {{ $vendor->verification_level === 'business' ? 'Identidad y negocio' : 'Identidad' }} · {{ $vendor->verified_at->format('d/m/Y') }}</p></div>
                        @if(auth()->user()->hasRole('superadmin'))<form class="mt-4" method="POST" action="{{ route('admin.vendors.verification.revoke', $vendor) }}">@csrf @method('DELETE')<textarea class="min-h-20 w-full rounded-2xl border border-red-200 px-4 py-3 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Motivo para retirar el distintivo"></textarea><button class="mt-3 w-full rounded-full border border-red-200 px-5 py-3 font-black text-red-700" type="submit">Retirar verificación</button></form>@endif
                    @elseif(auth()->user()->hasRole('superadmin'))
                        <p class="mt-2 text-sm font-semibold text-[#6B7D83]">Aprobar el perfil permite operar; este distintivo requiere una revisión adicional. Pagar una tarifa de revisión nunca garantiza obtenerlo.</p>
                        <form class="mt-4 space-y-3" method="POST" action="{{ route('admin.vendors.verify', $vendor) }}">@csrf @method('PATCH')<select class="w-full rounded-2xl bg-[#FAF8F4] px-4 py-3" name="verification_level" required><option value="identity">Identidad revisada</option><option value="business">Identidad y negocio revisados</option></select><textarea class="min-h-24 w-full rounded-2xl border border-[#123B4A]/10 px-4 py-3 text-sm" name="verification_note" minlength="10" maxlength="1000" required placeholder="Anota qué documentos o evidencia fueron contrastados"></textarea><button class="w-full rounded-full bg-[#123B4A] px-5 py-3 font-black text-white" type="submit">Otorgar distintivo verificado</button></form>
                    @else
                        <p class="mt-2 text-sm font-semibold text-[#6B7D83]">Solo el superadministrador puede otorgar el distintivo después de revisar el expediente.</p>
                    @endif
                </section>
            @endif
            @if($vendor->status === 'pending')
                <section class="rounded-[1.75rem] border border-[#F97316]/20 bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Decisión administrativa</h2><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Aprueba solo después de contrastar toda la información anterior.</p>
                    @if($ready)<form class="mt-5" method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-[#14734A] px-5 py-3 font-black text-white" type="submit">Aprobar proveedor</button></form>@else<button class="mt-5 w-full cursor-not-allowed rounded-full bg-[#D8DEDB] px-5 py-3 font-black text-[#70817B]" disabled>Faltan requisitos</button>@endif
                    <form class="mt-4" method="POST" action="{{ route('admin.vendors.reject', $vendor) }}">@csrf @method('PATCH')<label class="text-sm font-black">Cambios que debe realizar</label><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Explica con precisión qué información debe corregir o completar."></textarea><button class="mt-3 w-full rounded-full border border-red-200 px-5 py-3 font-black text-red-700" type="submit">Solicitar cambios</button></form>
                </section>
            @elseif($vendor->status === 'active')
                <section class="rounded-[1.75rem] border border-red-200 bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Moderación</h2><form class="mt-4" method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">@csrf @method('PATCH')<textarea class="min-h-24 w-full rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Motivo documentado de la suspensión"></textarea><button class="mt-3 w-full rounded-full bg-red-700 px-5 py-3 font-black text-white" type="submit">Suspender proveedor</button></form></section>
            @elseif($vendor->status === 'suspended' && $ready)
                <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><p class="text-sm font-semibold">Motivo de suspensión: {{ $vendor->suspension_reason }}</p><form class="mt-4" method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-[#14734A] px-5 py-3 font-black text-white" type="submit">Reactivar proveedor</button></form></section>
                <a class="inline-flex rounded-full border border-[#123B4A]/10 bg-white px-5 py-3 text-sm font-black" href="{{ route('admin.support.index', ['q' => $vendor->user->email]) }}">Buscar casos de soporte de esta cuenta</a>
            @endif
        </aside>
    </div>
</main>
</body>
</html>
