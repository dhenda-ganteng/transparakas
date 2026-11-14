<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Aturan T-02: Audit log bersifat hanya-baca (tidak boleh diubah atau dihapus)
        static::updating(function () {
            throw new LogicException('Audit log bersifat hanya-baca dan tidak boleh diubah (Aturan T-02).');
        });

        static::deleting(function () {
            throw new LogicException('Audit log bersifat hanya-baca dan tidak boleh dihapus (Aturan T-02).');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $orgId = null,
        ?int $userId = null
    ): self {
        return self::withoutGlobalScopes()->create([
            'org_id' => $orgId ?? auth()->user()?->currentOrgId(),
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}