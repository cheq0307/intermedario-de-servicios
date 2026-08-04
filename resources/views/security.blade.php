<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seguridad - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased">
    @php($user = auth()->user())
    <header class="border-b border-[#123B4A]/10 bg-white/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-5 py-4">
            <a class="flex items-center gap-3 font-black" href="{{ route('dashboard') }}">
                <span class="grid size-10 place-items-center rounded-2xl bg-[#123B4A] text-white">P</span>
                Plaza Local
            </a>
            <a class="rounded-full border border-[#123B4A]/10 px-4 py-2 text-sm font-black" href="{{ route('dashboard') }}">Volver al inicio</a>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-5 py-10">
        <div class="max-w-2xl">
            <p class="text-xs font-black uppercase tracking-[.2em] text-[#F97316]">Seguridad de la cuenta</p>
            <h1 class="mt-2 text-4xl font-black tracking-tight">Autenticación en dos pasos</h1>
            <p class="mt-4 leading-7 text-[#6B7D83]">Además de tu contraseña, solicitaremos un código temporal generado en tu teléfono cuando inicies sesión.</p>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]">Configuración de seguridad actualizada.</div>
        @endif

        <section class="mt-8 rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm sm:p-8">
            @if (! $user->two_factor_secret)
                <div class="flex items-start gap-4">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-[#FFF1E8] text-xl font-black text-[#F97316]">2</span>
                    <div>
                        <h2 class="text-xl font-black">La protección está desactivada</h2>
                        <p class="mt-2 leading-7 text-[#6B7D83]">Necesitarás una aplicación autenticadora. No compartas con nadie los códigos que genere.</p>
                    </div>
                </div>
                <form class="mt-6" method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button class="rounded-full bg-[#F97316] px-6 py-3 font-black text-white" type="submit">Activar autenticación en dos pasos</button>
                </form>
            @elseif (! $user->two_factor_confirmed_at)
                <p class="text-xs font-black uppercase tracking-[.16em] text-[#F97316]">Falta confirmar</p>
                <h2 class="mt-2 text-2xl font-black">Escanea este código QR</h2>
                <p class="mt-3 leading-7 text-[#6B7D83]">Abre tu aplicación autenticadora, agrega una cuenta y escanea el código. Después escribe el código de seis dígitos.</p>
                <div class="mt-6 inline-block overflow-hidden rounded-2xl border border-[#123B4A]/10 bg-white p-4">{!! $user->twoFactorQrCodeSvg() !!}</div>
                <details class="mt-4 rounded-2xl bg-[#FAF8F4] p-4">
                    <summary class="cursor-pointer text-sm font-black">No puedo escanear el QR</summary>
                    <p class="mt-3 break-all font-mono text-sm">{{ $user->twoFactorSecretKey() }}</p>
                </details>
                <form class="mt-6 max-w-sm space-y-4" method="POST" action="{{ route('two-factor.confirm') }}">
                    @csrf
                    <input class="w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-4 text-center text-xl font-black tracking-[.3em] outline-none focus:border-[#F97316]/50" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autocomplete="one-time-code" placeholder="000000">
                    @error('code') <span class="block text-sm font-bold text-red-600">{{ $message }}</span> @enderror
                    <button class="w-full rounded-2xl bg-[#123B4A] px-5 py-3.5 font-black text-white" type="submit">Confirmar y activar</button>
                </form>
            @else
                <div class="flex items-start gap-4">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-[#E9F7F0] text-xl font-black text-[#14734A]">✓</span>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-[#22A06B]">Protección activa</p>
                        <h2 class="mt-1 text-2xl font-black">Tu cuenta solicita un segundo paso</h2>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl bg-[#FAF8F4] p-5">
                    <h3 class="font-black">Códigos de recuperación</h3>
                    <p class="mt-2 text-sm leading-6 text-[#6B7D83]">Guárdalos en un lugar seguro. Cada código funciona una sola vez si pierdes acceso a tu teléfono.</p>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($user->recoveryCodes() as $recoveryCode)
                            <code class="rounded-xl bg-white px-3 py-2 text-center text-sm font-bold">{{ $recoveryCode }}</code>
                        @endforeach
                    </div>
                    <form class="mt-4" method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                        @csrf
                        <button class="text-sm font-black text-[#D85B0B]" type="submit">Generar códigos nuevos</button>
                    </form>
                </div>

                <form class="mt-6" method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-full border border-red-200 px-5 py-3 text-sm font-black text-red-700" type="submit">Desactivar segundo paso</button>
                </form>
            @endif
        </section>
    </main>
</body>
</html>
