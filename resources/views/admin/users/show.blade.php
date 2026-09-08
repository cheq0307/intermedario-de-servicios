<x-admin-layout title="Administrar cuenta" section="accounts">
    @php
        $accountLabels = ['active' => 'Activa', 'suspended' => 'Suspendida', 'deactivated' => 'Dada de baja'];
        $commercialLabels = ['draft' => 'Borrador', 'pending' => 'Pendiente de revisión', 'active' => 'Habilitada', 'rejected' => 'Requiere cambios', 'suspended' => 'Suspendida'];
        $role = $user->hasRole('superadmin') ? 'Superadministrador' : ($user->hasRole('admin') ? 'Administrador' : 'Cuenta normal');
        $commercialStatus = $user->vendor ? ($commercialLabels[$user->vendor->status] ?? ucfirst($user->vendor->status)) : '—';
        $commercialReady = $user->vendor?->isReadyForReview() ?? false;
    @endphp

    @if(session('status'))<div class="mb-6 rounded-2xl bg-brand-success-soft px-5 py-4 text-sm font-black text-brand-success">{{ session('status') }}</div>@endif

    <a class="inline-flex items-center gap-2 text-sm font-black text-brand-success" href="{{ route('admin.users.index') }}"><span aria-hidden="true">←</span> Volver a cuentas</a>

    <section class="mt-5 rounded-[2rem] border border-brand/10 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                @if($user->avatar_path)<img class="size-20 rounded-3xl object-cover" src="{{ $user->avatarUrl() }}" alt="">@else<span class="grid size-20 shrink-0 place-items-center rounded-3xl bg-brand-avatar-strong text-2xl font-black">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>@endif
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[.14em] text-brand-orange">Expediente de cuenta</p>
                    <h1 class="mt-2 break-words text-3xl font-black">{{ $user->name }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2"><span class="rounded-full border border-brand/20 px-3 py-1 text-xs font-black">{{ $role }}</span><span class="inline-flex items-center gap-2 text-sm font-bold"><span class="size-2 rounded-full {{ $user->account_status === 'active' ? 'bg-brand-success' : 'bg-red-600' }}"></span>{{ $accountLabels[$user->account_status] ?? ucfirst($user->account_status) }}</span></div>
                    <p class="mt-3 break-all text-sm text-brand-copy">{{ $user->email }}{{ $user->phone ? ' · '.$user->phone : '' }}</p>
                    <p class="mt-1 text-sm font-semibold text-brand-copy">{{ $user->community?->name ?? 'Sin comunidad' }}{{ $user->community?->municipality ? ' · '.$user->community->municipality.', '.$user->community->state : '' }}</p>
                </div>
            </div>
            <a class="inline-flex justify-center rounded-full border border-brand px-5 py-2.5 text-sm font-black text-brand" href="{{ route('admin.users.preview', $user) }}">Ver perfil público</a>
        </div>
    </section>

    @if($user->account_status !== 'active' || $user->vendor?->status === 'suspended')
        <section class="mt-5 grid gap-3 md:grid-cols-2">
            @if($user->account_status !== 'active')<div class="rounded-2xl border border-red-200 bg-red-50 p-4"><strong class="text-red-800">Acceso de cuenta: {{ $accountLabels[$user->account_status] }}</strong><p class="mt-1 text-sm text-red-700">La persona no puede operar en Plaza Local hasta reactivar su cuenta.</p></div>@endif
            @if($user->vendor?->status === 'suspended')<div class="rounded-2xl border border-red-200 bg-red-50 p-4"><strong class="text-red-800">Actividad comercial suspendida</strong><p class="mt-1 text-sm text-red-700">El expediente comercial está suspendido, aunque el acceso general de la cuenta puede seguir activo.</p></div>@endif
        </section>
    @endif

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([
            ['Publicaciones', $user->posts_count],
            ['Compras', $user->purchases_count],
            ['Solicitudes', $user->job_requests_count],
            ['Propuestas', $user->job_proposals_count],
            ['Empleo', $user->job_vacancies_count + $user->job_applications_count],
            ['Conversaciones', $user->conversations_count],
        ] as [$label, $value])
            <article class="rounded-2xl border border-brand/10 bg-white p-4 shadow-sm"><strong class="text-2xl">{{ $value }}</strong><p class="mt-1 text-xs font-bold text-brand-muted">{{ $label }}</p></article>
        @endforeach
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
        <x-admin-account-actions :user="$user" />

        <section class="rounded-3xl border border-brand/10 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[.14em] text-brand-muted">Capacidad para vender u ofrecer</p>
            <div class="mt-3 flex items-center justify-between gap-3"><h2 class="text-xl font-black">Expediente comercial</h2><strong class="{{ $user->vendor?->status === 'suspended' ? 'text-red-700' : 'text-brand-copy' }}">{{ $commercialStatus }}</strong></div>
            @if(!$user->vendor)
                <p class="mt-4 text-sm text-brand-muted">La cuenta todavía no ha creado un expediente comercial.</p>
            @else
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="font-bold text-brand-muted">Identidad</dt><dd class="mt-1 font-black">{{ $user->vendor->verified_at ? 'Verificada' : 'Sin verificar' }}</dd></div>
                    <div><dt class="font-bold text-brand-muted">Documentos</dt><dd class="mt-1 font-black">{{ $user->vendor->verification_documents_count }}</dd></div>
                    <div><dt class="font-bold text-brand-muted">Publicaciones comerciales</dt><dd class="mt-1 font-black">{{ $user->vendor->listings_count }}</dd></div>
                    <div><dt class="font-bold text-brand-muted">Órdenes</dt><dd class="mt-1 font-black">{{ $user->vendor->orders_count }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-wrap gap-3">
                    @if($user->vendor->status === 'suspended' && $commercialReady)
                        <form method="POST" action="{{ route('admin.vendors.approve', $user->vendor) }}">@csrf @method('PATCH')<button class="rounded-full bg-brand-success px-5 py-2.5 text-sm font-black text-white" type="submit">Reactivar actividad comercial</button></form>
                    @elseif($user->vendor->status === 'suspended')
                        <span class="self-center text-xs font-bold text-red-700">Faltan requisitos antes de reactivar.</span>
                    @endif
                    <a class="inline-flex rounded-full border border-brand px-5 py-2.5 text-sm font-black text-brand" href="{{ route('admin.vendors.show', $user->vendor) }}">Administrar expediente</a>
                </div>
            @endif
        </section>
    </div>

    <section class="mt-5 rounded-3xl border border-brand/10 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[.14em] text-brand-muted">Trazabilidad</p><h2 class="mt-2 text-xl font-black">Actividad administrativa reciente</h2></div><span class="text-sm font-bold text-brand-muted">{{ $user->support_tickets_count }} casos de soporte · {{ $user->followers_count }} seguidores</span></div>
        <div class="mt-4 divide-y divide-brand/10">
            @forelse($auditLogs as $log)
                <article class="grid gap-1 py-3 text-sm sm:grid-cols-[10rem_1fr_12rem]"><span class="text-brand-muted">{{ $log->created_at?->format('d/m/Y H:i') }}</span><strong>{{ $log->action }}</strong><span class="text-brand-muted">{{ $log->admin?->name ?? $log->user?->name ?? 'Sistema' }}</span></article>
            @empty
                <p class="py-6 text-center font-semibold text-brand-muted">No hay cambios administrativos registrados para esta cuenta.</p>
            @endforelse
        </div>
    </section>
</x-admin-layout>
