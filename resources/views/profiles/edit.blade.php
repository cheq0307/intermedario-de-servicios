<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar perfil - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-surface text-brand-ink antialiased">
    <x-market-nav :back-url="route('more.index')" />
    <main class="mx-auto max-w-4xl px-4 py-6 sm:px-6 sm:py-9">
        <p class="text-xs font-black uppercase tracking-[.18em] text-brand-orange">Tu presencia en la comunidad</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Completa tu perfil</h1>
        <section class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand/10 bg-white p-4">
            <p class="text-sm font-bold">{{ $user->phone_verified_at ? 'Teléfono verificado por SMS' : 'Tu teléfono está pendiente de verificación por SMS' }}</p>
            <a class="inline-flex min-h-11 items-center text-sm font-bold text-brand-success" href="{{ route('phone.show') }}">{{ $user->phone_verified_at ? 'Revisar teléfono' : 'Verificar mi teléfono' }}</a>
        </section>
        <p class="mt-3 text-brand-muted">La información pública ayuda a generar confianza. Tu correo, teléfono y ubicación exacta permanecen privados.</p>
        @if(session('status'))
            <div class="mt-6 rounded-2xl bg-brand-success-soft px-5 py-4 text-sm font-black text-brand-success">{{ session('status') }}</div>
        @endif
        @if(! $user->hasVerifiedEmail())
            <section class="mt-6 flex flex-col gap-4 rounded-2xl border border-brand-orange/20 bg-brand-orange-soft px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="font-black text-brand-orange-dark">Tu correo sigue pendiente de verificación</p><p class="mt-1 text-sm font-semibold text-brand-orange-muted">Confirma {{ $user->email }} para publicar, comprar y enviar propuestas.</p></div>
                <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="shrink-0 rounded-full bg-brand-orange px-5 py-2.5 text-sm font-black text-white" type="submit">Reenviar enlace</button></form>
            </section>
        @endif
        <section class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-[2rem] border border-brand/10 bg-white p-5 shadow-sm sm:p-6"><div><p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Cuenta unificada</p><h2 class="mt-2 text-xl font-black">Eres usuario de Plaza Local</h2><p class="mt-2 text-sm font-semibold text-brand-muted">Puedes solicitar y comprar. Si quieres ofrecer, crea y completa la información comercial en esta misma cuenta.</p></div>@if(! $profileForm->hasCommercialProfile)<form class="w-full sm:w-auto" method="POST" action="{{ route('capabilities.activate', $profileForm->providerCapability) }}">@csrf<button class="w-full rounded-full bg-brand px-5 py-3 text-sm font-black text-white sm:w-auto" type="submit">Agregar mis servicios</button></form>@else<span class="rounded-full bg-brand-success-soft px-4 py-2 text-xs font-black text-brand-success">{{ $profileForm->vendorStatusLabel }}</span>@endif</section>

        <form id="profile-interests-form" method="POST" action="{{ route('profile.interests.update') }}" class="hidden">@csrf @method('PUT')</form>
        <form class="mt-8 space-y-6" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <section class="rounded-[2rem] border border-brand/10 bg-white p-5 shadow-sm sm:p-8">
                <h2 class="text-xl font-black">Información general</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <label class="block"><span class="text-sm font-black">Nombre completo</span><input class="mt-2 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name">@error('name')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
                    <div data-postal-assistant><label class="block"><span class="text-sm font-black">Código postal</span><span class="mt-2 flex gap-2"><input class="min-w-0 flex-1 scroll-mt-24 rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" data-postal-input value="{{ old('postal_code', $user->community?->postal_code) }}" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" placeholder="5 dígitos"><button class="shrink-0 rounded-2xl bg-brand px-4 text-xs font-black text-white" data-postal-submit type="button">Buscar</button></span></label><p class="mt-1 text-xs font-bold text-brand-caption" data-postal-status aria-live="polite">Úsalo para localizar tu municipio y comunidad.</p></div>
                    <label class="block"><span class="text-sm font-black">Ciudad y comunidad</span><select class="mt-2 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="community_id" data-community-select required><option value="">Selecciona tu comunidad</option>@foreach($communities as $community)<option value="{{ $community->id }}" data-postal-code="{{ $community->postal_code }}" @selected((string) old('community_id', $user->community_id) === (string) $community->id)>{{ $community->display_label }}</option>@endforeach</select>@error('community_id')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror<span class="mt-1 block text-xs font-bold text-brand-caption">Solo aparecen comunidades habilitadas por administración.</span></label>
                    <label class="block"><span class="text-sm font-black">Teléfono celular privado</span><input class="mt-2 w-full scroll-mt-24 rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required inputmode="numeric" pattern="[0-9]{10}" minlength="10" maxlength="10" autocomplete="tel-national" placeholder="10 dígitos">@error('phone')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror<span class="mt-1 block text-xs font-bold text-brand-caption">Exactamente 10 dígitos; es obligatorio, único y no aparecerá en tu perfil público.</span></label>
                    <label class="block"><span class="text-sm font-black">Foto de perfil</span><input class="mt-2 block w-full text-sm font-bold file:mr-3 file:rounded-full file:border-0 file:bg-brand-avatar-soft file:px-4 file:py-2 file:font-black file:text-brand-success" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"><span class="mt-1 block text-xs font-bold text-brand-caption">JPG, PNG o WebP; máximo 2 MB.</span></label>
                    <label class="block sm:col-span-2"><span class="text-sm font-black">Sobre ti</span><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="bio" maxlength="800">{{ old('bio', $user->bio) }}</textarea></label>
                    <fieldset class="sm:col-span-2"><legend class="text-sm font-black">Mis intereses</legend><p class="mt-1 text-xs font-semibold text-brand-muted">Elige temas que quieres descubrir. Plaza Local los usa para ordenar tu sección “Para ti”; no significa que ofrezcas esos servicios.</p><div class="mt-3 flex flex-wrap gap-2">@foreach($categories as $category)<label class="cursor-pointer"><input class="peer sr-only" type="checkbox" name="interests[]" value="{{ $category->id }}" form="profile-interests-form" @checked(in_array($category->id, old('interests', $profileForm->interestCategoryIds)))><span class="block rounded-full border border-brand/10 bg-brand-surface px-4 py-2 text-xs font-black peer-checked:border-brand-success peer-checked:bg-brand-success-soft peer-checked:text-brand-success">{{ $category->name }}</span></label>@endforeach</div>@error('interests')<span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror<div class="mt-4 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between"><span class="text-xs font-semibold text-brand-caption">Se guardan por separado del resto del perfil.</span><button class="w-full rounded-full bg-brand-success px-5 py-2.5 text-sm font-black text-white sm:w-auto" type="submit" form="profile-interests-form">Guardar intereses</button></div></fieldset>
                </div>
            </section>

            @if ($profileForm->hasCommercialProfile)
                <section id="servicios" class="scroll-mt-24 rounded-[2rem] border border-brand/10 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-xl font-black">Servicios, productos o actividades que ofreces</h2>
                    <p class="mt-2 text-sm font-semibold text-brand-muted">Esta selección es distinta de tus intereses: aquí indicas lo que otras personas pueden contratarte o comprarte.</p>
                    <fieldset class="mt-5"><legend class="text-sm font-black">Rubros que ofrezco</legend><div class="mt-3 flex flex-wrap gap-2">@foreach($categories as $category)<label class="cursor-pointer"><input class="peer sr-only" type="checkbox" name="offered_categories[]" value="{{ $category->id }}" @checked(in_array($category->id, old('offered_categories', $user->vendor?->categories->pluck('id')->all() ?? [])))><span class="block rounded-full border border-brand/10 bg-brand-surface px-4 py-2 text-xs font-black peer-checked:border-brand-danger-warm peer-checked:bg-brand-orange-soft peer-checked:text-brand-danger-warm">{{ $category->name }}</span></label>@endforeach</div>@error('offered_categories')<span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</fieldset>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <label class="block sm:col-span-2"><span class="text-sm font-black">Nombre comercial</span><input class="mt-2 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="display_name" value="{{ old('display_name', $user->vendor?->display_name ?? $user->name) }}" required maxlength="120"></label>
                        <label class="block"><span class="text-sm font-black">Especialidad principal</span><input class="mt-2 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="specialty" value="{{ old('specialty', $user->vendor?->specialty) }}" placeholder="Ej. Plomería residencial"></label>
                        <label class="block"><span class="text-sm font-black">Zona de servicio</span><input class="mt-2 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="service_area" value="{{ old('service_area', $user->vendor?->service_area) }}" placeholder="Ej. Centro y colonias cercanas"></label>
                        <label class="block"><span class="text-sm font-black">Años de experiencia</span><input class="mt-2 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" type="number" name="years_experience" value="{{ old('years_experience', $user->vendor?->years_experience) }}" min="0" max="80"></label>
                        <label class="block"><span class="text-sm font-black">Estado de trabajo</span><select class="mt-2 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="availability_status" required><option value="available" @selected(old('availability_status', $user->vendor?->availability_status) === 'available')>Disponible para una nuevo trabajo</option><option value="busy" @selected(old('availability_status', $user->vendor?->availability_status) === 'busy')>Realizando un trabajo</option><option value="unavailable" @selected(old('availability_status', $user->vendor?->availability_status) === 'unavailable')>No disponible temporalmente</option></select><span class="mt-1 block text-xs font-bold text-brand-caption">Indica tu carga actual; es independiente de tu horario.</span></label>
                        <fieldset class="sm:col-span-2"><legend class="text-sm font-black">Días de atención</legend><div class="mt-2 grid grid-cols-4 gap-2 sm:grid-cols-7">@foreach($profileForm->dayLabels as $day => $label)<label class="cursor-pointer"><input class="peer sr-only" type="checkbox" name="business_days[]" value="{{ $day }}" @checked(in_array($day, old('business_days', $profileForm->businessDays), true))><span class="grid min-h-11 place-items-center rounded-xl border border-brand/10 bg-brand-surface px-2 text-sm font-black transition peer-checked:border-brand-success peer-checked:bg-brand-success-soft peer-checked:text-brand-success">{{ $label }}</span></label>@endforeach</div>@error('business_days')<span class="mt-2 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</fieldset>
                        <label class="block"><span class="text-sm font-black">Inicio del horario</span><input class="mt-2 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" type="time" name="business_opens_at" value="{{ old('business_opens_at', $profileForm->businessHours['opens_at'] ?? '09:00') }}" required>@error('business_opens_at')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
                        <label class="block"><span class="text-sm font-black">Fin del horario</span><input class="mt-2 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" type="time" name="business_closes_at" value="{{ old('business_closes_at', $profileForm->businessHours['closes_at'] ?? '18:00') }}" required>@error('business_closes_at')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
                        <label class="block sm:col-span-2"><span class="text-sm font-black">Descripción del negocio o servicio</span><textarea class="mt-2 min-h-32 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="description" maxlength="1200">{{ old('description', $user->vendor?->description) }}</textarea></label>
                        <label class="block"><span class="text-sm font-black">Certificaciones o preparación</span><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="certifications" maxlength="1000">{{ old('certifications', $user->vendor?->certifications) }}</textarea></label>
                        <label class="block"><span class="text-sm font-black">Herramientas y capacidades</span><textarea class="mt-2 min-h-28 w-full rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3" name="tools" maxlength="1000">{{ old('tools', $user->vendor?->tools) }}</textarea></label>
                    </div>
                </section>
                @if($user->vendor?->status === 'active')
                    <section class="rounded-[2rem] border border-brand/10 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="text-xl font-black">Cobros y depositos</h2>
                        <p class="mt-2 text-sm leading-6 text-brand-muted">Stripe recopila y verifica identidad y cuenta bancaria. Plaza Local no almacena esos datos sensibles.</p>
                        @if($user->vendor->stripe_payouts_enabled)<p class="mt-4 rounded-2xl bg-brand-success-soft p-4 text-sm font-black text-brand-success">Cuenta verificada y habilitada para recibir depositos.</p>@else<form class="mt-4" method="POST" action="{{ route('stripe.connect') }}">@csrf<button class="rounded-full bg-stripe px-5 py-3 text-sm font-black text-white" type="submit">{{ $user->vendor->stripe_account_id ? 'Continuar verificacion con Stripe' : 'Configurar cobros con Stripe' }}</button></form>@endif
                    </section>
                @endif
            @endif

            <div class="grid grid-cols-2 gap-3 sm:flex sm:justify-end"><a class="rounded-full border border-brand/10 bg-white px-4 py-3.5 text-center font-black sm:px-7" href="{{ route('more.index') }}">Cancelar</a><button class="rounded-full bg-brand-orange px-4 py-3.5 font-black text-white shadow-lg shadow-brand-orange/15 sm:px-7" type="submit">Guardar perfil</button></div>
        </form>
        @if($profileForm->hasCommercialProfile)
            <section class="mt-8 rounded-[2rem] border border-brand/10 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Expediente privado de confianza</p>
                <h2 class="mt-2 text-xl font-black">Documentos para el distintivo verificado</h2>
                <p class="mt-2 text-sm leading-6 text-brand-muted">No son necesarios para usar Plaza Local ni para que administración apruebe tu perfil comercial. Identificación y domicilio sirven para solicitar el distintivo de identidad; la constancia fiscal o evidencia del negocio es completamente opcional y solo aplica a quien quiera acreditar un negocio formal.</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach(\App\Models\VendorVerificationDocument::TYPES as $type => $label)
                        @php($currentDocument = $profileForm->latestVerificationDocuments[$type] ?? null)
                        <div class="rounded-2xl bg-brand-surface p-4"><strong class="text-sm">{{ $label }}</strong><p class="mt-2 text-xs font-bold text-brand-muted">{{ $currentDocument ? (\App\Models\VendorVerificationDocument::STATUSES[$currentDocument->status] ?? $currentDocument->status) : 'No enviado' }}</p>@if($currentDocument)<a class="mt-2 inline-flex text-xs font-black text-brand-success underline" href="{{ route('verification-documents.download', $currentDocument) }}">Descargar mi archivo</a>@endif</div>
                    @endforeach
                </div>
                <form class="mt-5 grid gap-3 sm:grid-cols-[1fr_1fr_auto]" method="POST" action="{{ route('verification-documents.store') }}" enctype="multipart/form-data">@csrf
                    <select class="rounded-2xl bg-brand-surface px-4 py-3" name="type" required>@foreach(\App\Models\VendorVerificationDocument::TYPES as $type => $label)<option value="{{ $type }}">{{ $label }}</option>@endforeach</select>
                    <input class="rounded-2xl border border-brand/10 bg-brand-surface px-4 py-3 text-sm" type="file" name="document" accept="application/pdf,image/jpeg,image/png" required>
                    <button class="rounded-full bg-brand px-5 py-3 font-black text-white" type="submit">Enviar</button>
                </form>
                <p class="mt-3 text-xs font-semibold text-brand-muted">PDF, JPG o PNG; máximo 5 MB. No solicitamos contraseña, NIP, CVV, CLABE ni fotografía de tarjeta.</p>
            </section>
        @endif        @if($profileForm->hasCommercialProfile)
            <section class="mt-8 rounded-[2rem] border border-brand-orange/20 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Verificación de proveedor</p>
                @if($profileForm->vendorStatus === 'active')
                    <h2 class="mt-2 text-xl font-black">Tu perfil está aprobado</h2>
                    <p class="mt-2 text-sm font-semibold text-brand-muted">Ya puedes publicar ofertas, enviar propuestas y configurar tus depósitos.</p>
                @elseif($profileForm->vendorStatus === 'pending')
                    <h2 class="mt-2 text-xl font-black">Solicitud en revisión</h2>
                    <p class="mt-2 text-sm font-semibold text-brand-muted">La enviaste {{ $user->vendor->submitted_at?->format('d/m/Y H:i') }}. Si editas los datos comerciales, volverá a borrador y tendrás que enviarla nuevamente.</p>
                @elseif($profileForm->vendorStatus === 'suspended')
                    <h2 class="mt-2 text-xl font-black">Perfil suspendido</h2>
                    <p class="mt-2 text-sm font-semibold text-brand-muted">Contacta a soporte para conocer el motivo y solicitar una revisión administrativa.</p>
                    <a class="mt-5 inline-flex rounded-full bg-brand px-5 py-3 text-sm font-black text-white" href="{{ route('support.create', ['category' => 'provider_suspension']) }}">Contactar soporte</a>
                @else
                    <h2 class="mt-2 text-xl font-black">Envía tu perfil cuando esté listo</h2>
                    <p class="mt-2 text-sm font-semibold text-brand-muted">Guardar el perfil no lo envía automáticamente. Tú decides cuándo solicitar la revisión.</p>
                    @if($profileForm->missingReviewRequirements !== [] || ! $user->hasVerifiedEmail())
                        <p class="mt-4 rounded-2xl bg-brand-warning-soft p-4 text-sm font-bold text-brand-warning-copy">Antes de enviarlo falta: {{ collect($profileForm->missingReviewRequirements)->values()->join(', ') }}{{ ! $user->hasVerifiedEmail() ? ($profileForm->missingReviewRequirements ? ', ' : '').'verificar correo' : '' }}.</p>
                    @else
                        <form class="mt-5" method="POST" action="{{ route('provider-applications.submit') }}">@csrf<button class="rounded-full bg-brand-success px-6 py-3 font-black text-white" type="submit">Enviar solicitud de verificación</button></form>
                    @endif
                @endif
            </section>
        @endif
    </main>
</body>
</html>
