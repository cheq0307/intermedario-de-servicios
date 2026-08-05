<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1500'],
        ]);

        DB::transaction(function () use ($request, $order, $validated): void {
            $lockedOrder = Order::with('vendor')->lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->isParticipant($request->user()), 403);
            abort_unless($lockedOrder->status === OrderStatus::Completed, 422);
            abort_if($lockedOrder->reviews()->where('author_id', $request->user()->id)->exists(), 422, 'Ya calificaste esta contratación.');

            $subjectId = $lockedOrder->buyer_id === $request->user()->id
                ? $lockedOrder->vendor->user_id
                : $lockedOrder->buyer_id;
            $lockedOrder->reviews()->create([
                'author_id' => $request->user()->id,
                'subject_user_id' => $subjectId,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'is_visible' => true,
            ]);
        });

        return back()->with('status', 'Tu calificación fue publicada.');
    }
}
