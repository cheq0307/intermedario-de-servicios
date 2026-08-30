<x-admin-layout title="Cuentas" section="accounts">
    @php
        $commercialLabels = ['draft' => 'Borrador', 'pending' => 'Pendiente de revisión', 'active' => 'Habilitada', 'rejected' => 'Requiere cambios', 'suspended' => 'Suspendida'];
        $accountLabels = ['active' => 'Activa', 'suspended' => 'Suspendida', 'deactivated' => 'Dada de baja'];
        $hasFilters = filled($filters['q'] ?? null) || filled($filters['account_status'] ?? null) || filled($filters['commercial_status'] ?? null);
    @endphp

    @if(session('status'))
        <div class="mb-6 rounded-2xl bg-brand-success-soft px-5 py-4 text-sm font-black text-brand-success">{{ session('status') }}</div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Gestión unificada</p>
            <h1 class="mt-2 text-3xl font-black">Cuentas</h1>
            <p class="mt-2 max-w-3xl font-semibold text-brand-muted">Consulta identidad, autoridad, estado de acceso y expediente comercial sin tratarlos como tipos de usuario distintos.</p>
        </div>
        <strong class="rounded-full bg-white px-4 py-2 text-sm shadow-sm">{{ $users->total() }} resultados</strong>
    </div>

    <form class="mt-6 grid gap-3 rounded-3xl border border-brand/10 bg-white p-4 shadow-sm md:grid-cols-[minmax(16rem,1fr)_13rem_15rem_auto]" method="GET">
        <label class="sr-only" for="account-search">Buscar cuenta</label>
        <input id="account-search" class="rounded-2xl bg-brand-page px-4 py-3" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Buscar por nombre, correo o teléfono">
        <label class="sr-only" for="account-status-filter">Estado de cuenta</label>
        <select id="account-status-filter" class="rounded-2xl bg-brand-page px-4 py-3" name="account_status" onchange="this.form.submit()">
            <option value="">Todos los accesos</option>
            @foreach($accountLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['account_status'] ?? '') === $value)>{{ $label }}</option>@endforeach
        </select>
        <label class="sr-only" for="commercial-status-filter">Expediente comercial</label>
        <select id="commercial-status-filter" class="rounded-2xl bg-brand-page px-4 py-3" name="commercial_status" onchange="this.form.submit()">
            <option value="">Todos los expedientes</option>
            <option value="none" @selected(($filters['commercial_status'] ?? '') === 'none')>Sin expediente</option>
            @foreach($commercialLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['commercial_status'] ?? '') === $value)>{{ $label }}</option>@endforeach
            <option value="verified" @selected(($filters['commercial_status'] ?? '') === 'verified')>Con identidad verificada</option>
        </select>
        @if($hasFilters)<a class="self-center text-center text-sm font-black text-brand-success" href="{{ route('admin.users.index') }}">Limpiar</a>@else<span></span>@endif
    </form>
    <p class="mt-2 px-2 text-xs font-semibold text-brand-muted">Los estados se filtran al cambiar la selección. Para buscar, escribe y presiona Enter.</p>

    <div class="mt-6 overflow-hidden rounded-3xl border border-brand/10 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1020px] text-left text-sm">
                <thead class="bg-brand-neutral-soft text-brand-copy">
                    <tr>
                        <th class="p-4">Cuenta</th>
                        <th class="p-4">Rol</th>
                        <th class="p-4">Comunidad</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4">Expediente comercial</th>
                        <th class="p-4">Registro</th>
                        <th class="p-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $role = $user->hasRole('superadmin') ? 'Superadministrador' : ($user->hasRole('admin') ? 'Administrador' : 'Cuenta normal');
                            $commercialStatus = $user->vendor ? ($commercialLabels[$user->vendor->status] ?? ucfirst($user->vendor->status)) : '—';
                        @endphp
                        <tr class="border-t border-brand/10 align-middle">
                            <td class="p-4">
                                <strong>{{ $user->name }}</strong>
                                <span class="mt-1 block text-xs text-brand-muted">{{ $user->email }}{{ $user->phone ? ' · '.$user->phone : '' }}</span>
                                <span class="mt-1 block text-xs font-semibold {{ $user->hasVerifiedEmail() ? 'text-brand-success' : 'text-brand-warning' }}">{{ $user->hasVerifiedEmail() ? 'Correo verificado' : 'Correo pendiente' }}</span>
                            </td>
                            <td class="p-4"><span class="inline-flex rounded-full border border-brand/20 px-3 py-1 text-xs font-black text-brand-copy-strong">{{ $role }}</span></td>
                            <td class="p-4">{{ $user->community?->name ?? 'Sin comunidad' }}</td>
                            <td class="p-4">
                                <span class="inline-flex items-center gap-2 font-bold">
                                    <span class="size-2 rounded-full {{ $user->account_status === 'active' ? 'bg-brand-success' : 'bg-red-600' }}"></span>
                                    {{ $accountLabels[$user->account_status] ?? ucfirst($user->account_status) }}
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="{{ $user->vendor?->status === 'suspended' ? 'font-bold text-red-700' : 'text-brand-copy' }}">{{ $commercialStatus }}</span>
                                @if($user->vendor?->verified_at)<span class="mt-1 block text-xs font-semibold text-brand-success">Identidad verificada</span>@endif
                            </td>
                            <td class="p-4">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="p-4 font-black text-brand-success">
                                <a href="{{ route('profile.show', $user) }}">Ver</a>
                                <span aria-hidden="true"> · </span>
                                <a href="{{ route('admin.users.show', $user) }}">Administrar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-10 text-center font-bold text-brand-muted" colspan="7">No hay cuentas con esos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($users->hasPages())<div class="mt-5">{{ $users->links() }}</div>@endif
</x-admin-layout>
