<x-layouts.auth title="Recuperar contrase&ntilde;a - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-coral-strong">Recupera tu acceso</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Olvid&eacute; mi contrase&ntilde;a</h1>
        <p class="mt-3 leading-7 text-brand-sage-muted">Escribe el correo de tu cuenta. Te enviaremos un enlace seguro para establecer una contrase&ntilde;a nueva.</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-2xl border border-brand-success-bright/20 bg-brand-success-soft px-4 py-3 text-sm font-bold text-brand-success" role="status">{{ session('status') }}</div>
    @endif

    <form class="mt-8 space-y-5" method="POST" action="{{ route('password.email') }}">
        @csrf
        <label class="block">
            <span class="text-sm font-black">Correo electr&oacute;nico</span>
            <input class="mt-2 w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3.5 outline-none focus:border-brand-forest-strong focus:ring-4 focus:ring-brand-forest-strong/10" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>
        <button class="w-full rounded-2xl bg-brand-forest-deep px-5 py-4 font-black text-white" type="submit">Enviar enlace de recuperaci&oacute;n</button>
    </form>

    <p class="mt-7 text-center text-sm font-semibold text-brand-sage-muted"><a class="font-black text-brand-forest-strong hover:underline" href="{{ route('login') }}">Volver a iniciar sesi&oacute;n</a></p>
</x-layouts.auth>
