<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\MessageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['admin_user_id',
        'conversation_id',
        'sender_id',
        'type',
        'body',
        'attachment_path',
        'metadata',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'metadata' => 'array',
            'edited_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
