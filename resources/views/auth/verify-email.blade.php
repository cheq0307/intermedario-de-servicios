<x-layouts.auth title="Verifica tu correo - Plaza Local">
    <div>
        <p class="text-xs font-black uppercase tracking-[.2em] text-brand-coral-strong">Confirma que eres tú</p>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">Revisa tu correo</h1>
        <p class="mt-3 leading-7 text-brand-sage-muted">Enviamos un enlace de verificación a <strong>{{ auth()->user()->email }}</strong>. Ábrelo para confirmar que el correo te pertenece.</p>
    </div>

    @if(config('mail.default') === 'log')
        <div class="mt-6 rounded-2xl border border-brand-gold-pale bg-brand-gold-faint px-4 py-3 text-sm font-bold leading-6 text-brand-warning-copy">Modo local: todavía no existe un servidor SMTP conectado. El enlace se guarda en <code>storage/logs/laravel.log</code> y no llegará a tu bandeja.</div>
    @endif

    @if (session('status') === 'verification-link-sent')
        <div class="mt-6 rounded-2xl border border-brand-success-bright/20 bg-brand-success-soft px-4 py-3 text-sm font-bold text-brand-success">{{ config('mail.default') === 'log' ? 'Generamos un enlace nuevo en el registro local.' : 'Enviamos un enlace nuevo. Revisa también la carpeta de spam.' }}</div>
    @endif
    @if (session('verification_delivery_failed'))
        <div class="mt-6 rounded-2xl border border-brand-coral/20 bg-brand-coral-soft px-4 py-3 text-sm font-bold text-brand-coral-strong" role="alert">No pudimos conectarnos al servicio de correo. Tu cuenta sigue activa; inténtalo nuevamente más tarde.</div>
    @endif

    <form class="mt-8" method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="w-full rounded-2xl bg-brand-forest-deep px-5 py-4 font-black text-white" type="submit">Volver a enviar el correo</button>
    </form>

    <a class="mt-4 block w-full rounded-2xl border border-brand-forest-deep/15 px-5 py-4 text-center font-black text-brand-forest-strong" href="{{ route('dashboard') }}">Continuar al inicio</a>
</x-layouts.auth>
