<x-layouts.auth title="Iniciar sesión - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Bienvenido de vuelta</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Inicia sesión</h1>
        <p class="mt-3 text-[#6f827b]">Accede a tus mensajes, pedidos y publicaciones.</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-4 py-3 text-sm font-bold text-[#14734A]" role="status">{{ session('status') }}</div>
    @endif

    <form class="mt-8 space-y-5" method="POST" action="{{ route('login') }}">
        @csrf
        <label class="block">
            <span class="text-sm font-black">Correo electrónico</span>
            <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none transition focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>

        <x-password-input id="login-password" label="Contraseña" autocomplete="current-password" />

        <div class="text-right">
            <a class="text-sm font-black text-[#1f6b4f] hover:underline" href="{{ route('password.request') }}">Olvid&eacute; mi contrase&ntilde;a</a>
        </div>

        <label class="flex items-center gap-3 text-sm font-bold text-[#6f827b]">
            <input class="size-4 rounded border-[#17352b]/20 text-[#1f6b4f]" type="checkbox" name="remember">
            Mantener mi sesión iniciada
        </label>

        <button class="w-full rounded-2xl bg-[#17352b] px-5 py-4 font-black text-white transition hover:bg-[#244b3e]" type="submit">Entrar a Plaza Local</button>
    </form>

    <p class="mt-7 text-center text-sm font-semibold text-[#6f827b]">¿Aún no tienes cuenta? <a class="font-black text-[#d2693c] hover:underline" href="{{ route('register') }}">Crear cuenta</a></p>
</x-layouts.auth>
