<x-layouts.auth title="Recuperar acceso administrativo">
    <h1 class="text-2xl font-black">Recuperar acceso administrativo</h1>
    <p class="mt-3 text-brand-copy">Este enlace cambia únicamente la contraseña de administración.</p>
    @if(session('status'))<p role="status" class="mt-4 text-brand-success">{{ session('status') }}</p>@endif
    <form method="POST" action="{{ route('admin.password.email') }}" class="mt-5 space-y-4">@csrf
        <label class="block">Correo administrativo<input type="email" name="email" required autocomplete="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border p-3"></label>
        @error('email')<p role="alert" class="text-red-700">{{ $message }}</p>@enderror
        <button class="min-h-11 rounded-xl bg-brand px-5 py-3 font-bold text-white">Enviar enlace</button>
    </form>
    <a href="{{ route('admin.login') }}" class="mt-5 inline-block text-brand-success">Volver al acceso administrativo</a>
</x-layouts.auth>
