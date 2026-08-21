<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostPromotion;
use App\Services\Payments\MercadoPagoPromotionCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    public function checkout(Request $request, PostPromotion $promotion, MercadoPagoPromotionCheckout $checkout): RedirectResponse
    {
        abort_unless($promotion->user_id === $request->user()->id, 403);
        abort_unless(in_array($promotion->status, ['pending_payment', 'payment_failed'], true), 422, 'Esta promoción ya no admite otro pago.');

        if ($promotion->provider_preference_id && $promotion->checkout_url) {
            return redirect()->away($promotion->checkout_url);
        }

        $data = $checkout->create($promotion);
        $promotion->update([
            'status' => 'pending_payment',
            'payment_provider' => 'mercadopago',
            'provider_preference_id' => $data['preference_id'],
            'checkout_url' => $data['checkout_url'],
            'payment_payload' => $data['payload'],
        ]);

        return redirect()->away($data['checkout_url']);
    }

    public function returned(Request $request, PostPromotion $promotion, MercadoPagoPromotionCheckout $checkout): RedirectResponse
    {
        abort_unless($promotion->user_id === $request->user()->id, 403);

        $paymentId = (string) $request->query('payment_id', '');
        if (ctype_digit($paymentId)) {
            try {
                $checkout->reconcile($checkout->fetchPayment((int) $paymentId));
            } catch (\Throwable $exception) {
                Log::warning('No se pudo conciliar el retorno de Mercado Pago.', [
                    'promotion_id' => $promotion->id,
                    'payment_id' => $paymentId,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $promotion->refresh();
        $message = match ($promotion->status) {
            'active' => 'Pago aprobado. Tu publicación ya aparece como patrocinada.',
            'payment_failed' => 'Mercado Pago no aprobó el pago. Puedes volver a intentarlo.',
            default => 'Mercado Pago está procesando el pago. Actualizaremos la promoción mediante una notificación segura.',
        };

        return redirect()->route('promotions.index')->with('status', $message);
    }
}
