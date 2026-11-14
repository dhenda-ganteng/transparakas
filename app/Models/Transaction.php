<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Transaction extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'org_id',
        'category_id',
        'budget_id',
        'transaction_date',
        'type',
        'amount',
        'description',
        'receipt_path',
        'status',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'is_flagged',
        'flag_note',
        'flagged_by',
        'flagged_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'is_flagged' => 'boolean',
            'flagged_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Aturan T-01: Dilarang menghapus transaksi secara permanen
        static::deleting(function () {
            throw new LogicException('Transaksi tidak boleh dihapus permanen (Aturan T-01). Gunakan fitur pembatalan.');
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function flagger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'flagged_by');
    }
}