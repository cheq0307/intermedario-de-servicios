<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model
{
    protected $fillable = [
        'postal_code', 'settlement', 'settlement_type', 'municipality', 'state', 'city',
        'state_code', 'municipality_code', 'settlement_code',
    ];
}
