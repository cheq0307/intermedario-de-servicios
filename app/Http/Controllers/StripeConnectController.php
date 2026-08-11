<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class StripeConnectController extends Controller
{
    public function onboard(Request $request, StripeClient $stripe): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor && $vendor->status === 'active', 403);
        if (! $vendor->stripe_account_id) {
            $account = $stripe->accounts->create(['type' => 'express', 'country' => 'MX', 'email' => $request->user()->email, 'capabilities' => ['transfers' => ['requested' => true]]], ['idempotency_key' => 'vendor-account-'.$vendor->id]);
            $vendor->update(['stripe_account_id' => $account->id]);
        }
        $link = $stripe->accountLinks->create(['account' => $vendor->stripe_account_id, 'refresh_url' => route('stripe.connect.refresh'), 'return_url' => route('stripe.connect.return'), 'type' => 'account_onboarding']);

        return redirect()->away($link->url);
    }

    public function refresh(Request $request, StripeClient $stripe): RedirectResponse
    {
        return $this->onboard($request, $stripe);
    }

    public function returned(Request $request, StripeClient $stripe): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        abort_unless($vendor?->stripe_account_id, 404);
        $account = $stripe->accounts->retrieve($vendor->stripe_account_id, []);
        $vendor->update(['stripe_details_submitted' => $account->details_submitted, 'stripe_charges_enabled' => $account->charges_enabled, 'stripe_payouts_enabled' => $account->payouts_enabled]);

        return redirect()->route('profile.edit')->with('status', $account->payouts_enabled ? 'Cuenta de cobro verificada.' : 'Stripe recibio tus datos; la verificacion sigue pendiente.');
    }
}
