<x-layouts.auth title="Verifica tu correo - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-[#d2693c]">Confirma que eres tú</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Revisa tu correo</h1>
        <p class="mt-3 leading-7 text-[#6f827b]">Enviamos un enlace de verificación a <strong>{{ auth()->user()->email }}</strong>. Ábrelo para confirmar que el correo te pertenece.</p>
    </div>

    @if(config('mail.default') === 'log')
        <div class="mt-6 rounded-2xl border border-[#F5D48D] bg-[#FFF8E6] px-4 py-3 text-sm font-bold leading-6 text-[#79551E]">Modo local: todavía no existe un servidor SMTP conectado. El enlace se guarda en <code>storage/logs/laravel.log</code> y no llegará a tu bandeja.</div>
    @endif

    @if (session('status') === 'verification-link-sent')
        <div class="mt-6 rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-4 py-3 text-sm font-bold text-[#14734A]">{{ config('mail.default') === 'log' ? 'Generamos un enlace nuevo en el registro local.' : 'Enviamos un enlace nuevo. Revisa también la carpeta de spam.' }}</div>
    @endif

    <form class="mt-8" method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="w-full rounded-2xl bg-[#17352b] px-5 py-4 font-black text-white" type="submit">Volver a enviar el correo</button>
    </form>

    <a class="mt-4 block w-full rounded-2xl border border-[#17352b]/15 px-5 py-4 text-center font-black text-[#1f6b4f]" href="{{ route('dashboard') }}">Continuar al inicio</a>
</x-layouts.auth>
