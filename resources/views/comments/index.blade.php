<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Comentarios - Plaza Local</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-brand-surface pb-24 text-brand-ink antialiased">
<x-market-nav :back-url="url()->previous()" />
<main class="mx-auto max-w-3xl px-4 py-7 sm:px-6">
<header class="rounded-[1.75rem] border border-brand/10 bg-white p-5 shadow-sm"><p class="text-xs font-black uppercase tracking-[.18em] text-brand-orange">Conversación de la comunidad</p><h1 class="mt-2 text-2xl font-black">Comentarios</h1><p class="mt-2 line-clamp-3 text-sm leading-6 text-brand-copy">{{ $post->body }}</p></header>
@if(session('status'))<p class="mt-4 rounded-2xl bg-brand-success-soft p-4 text-sm font-black text-brand-success">{{ session('status') }}</p>@endif
@auth @if($post->comments_enabled)<form class="mt-5 flex gap-2" method="POST" action="{{ route('posts.comments.store', $post) }}">@csrf<input class="min-w-0 flex-1 rounded-full border border-brand/10 bg-white px-4 py-3 outline-none" name="body" maxlength="1000" required placeholder="Escribe un comentario"><button class="rounded-full bg-brand px-5 py-3 text-sm font-black text-white">Publicar</button></form>@endif @endauth
<section class="mt-5 space-y-3">
@forelse($comments as $comment)
<article class="rounded-2xl border border-brand/10 bg-white p-4">
<div class="flex items-start gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-avatar-strong font-black">{{ mb_strtoupper(mb_substr($comment->user->name,0,1)) }}</span><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><strong class="text-sm">{{ $comment->user->name }}</strong><time class="text-xs text-brand-caption">{{ $comment->created_at->diffForHumans() }}</time>@if($comment->updated_at->gt($comment->created_at))<span class="text-xs font-bold text-brand-caption">Editado</span>@endif</div><p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-brand-copy">{{ $comment->body }}</p></div></div>
@auth
@if($comment->user_id === auth()->id())<details class="mt-3"><summary class="cursor-pointer text-xs font-black text-brand-success">Editar</summary><form class="mt-2 flex gap-2" method="POST" action="{{ route('posts.comments.update',$comment) }}">@csrf @method('PATCH')<input class="min-w-0 flex-1 rounded-xl border px-3 py-2 text-sm" name="body" value="{{ $comment->body }}" maxlength="1000" required><button class="rounded-xl bg-brand px-3 text-xs font-black text-white">Guardar</button></form></details>@endif
@if($comment->user_id === auth()->id() || $post->user_id === auth()->id() || auth()->user()->hasAnyRole(['admin','superadmin']))<form class="mt-2" method="POST" action="{{ route('posts.comments.destroy',$comment) }}">@csrf @method('DELETE')<button class="text-xs font-black text-red-600">Eliminar</button></form>@endif
@endauth
</article>
@empty
<div class="rounded-3xl border border-dashed bg-white/60 p-10 text-center font-bold text-brand-muted">Todavía no hay comentarios.</div>
@endforelse
</section>
@if($comments->hasPages())<div class="mt-6">{{ $comments->links() }}</div>@endif
</main>
<x-bottom-nav active="home" />
</body></html>