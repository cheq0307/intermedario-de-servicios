@props(['user'])

@php
    $actor = auth()->user();
    $isAuthority = $user->hasAnyRole(['admin', 'superadmin']);
    $canManage = $actor instanceof \App\Models\AdminUser && ! $actor->ownsMarketplaceAccount($user->id) && ! $isAuthority;
    $isSuperadmin = $actor?->hasRole('superadmin') ?? false;
    $accountLabels = ['active' => 'Activa', 'suspended' => 'Suspendida', 'deactivated' => 'Dada de baja'];
@endphp

<section class="rounded-3xl border border-brand/10 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-black uppercase tracking-[.14em] text-brand-muted">Acceso a Plaza Local</p>
            <h2 class="mt-2 text-xl font-black">Control de la cuenta</h2>
        </div>
        <span class="inline-flex items-center gap-2 font-black">
            <span class="size-2.5 rounded-full {{ $user->account_status === 'active' ? 'bg-brand-success' : 'bg-red-600' }}"></span>
            {{ $accountLabels[$user->account_status] ?? ucfirst($user->account_status) }}
        </span>
    </div>

    @if($user->account_status_reason)
        <div class="mt-4 rounded-2xl bg-brand-surface p-4 text-sm"><strong>Motivo registrado:</strong><p class="mt-1 text-brand-copy">{{ $user->account_status_reason }}</p></div>
    @endif

    @if(!$canManage)
        <p class="mt-4 text-sm font-semibold text-brand-muted">Esta cuenta de autoridad se administra mediante delegación de cargos, no desde el ciclo comercial.</p>
    @elseif($user->account_status === 'active')
        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <form class="rounded-2xl border border-brand/10 p-4" method="POST" action="{{ route('admin.users.suspend', $user) }}">
                @csrf
                @method('PATCH')
                <label class="text-sm font-black">Suspensión temporal<textarea class="mt-2 min-h-24 w-full rounded-xl border border-brand/15 bg-white px-3 py-2 font-normal" name="reason" minlength="10" maxlength="1000" placeholder="Describe el motivo administrativo" required></textarea></label>
                <button class="mt-3 rounded-full bg-brand px-5 py-2.5 text-sm font-black text-white" type="submit">Suspender cuenta</button>
            </form>
            @if($isSuperadmin)
                <form class="rounded-2xl border border-red-200 p-4" method="POST" action="{{ route('admin.users.deactivate', $user) }}">
                    @csrf
                    @method('PATCH')
                    <label class="text-sm font-black text-red-800">Baja administrativa<textarea class="mt-2 min-h-24 w-full rounded-xl border border-red-200 bg-white px-3 py-2 font-normal text-brand-ink" name="reason" minlength="10" maxlength="1000" placeholder="Describe por qué se dará de baja" required></textarea></label>
                    <button class="mt-3 rounded-full border border-red-300 px-5 py-2.5 text-sm font-black text-red-700" type="submit">Dar de baja</button>
                </form>
            @endif
        </div>
    @elseif($user->account_status === 'suspended')
        <div class="mt-5 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">@csrf @method('PATCH')<button class="rounded-full bg-brand-success px-5 py-2.5 text-sm font-black text-white" type="submit">Reactivar cuenta</button></form>
            @if($isSuperadmin)
                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">@csrf @method('PATCH')<input type="hidden" name="reason" value="Baja definitiva posterior a una suspensión administrativa documentada."><button class="rounded-full border border-red-300 px-5 py-2.5 text-sm font-black text-red-700" type="submit">Dar de baja</button></form>
            @endif
        </div>
    @elseif($user->account_status === 'deactivated' && $isSuperadmin)
        <form class="mt-5" method="POST" action="{{ route('admin.users.reactivate', $user) }}">@csrf @method('PATCH')<button class="rounded-full bg-brand-success px-5 py-2.5 text-sm font-black text-white" type="submit">Restaurar cuenta dada de baja</button></form>
    @endif
</section>
