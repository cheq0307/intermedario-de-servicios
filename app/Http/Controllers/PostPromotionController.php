<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostPromotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PostPromotionController extends Controller
{
    public function index(Request $request): View
    {
        $promotions = PostPromotion::query()
            ->with(['post.listing', 'post.media', 'community'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);
        $eligiblePosts = Post::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('type', ['product', 'service', 'promotion'])
            ->whereNotNull('published_at')
            ->whereNull('removed_at')
            ->latest('published_at')
            ->get();

        return view('promotions.index', compact('promotions', 'eligiblePosts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'post_id' => [
                'required',
                Rule::exists('posts', 'id')->where(fn ($query) => $query
                    ->where('user_id', $request->user()->id)
                    ->whereIn('type', ['product', 'service', 'promotion'])
                    ->whereNull('removed_at')),
            ],
            'duration_days' => ['required', 'integer', Rule::in(array_keys(config('marketplace.promotion_prices', [])))],
        ]);

        $prices = config('marketplace.promotion_prices', []);
        $promotion = PostPromotion::create([
            'post_id' => $validated['post_id'],
            'user_id' => $request->user()->id,
            'community_id' => $request->user()->community_id,
            'status' => 'pending_payment',
            'duration_days' => $validated['duration_days'],
            'amount' => $prices[$validated['duration_days']],
            'currency' => 'MXN',
        ]);

        return back()->with('status', 'Promoción creada. Quedó pendiente de pago; todavía no se mostrará como patrocinada.');
    }
}
