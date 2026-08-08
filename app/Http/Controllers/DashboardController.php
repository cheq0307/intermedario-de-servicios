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
            ->with(['user', 'listing', 'jobRequest'])
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->latest('id')
            ->paginate(12);

        return view('dashboard', compact('posts', 'activeMode'));
    }
}
