<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'provider_suspension' => 'Revisión de perfil suspendido',
        'provider_verification' => 'Verificación de proveedor',
        'payment' => 'Pago o reembolso',
        'account' => 'Cuenta y acceso',
        'report' => 'Reporte de seguridad',
        'general' => 'Ayuda general',
    ];

    protected $fillable = ['admin_user_id',
        'user_id', 'vendor_id', 'assigned_admin_id', 'category', 'subject', 'status',
        'last_message_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'last_message_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function getReferenceAttribute(): string
    {
        return 'PLZ-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Ayuda general';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
