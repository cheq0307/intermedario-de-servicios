<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\AccountType;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $allowedTypes = $request->user()->account_type === AccountType::Provider
            ? ['portfolio', 'business_update', 'product', 'service', 'promotion']
            : ['job_request'];

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in($allowedTypes)],
            'body' => ['required', 'string', 'min:10', 'max:1500'],
        ]);

        Post::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'published_at' => now(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Tu publicación ya está visible en la comunidad.');
    }
}
