@foreach($comments as $comment)
    <article class="mb-3 rounded-2xl bg-white px-4 py-3" data-comment-id="{{ $comment->id }}">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <strong class="text-sm">{{ $comment->user->name }}</strong>
                    <time class="text-[11px] text-brand-caption">{{ $comment->created_at->diffForHumans() }}</time>
                </div>
                <p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-brand-copy">{{ $comment->body }}</p>
                @if($comment->updated_at->gt($comment->created_at))<span class="text-[10px] font-bold text-brand-caption">Editado</span>@endif
            </div>
            @auth
                @if($comment->user_id === auth()->id() || $post->user_id === auth()->id() || auth()->user()->hasAnyRole(['admin','superadmin']))
                    <form method="POST" action="{{ route('posts.comments.destroy', $comment) }}">@csrf @method('DELETE')<button class="text-xs font-black text-red-600" type="submit">Eliminar</button></form>
                @endif
            @endauth
        </div>
        @auth
            @if($comment->user_id === auth()->id())
                <details class="mt-2">
                    <summary class="cursor-pointer text-xs font-black text-brand-success">Editar</summary>
                    <form class="mt-2 flex gap-2" method="POST" action="{{ route('posts.comments.update', $comment) }}">@csrf @method('PATCH')<input class="min-w-0 flex-1 rounded-xl border border-brand/10 px-3 py-2 text-sm" name="body" value="{{ $comment->body }}" minlength="2" maxlength="1000" required><button class="rounded-xl bg-brand px-3 text-xs font-black text-white" type="submit">Guardar</button></form>
                </details>
            @endif
        @endauth
    </article>
@endforeach
