<x-layouts.auth title="Recuperar contrase&ntilde;a - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Recupera tu acceso</p>
        <h1 class="mt-3 text-4xl font-black tracking-tight">Olvid&eacute; mi contrase&ntilde;a</h1>
        <p class="mt-3 leading-7 text-[#6f827b]">Escribe el correo de tu cuenta. Te enviaremos un enlace seguro para establecer una contrase&ntilde;a nueva.</p>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-4 py-3 text-sm font-bold text-[#14734A]" role="status">{{ session('status') }}</div>
    @endif

    <form class="mt-8 space-y-5" method="POST" action="{{ route('password.email') }}">
        @csrf
        <label class="block">
            <span class="text-sm font-black">Correo electr&oacute;nico</span>
            <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>
        <button class="w-full rounded-2xl bg-[#17352b] px-5 py-4 font-black text-white" type="submit">Enviar enlace de recuperaci&oacute;n</button>
    </form>

    <p class="mt-7 text-center text-sm font-semibold text-[#6f827b]"><a class="font-black text-[#1f6b4f] hover:underline" href="{{ route('login') }}">Volver a iniciar sesi&oacute;n</a></p>
</x-layouts.auth>
