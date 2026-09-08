<x-admin-layout title="Administradores" section="team">
    <h1 class="text-3xl font-black">Administradores</h1>
    <p class="mt-2 text-brand-muted">Gestiona el acceso del equipo administrativo.</p>
    @if(session('status'))<p class="mt-4 text-brand-success">{{ session('status') }}</p>@endif
    @if(session('invitation_url'))<label class="mt-3 block">Enlace privado de invitación<input readonly class="mt-2 w-full rounded-xl border bg-white p-3" value="{{ session('invitation_url') }}" onclick="this.select()"></label>@endif
    @foreach($errors->all() as $error)<p class="mt-2 text-red-700">{{ $error }}</p>@endforeach
    <form method="GET" class="mt-5"><label>Buscar administrador<input class="ml-3 rounded-xl border bg-white p-3" name="q" value="{{ $q }}" placeholder="Nombre, correo o teléfono"></label></form>
    <div class="mt-5 overflow-x-auto rounded-2xl border bg-white">
        <table class="w-full min-w-[720px] text-left"><thead><tr class="border-b"><th class="p-4">Administrador</th><th>Contacto</th><th>Verificación</th><th>Acceso</th><th>Acción</th></tr></thead>
        <tbody>@forelse($admins as $admin)<tr class="border-b"><td class="p-4">{{ $admin->name }}<span class="block text-xs text-brand-muted">{{ $admin->commercialRoleLabel() }}</span></td><td>{{ $admin->email }}<span class="block text-xs">{{ $admin->phone ?: 'Teléfono pendiente' }}</span></td><td class="text-sm">Correo: {{ $admin->hasVerifiedEmail() ? 'verificado' : 'pendiente' }}<br>Teléfono: {{ $admin->phone_verified_at ? 'verificado' : 'pendiente' }}</td><td>{{ $admin->active ? 'Activo' : 'Suspendido' }}</td><td>@unless($admin->hasRole('superadmin'))<form method="POST" action="{{ route('admin.team.toggle',$admin) }}">@csrf @method('PATCH')<button class="min-h-11 text-brand-success font-bold">{{ $admin->active ? 'Suspender' : 'Reactivar' }}</button></form>@endunless</td></tr>@empty<tr><td colspan="5" class="p-6">No hay administradores con esa búsqueda.</td></tr>@endforelse</tbody></table>
    </div>
    <div class="mt-4">{{ $admins->links() }}</div>
    <section class="mt-8 rounded-2xl border bg-white p-5">
        <h2 class="text-xl font-bold">Invitar administrador</h2>
        <p class="mt-2 text-sm text-brand-muted">Si ya tiene cuenta de Plaza Local, usa exactamente su correo y teléfono. Esta invitación autoriza el vínculo de ambas cuentas.</p>
        <form method="POST" action="{{ route('admin.team.invite') }}" class="mt-4 grid gap-4 sm:grid-cols-2">@csrf
            <label>Nombre completo<input class="mt-2 w-full rounded-xl border p-3" name="name" required maxlength="120" value="{{ old('name') }}"></label>
            <label>Correo<input class="mt-2 w-full rounded-xl border p-3" type="email" name="email" required value="{{ old('email') }}"></label>
            <label>Teléfono<input class="mt-2 w-full rounded-xl border p-3" name="phone" pattern="[0-9]{10}" maxlength="10" inputmode="numeric" required value="{{ old('phone') }}"></label>
            <button class="self-end min-h-11 rounded-xl bg-brand p-3 text-white font-bold">Crear invitación</button>
        </form>
    </section>
</x-admin-layout>
