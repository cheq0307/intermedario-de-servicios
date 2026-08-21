<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PostMedia extends Model
{
    protected $table = 'post_media';

    protected $fillable = ['post_id', 'type', 'path', 'disk', 'thumbnail_path', 'position', 'alt_text'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }
}
