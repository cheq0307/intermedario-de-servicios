<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Community;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Post;
use App\Models\PostalCode;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            'postal_codes' => PostalCode::count(),
            'open_support_tickets' => SupportTicket::whereIn('status', ['open', 'in_progress', 'waiting_user'])->count(),
            'pending_vendors' => Vendor::where('status', 'pending')->where('user_id', '!=', $request->user()->id)->count(),
            'open_disputes' => Dispute::where('status', 'open')->count(),
            'active_orders' => Order::whereIn('status', ['accepted', 'awaiting_payment', 'paid', 'in_progress', 'ready', 'delivered', 'disputed'])->count(),
            'active_posts' => Post::whereNull('removed_at')->count(),
            'unread_notifications' => $request->user()->unreadNotifications()->count(),
        ];
        $pendingVendorCount = $metrics['pending_vendors'];
        $adminSearch = trim((string) $request->query('admin_q', ''));
        abort_if(mb_strlen($adminSearch) > 100, 422, 'La búsqueda es demasiado larga.');
        $administrators = User::query()
            ->with(['roles:id,name', 'community:id,name'])
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->whereKeyNot($request->user()->id)
            ->orderBy('name')
            ->paginate(8, ['*'], 'administrators_page')
            ->withQueryString();
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
        $auditLogs = AuditLog::with('user:id,name')->latest('created_at')->paginate(15, ['*'], 'audit_page')->withQueryString();
        $communities = Community::query()->withCount(['users', 'jobRequests'])->orderBy('name')->paginate(9, ['*'], 'communities_page')->withQueryString();
        $auditActions = ['account.active' => 'Cuenta reactivada', 'account.suspended' => 'Cuenta suspendida', 'account.deactivated' => 'Cuenta dada de baja', 'vendor.submitted' => 'Solicitud de proveedor enviada', 'admin.granted' => 'Administrador asignado', 'admin.revoked' => 'Permiso de administrador retirado', 'vendor.active' => 'Proveedor aprobado o reactivado', 'vendor.rejected' => 'Cambios solicitados al proveedor', 'vendor.suspended' => 'Proveedor suspendido', 'vendor.verified' => 'Proveedor verificado', 'vendor.verification_revoked' => 'Verificación retirada', 'post.removed' => 'Publicación retirada', 'community.created' => 'Comunidad agregada', 'community.updated' => 'Comunidad actualizada', 'community.suspended' => 'Comunidad suspendida', 'community.reactivated' => 'Comunidad reactivada', 'community.deleted' => 'Comunidad eliminada'];
        $categories = Category::query()->withCount(['users', 'vendors', 'listings', 'jobRequests'])->orderBy('name')->paginate(9, ['*'], 'categories_page')->withQueryString();
        $auditSubjects = ['User' => 'Usuario', 'Vendor' => 'Proveedor', 'Post' => 'Publicación', 'Community' => 'Comunidad'];
        $isSuperadmin = $request->user()->hasRole('superadmin');

        return view('admin.index', compact('metrics', 'pendingVendorCount', 'administrators', 'adminCandidates', 'adminSearch', 'auditLogs', 'communities', 'categories', 'auditActions', 'auditSubjects', 'isSuperadmin'));
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
        $vendor->user->assignRole(Role::findOrCreate('provider'));
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

    public function verifyVendor(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        abort_unless($vendor->status === 'active', 422, 'Solo se puede verificar un proveedor aprobado y activo.');
        $validated = $request->validate([
            'verification_level' => ['required', Rule::in(['identity', 'business'])],
            'verification_note' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        abort_unless(
            $vendor->hasApprovedVerificationDocuments($validated['verification_level']),
            422,
            'Faltan documentos aprobados para este nivel de verificación.',
        );

        $vendor->update([
            'verified_at' => now(),
            'verified_by_user_id' => $request->user()->id,
            'verification_level' => $validated['verification_level'],
            'verification_note' => $validated['verification_note'],
        ]);
        $this->audit($request, 'vendor.verified', $vendor, ['level' => $validated['verification_level']]);
        $vendor->loadMissing('user');
        $vendor->user?->notify(new MarketplaceActivity('Obtuviste el distintivo de proveedor verificado', 'Tu identidad o negocio fue revisado por Plaza Local. Este distintivo no fue comprado.', 'profile.show', ['user' => $vendor->user_id], 'vendor_verified'));

        return back()->with('status', 'Proveedor verificado y notificado.');
    }

    public function revokeVendorVerification(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        abort_if($vendor->verified_at === null, 422, 'Este proveedor no tiene un distintivo vigente.');
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        $vendor->update([
            'verified_at' => null,
            'verified_by_user_id' => null,
            'verification_level' => null,
            'verification_note' => null,
        ]);
        $this->audit($request, 'vendor.verification_revoked', $vendor, ['reason' => $validated['reason']]);
        $vendor->loadMissing('user');
        $vendor->user?->notify(new MarketplaceActivity('Tu distintivo de proveedor verificado fue retirado', 'Motivo: '.$validated['reason'], 'support.create', ['category' => 'moderation'], 'vendor_verification_revoked'));

        return back()->with('status', 'Distintivo retirado y proveedor notificado.');
    }

    public function removePost(Request $request, Post $post): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_if($post->removed_at !== null, 422, 'Esta publicación ya fue retirada.');
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);

        DB::transaction(function () use ($request, $post, $validated): void {
            $post->update([
                'removed_at' => now(),
                'removed_by_user_id' => $request->user()->id,
                'removal_reason' => $validated['reason'],
            ]);
            $this->audit($request, 'post.removed', $post, [
                'reason' => $validated['reason'],
                'type' => $post->type,
            ]);
        });

        $post->loadMissing('user');
        $post->user?->notify(new MarketplaceActivity(
            'Una publicación fue retirada',
            'Motivo: '.$validated['reason'].' Si necesitas una revisión, contacta a soporte.',
            'support.create',
            ['category' => 'moderation', 'subject' => 'Revisión de publicación #'.$post->id],
            'post_removed',
        ));

        return back()->with('status', 'Publicación retirada y autor notificado.');
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
            'default_radius_km' => ['nullable', 'numeric', 'min:1', 'max:100'],
        ]);
        $validated['default_radius_km'] ??= 8;

        $community = DB::transaction(function () use ($validated): Community {
            $community = Community::create($validated + ['is_active' => true]);

            if (! empty($validated['postal_code'])) {
                PostalCode::query()->updateOrCreate([
                    'postal_code' => $validated['postal_code'],
                    'settlement' => $validated['name'],
                    'municipality' => $validated['municipality'],
                ], [
                    'settlement_type' => 'Comunidad registrada',
                    'state' => $validated['state'] ?? null,
                    'city' => $validated['municipality'],
                ]);
            }

            return $community;
        });
        $this->audit($request, 'community.created', $community);

        return back()->with('status', 'Comunidad agregada al catálogo territorial.');
    }

    public function importPostalCodes(Request $request): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        $validated = $request->validate([
            'catalog' => ['required', 'file', 'max:51200', 'mimetypes:text/plain,text/csv,application/octet-stream'],
        ], [
            'catalog.required' => 'Selecciona el TXT oficial de Correos de México.',
            'catalog.max' => 'El catálogo no puede superar 50 MB.',
            'catalog.mimetypes' => 'El catálogo debe ser el archivo TXT oficial.',
        ]);

        $exitCode = Artisan::call('plaza:import-postal-codes', [
            'file' => $validated['catalog']->getRealPath(),
        ]);
        $output = trim(Artisan::output());
        if ($exitCode !== 0) {
            return back()->withErrors(['catalog' => $output ?: 'No fue posible importar el catálogo postal.']);
        }

        return back()->with('status', $output);
    }

    public function updateCommunity(Request $request, Community $community): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120', Rule::unique('communities', 'name')->ignore($community->id)->where(fn ($query) => $query->where('municipality', $request->input('municipality', $community->municipality)))],
            'municipality' => ['sometimes', 'required', 'string', 'max:120'],
            'state' => ['sometimes', 'nullable', 'string', 'max:120'],
            'postal_code' => ['sometimes', 'nullable', 'regex:/^\d{5}$/'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'default_radius_km' => ['sometimes', 'required', 'numeric', 'min:1', 'max:100'],
        ]);

        $community->update($validated);
        if ($community->postal_code) {
            PostalCode::query()->updateOrCreate([
                'postal_code' => $community->postal_code,
                'settlement' => $community->name,
                'municipality' => $community->municipality,
            ], [
                'settlement_type' => 'Comunidad registrada',
                'state' => $community->state,
                'city' => $community->municipality,
            ]);
        }
        $this->audit($request, 'community.updated', $community, ['changes' => array_keys($validated)]);

        return back()->with('status', 'Información de la comunidad actualizada.');
    }

    public function toggleCommunity(Request $request, Community $community): RedirectResponse
    {
        $this->authorizeAdmin($request);
        if ($community->is_active && Community::query()->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['community' => 'Debe permanecer al menos una comunidad activa en Plaza Local.']);
        }

        $community->update(['is_active' => ! $community->is_active]);
        $action = $community->is_active ? 'community.reactivated' : 'community.suspended';
        $this->audit($request, $action, $community, ['is_active' => $community->is_active]);

        return back()->with('status', $community->is_active ? 'Comunidad reactivada.' : 'Comunidad suspendida. Ya no aparecerá en registros, publicaciones ni búsquedas nuevas.');
    }

    public function destroyCommunity(Request $request, Community $community): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        if ($community->is_active) {
            return back()->withErrors(['community' => 'Primero suspende la comunidad antes de eliminarla.']);
        }

        $community->loadCount(['users', 'jobRequests']);
        if ($community->users_count > 0 || $community->job_requests_count > 0) {
            return back()->withErrors(['community' => 'No se puede eliminar porque conserva usuarios o solicitudes asociadas. Déjala suspendida para preservar el historial.']);
        }

        $this->audit($request, 'community.deleted', $community, ['name' => $community->name]);
        $community->delete();

        return back()->with('status', 'Comunidad vacía eliminada definitivamente.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $slug = Str::slug($validated['name']);
        abort_if($slug === '', 422, 'El nombre del rubro no es válido.');
        $category = Category::firstOrCreate(['slug' => $slug], ['name' => $validated['name'], 'is_active' => true]);
        if (! $category->wasRecentlyCreated) {
            $category->update(['name' => $validated['name'], 'is_active' => true]);
        }
        $this->audit($request, 'category.created', $category);

        return back()->with('status', 'Rubro disponible para perfiles, publicaciones y búsquedas.');
    }

    public function toggleCategory(Request $request, Category $category): RedirectResponse
    {
        $this->authorizeAdmin($request);
        if ($category->is_active && ($category->listings()->exists() || $category->jobRequests()->exists())) {
            return back()->withErrors(['category' => 'No se puede desactivar un rubro que todavía tiene publicaciones asociadas.']);
        }
        $category->update(['is_active' => ! $category->is_active]);
        $this->audit($request, 'category.updated', $category, ['is_active' => $category->is_active]);

        return back()->with('status', $category->is_active ? 'Rubro reactivado.' : 'Rubro desactivado.');
    }

    public function grantAdmin(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        abort_if($user->hasRole('superadmin'), 422);
        $user->assignRole(Role::findOrCreate('admin'));
        $this->audit($request, 'admin.granted', $user, ['commercial_access_paused' => true]);
        $request->session()->forget('marketplace_mode');

        return redirect(route('admin.index').'#administradores')->with('status', 'Administrador delegado. Su actividad comercial quedó pausada mientras conserve el cargo.');
    }

    public function revokeAdmin(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperadmin($request);
        abort_if($user->id === $request->user()->id || $user->hasRole('superadmin'), 422);
        $user->removeRole('admin');
        $this->audit($request, 'admin.revoked', $user, ['commercial_access_restored' => true]);

        return redirect(route('admin.index').'#administradores')->with('status', 'Permiso retirado. La cuenta recuperó automáticamente sus capacidades comerciales previas.');
    }

    private function changeVendorStatus(Request $request, Vendor $vendor, string $status, array $metadata = []): void
    {
        DB::transaction(function () use ($request, $vendor, $status, $metadata): void {
            $vendor->update([
                'status' => $status,
                'verified_at' => in_array($status, ['rejected', 'suspended'], true) ? null : $vendor->verified_at,
                'verified_by_user_id' => in_array($status, ['rejected', 'suspended'], true) ? null : $vendor->verified_by_user_id,
                'verification_level' => in_array($status, ['rejected', 'suspended'], true) ? null : $vendor->verification_level,
                'verification_note' => in_array($status, ['rejected', 'suspended'], true) ? null : $vendor->verification_note,
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
