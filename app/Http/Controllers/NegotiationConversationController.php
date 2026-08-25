<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Post;
use App\Notifications\MarketplaceActivity;
use App\Services\Marketplace\NegotiationConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NegotiationConversationController extends Controller
{
    public function start(Request $request, Post $post, NegotiationConversationService $service): RedirectResponse
    {
        abort_if($post->user_id === $request->user()->id, 403, 'No puedes abrir una negociación contigo mismo.');
        abort_unless($post->published_at && ! $post->removed_at && $post->user, 404);

        $conversation = $service->start($post, $request->user());

        if ($conversation->wasRecentlyCreated) {
            $post->user->notify(new MarketplaceActivity(
                'Nueva conversación sobre tu publicación',
                $request->user()->name.' quiere hablar sobre “'.$service->postTitle($post).'”.',
                'conversations.show',
                ['conversation' => $conversation->public_id],
                'conversation_started',
            ));
        }

        return redirect()->route('conversations.show', $conversation);
    }

    public function extend(Request $request, Conversation $conversation, NegotiationConversationService $service): RedirectResponse
    {
        abort_unless($conversation->includesUser($request->user()), 403);
        $service->extend($conversation, $request->user());

        return back()->with('status', 'La conversación seguirá abierta siete días más.');
    }

    public function close(Request $request, Conversation $conversation, NegotiationConversationService $service): RedirectResponse
    {
        abort_unless($conversation->includesUser($request->user()), 403);
        $service->close($conversation, $request->user());

        return back()->with('status', 'La conversación terminó y ahora es de solo lectura.');
    }
}
