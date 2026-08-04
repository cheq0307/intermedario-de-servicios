<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'job_request_id',
        'provider_id',
        'amount',
        'currency',
        'message',
        'estimated_days',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
