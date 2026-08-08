<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MarketplaceCapabilityController extends Controller
{
    public function activate(Request $request, string $capability): RedirectResponse
    {
        abort_unless(in_array($capability, ['client', 'provider'], true), 404);

        $user = $request->user();
        if ($user->supportsMarketplaceMode($capability)) {
            return back()->with('status', 'Esta capacidad ya estaba activa en tu cuenta.');
        }

        DB::transaction(function () use ($user, $capability): void {
            $user->assignRole(Role::findOrCreate($capability));

            if ($capability === 'provider') {
                Vendor::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'display_name' => $user->name,
                        'slug' => Str::slug($user->name).'-'.$user->id,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'status' => 'pending',
                    ],
                );
            }
        });

        $request->session()->put('marketplace_mode', $capability);

        $message = $capability === 'provider'
            ? 'Ya puedes completar tu perfil comercial. Un administrador deberá aprobarlo antes de que publiques ofertas.'
            : 'La capacidad de cliente quedó activa; ya puedes comprar y publicar solicitudes.';

        return redirect()->route('profile.edit')->with('status', $message);
    }

    public function switchMode(Request $request, string $mode): RedirectResponse
    {
        abort_unless(in_array($mode, ['client', 'provider'], true), 404);
        abort_unless($request->user()->supportsMarketplaceMode($mode), 403);

        $request->session()->put('marketplace_mode', $mode);

        return back()->with(
            'status',
            $mode === 'provider' ? 'Ahora estás usando Plaza Local como proveedor.' : 'Ahora estás usando Plaza Local como cliente.',
        );
    }
}
