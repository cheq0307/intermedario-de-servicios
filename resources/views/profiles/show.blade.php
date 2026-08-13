<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $user->name }} - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased">
    @php
        $isOwner = auth()->id() === $user->id;
        $canViewPrivateContact = $isOwner || auth()->user()?->hasAnyRole(['admin', 'superadmin']);
        $isProvider = $user->canActAsProvider();
        $vendor = $user->vendor;
        $roleLabels = ['client' => 'Cliente', 'provider' => 'Proveedor', 'admin' => 'Administrador', 'superadmin' => 'Superadministrador'];
        $profileRoles = $user->getRoleNames()->map(fn ($role) => $roleLabels[$role] ?? null)->filter();
        $dayLabels = ['monday' => 'Lun', 'tuesday' => 'Mar', 'wednesday' => 'Mié', 'thursday' => 'Jue', 'friday' => 'Vie', 'saturday' => 'Sáb', 'sunday' => 'Dom'];
        $businessHours = $vendor?->business_hours ?? [];
        $withinBusinessHours = $vendor?->isWithinBusinessHours();
        $availability = $vendor?->status === 'suspended'
            ? ['Perfil suspendido', '#8A3A3A', '#FCE8E8']
            : [
                'available' => ['Disponible para nuevo trabajo', '#14734A', '#E9F7F0'],
                'busy' => ['Realizando un trabajo', '#9A5A0A', '#FFF4D6'],
                'unavailable' => ['No disponible', '#8A3A3A', '#FCE8E8'],
            ][$vendor?->availability_status ?? 'available'];
    @endphp

    <header class="border-b border-[#123B4A]/10 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4">
            <a class="flex items-center gap-3 font-black" href="{{ route('dashboard') }}"><span class="grid size-10 place-items-center rounded-2xl bg-[#123B4A] text-white">P</span> Plaza Local</a>
            <div class="flex gap-2">
                @if ($isOwner)
                    <a class="rounded-full bg-[#F97316] px-5 py-2.5 text-sm font-black text-white" href="{{ route('profile.edit') }}">Editar perfil</a>
                @endif
                <a class="rounded-full border border-[#123B4A]/10 bg-white px-4 py-2.5 text-sm font-black" href="{{ route('dashboard') }}">Inicio</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-5 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]">{{ session('status') }}</div>
        @endif

        <section class="overflow-hidden rounded-[2rem] border border-[#123B4A]/10 bg-white shadow-sm">
            <div class="h-32 bg-[linear-gradient(120deg,#123B4A,#1F6B4F_55%,#F2C66D)] sm:h-44"></div>
            <div class="px-6 pb-7 sm:px-9">
                <div class="-mt-14 flex flex-col gap-5 sm:-mt-16 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        @if ($user->avatar_path)
                            <img class="size-28 rounded-[2rem] border-4 border-white object-cover shadow-lg sm:size-32" src="{{ asset('storage/'.$user->avatar_path) }}" alt="Foto de {{ $user->name }}">
                        @else
                            <span class="grid size-28 place-items-center rounded-[2rem] border-4 border-white bg-[#DCEAE6] text-4xl font-black text-[#123B4A] shadow-lg sm:size-32">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        @endif
                        <div class="pb-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="rounded-xl bg-white px-3 py-1 text-3xl font-black tracking-tight text-[#123B4A] shadow-sm">{{ $isProvider ? ($vendor?->display_name ?? $user->name) : $user->name }}</h1>
                                @if ($vendor?->verified_at)<span class="rounded-full bg-[#E9F7F0] px-3 py-1 text-xs font-black text-[#14734A]">Verificado</span>@endif
                            </div>
                            @if($profileRoles->isNotEmpty())<div class="mt-2 flex flex-wrap gap-2">@foreach($profileRoles as $role)<span class="rounded-full border border-[#123B4A]/10 bg-[#FAF8F4] px-3 py-1 text-xs font-black text-[#536A72]">{{ $role }}</span>@endforeach</div>@endif
                            <p class="mt-2 font-bold text-[#6B7D83]">{{ $isProvider ? ($vendor?->specialty ?: 'Proveedor local') : 'Cliente de la comunidad' }} @if($user->city) · {{ $user->city }} @endif</p>
                            @if ($canViewPrivateContact)
                                <p class="mt-1 break-all text-sm font-bold text-[#314B54]">Correo: <a class="text-[#14734A] underline decoration-[#14734A]/30 underline-offset-2" href="mailto:{{ $user->email }}">{{ $user->email }}</a></p>
                            @elseif ($user->hasVerifiedEmail())
                                <p class="mt-1 text-sm font-bold text-[#14734A]">Correo verificado</p>
                            @endif
                        </div>
                    </div>
                    @if ($isProvider)
                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <span class="w-fit rounded-full px-4 py-2 text-sm font-black" style="color: {{ $availability[1] }}; background: {{ $availability[2] }}"><span class="mr-2 inline-block size-2 rounded-full" style="background: {{ $availability[1] }}"></span>{{ $availability[0] }}</span>
                            @if($withinBusinessHours === true)<span class="w-fit rounded-full bg-[#E9F7F0] px-4 py-2 text-sm font-black text-[#14734A]">En horario laboral</span>@elseif($withinBusinessHours === false)<span class="w-fit rounded-full bg-[#F1F3F3] px-4 py-2 text-sm font-black text-[#536A72]">Fuera de horario</span>@else<span class="w-fit rounded-full bg-[#FFF4D6] px-4 py-2 text-sm font-black text-[#79551E]">Horario no configurado</span>@endif
                        </div>
                    @endif
                </div>

                <div class="mt-7 grid gap-6 lg:grid-cols-[1fr_300px]">
                    <div>
                        <p class="max-w-2xl whitespace-pre-line leading-7 text-[#536A72]">{{ $isProvider ? ($vendor?->description ?: $user->bio ?: 'Este proveedor todavía está completando su presentación.') : ($user->bio ?: 'Este cliente todavía está completando su presentación.') }}</p>
                        @if ($isProvider)
                            <div class="mt-5 flex flex-wrap gap-2 text-sm font-bold text-[#536A72]">
                                @if ($vendor?->service_area)<span class="rounded-full bg-[#FAF8F4] px-4 py-2">Zona: {{ $vendor->service_area }}</span>@endif
                                @if ($vendor?->businessHoursConfigured())<span class="rounded-full bg-[#FAF8F4] px-4 py-2">Horario: {{ collect($businessHours['days'])->map(fn ($day) => $dayLabels[$day] ?? $day)->join(', ') }} · {{ $businessHours['opens_at'] }}–{{ $businessHours['closes_at'] }}</span>@endif
                                @if ($vendor?->years_experience !== null)<span class="rounded-full bg-[#FAF8F4] px-4 py-2">{{ $vendor->years_experience }} años de experiencia</span>@endif
                            </div>
                        @endif
                    </div>
                    <div class="grid grid-cols-3 gap-2 rounded-2xl bg-[#FAF8F4] p-4 text-center">
                        <div><strong class="block text-xl">{{ $user->posts_count }}</strong><span class="text-xs font-bold text-[#6B7D83]">Publicaciones</span></div>
                        <div><strong class="block text-xl">{{ $isProvider ? $user->posts_count : $user->job_requests_count }}</strong><span class="text-xs font-bold text-[#6B7D83]">Actividad</span></div>
                        <div><strong class="block text-xl">{{ $rating ? number_format($rating, 1) : '—' }}</strong><span class="text-xs font-bold text-[#6B7D83]">{{ $reviewsCount }} reseñas</span></div>
                    </div>
                </div>

                @if (! $isOwner)
                    <div class="mt-7 rounded-2xl border border-[#F97316]/15 bg-[#FFF8F2] p-4">
                        @if ($user->hasVerifiedEmail())
                            <form method="POST" action="{{ route('conversations.start') }}">@csrf<input type="hidden" name="recipient_id" value="{{ $user->id }}"><button class="rounded-full bg-[#123B4A] px-6 py-3 font-black text-white" type="submit">Contactar dentro de Plaza Local</button></form>
                            <p class="mt-2 text-xs font-bold text-[#8A6A55]">La conversaci&oacute;n quedar&aacute; protegida dentro de la plataforma. No mostraremos correo ni tel&eacute;fono.</p>
                        @else
                            <p class="text-sm font-black text-[#8A6A55]">Esta persona debe verificar su correo antes de recibir conversaciones.</p>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        @if ($isProvider && ($vendor?->certifications || $vendor?->tools))
            <section class="mt-6 grid gap-4 sm:grid-cols-2">
                @if ($vendor?->certifications)<div class="rounded-3xl border border-[#123B4A]/10 bg-white p-6"><h2 class="font-black">Certificaciones y preparación</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-[#536A72]">{{ $vendor->certifications }}</p></div>@endif
                @if ($vendor?->tools)<div class="rounded-3xl border border-[#123B4A]/10 bg-white p-6"><h2 class="font-black">Herramientas y capacidades</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-[#536A72]">{{ $vendor->tools }}</p></div>@endif
            </section>
        @endif

        <section class="mt-9">
            <p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Actividad pública</p>
            <h2 class="mt-1 text-2xl font-black">Publicaciones de {{ explode(' ', trim($user->name))[0] }}</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @forelse ($posts as $post)
                    <article class="rounded-3xl border border-[#123B4A]/10 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3"><span class="text-xs font-black uppercase tracking-[.12em] text-[#F97316]">{{ str_replace('_', ' ', $post->type) }}</span><time class="text-xs font-bold text-[#8A999E]">{{ $post->published_at->diffForHumans() }}</time></div>
                        <p class="mt-4 whitespace-pre-line leading-7 text-[#314B54]">{{ $post->body }}</p>
                        @if ($post->listing)<p class="mt-4 rounded-2xl bg-[#FAF8F4] px-4 py-3 font-black">{{ $post->listing->name }}</p>@endif
                        @if ($post->jobRequest)<p class="mt-4 rounded-2xl bg-[#FFF8F2] px-4 py-3 font-black">{{ $post->jobRequest->title }}</p>@endif
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-[#123B4A]/20 bg-white/60 p-10 text-center text-sm font-bold text-[#6B7D83] md:col-span-2">Todavía no hay publicaciones públicas.</div>
                @endforelse
            </div>
            @if ($posts->hasPages())<div class="mt-6">{{ $posts->links() }}</div>@endif
        </section>
    </main>
</body>
</html>
