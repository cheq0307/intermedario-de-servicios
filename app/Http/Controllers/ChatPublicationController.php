<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatPublicationController extends Controller
{
    public function __invoke(Request $request, Conversation $conversation)
    {
        Gate::forUser($request->user())->authorize('view', $conversation);
        $post = $conversation->post;
        abort_unless($post && $post->published_at && ! $post->removed_at, 404);
        $post->load(['listing', 'jobRequest', 'media', 'user']);
        $title = $post->listing?->name ?? $post->jobRequest?->title ?? 'Publicación';

        return view('conversations.publication', compact('conversation', 'post', 'title'));
    }
}
