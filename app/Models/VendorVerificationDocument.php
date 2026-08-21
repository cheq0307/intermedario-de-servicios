<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorVerificationDocument extends Model
{
    public const TYPE_GOVERNMENT_ID = 'government_id';
    public const TYPE_PROOF_OF_ADDRESS = 'proof_of_address';
    public const TYPE_BUSINESS_PROOF = 'business_proof';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUPERSEDED = 'superseded';
    public const TYPES = [
        'government_id' => 'Identificación oficial vigente',
        'proof_of_address' => 'Comprobante de domicilio reciente',
        'business_proof' => 'Constancia fiscal o evidencia del negocio',
    ];

    public const STATUSES = ['pending' => 'Pendiente', 'approved' => 'Aprobado', 'rejected' => 'Rechazado', 'superseded' => 'Reemplazado'];

    protected $fillable = [
        'vendor_id', 'uploaded_by_user_id', 'reviewed_by_user_id', 'type', 'status',
        'disk', 'path', 'original_name', 'mime_type', 'size', 'sha256',
        'review_note', 'reviewed_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function vendor(): BelongsTo { return $this->belongsTo(Vendor::class); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by_user_id'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by_user_id'); }
    public function label(): string { return self::TYPES[$this->type] ?? $this->type; }
}