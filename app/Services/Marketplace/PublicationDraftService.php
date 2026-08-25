<?php

namespace App\Services\Marketplace;

use App\Models\PublicationDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicationDraftService
{
    public function saveAndRequestVerification(Request $request): RedirectResponse
    {
        $allowedTypes = ['job_request', 'portfolio', 'business_update', 'product', 'service', 'promotion'];
        $type = in_array($request->input('type'), $allowedTypes, true) ? $request->input('type') : 'job_request';
        $submissionToken = (string) $request->input('submission_token');

        $payload = [
            'submission_token' => Str::isUuid($submissionToken) ? $submissionToken : (string) Str::uuid(),
            'type' => $type,
            'body' => Str::limit(trim((string) $request->input('body')), 1500, ''),
            'title' => Str::limit(trim((string) $request->input('title')), 120, ''),
            'category_id' => $request->integer('category_id') ?: null,
            'community_ids' => collect($request->input('community_ids', []))
                ->filter(fn ($id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false)
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->take(25)
                ->values()
                ->all(),
            'price_type' => in_array($request->input('price_type'), ['fixed', 'starting_at', 'quote'], true)
                ? $request->input('price_type')
                : 'quote',
            'price' => Str::limit((string) $request->input('price'), 20, ''),
            'stock' => Str::limit((string) $request->input('stock'), 20, ''),
            'budget_min' => Str::limit((string) $request->input('budget_min'), 20, ''),
            'budget_max' => Str::limit((string) $request->input('budget_max'), 20, ''),
            'urgency' => in_array($request->input('urgency'), ['normal', 'soon', 'urgent'], true)
                ? $request->input('urgency')
                : 'normal',
            'location_label' => Str::limit(trim((string) $request->input('location_label')), 120, ''),
        ];

        PublicationDraft::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['payload' => $payload, 'expires_at' => now()->addDays(config('marketplace.publication_draft_days', 30))],
        );

        return redirect()->route('verification.notice')->with(
            'status',
            'Guardamos el texto y las opciones de tu publicación. Verifica tu correo y la recuperaremos automáticamente. Por seguridad, tendrás que seleccionar otra vez las fotos o videos.',
        );
    }
}
