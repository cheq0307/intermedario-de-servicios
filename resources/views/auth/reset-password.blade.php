<x-layouts.auth title="Nueva contrase&ntilde;a - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Enlace verificado</p>
        <h1 class="mt-3 text-4xl font-black tracking-tight">Crea una contrase&ntilde;a nueva</h1>
        <p class="mt-3 leading-7 text-[#6f827b]">La contrase&ntilde;a cambia, pero tu autenticaci&oacute;n en dos pasos continuar&aacute; protegiendo la cuenta.</p>
    </div>

    <form class="mt-8 space-y-5" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <label class="block">
            <span class="text-sm font-black">Correo electr&oacute;nico</span>
            <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="email">
            @error('email') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
                <span class="text-sm font-black">Nueva contrase&ntilde;a</span>
                <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none" type="password" name="password" minlength="8" required autocomplete="new-password" data-password>
            </label>
            <label class="block">
                <span class="text-sm font-black">Confirmar</span>
                <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password" data-password-confirmation>
            </label>
        </div>
        @error('password') <span class="block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        <ul class="grid gap-1.5 text-xs font-bold text-[#75857f] sm:grid-cols-2" aria-live="polite" data-password-requirements>
            <li class="flex items-center gap-2" data-password-rule="length"><span aria-hidden="true">&bull;</span> Al menos 8 caracteres</li>
            <li class="flex items-center gap-2" data-password-rule="letter"><span aria-hidden="true">&bull;</span> Incluye una letra</li>
            <li class="flex items-center gap-2" data-password-rule="number"><span aria-hidden="true">&bull;</span> Incluye un n&uacute;mero</li>
            <li class="flex items-center gap-2" data-password-rule="match"><span aria-hidden="true">&bull;</span> Las contrase&ntilde;as coinciden</li>
        </ul>
        <button class="w-full rounded-2xl bg-[#d2693c] px-5 py-4 font-black text-white" type="submit">Guardar nueva contrase&ntilde;a</button>
    </form>
</x-layouts.auth>
