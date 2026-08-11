<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $activeMode = $request->session()->get('marketplace_mode');
        if (! is_string($activeMode) || ! $user->supportsMarketplaceMode($activeMode)) {
            $activeMode = $user->defaultMarketplaceMode();
            $request->session()->put('marketplace_mode', $activeMode);
        }

        if ($activeMode === null && $user->hasAnyRole(['admin', 'superadmin'])) {
            return redirect()->route('admin.index');
        }

        $feed = (string) $request->query('feed', 'all');
        if (! in_array($feed, ['for_you', 'offers', 'requests', 'community', 'all'], true)) {
            $feed = 'all';
        }

        $providerPostTypes = ['portfolio', 'business_update', 'product', 'service', 'promotion'];
        $feedTypes = match ($feed) {
            'offers' => ['product', 'service', 'promotion'],
            'requests' => ['job_request'],
            'community' => ['portfolio', 'business_update'],
            'all' => null,
            default => match ($activeMode) {
                'client' => $providerPostTypes,
                'provider' => ['job_request'],
                default => null,
            },
        };

        $posts = Post::query()
            ->with(['user', 'listing', 'jobRequest', 'media', 'comments.user'])
            ->withCount(['reactions', 'comments', 'shares'])
            ->withExists(['reactions as reacted_by_user' => fn ($query) => $query->where('user_id', $user->id)])
            ->whereNotNull('published_at')
            ->whereHas('user')
            ->when($feedTypes !== null, function ($query) use ($feed, $feedTypes, $user): void {
                $query->where(function ($query) use ($feed, $feedTypes, $user): void {
                    $query->whereIn('type', $feedTypes);

                    if ($feed === 'for_you') {
                        $query->orWhere('user_id', $user->id);
                    }
                });
            })
            ->latest('published_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('dashboard', compact('posts', 'activeMode', 'feed'));
    }
}
