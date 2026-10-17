@props(['vacancy'])

@php
    $workModes = ['onsite' => 'Presencial', 'hybrid' => 'Híbrido', 'remote' => 'Remoto'];
    $periods = ['hourly' => 'por hora', 'daily' => 'al día', 'weekly' => 'por semana', 'monthly' => 'al mes'];
@endphp

<a {{ $attributes->class(['group flex min-w-0 flex-col rounded-[1.4rem] bg-white p-4 shadow-market-card transition-transform hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange']) }} href="{{ route('vacancies.show', $vacancy) }}" data-vacancy-card>
    <div class="flex items-center justify-between gap-3">
        <span class="rounded-full bg-brand-success-soft px-2.5 py-1 text-[11px] font-black text-brand-success">Empleo</span>
        <span class="text-[11px] font-bold text-brand-muted">{{ $vacancy->applications_count }} postulaciones</span>
    </div>
    <h3 class="mt-3 line-clamp-2 text-lg font-black leading-6 text-brand">{{ $vacancy->title }}</h3>
    <p class="mt-2 truncate text-sm font-bold text-brand-copy">{{ $vacancy->employer->name }}</p>
    <p class="mt-1 truncate text-xs font-semibold text-brand-muted">{{ $vacancy->community->name }} · {{ $workModes[$vacancy->work_mode] ?? $vacancy->work_mode }}</p>
    <div class="mt-auto pt-4">
        @if($vacancy->salary_min_amount)
            <strong class="block text-sm text-brand-success">Desde MXN {{ number_format($vacancy->salary_min_amount / 100, 2) }} <span class="font-semibold text-brand-muted">{{ $periods[$vacancy->pay_period] ?? '' }}</span></strong>
        @else
            <strong class="block text-sm text-brand-success">Condiciones en la vacante</strong>
        @endif
    </div>
</a>
