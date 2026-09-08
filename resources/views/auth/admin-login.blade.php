<x-layouts.auth title="Acceso administrativo">
    <h1 class="text-2xl font-black">Administración de Plaza Local</h1>
    <p class="mt-3 text-brand-muted">Entra con tu cuenta administrativa.</p>
    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-5">
        @csrf
        <label class="block">Correo administrativo<input class="mt-2 w-full rounded-xl border p-3" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"></label>
        @error('email')<p class="text-red-700">{{ $message }}</p>@enderror
        <label class="block">Contraseña<input class="mt-2 w-full rounded-xl border p-3" type="password" name="password" required autocomplete="current-password"></label>
        <button class="w-full rounded-xl bg-brand p-3 font-bold text-white">Entrar a administración</button>
    </form>
    <a href="{{ route('login') }}" class="mt-5 block text-brand-success">Entrar a mi cuenta de Plaza Local</a>
    <a class="mt-5 inline-block text-brand-success" href="{{ route('admin.password.request') }}">Olvidé mi contraseña administrativa</a>
</x-layouts.auth>
