<x-layouts.auth title="Aceptar invitación administrativa">
    <h1 class="text-2xl font-black">Tu cuenta administrativa</h1>
    <p class="mt-3">{{ $invitation->name }} · {{ $invitation->email }}</p>
    <p class="mt-2 text-brand-muted">Crea una contraseña propia de al menos 12 caracteres. Después verificarás tu correo y teléfono.</p>
    @foreach($errors->all() as $error)<p class="mt-2 text-red-700">{{ $error }}</p>@endforeach
    <form method="POST" action="{{ route('admin.invitation.accept',$token) }}" class="mt-5 space-y-4">@csrf
        <label class="block">Contraseña administrativa<input class="mt-2 w-full rounded-xl border p-3" type="password" name="password" autocomplete="new-password" required minlength="12"></label>
        <label class="block">Confirmar contraseña<input class="mt-2 w-full rounded-xl border p-3" type="password" name="password_confirmation" autocomplete="new-password" required></label>
        <button class="w-full rounded-xl bg-brand p-3 font-bold text-white">Crear cuenta administrativa</button>
    </form>
</x-layouts.auth>
