<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Solicitar servicio - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-brand-cream pb-24 text-brand-body antialiased">
<x-market-nav :back-url="route('profile.show', $provider)" />
<main class="mx-auto max-w-2xl px-4 py-5 sm:py-8">
    <div class="mb-5">
        <p class="text-xs font-black uppercase tracking-[.16em] text-brand-orange">Acuerdo local</p>
        <h1 class="mt-1 text-2xl font-black text-brand-deep sm:text-3xl">Solicitar servicio a {{ $vendor->display_name }}</h1>
        <p class="mt-2 text-sm font-semibold leading-6 text-brand-muted-warm">Describe lo necesario. La persona recibirá la solicitud y podrá responder con una propuesta antes de que exista un trabajo.</p>
    </div>

    <form class="space-y-5 rounded-[1.75rem] border border-brand-line bg-white p-5 shadow-profile-card sm:p-7" method="POST" action="{{ route('posts.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}">
        <input type="hidden" name="type" value="job_request">
        <input type="hidden" name="preferred_provider_id" value="{{ $provider->id }}">

        <section class="flex items-center gap-3 rounded-2xl bg-brand-surface p-4">
            @if($provider->avatar_path)<img class="size-14 rounded-2xl object-cover" src="{{ $provider->avatarUrl() }}" alt="Foto de {{ $vendor->display_name }}">@else<span class="grid size-14 place-items-center rounded-2xl bg-brand-avatar text-xl font-black text-brand-deep">{{ mb_strtoupper(mb_substr($vendor->display_name, 0, 1)) }}</span>@endif
            <div class="min-w-0"><strong class="block truncate">{{ $vendor->display_name }}</strong><span class="mt-1 block text-xs font-bold text-brand-muted-warm">{{ $vendor->specialty ?: 'Servicio en tu comunidad' }}</span></div>
        </section>

        <label class="block"><span class="text-sm font-black">¿Qué servicio necesitas?</span><select class="mt-2 min-h-12 w-full rounded-2xl border border-brand-line bg-white px-4 font-semibold" name="category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
        <label class="block"><span class="text-sm font-black">Título breve</span><input class="mt-2 min-h-12 w-full rounded-2xl border border-brand-line px-4" name="title" value="{{ old('title') }}" minlength="5" maxlength="120" placeholder="Ej. Reparar una fuga en el baño" required>@error('title')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>
        <label class="block"><span class="text-sm font-black">Cuéntanos los detalles</span><textarea class="mt-2 min-h-32 w-full rounded-2xl border border-brand-line p-4" name="body" minlength="10" maxlength="1500" placeholder="Qué sucede, qué resultado esperas y cualquier detalle útil" required>{{ old('body') }}</textarea>@error('body')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</label>

        <fieldset><legend class="text-sm font-black">Urgencia</legend><div class="mt-2 grid grid-cols-3 gap-2">@foreach(['normal'=>'Flexible','soon'=>'Pronto','urgent'=>'Urgente'] as $value=>$label)<label class="cursor-pointer"><input class="peer sr-only" type="radio" name="urgency" value="{{ $value }}" @checked(old('urgency','normal')===$value)><span class="grid min-h-11 place-items-center rounded-full border border-brand-line px-2 text-xs font-black peer-checked:border-brand-coral peer-checked:bg-brand-coral-soft peer-checked:text-brand-danger-warm">{{ $label }}</span></label>@endforeach</div></fieldset>

        <div><span class="text-sm font-black">Fecha y horario</span><p class="mt-1 text-xs font-semibold text-brand-muted-warm">Opcional; podrán confirmarlo por mensaje.</p><input class="mt-2 min-h-12 w-full rounded-2xl border border-brand-line px-4" type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for') }}">@error('scheduled_for')<span class="mt-1 block text-sm font-bold text-red-600">{{ $message }}</span>@enderror</div>
        <div class="grid gap-3 sm:grid-cols-2"><label><span class="text-sm font-black">Presupuesto mínimo</span><input class="mt-2 min-h-12 w-full rounded-2xl border border-brand-line px-4" type="number" min="0" step="0.01" name="budget_min" value="{{ old('budget_min') }}" placeholder="$"></label><label><span class="text-sm font-black">Presupuesto máximo</span><input class="mt-2 min-h-12 w-full rounded-2xl border border-brand-line px-4" type="number" min="0" step="0.01" name="budget_max" value="{{ old('budget_max') }}" placeholder="$"></label></div>
        <label class="block"><span class="text-sm font-black">Lugar</span><input class="mt-2 min-h-12 w-full rounded-2xl border border-brand-line px-4" name="location_label" value="{{ old('location_label', auth()->user()->community?->public_location_label) }}" maxlength="120" placeholder="Colonia o referencia general"></label>
        <label class="block"><span class="text-sm font-black">Foto o video</span><input class="mt-2 block w-full rounded-2xl border border-dashed border-brand-line p-4 text-sm" type="file" name="media[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm"><span class="mt-1 block text-xs font-semibold text-brand-muted-warm">Opcional. No compartas documentos ni datos sensibles.</span></label>
        <button class="min-h-12 w-full rounded-full bg-brand-coral px-6 font-black text-white shadow-sm" type="submit">Enviar solicitud</button>
    </form>
</main>
<x-bottom-nav active="home" />
</body></html>
