<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Community;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $metrics = [
            'users' => User::count(),
            'vendors' => Vendor::count(),
            'pending_vendors' => Vendor::where('status', 'pending')->where('user_id', '!=', $request->user()->id)->count(),
            'open_disputes' => Dispute::where('status', 'open')->count(),
            'active_orders' => Order::whereIn('status', ['accepted', 'awaiting_payment', 'paid', 'in_progress', 'ready', 'delivered', 'disputed'])->count(),
        ];
        $pendingVendors = Vendor::with('user:id,name,email,email_verified_at')
            ->where('status', 'pending')->where('user_id', '!=', $request->user()->id)
            ->latest('submitted_at')->get();
        $vendors = Vendor::with('user:id,name,email,email_verified_at')
            ->whereIn('status', ['active', 'suspended'])->where('user_id', '!=', $request->user()->id)
            ->latest()->limit(30)->get();
        $adminSearch = trim((string) $request->query('admin_q', ''));
        abort_if(mb_strlen($adminSearch) > 100, 422, 'La búsqueda es demasiado larga.');
        $administrators = User::query()
            ->with(['roles:id,name', 'community:id,name'])
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->whereKeyNot($request->user()->id)
            ->orderBy('name')
            ->get();
        $adminCandidates = collect();
        if ($request->user()->hasRole('superadmin') && mb_strlen($adminSearch) >= 2) {
            $adminCandidates = User::query()
                ->with(['roles:id,name', 'community:id,name'])
                ->whereKeyNot($request->user()->id)
                ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['admin', 'superadmin']))
                ->where(function ($query) use ($adminSearch): void {
                    $query->where('name', 'like', "%{$adminSearch}%")
                        ->orWhere('email', 'like', "%{$adminSearch}%");
                })
                ->orderBy('name')
                ->limit(10)
                ->get();
        }
        $auditLogs = AuditLog::with('user:id,name')->latest('created_at')->limit(30)->get();
        $communities = Community::query()->withCount('users')->orderBy('name')->get();
        $auditActions = ['admin.granted' => 'Administrador asignado', 'admin.revoked' => 'Permiso de administrador retirado', 'vendor.active' => 'Proveedor aprobado o reactivado', 'vendor.rejected' => 'Cambios solicitados al proveedor', 'vendor.suspended' => 'Proveedor suspendido', 'community.created' => 'Comunidad agregada', 'community.updated' => 'Centro comunitario actualizado'];
        $auditSubjects = ['User' => 'Usuario', 'Vendor' => 'Proveedor', 'Community' => 'Comunidad'];
        $isSuperadmin = $request->user()->hasRole('superadmin');

        return view('admin.index', compact('metrics', 'pendingVendors', 'vendors', 'administrators', 'adminCandidates', 'adminSearch', 'auditLogs', 'communities', 'auditActions', 'auditSubjects', 'isSuperadmin'));
    }

    public function approveVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_if($vendor->user_id === $request->user()->id, 403, 'Un administrador no puede aprobar su propio perfil comercial.');
        abort_unless(
            ($vendor->status === 'pending' && $vendor->submitted_at) || $vendor->status === 'suspended',
            422,
            'Este perfil no fue enviado a revisión.',
        );
        $vendor->loadMissing('user');
        abort_unless($vendor->user?->hasVerifiedEmail(), 422, 'El proveedor debe verificar su correo antes de ser aprobado.');
        abort_if($vendor->missingReviewRequirements() !== [], 422, 'El proveedor todavía debe completar: '.implode(', ', $vendor->missingReviewRequirements()).'.');
        $this->changeVendorStatus($request, $vendor, 'active');
        $vendor->user->notify(new MarketplaceActivity('Tu perfil de proveedor fue aprobado', 'Ya puedes publicar ofertas y enviar propuestas en Plaza Local.', 'profile.show', ['user' => $vendor->user_id], 'vendor_approved'));

        return back()->with('status', 'Proveedor aprobado y notificado.');
    }

    public function rejectVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_if($vendor->user_id === $request->user()->id, 403);
        abort_unless($vendor->status === 'pending' && $vendor->submitted_at, 422, 'Este perfil no fue enviado a revisión.');
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $this->changeVendorStatus($request, $vendor, 'rejected', ['reason' => $validated['reason']]);
        $vendor->loadMissing('user');
        $vendor->user?->notify(new MarketplaceActivity('Tu solicitud de proveedor necesita cambios', 'Motivo: '.$validated['reason'], 'profile.edit', [], 'vendor_rejected'));

        return back()->with('status', 'Solicitud devuelta al usuario con el motivo indicado.');
    }

    public function suspendVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_if($vendor->user_id === $request->user()->id, 403, 'Un administrador no puede suspender su propio perfil comercial.');
        abort_unless($vendor->status === 'active', 422, 'Solo un proveedor activo puede ser suspendido.');
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $this->changeVendorStatus($request, $vendor, 'suspended', ['reason' => $validated['reason']]);
        $vendor->loadMissing('user');
        $vendor->user?->notify(new MarketplaceActivity('Tu perfil de proveedor fue suspendido', 'Motivo: '.$validated['reason'], 'profile.show', ['user' => $vendor->user_id], 'vendor_suspended'));

        return back()->with('status', 'Proveedor suspendido y notificado.');
    }

    public function storeCommunity(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('communities', 'name')->where(fn ($query) => $query->where('municipality', $request->input('municipality')))],
            'municipality' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'regex:/^\d{5}$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'default_radius_km' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        $community = Community::create($validated + ['is_active' => true]);
        $this->audit($request, 'community.created', $community);

        return back()->with('status', 'Comunidad agregada al catálogo territorial.');
    }
    public function updateCommunity(Request $request, Community $community): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'default_radius_km' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        $community->update($validated);
        $this->audit($request, 'community.updated', $community, [
            'default_radius_km' => $validated['default_radius_km'],
        ]);

        return back()->with('status', 'Centro y radio de la comunidad actualizados.');
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
            $vendor->update([
                'status' => $status,
                'verified_at' => $status === 'active' ? now() : null,
                'reviewed_at' => now(),
                'rejection_reason' => $status === 'rejected' ? ($metadata['reason'] ?? null) : null,
                'suspension_reason' => $status === 'suspended' ? ($metadata['reason'] ?? null) : null,
                'suspended_at' => $status === 'suspended' ? now() : null,
            ]);
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
