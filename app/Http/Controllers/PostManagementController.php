<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\JobRequestStatus;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostManagementController extends Controller
{
    public function edit(Request $request, Post $post): View
    {
        $post->load(['listing.vendor', 'jobRequest.proposals']);
        $this->authorizeOwner($request, $post);
        $this->ensureEditable($post);

        return view('posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        DB::transaction(function () use ($request, $post): void {
            $lockedPost = Post::with(['listing.vendor', 'jobRequest.proposals'])->lockForUpdate()->findOrFail($post->id);
            $this->authorizeOwner($request, $lockedPost);
            $this->ensureEditable($lockedPost);

            $validated = $request->validate(['body' => ['required', 'string', 'min:10', 'max:1500']]);

            if ($lockedPost->listing) {
                $details = $request->validate([
                    'title' => ['required', 'string', 'min:3', 'max:120'],
                    'price_type' => ['required', Rule::in(['fixed', 'starting_at', 'quote'])],
                    'price' => [Rule::requiredIf(fn (): bool => $request->input('price_type') !== 'quote'), 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
                    'stock' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
                ]);
                $lockedPost->listing->update([
                    'name' => $details['title'],
                    'description' => $validated['body'],
                    'price_type' => $details['price_type'],
                    'price_amount' => $this->minorUnits($details['price'] ?? null),
                    'stock' => $lockedPost->listing->type->value === 'product' ? ($details['stock'] ?? null) : null,
                ]);
            } elseif ($lockedPost->jobRequest) {
                $details = $request->validate([
                    'title' => ['required', 'string', 'min:5', 'max:120'],
                    'budget_min' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                    'budget_max' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                    'urgency' => ['required', Rule::in(['normal', 'soon', 'urgent'])],
                    'location_label' => ['nullable', 'string', 'max:120'],
                ]);
                if (isset($details['budget_min'], $details['budget_max']) && (float) $details['budget_max'] < (float) $details['budget_min']) {
                    throw ValidationException::withMessages(['budget_max' => 'El presupuesto máximo debe ser mayor o igual al mínimo.']);
                }
                $lockedPost->jobRequest->update([
                    'title' => $details['title'],
                    'description' => $validated['body'],
                    'budget_min_amount' => $this->minorUnits($details['budget_min'] ?? null),
                    'budget_max_amount' => $this->minorUnits($details['budget_max'] ?? null),
                    'urgency' => $details['urgency'],
                    'location_label' => $details['location_label'] ?? null,
                ]);
            }

            $lockedPost->update(['body' => $validated['body']]);
        });

        return redirect()->route('dashboard')->with('status', 'Publicación actualizada correctamente.');
    }

    private function authorizeOwner(Request $request, Post $post): void
    {
        abort_unless($post->user_id === $request->user()->id, 403);
    }

    private function ensureEditable(Post $post): void
    {
        if ($post->listing) {
            abort_unless($post->listing->vendor->status === 'active', 422, 'El perfil comercial debe estar activo para editar esta oferta.');
        }

        if ($post->jobRequest) {
            abort_unless($post->jobRequest->status === JobRequestStatus::Published && $post->jobRequest->proposals->isEmpty(), 422, 'Esta solicitud ya recibió actividad y quedó protegida contra cambios.');
        }
    }

    private function minorUnits(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) round(((float) $value) * 100);
    }
}
