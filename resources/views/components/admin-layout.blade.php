@props([
    'title' => 'Administración',
    'section' => 'center',
    'unreadCount' => null,
    'pendingCount' => null,
])

@php
    $adminUser = auth()->user();
    $unreadCount ??= $adminUser?->unreadNotifications()->count() ?? 0;
    $pendingCount ??= \App\Models\Vendor::query()->where('status', 'pending')->whereNotNull('submitted_at')->count();
    $isSuperadmin = $adminUser?->hasRole('superadmin') ?? false;
    $roleLabel = $isSuperadmin ? 'Superadministrador' : 'Administrador';
    $navGroups = [
        'Operación' => [
            ['key' => 'center', 'label' => 'Centro de operación', 'href' => route('admin.index'), 'icon' => 'grid'],
            ['key' => 'audit', 'label' => 'Auditoría', 'href' => route('admin.index').'#auditoria', 'icon' => 'history'],
        ],
        'Gestión' => [
            ['key' => 'accounts', 'label' => 'Cuentas', 'href' => route('admin.users.index'), 'icon' => 'users', 'badge' => $pendingCount ?: null],
            ['key' => 'posts', 'label' => 'Publicaciones', 'href' => route('admin.posts.index'), 'icon' => 'document'],
            ['key' => 'employment', 'label' => 'Empleo y postulaciones', 'href' => route('admin.jobs.index'), 'icon' => 'briefcase'],
            ['key' => 'operations', 'label' => 'Operaciones', 'href' => route('admin.operations.index'), 'icon' => 'orders'],
            ['key' => 'disputes', 'label' => 'Disputas', 'href' => route('disputes.admin-index'), 'icon' => 'alert'],
        ],
        'Atención y negocio' => [
            ['key' => 'support', 'label' => 'Soporte', 'href' => route('admin.support.index'), 'icon' => 'support'],
            ['key' => 'promotions', 'label' => 'Publicidad', 'href' => route('admin.promotions.index'), 'icon' => 'megaphone'],
            ['key' => 'payments', 'label' => 'Pagos y conciliación', 'href' => route('admin.payments.index'), 'icon' => 'payment'],
        ],
        'Sistema' => [
            ['key' => 'settings', 'label' => 'Configuración', 'href' => route('admin.index').'#configuracion', 'icon' => 'settings'],
        ],
    ];
@endphp

@once
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-admin-summary-url="{{ route('admin.summary') }}" class="min-h-screen bg-brand-page text-brand-ink antialiased">
@endonce

<div class="min-h-screen lg:grid lg:grid-cols-[17rem_minmax(0,1fr)]">
    <aside class="hidden min-h-screen border-r border-white/10 bg-brand-navy-deep text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
        <a class="flex items-center gap-3 border-b border-white/10 px-6 py-6 font-black" href="{{ route('admin.index') }}">
            <span class="grid size-10 place-items-center rounded-2xl bg-brand-orange text-white">P</span>
            <span><span class="block text-base">Plaza Local</span><span class="mt-0.5 block text-[.65rem] font-bold uppercase tracking-[.18em] text-white/55">Administración</span></span>
        </a>

        <nav class="min-h-0 flex-1 overflow-y-auto px-4 py-5" aria-label="Navegación administrativa">
            @foreach($navGroups as $group => $items)
                <p class="mb-2 mt-5 px-3 text-[.65rem] font-black uppercase tracking-[.18em] text-white/45 first:mt-0">{{ $group }}</p>
                <div class="space-y-1">
                    @foreach($items as $item)
                        <a class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $section === $item['key'] ? 'bg-white text-brand-navy-deep shadow-sm' : 'text-white/75 hover:bg-white/10 hover:text-white' }}" href="{{ $item['href'] }}">
                            <span class="grid size-6 place-items-center" aria-hidden="true">
                                @switch($item['icon'])
                                    @case('grid')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>@break
                                    @case('history')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M4 12a8 8 0 1 0 2.3-5.7L4 8.6"/><path d="M4 4v4.6h4.6M12 8v4l2.8 1.8"/></svg>@break
                                    @case('users')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3.5 19c.4-4 2.2-6 5.5-6s5.1 2 5.5 6M15 5.5a3 3 0 0 1 0 5.8M16 14c2.7.3 4.1 2 4.5 5"/></svg>@break
                                    @case('document')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6M9 16h6"/></svg>@break
                                    @case('briefcase')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2"/></svg>@break
                                    @case('orders')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 3.5h6V6H9zM8 11h8M8 15h5"/></svg>@break
                                    @case('alert')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M12 3 2.8 20h18.4zM12 9v5M12 17.5v.5"/></svg>@break
                                    @case('support')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M8.5 9a3.5 3.5 0 0 1 6.8 1.1c0 2.2-3.3 2.3-3.3 4.4M12 18v.2"/></svg>@break
                                    @case('megaphone')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="m4 13 12-5v10L4 13zM16 10c2 0 4-1.5 4-3v12c0-1.5-2-3-4-3M6 14l1.5 6h4L10 13"/></svg>@break
                                    @case('payment')<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 9h18M7 15h4"/></svg>@break
                                    @default<svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19 13.5v-3l-2-.6a7 7 0 0 0-.7-1.7l1-1.8-2.1-2.1-1.8 1A7 7 0 0 0 11.5 5L11 3H8l-.6 2a7 7 0 0 0-1.7.7l-1.8-1-2.1 2.1 1 1.8A7 7 0 0 0 2 10.5l-2 .5v3l2 .6a7 7 0 0 0 .7 1.7l-1 1.8 2.1 2.1 1.8-1a7 7 0 0 0 1.7.7l.6 2h3l.6-2a7 7 0 0 0 1.7-.7l1.8 1 2.1-2.1-1-1.8a7 7 0 0 0 .7-1.7z" transform="translate(2 -1) scale(.9)"/></svg>
                                @endswitch
                            </span>
                            <span class="min-w-0 flex-1">{{ $item['label'] }}</span>
                            @if($item['key'] === 'accounts')<span data-admin-pending-badge class="rounded-full bg-brand-orange px-2 py-0.5 text-[.65rem] text-white {{ ($item['badge'] ?? 0) ? '' : 'hidden' }}">{{ min(99, $item['badge'] ?? 0) }}</span>@elseif($item['badge'] ?? null)<span class="rounded-full bg-brand-orange px-2 py-0.5 text-[.65rem] text-white">{{ min(99, $item['badge']) }}</span>@endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <form class="border-t border-white/10 p-4" method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-black text-brand-danger-pale hover:bg-red-500/10" type="submit"><span aria-hidden="true">↪</span>Cerrar sesión</button>
        </form>
    </aside>

    <div class="min-w-0">
        <header class="sticky top-0 z-40 border-b border-brand/10 bg-white/95 backdrop-blur-xl">
            <div class="flex min-h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <details class="relative lg:hidden">
                    <summary class="grid size-10 cursor-pointer list-none place-items-center rounded-xl border border-brand/10 bg-white" aria-label="Abrir menú administrativo"><span class="text-xl">☰</span></summary>
                    <div class="fixed inset-x-3 top-[4.5rem] max-h-[calc(100vh-6rem)] overflow-y-auto rounded-3xl border border-brand/10 bg-brand-navy-deep p-4 text-white shadow-2xl">
                        @foreach($navGroups as $group => $items)
                            <p class="mb-2 mt-4 px-2 text-[.65rem] font-black uppercase tracking-[.16em] text-white/45 first:mt-0">{{ $group }}</p>
                            @foreach($items as $item)<a class="mb-1 flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-bold {{ $section === $item['key'] ? 'bg-white text-brand-navy-deep' : 'text-white/80' }}" href="{{ $item['href'] }}"><span>{{ $item['label'] }}</span>@if($item['badge'] ?? null)<span class="rounded-full bg-brand-orange px-2 py-0.5 text-[.65rem] text-white">{{ min(99, $item['badge']) }}</span>@endif</a>@endforeach
                        @endforeach
                        <form class="mt-4 border-t border-white/10 pt-3" method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-black text-brand-danger-pale" type="submit">Cerrar sesión</button></form>
                    </div>
                </details>

                <form class="hidden max-w-md flex-1 sm:flex" method="GET" action="{{ route('admin.users.index') }}">
                    <label class="flex w-full items-center gap-3 rounded-2xl bg-brand-page px-4 py-2.5"><svg viewBox="0 0 24 24" class="size-5 fill-none stroke-brand-copy" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/></svg><span class="sr-only">Buscar cuenta</span><input class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm outline-none ring-0" type="search" name="q" maxlength="100" placeholder="Buscar cuenta por nombre, correo o teléfono"></label>
                </form>

                <div class="ml-auto flex items-center gap-2 sm:gap-3">
                    <x-admin-notification-tray :user="$adminUser" :unread-count="$unreadCount" />
                    <div class="flex items-center gap-3 rounded-full border border-brand/10 bg-white py-1.5 pl-1.5 pr-3">
                        <span class="grid size-9 place-items-center rounded-full bg-brand-teal-surface font-black text-brand">{{ mb_strtoupper(mb_substr($adminUser?->name ?? 'A', 0, 1)) }}</span>
                        <span class="hidden min-w-0 sm:block"><strong class="block max-w-36 truncate text-sm">{{ $adminUser?->name }}</strong><span class="block text-[.68rem] font-bold text-brand-muted">{{ $roleLabel }}</span></span>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-7 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</div>

@once
</body>
</html>
@endonce
