<x-layouts.auth title="Verificación en dos pasos - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Protección adicional</p>
        <h1 class="mt-3 text-4xl font-black tracking-tight">Confirma que eres tú</h1>
        <p class="mt-3 leading-7 text-[#6f827b]">Escribe el código de seis dígitos generado por tu aplicación autenticadora.</p>
    </div>

    <form class="mt-8 space-y-5" method="POST" action="{{ route('two-factor.login.store') }}">
        @csrf
        <label class="block">
            <span class="text-sm font-black">Código de autenticación</span>
            <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-4 text-center text-2xl font-black tracking-[.35em] outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autofocus autocomplete="one-time-code">
            @error('code') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>
        <button class="w-full rounded-2xl bg-[#17352b] px-5 py-4 font-black text-white transition hover:bg-[#244b3e]" type="submit">Verificar y continuar</button>
    </form>

    <details class="mt-6 rounded-2xl border border-[#17352b]/10 bg-white p-4">
        <summary class="cursor-pointer text-sm font-black text-[#1f6b4f]">Usar un código de recuperación</summary>
        <form class="mt-4 space-y-4" method="POST" action="{{ route('two-factor.login.store') }}">
            @csrf
            <input class="w-full rounded-2xl border border-[#17352b]/15 bg-[#f7f5ef] px-4 py-3 font-mono outline-none focus:border-[#1f6b4f]" type="text" name="recovery_code" autocomplete="one-time-code" placeholder="Código de recuperación">
            @error('recovery_code') <span class="block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
            <button class="w-full rounded-2xl border border-[#17352b]/15 px-4 py-3 text-sm font-black" type="submit">Usar código de recuperación</button>
        </form>
    </details>
</x-layouts.auth>
