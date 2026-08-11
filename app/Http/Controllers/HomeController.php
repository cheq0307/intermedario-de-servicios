<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect()->route('dashboard');
        }

        $featuredListings = Listing::query()
            ->with(['vendor.user:id,name,avatar_path,city', 'post.media'])
            ->where('is_active', true)
            ->whereHas('vendor', fn (Builder $query) => $query->where('status', 'active'))
            ->latest()
            ->limit(3)
            ->get();

        $metrics = [
            'listings' => Listing::where('is_active', true)->whereHas('vendor', fn (Builder $query) => $query->where('status', 'active'))->count(),
            'providers' => Vendor::where('status', 'active')->count(),
        ];

        return view('home', compact('featuredListings', 'metrics'));
    }
}
