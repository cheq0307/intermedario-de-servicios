<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Empleo - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen overflow-x-hidden bg-[#FAF8F4] pb-24 text-[#17313A] antialiased">
<x-market-nav :back-url="auth()->check() ? route('more.index') : route('home')" :search-value="request('q','')" />
<main class="mx-auto w-full min-w-0 max-w-6xl px-4 py-6 sm:px-6">
    <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0"><p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Bolsa de trabajo local</p><h1 class="mt-2 text-3xl font-black">Vacantes de empleo</h1><p class="mt-2 max-w-2xl leading-6 text-[#536A72]">Empleo formal o temporal, separado de contratar un servicio puntual.</p></div>
        @auth
            @if(auth()->user()->canUseMarketplace())
                <div class="flex flex-wrap gap-2"><a class="inline-flex rounded-full bg-[#F97316] px-5 py-3 text-sm font-black text-white" href="{{ route('vacancies.create') }}">Publicar vacante</a><a class="inline-flex rounded-full bg-[#123B4A] px-5 py-3 text-sm font-black text-white" href="{{ route('vacancies.mine') }}">Mis vacantes y postulaciones</a></div>
            @endif
        @endauth
    </div>
    <form class="mt-6 grid min-w-0 gap-3 rounded-3xl border bg-white p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(13rem,18rem)_auto]" method="GET">
        <label class="min-w-0"><span class="sr-only">Puesto o palabra clave</span><input class="block w-full min-w-0 max-w-full rounded-2xl border bg-[#FAF8F4] px-4 py-3" name="q" value="{{ request('q') }}" placeholder="Puesto, actividad o palabra clave"></label>
        <label class="min-w-0"><span class="sr-only">Comunidad</span><select class="block w-full min-w-0 max-w-full rounded-2xl border bg-[#FAF8F4] px-4 py-3" name="community_id"><option value="">Todas las comunidades</option>@foreach($communities as $community)<option value="{{ $community->id }}" @selected(request('community_id')==$community->id)>{{ $community->display_label }}</option>@endforeach</select></label>
        <button class="w-full rounded-2xl bg-[#F97316] px-6 py-3 font-black text-white sm:col-span-2 lg:col-span-1 lg:w-auto">Buscar</button>
    </form>
    <div class="mt-6 grid min-w-0 gap-4 md:grid-cols-2">
        @forelse($vacancies as $vacancy)
            <a class="min-w-0 overflow-hidden rounded-3xl border bg-white p-5 shadow-sm" href="{{ route('vacancies.show',$vacancy) }}"><span class="rounded-full bg-[#FFF4D6] px-3 py-1 text-xs font-black text-[#8B5B00]">EMPLEO</span><h2 class="mt-3 break-words text-xl font-black">{{ $vacancy->title }}</h2><p class="mt-2 break-words font-bold text-[#536A72]">{{ $vacancy->employer->name }} · {{ $vacancy->community->name }}</p><p class="mt-3 line-clamp-2 break-words text-sm leading-6 text-[#536A72]">{{ $vacancy->description }}</p><div class="mt-4 flex flex-wrap gap-2 text-xs font-black"><span class="rounded-full bg-[#E9F7F0] px-3 py-1.5">{{ $vacancy->work_mode }}</span>@if($vacancy->salary_min_amount)<span class="rounded-full bg-[#FAF8F4] px-3 py-1.5">Desde MXN {{ number_format($vacancy->salary_min_amount/100,2) }}</span>@endif<span class="rounded-full bg-[#FAF8F4] px-3 py-1.5">{{ $vacancy->applications_count }} postulaciones</span></div></a>
        @empty
            <div class="rounded-3xl border border-dashed bg-white/60 p-10 text-center font-bold text-[#6B7D83] md:col-span-2">No hay vacantes que coincidan.</div>
        @endforelse
    </div>
    @if($vacancies->hasPages())<div class="mt-6">{{ $vacancies->links() }}</div>@endif
</main>
<x-bottom-nav active="home" />
</body></html>