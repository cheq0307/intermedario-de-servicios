<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $user->name }} - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#FAF8F4] pb-24 text-[#17313A] antialiased">
@php
$isOwner=auth()->id()===$user->id;
$vendor=$user->vendor;
$isProvider=$vendor?->status==='active';
$staffLabel=$isStaff ? ($user->hasRole('superadmin')?'Superadministrador':'Administrador') : null;
@endphp
<x-market-nav :back-url="url()->previous()" />
<main class="mx-auto max-w-6xl px-4 py-7 sm:px-6">
@if(session('status'))<div class="mb-5 rounded-2xl bg-[#E9F7F0] p-4 text-sm font-black text-[#14734A]">{{ session('status') }}</div>@endif
<section class="overflow-hidden rounded-[2rem] border border-[#123B4A]/10 bg-white shadow-sm">
<div class="h-32 bg-[linear-gradient(120deg,#123B4A,#1F6B4F_55%,#F2C66D)] sm:h-44"></div>
<div class="px-5 pb-7 sm:px-9">
<div class="-mt-12 flex items-end justify-between gap-3">
@if($user->avatar_path)<img class="size-28 rounded-[2rem] border-4 border-white object-cover shadow-lg" src="{{ asset('storage/'.$user->avatar_path) }}" alt="Foto de {{ $user->name }}">@else<span class="grid size-28 place-items-center rounded-[2rem] border-4 border-white bg-[#DCEAE6] text-4xl font-black shadow-lg">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</span>@endif
@if($isOwner)
<details class="relative mb-2"><summary class="grid size-11 cursor-pointer list-none place-items-center rounded-full border bg-white shadow-sm" aria-label="Configuración de mi cuenta"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg></summary>
<div class="absolute right-0 top-14 z-30 w-72 overflow-hidden rounded-2xl border bg-white p-2 shadow-2xl">
<a class="block rounded-xl px-4 py-3 font-black hover:bg-[#FAF8F4]" href="{{ route('profile.edit') }}">Editar perfil e intereses</a>
<a class="block rounded-xl px-4 py-3 font-black hover:bg-[#FAF8F4]" href="{{ route('support.index') }}">Ayuda y soporte</a>
@if(auth()->user()->hasAnyRole(['admin','superadmin']))<a class="block rounded-xl px-4 py-3 font-black hover:bg-[#FAF8F4]" href="{{ route('admin.index') }}">Administración</a>@endif
<form class="border-t pt-2" method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-xl px-4 py-3 text-left font-black text-red-600 hover:bg-red-50">Cerrar sesión</button></form>
</div>
</details>
@endif
</div>
<div class="mt-4">
<div class="flex flex-wrap items-center gap-2"><h1 class="text-3xl font-black text-[#123B4A]">{{ $isProvider ? ($vendor->display_name ?: $user->name) : $user->name }}</h1>@if($vendor?->verified_at)<span class="rounded-full bg-[#E9F7F0] px-3 py-1 text-xs font-black text-[#14734A]">✓ Verificado</span>@endif</div>
<span class="mt-2 inline-flex rounded-full border bg-[#FAF8F4] px-3 py-1 text-xs font-black">{{ $staffLabel ?: $user->commercialRoleLabel() }}</span>
<p class="mt-3 font-bold text-[#536A72]">@if($isStaff)Cuenta institucional de Plaza Local @else {{ $isProvider ? ($vendor->specialty ?: 'Ofrece productos o servicios') : 'Compra, solicita y participa en la comunidad' }} @endif @if($user->community) · {{ $user->community->display_label }} @endif</p>
@if($isOwner || auth()->user()?->hasAnyRole(['admin','superadmin']))<p class="mt-2 break-all text-sm font-bold text-[#314B54]">Correo: <span class="text-[#14734A]">{{ $user->email }}</span></p>@endif
@if($isStaff)<p class="mt-4 max-w-2xl leading-7 text-[#536A72]">Esta cuenta representa al equipo de administración. No publica ofertas, no contrata servicios y no recibe reseñas comerciales.</p>@else<p class="mt-4 max-w-2xl whitespace-pre-line leading-7 text-[#536A72]">{{ $vendor?->description ?: $user->bio ?: 'Esta persona todavía está completando su presentación.' }}</p>@endif
@if($isProvider)<div class="mt-4 flex flex-wrap gap-2">@if($vendor->availability_status==='busy')<span class="rounded-full bg-[#FFF4D6] px-3 py-1.5 text-xs font-black text-[#9A5A0A]">Realizando un trabajo</span>@elseif($vendor->availability_status==='available')<span class="rounded-full bg-[#E9F7F0] px-3 py-1.5 text-xs font-black text-[#14734A]">Disponible</span>@endif @if($vendor->businessHoursConfigured())<span class="rounded-full bg-[#FAF8F4] px-3 py-1.5 text-xs font-black">Horario: {{ collect($vendor->business_hours['days'])->map(fn($day)=>['monday'=>'Lun','tuesday'=>'Mar','wednesday'=>'Mié','thursday'=>'Jue','friday'=>'Vie','saturday'=>'Sáb','sunday'=>'Dom'][$day]??$day)->join(', ') }} · {{ $vendor->business_hours['opens_at'] }}–{{ $vendor->business_hours['closes_at'] }}</span>@endif</div>@endif
</div>
@if(!$isStaff)
<div class="mt-6 grid gap-3 sm:grid-cols-3">
    @if($isProvider)<div class="rounded-2xl border bg-[#FAF8F4] p-4"><span class="block text-xs font-black uppercase tracking-wide text-[#6B7D83]">Especialidad</span><strong class="mt-1 block">{{ $vendor->specialty ?: 'Por completar' }}</strong>@if($vendor->categories->isNotEmpty())<span class="mt-1 block text-xs font-semibold text-[#6B7D83]">{{ $vendor->categories->pluck('name')->join(', ') }}</span>@endif</div>@endif
    <div class="rounded-2xl border bg-[#FAF8F4] p-4"><span class="block text-xs font-black uppercase tracking-wide text-[#6B7D83]">Cobertura</span><strong class="mt-1 block">{{ $user->community?->display_label ?: 'Por definir' }}</strong>@if($isProvider && $vendor->service_area)<span class="mt-1 block text-xs font-semibold text-[#6B7D83]">{{ $vendor->service_area }}</span>@endif</div>
    <div class="rounded-2xl border bg-[#FAF8F4] p-4"><span class="block text-xs font-black uppercase tracking-wide text-[#6B7D83]">Confianza verificada</span><strong class="mt-1 block">{{ $rating ? number_format($rating,1).' de 5' : 'Sin calificación' }}</strong><span class="mt-1 block text-xs font-semibold text-[#6B7D83]">{{ $completedOrdersCount }} trabajos · {{ $reviewsCount }} reseñas</span></div>
</div>
<div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl border border-[#123B4A]/8 bg-white px-4 py-3 text-xs font-bold text-[#6B7D83]" aria-label="Actividad social"><span><strong class="text-[#17313A]">{{ $socialMetrics['followers'] }}</strong> Seguidores</span><span><strong class="text-[#17313A]">{{ $socialMetrics['likes'] }}</strong> Me gusta</span><span><strong class="text-[#17313A]">{{ $socialMetrics['comments'] }}</strong> Comentarios</span><span><strong class="text-[#17313A]">{{ $socialMetrics['shares'] }}</strong> Compartidos</span></div>
@endif
@if(!$isOwner)
<div class="mt-6 rounded-2xl border border-[#F97316]/15 bg-[#FFF8F2] p-4">
@if(!$isStaff)
    @guest
        <a class="mb-3 inline-flex rounded-full border border-[#123B4A] px-6 py-3 font-black text-[#123B4A]" href="{{ route('login') }}">Inicia sesión para seguir</a>
    @else
        <form class="mb-3" method="POST" action="{{ route('profiles.follow.toggle', $user) }}">@csrf<button class="rounded-full {{ $isFollowing ? 'border border-[#123B4A] bg-white text-[#123B4A]' : 'bg-[#F97316] text-white' }} px-6 py-3 font-black">{{ $isFollowing ? 'Siguiendo · dejar de seguir' : 'Seguir' }}</button></form>
    @endguest
@endif
@if($isStaff)<a class="inline-flex rounded-full bg-[#123B4A] px-6 py-3 font-black text-white" href="{{ route('support.create') }}">Contactar soporte oficial</a>@guest<p class="mt-2 text-xs font-bold text-[#8A6A55]">Inicia sesión para abrir un caso.</p>@endguest @else @guest<a class="inline-flex rounded-full bg-[#123B4A] px-6 py-3 font-black text-white" href="{{ route('register') }}">Regístrate para contactar</a>@else<form method="POST" action="{{ route('conversations.start') }}">@csrf<input type="hidden" name="recipient_id" value="{{ $user->id }}"><button class="rounded-full bg-[#123B4A] px-6 py-3 font-black text-white">Contactar dentro de Plaza Local</button></form>@endguest @endif</div>
@endif
</div>
</section>

@if(!$isStaff)
<nav class="mt-7 flex gap-2 overflow-x-auto pb-2" aria-label="Secciones del perfil">
@foreach(['offers'=>'Lo que ofrece','needs'=>'Lo que necesita','work'=>'Trabajos realizados','reviews'=>'Reseñas'] as $key=>$label)<a class="shrink-0 rounded-full px-5 py-2.5 text-sm font-black {{ $tab===$key?'bg-[#123B4A] text-white':'border bg-white' }}" href="{{ route('profile.show',[$user,'tab'=>$key]) }}">{{ $label }}</a>@endforeach
</nav>
<section class="mt-3">
@if(in_array($tab,['offers','needs']))
<div class="grid gap-4 md:grid-cols-2">
@forelse($posts as $post)<article class="overflow-hidden rounded-3xl border bg-white shadow-sm">@if($post->media->firstWhere('type','image'))<img class="h-48 w-full object-cover" src="{{ $post->media->firstWhere('type','image')->url }}" alt="">@endif<div class="p-5"><span class="rounded-full px-3 py-1 text-xs font-black {{ $post->listing?'bg-[#E9F7F0] text-[#14734A]':'bg-[#FFF1E8] text-[#D85B0B]' }}">{{ $post->listing ? ($post->listing->type->value==='product'?'PRODUCTO':'SERVICIO') : 'SOLICITUD' }}</span><h2 class="mt-3 text-lg font-black">{{ $post->listing?->name ?? $post->jobRequest?->title }}</h2><p class="mt-2 line-clamp-3 text-sm leading-6 text-[#536A72]">{{ $post->body }}</p></div></article>@empty<div class="rounded-3xl border border-dashed bg-white/60 p-10 text-center font-bold text-[#6B7D83] md:col-span-2">No hay contenido en esta sección.</div>@endforelse
</div>
@if($posts->hasPages())<div class="mt-6">{{ $posts->links() }}</div>@endif
@elseif($tab==='work')
<div class="grid gap-4 md:grid-cols-2">@forelse($completedOrders as $order)<article class="overflow-hidden rounded-3xl border bg-white shadow-sm">@if($order->jobRequest?->post?->media?->firstWhere('type','image'))<img class="h-48 w-full object-cover" src="{{ $order->jobRequest->post->media->firstWhere('type','image')->url }}" alt="">@endif<div class="p-5"><span class="rounded-full bg-[#E9F7F0] px-3 py-1 text-xs font-black text-[#14734A]">✓ Trabajo verificado</span><h2 class="mt-3 text-lg font-black">{{ $order->jobRequest?->title ?? $order->items->first()?->name_snapshot ?? 'Trabajo completado' }}</h2><p class="mt-2 text-sm font-bold text-[#536A72]">Completado {{ $order->completed_at?->diffForHumans() }}</p></div></article>@empty<div class="rounded-3xl border border-dashed bg-white/60 p-10 text-center font-bold text-[#6B7D83] md:col-span-2">Todavía no hay trabajos completados dentro de la plataforma.</div>@endforelse</div>
@else
<div class="space-y-3">@forelse($reviews as $review)<article class="rounded-2xl border bg-white p-5"><div class="flex items-center justify-between gap-3"><strong>{{ $review->author->name }}</strong><span class="font-black text-[#E6A700]">{{ str_repeat('★',$review->rating) }}</span></div>@if($review->comment)<p class="mt-3 leading-7 text-[#536A72]">{{ $review->comment }}</p>@endif@if($review->updated_at->gt($review->created_at))<span class="mt-2 block text-xs font-bold text-[#8A999E]">Editada</span>@endif</article>@empty<div class="rounded-3xl border border-dashed bg-white/60 p-10 text-center font-bold text-[#6B7D83]">Todavía no hay reseñas verificadas.</div>@endforelse</div>
@endif
</section>
@else
<section class="mt-7 rounded-[2rem] border bg-white p-7"><p class="text-xs font-black uppercase tracking-[.18em] text-[#F97316]">Cuenta institucional</p><h2 class="mt-2 text-2xl font-black">{{ $staffLabel }}</h2><p class="mt-3 max-w-2xl leading-7 text-[#536A72]">Su función es moderar, resolver incidencias y proteger la operación de las comunidades. Para atención, utiliza el canal de soporte oficial.</p></section>
@endif
</main>
<x-bottom-nav active="more" />
</body></html>