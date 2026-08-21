<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Community;
use App\Models\Listing;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('superadmin')) {
            $request->session()->forget('marketplace_mode');

            return redirect()->route('admin.index');
        }

        if (! $user->canUseMarketplace() && $user->hasAnyRole(['admin', 'superadmin'])) {
            return redirect()->route('admin.index');
        }

        $publishAs = (string) $request->query('publicar', '');
        $showComposer = in_array($publishAs, ['request', 'offer'], true);
        $activeMode = $publishAs === 'offer' ? 'provider' : 'client';

        $module = (string) $request->query('module', 'services');
        if (! in_array($module, ['food', 'services', 'products', 'transport'], true)) {
            $module = 'services';
        }

        $feed = (string) $request->query('feed', 'for_you');
        if (! in_array($feed, ['for_you', 'offers', 'requests', 'community', 'all'], true)) {
            $feed = 'for_you';
        }

        $feedTypes = match ($feed) {
            'offers' => ['product', 'service', 'promotion'],
            'requests' => ['job_request'],
            'community' => ['portfolio', 'business_update'],
            'all' => null,
            default => null,
        };

        $preferredCategoryIds = $user->categoryPreferences()
            ->orderByRaw('(interest_score + behavior_score) desc')
            ->limit(20)
            ->pluck('categories.id')
            ->all();

        $showcaseListings = Listing::query()
            ->with(['category', 'vendor.user.community', 'post.media', 'post.activePromotion'])
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->whereHas('vendor', fn ($query) => $query->where('status', 'active'))
            ->whereHas('post', fn ($query) => $query->whereNotNull('published_at')->whereNull('removed_at'))
            ->when($module === 'food', fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', 'comida-bebidas')))
            ->when($module === 'transport', fn ($query) => $query->whereHas('category', fn ($category) => $category->where('slug', 'transporte-taxi')))
            ->when($module === 'products', fn ($query) => $query->where('type', 'product'))
            ->when($module === 'services', fn ($query) => $query
                ->where('type', 'service')
                ->whereHas('category', fn ($category) => $category->whereNotIn('slug', ['comida-bebidas', 'transporte-taxi'])))
            ->orderByRaw('CASE WHEN EXISTS (SELECT 1 FROM post_promotions WHERE post_promotions.post_id = (SELECT posts.id FROM posts WHERE posts.listing_id = listings.id LIMIT 1) AND post_promotions.status = ? AND post_promotions.starts_at <= ? AND post_promotions.ends_at > ?) THEN 0 ELSE 1 END', ['active', now(), now()])
            ->when($preferredCategoryIds !== [], fn ($query) => $query->orderByRaw(
                'CASE WHEN listings.category_id IN ('.implode(',', array_map('intval', $preferredCategoryIds)).') THEN 0 ELSE 1 END'
            ))
            ->when($user->community_id, fn ($query) => $query->orderByRaw(
                'CASE WHEN EXISTS (SELECT 1 FROM vendors INNER JOIN users ON users.id = vendors.user_id WHERE vendors.id = listings.vendor_id AND users.community_id = ?) THEN 0 ELSE 1 END',
                [$user->community_id]
            ))
            ->inRandomOrder()
            ->limit(48)
            ->get();

        $showcaseSections = $showcaseListings
            ->groupBy('category_id')
            ->map(fn ($listings) => [
                'category' => $listings->first()->category,
                'listings' => $listings->take(10)->values(),
            ])
            ->take(5)
            ->values();

        $posts = Post::query()
            ->with(['user.community', 'listing.category', 'jobRequest.category', 'jobRequest.communities', 'media', 'comments' => fn ($query) => $query->with('user:id,name,avatar_path,avatar_disk')->limit(2)])
            ->withCount(['reactions', 'comments', 'shares'])
            ->withExists(['reactions as reacted_by_user' => fn ($query) => $query->where('user_id', $user->id)])
            ->whereNotNull('published_at')
            ->whereNull('removed_at')
            ->whereHas('user')
            ->where(function ($query): void {
                $query->whereNotIn('type', ['portfolio', 'business_update', 'product', 'service', 'promotion'])
                    ->orWhereDoesntHave('user.vendor', fn ($vendorQuery) => $vendorQuery->where('status', 'suspended'));
            })
            ->when($module === 'food', fn ($query) => $query->where(function ($query): void {
                $query->whereHas('listing.category', fn ($category) => $category->where('slug', 'comida-bebidas'))
                    ->orWhereHas('jobRequest.category', fn ($category) => $category->where('slug', 'comida-bebidas'));
            }))
            ->when($module === 'transport', fn ($query) => $query->where(function ($query): void {
                $query->whereHas('listing.category', fn ($category) => $category->where('slug', 'transporte-taxi'))
                    ->orWhereHas('jobRequest.category', fn ($category) => $category->where('slug', 'transporte-taxi'));
            }))
            ->when($module === 'products', fn ($query) => $query->whereIn('type', ['product', 'promotion']))
            ->when($module === 'services', fn ($query) => $query
                ->whereIn('type', ['service', 'job_request', 'portfolio', 'business_update'])
                ->whereDoesntHave('listing.category', fn ($category) => $category->whereIn('slug', ['comida-bebidas', 'transporte-taxi']))
                ->whereDoesntHave('jobRequest.category', fn ($category) => $category->whereIn('slug', ['comida-bebidas', 'transporte-taxi'])))
            ->when($feedTypes !== null, fn ($query) => $query->whereIn('type', $feedTypes))
            ->when($feed === 'for_you' && $preferredCategoryIds !== [], fn ($query) => $query->orderByRaw(
                'CASE WHEN EXISTS (SELECT 1 FROM listings WHERE listings.id = posts.listing_id AND listings.category_id IN ('.implode(',', array_map('intval', $preferredCategoryIds)).')) OR EXISTS (SELECT 1 FROM job_requests WHERE job_requests.id = posts.job_request_id AND job_requests.category_id IN ('.implode(',', array_map('intval', $preferredCategoryIds)).')) THEN 0 ELSE 1 END'
            ))
            ->latest('published_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $communities = Community::query()->where('is_active', true)->orderBy('name')->get();

        return view('dashboard', compact('posts', 'activeMode', 'module', 'feed', 'showComposer', 'publishAs', 'categories', 'communities', 'showcaseSections'));
    }
}
