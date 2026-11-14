<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        // Otomatis filter WHERE org_id sesuai organisasi user yang sedang login
        static::addGlobalScope('organization', function (Builder $builder) {
            if (Auth::check()) {
                $orgId = Auth::user()->currentOrgId();
                // Jika user belum punya org_id (atau Super Admin T-04), kunci agar tidak melihat data keuangan
                $builder->where($builder->getModel()->getTable() . '.org_id', $orgId ?? 0);
            }
        });

        // Otomatis isi org_id saat membuat record baru
        static::creating(function ($model) {
            if (empty($model->org_id) && Auth::check() && Auth::user()->currentOrgId()) {
                $model->org_id = Auth::user()->currentOrgId();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}