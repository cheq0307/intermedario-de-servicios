<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'width', 'height',
    ];

    // Storage details must not be exposed through JSON/broadcast payloads.
    protected $hidden = ['disk', 'path'];

    protected $attributes = ['disk' => 'local'];

    protected static function booted(): void
    {
        static::creating(function (self $attachment): void {
            $attachment->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
