<x-layouts.auth title="Verificar teléfono">
    <h1 class="text-2xl font-black">Verifica tu teléfono</h1>
    @include('auth.partials.phone-verification')
    <a class="mt-6 block text-brand-success" href="{{ route($administrative ? 'admin.verification.notice' : 'profile.edit') }}">Volver</a>
</x-layouts.auth>
