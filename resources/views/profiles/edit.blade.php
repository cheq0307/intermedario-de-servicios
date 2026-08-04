<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar perfil - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased">
    @php($isProvider = $user->account_type->value === 'provider')
    <header class="border-b border-[#123B4A]/10 bg-white"><div class="mx-auto flex max-w-4xl items-center justify-between px-5 py-4"><a class="font-black" href="{{ route('dashboard') }}">Plaza Local</a><a class="rounded-full border border-[#123B4A]/10 px-4 py-2 text-sm font-black" href="{{ route('profile.show', $user) }}">Cancelar</a></div></header>
    <main class="mx-auto max-w-4xl px-5 py-9">
        <p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Tu presencia en la comunidad</p>
        <h1 class="mt-2 text-4xl font-black tracking-tight">Completa tu perfil</h1>
        <p class="mt-3 text-[#6B7D83]">La información pública ayuda a generar confianza. Tu correo, teléfono y ubicación exacta permanecen privados.</p>

        <form class="mt-8 space-y-6" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <section class="rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-xl font-black">Información general</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <label class="block"><span class="text-sm font-black">Nombre</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="name" value="{{ old('name', $user->name) }}" required maxlength="120">@error('name')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="text-sm font-black">Ciudad o comunidad</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="city" value="{{ old('city', $user->city) }}" maxlength="120"></label>
                    <label class="block"><span class="text-sm font-black">Teléfono privado</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30"><span class="mt-1 block text-xs font-bold text-[#8A999E]">No aparecerá en tu perfil público.</span></label>
                    <label class="block"><span class="text-sm font-black">Foto de perfil</span><input class="mt-2 block w-full text-sm font-bold file:mr-3 file:rounded-full file:border-0 file:bg-[#E8F1EE] file:px-4 file:py-2 file:font-black file:text-[#14734A]" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"><span class="mt-1 block text-xs font-bold text-[#8A999E]">JPG, PNG o WebP; máximo 2 MB.</span></label>
                    <label class="block sm:col-span-2"><span class="text-sm font-black">Sobre ti</span><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="bio" maxlength="800">{{ old('bio', $user->bio) }}</textarea></label>
                </div>
            </section>

            @if ($isProvider)
                <section class="rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-xl font-black">Perfil profesional o comercial</h2>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <label class="block sm:col-span-2"><span class="text-sm font-black">Nombre comercial</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="display_name" value="{{ old('display_name', $user->vendor?->display_name ?? $user->name) }}" required maxlength="120"></label>
                        <label class="block"><span class="text-sm font-black">Especialidad principal</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="specialty" value="{{ old('specialty', $user->vendor?->specialty) }}" placeholder="Ej. Plomería residencial"></label>
                        <label class="block"><span class="text-sm font-black">Zona de servicio</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="service_area" value="{{ old('service_area', $user->vendor?->service_area) }}" placeholder="Ej. Centro y colonias cercanas"></label>
                        <label class="block"><span class="text-sm font-black">Años de experiencia</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" type="number" name="years_experience" value="{{ old('years_experience', $user->vendor?->years_experience) }}" min="0" max="80"></label>
                        <label class="block"><span class="text-sm font-black">Disponibilidad</span><select class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="availability_status" required><option value="available" @selected(old('availability_status', $user->vendor?->availability_status) === 'available')>Disponible</option><option value="busy" @selected(old('availability_status', $user->vendor?->availability_status) === 'busy')>Ocupado</option><option value="unavailable" @selected(old('availability_status', $user->vendor?->availability_status) === 'unavailable')>No disponible</option></select></label>
                        <label class="block sm:col-span-2"><span class="text-sm font-black">Descripción del negocio o servicio</span><textarea class="mt-2 min-h-32 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="description" maxlength="1200">{{ old('description', $user->vendor?->description) }}</textarea></label>
                        <label class="block"><span class="text-sm font-black">Certificaciones o preparación</span><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="certifications" maxlength="1000">{{ old('certifications', $user->vendor?->certifications) }}</textarea></label>
                        <label class="block"><span class="text-sm font-black">Herramientas y capacidades</span><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="tools" maxlength="1000">{{ old('tools', $user->vendor?->tools) }}</textarea></label>
                    </div>
                </section>
            @endif

            <div class="flex justify-end"><button class="rounded-full bg-[#F97316] px-7 py-3.5 font-black text-white shadow-lg shadow-[#F97316]/15" type="submit">Guardar perfil</button></div>
        </form>
    </main>
</body>
</html>
