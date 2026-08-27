<x-admin-layout title="Empleo y postulaciones" section="employment">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Gestión</p><h1 class="mt-2 text-3xl font-black">Empleo y postulaciones</h1><p class="mt-2 text-sm font-semibold text-[#6B7D83]">Supervisa vacantes pagadas, vigencia y respuesta recibida. Este flujo es distinto de contratar un servicio puntual.</p></div>
        <strong class="rounded-full bg-white px-4 py-2 text-sm shadow-sm">{{ $vacancies->total() }} vacantes</strong>
    </div>

    <form class="mt-6 grid gap-3 rounded-3xl border border-[#123B4A]/10 bg-white p-5 shadow-sm md:grid-cols-3" method="GET">
        <input class="rounded-2xl bg-[#F4F7F6] px-4 py-3" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Puesto, descripción, cuenta o correo">
        <select class="rounded-2xl bg-[#F4F7F6] px-4 py-3" name="status"><option value="">Todos los estados</option>@foreach(['pending_payment'=>'Pendiente de pago','published'=>'Publicada','closed'=>'Cerrada','expired'=>'Vencida'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
        <select class="rounded-2xl bg-[#F4F7F6] px-4 py-3" name="community_id"><option value="">Todas las comunidades</option>@foreach($communities as $community)<option value="{{ $community->id }}" @selected((string)($filters['community_id'] ?? '') === (string)$community->id)>{{ $community->name }} · {{ $community->municipality }}</option>@endforeach</select>
        <button class="rounded-full bg-[#123B4A] px-5 py-3 font-black text-white" type="submit">Aplicar filtros</button><a class="self-center text-center text-sm font-black text-[#D85B0B]" href="{{ route('admin.jobs.index') }}">Limpiar</a>
    </form>

    <div class="mt-6 overflow-hidden rounded-3xl border border-[#123B4A]/10 bg-white"><div class="overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm"><thead class="bg-[#EDF3F1] text-[#536A72]"><tr><th class="p-4">Vacante</th><th class="p-4">Cuenta</th><th class="p-4">Comunidad</th><th class="p-4">Estado</th><th class="p-4">Postulaciones</th><th class="p-4">Vigencia</th><th class="p-4">Acción</th></tr></thead><tbody>
        @forelse($vacancies as $vacancy)<tr class="border-t border-[#123B4A]/10"><td class="p-4"><strong>{{ $vacancy->title }}</strong><span class="mt-1 block text-xs text-[#6B7D83]">{{ $vacancy->category?->name ?? 'Sin rubro' }} · {{ $vacancy->vacancies_count }} lugar(es)</span></td><td class="p-4"><strong>{{ $vacancy->employer?->name }}</strong><span class="block text-xs text-[#6B7D83]">{{ $vacancy->employer?->email }}</span></td><td class="p-4">{{ $vacancy->community?->name ?? 'Sin comunidad' }}</td><td class="p-4"><span class="rounded-full bg-[#F4F7F6] px-3 py-1 text-xs font-black">{{ ['pending_payment'=>'Pendiente de pago','published'=>'Publicada','closed'=>'Cerrada','expired'=>'Vencida'][$vacancy->status] ?? ucfirst($vacancy->status) }}</span></td><td class="p-4 font-black">{{ $vacancy->applications_count }}</td><td class="p-4">{{ $vacancy->expires_at?->format('d/m/Y') ?? 'Sin límite' }}</td><td class="p-4"><a class="font-black text-[#14734A]" href="{{ route('vacancies.show', $vacancy) }}">Ver detalle</a></td></tr>
        @empty<tr><td class="p-10 text-center font-bold text-[#6B7D83]" colspan="7">No hay vacantes con esos filtros.</td></tr>@endforelse
    </tbody></table></div></div>
    @if($vacancies->hasPages())<div class="mt-5">{{ $vacancies->links() }}</div>@endif
</x-admin-layout>
