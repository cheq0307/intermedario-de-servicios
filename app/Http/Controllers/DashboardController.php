<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $posts = Post::query()
            ->with(['user', 'listing', 'jobRequest'])
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->latest('id')
            ->paginate(12);

        return view('dashboard', compact('posts'));
    }
}
