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
            ],
            'profiles' => User::query()
                ->where('email', 'like', '%.demo@example.test')
                ->with('vendor')
                ->orderBy('name')
                ->get()
                ->groupBy(fn (User $user): string => (string) preg_replace('/\d+\.demo@example\.test$/', '', $user->email)),
            'administrators' => AdminUser::query()
                ->where('email', 'like', '%.demo@example.test')
                ->where('active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, DemoMode $demoMode): RedirectResponse
    {
        $demoMode->ensureEnabled();
        $validated = $request->validate([
            'guard' => ['required', 'in:web,admin'],
            'id' => ['required', 'integer'],
        ]);

        $identity = $validated['guard'] === 'admin'
            ? AdminUser::query()->where('active', true)->findOrFail($validated['id'])
            : User::query()->where('account_status', 'active')->findOrFail($validated['id']);

        abort_unless($demoMode->isDemoIdentity($identity), 403);

        Auth::guard($validated['guard'])->login($identity);
        $request->session()->regenerate();

        return redirect()->route($validated['guard'] === 'admin' ? 'admin.index' : 'dashboard');
    }
}
