<?php

namespace App\Models;

use App\Domain\Administration\AdminRole;
use App\Notifications\AdminResetPassword;
use App\Notifications\AdminVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;

class AdminUser extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'active'];

    protected $attributes = ['role' => 'admin', 'active' => true];

    protected $hidden = ['password', 'remember_token'];

    protected static function booted(): void
    {
        static::saving(function (self $admin): void {
            $admin->owner_slot = $admin->role === AdminRole::Superadmin ? 1 : null;
        });
    }

    protected function casts(): array
    {
        return ['password' => 'hashed', 'role' => AdminRole::class, 'active' => 'boolean',
            'email_verified_at' => 'datetime', 'phone_verified_at' => 'datetime'];
    }

    public function hasRole(string $role): bool
    {
        return $this->role->value === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role->value, $roles, true);
    }

    public function canUseMarketplace(): bool
    {
        return false;
    }

    public function isAccountActive(): bool
    {
        return $this->active;
    }

    public function canActAsClient(): bool
    {
        return false;
    }

    public function canActAsProvider(): bool
    {
        return false;
    }

    public function unreadConversationsCount(): int
    {
        return 0;
    }

    public function commercialRoleLabel(): string
    {
        return $this->hasRole('superadmin') ? 'Superadministrador' : 'Administrador';
    }

    public function avatarUrl(): ?string
    {
        return null;
    }

    public function ownsMarketplaceAccount(int $id): bool
    {
        return AccountIdentityLink::where('admin_user_id', $this->id)->where('user_id', $id)->exists();
    }

    public function sendEmailVerificationNotification(): void
    {
        $url = URL::temporarySignedRoute('admin.verification.verify', now()->addMinutes(60),
            ['id' => $this->id, 'hash' => sha1($this->email)]);
        $this->notify(new AdminVerifyEmail($url));
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AdminResetPassword($token));
    }
}
