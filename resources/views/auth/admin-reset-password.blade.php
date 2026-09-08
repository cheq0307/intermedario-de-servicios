<x-layouts.auth title="Nueva contraseña administrativa">
    <h1 class="text-2xl font-black">Nueva contraseña administrativa</h1>
    <form method="POST" action="{{ route('admin.password.update') }}" class="mt-5 space-y-4">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="block">Correo<input name="email" type="email" value="{{ old('email', $email) }}" required class="mt-2 w-full rounded-xl border p-3"></label>
        <label class="block">Nueva contraseña<input name="password" type="password" required minlength="12" autocomplete="new-password" class="mt-2 w-full rounded-xl border p-3"></label>
        <p class="text-sm text-brand-copy">Al menos 12 caracteres, con letras y números; distinta de tu cuenta de Plaza Local.</p>
        <label class="block">Confirmar contraseña<input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border p-3"></label>
        @foreach($errors->all() as $error)<p role="alert" class="text-red-700">{{ $error }}</p>@endforeach
        <button class="min-h-11 rounded-xl bg-brand px-5 py-3 font-bold text-white">Guardar contraseña</button>
    </form>
</x-layouts.auth>
