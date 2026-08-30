@php
    $statusLabels = ['draft' => 'Borrador', 'pending' => 'Pendiente de revisión', 'active' => 'Aprobado', 'rejected' => 'Cambios solicitados', 'suspended' => 'Suspendido'];
    $availabilityLabels = ['available' => 'Disponible para nuevos trabajos', 'busy' => 'Realizando un trabajo', 'unavailable' => 'No disponible temporalmente'];
    $missing = $vendor->missingReviewRequirements();
    $ready = $vendor->isReadyForReview();
@endphp
<x-admin-layout title="Expediente comercial" section="accounts">
    @if(session('status'))<div class="mb-6 rounded-2xl bg-brand-success-soft px-5 py-4 text-sm font-black text-brand-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-6 rounded-2xl bg-red-50 px-5 py-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif

    <section class="rounded-[2rem] bg-brand p-6 text-white sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-5"><div><p class="text-xs font-black uppercase tracking-[.18em] text-brand-peach-strong">Expediente administrativo privado</p><h1 class="mt-2 text-3xl font-black">{{ $vendor->display_name }}</h1><p class="mt-2 text-sm font-semibold text-white/75">Solicitud enviada {{ $vendor->submitted_at?->format('d/m/Y H:i') ?? 'sin fecha' }}</p></div><span class="rounded-full bg-white/10 px-4 py-2 text-xs font-black">{{ $statusLabels[$vendor->status] ?? $vendor->status }}</span></div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
        <div class="space-y-6">
            <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Identidad y contacto</h2><p class="mt-2 text-xs font-bold text-brand-danger-warm">Información privada: solo administración puede verla.</p>
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2"><div><dt class="font-bold text-brand-muted">Nombre completo</dt><dd class="mt-1 font-black">{{ $vendor->user->name }}</dd></div><div><dt class="font-bold text-brand-muted">Correo</dt><dd class="mt-1 font-black break-all">{{ $vendor->user->email }} · {{ $vendor->user->hasVerifiedEmail() ? 'verificado' : 'sin verificar' }}</dd></div><div><dt class="font-bold text-brand-muted">Teléfono</dt><dd class="mt-1 font-black">{{ $vendor->user->phone ?: $vendor->phone ?: 'No proporcionado' }}</dd></div><div><dt class="font-bold text-brand-muted">Comunidad</dt><dd class="mt-1 font-black">{{ $vendor->user->community?->name ?? 'Sin comunidad' }}{{ $vendor->user->community?->municipality ? ' · '.$vendor->user->community->municipality : '' }}{{ $vendor->user->community?->postal_code ? ' · CP '.$vendor->user->community->postal_code : '' }}</dd></div></dl>
            </section>

            <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Actividad comercial declarada</h2>
                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2"><div><dt class="font-bold text-brand-muted">Especialidad</dt><dd class="mt-1 font-black">{{ $vendor->specialty ?: 'No indicada' }}</dd></div><div><dt class="font-bold text-brand-muted">Zona de servicio</dt><dd class="mt-1 font-black">{{ $vendor->service_area ?: 'No indicada' }}</dd></div><div><dt class="font-bold text-brand-muted">Experiencia</dt><dd class="mt-1 font-black">{{ $vendor->years_experience !== null ? $vendor->years_experience.' años' : 'No indicada' }}</dd></div><div><dt class="font-bold text-brand-muted">Disponibilidad</dt><dd class="mt-1 font-black">{{ $availabilityLabels[$vendor->availability_status] ?? 'No indicada' }}</dd></div></dl>
                <div class="mt-5"><h3 class="text-sm font-bold text-brand-muted">Rubros que ofrece</h3><div class="mt-2 flex flex-wrap gap-2">@forelse($vendor->categories as $category)<span class="rounded-full bg-brand-orange-soft px-3 py-1.5 text-xs font-black text-brand-danger-warm">{{ $category->name }}</span>@empty<span class="text-sm font-bold text-red-700">No seleccionó rubros</span>@endforelse</div></div>
                <div class="mt-5"><h3 class="text-sm font-bold text-brand-muted">Descripción</h3><p class="mt-2 whitespace-pre-line text-sm leading-6">{{ $vendor->description ?: 'No proporcionada' }}</p></div>
                <div class="mt-5 grid gap-5 sm:grid-cols-2"><div><h3 class="text-sm font-bold text-brand-muted">Certificaciones o preparación</h3><p class="mt-2 whitespace-pre-line text-sm leading-6">{{ $vendor->certifications ?: 'No proporcionadas' }}</p></div><div><h3 class="text-sm font-bold text-brand-muted">Herramientas y capacidades</h3><p class="mt-2 whitespace-pre-line text-sm leading-6">{{ $vendor->tools ?: 'No proporcionadas' }}</p></div></div>
                <div class="mt-5"><h3 class="text-sm font-bold text-brand-muted">Horario declarado</h3>@if($vendor->businessHoursLabel())<p class="mt-2 text-sm font-black">{{ $vendor->businessHoursLabel() }}</p>@else<p class="mt-2 text-sm font-bold text-red-700">No configurado</p>@endif</div>
            </section>

            <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Contexto de la cuenta</h2><div class="mt-4 grid gap-4 sm:grid-cols-3"><div class="rounded-2xl bg-brand-surface p-4"><strong class="text-2xl">{{ $vendor->listings_count }}</strong><p class="text-xs font-bold text-brand-muted">Ofertas publicadas</p></div><div class="rounded-2xl bg-brand-surface p-4"><strong class="text-2xl">{{ $vendor->orders_count }}</strong><p class="text-xs font-bold text-brand-muted">Operaciones</p></div><div class="rounded-2xl bg-brand-surface p-4"><strong class="text-2xl">{{ $vendor->user->categoryPreferences->count() }}</strong><p class="text-xs font-bold text-brand-muted">Intereses personales</p></div></div><a class="mt-5 inline-flex rounded-full border border-brand/15 px-4 py-2 text-xs font-black" href="{{ route('profile.show', $vendor->user) }}">Ver perfil público por separado</a></section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Evaluación</h2><div class="mt-4 rounded-2xl p-4 {{ $ready ? 'bg-brand-success-soft text-brand-success' : 'bg-brand-warning-soft text-brand-warning-copy' }}"><p class="font-black">{{ $ready ? 'Expediente listo para decisión' : 'Expediente incompleto' }}</p>@if(!$ready)<p class="mt-2 text-sm font-bold">Falta: {{ collect($missing)->values()->join(', ') }}{{ ! $vendor->user->hasVerifiedEmail() ? ($missing ? ', ' : '').'verificar correo' : '' }}.</p>@endif</div>
                <ul class="mt-4 space-y-2 text-sm font-bold"><li>{{ $vendor->user->hasVerifiedEmail() ? '✓' : '×' }} Correo verificado</li><li>{{ $vendor->categories->isNotEmpty() ? '✓' : '×' }} Al menos un rubro ofrecido</li><li>{{ $vendor->businessHoursConfigured() ? '✓' : '×' }} Horario configurado</li><li>{{ blank($vendor->description) ? '×' : '✓' }} Descripción profesional</li><li>{{ $vendor->user->community ? '✓' : '×' }} Comunidad asignada</li></ul>
            </section>

            <section class="rounded-[1.75rem] border border-brand/10 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black">Documentos de verificación</h2>
                <p class="mt-2 text-sm font-semibold text-brand-muted">Archivos privados. Contrasta identidad y vigencia; no copies datos sensibles fuera del expediente. La constancia fiscal o evidencia de negocio es opcional y nunca bloquea la aprobación para operar.</p>
                <div class="mt-4 space-y-3">
                    @forelse($documents as $document)
                        <article class="rounded-2xl bg-brand-surface p-4"><div class="flex flex-wrap items-center justify-between gap-2"><div><strong class="text-sm">{{ $document->label() }}</strong><p class="text-xs font-bold text-brand-muted">{{ \App\Models\VendorVerificationDocument::STATUSES[$document->status] ?? $document->status }} · {{ number_format($document->size / 1024, 0) }} KB</p></div><a class="rounded-full border border-brand/15 px-3 py-2 text-xs font-black" href="{{ route('verification-documents.download', $document) }}">Abrir</a></div>
                        @if($document->status === 'pending')<form class="mt-3 space-y-2" method="POST" action="{{ route('admin.verification-documents.review', $document) }}">@csrf @method('PATCH')<textarea class="min-h-20 w-full rounded-xl border border-brand/10 px-3 py-2 text-sm" name="review_note" minlength="10" maxlength="1000" required placeholder="Resultado de la revisión"></textarea><div class="flex gap-2"><button class="flex-1 rounded-full bg-brand-success px-3 py-2 text-xs font-black text-white" name="decision" value="approved">Aprobar</button><button class="flex-1 rounded-full border border-red-200 px-3 py-2 text-xs font-black text-red-700" name="decision" value="rejected">Rechazar</button></div></form>@elseif($document->review_note)<p class="mt-2 text-xs font-semibold text-brand-muted">{{ $document->review_note }}</p>@endif
                        </article>
                    @empty<p class="rounded-2xl border border-dashed border-brand/20 p-5 text-sm font-bold text-brand-muted">La persona aún no ha enviado documentos.</p>@endforelse
                </div>
                @if($documents->hasPages())<div class="mt-5">{{ $documents->links() }}</div>@endif
            </section>            @if($vendor->status === 'active')
                <section class="rounded-[1.75rem] border border-brand/10 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black">Distintivo de confianza</h2>
                    @if($vendor->verified_at)
                        <div class="mt-4 rounded-2xl bg-brand-success-soft p-4 text-sm text-brand-success"><strong>Actividad comercial verificada</strong><p class="mt-1 font-semibold">Nivel: {{ $vendor->verification_level === 'business' ? 'Identidad y negocio' : 'Identidad' }} · {{ $vendor->verified_at->format('d/m/Y') }}</p></div>
                        @if(auth()->user()->hasRole('superadmin'))<form class="mt-4" method="POST" action="{{ route('admin.vendors.verification.revoke', $vendor) }}">@csrf @method('DELETE')<textarea class="min-h-20 w-full rounded-2xl border border-red-200 px-4 py-3 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Motivo para retirar el distintivo"></textarea><button class="mt-3 w-full rounded-full border border-red-200 px-5 py-3 font-black text-red-700" type="submit">Retirar verificación</button></form>@endif
                    @elseif(auth()->user()->hasRole('superadmin'))
                        <p class="mt-2 text-sm font-semibold text-brand-muted">Aprobar el perfil permite operar; este distintivo requiere una revisión adicional. Pagar una tarifa de revisión nunca garantiza obtenerlo.</p>
                        <form class="mt-4 space-y-3" method="POST" action="{{ route('admin.vendors.verify', $vendor) }}">@csrf @method('PATCH')<select class="w-full rounded-2xl bg-brand-surface px-4 py-3" name="verification_level" required><option value="identity">Identidad revisada</option><option value="business">Identidad y negocio revisados</option></select><textarea class="min-h-24 w-full rounded-2xl border border-brand/10 px-4 py-3 text-sm" name="verification_note" minlength="10" maxlength="1000" required placeholder="Anota qué documentos o evidencia fueron contrastados"></textarea><button class="w-full rounded-full bg-brand px-5 py-3 font-black text-white" type="submit">Otorgar distintivo verificado</button></form>
                    @else
                        <p class="mt-2 text-sm font-semibold text-brand-muted">Solo el superadministrador puede otorgar el distintivo después de revisar el expediente.</p>
                    @endif
                </section>
            @endif
            @if($vendor->status === 'pending')
                <section class="rounded-[1.75rem] border border-brand-orange/20 bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Decisión administrativa</h2><p class="mt-2 text-sm font-semibold text-brand-muted">Aprueba solo después de contrastar toda la información anterior.</p>
                    @if($ready)<form class="mt-5" method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-brand-success px-5 py-3 font-black text-white" type="submit">Habilitar actividad comercial</button></form>@else<button class="mt-5 w-full cursor-not-allowed rounded-full bg-brand-disabled-surface px-5 py-3 font-black text-brand-disabled-copy" disabled>Faltan requisitos</button>@endif
                    <form class="mt-4" method="POST" action="{{ route('admin.vendors.reject', $vendor) }}">@csrf @method('PATCH')<label class="text-sm font-black">Cambios que debe realizar</label><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Explica con precisión qué información debe corregir o completar."></textarea><button class="mt-3 w-full rounded-full border border-red-200 px-5 py-3 font-black text-red-700" type="submit">Solicitar cambios</button></form>
                </section>
            @elseif($vendor->status === 'active')
                <section class="rounded-[1.75rem] border border-red-200 bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Moderación</h2><form class="mt-4" method="POST" action="{{ route('admin.vendors.suspend', $vendor) }}">@csrf @method('PATCH')<textarea class="min-h-24 w-full rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm" name="reason" minlength="10" maxlength="1000" required placeholder="Motivo documentado de la suspensión"></textarea><button class="mt-3 w-full rounded-full bg-red-700 px-5 py-3 font-black text-white" type="submit">Suspender actividad comercial</button></form></section>
            @elseif($vendor->status === 'suspended' && $ready)
                <section class="rounded-[1.75rem] bg-white p-6 shadow-sm"><p class="text-sm font-semibold">Motivo de suspensión: {{ $vendor->suspension_reason }}</p><form class="mt-4" method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-brand-success px-5 py-3 font-black text-white" type="submit">Reactivar actividad comercial</button></form></section>
                <a class="inline-flex rounded-full border border-brand/10 bg-white px-5 py-3 text-sm font-black" href="{{ route('admin.support.index', ['q' => $vendor->user->email]) }}">Buscar casos de soporte de esta cuenta</a>
            @endif
        </aside>
    </div>
</x-admin-layout>
