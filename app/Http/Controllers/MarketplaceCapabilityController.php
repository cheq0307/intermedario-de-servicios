<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceActivity;
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
        abort_if($user->hasRole('superadmin'), 403, 'La cuenta superadministradora es exclusivamente administrativa.');

        if ($user->supportsMarketplaceMode($capability)) {
            return back()->with('status', 'Esta capacidad ya estaba activa en tu cuenta.');
        }

        DB::transaction(function () use ($user, $capability): void {
            if ($capability === 'provider') {
                // La capacidad técnica se concede únicamente después de la aprobación administrativa.
                Vendor::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'display_name' => $user->name,
                        'slug' => Str::slug($user->name).'-'.$user->id,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'status' => 'draft',
                    ],
                );
            } else {
                $user->assignRole(Role::findOrCreate('client'));
            }
        });

        $request->session()->put('marketplace_mode', $capability);

        $message = $capability === 'provider'
            ? 'Tu perfil de proveedor quedó en borrador. Complétalo y envíalo a verificación cuando esté listo.'
            : 'La capacidad de cliente quedó activa; ya puedes comprar y publicar solicitudes.';

        return redirect()->route('profile.edit')->with('status', $message);
    }

    public function submitProviderApplication(Request $request): RedirectResponse
    {
        $user = $request->user()->load('vendor');
        abort_if($user->hasRole('superadmin'), 403);
        abort_unless($user->canUseMarketplace() && $user->vendor, 403);
        abort_if($user->vendor->status === 'active', 422, 'Tu perfil de proveedor ya está aprobado.');
        abort_if($user->vendor->status === 'suspended', 422, 'Un perfil suspendido debe ser revisado por soporte.');
        abort_unless($user->hasVerifiedEmail(), 422, 'Verifica tu correo antes de enviar la solicitud.');
        abort_if($user->vendor->missingReviewRequirements() !== [], 422, 'Completa tu perfil comercial antes de enviarlo.');

        $user->vendor->update([
            'status' => 'pending',
            'submitted_at' => now(),
            'reviewed_at' => null,
            'rejection_reason' => null,
            'verified_at' => null,
        ]);

        User::whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'superadmin']))
            ->each(function (User $administrator) use ($user): void {
                $administrator->notify(new MarketplaceActivity(
                    'Nueva solicitud de proveedor',
                    $user->vendor->display_name.' envió su perfil para revisión.',
                    'admin.index',
                    [],
                    'vendor_submitted',
                ));
            });

        return redirect()->route('profile.edit')->with('status', 'Solicitud enviada. Un administrador revisará tu correo y perfil comercial.');
    }

    public function switchMode(Request $request, string $mode): RedirectResponse
    {
        abort_if($request->user()->hasRole('superadmin'), 403, 'La cuenta superadministradora es exclusivamente administrativa.');
        abort_unless(in_array($mode, ['client', 'provider'], true), 404);
        abort_unless($request->user()->supportsMarketplaceMode($mode), 403);

        $request->session()->put('marketplace_mode', $mode);

        return back()->with('status', $mode === 'provider'
            ? 'Ahora estás usando Plaza Local como proveedor.'
            : 'Ahora estás usando Plaza Local como cliente.');
    }
}
