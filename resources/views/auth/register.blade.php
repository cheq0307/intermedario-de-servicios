<x-layouts.auth title="Crear cuenta - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Comienza en tu comunidad</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Crea tu cuenta</h1>
        <p class="mt-3 text-[#6f827b]">Una sola cuenta para solicitar, comprar, vender u ofrecer servicios.</p>
    </div>

    <form class="mt-8 space-y-5" method="POST" action="{{ route('register') }}">
        @csrf
        <label class="block">
            <span class="text-sm font-black">Nombre completo</span>
            <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
            @error('name') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-black">Ciudad y comunidad</span>
            <select class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" name="community_id" required>
                <option value="">Selecciona tu comunidad</option>
                @foreach($communities as $community)<option value="{{ $community->id }}" @selected((string) old('community_id') === (string) $community->id)>{{ $community->display_label }}</option>@endforeach
            </select>
            @error('community_id') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>

        <fieldset>
            <legend class="text-sm font-black">¿Qué te interesa encontrar?</legend>
            <p class="mt-1 text-xs font-semibold text-[#6f827b]">Elige algunos temas; podrás modificarlos en tu perfil.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($categories as $category)<label class="cursor-pointer"><input class="peer sr-only" type="checkbox" name="interests[]" value="{{ $category->id }}" @checked(in_array($category->id, old('interests', [])))><span class="block rounded-full border border-[#17352b]/15 bg-white px-4 py-2 text-xs font-black transition peer-checked:border-[#1f6b4f] peer-checked:bg-[#e6f1eb] peer-checked:text-[#1f6b4f]">{{ $category->name }}</span></label>@endforeach
            </div>
            @error('interests') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
            @error('interests.*') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block sm:col-span-2">
                <span class="text-sm font-black">Correo electrónico</span>
                <input class="mt-2 w-full rounded-2xl border border-[#17352b]/15 bg-white px-4 py-3.5 outline-none focus:border-[#1f6b4f] focus:ring-4 focus:ring-[#1f6b4f]/10" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
            </label>
            <x-password-input id="register-password" label="Contraseña" autocomplete="new-password" minlength="8" requirements="primary" />
            <x-password-input id="register-password-confirmation" label="Confirmar contraseña" name="password_confirmation" autocomplete="new-password" minlength="8" requirements="confirmation" />
        </div>
        <ul class="grid gap-1.5 text-xs font-bold text-[#75857f] sm:grid-cols-2" aria-live="polite" data-password-requirements>
            <li class="flex items-center gap-2" data-password-rule="length"><span aria-hidden="true">&bull;</span> Al menos 8 caracteres</li>
            <li class="flex items-center gap-2" data-password-rule="letter"><span aria-hidden="true">&bull;</span> Incluye una letra</li>
            <li class="flex items-center gap-2" data-password-rule="number"><span aria-hidden="true">&bull;</span> Incluye un n&uacute;mero</li>
            <li class="flex items-center gap-2" data-password-rule="match"><span aria-hidden="true">&bull;</span> Las contrase&ntilde;as coinciden</li>
        </ul>

        <button class="w-full rounded-2xl bg-[#d2693c] px-5 py-4 font-black text-white transition hover:bg-[#b9552d]" type="submit">Crear mi cuenta</button>
    </form>

    <p class="mt-7 text-center text-sm font-semibold text-[#6f827b]">¿Ya tienes cuenta? <a class="font-black text-[#1f6b4f] hover:underline" href="{{ route('login') }}">Iniciar sesión</a></p>
</x-layouts.auth>
