<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\PostalCode;
use Illuminate\Http\JsonResponse;

class PostalCodeController extends Controller
{
    public function show(string $postalCode): JsonResponse
    {
        abort_unless(preg_match('/^\d{5}$/', $postalCode) === 1, 404);

        $places = PostalCode::query()
            ->where('postal_code', $postalCode)
            ->orderBy('settlement')
            ->get(['postal_code', 'settlement', 'settlement_type', 'municipality', 'state', 'city']);
        if ($places->isEmpty()) {
            $places = Community::query()
                ->where('postal_code', $postalCode)
                ->orderBy('name')
                ->get()
                ->map(fn (Community $community): array => [
                    'postal_code' => $community->postal_code,
                    'settlement' => $community->name,
                    'settlement_type' => 'Comunidad registrada',
                    'municipality' => $community->municipality,
                    'state' => $community->state,
                    'city' => $community->municipality,
                ]);
        }

        $communities = Community::query()->where('is_active', true)->where('postal_code', $postalCode)
            ->orderBy('name')->get(['id', 'name', 'municipality', 'state', 'postal_code', 'default_radius_km']);

        return response()->json([
            'found' => $places->isNotEmpty(),
            'postal_code' => $postalCode,
            'places' => $places,
            'communities' => $communities,
        ]);
    }
}
