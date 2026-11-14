<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'type',
        'visibility_policy',
        'status',
        'deactivation_reason',
        'deactivation_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'deactivation_requested_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'org_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'org_id');
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(JoinRequest::class, 'org_id');
    }
}