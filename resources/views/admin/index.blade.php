<x-admin-layout title="Centro de operación" section="center" :unread-count="$metrics['unread_notifications']" :pending-count="$metrics['pending_vendors']">
        @if(session('status'))<div class="mb-6 rounded-2xl border border-brand-success-bright/20 bg-brand-success-soft px-5 py-4 text-sm font-black text-brand-success" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-black text-red-700" role="alert">{{ $errors->first() }}</div>@endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-xs font-black uppercase tracking-[.18em] text-brand-orange">{{ $isSuperadmin ? 'Gobierno global' : 'Operación administrativa' }}</p><h1 class="mt-2 text-3xl font-black">{{ $isSuperadmin ? 'Resumen de Plaza Local' : 'Centro de operación' }}</h1><p class="mt-2 max-w-2xl text-sm font-semibold leading-6 text-brand-muted">{{ $isSuperadmin ? 'Supervisa el estado general, las excepciones y la expansión de todas las comunidades.' : 'Atiende revisiones, incidencias y moderación sin mezclar el cargo con actividad comercial.' }}</p></div>
            <span class="rounded-full bg-brand-success-soft px-5 py-3 text-center text-sm font-black text-brand-success">Cuenta exclusivamente administrativa</span>
        </div>

        <section class="mt-7 grid gap-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
            <a class="rounded-3xl border border-brand-orange/20 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('admin.vendors.index', ['status' => 'pending']) }}"><strong class="text-3xl"><span data-admin-metric="pending_vendors">{{ $metrics['pending_vendors'] }}</span></strong><p class="mt-2 text-sm font-bold text-brand-muted">Cuentas por verificar</p><span class="mt-3 block text-xs font-black text-brand-danger-warm">Revisar →</span></a>
            <a class="rounded-3xl border border-brand/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('disputes.admin-index') }}"><strong class="text-3xl"><span data-admin-metric="open_disputes">{{ $metrics['open_disputes'] }}</span></strong><p class="mt-2 text-sm font-bold text-brand-muted">Disputas abiertas</p><span class="mt-3 block text-xs font-black text-brand-success">Atender →</span></a>
            <a class="rounded-3xl border border-brand/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('admin.support.index') }}"><strong class="text-3xl"><span data-admin-metric="open_support_tickets">{{ $metrics['open_support_tickets'] }}</span></strong><p class="mt-2 text-sm font-bold text-brand-muted">Casos de soporte</p><span class="mt-3 block text-xs font-black text-brand-success">Responder →</span></a>
            <a class="rounded-3xl border border-brand/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('admin.operations.index') }}"><strong class="text-3xl"><span data-admin-metric="active_orders">{{ $metrics['active_orders'] }}</span></strong><p class="mt-2 text-sm font-bold text-brand-muted">Operaciones activas</p><span class="mt-3 block text-xs font-black text-brand-success">Supervisar →</span></a>
            <a class="rounded-3xl border border-brand/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('admin.users.index') }}"><strong class="text-3xl"><span data-admin-metric="users">{{ $metrics['users'] }}</span></strong><p class="mt-2 text-sm font-bold text-brand-muted">Cuentas registradas</p><span class="mt-3 block text-xs font-black text-brand-success">Abrir directorio →</span></a>
            <a class="rounded-3xl border border-brand/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5" href="{{ route('admin.posts.index') }}"><strong class="text-3xl"><span data-admin-metric="active_posts">{{ $metrics['active_posts'] }}</span></strong><p class="mt-2 text-sm font-bold text-brand-muted">Publicaciones activas</p><span class="mt-3 block text-xs font-black text-brand-success">Moderar →</span></a>
        </section>

        <details class="mt-6 rounded-2xl border border-brand/10 bg-white px-5 py-4">
            <summary class="cursor-pointer font-black">Información y alcance de mi cargo</summary>
            <p class="mt-3 max-w-3xl text-sm font-semibold leading-6 text-brand-copy">{{ $isSuperadmin ? 'Tienes control global: expansión territorial, delegación de administradores, métricas, auditoría y excepciones. No realizas compras, ventas ni publicaciones con esta cuenta.' : 'Puedes revisar expedientes comerciales, moderar publicaciones, administrar comunidades y rubros, atender soporte y disputas. No puedes delegar administradores ni acceder a actividad comercial mientras conserves el cargo.' }}</p>
        </details>

        <details class="mt-8 rounded-[1.75rem] border border-brand/10 bg-white" id="configuracion">
            <summary class="cursor-pointer list-none px-6 py-5"><span class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Configuración</span><span class="mt-1 block text-xl font-black">Territorio, códigos postales y rubros</span><span class="mt-2 block text-sm font-semibold text-brand-muted">Abre esta sección únicamente cuando necesites cambiar el catálogo global.</span></summary>
            <div class="border-t border-brand/10 px-6 pb-6">
            <div class="mt-5 rounded-2xl border border-brand/10 bg-brand-surface p-4">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm font-black">Catálogo postal listo</p><p class="mt-1 text-xs font-semibold text-brand-muted">{{ number_format($metrics['postal_codes']) }} asentamientos disponibles para autocompletar comunidades.</p></div><span class="rounded-full bg-brand-success-soft px-3 py-1 text-xs font-black text-brand-success">Mantenimiento global</span></div>
                @if($isSuperadmin)
                    <details class="mt-3 border-t border-brand/10 pt-3">
                        <summary class="cursor-pointer text-xs font-black text-brand">Actualizar catálogo postal</summary>
                        <p class="mt-2 text-xs font-semibold leading-5 text-brand-muted">Solo es necesario cuando Correos de México publique un catálogo nuevo. Los registros se actualizan sin duplicarse.</p>
                        <form class="mt-3 flex flex-col gap-2 sm:flex-row" method="POST" action="{{ route('admin.postal-codes.import') }}" enctype="multipart/form-data">@csrf
                            <input class="max-w-sm rounded-xl border border-brand/10 bg-white px-3 py-2 text-sm" type="file" name="catalog" accept=".txt,text/plain" required>
                            <button class="rounded-full bg-brand-success px-5 py-2.5 text-sm font-black text-white" type="submit">Importar actualización</button>
                            <a class="self-center text-xs font-black text-brand-success underline" href="https://www.correosdemexico.gob.mx/SSLServicios/ConsultaCP/CodigoPostal_Exportar.aspx" target="_blank" rel="noopener noreferrer">Descargar catálogo oficial</a>
                        </form>
                        @error('catalog')<p class="mt-2 text-sm font-bold text-red-700">{{ $message }}</p>@enderror
                    </details>
                @endif
            </div>
        <section class="mt-8 rounded-[1.75rem] border border-brand/10 bg-white p-6" id="comunidades">
            <div><p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Cobertura territorial</p><h2 class="mt-1 text-xl font-black">Comunidades disponibles</h2><p class="mt-2 text-sm font-semibold text-brand-muted">El código postal completa los datos oficiales. Las personas elegirán comunidades concretas al solicitar u ofrecer; las coordenadas y el radio son opcionales y solo mejoran “cerca de mí”.</p></div>
            <form class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5" method="POST" action="{{ route('admin.communities.store') }}">@csrf
                <label><span class="text-xs font-black">Código postal</span><input id="community-postal-code" class="mt-2 w-full rounded-xl border border-brand/10 bg-brand-surface px-3 py-2.5" name="postal_code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" autocomplete="postal-code" placeholder="5 dígitos"><span id="postal-code-status" class="mt-1 block text-xs font-bold text-brand-muted">Al completar 5 dígitos buscaremos automáticamente.</span></label>
                <label><span class="text-xs font-black">Nombre de la comunidad</span><input id="community-name" class="mt-2 w-full rounded-xl border border-brand/10 bg-brand-surface px-3 py-2.5" name="name" list="postal-settlements" required maxlength="120" placeholder="Ej. San Miguel"><datalist id="postal-settlements"></datalist></label>
                <label><span class="text-xs font-black">Municipio o ciudad</span><input id="community-municipality" class="mt-2 w-full rounded-xl border border-brand/10 bg-brand-surface px-3 py-2.5" name="municipality" required maxlength="120"></label>
                <label><span class="text-xs font-black">Estado</span><input id="community-state" class="mt-2 w-full rounded-xl border border-brand/10 bg-brand-surface px-3 py-2.5" name="state" maxlength="120"></label>

                <button class="self-end rounded-full bg-brand px-5 py-3 text-sm font-black text-white" type="submit">Agregar comunidad</button>
            </form>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($communities as $community)
                    <article class="rounded-2xl border p-4 {{ $community->is_active ? 'border-brand/10 bg-brand-surface' : 'border-brand-danger-warm/20 bg-orange-50/40' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div><h3 class="font-black">{{ $community->name }}</h3><p class="mt-1 text-xs font-bold text-brand-muted">{{ $community->municipality }}{{ $community->state ? ', '.$community->state : '' }}</p></div>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $community->is_active ? 'bg-brand-success-soft text-brand-success' : 'bg-orange-100 text-brand-danger-warm' }}">{{ $community->is_active ? 'Activa' : 'Suspendida' }}</span>
                        </div>
                        <p class="mt-3 text-xs font-bold text-brand-copy">{{ $community->users_count }} usuarios · {{ $community->job_requests_count }} solicitudes · {{ $community->postal_code ? 'CP '.$community->postal_code : 'Sin CP' }}</p>
                        <p class="mt-1 text-xs font-bold {{ $community->hasCoordinates() ? 'text-brand-success' : 'text-brand-danger-warm' }}">{{ $community->hasCoordinates() ? 'Centro configurado · radio '.number_format((float) $community->default_radius_km, 1).' km' : 'Sin coordenadas: funciona por selección explícita de comunidad' }}</p>
                        <details class="mt-3"><summary class="cursor-pointer text-xs font-black text-brand">Editar comunidad y ubicación</summary>
                            <form class="mt-3 grid gap-2" method="POST" action="{{ route('admin.communities.update', $community) }}">@csrf @method('PATCH')
                                <label class="text-xs font-black">Nombre completo<input class="mt-1 w-full rounded-xl border border-brand/10 bg-white px-3 py-2 text-sm font-normal" name="name" value="{{ $community->name }}" required maxlength="120"></label>
                                <label class="text-xs font-black">Municipio o ciudad<input class="mt-1 w-full rounded-xl border border-brand/10 bg-white px-3 py-2 text-sm font-normal" name="municipality" value="{{ $community->municipality }}" required maxlength="120"></label>
                                <label class="text-xs font-black">Estado<input class="mt-1 w-full rounded-xl border border-brand/10 bg-white px-3 py-2 text-sm font-normal" name="state" value="{{ $community->state }}" maxlength="120"></label>
                                <label class="text-xs font-black">Código postal<input class="mt-1 w-full rounded-xl border border-brand/10 bg-white px-3 py-2 text-sm font-normal" name="postal_code" value="{{ $community->postal_code }}" inputmode="numeric" pattern="[0-9]{5}" maxlength="5"></label>
                                <div class="grid grid-cols-2 gap-2"><label class="text-xs font-black">Latitud<input class="mt-1 w-full rounded-xl border border-brand/10 bg-white px-3 py-2 text-sm font-normal" type="number" name="latitude" value="{{ $community->latitude }}" min="-90" max="90" step="0.0000001" placeholder="Opcional"></label><label class="text-xs font-black">Longitud<input class="mt-1 w-full rounded-xl border border-brand/10 bg-white px-3 py-2 text-sm font-normal" type="number" name="longitude" value="{{ $community->longitude }}" min="-180" max="180" step="0.0000001" placeholder="Opcional"></label></div>
                                <label class="text-xs font-black">Radio auxiliar en km<input class="mt-1 w-full rounded-xl border border-brand/10 bg-white px-3 py-2 text-sm font-normal" type="number" name="default_radius_km" value="{{ $community->default_radius_km }}" min="1" max="100" step="0.5" required></label>
                                <button class="rounded-full bg-brand px-4 py-2 text-xs font-black text-white" type="submit">Guardar cambios</button>
                            </form>
                        </details>
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-brand/10 pt-3">
                            <form method="POST" action="{{ route('admin.communities.toggle', $community) }}">@csrf @method('PATCH')<button class="text-xs font-black {{ $community->is_active ? 'text-brand-danger-warm' : 'text-brand-success' }}" type="submit">{{ $community->is_active ? 'Suspender comunidad' : 'Reactivar comunidad' }}</button></form>
                            @if($isSuperadmin)
                                @if(!$community->is_active && $community->users_count === 0 && $community->job_requests_count === 0)
                                    <form method="POST" action="{{ route('admin.communities.destroy', $community) }}" onsubmit="return confirm('¿Eliminar definitivamente esta comunidad vacía?')">@csrf @method('DELETE')<button class="text-xs font-black text-red-700" type="submit">Eliminar definitivamente</button></form>
                                @else
                                    <span class="text-[.68rem] font-bold text-brand-muted">Para eliminar: debe estar suspendida y sin historial asociado.</span>
                                @endif
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            @if($communities->hasPages())<div class="mt-5">{{ $communities->links() }}</div>@endif
        </section>

        <section class="mt-8 rounded-[1.75rem] border border-brand/10 bg-white p-6" id="rubros">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Configuración global</p><h2 class="mt-1 text-xl font-black">Catálogo maestro de rubros</h2><p class="mt-2 text-sm font-semibold text-brand-muted">Define las opciones disponibles para toda Plaza Local. No representa los intereses personales de esta cuenta administrativa: cada usuario elige “Mis intereses” y, por separado, “Lo que ofrezco”.</p></div>
                <form class="flex gap-2" method="POST" action="{{ route('admin.categories.store') }}">@csrf<input class="min-w-0 rounded-xl border border-brand/10 bg-brand-surface px-4 py-3" name="name" required maxlength="100" placeholder="Ej. Taxi o comida local"><button class="rounded-full bg-brand px-5 py-3 text-sm font-black text-white" type="submit">Agregar rubro</button></form>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($categories as $category)
                    <article class="rounded-2xl border border-brand/10 bg-brand-surface p-4">
                        <div class="flex items-start justify-between gap-3"><div><h3 class="font-black">{{ $category->name }}</h3><p class="mt-1 text-xs font-bold text-brand-muted">{{ $category->users_count }} interesados · {{ $category->vendors_count }} personas ofrecen · {{ $category->listings_count + $category->job_requests_count }} publicaciones</p></div><span class="rounded-full px-3 py-1 text-xs font-black {{ $category->is_active ? 'bg-brand-success-soft text-brand-success' : 'bg-brand-neutral-line text-brand-copy' }}">{{ $category->is_active ? 'Activo' : 'Inactivo' }}</span></div>
                        <form class="mt-3" method="POST" action="{{ route('admin.categories.toggle', $category) }}">@csrf @method('PATCH')<button class="text-xs font-black {{ $category->is_active ? 'text-red-700' : 'text-brand-success' }}" type="submit">{{ $category->is_active ? 'Desactivar' : 'Reactivar' }}</button></form>
                    </article>
                @endforeach
            </div>
            @if($categories->hasPages())<div class="mt-5">{{ $categories->links() }}</div>@endif
        </section>
            </div>
        </details>

        <section class="mt-8 rounded-[1.75rem] border border-brand-orange/20 bg-white p-5 shadow-sm sm:p-6" id="bandeja-operativa">
            <div><p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Bandeja operativa</p><h2 class="mt-1 text-xl font-black">Asuntos que requieren atención</h2></div>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                <a class="rounded-2xl bg-brand-orange-faint p-4" href="{{ route('admin.vendors.index', ['status' => 'pending']) }}"><strong>{{ $pendingVendorCount }} cuentas por verificar</strong><span class="mt-2 block text-xs font-black text-brand-danger-warm">Abrir expedientes →</span></a>
                <a class="rounded-2xl bg-brand-surface p-4" href="{{ route('disputes.admin-index') }}"><strong><span data-admin-metric="open_disputes">{{ $metrics['open_disputes'] }}</span> disputas abiertas</strong><span class="mt-2 block text-xs font-black text-brand-success">Atender casos →</span></a>
                <a class="rounded-2xl bg-brand-surface p-4" href="{{ route('admin.support.index') }}"><strong><span data-admin-metric="open_support_tickets">{{ $metrics['open_support_tickets'] }}</span> casos de soporte</strong><span class="mt-2 block text-xs font-black text-brand-success">Responder →</span></a>
            </div>
        </section>

        <details id="auditoria" class="mt-6 scroll-mt-24 rounded-[1.75rem] border border-brand/10 bg-white p-6"><summary class="cursor-pointer text-xl font-black">Auditoría reciente</summary><p class="mt-2 text-sm font-semibold text-brand-muted">Registro de quién realizó cada cambio administrativo y sobre qué elemento.</p><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[650px] text-left text-sm"><thead><tr class="text-brand-muted"><th class="p-3">Fecha</th><th class="p-3">Responsable</th><th class="p-3">Qué ocurrió</th><th class="p-3">Elemento afectado</th></tr></thead><tbody>@forelse($auditLogs as $log)<tr class="border-t border-brand/10"><td class="p-3">{{ $log->created_at->format('d/m/Y H:i') }}</td><td class="p-3">{{ $log->admin?->name ?? $log->user?->name ?? 'Sistema' }}</td><td class="p-3 font-black">{{ $auditActions[$log->action] ?? str_replace(['.', '_'], ' ', ucfirst($log->action)) }}</td><td class="p-3">{{ $auditSubjects[class_basename($log->subject_type)] ?? class_basename($log->subject_type) }} #{{ $log->subject_id }}</td></tr>@empty<tr><td class="p-6 text-center font-bold text-brand-muted" colspan="4">Todavía no hay acciones administrativas registradas.</td></tr>@endforelse</tbody></table></div>@if($auditLogs->hasPages())<div class="mt-5">{{ $auditLogs->links() }}</div>@endif</details>
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
            postalStatus.className = 'mt-1 block text-xs font-bold text-brand-muted';
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
                postalStatus.className = 'mt-1 block text-xs font-bold text-brand-success';
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
</x-admin-layout>
