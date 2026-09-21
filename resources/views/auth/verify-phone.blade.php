<x-layouts.auth :title="config('phone_verification.enabled') ? 'Verificar teléfono' : 'Guardar celular'">
    <h1 class="text-2xl font-black">{{ config('phone_verification.enabled') ? 'Verifica tu teléfono' : 'Tu número de celular' }}</h1>
    @include('auth.partials.phone-verification')
    <a class="mt-6 block text-brand-success" href="{{ route($administrative ? 'admin.verification.notice' : 'profile.edit') }}">Volver</a>
</x-layouts.auth>
