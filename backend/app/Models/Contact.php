<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'phone',
        'name',
        'email',
        'lead_status',
        'vehicle_interest',
        'budget',
        'source',
        'metadata',
        'assigned_to',
    ];

    protected $casts = [
        'metadata' => 'array',
        'budget' => 'decimal:2',
    ];

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(Segment::class, 'contact_segment');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(ContactSequence::class);
    }
}
