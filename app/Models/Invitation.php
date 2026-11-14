<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = [
        'org_id',
        'code',
        'default_role',
        'expires_at',
        'revoked_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function generateUniqueCode(): string
    {
        do {
            // Kode acak 10 karakter huruf besar + angka (T-05: tidak berurutan & tidak mudah ditebak)
            $code = strtoupper(Str::random(10));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function isValid(): bool
    {
        return is_null($this->revoked_at) && $this->expires_at->isFuture();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}