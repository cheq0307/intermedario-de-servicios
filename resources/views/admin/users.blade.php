<x-admin-layout title="Cuentas" section="accounts">
    @php
        $hasFilters = collect(['q', 'account_status', 'community_id', 'verification', 'phone_status'])
            ->contains(fn (string $filter) => filled($filters[$filter] ?? null));
    @endphp

    @if(session('status'))
        <div class="mb-6 rounded-2xl bg-brand-success-soft px-5 py-4 text-sm font-black text-brand-success">{{ session('status') }}</div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Gestión de acceso</p>
            <h1 class="mt-2 text-3xl font-black">Cuentas de Plaza Local</h1>
            <p class="mt-2 max-w-3xl font-semibold text-brand-muted">Localiza una cuenta por sus datos de contacto, comunidad o estado y abre su administración cuando necesites intervenir.</p>
        </div>
        <strong class="rounded-full bg-white px-4 py-2 text-sm shadow-sm">{{ $users->total() }} resultados</strong>
    </div>

    <form class="mt-6 grid gap-3 rounded-3xl border border-brand/10 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-3" method="GET">
        <label class="sm:col-span-2 xl:col-span-1" for="account-search">
            <span class="mb-1.5 block text-xs font-black text-brand-copy">Nombre, correo o teléfono</span>
            <input id="account-search" class="min-h-11 w-full rounded-2xl bg-brand-page px-4 py-3" type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Buscar una cuenta">
        </label>

        <label for="account-status-filter">
            <span class="mb-1.5 block text-xs font-black text-brand-copy">Acceso</span>
            <select id="account-status-filter" class="min-h-11 w-full rounded-2xl bg-brand-page px-4 py-3" name="account_status" onchange="this.form.submit()">
                <option value="">Todos los accesos</option>
                @foreach($accountLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['account_status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label for="community-filter">
            <span class="mb-1.5 block text-xs font-black text-brand-copy">Comunidad</span>
            <select id="community-filter" class="min-h-11 w-full rounded-2xl bg-brand-page px-4 py-3" name="community_id" onchange="this.form.submit()">
                <option value="">Todas las comunidades</option>
                @foreach($communities as $community)
                    <option value="{{ $community->id }}" @selected((string) ($filters['community_id'] ?? '') === (string) $community->id)>{{ $community->name }} · {{ $community->municipality }}</option>
                @endforeach
            </select>
        </label>

        <label for="email-verification-filter">
            <span class="mb-1.5 block text-xs font-black text-brand-copy">Correo</span>
            <select id="email-verification-filter" class="min-h-11 w-full rounded-2xl bg-brand-page px-4 py-3" name="verification" onchange="this.form.submit()">
                <option value="">Todos los correos</option>
                <option value="verified" @selected(($filters['verification'] ?? '') === 'verified')>Verificado</option>
                <option value="pending" @selected(($filters['verification'] ?? '') === 'pending')>Pendiente</option>
            </select>
        </label>

        <label for="phone-filter">
            <span class="mb-1.5 block text-xs font-black text-brand-copy">Teléfono</span>
            <select id="phone-filter" class="min-h-11 w-full rounded-2xl bg-brand-page px-4 py-3" name="phone_status" onchange="this.form.submit()">
                <option value="">Todos los teléfonos</option>
                <option value="verified" @selected(($filters['phone_status'] ?? '') === 'verified')>Verificado por SMS</option>
                <option value="pending" @selected(($filters['phone_status'] ?? '') === 'pending')>Sin verificar</option>
                <option value="present" @selected(($filters['phone_status'] ?? '') === 'present')>Registrado</option>
                <option value="missing" @selected(($filters['phone_status'] ?? '') === 'missing')>Faltante</option>
            </select>
        </label>

        <div class="flex min-h-11 items-center justify-end sm:col-span-2 xl:col-span-3">
            @if($hasFilters)
                <a class="text-sm font-black text-brand-success" href="{{ route('admin.users.index') }}">Limpiar filtros</a>
            @else
                <p class="text-xs font-semibold text-brand-muted">Los selectores filtran al cambiar; la búsqueda se ejecuta al presionar Enter.</p>
            @endif
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-3xl border border-brand/10 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-brand-neutral-soft text-brand-copy">
                    <tr>
                        <th class="p-4">Cuenta</th>
                        <th class="p-4">Contacto</th>
                        <th class="p-4">Comunidad</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4">Registro</th>
                        <th class="p-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-t border-brand/10 align-middle">
                            <td class="p-4"><strong>{{ $user->normalizedDisplayName() }}</strong></td>
                            <td class="p-4">
                                <span class="block text-xs font-semibold text-brand-copy">{{ $user->email }}</span>
                                <span class="mt-1 block text-xs font-semibold {{ $user->phone ? 'text-brand-copy' : 'text-brand-warning' }}">{{ $user->phone ?: 'Teléfono pendiente' }}</span>
                                <span class="mt-1 block text-xs font-semibold {{ $user->hasVerifiedEmail() ? 'text-brand-success' : 'text-brand-warning' }}">{{ $user->hasVerifiedEmail() ? 'Correo verificado' : 'Correo pendiente' }}</span>
                                <span class="mt-1 block text-xs text-brand-copy">{{ $user->phone_verified_at ? 'Teléfono verificado por SMS' : 'Teléfono sin verificar' }}</span>
                            </td>
                            <td class="p-4">{{ $user->community?->name ?? 'Sin comunidad' }}</td>
                            <td class="p-4">
                                <span class="inline-flex items-center gap-2 font-bold">
                                    <span class="size-2 rounded-full {{ $user->account_status === 'active' ? 'bg-brand-success' : 'bg-red-600' }}"></span>
                                    {{ $accountLabels[$user->account_status] ?? ucfirst($user->account_status) }}
                                </span>
                            </td>
                            <td class="p-4">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="p-4 text-right"><a class="font-black text-brand-success" href="{{ route('admin.users.show', $user) }}">Administrar</a></td>
                        </tr>
                    @empty
                        <tr><td class="p-10 text-center font-bold text-brand-muted" colspan="6">No hay cuentas con esos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-bold text-brand-muted">Mostrando {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} de {{ $users->total() }} cuentas</p>
        @if($users->hasPages())<div>{{ $users->links() }}</div>@endif
    </div>
</x-admin-layout>
