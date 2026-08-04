<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\AccountType;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'account_type',
        'phone',
        'avatar_path',
        'bio',
        'city',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'account_type' => AccountType::class,
            'password' => 'hashed',
        ];
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'muted_until'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'subject_user_id');
    }

    public function jobProposals(): HasMany
    {
        return $this->hasMany(JobProposal::class, 'provider_id');
    }

    public function jobRequests(): HasMany
    {
        return $this->hasMany(JobRequest::class, 'client_id');
    }
}
