<x-layouts.auth title="Verificar acceso administrativo">
    <h1 class="text-2xl font-black">Confirma tu cuenta administrativa</h1>
    <p class="mt-3">{{ config('phone_verification.enabled') ? 'Para acceder, verifica tu correo y tu teléfono.' : 'Para acceder, verifica tu correo y guarda tu celular. La verificación por SMS está pausada.' }}</p>
    @if($identity->phone_verified_at)
        @if(session('status'))<p class="mt-3" role="status">{{ session('status') }}</p>@endif
        @foreach($errors->all() as $error)<p class="mt-3 text-red-700" role="alert">{{ $error }}</p>@endforeach
    @endif
    <p class="mt-4">{{ $identity->email }} · {{ $identity->hasVerifiedEmail() ? 'Correo verificado' : 'Correo pendiente' }}</p>
    @unless($identity->hasVerifiedEmail())
    <form class="mt-3" method="POST" action="{{ route('admin.verification.send') }}">@csrf<button class="min-h-11 rounded-xl border px-4">Enviar enlace al correo</button></form>
    @endunless
    @if($identity->phone_verified_at)<p class="mt-4 text-brand-success">Teléfono verificado</p>
    @else @include('auth.partials.phone-verification') @endif
    <form class="mt-6" method="POST" action="{{ route('admin.logout') }}">@csrf<button>Cerrar sesión administrativa</button></form>
</x-layouts.auth>
