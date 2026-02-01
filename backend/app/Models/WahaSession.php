<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WahaSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'session_name',
        'phone_number',
        'status',
        'last_seen_at',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'last_seen_at' => 'datetime',
    ];
}
