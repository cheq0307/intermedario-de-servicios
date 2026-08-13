<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar perfil - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased">
    @php
        $isProvider = $user->canActAsProvider();
        $businessHours = $user->vendor?->business_hours ?? [];
        $selectedBusinessDays = old('business_days', $businessHours['days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
        $dayLabels = ['monday' => 'Lun', 'tuesday' => 'Mar', 'wednesday' => 'Mié', 'thursday' => 'Jue', 'friday' => 'Vie', 'saturday' => 'Sáb', 'sunday' => 'Dom'];
        $vendorStatus = $user->vendor?->status;
        $vendorStatusLabels = ['draft' => 'Perfil en borrador', 'pending' => 'Solicitud enviada', 'active' => 'Proveedor aprobado', 'rejected' => 'Necesita cambios', 'suspended' => 'Perfil suspendido'];
        $missingReviewRequirements = $user->vendor?->missingReviewRequirements() ?? [];
    @endphp
    <header class="border-b border-[#123B4A]/10 bg-white"><div class="mx-auto flex max-w-4xl items-center justify-between px-5 py-4"><a class="font-black" href="{{ route('dashboard') }}">Plaza Local</a><a class="rounded-full border border-[#123B4A]/10 px-4 py-2 text-sm font-black" href="{{ route('profile.show', $user) }}">Cancelar</a></div></header>
    <main class="mx-auto max-w-4xl px-5 py-9">
        <p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Tu presencia en la comunidad</p>
        <h1 class="mt-2 text-4xl font-black tracking-tight">Completa tu perfil</h1>
        <p class="mt-3 text-[#6B7D83]">La información pública ayuda a generar confianza. Tu correo, teléfono y ubicación exacta permanecen privados.</p>
        @if(session('status'))
            <div class="mt-6 rounded-2xl bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]">{{ session('status') }}</div>
        @endif
        @if(! $user->hasVerifiedEmail())
            <section class="mt-6 flex flex-col gap-4 rounded-2xl border border-[#F97316]/20 bg-[#FFF1E8] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="font-black text-[#A94708]">Tu correo sigue pendiente de verificación</p><p class="mt-1 text-sm font-semibold text-[#8A6A55]">Confirma {{ $user->email }} para publicar, comprar y enviar propuestas.</p></div>
                <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="shrink-0 rounded-full bg-[#F97316] px-5 py-2.5 text-sm font-black text-white" type="submit">Reenviar enlace</button></form>
            </section>
        @endif
        <section class="mt-8 rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Una cuenta, varios usos</p>
                    <h2 class="mt-2 text-xl font-black">Capacidades de tu cuenta</h2>
                    <p class="mt-2 text-sm font-semibold text-[#6B7D83]">Puedes comprar y ofrecer servicios con la misma sesión. Los permisos administrativos son independientes.</p>
                </div>
                @if($user->hasAnyRole(['admin', 'superadmin']))<span class="rounded-full bg-[#FFF1E8] px-4 py-2 text-xs font-black text-[#D85B0B]">{{ $user->hasRole('superadmin') ? 'Superadministrador' : 'Administrador' }}</span>@endif
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <article class="rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div><h3 class="font-black">Cliente</h3><p class="mt-1 text-sm font-semibold text-[#6B7D83]">Comprar productos y publicar solicitudes.</p></div>
                        @if($user->canActAsClient())<span class="rounded-full bg-[#E9F7F0] px-3 py-1 text-xs font-black text-[#14734A]">Activa</span>@else<form method="POST" action="{{ route('capabilities.activate', 'client') }}">@csrf<button class="rounded-full bg-[#123B4A] px-4 py-2 text-xs font-black text-white">Activar cliente</button></form>@endif
                    </div>
                </article>
                <article class="rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div><h3 class="font-black">Proveedor</h3><p class="mt-1 text-sm font-semibold text-[#6B7D83]">Vender productos, ofrecer servicios y enviar propuestas.</p></div>
                        @if($user->canActAsProvider())
                            <span class="rounded-full bg-[#E9F7F0] px-3 py-1 text-xs font-black text-[#14734A]">Activa</span>
                        @else
                            <form method="POST" action="{{ route('capabilities.activate', 'provider') }}">@csrf<button class="rounded-full bg-[#F97316] px-4 py-2 text-xs font-black text-white">Quiero ser proveedor</button></form>
                        @endif
                    </div>
                    @if($user->canActAsProvider())
                        <p class="mt-3 text-xs font-bold text-[#79551E]">{{ $vendorStatusLabels[$vendorStatus] ?? 'Perfil en borrador' }}</p>
                        @if($vendorStatus === 'rejected' && $user->vendor?->rejection_reason)<p class="mt-2 rounded-xl bg-red-50 px-3 py-2 text-xs font-bold text-red-700">Cambios solicitados: {{ $user->vendor->rejection_reason }}</p>@endif
                    @endif
                </article>
            </div>
        </section>

        <form class="mt-8 space-y-6" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <section class="rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-xl font-black">Información general</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <label class="block"><span class="text-sm font-black">Nombre completo</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name">@error('name')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="block"><span class="text-sm font-black">Ciudad y comunidad</span><select class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="community_id" required><option value="">Selecciona tu comunidad</option>@foreach($communities as $community)<option value="{{ $community->id }}" @selected((string) old('community_id', $user->community_id) === (string) $community->id)>{{ $community->display_label }}</option>@endforeach</select>@error('community_id')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror<span class="mt-1 block text-xs font-bold text-[#8A999E]">Solo aparecen comunidades habilitadas por administración.</span></label>
                    <label class="block"><span class="text-sm font-black">Teléfono celular privado</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" autocomplete="tel-national" placeholder="10 dígitos">@error('phone')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror<span class="mt-1 block text-xs font-bold text-[#8A999E]">Exactamente 10 dígitos; no aparecerá en tu perfil público.</span></label>
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
                        <label class="block"><span class="text-sm font-black">Estado de trabajo</span><select class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="availability_status" required><option value="available" @selected(old('availability_status', $user->vendor?->availability_status) === 'available')>Disponible para una nuevo trabajo</option><option value="busy" @selected(old('availability_status', $user->vendor?->availability_status) === 'busy')>Realizando un trabajo</option><option value="unavailable" @selected(old('availability_status', $user->vendor?->availability_status) === 'unavailable')>No disponible temporalmente</option></select><span class="mt-1 block text-xs font-bold text-[#8A999E]">Indica tu carga actual; es independiente de tu horario.</span></label>
                        <fieldset class="sm:col-span-2"><legend class="text-sm font-black">Días de atención</legend><div class="mt-2 grid grid-cols-4 gap-2 sm:grid-cols-7">@foreach($dayLabels as $day => $label)<label class="cursor-pointer"><input class="peer sr-only" type="checkbox" name="business_days[]" value="{{ $day }}" @checked(in_array($day, $selectedBusinessDays, true))><span class="grid min-h-11 place-items-center rounded-xl border border-[#123B4A]/10 bg-[#FAF8F4] px-2 text-sm font-black transition peer-checked:border-[#14734A] peer-checked:bg-[#E9F7F0] peer-checked:text-[#14734A]">{{ $label }}</span></label>@endforeach</div>@error('business_days')<span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</fieldset>
                        <label class="block"><span class="text-sm font-black">Inicio del horario</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" type="time" name="business_opens_at" value="{{ old('business_opens_at', $businessHours['opens_at'] ?? '09:00') }}" required>@error('business_opens_at')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
                        <label class="block"><span class="text-sm font-black">Fin del horario</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" type="time" name="business_closes_at" value="{{ old('business_closes_at', $businessHours['closes_at'] ?? '18:00') }}" required>@error('business_closes_at')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
                        <label class="block sm:col-span-2"><span class="text-sm font-black">Descripción del negocio o servicio</span><textarea class="mt-2 min-h-32 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="description" maxlength="1200">{{ old('description', $user->vendor?->description) }}</textarea></label>
                        <label class="block"><span class="text-sm font-black">Certificaciones o preparación</span><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="certifications" maxlength="1000">{{ old('certifications', $user->vendor?->certifications) }}</textarea></label>
                        <label class="block"><span class="text-sm font-black">Herramientas y capacidades</span><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="tools" maxlength="1000">{{ old('tools', $user->vendor?->tools) }}</textarea></label>
                    </div>
                </section>
                @if($user->vendor?->status === 'active')
                    <section class="rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="text-xl font-black">Cobros y depositos</h2>
                        <p class="mt-2 text-sm leading-6 text-[#6B7D83]">Stripe recopila y verifica identidad y cuenta bancaria. Plaza Local no almacena esos datos sensibles.</p>
                        @if($user->vendor->stripe_payouts_enabled)<p class="mt-4 rounded-2xl bg-[#E9F7F0] p-4 text-sm font-black text-[#14734A]">Cuenta verificada y habilitada para recibir depositos.</p>@else<form class="mt-4" method="POST" action="{{ route('stripe.connect') }}">@csrf<button class="rounded-full bg-[#635BFF] px-5 py-3 text-sm font-black text-white" type="submit">{{ $user->vendor->stripe_account_id ? 'Continuar verificacion con Stripe' : 'Configurar cobros con Stripe' }}</button></form>@endif
                    </section>
                @endif
            @endif

            <div class="flex justify-end"><button class="rounded-full bg-[#F97316] px-7 py-3.5 font-black text-white shadow-lg shadow-[#F97316]/15" type="submit">Guardar perfil</button></div>
        </form>
        @if($isProvider)
            <section class="mt-8 rounded-[2rem] border border-[#F97316]/20 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Verificación de proveedor</p>
                @if($vendorStatus === 'active')
                    <h2 class="mt-2 text-xl font-black">Tu perfil está aprobado</h2>
                    <p class="mt-2 text-sm font-semibold text-[#6B7D83]">Ya puedes publicar ofertas, enviar propuestas y configurar tus depósitos.</p>
                @elseif($vendorStatus === 'pending')
                    <h2 class="mt-2 text-xl font-black">Solicitud en revisión</h2>
                    <p class="mt-2 text-sm font-semibold text-[#6B7D83]">La enviaste {{ $user->vendor->submitted_at?->format('d/m/Y H:i') }}. Si editas los datos comerciales, volverá a borrador y tendrás que enviarla nuevamente.</p>
                @elseif($vendorStatus === 'suspended')
                    <h2 class="mt-2 text-xl font-black">Perfil suspendido</h2>
                    <p class="mt-2 text-sm font-semibold text-[#6B7D83]">Contacta a soporte para conocer el motivo y solicitar una revisión administrativa.</p>
                @else
                    <h2 class="mt-2 text-xl font-black">Envía tu perfil cuando esté listo</h2>
                    <p class="mt-2 text-sm font-semibold text-[#6B7D83]">Guardar el perfil no lo envía automáticamente. Tú decides cuándo solicitar la revisión.</p>
                    @if($missingReviewRequirements !== [] || ! $user->hasVerifiedEmail())
                        <p class="mt-4 rounded-2xl bg-[#FFF4D6] p-4 text-sm font-bold text-[#79551E]">Antes de enviarlo falta: {{ collect($missingReviewRequirements)->values()->join(', ') }}{{ ! $user->hasVerifiedEmail() ? ($missingReviewRequirements ? ', ' : '').'verificar correo' : '' }}.</p>
                    @else
                        <form class="mt-5" method="POST" action="{{ route('provider-applications.submit') }}">@csrf<button class="rounded-full bg-[#14734A] px-6 py-3 font-black text-white" type="submit">Enviar solicitud de verificación</button></form>
                    @endif
                @endif
            </section>
        @endif
    </main>
</body>
</html>
