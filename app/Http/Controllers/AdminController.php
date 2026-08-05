<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'active_orders' => Order::whereIn('status', ['accepted', 'in_progress', 'delivered', 'disputed'])->count(),
        ];
        $vendors = Vendor::with('user:id,name,email')->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest()->limit(30)->get();
        $users = User::with('roles:id,name')->latest()->limit(30)->get();
        $auditLogs = AuditLog::with('user:id,name')->latest('created_at')->limit(30)->get();
        $isSuperadmin = $request->user()->hasRole('superadmin');

        return view('admin.index', compact('metrics', 'vendors', 'users', 'auditLogs', 'isSuperadmin'));
    }

    public function approveVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $this->changeVendorStatus($request, $vendor, 'active');

        return back()->with('status', 'Proveedor aprobado.');
    }

    public function suspendVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $this->changeVendorStatus($request, $vendor, 'suspended', ['reason' => $validated['reason']]);

        return back()->with('status', 'Proveedor suspendido.');
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
