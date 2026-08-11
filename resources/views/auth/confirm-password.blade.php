<x-layouts.auth title="Confirmar contraseña - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Área protegida</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Confirma tu contraseña</h1>
        <p class="mt-3 leading-7 text-[#6f827b]">Antes de modificar la autenticación de dos pasos necesitamos comprobar nuevamente tu identidad.</p>
    </div>

    <form class="mt-8 space-y-5" method="POST" action="{{ route('password.confirm.store') }}">
        @csrf
        <x-password-input id="confirm-current-password" label="Contraseña actual" autocomplete="current-password" :autofocus="true" />
        <button class="w-full rounded-2xl bg-[#17352b] px-5 py-4 font-black text-white" type="submit">Confirmar y continuar</button>
    </form>
</x-layouts.auth>
