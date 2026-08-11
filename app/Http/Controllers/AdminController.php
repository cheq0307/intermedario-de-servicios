<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $metrics = [
            'users' => User::count(),
            'pending_vendors' => Vendor::where('status', 'pending')->count(),
            'open_disputes' => Dispute::where('status', 'open')->count(),
            'active_orders' => Order::whereIn('status', ['accepted', 'awaiting_payment', 'paid', 'in_progress', 'ready', 'delivered', 'disputed'])->count(),
        ];
        $pendingVendors = Vendor::with('user:id,name,email,email_verified_at')->where('status', 'pending')->latest()->get();
        $vendors = Vendor::with('user:id,name,email,email_verified_at')->where('status', '!=', 'pending')->latest()->limit(30)->get();
        $users = User::with('roles:id,name')->latest()->limit(30)->get();
        $auditLogs = AuditLog::with('user:id,name')->latest('created_at')->limit(30)->get();
        $isSuperadmin = $request->user()->hasRole('superadmin');

        return view('admin.index', compact('metrics', 'pendingVendors', 'vendors', 'users', 'auditLogs', 'isSuperadmin'));
    }

    public function approveVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $vendor->loadMissing('user');
        abort_unless($vendor->user?->hasVerifiedEmail(), 422, 'El proveedor debe verificar su correo antes de ser aprobado.');
        abort_if($vendor->missingReviewRequirements() !== [], 422, 'El proveedor todavía debe completar: '.implode(', ', $vendor->missingReviewRequirements()).'.');
        $this->changeVendorStatus($request, $vendor, 'active');
        $vendor->user->notify(new MarketplaceActivity(
            'Tu perfil de proveedor fue aprobado',
            'Ya puedes publicar ofertas y enviar propuestas en Plaza Local.',
            'profile.show',
            ['user' => $vendor->user_id],
            'vendor_approved',
        ));

        return back()->with('status', 'Proveedor aprobado y notificado.');
    }

    public function suspendVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $this->changeVendorStatus($request, $vendor, 'suspended', ['reason' => $validated['reason']]);
        $vendor->loadMissing('user');
        $vendor->user?->notify(new MarketplaceActivity(
            'Tu perfil de proveedor fue suspendido',
            'Motivo: '.$validated['reason'],
            'profile.show',
            ['user' => $vendor->user_id],
            'vendor_suspended',
        ));

        return back()->with('status', 'Proveedor suspendido y notificado.');
    }

    public function grantAdmin(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        abort_if($user->hasRole('superadmin'), 422);
        $user->assignRole(Role::findOrCreate('admin'));
        $this->audit($request, 'admin.granted', $user);

        return back()->with('status', 'Administrador delegado correctamente.');
    }

    public function revokeAdmin(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        abort_if($user->id === $request->user()->id || $user->hasRole('superadmin'), 422);
        $user->removeRole('admin');
        $this->audit($request, 'admin.revoked', $user);

        return back()->with('status', 'Permiso de administrador retirado.');
    }

    public function grantCapability(Request $request, User $user, string $capability): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        abort_unless(in_array($capability, ['client', 'provider'], true), 404);

        if ($user->hasRole($capability)) {
            return back()->with('status', 'La capacidad ya estaba asignada.');
        }

        DB::transaction(function () use ($request, $user, $capability): void {
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
            $this->audit($request, 'capability.granted', $user, ['capability' => $capability]);
        });

        return back()->with('status', 'Capacidad comercial asignada correctamente.');
    }

    public function revokeCapability(Request $request, User $user, string $capability): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        abort_unless(in_array($capability, ['client', 'provider'], true), 404);
        abort_unless($user->hasRole($capability), 422, 'La cuenta no tiene esta capacidad.');

        $hasOtherCapability = $user->hasRole($capability === 'client' ? 'provider' : 'client');
        $hasStaffAuthority = $user->hasAnyRole(['admin', 'superadmin']);
        abort_unless($hasOtherCapability || $hasStaffAuthority, 422, 'La cuenta debe conservar otra capacidad o autoridad administrativa.');

        DB::transaction(function () use ($request, $user, $capability): void {
            $user->removeRole($capability);

            if ($capability === 'provider') {
                $user->vendor?->update(['status' => 'suspended', 'verified_at' => null]);
            }
            $this->audit($request, 'capability.revoked', $user, ['capability' => $capability]);
        });

        return back()->with('status', 'Capacidad comercial retirada; los datos históricos se conservaron.');
    }

    private function changeVendorStatus(Request $request, Vendor $vendor, string $status, array $metadata = []): void
    {
        DB::transaction(function () use ($request, $vendor, $status, $metadata): void {
            $vendor->update(['status' => $status, 'verified_at' => $status === 'active' ? now() : null]);
            $this->audit($request, 'vendor.'.$status, $vendor, $metadata);
        });
    }

    private function audit(Request $request, string $action, object $subject, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'superadmin']), 403);
    }

    private function authorizeSuperadmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('superadmin'), 403);
    }
}
