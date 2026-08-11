<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Propuestas - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF8F4] text-[#17313A] antialiased">
    @php($statusLabels = ['pending' => 'Pendiente', 'accepted' => 'Aceptada', 'rejected' => 'Rechazada', 'withdrawn' => 'Retirada'])
    <header class="border-b border-[#123B4A]/10 bg-white"><div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-4"><a class="font-black" href="{{ route('dashboard') }}">Plaza Local</a><a class="rounded-full border border-[#123B4A]/10 px-4 py-2 text-sm font-black" href="{{ route('dashboard') }}">Volver al inicio</a></div></header>
    <main class="mx-auto max-w-5xl px-5 py-9">
        @if (session('status'))<div class="mb-6 rounded-2xl border border-[#22A06B]/20 bg-[#E9F7F0] px-5 py-4 text-sm font-black text-[#14734A]">{{ session('status') }}</div>@endif
        @error('proposal')<div class="mb-6 rounded-2xl bg-red-50 px-5 py-4 text-sm font-black text-red-700">{{ $message }}</div>@enderror

        <section class="rounded-[2rem] bg-[#123B4A] p-6 text-white sm:p-8">
            <p class="text-xs font-black uppercase tracking-[.18em] text-[#F9B36B]">Solicitud de trabajo</p>
            <h1 class="mt-3 text-3xl font-black">{{ $jobRequest->title }}</h1>
            <p class="mt-4 max-w-3xl whitespace-pre-line leading-7 text-white/70">{{ $jobRequest->description }}</p>
            <div class="mt-5 flex flex-wrap gap-2 text-xs font-black"><span class="rounded-full bg-white/10 px-3 py-2">{{ $jobRequest->location_label ?: 'Zona por confirmar' }}</span><span class="rounded-full bg-white/10 px-3 py-2">Estado: {{ str_replace('_', ' ', $jobRequest->status->value) }}</span></div>
        </section>

        @if ($isProvider && $jobRequest->client_id !== auth()->id())
            <section class="mt-6 rounded-[2rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-2xl font-black">{{ $ownProposal ? 'Tu propuesta' : 'Enviar propuesta' }}</h2>
                @if(auth()->user()->vendor?->status !== 'active')
                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-bold leading-6 text-amber-900">Tu perfil comercial está {{ auth()->user()->vendor?->status === 'suspended' ? 'suspendido' : 'pendiente de aprobación' }}. Un administrador debe aprobarlo antes de que puedas enviar propuestas.</div>
                @elseif (in_array($jobRequest->status->value, ['published', 'in_conversation'], true) && (! $ownProposal || $ownProposal->status->value === 'pending'))
                    <form class="mt-5 grid gap-5 sm:grid-cols-2" method="POST" action="{{ route('job-proposals.store', $jobRequest) }}">@csrf
                        <label><span class="text-sm font-black">Precio total en MXN</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" type="number" name="amount" value="{{ old('amount', $ownProposal ? $ownProposal->amount / 100 : '') }}" min="1" step="0.01" required>@error('amount')<span class="text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
                        <label><span class="text-sm font-black">Días estimados</span><input class="mt-2 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" type="number" name="estimated_days" value="{{ old('estimated_days', $ownProposal?->estimated_days) }}" min="1" max="365" required></label>
                        <label class="sm:col-span-2"><span class="text-sm font-black">Qué incluye tu propuesta</span><textarea class="mt-2 min-h-32 w-full rounded-2xl border border-[#123B4A]/10 bg-[#FAF8F4] px-4 py-3" name="message" minlength="20" maxlength="2000" required>{{ old('message', $ownProposal?->message) }}</textarea>@error('message')<span class="text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
                        <div class="flex items-center gap-3 sm:col-span-2"><button class="rounded-full bg-[#F97316] px-6 py-3 font-black text-white" type="submit">{{ $ownProposal ? 'Actualizar propuesta' : 'Enviar propuesta' }}</button>@if($ownProposal)<button class="rounded-full border border-red-200 px-5 py-3 text-sm font-black text-red-700" type="submit" form="withdraw-proposal">Retirar</button>@endif</div>
                    </form>
                    @if($ownProposal)<form id="withdraw-proposal" method="POST" action="{{ route('job-proposals.withdraw', [$jobRequest, $ownProposal]) }}">@csrf @method('PATCH')</form>@endif
                @elseif ($ownProposal)
                    <p class="mt-4 font-bold text-[#6B7D83]">Estado: {{ $statusLabels[$ownProposal->status->value] }}. Esta propuesta ya no puede modificarse.</p>
                    @if($ownProposal->order)<a class="mt-4 inline-block rounded-full bg-[#123B4A] px-5 py-3 text-sm font-black text-white" href="{{ route('orders.show', $ownProposal->order) }}">Ver contratación</a>@endif
                @else
                    <p class="mt-4 font-bold text-[#6B7D83]">Esta solicitud ya no está recibiendo propuestas.</p>
                @endif
            </section>
        @endif

        @if ($isOwner)
            <section class="mt-8"><p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Respuestas privadas</p><h2 class="mt-1 text-2xl font-black">Propuestas recibidas</h2>
                <div class="mt-5 space-y-4">
                    @forelse($proposals as $proposal)
                        <article class="rounded-[1.75rem] border border-[#123B4A]/10 bg-white p-6 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-4"><div><a class="text-lg font-black hover:text-[#F97316]" href="{{ route('profile.show', $proposal->provider) }}">{{ $proposal->provider->vendor?->display_name ?? $proposal->provider->name }}</a><p class="mt-1 text-sm font-bold text-[#6B7D83]">{{ $proposal->provider->vendor?->specialty ?: 'Proveedor local' }}</p></div><div class="text-right"><strong class="block text-xl">${{ number_format($proposal->amount / 100, 2) }} MXN</strong><span class="text-xs font-black text-[#6B7D83]">{{ $proposal->estimated_days }} días · {{ $statusLabels[$proposal->status->value] }}</span></div></div>
                            <p class="mt-5 whitespace-pre-line leading-7 text-[#536A72]">{{ $proposal->message }}</p>
                            @if($proposal->status->value === 'pending')<div class="mt-5 flex gap-3"><form method="POST" action="{{ route('job-proposals.accept', [$jobRequest, $proposal]) }}">@csrf @method('PATCH')<button class="rounded-full bg-[#14734A] px-5 py-2.5 text-sm font-black text-white" type="submit">Aceptar propuesta</button></form><form method="POST" action="{{ route('job-proposals.reject', [$jobRequest, $proposal]) }}">@csrf @method('PATCH')<button class="rounded-full border border-red-200 px-5 py-2.5 text-sm font-black text-red-700" type="submit">Rechazar</button></form></div>@endif
                            @if($proposal->order)<a class="mt-5 inline-block rounded-full bg-[#123B4A] px-5 py-2.5 text-sm font-black text-white" href="{{ route('orders.show', $proposal->order) }}">Ver contratación</a>@endif
                        </article>
                    @empty<div class="rounded-3xl border border-dashed border-[#123B4A]/20 p-10 text-center text-sm font-bold text-[#6B7D83]">Todavía no recibes propuestas.</div>@endforelse
                </div>
            </section>
        @endif
    </main>
</body>
</html>
