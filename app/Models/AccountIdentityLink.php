<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountIdentityLink extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }
}
