<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Notifications\MarketplaceActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostEngagementController extends Controller
{
    public function comments(Post $post): View
    {
        abort_if($post->removed_at !== null, 404);
        $post->load('user:id,name');
        $comments = $post->comments()->with('user:id,name,avatar_path,avatar_disk')->paginate(20);

        return view('comments.index', compact('post', 'comments'));
    }

    public function toggleReaction(Request $request, Post $post): RedirectResponse
    {
        abort_if($post->removed_at !== null, 404);
        $reaction = $post->reactions()->where('user_id', $request->user()->id)->first();
        if ($reaction) {
            $reaction->delete();
            $message = 'Ya no te gusta esta publicación.';
        } else {
            $post->reactions()->create(['user_id' => $request->user()->id, 'type' => 'like']);
            $message = 'Marcaste esta publicación con Me gusta.';
            $this->notifyOwner($request, $post, 'Nuevo Me gusta', $request->user()->name.' indicó que le gusta tu publicación.', 'social_like');
        }

        return back()->withFragment('post-'.$post->id)->with('status', $message);
    }

    public function comment(Request $request, Post $post): RedirectResponse
    {
        abort_if($post->removed_at !== null, 404);
        abort_unless($post->comments_enabled, 403);
        $validated = $request->validate(['body' => ['required', 'string', 'min:2', 'max:1000']]);
        $post->comments()->create(['user_id' => $request->user()->id, 'body' => $validated['body']]);
        $this->notifyOwner($request, $post, 'Nuevo comentario', $request->user()->name.' comentó tu publicación.', 'social_comment', 'posts.comments.index');

        return back()->withFragment('post-'.$post->id)->with('status', 'Comentario publicado.');
    }

    public function updateComment(Request $request, PostComment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);
        $validated = $request->validate(['body' => ['required', 'string', 'min:2', 'max:1000']]);
        $comment->update(['body' => $validated['body']]);

        return back()->with('status', 'Comentario actualizado.');
    }

    public function deleteComment(Request $request, PostComment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id || $comment->post->user_id === $request->user()->id || $request->user()->hasAnyRole(['admin', 'superadmin']), 403);
        $postId = $comment->post_id;
        $comment->delete();

        return back()->withFragment('post-'.$postId)->with('status', 'Comentario eliminado.');
    }

    public function share(Request $request, Post $post): RedirectResponse
    {
        abort_if($post->removed_at !== null, 404);
        $validated = $request->validate(['channel' => ['nullable', 'string', 'in:native,clipboard,whatsapp,facebook']]);
        $post->shares()->create(['user_id' => $request->user()->id, 'channel' => $validated['channel'] ?? 'native']);
        $this->notifyOwner($request, $post, 'Publicación compartida', $request->user()->name.' compartió tu publicación.', 'social_share');

        return back()->withFragment('post-'.$post->id);
    }

    private function notifyOwner(Request $request, Post $post, string $title, string $body, string $kind, string $routeName = 'dashboard'): void
    {
        if ($post->user_id === $request->user()->id) {
            return;
        }

        $post->user->notify(new MarketplaceActivity(
            $title,
            $body,
            $routeName,
            $routeName === 'posts.comments.index' ? ['post' => $post->id] : [],
            $kind,
        ));
    }
}
