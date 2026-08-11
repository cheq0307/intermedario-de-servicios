<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $activeMode = $request->session()->get('marketplace_mode');
        if (! is_string($activeMode) || ! $user->supportsMarketplaceMode($activeMode)) {
            $activeMode = $user->defaultMarketplaceMode();
            $request->session()->put('marketplace_mode', $activeMode);
        }

        $posts = Post::query()
            ->with(['user', 'listing', 'jobRequest', 'media', 'comments.user'])
            ->withCount(['reactions', 'comments', 'shares'])
            ->withExists(['reactions as reacted_by_user' => fn ($query) => $query->where('user_id', $user->id)])
            ->whereNotNull('published_at')
            ->whereHas('user')
            ->latest('published_at')
            ->latest('id')
            ->paginate(12);

        return view('dashboard', compact('posts', 'activeMode'));
    }
}
