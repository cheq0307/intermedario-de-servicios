@props(['user'])

@php
    $actor = auth()->user();
    $isAuthority = $user->hasAnyRole(['admin', 'superadmin']);
    $canManage = $actor && $actor->id !== $user->id && ! $isAuthority;
    $isSuperadmin = $actor?->hasRole('superadmin') ?? false;
@endphp

@if($canManage)
    <details class="relative">
        <summary class="cursor-pointer list-none font-black text-[#D85B0B]">Gestionar cuenta</summary>
        <div class="mt-3 w-72 space-y-3 rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] p-4 shadow-sm">
            @if($user->account_status === 'active')
                <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                    @csrf
                    @method('PATCH')
                    <label class="text-xs font-black">Motivo de suspensión<textarea class="mt-2 min-h-20 w-full rounded-xl border border-[#123B4A]/10 bg-white px-3 py-2 text-sm font-normal" name="reason" minlength="10" maxlength="1000" required></textarea></label>
                    <button class="mt-2 w-full rounded-full bg-[#D85B0B] px-4 py-2 text-xs font-black text-white" type="submit">Suspender cuenta</button>
                </form>
                @if($isSuperadmin)
                    <form class="border-t border-[#123B4A]/10 pt-3" method="POST" action="{{ route('admin.users.deactivate', $user) }}">
                        @csrf
                        @method('PATCH')
                        <label class="text-xs font-black">Motivo de baja<textarea class="mt-2 min-h-20 w-full rounded-xl border border-red-200 bg-white px-3 py-2 text-sm font-normal" name="reason" minlength="10" maxlength="1000" required></textarea></label>
                        <button class="mt-2 w-full rounded-full border border-red-300 px-4 py-2 text-xs font-black text-red-700" type="submit">Dar de baja</button>
                    </form>
                @endif
            @elseif($user->account_status === 'suspended')
                <p class="text-xs font-semibold text-[#6B7D83]">{{ $user->account_status_reason }}</p>
                <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-[#14734A] px-4 py-2 text-xs font-black text-white" type="submit">Reactivar cuenta</button></form>
                @if($isSuperadmin)
                    <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">@csrf @method('PATCH')<input type="hidden" name="reason" value="Baja definitiva posterior a una suspensión administrativa documentada."><button class="w-full rounded-full border border-red-300 px-4 py-2 text-xs font-black text-red-700" type="submit">Dar de baja</button></form>
                @endif
            @elseif($user->account_status === 'deactivated' && $isSuperadmin)
                <p class="text-xs font-semibold text-[#6B7D83]">{{ $user->account_status_reason }}</p>
                <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">@csrf @method('PATCH')<button class="w-full rounded-full bg-[#14734A] px-4 py-2 text-xs font-black text-white" type="submit">Restaurar cuenta dada de baja</button></form>
            @endif
        </div>
    </details>
@endif
