<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $user->name }} - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#F7F3EA] pb-24 text-[#16241F] antialiased">
@php
$isOwner=auth()->id()===$user->id;
$vendor=$user->vendor;
$isProvider=$vendor?->status==='active';
$staffLabel=$isStaff ? ($user->hasRole('superadmin')?'Superadministrador':'Administrador') : null;
@endphp
<x-market-nav :back-url="url()->previous()" />
<main class="mx-auto max-w-3xl pb-8 sm:px-4 sm:py-5">
@if(session('status'))<div class="mx-4 mb-4 mt-4 rounded-2xl border border-[#1F6F5C]/15 bg-[#E9F7F0] p-4 text-sm font-black text-[#14734A] sm:mx-0 sm:mt-0">{{ session('status') }}</div>@endif
<section class="border-b border-[#E7E1D2] bg-white sm:overflow-hidden sm:rounded-[1.75rem] sm:border sm:shadow-[0_10px_35px_rgba(15,61,62,.08)]">
<div class="h-44 bg-[linear-gradient(115deg,#0F3D3E_0%,#1F6F5C_48%,#E3B23C_100%)] sm:h-56"></div>
<div class="px-5 pb-7 sm:px-7">
<div class="-mt-12 flex items-end justify-between gap-3">
@if($user->avatar_path)<img class="size-24 rounded-full border-4 border-white object-cover shadow-lg ring-2 {{ $vendor?->verified_at?'ring-[#E8734D]':'ring-[#E7E1D2]' }} sm:size-28" src="{{ $user->avatarUrl() }}" alt="Foto de {{ $user->name }}">@else<span class="grid size-24 place-items-center rounded-full border-4 border-white bg-[#E4EEEA] text-3xl font-black text-[#0F3D3E] shadow-lg ring-2 {{ $vendor?->verified_at?'ring-[#E8734D]':'ring-[#E7E1D2]' }} sm:size-28 sm:text-4xl">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</span>@endif
@if($isOwner)
<details class="relative mb-2"><summary class="grid size-11 cursor-pointer list-none place-items-center rounded-full border bg-white shadow-sm" aria-label="Configuración de mi cuenta"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg></summary>
<div class="absolute right-0 top-14 z-30 w-72 overflow-hidden rounded-2xl border bg-white p-2 shadow-2xl">
<a class="block rounded-xl px-4 py-3 font-black hover:bg-[#FAF8F4]" href="{{ route('profile.edit') }}">Editar perfil e intereses</a>
<a class="block rounded-xl px-4 py-3 font-black hover:bg-[#FAF8F4]" href="{{ route('support.index') }}">Ayuda y soporte</a>
@if(auth()->user()->hasAnyRole(['admin','superadmin']))<a class="block rounded-xl px-4 py-3 font-black hover:bg-[#FAF8F4]" href="{{ route('admin.index') }}">Administración</a>@endif
<form class="border-t pt-2" method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-xl px-4 py-3 text-left font-black text-red-600 hover:bg-red-50">Cerrar sesión</button></form>
</div>
</details>
@elseif($administrativePreview)
<a class="mb-2 inline-flex min-h-11 items-center rounded-full bg-[#0F3D3E] px-5 text-xs font-black text-white sm:text-sm" href="{{ route('admin.users.show', $user) }}">Administrar</a>
@elseif($isStaff)
<a class="mb-2 inline-flex min-h-11 items-center rounded-full bg-[#0F3D3E] px-4 text-xs font-black text-white sm:px-5 sm:text-sm" href="{{ route('support.create') }}">Contactar soporte</a>
@else
<div class="mb-2 flex items-center gap-2">
    @guest
        <a class="hidden min-h-11 items-center rounded-full border border-[#E7E1D2] bg-white px-4 text-xs font-black text-[#0F3D3E] sm:inline-flex" href="{{ route('login') }}">Iniciar sesión</a>
        <a class="inline-flex min-h-11 items-center rounded-full bg-[#E8734D] px-4 text-xs font-black text-white sm:px-5" href="{{ route('register') }}">Regístrate para contactar</a>
    @else
        <form method="POST" action="{{ route('conversations.start') }}">@csrf<input type="hidden" name="recipient_id" value="{{ $user->id }}"><button class="min-h-11 rounded-full border border-[#E7E1D2] bg-white px-4 text-xs font-black text-[#0F3D3E] sm:px-5 sm:text-sm">Mensaje</button></form>
        <form method="POST" action="{{ route('profiles.follow.toggle', $user) }}">@csrf<button class="min-h-11 rounded-full px-4 text-xs font-black sm:px-5 sm:text-sm {{ $isFollowing ? 'border border-[#0F3D3E] bg-white text-[#0F3D3E]' : 'bg-[#E8734D] text-white' }}">{{ $isFollowing ? 'Siguiendo' : 'Seguir' }}</button></form>
    @endguest
</div>
@endif
</div>
<div class="mt-4">
<div class="flex flex-wrap items-center gap-2"><h1 class="break-words text-2xl font-black capitalize leading-tight text-[#0F3D3E] sm:text-[1.75rem]">{{ !$isStaff && $isProvider ? ($vendor->display_name ?: $user->name) : $user->name }}</h1>@if(!$isStaff && $vendor?->verified_at)<span class="grid size-5 place-items-center rounded-full bg-[#E8734D] text-xs font-black text-white" title="Identidad verificada">✓</span>@endif</div>
<span class="mt-3 inline-flex rounded-full border border-[#E7E1D2] bg-[#F7F3EA] px-3 py-1 text-xs font-black text-[#0F3D3E]">{{ $staffLabel ?: $user->commercialRoleLabel() }}</span>
<p class="mt-3 flex items-start gap-2 font-semibold text-[#6B7A72]"><svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-6.1 7-11.2A7 7 0 1 0 5 9.8C5 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/></svg><span>@if($isStaff)Cuenta institucional de Plaza Local @else {{ $isProvider ? 'Ofrece productos o servicios' : 'Compra, solicita y participa en la comunidad' }} @endif @if($user->community) · {{ $user->community->display_label }} @endif</span></p>
@if($isOwner || auth()->user()?->hasAnyRole(['admin','superadmin']))<p class="mt-2 break-all text-sm font-bold text-[#314B54]">Correo: <span class="text-[#14734A]">{{ $user->email }}</span></p>@endif
@if($isStaff)<p class="mt-4 max-w-2xl leading-7 text-[#536A72]">Esta cuenta representa al equipo de administración. No publica ofertas, no contrata servicios y no recibe reseñas comerciales.</p>@else<p class="mt-4 max-w-2xl whitespace-pre-line leading-7 text-[#536A72]">{{ $vendor?->description ?: $user->bio ?: 'Esta persona todavía está completando su presentación.' }}</p>@endif
@if(!$isStaff && $isProvider)<div class="mt-4 flex flex-wrap gap-2">@if($vendor->specialty)<span class="rounded-full border border-[#E7E1D2] bg-white px-3 py-1.5 text-xs font-black text-[#0F3D3E]">{{ $vendor->specialty }}</span>@endif @foreach($vendor->categories as $category)<span class="rounded-full border border-[#E7E1D2] bg-[#F7F3EA] px-3 py-1.5 text-xs font-bold text-[#536A72]">{{ $category->name }}</span>@endforeach @if($vendor->availability_status==='busy')<span class="rounded-full bg-[#FFF4D6] px-3 py-1.5 text-xs font-black text-[#9A5A0A]">Realizando un trabajo</span>@elseif($vendor->availability_status==='available')<span class="rounded-full bg-[#E9F7F0] px-3 py-1.5 text-xs font-black text-[#14734A]">Disponible</span>@endif @if($vendor->businessHoursConfigured())<span class="rounded-full border border-[#E7E1D2] bg-white px-3 py-1.5 text-xs font-bold text-[#536A72]">Horario: {{ collect($vendor->business_hours['days'])->map(fn($day)=>['monday'=>'Lun','tuesday'=>'Mar','wednesday'=>'Mié','thursday'=>'Jue','friday'=>'Vie','saturday'=>'Sáb','sunday'=>'Dom'][$day]??$day)->join(', ') }} · {{ $vendor->business_hours['opens_at'] }}–{{ $vendor->business_hours['closes_at'] }}</span>@endif</div>@endif
</div>
@if(!$isStaff)
<div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm" aria-label="Actividad social"><span><strong>{{ $socialMetrics['followers'] }}</strong> <span class="text-[#6B7A72]">Seguidores</span></span><span><strong>{{ $socialMetrics['likes'] }}</strong> <span class="text-[#6B7A72]">Me gusta</span></span><span><strong>{{ $socialMetrics['comments'] }}</strong> <span class="text-[#6B7A72]">Comentarios</span></span><span><strong>{{ $socialMetrics['shares'] }}</strong> <span class="text-[#6B7A72]">Compartidos</span></span></div>
<div class="mt-5 grid grid-cols-2 gap-3">
    <div class="rounded-2xl border border-[#E7E1D2] bg-white p-4"><span class="block text-[10px] font-black uppercase tracking-[.12em] text-[#6B7A72]">Cobertura</span><strong class="mt-1 block text-sm leading-5">{{ $user->community?->display_label ?: 'Por definir' }}</strong>@if($isProvider && $vendor->service_area)<span class="mt-1 block text-xs font-semibold text-[#6B7A72]">{{ $vendor->service_area }}</span>@endif</div>
    <div class="rounded-2xl border border-[#E7E1D2] bg-white p-4"><span class="block text-[10px] font-black uppercase tracking-[.12em] text-[#6B7A72]">Confianza verificada</span><strong class="mt-1 block text-sm leading-5">{{ $rating ? number_format($rating,1).' de 5' : 'Sin calificación' }}</strong><span class="mt-1 block text-xs font-semibold text-[#6B7A72]">{{ $completedOrdersCount }} trabajos · {{ $reviewsCount }} reseñas</span></div>
</div>
@endif
@if($administrativePreview)
<div class="mt-5 flex flex-col gap-3 rounded-2xl border border-[#F0CBA9] bg-[#FCEEE3] p-4 sm:flex-row sm:items-center sm:justify-between">
    <div><strong class="text-[#0F3D3E]">Vista administrativa de solo lectura</strong><p class="mt-1 text-sm font-semibold text-[#6B7A72]">Las cuentas administrativas no siguen, contactan ni generan actividad comercial.</p></div>
    <a class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-full bg-[#E8734D] px-5 text-sm font-black text-white" href="{{ route('admin.users.show', $user) }}">Administrar cuenta</a>
</div>
@endif
</div>
</section>

@if(!$isStaff)
<nav class="mt-4 flex overflow-x-auto border-b border-[#E7E1D2] px-3 sm:mt-6 sm:px-0" aria-label="Secciones del perfil">
@foreach(['offers'=>'Lo que ofrece','needs'=>'Lo que necesita','work'=>'Trabajos realizados','reviews'=>'Reseñas'] as $key=>$label)<a class="shrink-0 border-b-[3px] px-4 py-3 text-center text-xs font-black sm:text-sm {{ $tab===$key?'border-[#E8734D] text-[#0F3D3E]':'border-transparent text-[#6B7A72]' }}" href="{{ route('profile.show',[$user,'tab'=>$key]) }}" @if($tab===$key) aria-current="page" @endif>{{ $label }}</a>@endforeach
</nav>
<section class="px-4 pt-4 sm:px-0">
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
