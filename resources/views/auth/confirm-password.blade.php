<x-layouts.auth title="Confirmar contraseña - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Área protegida</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Confirma tu contraseña</h1>
        <p class="mt-3 leading-7 text-[#6f827b]">Antes de modificar la autenticación de dos pasos necesitamos comprobar nuevamente tu identidad.</p>
    </div>

    <form class="mt-8 space-y-5" method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        <label class="block">
            <span class="text-sm font-black">Contraseña actual</span>
            <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="password" name="password" required autofocus autocomplete="current-password">
            @error('password') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>
        <button class="w-full rounded-2xl bg-[#17352b] px-5 py-4 font-black text-white" type="submit">Confirmar y continuar</button>
    </form>
</x-layouts.auth>
