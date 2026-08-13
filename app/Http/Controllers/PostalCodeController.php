<?php

namespace App\Http\Controllers;

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

        return response()->json([
            'found' => $places->isNotEmpty(),
            'postal_code' => $postalCode,
            'places' => $places,
        ]);
    }
}
