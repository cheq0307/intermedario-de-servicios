<x-layouts.auth title="Iniciar sesión - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-coral-strong">Bienvenido de vuelta</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Inicia sesión</h1>
        <p class="mt-3 text-brand-sage-muted">Accede a tus mensajes, pedidos y publicaciones.</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-2xl border border-brand-success-bright/20 bg-brand-success-soft px-4 py-3 text-sm font-bold text-brand-success" role="status">{{ session('status') }}</div>
    @endif

    <form class="mt-8 space-y-5" method="POST" action="{{ route('login') }}">
        @csrf
        <label class="block">
            <span class="text-sm font-black">Correo electrónico</span>
            <input class="mt-2 w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3.5 outline-none transition focus:border-brand-forest-strong focus:ring-4 focus:ring-brand-forest-strong/10" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>

        <x-password-input id="login-password" label="Contraseña" autocomplete="current-password" />

        <div class="text-right">
            <a class="text-sm font-black text-brand-forest-strong hover:underline" href="{{ route('password.request') }}">Olvid&eacute; mi contrase&ntilde;a</a>
        </div>

        <label class="flex items-center gap-3 text-sm font-bold text-brand-sage-muted">
            <input class="size-4 rounded border-brand-forest-deep/20 text-brand-forest-strong" type="checkbox" name="remember">
            Mantener mi sesión iniciada
        </label>

        <button class="w-full rounded-2xl bg-brand-forest-deep px-5 py-4 font-black text-white transition hover:bg-brand-forest-hover" type="submit">Entrar a Plaza Local</button>
    </form>

    <p class="mt-7 text-center text-sm font-semibold text-brand-sage-muted">¿Aún no tienes cuenta? <a class="font-black text-brand-coral-strong hover:underline" href="{{ route('register') }}">Crear cuenta</a></p>
</x-layouts.auth>
