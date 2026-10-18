<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\User;
use App\Support\DemoMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class DemoAccessController extends Controller
{
    public function index(DemoMode $demoMode): View
    {
        $demoMode->ensureEnabled();

        return view('auth.demo-access', [
            'profileLabels' => [
                'comprador' => 'Compradores y solicitantes',
                'proveedor' => 'Proveedores',
                'empleador' => 'Empleadores',
                'postulante' => 'Postulantes',
                'otros' => 'Otros perfiles de prueba',
            ],
            'profiles' => User::query()
                ->where('email', 'like', '%.demo@example.test')
                ->with('vendor')
                ->orderBy('name')
                ->get()
                ->groupBy(fn (User $user): string => $this->profileType($user)),
        ]);
    }

    public function store(Request $request, DemoMode $demoMode): RedirectResponse
    {
        $demoMode->ensureEnabled();
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $identity = User::query()->where('account_status', 'active')->findOrFail($validated['id']);

        abort_unless($demoMode->isDemoIdentity($identity), 403);

        Auth::guard('web')->login($identity);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function adminIndex(DemoMode $demoMode): View
    {
        $demoMode->ensureEnabled();

        return view('auth.demo-admin-access', [
            'administrators' => AdminUser::query()
                ->where('email', 'like', '%.demo@example.test')
                ->where('active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeAdmin(Request $request, DemoMode $demoMode): RedirectResponse
    {
        $demoMode->ensureEnabled();
        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        $identity = AdminUser::query()->where('active', true)->findOrFail($validated['id']);

        abort_unless($demoMode->isDemoIdentity($identity), 403);

        Auth::guard('admin')->login($identity);
        $request->session()->regenerate();

        return redirect()->route('admin.index');
    }

    private function profileType(User $user): string
    {
        preg_match(
            '/^(comprador|proveedor|empleador|postulante)\d*\.demo@example\.test$/',
            strtolower($user->email),
            $matches,
        );

        return $matches[1] ?? 'otros';
    }
}
