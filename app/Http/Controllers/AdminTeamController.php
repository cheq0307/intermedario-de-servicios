<?php

namespace App\Http\Controllers;

use App\Models\AccountIdentityLink;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\Community;
use App\Models\User;
use App\Services\Accounts\IdentityContacts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AdminTeamController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('superadmin'), 403);
        $q = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? '';
        $admins = AdminUser::query()->when($q !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.team', compact('admins', 'q'));
    }

    public function invite(Request $request)
    {
        abort_unless($request->user()->hasRole('superadmin'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'lowercase', Rule::unique('admin_users', 'email')],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('admin_users', 'phone')]]);
        $user = User::whereNull('migrated_to_admin_at')->where(fn ($q) => $q->where('email', $data['email'])->orWhere('phone', $data['phone']))->first();
        abort_if($user && ($user->email !== $data['email'] || $user->phone !== $data['phone']), 422, 'Si ya existe una cuenta de Plaza Local, correo y teléfono deben coincidir con esa misma cuenta.');
        $token = Str::random(64);
        DB::transaction(function () use ($request, $data, $user, $token) {
            IdentityContacts::lock();
            $reserved = DB::table('admin_invitations')->where('phone', $data['phone'])->where('email', '!=', $data['email'])->exists();
            if ($reserved) {
                throw ValidationException::withMessages(['phone' => 'Ya hay una invitación con este teléfono y otro correo. Revisa los datos antes de volver a invitar.']);
            }
            DB::table('admin_invitations')->updateOrInsert(['email' => $data['email']], $data + [
                'token_hash' => hash('sha256', $token), 'user_id' => $user?->id, 'invited_by' => $request->user()->id,
                'expires_at' => now()->addDays(2), 'accepted_at' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->audit($request, 'admin.invited', null, ['email' => $data['email'], 'marketplace_user_id' => $user?->id]);
        });

        return back()->with('invitation_url', route('admin.invitation.show', $token))->with('status', 'Invitación creada. Comparte el enlace privado con la persona; caduca en 48 horas.');
    }

    public function invitation(string $token)
    {
        $invitation = DB::table('admin_invitations')->where('token_hash', hash('sha256', $token))->whereNull('accepted_at')->where('expires_at', '>', now())->first();
        abort_unless($invitation, 404);

        return view('auth.admin-invitation', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token)
    {
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        $admin = DB::transaction(function () use ($data, $token) {
            IdentityContacts::lock();
            $invitation = DB::table('admin_invitations')->where('token_hash', hash('sha256', $token))->lockForUpdate()->first();
            abort_unless($invitation && ! $invitation->accepted_at && now()->lt($invitation->expires_at), 422, 'La invitación caducó o ya fue utilizada.');
            abort_unless(AdminUser::whereKey($invitation->invited_by)->where('active', true)->where('role', 'superadmin')->exists(), 403);
            $user = $invitation->user_id ? User::lockForUpdate()->findOrFail($invitation->user_id) : null;
            $collision = User::whereNull('migrated_to_admin_at')->where(fn ($q) => $q->where('email', $invitation->email)->orWhere('phone', $invitation->phone))
                ->when($user, fn ($q) => $q->whereKeyNot($user->id))->exists();
            abort_if($collision, 422, 'Se registró otra cuenta con estos datos. Solicita una invitación actualizada.');
            if ($user && Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages(['password' => 'Usa una contraseña distinta de la de tu cuenta de Plaza Local.']);
            }
            abort_if($user && ($user->email !== $invitation->email || $user->phone !== $invitation->phone), 422, 'Los datos de la cuenta cambiaron. Solicita otra invitación.');
            abort_if(AdminUser::where('email', $invitation->email)->orWhere('phone', $invitation->phone)->exists(), 422, 'Ya existe una identidad administrativa con esos datos.');
            $admin = AdminUser::create(['name' => $invitation->name, 'email' => $invitation->email, 'phone' => $invitation->phone, 'password' => $data['password'], 'active' => true]);
            if ($user) {
                AccountIdentityLink::create(['admin_user_id' => $admin->id, 'user_id' => $user->id, 'approved_by_admin_id' => $invitation->invited_by,
                    'email' => $user->email, 'phone' => $user->phone, 'approved_at' => now()]);
            }
            DB::table('admin_invitations')->where('id', $invitation->id)->update(['accepted_at' => now()]);

            return $admin;
        });
        $request->session()->invalidate();
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.verification.notice');
    }

    public function toggle(Request $request, AdminUser $admin)
    {
        abort_unless($request->user()->hasRole('superadmin'), 403);
        abort_if($admin->hasRole('superadmin'), 403, 'El superadministrador no se modifica desde esta pantalla.');
        DB::transaction(function () use ($admin, $request) {
            $admin->update(['active' => ! $admin->active]);
            $this->audit($request, $admin->active ? 'admin.reactivated' : 'admin.suspended', $admin->id);
        });

        return back()->with('status', 'Acceso administrativo actualizado.');
    }

    public function marketplace(Request $request)
    {
        return view('admin.marketplace-account', ['linked' => AccountIdentityLink::where('admin_user_id', $request->user()->id)->exists(),
            'communities' => Community::where('is_active', true)->orderBy('name')->get()]);
    }

    public function createMarketplace(Request $request)
    {
        $admin = $request->user();
        abort_unless($admin->hasVerifiedEmail() && $admin->phone_verified_at, 403);
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'community_id' => ['required', Rule::exists('communities', 'id')->where('is_active', true)]]);
        DB::transaction(function () use ($admin, $data) {
            IdentityContacts::lock();
            $admin = AdminUser::lockForUpdate()->findOrFail($admin->id);
            abort_unless($admin->active && $admin->hasVerifiedEmail() && $admin->phone_verified_at, 403);
            if (Hash::check($data['password'], $admin->password)) {
                throw ValidationException::withMessages(['password' => 'Usa una contraseña distinta de la administrativa.']);
            }
            abort_if(AccountIdentityLink::where('admin_user_id', $admin->id)->exists(), 422, 'Ya tienes una cuenta de Plaza Local vinculada.');
            abort_if(User::where('email', $admin->email)->orWhere('phone', $admin->phone)->exists(), 422, 'Ya existe una cuenta con estos datos. El superadministrador debe revisar el vínculo.');
            $user = User::create(['name' => $admin->name, 'email' => $admin->email, 'phone' => $admin->phone, 'password' => $data['password'],
                'community_id' => $data['community_id'], 'account_type' => 'client']);
            $user->forceFill(['email_verified_at' => $admin->email_verified_at, 'phone_verified_at' => $admin->phone_verified_at])->save();
            $user->assignRole(Role::findOrCreate('client', 'web'));
            AccountIdentityLink::create(['admin_user_id' => $admin->id, 'user_id' => $user->id, 'email' => $admin->email, 'phone' => $admin->phone, 'approved_at' => now()]);
        });

        return back()->with('status', 'Cuenta de Plaza Local creada. Entra desde el login de la plaza con tu nueva contraseña.');
    }

    private function audit(Request $request, string $action, ?int $subject, array $metadata = []): void
    {
        AuditLog::create(['admin_user_id' => $request->user()->id, 'action' => $action, 'subject_type' => AdminUser::class,
            'subject_id' => $subject, 'metadata' => $metadata, 'ip_address' => $request->ip(), 'created_at' => now()]);
    }
}
