<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostEngagementController extends Controller
{
    public function toggleReaction(Request $request, Post $post): RedirectResponse
    {
        $reaction = $post->reactions()->where('user_id', $request->user()->id)->first();

        if ($reaction) {
            $reaction->delete();
            $message = 'Ya no te gusta esta publicación.';
        } else {
            $post->reactions()->create(['user_id' => $request->user()->id, 'type' => 'like']);
            $message = 'Marcaste esta publicación con Me gusta.';
        }

        return back()->withFragment('post-'.$post->id)->with('status', $message);
    }

    public function comment(Request $request, Post $post): RedirectResponse
    {
        abort_unless($post->comments_enabled, 403);
        $validated = $request->validate(['body' => ['required', 'string', 'min:2', 'max:1000']]);
        $post->comments()->create(['user_id' => $request->user()->id, 'body' => $validated['body']]);

        return back()->withFragment('post-'.$post->id)->with('status', 'Comentario publicado.');
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
        $validated = $request->validate(['channel' => ['nullable', 'string', 'in:native,clipboard,whatsapp,facebook']]);
        $post->shares()->create(['user_id' => $request->user()->id, 'channel' => $validated['channel'] ?? 'native']);

        return back()->withFragment('post-'.$post->id);
    }
}
