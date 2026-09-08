<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAccountController extends Controller
{
    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->authorizeCommercialTarget($request, $user);
        abort_unless($user->account_status === 'active', 422, 'Solo una cuenta activa puede suspenderse.');

        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $this->changeStatus($request, $user, 'suspended', $validated['reason']);

        return back()->with('status', 'Cuenta suspendida. Ya no podrá iniciar ni conservar una sesión activa.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        $this->authorizeCommercialTarget($request, $user);
        abort_if($user->account_status === 'deactivated', 422, 'La cuenta ya fue dada de baja.');

        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $this->changeStatus($request, $user, 'deactivated', $validated['reason']);

        return back()->with('status', 'Cuenta dada de baja conservando su historial administrativo y operativo.');
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorizeCommercialTarget($request, $user);
        abort_unless(in_array($user->account_status, ['suspended', 'deactivated'], true), 422, 'La cuenta ya está activa.');

        if ($user->account_status === 'deactivated') {
            $this->authorizeSuperadmin($request);
        }

        $previousStatus = $user->account_status;
        $this->changeStatus($request, $user, 'active', null, ['previous_status' => $previousStatus]);

        return back()->with('status', 'Cuenta reactivada correctamente.');
    }

    private function changeStatus(Request $request, User $user, string $status, ?string $reason, array $metadata = []): void
    {
        DB::transaction(function () use ($request, $user, $status, $reason, $metadata): void {
            $user->update([
                'account_status' => $status,
                'account_status_reason' => $reason,
                'account_status_changed_at' => now(),
                'admin_user_id' => $request->user()->id,
            ]);

            AuditLog::create([
                'admin_user_id' => $request->user()->id,
                'action' => 'account.'.$status,
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'metadata' => array_merge($metadata, ['reason' => $reason]),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                'created_at' => now(),
            ]);
        });
    }

    private function authorizeCommercialTarget(Request $request, User $user): void
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'superadmin']), 403);
        abort_if($request->user()->ownsMarketplaceAccount($user->id), 422, 'No puedes cambiar el estado de tu propia cuenta.');
        abort_if($user->hasAnyRole(['admin', 'superadmin']), 422, 'Las cuentas administrativas se gestionan mediante delegación de autoridad.');
    }

    private function authorizeSuperadmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('superadmin'), 403);
    }
}
