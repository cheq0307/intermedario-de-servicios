<x-layouts.auth title="Crear cuenta - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-coral-strong">Comienza en tu comunidad</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Crea tu cuenta</h1>
        <p class="mt-3 text-brand-sage-muted">Una sola cuenta para solicitar, comprar, vender u ofrecer servicios.</p>
    </div>

    <form class="mt-8 space-y-5" method="POST" action="{{ route('register') }}">
        @csrf
        <label class="block">
            <span class="text-sm font-black">Nombre completo</span>
            <input class="mt-2 w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3.5 outline-none focus:border-brand-forest-strong focus:ring-4 focus:ring-brand-forest-strong/10" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">
            @error('name') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>
        <div data-postal-assistant>
            <label class="block"><span class="text-sm font-black">Código postal</span><span class="mt-2 flex gap-2"><input class="min-w-0 flex-1 rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3.5" data-postal-input inputmode="numeric" pattern="[0-9]{5}" maxlength="5" placeholder="5 dígitos"><button class="rounded-2xl bg-brand-forest-deep px-4 text-sm font-black text-white" data-postal-submit type="button">Buscar</button></span></label>
            <p class="mt-2 text-xs font-bold text-brand-sage-muted" data-postal-status aria-live="polite">Escribe tu CP para encontrar tu municipio y las comunidades disponibles.</p>
        </div>


        <label class="block">
            <span class="text-sm font-black">Ciudad y comunidad</span>
            <select class="mt-2 w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3.5 outline-none focus:border-brand-forest-strong focus:ring-4 focus:ring-brand-forest-strong/10" name="community_id" data-community-select required>
                <option value="">Selecciona tu comunidad</option>
                @foreach($communities as $community)<option value="{{ $community->id }}" data-postal-code="{{ $community->postal_code }}" @selected((string) old('community_id') === (string) $community->id)>{{ $community->display_label }}</option>@endforeach
            </select>
            @error('community_id') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </label>

        <fieldset>
            <legend class="text-sm font-black">¿Qué te interesa encontrar?</legend>
            <p class="mt-1 text-xs font-semibold text-brand-sage-muted">Elige algunos temas; podrás modificarlos en tu perfil.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($categories as $category)<label class="cursor-pointer"><input class="peer sr-only" type="checkbox" name="interests[]" value="{{ $category->id }}" @checked(in_array($category->id, old('interests', [])))><span class="block rounded-full border border-brand-forest-deep/15 bg-white px-4 py-2 text-xs font-black transition peer-checked:border-brand-forest-strong peer-checked:bg-brand-success-mist peer-checked:text-brand-forest-strong">{{ $category->name }}</span></label>@endforeach
            </div>
            @error('interests') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
            @error('interests.*') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block sm:col-span-2">
                <span class="text-sm font-black">Teléfono celular</span>
                <input class="mt-2 w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3.5 outline-none focus:border-brand-forest-strong focus:ring-4 focus:ring-brand-forest-strong/10" type="tel" name="phone" value="{{ old('phone') }}" required inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" autocomplete="tel-national" placeholder="10 dígitos">
                <span class="mt-1 block text-xs font-bold text-brand-caption">Será privado y no aparecerá en tu perfil público.</span>
                @error('phone') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block sm:col-span-2">
                <span class="text-sm font-black">Correo electrónico</span>
                <input class="mt-2 w-full rounded-2xl border border-brand-forest-deep/15 bg-white px-4 py-3.5 outline-none focus:border-brand-forest-strong focus:ring-4 focus:ring-brand-forest-strong/10" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email') <span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
            </label>
            <x-password-input id="register-password" label="Contraseña" autocomplete="new-password" minlength="8" requirements="primary" />
            <x-password-input id="register-password-confirmation" label="Confirmar contraseña" name="password_confirmation" autocomplete="new-password" minlength="8" requirements="confirmation" />
        </div>
        <ul class="grid gap-1.5 text-xs font-bold text-brand-form-muted sm:grid-cols-2" aria-live="polite" data-password-requirements>
            <li class="flex items-center gap-2" data-password-rule="length"><span aria-hidden="true">&bull;</span> Al menos 8 caracteres</li>
            <li class="flex items-center gap-2" data-password-rule="letter"><span aria-hidden="true">&bull;</span> Incluye una letra</li>
            <li class="flex items-center gap-2" data-password-rule="number"><span aria-hidden="true">&bull;</span> Incluye un n&uacute;mero</li>
            <li class="flex items-center gap-2" data-password-rule="match"><span aria-hidden="true">&bull;</span> Las contrase&ntilde;as coinciden</li>
        </ul>

        <p class="rounded-2xl bg-brand-gold-faint px-4 py-3 text-xs font-bold leading-5 text-brand-warning-copy">Después de registrarte tendrás 7 días para verificar tu correo. Si no lo haces y la cuenta no tiene actividad que debamos conservar, se eliminará automáticamente.</p>

        <button class="w-full rounded-2xl bg-brand-coral-strong px-5 py-4 font-black text-white transition hover:bg-brand-coral-dark" type="submit">Crear mi cuenta</button>
    </form>

    <p class="mt-7 text-center text-sm font-semibold text-brand-sage-muted">¿Ya tienes cuenta? <a class="font-black text-brand-forest-strong hover:underline" href="{{ route('login') }}">Iniciar sesión</a></p>
</x-layouts.auth>
