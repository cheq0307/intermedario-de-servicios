<x-layouts.auth title="Crear cuenta - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Comienza en tu comunidad</p>
        <h1 class="mt-3 text-4xl font-black tracking-tight">Crea tu cuenta</h1>
        <p class="mt-3 text-[#6f827b]">Elige cómo quieres comenzar. Podrás ampliar tu perfil después.</p>
    </div>

    <form class="mt-8 space-y-5" method="POST" action="{{ route('register') }}">
        @csrf
        <fieldset>
            <legend class="text-sm font-black">Tipo de cuenta</legend>
            <div class="mt-2 grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input class="peer sr-only" type="radio" name="account_type" value="client" {{ old('account_type', 'client') === 'client' ? 'checked' : '' }}>
                    <span class="block rounded-2xl border border-[#17352b]/15 bg-white p-4 transition peer-checked:border-[#1f6b4f] peer-checked:bg-[#e6f1eb] peer-checked:ring-2 peer-checked:ring-[#1f6b4f]/20">
                        <span class="block text-xl" aria-hidden="true">🛍️</span>
                        <span class="mt-2 block font-black">Cliente</span>
                        <span class="mt-1 block text-xs font-semibold text-[#6f827b]">Quiero comprar o contratar.</span>
                    </span>
                </label>
                <label class="cursor-pointer">
                    <input class="peer sr-only" type="radio" name="account_type" value="provider" {{ old('account_type') === 'provider' ? 'checked' : '' }}>
                    <span class="block rounded-2xl border border-[#17352b]/15 bg-white p-4 transition peer-checked:border-[#1f6b4f] peer-checked:bg-[#e6f1eb] peer-checked:ring-2 peer-checked:ring-[#1f6b4f]/20">
                        <span class="block text-xl" aria-hidden="true">🛠️</span>
                        <span class="mt-2 block font-black">Proveedor</span>
                        <span class="mt-1 block text-xs font-semibold text-[#6f827b]">Quiero vender u ofrecer servicios.</span>
                    </span>
                </label>
            </div>
            @error('account_type') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </fieldset>

        <label class="block">
            <span class="text-sm font-black">Nombre completo</span>
            <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
            @error('name') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block sm:col-span-2">
                <span class="text-sm font-black">Correo electrónico</span>
                <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm font-black">Contraseña</span>
                <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="password" name="password" required autocomplete="new-password">
            </label>
            <label class="block">
                <span class="text-sm font-black">Confirmar</span>
                <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="password" name="password_confirmation" required autocomplete="new-password">
            </label>
        </div>
        @error('password') <span class="block text-sm font-bold text-red-600">{{ $message }}</span> @enderror

        <button class="w-full rounded-2xl bg-[#d2693c] px-5 py-4 font-black text-white transition hover:bg-[#b9552d]" type="submit">Crear mi cuenta</button>
    </form>

    <p class="mt-7 text-center text-sm font-semibold text-[#6f827b]">¿Ya tienes cuenta? <a class="font-black text-[#1f6b4f] hover:underline" href="{{ route('login') }}">Iniciar sesión</a></p>
</x-layouts.auth>
